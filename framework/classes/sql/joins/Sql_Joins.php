<?php
namespace SAF\Framework;

class Sql_Joins
{

	//-------------------------------------------------------------------------------- $alias_counter
	/**
	 * alias counter for the next aliased table
	 *
	 * @var integer
	 */
	private $alias_counter;

	//-------------------------------------------------------------------------------------- $classes
	/**
	 * link property full path to their class name
	 *
	 * @var multitype:string indice is property full path
	 */
	private $classes = array();

	//---------------------------------------------------------------------------------------- $joins
	/**
	 * link property path to sql join
	 *
	 * @var multitype:Sql_Join indice is property full path
	 */
	private $joins = array();

	//----------------------------------------------------------------------------------- $properties
	/**
	 * link class names to their properties
	 *
	 * @var multitype:multitype:Reflection_Property indices are : class name, property name 
	 */
	private $properties = array();

	//-------------------------------------------------------------------------- $starting_class_name
	/**
	 * Starting class name : all properties path must start from this class
	 *
	 * @var string
	 */
	private $starting_class_name;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct Sql_Joins object and prepare joins for a list of property paths
	 *
	 * @param string $starting_class_name the class name for the root of property paths
	 * @param array $paths a property paths list to add at construction
	 */
	public function __construct($starting_class_name, $paths = array())
	{
		$this->alias_counter = 1;
		$this->classes[""] = $starting_class_name;
		$class = Reflection_Class::getInstanceOf($starting_class_name);
		$this->properties[$starting_class_name] = $class->getAllProperties();
		$this->starting_class_name = $starting_class_name;
		foreach ($paths as $path) {
			$this->add($path);
		}
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Adds a property path to the joins list
	 *
	 * @param string  $path full path to desired property, starting from starting class
	 * @param integer $depth for internal use : please do not use this
	 * @return Sql_Join the added join, or null if $path does not generate any join
	 */
	public function add($path, $depth = 0)
	{
		if (array_key_exists($path, $this->joins)) {
			return $this->joins[$path];
		}
		list($master_path, $master_property_name) = Sql_Builder::splitPropertyPath($path);
		if ($master_path && !isset($this->joins[$master_path])) {
			$this->add($master_path, $depth + 1);
		}
		$join = new Sql_Join();
		$foreign_class_name = (strpos($master_property_name, "->"))
			? $this->addReverseJoin($join, $master_property_name)
			: $this->addSimpleJoin($join, $master_path, $master_property_name, $path);
		$this->joins[$path] = $join->mode
			? $this->addFinalize($join, $master_path, $foreign_class_name, $path, $depth)
			: null;
		return $this->joins[$path];
	}

	//----------------------------------------------------------------------------------- addFinalize
	/**
	 * @param Sql_Join $join
	 * @param string   $master_path
	 * @param string   $foreign_class_name
	 * @param string   $foreign_path
	 * @param integer  $depth
	 */
	private function addFinalize(
		Sql_Join $join, $master_path, $foreign_class_name, $foreign_path, $depth
	) {
		if (!$depth) {
			$join->type = Sql_Join::OBJECT;
		}
		$join->foreign_alias = "t" . $this->alias_counter ++;
		if (!isset($join->foreign_table)) {
			$join->foreign_table = Dao::current()->storeNameOf($foreign_class_name);
		}
		if (!isset($join->master_alias)) {
			$join->master_alias = $master_path ? $this->getAlias($master_path) : "t0";
		}
		$this->classes[$foreign_path] = $foreign_class_name;
		$foreign_class = Reflection_Class::getInstanceOf($foreign_class_name);
		$this->properties[$foreign_class_name] = $foreign_class->getAllProperties();
		return $join;
	}

	//--------------------------------------------------------------------------------- addLinkedJoin
	/**
	 * @param Sql_Join $join
	 * @param string $master_path
	 * @param Reflection_Property $master_property
	 * @param string $foreign_path
	 * @param string $foreign_class_name
	 * @param string $foreign_property_name
	 */
	private function addLinkedJoin(
		Sql_Join $join, $master_path, Reflection_Property $master_property,
		$foreign_path, $foreign_class_name, $foreign_property_name
	) {
		$linked_join = new Sql_Join();
		$linked_join->foreign_column = "id_" . $foreign_property_name;
		if ($master_property->getAnnotation("master")->value) {
			$linked_join->foreign_table = Dao::storeNameOf($master_property->class)
				. "_" . Dao::storeNameOf($foreign_class_name)
				. "_links";
		} else {
			$linked_join->foreign_table = Dao::storeNameOf($foreign_class_name)
			. "_" . Dao::storeNameOf($master_property->class)
			. "_links";
		}
		$linked_join->master_column = "id";
		$linked_join->mode = $join->mode;
		$this->joins[$foreign_path . "-link"] = $this->addFinalize(
			$linked_join, $master_path, $foreign_class_name, $foreign_path, 1 
		);
		$join->foreign_column = "id";
		$join->master_column = "id_" . $master_property->getAnnotation("foreignlink");
		$join->master_alias = $linked_join->foreign_alias;
	}

	//----------------------------------------------------------------------------------- addMultiple
	/**
	 * Adds multiple properties paths to the joins list
	 *
	 * @param multitype:string $paths_array
	 * @return Sql_Joins
	 */
	public function addMultiple($paths_array)
	{
		foreach ($paths_array as $path) {
			$this->add($path);
		}
		return $this;
	}

	//-------------------------------------------------------------------------------- addReverseJoin
	/**
	 * @param Sql_Join $join
	 * @param string   $master_property_name
	 * @return string
	 */
	private function addReverseJoin(Sql_Join $join, $master_property_name)
	{
		list($foreign_class_name, $property) = explode("->", $master_property_name);
		if (strpos($property, "=")) {
			list($join->foreign_column, $join->master_column) = explode("=", $property);
			$join->foreign_column = "id_" . $join->foreign_column;
		} else {
			$join->foreign_column = "id_" . $property;
			$join->master_column = "id";
		}
		$join->mode = Sql_Join::LEFT;
		return $foreign_class_name;
	}

	//--------------------------------------------------------------------------------- addSimpleJoin
	/**
	 * @param Sql_Join $join
	 * @param string   $master_path
	 * @param string   $master_property_name
	 * @param string   $foreign_path
	 * @return string
	 */
	private function addSimpleJoin(Sql_Join $join, $master_path, $master_property_name, $foreign_path)
	{
		$foreign_class_name = null;
		$master_property = $this->getProperty($master_path, $master_property_name);
		if ($master_property) {
			$foreign_class_name = $master_property->getType();
			if ($foreign_class_name === "multitype:string") {
				// TODO : multitype:string can have multiple implementations, depending on database engine
				// linked strings table, mysql's set.. should find a way to make this common without
				// knowing anything about the specific
			}
			elseif (!Type::isBasic($foreign_class_name)) {
				$join->mode = $master_property->getAnnotation("mandatory")->value
					? Sql_Join::INNER
					: Sql_Join::LEFT;
				if ($single_class_name = Type::isMultiple($foreign_class_name)) {
					$foreign_class_name = $single_class_name;
					$foreign_property_name = $master_property->getAnnotation("foreign")->value;
					if (property_exists(
						Namespaces::fullClassName($foreign_class_name), $foreign_property_name
					)) {
						$join->foreign_column = "id_" . $foreign_property_name;
						$join->master_column  = "id";
					} else {
						$this->addLinkedJoin(
							$join, $master_path, $master_property,
							$foreign_path, $foreign_class_name, $foreign_property_name
						);
					}
				}
				else {
					$join->master_column  = "id_" . $master_property_name;
					$join->foreign_column = "id";
				}
			}
		}
		return $foreign_class_name;
	}

	//-------------------------------------------------------------------------------------- getAlias
	/**
	 * Gets foreign table alias for a given property path
	 *
	 * @param string $path
	 * @return string
	 */
	public function getAlias($path)
	{
		return $this->joins[$path]->foreign_alias;
	}
 
	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * Gets Sql_Join object for a given property path
	 *
	 * @param string $path full property path
	 * @return Sql_Join may be null if no join have been generated with $path
	 */
	public function getJoin($path)
	{
		return isset($this->joins[$path]) ? $this->joins[$path] : null;
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * Gets all joins object
	 *
	 * @return Sql_Join[] indiced by properties paths
	 */
	public function getJoins()
	{
		return $this->joins;
	}

	//--------------------------------------------------------------------------------- getProperties
	/**
	 * Gets the list of Reflection_Property objects for a given property path
	 *
	 * @param string $master_path
	 * @return multitype:Reflection_Property
	 */
	public function getProperties($master_path)
	{
		$class_name = isset($this->classes[$master_path]) ? $this->classes[$master_path] : null;
		return isset($this->properties[$class_name]) ? $this->properties[$class_name] : array();
	}

	//----------------------------------------------------------------------------------- getProperty
	/**
	 * Gets a Reflection_Property object for a given property path
	 *
	 * @param string $master_path
	 * @param string $property_name
	 * @return Reflection_Property
	 */
	private function getProperty($master_path, $property_name)
	{
		$properties = $this->getProperties($master_path);
		return isset($properties[$property_name]) ? $properties[$property_name] : null;
	}

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * A new instance of Sql_Joins, for those who don't lake the new Sql_Joins() syntax
	 *
	 * Construct Sql_Joins object and prepare joins for a list of property paths.
	 *
	 * @param string $starting_class_name the class name for the root of property paths
	 * @param array $paths a property paths list to add at construction
	 * @return Sql_Joins
	 */
	public static function newInstance($starting_class_name, $paths = array())
	{
		return new Sql_Joins($starting_class_name, $paths);
	}

}
