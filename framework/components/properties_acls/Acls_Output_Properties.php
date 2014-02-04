<?php
namespace SAF\Framework;

/**
 * This plugin enables storage of properties available into output / edit views as acls
 */
class Acls_Output_Properties extends Acls_Properties implements Plugin
{

	//----------------------------------------------------------------- outputControllerGetProperties
	/**
	 * @param $class_name string
	 * @param $joinpoint  Around_Method_Joinpoint
	 * @return string[] property names list
	 */
	public function outputControllerGetProperties($class_name, Around_Method_Joinpoint $joinpoint)
	{
		$acls_output_properties = new Acls_Output_Properties($class_name);
		$properties = $acls_output_properties->getPropertiesNames("output");
		return (isset($properties))
			? $properties
			: $joinpoint->process($class_name);
	}

	//---------------------------------------------------------------------- propertyRemoveController
	/**
	 * Call this to remove an element from a given class + feature context
	 *
	 * @param $parameters Controller_Parameters removal parameters
	 * - key 0 : context class name (ie a business class)
	 * - key 1 : context feature name (ie "output", "list")
	 * - keys 2 and more : the identifiers of the removed elements (ie property names)
	 * @param $form       array not used
	 * @param $files      array not used
	 * @param $joinpoint  Around_Method_Joinpoint
	 * @return mixed
	 */
	public function propertyRemoveController(
		Controller_Parameters $parameters, $form, $files, Around_Method_Joinpoint $joinpoint
	) {
		if ($parameters->getRawParameter(1) == "edit") {
			$parameters->set(1, "output");
		}
		if ($parameters->getRawParameter(1) == "output") {
			$parameters->unshiftUnnamed(__CLASS__);
			return (new Acls_Property_Remove_Controller)->run($parameters, $form, $files);
		}
		else {
			return $joinpoint->process($parameters, $form, $files);
		}
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 *
	 * @param $register Plugin_Register
	 */
	public function register(Plugin_Register $register)
	{
		$aop = $register->aop;
		$aop->aroundMethod(
			array('SAF\Framework\Default_Output_Controller', "getPropertiesList"),
			array($this, "outputControllerGetProperties")
		);
		$aop->aroundMethod(
			array('SAF\Framework\Property_Remove_Controller', "run"),
			array($this, "propertyRemoveController")
		);
	}

}
