<?php
namespace SAF\Framework\Widget\Validate;

use SAF\Framework\Controller\Default_Class_Controller;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\View;

/**
 * This controller enables object validation
 *
 * - checks if the object data follows all business rules,
 * - if there are errors, outputs an error view with error and warning messages,
 * - if there are warnings, outputs a confirmation view with warning messages
 * - if there are no errors/warnings or if the user confirmed, outputs a validated view
 * - for classes that have a "validated" status property, set its value to true
 */
class Validate_Controller implements Default_Class_Controller
{

	//-------------------------------------------------------------------------------------- VALIDATE
	const VALIDATE = 'validate';

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for the class controller, when no runFeatureName() method was found in it.
	 *
	 * Class controllers must implement this method if you want the controller to work.
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		$object = $parameters->getMainObject();
		$parameters = $parameters->getRawParameters();

		$validator = new Validator();
		$validator->validate($object);
		$parameters['validator'] = $validator;

		return View::run($parameters, $form, $files, get_class($object), self::VALIDATE);
	}

}
