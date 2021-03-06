<?php
namespace SAF\Framework\Controller;

use SAF\Framework\Controller;

/**
 * A default class controller, called for a given feature
 */
interface Default_Class_Controller extends Controller
{

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
	public function run(Parameters $parameters, $form, $files, $class_name);

}
