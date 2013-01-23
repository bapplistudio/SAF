<?php
namespace SAF\Framework;

class Default_List_Controller extends List_Controller
{

	//----------------------------------------------------------------------------- getGeneralButtons
	protected function getGeneralButtons($class_name)
	{
		return array(
			new Button("Add", View::link($class_name, "new"), "add")
		);
	}

	//----------------------------------------------------------------------------- getPropertiesList
	protected function getPropertiesList($class_name)
	{
		return Reflection_Class::getInstanceOf($class_name)->getAnnotation("representative")->value;
	}

	//------------------------------------------------------------------------------- getSearchValues
	/**
	 * Get search values from form's "search" array
	 *
	 * @param string $class_name element class name
	 * @param array $form the values, key is the name/path of each property into the class
	 * @return Reflection_Property_Value[] Search values
	 */
	protected function getSearchValues($class_name, $form)
	{
		$search = array();
		foreach ($form as $property_name => $value) {
			if (strlen($value)) {
				$property_name = str_replace(">", ".", $property_name);
				$search[$property_name] = new Reflection_Property_Value(
					$class_name, $property_name, $value
				);
			}
		}
		return $search;
	}

	//--------------------------------------------------------------------------- getSelectionButtons
	protected function getSelectionButtons($class_name)
	{
		return array(
			new Button("Print", View::link($class_name, "print"), "print")
		);
	}

	//----------------------------------------------------------------------------- getViewParameters
	protected function getViewParameters(Controller_Parameters $parameters, $form, $class_name)
	{
		$parameters = $parameters->getObjects();
		$element_class_name = Set::elementClassNameOf($class_name);
		$properties_list = $this->getPropertiesList($element_class_name);
		$search_values = $this->getSearchValues($element_class_name, $form);
		$search = isset($search_values)
			? array_merge(array_combine($properties_list, $properties_list), $search_values)
			: $properties_list;
		$parameters = array_merge(
			array(
				$element_class_name => Dao::select($element_class_name, $properties_list, $search_values),
				"search" => $search
			),
			$parameters
		);
		$parameters["general_buttons"]   = $this->getGeneralButtons($element_class_name);
		$parameters["selection_buttons"] = $this->getSelectionButtons($element_class_name);
		return $parameters;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for default "list-typed" view controller
	 *
	 * @param Controller_Parameters $parameters
	 * @param array  $form
	 * @param array  $files
	 * @param string $class_name
	 */
	public function run(Controller_Parameters $parameters, $form, $files, $class_name)
	{
		$parameters = $this->getViewParameters($parameters, $form, $class_name);
		View::run($parameters, $form, $files, $class_name, "list");
	}

}
