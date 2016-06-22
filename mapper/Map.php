<?php
namespace SAF\Framework\Mapper;

use SAF\Framework\Dao;
use SAF\Framework\Dao\Option\Sort;
use SAF\Framework\Tools\List_Row;

/**
 * A map is an array of objects which the container object is linked to
 */
class Map
{

	//-------------------------------------------------------------------------------------- $objects
	/**
	 * @var object[]
	 */
	public $objects;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * A collection of objects of the same class, linked to the same data link
	 * Beware : $objects array is used as reference and will be altered by any changes made to the map
	 *
	 * @param $objects   object[]
	 * @param $key_is_id boolean Set this to true if your objects array use objects id as key
	 *                           This will enable an optimization to get this working faster
	 */
	public function __construct(&$objects = [], $key_is_id = false)
	{
		if (!$key_is_id) {
			$this->objects = $objects;
			$objects       = [];
			foreach ($this->objects as $key => $object) {
				$objects[Dao::getObjectIdentifier($object) ?: $key] = $object;
			}
		}
		$this->objects =& $objects;
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Add an object into an objects array
	 *
	 * @param $element object|object[]
	 */
	public function add($element)
	{
		if (is_array($element)) {
			foreach ($element as $elem) {
				$this->objects[Dao::getObjectIdentifier($elem)] = $elem;
			}
		}
		else {
			$this->objects[Dao::getObjectIdentifier($element)] = $element;
		}
	}

	//------------------------------------------------------------------------------------------- has
	/**
	 * Returns true if the objects array has the object
	 *
	 * @param $element object
	 * @return boolean
	 */
	public function has($element)
	{
		$key = Dao::getObjectIdentifier($element);
		return isset($this->objects[$key]) || array_key_exists($key, $this->objects);
	}

	//------------------------------------------------------------------------------------- intersect
	/**
	 * Returns the intersection of two objects maps
	 * Only objects which Dao identifier match into the two objects map are returned
	 *
	 * $only_first_common_element = true can be used for optimisation purpose if you are interested in
	 * knowing if there is at least one common element instead of getting all the intersection
	 * elements.
	 *
	 * @param $objects                   Map|object[]
	 * @param $only_first_common_element boolean If true : returns only the first common element
	 * @return Map|object[] the intersection of this set and linked elements / Set elements
	 * Returns a Map if $elements was a Map, or an object[] if $elements was an object[]
	 */
	public function intersect($objects, $only_first_common_element = false)
	{
		if ($objects instanceof Map) {
			$objects = $objects->objects;
			$returns_map = true;
		}
		foreach ($objects as $key => $object) {
			if (!isset($this->objects[Dao::getObjectIdentifier($object)])) {
				unset($objects[$key]);
			}
			elseif ($only_first_common_element) {
				$objects = [$key => $object];
				break;
			}
		}
		return isset($returns_map) ? new Map($objects) : $objects;
	}

	//---------------------------------------------------------------------------------------- remove
	/**
	 * Remove an object from an objects array
	 *
	 * @param $element object|object[]
	 */
	public function remove($element)
	{
		if (is_array($element)) {
			foreach ($element as $elem) {
				$key = Dao::getObjectIdentifier($elem);
				if (isset($this->objects[$key]) || array_key_exists($key, $this->objects)) {
					unset($this->objects[$key]);
				}
			}
		}
		else {
			$key = Dao::getObjectIdentifier($element);
			if (isset($this->objects[$key]) || array_key_exists($key, $this->objects)) {
				unset($this->objects[$key]);
			}
		}
	}

	//------------------------------------------------------------------------------------------ sort
	/**
	 * Sorts a collection of objects and returns the sorted objects collection
	 *
	 * @param $sort    Sort
	 * @return object[] the sorted objects collection
	 *
	 * @todo Dao_Sort_Option should become something as simple as Sort, used by Dao and Collection
	 */
	public function sort(Sort $sort = null)
	{
		if ($this->objects) {
			$object = reset($this->objects);
			if (!isset($sort)) {
				$sort = ($object instanceof List_Row)
					? new Sort($object->getClassName())
					: new Sort(get_class($object));
			}

			/**
			 * This patch disables the warning message on uasort when sort columns are objects which
			 * classes that are loaded by the autoloader inside the uasort().
			 * uasort does not like throw Exceptions inside of it. There may be some.
			 *
			 * This patch executes all the potential autoload before calling uasort, so we never get
			 * this warning message. To be removed when the PHP issue will be fixed.
			 *
			 * Caching of Parser::getAnnotationClassName was also needed : all classes must be
			 * included before calling uasort() to be sure this will work smoothly.
			 *
			 * @see https://bugs.php.net/bug.php?id=50688
			 * @see http://stackoverflow.com/questions/3235387
			 */
			if ($this->objects) {
				foreach ($sort->getProperties() as $sort_column => $property) {
					$type = $property->getType();
					if ($type->isClass()) {
						$type->asReflectionClass();
					}
				}
			}

			uasort($this->objects, function($object1, $object2) use ($sort)
			{
				if (($object1 instanceof List_Row) && ($object2 instanceof List_Row)) {
					$object1 = $object1->getObject();
					$object2 = $object2->getObject();
				}
				foreach ($sort->columns as $sort_column) {
					$reverse = isset($sort->reverse[strval($sort_column)]);
					while (($i = strpos($sort_column, DOT)) !== false) {
						$column = substr($sort_column, 0, $i);
						$object1 = isset($object1) ? $object1->$column : null;
						$object2 = isset($object2) ? $object2->$column : null;
						$sort_column = substr($sort_column, $i + 1);
					}
					$value1 = isset($object1) ? $object1->$sort_column : null;
					$value2 = isset($object2) ? $object2->$sort_column : null;
					$compare = $reverse ? -strnatcasecmp($value1, $value2) : strnatcasecmp($value1, $value2);
					if ($compare) return $compare;
				}
				return 0;
			});
		}
		return $this->objects;
	}

}
