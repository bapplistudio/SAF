<?php
namespace SAF\Framework\Webservice\Api;

use SAF\Framework\Controller\Default_Feature_Controller;
use SAF\Framework\Controller\Parameter;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Dao;
use SAF\Framework\Mapper\Object_Builder_Array;

/**
 * A common API to access / alter any business object
 * - create : create an object.
 *   Initialize properties values into parameters and/or form.
 *   The created object identifier is returned to the caller.
 */
class Api_Controller implements Default_Feature_Controller
{

	const CREATE = 'create';

	//---------------------------------------------------------------------------------------- create
	/**
	 * Create an object
	 *
	 * @param $object object
	 * @param $form   array
	 * @return integer
	 */
	private function create($object, $form)
	{
		Dao::begin();
		$builder = new Object_Builder_Array();
		$builder->null_if_empty_sub_objects = true;
		$builder->build($form, $object);
		foreach ($builder->getBuiltObjects() as $object) {
			Dao::write($object);
		}
		Dao::commit();
		return Dao::getObjectIdentifier($object);
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Run method for a feature controller working for any class
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		$feature = $parameters->shiftUnnamed();
		$form = array_merge($parameters->getRawParameters(), $form);
		if (isset($form[Parameter::AS_WIDGET])) {
			unset($form[Parameter::AS_WIDGET]);
		}
		$object = $parameters->getMainObject($class_name);
		switch ($feature) {
			case self::CREATE: return $this->create($object, $form);
		}
		trigger_error('Not a valid API action ' . $feature, E_USER_ERROR);
		return null;
	}

}
