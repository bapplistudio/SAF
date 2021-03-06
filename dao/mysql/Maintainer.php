<?php
namespace SAF\Framework\Dao\Mysql;

use mysqli;
use mysqli_result;
use SAF\Framework\AOP\Joinpoint\Before_Method;
use SAF\Framework\Dao;
use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Sql\Builder\Alter_Table;
use SAF\Framework\Sql\Builder\Create_Table;
use SAF\Framework\Tools\Contextual_Mysqli;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Namespaces;

/**
 * This is an intelligent database maintainer that automatically updates a table structure if there
 * is an error when executing a query.
 */
class Maintainer implements Registerable
{

	//-------------------------------------------------------------------------------------- $already
	/**
	 * All queries that have been already solved by the maintainer
	 * Used to avoid trying to solve the same query twice (real errors)
	 *
	 * @var integer[] key is the query, value is the solved counter
	 */
	private $already = [];

	//----------------------------------------------------------------------------------- createTable
	/**
	 * Create a table in database, using a data class structure
	 *
	 * @param $mysqli     Contextual_Mysqli
	 * @param $class_name string
	 */
	private function createTable(Contextual_Mysqli $mysqli, $class_name)
	{
		$builder = new Table_Builder_Class();
		$build = $builder->build($class_name);
		foreach ($build as $table) {
			if (!$mysqli->exists($table->getName())) {
				$last_context    = $mysqli->context;
				$mysqli->context = $builder->dependencies_context;
				$queries         = (new Create_Table($table))->build();
				foreach ($queries as $query) {
					$mysqli->query($query);
				}
				$mysqli->context = $last_context;
			}
		}
	}

	//--------------------------------------------------------------------------- createImplicitTable
	/**
	 * Create a table in database, which has no associated class, using fields names
	 *
	 * @param $mysqli       mysqli
	 * @param $table_name   string
	 * @param $column_names string[]
	 * @return boolean
	 * @todo mysqli context should contain sql builder (ie Select) in order to know if this was
	 *       an implicit link table. If then : only one unique index should be built
	 */
	private function createImplicitTable(mysqli $mysqli, $table_name, $column_names)
	{
		$only_ids = true;
		$table = new Table($table_name);
		$ids_index = new Index();
		$ids_index->setType(Index::UNIQUE);
		$indexes = [];
		foreach ($column_names as $column_name) {
			$table->addColumn(
				($column_name === 'id')
				? Column::buildId()
				: Column::buildLink($column_name)
			);
			if (substr($column_name, 0, 3) === 'id_') {
				if (($mysqli instanceof Contextual_Mysqli) && is_array($mysqli->context)) {
					$ids_index->addKey($column_name);
					$index = Index::buildLink($column_name);
					foreach ($mysqli->context as $context_class) {
						$id_context_property = 'id_' . Names::classToProperty(
							Names::setToSingle(Dao::storeNameOf($context_class))
						);
						$id_context_property_2 = 'id_' . Names::classToProperty(
							Names::setToSingle(Namespaces::shortClassName($context_class))
						);
						if (in_array($column_name, [$id_context_property, $id_context_property_2])) {
							$table->addForeignKey(
								Foreign_Key::buildLink($table_name, $column_name, $context_class)
							);
							break;
						}
					}
					$indexes[] = $index;
				}
			}
			else {
				$only_ids = false;
			}
		}
		if ($only_ids) {
			$table->addIndex($ids_index);
		}
		else {
			foreach ($indexes as $index) {
				$table->addIndex($index);
			}
		}
		foreach ((new Create_Table($table))->build() as $query) {
			$mysqli->query($query);
		}
		return true;
	}

	//--------------------------------------------------------------------- createTableWithoutContext
	/**
	 * Create table (probably links table) without context
	 *
	 * @param $mysqli     Contextual_Mysqli
	 * @param $table_name string
	 * @param $query      string
	 * @return boolean
	 */
	private function createTableWithoutContext(Contextual_Mysqli $mysqli, $table_name, $query)
	{
		$query = str_replace(LF, SP, $query);
		// if a class name exists for the table name, use it as context and create table from class
		$class_names = Dao::classNamesOf($table_name);
		foreach ($class_names as $class_name) {
			if (class_exists($class_name, false)) {
				if (isset($created)) {
					$this->updateTable($mysqli, $class_name);
				}
				else {
					$this->createTable($mysqli, $class_name);
				}
				$created = true;
			}
			if (isset($created)) {
				return true;
			}
		}
		// if no class name, create it from columns names in the query
		$column_names = [];
		$alias_pos = 0;
		while (true) {
			$alias_pos = strpos($query, BQ . $table_name . BQ . SP . 't', $alias_pos);
			if (!$alias_pos) {
				break;
			}
			$alias_pos += strlen($table_name) + 4;
			$alias = 't' . ((substr($query, $alias_pos)) + 0);
			$i = 0;
			while (($i = strpos($query, $alias . DOT, $i)) !== false) {
				$i += strlen($alias) + 1;
				$j                         = strpos($query, SP, $i) ?: strlen($query);
				$field_name                = trim(substr($query, $i, $j - $i), BQ);
				$column_names[$field_name] = $field_name;
			}
		}
		if (!$column_names) {
			if ($mysqli->isDelete($query)) {
				// @todo create table without context DELETE columns detection
				trigger_error(
					"TODO Mysql maintainer create table $table_name from a DELETE query without context",
					E_USER_ERROR
				);
			}
			elseif ($mysqli->isInsert($query)) {
				$column_names = explode(',', str_replace([BQ, SP], '', mParse($query, '(', ')')));
			}
			elseif ($mysqli->isSelect($query) || $mysqli->isExplainSelect($query)) {
				// @todo create table without context SELECT columns detection (needs complete sql analyst)
				trigger_error(
					"TODO Mysql maintainer create table $table_name from a SELECT query without context",
					E_USER_ERROR
				);
			}
			elseif ($mysqli->isTruncate($query)) {
				trigger_error(
					"Mysql maintainer can't create table $table_name from a TRUNCATE query without context",
					E_USER_ERROR
				);
				return false;
			}
			elseif ($mysqli->isUpdate($query)) {
				// @todo create table without context UPDATE columns detection
				trigger_error(
					"TODO Mysql maintainer create table $table_name from a UPDATE query without context",
					E_USER_ERROR
				);
			}
		}
		return $this->createImplicitTable($mysqli, $table_name, $column_names);
	}

	//---------------------------------------------------------------------------------- guessContext
	/**
	 * @param $query  string
	 * @param $mysqli Contextual_Mysqli
	 * @return string[]|null
	 */
	private function guessContext($query, Contextual_Mysqli $mysqli)
	{
		$context = [];
		if ($mysqli->isSelect($query) || $mysqli->isExplainSelect($query)) {
			// SELECT : first clause between `...` after FROM is the name of the table
			$table_name = mParse($query, ['FROM', BQ], BQ);
		}
		else {
			// DELETE, INSERT, UPDATE
			// first clause between `...` is probably the name of the table
			$table_name = mParse($query, BQ, BQ);
		}
		if ($table_name) {
			foreach (Dao::classNamesOf($table_name) as $class_name) {
				if (strpos($class_name, BS)) {
					$context[] = $class_name;
				}
			}
		}
		// every JOIN `...` may be the name of a table
		$joins = explode('JOIN ' . BQ, $query);
		array_shift($joins);
		foreach ($joins as $join) {
			$table_name = lParse($join, BQ);
			if ($table_name) {
				$class_names = Dao::classNamesOf($table_name);
				foreach ($class_names as $class_name) {
					if (strpos($class_name, BS)) {
						$context[] = $class_name;
					}
				}
			}
		}
		return $context ? $context : null;
	}

	//------------------------------------------------------------------------ onCantCreateTableError
	/**
	 * @param $mysqli Contextual_Mysqli
	 * @param $query  string
	 * @return boolean true if the query with an error can be retried after this error was dealt with
	 */
	private function onCantCreateTableError(Contextual_Mysqli $mysqli, $query)
	{
		$retry = false;
		$error_table_names = $this->parseNamesFromQuery($query);
		foreach ($error_table_names as $error_table_name) {
			if (!$mysqli->exists($error_table_name)) {
				$this->createImplicitTable($mysqli, $error_table_name, ['id']);
			}
			$retry = true;
		}
		return $retry;
	}

	//---------------------------------------------------------------------------- onMysqliQueryError
	/**
	 * This is called after each mysql query in order to update automatically database structure in
	 * case of errors
	 *
	 * @param $object    Contextual_Mysqli
	 * @param $query     string
	 * @param $result    mysqli_result|boolean
	 * @param $joinpoint Before_Method
	 */
	public function onMysqliQueryError(
		Contextual_Mysqli $object, $query, &$result, Before_Method $joinpoint
	) {
		$mysqli = $object;
		if ($mysqli->last_errno && !isset($this->already[$query])) {
			$last_errno = $mysqli->last_errno;
			$last_error = $mysqli->last_error;
			$this->already[$query] = 1;
			if (!isset($mysqli->context)) {
				$mysqli->context = $this->guessContext($query, $mysqli);
			}
			if (isset($mysqli->context)) {
				$retry = false;
				$context = is_array($mysqli->context) ? $mysqli->context : [$mysqli->context];
				$mysqli->last_errno = $last_errno;
				$mysqli->last_error = $last_error;
				if (
					($last_errno == Errors::ER_CANT_CREATE_TABLE)
					&& strpos($last_error, '(errno: 150)')
				) {
					$retry = $this->onCantCreateTableError($mysqli, $query);
				}
				elseif ($last_errno == Errors::ER_NO_SUCH_TABLE) {
					$retry = $this->onNoSuchTableError($mysqli, $query, $context);
				}
				elseif (
					in_array($last_errno, [Errors::ER_BAD_FIELD_ERROR, Errors::ER_CANNOT_ADD_FOREIGN])
				) {
					$retry = $this->updateContextTables($mysqli, $context);
				}
				if ($retry) {
					$result = $mysqli->query($query);
					if (!$mysqli->last_errno && !$mysqli->last_error) {
						$joinpoint->stop = true;
					}
				}
			}
			if (!$joinpoint->stop) {
				$mysqli->last_errno = $last_errno;
				$mysqli->last_error = $last_error;
			}
		}
	}

	//---------------------------------------------------------------------------- onNoSuchTableError
	/**
	 * @param $mysqli  Contextual_Mysqli
	 * @param $query   string
	 * @param $context string[]
	 * @return boolean true if the query with an error can be retried after this error was dealt with
	 */
	private function onNoSuchTableError(Contextual_Mysqli $mysqli, $query, $context)
	{
		$retry = false;
		$error_table_names = [$this->parseNameFromError($mysqli->last_error)];
		if (!reset($error_table_names)) {
			$error_table_names = $this->parseNamesFromQuery($query);
		}
		foreach ($context as $key => $context_class) {
			$context_table = is_array($context_class) ? $key : Dao::storeNameOf($context_class);
			if (in_array($context_table, $error_table_names)) {
				if (!is_array($context_class)) {
					$this->createTable($mysqli, $context_class);
				}
				else {
					$this->createImplicitTable($mysqli, $context_table, $context_class);
				}
				$retry = true;
			}
		}
		if (!$retry) {
			foreach ($error_table_names as $error_table_name) {
				$retry = $retry
					|| $this->createTableWithoutContext(
						$mysqli, $error_table_name, $query
					);
			}
			return $retry;
		}
		return $retry;
	}

	//---------------------------------------------------------------------------- parseNameFromError
	/**
	 * Gets the first name between '' from a mysqli error message
	 *
	 * ie table name or field name
	 *
	 * @param $error string
	 * @return string
	 */
	private function parseNameFromError($error)
	{
		$i = strpos($error, Q) + 1;
		$j = strpos($error, Q, $i);
		$name = substr($error, $i, $j - $i);
		if (strpos($name, DOT)) {
			$name = substr($name, strrpos($name, DOT) + 1);
		}
		if ((substr($name, 0, 1) == BQ) && (substr($name, -1) == BQ)) {
			$name = substr($name, 1, -1);
		}
		return $name;
	}

	//--------------------------------------------------------------------------- parseNamesFromQuery
	/**
	 * Parse an SQL query to get all table names
	 *
	 * @param $query string
	 * @return string[]
	 */
	private function parseNamesFromQuery($query)
	{
		$tables = [];
		$i = 0;
		while (($i = strpos($query, 'REFERENCES ', $i)) !== false) {
			$i = strpos($query, BQ, $i) + 1;
			$j = strpos($query, BQ, $i);
			$table_name = substr($query, $i, $j - $i);
			$tables[substr($query, $i, $j - $i)] = $table_name;
			$i = $j + 1;
		}
		return $tables;
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Registers the Mysql maintainer plugin
	 *
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$aop = $register->aop;
		$aop->beforeMethod([Contextual_Mysqli::class, 'queryError'], [$this, 'onMysqliQueryError']);
	}

	//--------------------------------------------------------------------------- updateContextTables
	/**
	 * @param $mysqli  Contextual_Mysqli
	 * @param $context string[]
	 * @return boolean true if the query with an error can be retried after this error was dealt with
	 */
	private function updateContextTables($mysqli, $context)
	{
		foreach ($context as $context_class) {
			if ($this->updateTable($mysqli, $context_class)) {
				$retry = true;
			}
		}
		return isset($retry);
	}

	//----------------------------------------------------------------------------------- updateTable
	/**
	 * Update table structure corresponding to a data class
	 *
	 * @param $mysqli mysqli
	 * @param $class_name string
	 * @return boolean true if an update query has been generated and executed
	 */
	public function updateTable(mysqli $mysqli, $class_name)
	{
		$result = false;
		foreach ((new Table_Builder_Class)->build($class_name) as $class_table) {
			$table_name = $class_table->getName();
			$mysql_table = Table_Builder_Mysqli::build($mysqli, $table_name);

			// create table
			if (!$mysql_table) {
				foreach ((new Create_Table($class_table))->build() as $query) {
					$mysqli->query($query);
				}
				$result = true;
			}

			// alter table
			else {
				$mysql_columns = $mysql_table->getColumns();
				$builder       = new Alter_Table($mysql_table);
				$alter_columns = [];
				$foreign_keys  = null;
				foreach ($class_table->getColumns() as $column) {
					$column_name = $column->getName();
					if (!isset($mysql_columns[$column_name])) {
						$builder->addColumn($column);
					}
					elseif (!$column->equiv($mysql_columns[$column_name])) {
						trigger_error(
							'Maintainer alters column ' . print_r($column, true)
								. print_r($mysql_columns[$column_name], true),
							E_USER_NOTICE
						);
						if (!isset($foreign_keys)) {
							$foreign_keys = Foreign_Key::buildTable($mysqli, $table_name);
						}
						$foreign_key = isset($foreign_keys[$column_name]) ? $foreign_keys[$column_name] : null;
						$builder->alterColumn($column_name, $column, $foreign_key);
						$alter_columns[$column_name] = $column_name;
					}
				}
				if ($builder->isReady()) {
					foreach ($builder->build(true) as $query) {
						$mysqli->query($query);
					}
					$result = true;
				}
			}

		}
		return $result;
	}

}
