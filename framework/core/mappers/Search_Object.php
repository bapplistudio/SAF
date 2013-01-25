<?php
namespace SAF\Framework;

abstract class Search_Object
{

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * Returns a new instance of a search-formatter object of given class
	 *
	 * This creates an object with unset properties, as only set properties are used for searches.
	 *
	 * @param $class_name string
	 * @return object
	 */
	public static function newInstance($class_name)
	{
		$class_name = Namespaces::fullClassName($class_name);
		$object = new $class_name();
		$class = Reflection_Class::getInstanceOf($class_name);
		$class->accessProperties();
		foreach (array_keys(get_object_vars($object)) as $property_name) {
			unset($object->$property_name);
		}
		$class->accessPropertiesDone();
		return $object;
	}

}
