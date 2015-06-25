<?php
namespace SAF\Framework\Dao\Mysql;

use mysqli_result;
use SAF\Framework\Builder;
use SAF\Framework\Dao;
use SAF\Framework\Dao\Option;
use SAF\Framework\Dao\Option\Only;
use SAF\Framework\Mapper\Abstract_Class;
use SAF\Framework\Mapper\Component;
use SAF\Framework\Mapper\Getter;
use SAF\Framework\Mapper\Null_Object;
use SAF\Framework\Mapper\Search_Object;
use SAF\Framework\Reflection\Annotation\Class_;
use SAF\Framework\Reflection\Annotation\Property\Link_Annotation;
use SAF\Framework\Reflection\Annotation;
use SAF\Framework\Reflection\Annotation\Sets\Replaces_Annotations;
use SAF\Framework\Reflection\Annotation\Template\Method_Annotation;
use SAF\Framework\Reflection\Link_Class;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Sql;
use SAF\Framework\Sql\Builder\Count;
use SAF\Framework\Sql\Builder\Map_Delete;
use SAF\Framework\Sql\Builder\Map_Insert;
use SAF\Framework\Sql\Builder\Select;
use SAF\Framework\Tools\Contextual_Mysqli;

/**
 * The mysql link for Dao
 */
class Link extends Dao\Sql\Link
{

	//----------------------------------------------------------------------------------- $connection
	/**
	 * Connection to the mysqli server is a mysqli object
	 *
	 * @var Contextual_Mysqli
	 */
	private $connection;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct a new Mysql using a parameters array, and connect to mysql database
	 *
	 * The $parameters array keys are : 'host', 'login', 'password', 'database'.
	 *
	 * @param $parameters array
	 */
	public function __construct($parameters = null)
	{
		parent::__construct($parameters);
		$this->connect($parameters);
	}

	//----------------------------------------------------------------------------------------- begin
	public function begin()
	{
		$this->query('START TRANSACTION');
	}

	//------------------------------------------------------------------------------------- construct
	/**
	 * Alternative constructor that enables configuration insurance
	 *
	 * @param $host     string
	 * @param $login    string
	 * @param $password string
	 * @param $database string
	 * @return Link
	 */
	public static function construct($host, $login, $password, $database)
	{
		return new Link([
			self::DATABASE => $database,
			self::HOST     => $host,
			self::LOGIN    => $login,
			self::PASSWORD => $password
		]);
	}

	//---------------------------------------------------------------------------------------- commit
	public function commit()
	{
		$this->query('COMMIT');
	}

	//--------------------------------------------------------------------------------------- connect
	/**
	 * @param $parameters string[]
	 */
	private function connect($parameters)
	{
		if (!isset($parameters[self::DATABASE]) && isset($parameters['databases'])) {
			$parameters[self::DATABASE] = str_replace('*', '', $parameters['databases']);
		}
		$this->connection = new Contextual_Mysqli(
			$parameters[self::HOST],     $parameters[self::LOGIN],
			$parameters[self::PASSWORD], $parameters[self::DATABASE]
		);
		$this->query('SET NAMES UTF8');
	}

	//----------------------------------------------------------------------------------------- count
	/**
	 * Count the number of elements that match filter
	 *
	 * @param $what       object|string|array source object, class name or properties for filter
	 * @param $class_name string must be set if is $what is a filter array instead of a filter object
	 * @return integer
	 */
	public function count($what, $class_name = null)
	{
		if (is_string($what)) {
			$class_name = $what;
			$what       = null;
		}
		$class_name = Builder::className($class_name);
		$builder = new Count($class_name, $what, $this);
		$query = $builder->buildQuery();
		$this->setContext($builder->getJoins()->getClassNames());
		$result_set = $this->connection->query($query);
		if ($result_set) {
			$row = $result_set->fetch_row();
			$result_set->free();
		}
		else {
			$row = [0];
		}
		return $row[0];
	}

	//--------------------------------------------------------------------------------- createStorage
	/**
	 * Create a storage space for $class_name objects
	 *
	 * If the storage space already exists, it is updated without losing data
	 *
	 * @param $class_name string
	 * @return boolean true if storage was created or updated, false if it was already up to date
	 */
	public function createStorage($class_name)
	{
		$class_name = Builder::className($class_name);
		return Maintainer::updateTable($this->connection, $class_name);
	}

	//---------------------------------------------------------------------------------------- delete
	/**
	 * Delete an object from current data link
	 *
	 * If object was originally read from data source, matching data will be overwritten.
	 * If object was not originally read from data source, nothing is done and returns false.
	 *
	 * @param $object object object to delete from data source
	 * @return boolean true if deleted
	 * @see Data_Link::delete()
	 */
	public function delete($object)
	{
		$will_delete = true;
		foreach (
			(new Reflection_Class(get_class($object)))->getAnnotations('before_delete') as $before_delete
		) {
			/** @var $before_delete Method_Annotation */
			if ($before_delete->call($object, [$this]) === false) {
				$will_delete = false;
				break;
			}
		}

		if ($will_delete) {
			$id = $this->getObjectIdentifier($object);
			if ($id) {
				$class_name = get_class($object);
				$class = new Reflection_Class($class_name);
				/** @var $link Class_\Link_Annotation */
				$link = $class->getAnnotation('link');
				$exclude_properties = $link->value
					? array_keys((new Reflection_Class($link->value))->getProperties([T_EXTENDS, T_USE]))
					: [];
				foreach ($class->accessProperties() as $property) {
					if (!$property->isStatic() && !in_array($property->name, $exclude_properties)) {
						if ($property->getAnnotation('link')->value == Link_Annotation::COLLECTION) {
							if ($property->getType()->isMultiple()) {
								$this->deleteCollection($object, $property, $property->getValue($object));
							}
							// TODO dead code ? @link Collection is only for @var object[], not for @var object
							else {
								$this->delete($property->getValue($object));
							}
						}
					}
				}
				$this->setContext($class_name);
				if ($link->value) {
					$id = [];
					foreach ($link->getLinkProperties() as $link_property) {
						$property_name = $link_property->getName();
						$column_name = ($link_property->getType()->isClass() ? 'id_' : '')
							. $link_property->getAnnotation('storage')->value;
						$id[$column_name] = $this->getObjectIdentifier($object, $property_name);
					}
				}
				$this->query(Sql\Builder::buildDelete($class_name, $id));
				$this->disconnect($object);
				return true;
			}
		}
		return false;
	}

	//------------------------------------------------------------------------------ deleteCollection
	/**
	 * Delete a collection of object
	 *
	 * This is called by delete() for linked object collection properties
	 *
	 * @param $parent object
	 * @param $property Reflection_Property
	 * @param $value mixed
	 */
	private function deleteCollection($parent, $property, $value)
	{
		$property_name = $property->name;
		$parent->$property_name = null;
		$old_collection = $parent->$property_name;
		$parent->$property_name = $value;
		if (isset($old_collection)) {
			foreach ($old_collection as $old_element) {
				$this->delete($old_element);
			}
		}
	}

	//---------------------------------------------------------------------------------- escapeString
	/**
	 * Escape string into string or binary values
	 *
	 * @param $value string|object
	 * @return string
	 */
	public function escapeString($value)
	{
		if (is_object($value)) {
			$id = $this->getObjectIdentifier($value, 'id');
			$value = is_numeric($id) ? $id : serialize($value);
		}
		return $this->connection->escape_string($value);
	}

	//----------------------------------------------------------------------------------------- fetch
	/**
	 * Fetch a result from a result set to an object
	 *
	 * @param $result_set mysqli_result The result set : in most cases, will come from query()
	 * @param $class_name string The class name to store the result data into
	 * @return object
	 */
	public function fetch($result_set, $class_name = null)
	{
		$object = $result_set->fetch_object(Builder::className($class_name));
		if ($object instanceof Abstract_Class) {
			$object = $this->read($this->getObjectIdentifier($object), $object->class);
		}
		return $object;
	}

	//-------------------------------------------------------------------------------------- fetchAll
	/**
	 * @param $class_name    string
	 * @param $options       Option[]
	 * @param $result_set    mysqli_result
	 * @return object[]
	 */
	protected function fetchAll($class_name, $options, $result_set)
	{
		$search_result = [];
		$keys = $this->getKeyPropertyName($options);
		if (($keys !== 'id') && isset($keys)) {
			if (is_array($keys)) {
				$object_key = [];
				foreach ($keys as $key => $value) {
					$keys[$key]       = explode(DOT, $value);
					$object_key[$key] = array_pop($keys[$key]);
				}
			}
			else {
				$keys       = explode(DOT, $keys);
				$object_key = array_pop($keys);
			}
		}
		$fetch_class_name = ((new Reflection_Class($class_name))->isAbstract())
			? Abstract_Class::class
			: $class_name;
		while ($object = $this->fetch($result_set, $fetch_class_name)) {
			$this->setObjectIdentifier($object, $object->id);
			// the most common key is the record id : do it quick
			if ($keys === 'id') {
				$search_result[$object->id] = $object;
			}
			// complex keys
			elseif (isset($keys) && isset($object_key)) {
				// result key must be a set of several id keys (used for linked classes collections)
				// (Dao::key(['property_1', 'property_2']))
				if (is_array($object_key)) {
					$k_key = '';
					foreach ($keys as $key => $k_keys) {
						$k_object_key = $object_key[$key];
						$key_object   = $object;
						foreach ($k_keys as $key) $key_object = $key_object->$key;
						$k_id_object_key = 'id_' . $k_object_key;
						$k_key .= ($k_key ? ',' : '') . (
							isset($key_object->$k_id_object_key)
								? ($k_object_key . '=' . $key_object->$k_id_object_key)
								: $key_object->$k_object_key
							);
					}
					$search_result[$k_key] = $object;
				}
				// result key must be a single id key (Dao::key('property_name'))
				else {
					$key_object = $object;
					foreach ($keys as $key) $key_object = $key_object->$key;
					$search_result[$key_object->$object_key] = $object;
				}
			}
			// we don't want a significant result key that (Dao::key(null))
			else {
				$search_result[] = $object;
			}
		}
		$this->free($result_set);
		return $search_result;
	}

	//-------------------------------------------------------------------------------------- fetchRow
	/**
	 * Fetch a result from a result set to an array
	 *
	 * @param $result_set mysqli_result The result set : in most cases, will come from query()
	 * @return object
	 */
	public function fetchRow($result_set)
	{
		$object = $result_set->fetch_row();
		return $object;
	}

	//------------------------------------------------------------------------------------------ free
	/**
	 * Free a result set
	 *
	 * Sql_Link inherited classes must implement freeing result sets only into this method.
	 *
	 * @param $result_set mysqli_result The result set : in most cases, will come from query()
	 */
	public function free($result_set)
	{
		$result_set->free();
	}

	//--------------------------------------------------------------------------------- getColumnName
	/**
	 * Gets the column name from result set
	 *
	 * Sql_Link inherited classes must implement getting column name only into this method.
	 *
	 * @param $result_set mysqli_result The result set : in most cases, will come from query()
	 * @param $index integer|string The index of the column we want to get the SQL name from
	 * @return string
	 */
	public function getColumnName($result_set, $index)
	{
		return $result_set->fetch_field_direct($index)->name;
	}

	//------------------------------------------------------------------------------- getColumnsCount
	/**
	 * Gets the column count from result set
	 *
	 * Sql_Link inherited classes must implement getting columns count only into this method.
	 *
	 * @param $result_set mysqli_result The result set : in most cases, will come from query()
	 * @return integer
	 */
	public function getColumnsCount($result_set)
	{
		return $result_set->field_count;
	}

	//--------------------------------------------------------------------------------- getConnection
	/**
	 * Gets raw connection object
	 *
	 * @return Contextual_Mysqli
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	//----------------------------------------------------------------------- getLinkObjectIdentifier
	/**
	 * Link classes objects identifiers are the identifiers of all their @composite properties values
	 * also known as link properties values.
	 *
	 * @param $object object
	 * @param $link   Class_\Link_Annotation send it for optimization, but this is not mandatory
	 * @return string identifiers in a single string, separated with '.'
	 */
	private function getLinkObjectIdentifier($object, Class_\Link_Annotation $link = null)
	{
		if (!isset($link)) {
			$link = (new Reflection_Class(get_class($object)))->getAnnotation('link');
		}
		if ($link->value) {
			$ids = '';
			foreach ($link->getLinkProperties() as $link_property) {
				$property_name = $link_property->getName();
				$id = parent::getObjectIdentifier($object, $property_name);
				if (!isset($id)) {
					if ($link->getLinkClass()->getCompositeProperty()->name == $property_name) {
						$id = isset($object->id) ? $object->id : null;
						if (!isset($id)) {
							return null;
						}
					}
					else {
						return null;
					}
				}
				$ids .= ($ids ? ',' : '') . $property_name . '=' . $id;
			}
			return $ids;
		}
		return null;
	}

	//--------------------------------------------------------------------------- getObjectIdentifier
	/**
	 * Used to get an object's identifier
	 *
	 * A null value will be returned for an object that is not linked to data link.
	 * If $object is already an identifier, the identifier is returned.
	 *
	 * @param $object        object an object to get data link identifier from
	 * @param $property_name string a property name to get data link identifier from instead of object
	 * @return mixed you can test if an object identifier is set with empty($of_this_result)
	 */
	public function getObjectIdentifier($object, $property_name = null)
	{
		return isset($property_name)
			? parent::getObjectIdentifier($object, $property_name)
			: ($this->getLinkObjectIdentifier($object) ?: parent::getObjectIdentifier($object));
	}

	//---------------------------------------------------------------------------------- getRowsCount
	/**
	 * Gets the count of rows read / changed by the last query
	 *
	 * Sql_Link inherited classes must implement getting rows count only into this method
	 *
	 * @param $result_set mixed The result set : in most cases, will come from query()
	 * @param $clause     string The SQL query was starting with this clause
	 * @param $options    Option[] If set, will set the result into Dao_Count_Option::$count
	 * @return integer will return null if $options is set but contains no Dao_Count_Option
	 */
	public function getRowsCount($result_set, $clause, $options = [])
	{
		if ($options) {
			foreach ($options as $option) {
				if ($option instanceof Option\Count) {
					$option->count = $this->getRowsCount($result_set, 'SELECT');
					return $option->count;
				}
			}
			return null;
		}
		else {
			if ($clause == 'SELECT') {
				$result = $this->connection->query('SELECT FOUND_ROWS()');
				$row = $result->fetch_row();
				$result->free();
				return $row[0];
			}
			return $this->connection->affected_rows;
		}
	}

	//--------------------------------------------------------------------------- getStoredProperties
	/**
	 * Returns the list of properties of class $class that are stored into data link
	 *
	 * If data link stores properties not existing into $class, they are listed too,
	 * as if they where official properties of $class, but they storage object is a Sql\Column
	 * and not a Reflection_Property.
	 *
	 * @param $class Reflection_Class
	 * @return Reflection_Property[]|Column[] key is the name of the property
	 */
	public function getStoredProperties($class)
	{
		$properties = $class->getProperties([T_EXTENDS, T_USE]);
		foreach ($properties as $key => $property) {
			$type = $property->getType();
			if ($property->isStatic() || ($type->isMultiple() && !$type->getElementType()->isBasic())) {
				unset($properties[$key]);
			}
			elseif ($type->isClass()) {
				$properties[$property->name] = new Column(
					'id_' . $property->getAnnotation('storage')->value
				);
			}
		}
		return $properties;
	}

	//----------------------------------------------------------------------------------------- query
	/**
	 * Executes an SQL query and returns the inserted record identifier or the mysqli result object
	 *
	 * - if $class_name is set and $query is a 'SELECT', returned value will be an object[]
	 * - if $query is a 'SELECT' without $class_name, then returned value will be a mysqli_result
	 * - if $query is an 'INSERT' returned value will be the last insert id
	 * - other cases : returned value make no sense : do not use it ! (may be null or last insert id)
	 *
	 * @param $query string
	 * @param $class_name string if set, the result will be object[] with read data
	 * @return integer|mysqli_result|object[]
	 */
	public function query($query, $class_name = null)
	{
		if ($query) {
			$result = $this->connection->query($query);
			if (isset($class_name)) {
				$class_name = Builder::className($class_name);
				$response = [];
				while ($object = $result->fetch_object($class_name)) {
					if (isset($object->id)) {
						$response[$object->id] = $object;
					}
					else {
						$response[] = $object;
					}
				}
				$result->free();
				return $response;
			}
			return $this->connection->isSelect($query) ? $result : $this->connection->insert_id;
		}
		else {
			return null;
		}
	}

	//------------------------------------------------------------------------------------------ read
	/**
	 * Read an object from data source
	 *
	 * @param $identifier integer identifier for the object
	 * @param $class_name string class for read object
	 * @return object an object of class objectClass, read from data source, or null if nothing found
	 */
	public function read($identifier, $class_name)
	{
		if (!$identifier) return null;
		$class_name = Builder::className($class_name);
		if ((new Reflection_Class($class_name))->getAnnotation('link')->value) {
			trigger_error(
				"You can't read a @link class with it's identifier : it has no identifier !",
				E_USER_ERROR
			);
		}
		// it's for optimisation purpose only
		$query = 'SELECT *'
			. LF . 'FROM' . SP . BQ . $this->storeNameOf($class_name) . BQ
			. LF . 'WHERE id = ' . $identifier;
		$this->setContext($class_name);
		$result_set = $this->connection->query($query);
		$object = $this->fetch($result_set, $class_name);
		$this->free($result_set);
		if ($object) {
			$this->setObjectIdentifier($object, $identifier);
		}
		return $object;
	}

	//--------------------------------------------------------------------------------------- readAll
	/**
	 * Read all objects of a given class from data source
	 *
	 * @param $class_name string class for read objects
	 * @param $options    Option[] some options for advanced read
	 * @return object[] a collection of read objects
	 */
	public function readAll($class_name, $options = [])
	{
		$class_name = Builder::className($class_name);
		$this->setContext($class_name);
		$query = (new Select($class_name, null, null, null, $options))->buildQuery();
		$result_set = $this->connection->query($query);
		if ($options) {
			$this->getRowsCount($result_set, 'SELECT', $options);
		}
		return $this->fetchAll($class_name, $options, $result_set);
	}

	//----------------------------------------------------------------------------- replaceReferences
	/**
	 * Replace all references to $replaced by references to $replacement into the database.
	 * Already loaded objects will not be changed.
	 *
	 * @param $replaced    object
	 * @param $replacement object
	 * @return boolean true if replacement has been done, false if something went wrong
	 */
	public function replaceReferences($replaced, $replacement)
	{
		$table_name = $this->storeNameOf(get_class($replaced));
		$replaced_id = $this->getObjectIdentifier($replaced);
		$replacement_id = $this->getObjectIdentifier($replacement);
		if ($replaced_id && $replacement_id && $table_name) {
			foreach (Foreign_Key::buildReferences($this->connection, $table_name) as $foreign_key) {
				$foreign_table_name = lParse($foreign_key->getConstraint(), DOT);
				$foreign_field_name = $foreign_key->getFields()[0];
				$query = 'UPDATE ' . BQ . $foreign_table_name . BQ
					. LF . 'SET ' . BQ . $foreign_field_name . BQ . ' = ' . $replacement_id
					. LF . 'WHERE ' . BQ . $foreign_field_name . BQ . ' = ' . $replaced_id;
				$this->query($query);
				if ($this->connection->last_errno) {
					$error = true;
				}
			}
			return isset($error) ? false : true;
		}
		return false;
	}

	//-------------------------------------------------------------------------------------- rollback
	/**
	 * Rollback a transaction (non-transactional MySQL engines as MyISAM will do nothing and return null)
	 *
	 * @return boolean|null true if commit succeeds, false if error, null if not a transactional SQL engine
	 */
	public function rollback()
	{
		$this->query('ROLLBACK');
	}

	//---------------------------------------------------------------------------------------- search
	/**
	 * Search objects from data source
	 *
	 * It is highly recommended to instantiate the $what object using Search_Object::instantiate() in order to initialize all properties as unset and build a correct search object.
	 * If some properties are an not-loaded objects, the search will be done on the object identifier, without joins to the linked object.
	 * If some properties are loaded objects : if the object comes from a read, the search will be done on the object identifier, without join. If object is not linked to data-link, the search is done with the linked object as others search criterion.
	 *
	 * @param $what       object|array source object for filter, or filter array (need class_name) only set properties will be used for search
	 * @param $class_name string must be set if $what is a filter array and not an object
	 * @param $options    Option[] some options for advanced search
	 * @return object[] a collection of read objects
	 */
	public function search($what, $class_name = null, $options = [])
	{
		if (!isset($class_name)) {
			$class_name = get_class($what);
		}
		$class_name = Builder::className($class_name);
		$builder = new Select($class_name, null, $what, $this, $options);
		$query = $builder->buildQuery();
		$this->setContext($builder->getJoins()->getClassNames());
		$result_set = $this->connection->query($query);
		if ($options) {
			$this->getRowsCount($result_set, 'SELECT', $options);
		}
		return $this->fetchAll($class_name, $options, $result_set);
	}

	//------------------------------------------------------------------------------------ setContext
	/**
	 * Set context for sql query
	 *
	 * @param $context_object string|string[] Can be a class name or an array of class names
	 */
	public function setContext($context_object = null)
	{
		$this->connection->context = $context_object;
	}

	//----------------------------------------------------------------------------------------- write
	/**
	 * Write an object into data source
	 *
	 * If object was originally read from data source, corresponding data will be overwritten
	 * If object was not originally read from data source nor linked to it using replace(), a new
	 * record will be written into data source using this object's data.
	 * If object is null (all properties null or unset), the object will be removed from data source
	 *
	 * TODO LOWEST factorize this to become SOLID
	 *
	 * @param $object  object object to write into data source
	 * @param $options Option[] some options for advanced write
	 * @return object the written object if written, or null if the object could not be written
	 */
	public function write($object, $options = [])
	{
		$will_write = true;
		foreach (
			(new Reflection_Class(get_class($object)))->getAnnotations('before_write') as $before_write
		) {
			/** @var $before_write Method_Annotation */
			if ($before_write->call($object, [$this, $options]) === false) {
				$will_write = false;
				break;
			}
		}

		if ($will_write) {

			if (Null_Object::isNull($object)) {
				$this->disconnect($object);
			}
			$class = new Link_Class(get_class($object));
			$id_property = 'id';
			do {
				/** @var $link Class_\Link_Annotation */
				$link = $class->getAnnotation('link');
				$table_columns_names = array_keys($this->getStoredProperties($class));
				$write_collections = [];
				$write_maps = [];
				$write = [];
				$aop_getter_ignore = Getter::$ignore;
				Getter::$ignore = true;
				$exclude_properties = $link->value
					? array_keys((new Reflection_Class($link->value))->getProperties([T_EXTENDS, T_USE]))
					: [];
				foreach ($options as $option) {
					if ($option instanceof Only) {
						$only = array_merge(isset($only) ? $only : [], $option->properties);
					}
				}
				/** @var $properties Reflection_Property[] */
				$properties = $class->accessProperties();
				$properties = Replaces_Annotations::removeReplacedProperties($properties);
				foreach ($properties as $property) {
					if (!isset($only) || in_array($property->name, $only)) {
						if (!$property->isStatic() && !in_array($property->name, $exclude_properties)) {
							$value = isset($object->$property) ? $property->getValue($object) : null;
							$property_is_null = $property->getAnnotation('null')->value;
							if (is_null($value) && !$property_is_null) {
								$value = '';
							}
							if (in_array($property->name, $table_columns_names)) {
								$element_type = $property->getType()->getElementType();
								// write basic
								if ($element_type->isBasic()) {
									$write[$property->getAnnotation('storage')->value] = $value;
								}
								elseif ($property->getAnnotation('store')->value == 'string') {
									$write[$property->getAnnotation('storage')->value] = strval($value);
								}
								// write object id if set or object if no id is set (new object)
								else {
									$column_name = 'id_' . $property->name;
									if (is_object($value)) {
										$value_class = new Link_Class(get_class($value));
										$id_value = (
											$value_class->getLinkedClassName()
											&& !$element_type->asReflectionClass()->getAnnotation('link')->value
										) ? 'id_' . $value_class->getCompositeProperty()->name
											: 'id';
										$object->$column_name = $this->getObjectIdentifier($value, $id_value);
										if (empty($object->$column_name)) {
											$object->$column_name = $this->getObjectIdentifier(
												$this->write($value), $id_value
											);
										}
									}
									$write['id_' . $property->getAnnotation('storage')->value]
										= ($property_is_null && !isset($object->$column_name))
											? null
											: intval($object->$column_name);
								}
							}
							// write collection
							elseif (
								is_array($value)
								&& ($property->getAnnotation('link')->value == Link_Annotation::COLLECTION)
							) {
								$write_collections[] = [$property, $value];
							}
							// write map
							elseif (
								is_array($value)
								&& ($property->getAnnotation('link')->value == Link_Annotation::MAP)
							) {
								foreach ($value as $key => $val) {
									if (!is_object($val)) {
										$val = Dao::read($val, $property->getType()->getElementTypeAsString());
										if (isset($val)) {
											$value[$key] = $val;
										}
										else {
											unset($value[$key]);
										}
									}
								}
								$write_maps[] = [$property, $value];
							}
						}
					}
				}
				unset($only);
				Getter::$ignore = $aop_getter_ignore;
				if ($write) {
					// link class : id is the couple of composite properties values
					if ($link->value) {
						$search = [];
						foreach ($link->getLinkProperties() as $property) {
							$property_name = $property->getName();
							$column_name = $property->getType()->isClass() ? 'id_' : '';
							$column_name .= $properties[$property_name]->getAnnotation('storage')->value;
							if (isset($write[$column_name])) {
								$search[$property_name] = $write[$column_name];
							}
							elseif (isset($write[$property_name])) {
								$search[$property_name] = $write[$column_name];
							}
							else {
								trigger_error("Can't search $property_name", E_USER_ERROR);
							}
						}
						if ($this->search($search, $class->name)) {
							$id = [];
							foreach ($search as $property_name => $value) {
								$column_name = $properties[$property_name]->getAnnotation('storage')->value;
								if (isset($write['id_' . $column_name])) {
									$column_name = 'id_' . $column_name;
								}
								$id[$column_name] = $value;
								unset($write[$column_name]);
							}
						}
						else {
							$id = null;
						}
					}
					// standard class : get the property 'id' value
					else {
						$id = $this->getObjectIdentifier($object, $id_property);
					}
					if ($write) {
						$this->setContext($class->name);
						if (empty($id)) {
							$this->disconnect($object);
							$id = $this->query(Sql\Builder::buildInsert($class->name, $write));
							if (!empty($id)) {
								$this->setObjectIdentifier($object, $id);
							}
						}
						else {
							$this->query(Sql\Builder::buildUpdate($class->name, $write, $id));
						}
					}
				}
				foreach ($write_collections as $write) {
					list($property, $value) = $write;
					$this->writeCollection($object, $property, $value);
				}
				foreach ($write_maps as $write) {
					list($property, $value) = $write;
					$this->writeMap($object, $property, $value);
				}
				// if link class : write linked object too
				$id_property = $link->value ? ('id_' . $class->getCompositeProperty()->name) : null;
				$class       = $link->value ? new Link_Class($link->value) : null;
			} while ($class && !Null_Object::isNull($object, $class->name));

			foreach (
				(new Reflection_Class(get_class($object)))->getAnnotations('after_write') as $after_write
			) {
				/** @var $after_write Method_Annotation */
				if ($after_write->call($object, [$this, $options]) === false) {
					break;
				}
			}

			return $object;
		}
		return null;
	}

	//------------------------------------------------------------------------------- writeCollection
	/**
	 * Write a component collection property value
	 *
	 * Ie when you write an order, it's implicitly needed to write it's lines
	 *
	 * @param $object     object
	 * @param $property   Reflection_Property
	 * @param $collection Component[]
	 */
	private function writeCollection($object, Reflection_Property $property, $collection)
	{
		// old collection
		$class_name = get_class($object);
		$old_object = Search_Object::create($class_name);
		$this->setObjectIdentifier($old_object, $this->getObjectIdentifier($object));
		$aop_getter_ignore = Getter::$ignore;
		Getter::$ignore = false;
		$old_collection = $property->getValue($old_object);
		Getter::$ignore = $aop_getter_ignore;

		$element_class = $property->getType()->asReflectionClass();
		/** @var $element_link Class_\Link_Annotation */
		$element_link = $element_class->getAnnotation('link');
		// collection properties : write each of them
		$id_set = [];
		if ($collection) {
			foreach ($collection as $key => $element) {
				if (!is_a($element, $element_class->getName())) {
					$collection[$key] = $element = Builder::createClone($element, $element_class->getName(), [
						$element_link->getLinkClass()->getCompositeProperty()->name => $element
					]);
				}
				$element->setComposite($object, $property->getAnnotation('foreign')->value);
				$id = $element_link->value
					? $this->getLinkObjectIdentifier($element, $element_link)
					: $this->getObjectIdentifier($element);
				if (!empty($id)) {
					$id_set[$id] = true;
				}
				$this->write($element);
			}
		}
		// remove old unused elements
		foreach ($old_collection as $old_element) {
			$id = $element_link->value
				? $this->getLinkObjectIdentifier($old_element, $element_link)
				: $this->getObjectIdentifier($old_element);
			if (!isset($id_set[$id])) {
				$this->delete($old_element);
			}
		}
	}

	//-------------------------------------------------------------------------------------- writeMap
	/**
	 * @param $object   object
	 * @param $property Reflection_Property
	 * @param $map      object[]
	 */
	private function writeMap($object, Reflection_Property $property, $map)
	{
		// old map
		$class = new Link_Class(get_class($object));
		$composite_property_name = $class->getAnnotation('link')->value
			? $class->getCompositeProperty()->name
			: null;
		$old_object = Search_Object::create(Link_Class::linkedClassNameOf($object));
		$this->setObjectIdentifier(
			$old_object, $this->getObjectIdentifier($object, $composite_property_name)
		);
		$aop_getter_ignore = Getter::$ignore;
		Getter::$ignore = false;
		$old_map = $property->getValue($old_object);
		Getter::$ignore = $aop_getter_ignore;
		// map properties : write each of them
		$insert_builder = new Map_Insert($property);
		$id_set = [];
		foreach ($map as $element) {
			$id = $this->getObjectIdentifier($element);
			if (!isset($old_map[$id])) {
				$query = $insert_builder->buildQuery($object, $element);
				$this->connection->query($query);
			}
			else {
				$id_set[$id] = true;
			}
		}
		// remove old unused elements
		$delete_builder = new Map_Delete($property);
		foreach ($old_map as $old_element) {
			$id = $this->getObjectIdentifier($old_element);
			if (!isset($id_set[$id])) {
				$query = $delete_builder->buildQuery($object, $old_element);
				$this->connection->query($query);
			}
		}
	}

}
