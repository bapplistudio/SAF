<?php
namespace SAF\Framework\Sql\Builder;

use SAF\Framework\Dao\Option;
use SAF\Framework\Dao\Sql\Link;
use SAF\Framework\Sql\Join\Joins;

/**
 * The SQL select queries builder
 */
class Select
{

	//----------------------------------------------------------------------------------- $class_name
	/**
	 * @var string
	 */
	private $class_name;

	//------------------------------------------------------------------------------ $columns_builder
	/**
	 * @var Columns
	 */
	private $columns_builder;

	//---------------------------------------------------------------------------------------- $joins
	/**
	 * @var Joins
	 */
	private $joins;

	//-------------------------------------------------------------------------------------- $options
	/**
	 * @var Option[]
	 */
	private $options;

	//------------------------------------------------------------------------------- $tables_builder
	/**
	 * @var Tables
	 */
	private $tables_builder;

	//-------------------------------------------------------------------------------- $where_builder
	/**
	 * @var Where
	 */
	private $where_builder;

	//---------------------------------------------------------------------- $additional_where_clause
	/**
	 * @var string
	 */
	private $additional_where_clause;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct a SQL SELECT query
	 *
	 * Supported columns naming forms are :
	 * - column_name : column_name must correspond to a property of class,
	 * - column.foreign_column : column must be a property of class, foreign_column must be a property
	 * of column's type class.
	 *
	 * @param $class_name  string        base object class name
	 * @param $properties  string[]|null properties paths list
	 * (default : all table columns will be read)
	 * @param $where_array array|object  where array expression, indices are columns names,
	 * or filter object
	 * @param $sql_link    Link
	 * @param $options     Option[] DAO options can be used for complex queries building
	 */
	public function __construct(
		$class_name, $properties = null, $where_array = null, Link $sql_link = null, $options = []
	) {
		$this->joins = $joins = new Joins($class_name);
		$this->class_name = $class_name;
		$this->columns_builder = new Columns($class_name, $properties, $joins);
		$this->tables_builder  = new Tables($class_name, $joins);
		$this->where_builder   = new Where($class_name, $where_array, $sql_link, $joins);
		$this->options = isset($options) ? (is_array($options) ? $options : [$options]) : [];
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->buildQuery();
	}

	//---------------------------------------------------------------------------------- buildOptions
	/**
	 * Builds optionnal SQL expressions, component of the SELECT query
	 *
	 * @return string[]
	 */
	private function buildOptions()
	{
		$options = [];
		foreach ($this->options as $option) {
			if ($option instanceof Option\Group_By) {
				$group_by = (new Columns(
					$this->class_name,
					$option->properties,
					$this->joins
				))->build();
				$options[10] = ' GROUP BY ' . $group_by;
			}
			elseif ($option instanceof Option\Sort) {
				$order_by = (new Columns(
					$this->class_name,
					$option->getColumns($this->class_name),
					$this->joins,
					['DESC' => $option->reverse]
				))->build();
				if ($order_by) {
					$options[20] = ' ORDER BY ' . $order_by;
				}
			}
			elseif ($option instanceof Option\Limit) {
				// todo this works only with Mysql so beware, this should be into Mysql or something
				$options[30] = ' LIMIT '
					. (isset($option->from) ? ($option->from - 1) . ', ' : '')
					. $option->count;
			}
			elseif ($option instanceof Option\Count) {
				$this->additional_where_clause = ' SQL_CALC_FOUND_ROWS';
			}
		}
		ksort($options);
		return $options;
	}

	//------------------------------------------------------------------------------------ buildQuery
	/**
	 * Build and returns the full SQL SELECT query
	 *
	 * @return string
	 */
	public function buildQuery()
	{
		// Call of buildOptions() and buildWhere() before buildColumns(), as all joins must be done to
		// correctly deal with all properties.
		// Call of buildColumns() and buildWhere() before buildTables(), to get joins ready.
		$this->additional_where_clause = '';
		$where   = $this->where_builder->build(true);
		$options = $this->buildOptions();
		$columns = $this->columns_builder->build();
		$tables  = $this->tables_builder->build();
		return $this->finalize($columns, $where, $tables, $options);
	}

	//-------------------------------------------------------------------------------------- finalize
	/**
	 * Finalize SQL query
	 *
	 * @param $columns string columns list, separated by ', '
	 * @param $tables  string tables list, including joins, without 'FROM'
	 * @param $where   string|string[] where clause, including ' WHERE ' or empty if no filter on read
	 * @param $options string[]
	 * @return string
	 */
	private function finalize($columns, $where, $tables, $options)
	{
		if (is_array($where)) {
			$sql = '';
			$options_inside = [];
			foreach ($options as $option) {
				if (
					(substr($option, 0, 10) !== ' ORDER BY ')
					&& (substr($option, 0, 7) !== ' LIMIT ')
				) {
					$options_inside[] = $option;
				}
			}
			foreach ($where as $sub_where) {
				if (!empty($sql)) {
					$sql .= LF . 'UNION ';
				}
				$sql .= $this->finalize($columns, $sub_where, $tables, $options_inside);
			}
			return 'SELECT * FROM (' . LF . $sql . ') t0 GROUP BY t0.id' . join('', $options);
		}
		return 'SELECT'
			. $this->additional_where_clause . SP
			. $columns . SP
			. 'FROM' . SP . $tables
			. $where
			. join('', $options);
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * @return Joins
	 */
	public function getJoins()
	{
		return $this->columns_builder->getJoins();
	}

	//------------------------------------------------------------------------------------ getSqlLink
	/**
	 * @return Link
	 */
	public function getSqlLink()
	{
		return $this->where_builder->getSqlLink();
	}

}