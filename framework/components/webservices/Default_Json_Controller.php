<?php
namespace SAF\Framework;

use StdClass;

/**
 * A default json controller to output any object or objects collection into json format
 */
class Default_Json_Controller implements Default_Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * Run the default json controller
	 *
	 * @param $parameters Controller_Parameters
	 * @param $form array
	 * @param $files array
	 * @param $class_name string
	 * @return string
	 */
	public function run(Controller_Parameters $parameters, $form, $files, $class_name)
	{
		$parameters = $parameters->getObjects();
		// read all objects corresponding to class name
		if (!$parameters) {
			return json_encode(Dao::readAll(Names::setToClass($class_name), Dao::sort()));
		}
		// read object
		$first_parameter = reset($parameters);
		if (is_object($first_parameter)) {
			return json_encode($first_parameter);
		}
		// search objects
		if (isset($parameters["term"])) {
			$element_class_name = Namespaces::fullClassName(Names::setToClass($class_name));
			$search = null;
			if (!empty($parameters["term"])) {
				$search = (new Search_Array_Builder)->buildMultiple(
					Reflection_Class::getInstanceOf($element_class_name), $parameters["term"], "%"
				);
			}
			if (isset($parameters["filters"])) {
				foreach ($parameters["filters"] as $filter_name => $filter_value) {
					$search[$filter_name] = $filter_value;
				}
				if (count($search) > 1) {
					$search = array("AND" => $search);
				}
			}
			$objects = array();
			foreach (Dao::search($search, $element_class_name, Dao::sort()) as $key => $source_object) {
				$object = new StdClass();
				$object->id = Dao::getObjectIdentifier($source_object);
				$object->value = strval($source_object);
				$objects[$key] = $object;
			}
			return json_encode($objects);
		}
		return "";
	}

}
