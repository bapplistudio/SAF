<?php
namespace SAF\Framework;

/**
 * The SQL where section of SQL queries builder
 */
class Sql_Where_Builder
{

	//---------------------------------------------------------------------------------------- $joins
	/**
	 * @var Sql_Joins
	 */
	private $joins;

	//------------------------------------------------------------------------------------- $sql_link
	/**
	 * Sql data link used for identifiers
	 *
	 * @var Sql_Link
	 */
	private $sql_link;

	//---------------------------------------------------------------------------------- $where_array
	/**
	 * Where array expression, indices are columns names
	 *
	 * @var array
	 */
	private $where_array;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct the SQL WHERE section of a query
	 *
	 * Supported columns naming forms are :
	 * column_name : column_name must correspond to a property of class
	 * column.foreign_column : column must be a property of class, foreign_column must be a property of column's var class
	 *
	 * @param $class_name  string base object class name
	 * @param $where_array array where array expression, indices are columns names
	 * @param $sql_link    Sql_Link
	 * @param $joins       Sql_Joins
	 */
	public function __construct(
		$class_name, $where_array = null, Sql_Link $sql_link = null, Sql_Joins $joins = null
	) {
		$this->joins       = $joins ? $joins : new Sql_Joins($class_name);
		$this->sql_link    = $sql_link ? $sql_link : Dao::current();
		$this->where_array = $where_array;
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->build();
	}

	//----------------------------------------------------------------------------------------- build
	/**
	 * Build SQL WHERE section, add add joins for search criterion
	 *
	 * @return string
	 */
	public function build()
	{
		$sql = is_null($this->where_array) ? "" : $this->buildPath("id", $this->where_array, "AND");
		return $sql ? " WHERE " . $sql : $sql;
	}

	//------------------------------------------------------------------------------------ buildArray
	/**
	 * Build SQL WHERE section for multiple where clauses
	 *
	 * @param $path        string Base property path for values (if keys are numeric or structure keywords)
	 * @param $array       array An array of where conditions
	 * @param $clause      string For multiple where clauses, tell if they are linked with "OR" or "AND"
	 * @return string
	 */
	private function buildArray($path, $array, $clause)
	{
		$sql = "";
		$first = true;
		foreach ($array as $key => $value) {
			if ($first) $first = false; else $sql .= " $clause ";
			$subclause = strtoupper($key);
			switch ($subclause) {
				case "NOT": $sql .= "NOT (" . $this->buildPath($path, $value, "AND") . ")";  break;
				case "AND": $sql .= $this->buildPath($path, $value, $subclause);             break;
				case "OR":  $sql .= "(" . $this->buildPath($path, $value, $subclause) . ")"; break;
				default:
					if (is_numeric($key)) {
						$build = $this->buildPath($path, $value, $clause);
					}
					else {
						$prefix = "";
						$master_path = (($i = strrpos($path, ".")) !== false) ? substr($path, 0, $i) : "";
						$property_name = ($i !== false) ? substr($path, $i + 1) : $path;
						$properties = $this->joins->getProperties($master_path);
						if (isset($properties[$property_name])) {
							$link = $properties[$property_name]->getAnnotation("link")->value;
							if ($link) {
								$prefix = ($master_path ? ($master_path . ".") : "") . $property_name . ".";
							}
						}
						$build = $this->buildPath($prefix . $key, $value, $clause);
					}
					if (!empty($build)) {
						$sql .= $build;
					}
					elseif (!empty($sql)) {
						$sql = substr($sql, 0, -strlen(" $clause "));
					}
			}
		}
		return $sql;
	}

	//----------------------------------------------------------------------------------- buildColumn
	/**
	 * @param $path   string
	 * @param $prefix string
	 * @return string
	 */
	public function buildColumn($path, $prefix = "")
	{
		$join = $this->joins->add($path);
		if (isset($join)) {
			if ($join->type === Sql_Join::LINK) {
				$column = $join->foreign_alias . ".`" . rLastParse($path, ".", 1, true) . "`";
			}
			else {
				$column = $join->foreign_alias . ".`" . $join->foreign_column . "`";
			}
		}
		else {
			list($master_path, $foreign_column) = Sql_Builder::splitPropertyPath($path);
			$column = ((!$master_path) || ($master_path === "id"))
				? ("t0.`" . $prefix . $foreign_column . "`")
				: ($this->joins->getAlias($master_path) . ".`" . $prefix . $foreign_column . "`");
		}
		return $column;
	}

	//----------------------------------------------------------------------------------- buildObject
	/**
	 * Build SQL WHERE section for an object
	 *
	 * @param $path        string Base property path pointing to the object
	 * @param $object      object The value is an object, which will be used for search
	 * @return string
	 */
	private function buildObject($path, $object)
	{
		if ($id = $this->sql_link->getObjectIdentifier($object)) {
			// object is linked to stored data : search with object identifier
			return $this->buildValue($path, $id, ($path == "id") ? "" : "id_");
		}
		// object is a search object : each property is a search entry, and must join table
		$this->joins->add($path);
		$array = array();
		$class = Reflection_Class::getInstanceOf(get_class($object));
		foreach ($class->accessProperties() as $property_name => $property) {
			if (isset($object->$property_name)) {
				$sub_path = $property_name;
				$array[$sub_path] = $object->$property_name;
			}
		}
		$class->accessPropertiesDone();
		$sql = $this->buildArray($path, $array, "AND");
		if (!$sql) {
			$sql = "FALSE";
		}
		return $sql;
	}

	//------------------------------------------------------------------------------------- buildPath
	/**
	 * Build SQL WHERE section for given path and value
	 *
	 * @param $path        string|integer Property path starting by a root class property (may be a numeric key, or a structure keyword)
	 * @param $value       mixed May be a value, or a structured array of multiple where clauses
	 * @param $clause      string For multiple where clauses, tell if they are linked with "OR" or "AND"
	 * @return string
	 */
	private function buildPath($path, $value, $clause)
	{
		if ($value instanceof Dao_Where_Function) {
			return $value->toSql($this, $path);
		}
		switch (gettype($value)) {
			case "NULL":   return "";
			case "array":  return $this->buildArray ($path, $value, $clause);
			case "object": return $this->buildObject($path, $value);
			default:       return $this->buildValue ($path, $value);
		}
	}

	//------------------------------------------------------------------------------------ buildValue
	/**
	 * Build SQL WHERE section for a unique value
	 *
	 * @param $path   string search property path
	 * @param $value  mixed search property value
	 * @param $prefix string Prefix for column name
	 * @return string
	 */
	private function buildValue($path, $value, $prefix = "")
	{
		$column = $this->buildColumn($path, $prefix);
		$is_like = Sql_Value::isLike($value);
		return $column . " " . ($is_like ? "LIKE" : "=") . " " . Sql_Value::escape($value, $is_like);
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * @return Sql_Joins
	 */
	public function getJoins()
	{
		return $this->joins;
	}

	//------------------------------------------------------------------------------------ getSqlLink
	/**
	 * Gets used Sql_Link as defined on constructor call
	 *
	 * @return Sql_Link
	 */
	public function getSqlLink()
	{
		return $this->sql_link;
	}

}
