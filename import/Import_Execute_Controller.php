<?php
namespace SAF\Framework\Import;

use SAF\Framework\Controller\Default_Feature_Controller;
use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Dao\File\Session_File\Files;
use SAF\Framework\Session;
use SAF\Framework\View;
use SAF\Framework\View\Html\Template;

/**
 * Import execution controller
 */
class Import_Execute_Controller implements Default_Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * This will be called for this controller, always.
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		upgradeTimeLimit(900);
		$import = Import_Builder_Form::build(
			$form, Session::current()->get(Files::class)->files
		);
		$import->class_name = $class_name;
		$parameters->getMainObject($import);
		$parameters = $parameters->getObjects();
		foreach ($import->worksheets as $worksheet) {
			$array = $worksheet->file->getCsvContent();
			$import_array = new Import_Array($worksheet->settings, $import->class_name);
			$import_array->importArray($array);
		}
		$parameters[Template::TEMPLATE] = 'importDone';
		return View::run($parameters, $form, $files, $class_name, Feature::F_IMPORT);
	}

}
