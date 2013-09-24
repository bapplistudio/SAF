<?php
namespace SAF\Framework;

/**
 * The default property remove controller does nothing : we must remove a property from a context
 */
class Property_Remove_Controller extends Default_Remove_Controller
{

	//------------------------------------------------------------------------ removePropertyFromList
	/**
	 * @param $class_name    string
	 * @param $property_path string
	 */
	public function removePropertyFromList($class_name, $property_path)
	{
		$list_controller = new Default_List_Controller();
		$list_settings = $list_controller->getListSettings($class_name);
		$list_settings->removeProperty($property_path);
		$list_settings->save();
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Call this to remove an element from a given class + feature context
	 *
	 * @param $parameters Controller_Parameters removal parameters
	 * - key 0 : context class name (ie a business class)
	 * - key 1 : context feature name (ie "output", "list")
	 * - keys 2 and more : the identifiers of the removed elements (ie property names)
	 * @param $form       array not used
	 * @param $files      array not used
	 * @return mixed
	 */
	public function run(Controller_Parameters $parameters, $form, $files)
	{
		$parameters = $parameters->getObjects();
		$parameters["class_name"]    = array_shift($parameters);
		$parameters["feature_name"]  = array_shift($parameters);
		$parameters["property_path"] = array_shift($parameters);
		array_unshift($parameters, new Property());
		if ($parameters["feature_name"] == "list") {
			$this->removePropertyFromList($parameters["class_name"], $parameters["property_path"]);
		}
		if ($parameters["feature_name"] == "form") {
			// ...
		}
		return View::run($parameters, $form, $files, 'SAF\Framework\Property', "removed");
	}

}
