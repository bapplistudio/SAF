<?php
namespace SAF\Framework;

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
			return json_encode(Dao::readAll(Names::setToClass($class_name, false), Dao::sort()));
		}
		// read object
		$first_parameter = reset($parameters);
		if (is_object($first_parameter)) {
			return json_encode($first_parameter);
		}
		// search objects for autocomplete combo pull-down list
		if (isset($parameters['term'])) {
			$element_class_name = Namespaces::fullClassName(Names::setToClass($class_name, false));
			$search = null;
			if (!empty($parameters['term'])) {
				$search = (new Search_Array_Builder)->buildMultiple(
					new Reflection_Class($element_class_name), $parameters['term'], '', '%'
				);
			}
			if (isset($parameters['filters'])) {
				foreach ($parameters['filters'] as $filter_name => $filter_value) {
					$search[$filter_name] = ($filter_value[0] == '!')
						? Dao_Func::notEqual(substr($filter_value, 1))
						: $filter_value;
				}
				if (count($search) > 1) {
					$search = ['AND' => $search];
				}
			}
			$objects = [];
			// first object only
			if (isset($parameters['first']) && $parameters['first']) {
				$objects = Dao::search($search, $element_class_name, [Dao::sort(), Dao::limit(1)]);
				$source_object = $objects ? reset($objects) : Builder::create($element_class_name);
				return json_encode(new Autocomplete_Entry(
					Dao::getObjectIdentifier($source_object), strval($source_object)
				));
			}
			// all results from search
			else {
				foreach (Dao::search($search, $element_class_name, Dao::sort()) as $source_object) {
					$objects[] = new Autocomplete_Entry(
						Dao::getObjectIdentifier($source_object), strval($source_object)
					);
				}
				return json_encode($objects);
			}
		}
		// single object for autocomplete pull-down list value
		elseif (isset($parameters['id'])) {
			$element_class_name = Namespaces::fullClassName(Names::setToClass($class_name));
			$source_object = Dao::read($parameters['id'], $element_class_name);
			return json_encode(new Autocomplete_Entry(
				Dao::getObjectIdentifier($source_object), strval($source_object)
			));
		}
		return '';
	}

}
