<?php
namespace SAF\Framework;

/**
 * Default write controller
 */
class Default_Write_Controller implements Default_Class_Controller
{

	//-------------------------------------------------------------------- formElementToPropertyValue
	/**
	 * @param $property Reflection_Property
	 * @param $value    mixed
	 * @return mixed
	 */
	private function formElementToPropertyValue(Reflection_Property $property, $value)
	{
		if ($property->getType()->isBoolean()) {
			$value = !(empty($value) || ($value === "false"));
		}
		elseif (is_array($value) && ($property->getAnnotation("link")->value == "Collection")) {
			$value = arrayToCollection($value, $property->getType()->getElementTypeAsString(), false);
		}
		return $value;
	}

	//---------------------------------------------------------------------------------- formToObject
	/**
	 * Returns the object that form data represents
	 *
	 * @param $object object The object or class name to fill-in
	 * @param $form array    The form data
	 * @return object The result object (same as $object if it was an object)
	 */
	public function formToObject($object, $form)
	{
		if (is_string($object)) {
			$object = Builder::create($object);
		}
		$class = Reflection_Class::getInstanceOf($object);
		$properties = $class->accessProperties();
		foreach ($form as $name => $value) {
			if (isset($properties[$name])) {
				$object->$name = $this->formElementToPropertyValue($properties[$name], $value);
			}
			else {
				$object->$name = $value;
				if ((substr($name, 0, 3) == "id_")) {
					$name = substr($name, 3);
					if (isset($object->$name)) {
						unset($object->$name);
					}
				}
			}
		}
		$class->accessPropertiesDone();
		return $object;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for default "write-typed" controller
	 *
	 * Save data from the posted form into the first parameter object using standard method.
	 * Create a new instance of this object if no identifier was given.
	 *
	 * @param $parameters Controller_Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Controller_Parameters $parameters, $form, $files, $class_name)
	{
		$objects = $parameters->getObjects();
		$object = reset($objects);
		if (!$object || !is_object($object) || (get_class($object) !== $class_name)) {
			$object = Builder::create($class_name);
			$objects = array_merge(array($class_name => $object), $objects);
			$parameters->unshift($object);
		}
		$object = $this->formToObject($object, $form);
		Dao::begin();
		Dao::write($object);
		Dao::commit();
		return View::run($objects, $form, $files, $class_name, "written");
	}

}
