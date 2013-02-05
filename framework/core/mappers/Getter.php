<?php
namespace SAF\Framework;

abstract class Getter
{

	//---------------------------------------------------------------------------------------- getAll
	/**
	 * Generic getter for getting all objects of a given class
	 *
	 * @param $collection
	 * @param $element_class
	 * @return object[]
	 */
	public static function getAll($collection, $element_class)
	{
		if (!isset($collection)) {
			$collection = Dao::readAll($element_class);
		}
		return $collection;
	}

	//--------------------------------------------------------------------------------- getCollection
	/**
	 * Generic getter for a collection of objects
	 *
	 * @param $collection    Component[]|null actual value of the property (will be returned if not null)
	 * @param $element_class string|null      the class for each collection's object
	 * @param $parent        object           the parent object
	 * @return object[]
	 */
	public static function getCollection($collection, $element_class, $parent)
	{
		if (!isset($collection)) {
			if (Dao::getObjectIdentifier($parent)) {
				$search_element = Search_Object::newInstance($element_class);
				if ($search_element instanceof Component) {
					$search_element->setParent($parent);
				}
				$collection = Dao::search($search_element);
				if ($search_element instanceof Component) {
					/** @var Component[] $collection */
					// this to avoid getter calls on $element->getParent() call (parent is already loaded)
					foreach ($collection as $element) {
						$element->setParent($parent);
					}
				}
			}
			else {
				$collection = array();
			}
		}
		return $collection;
	}

	//---------------------------------------------------------------------------------------- getMap
	/**
	 * Generic getter for mapped objects
	 *
	 * @param $map      Component[]|null actual value of the property (will be returned if not null)
	 * @param $property Reflection_Property the source property for map reading
	 * @param $parent   object the parent object
	 * @return object[]
	 */
	public static function getMap($map, Reflection_Property $property, $parent)
	{
		if (!isset($map)) {
			if (Dao::getObjectIdentifier($parent)) {
				$map = Dao::search(
					array(get_class($parent) . "->" . $property->name => $parent),
					$property->getType()->getElementTypeAsString()
				);
			}
			else {
				$map = array();
			}
		}
		return $map;
	}

	//------------------------------------------------------------------------------------- getObject
	/**
	 * Generic getter for an object
	 *
	 * @param $object          mixed  actual value of the object (will be returned if already an object)
	 * @param $class_name      string the object class name
	 * @param $parent          object the parent object
	 * @param $parent_property string the parent property name
	 * @return object
	 */
	public static function getObject($object, $class_name, $parent = null, $parent_property = null)
	{
		if (!is_object($object)) {
			if (is_object($parent) && is_string($parent_property)) {
				$parent_property = "id_" . $parent_property;
				if (isset($parent->$parent_property)) {
					$object = $parent->$parent_property;
				}
			}
			if (isset($object)) {
				$object = Dao::read($object, $class_name);
			}
		}
		return $object;
	}

}
