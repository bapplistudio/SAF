<?php
namespace SAF\Framework\Import;

use SAF\Framework\Builder;
use SAF\Framework\Controller\Default_Feature_Controller;
use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Controller\Tag;
use SAF\Framework\Controller\Target;
use SAF\Framework\Dao\File\Builder\Post_Files;
use SAF\Framework\Dao\File;
use SAF\Framework\Dao\File\Session_File\Files;
use SAF\Framework\Dao\File\Spreadsheet_File;
use SAF\Framework\Import;
use SAF\Framework\Import\Settings\Import_Settings;
use SAF\Framework\Import\Settings\Import_Settings_Builder;
use SAF\Framework\Session;
use SAF\Framework\Setting\Custom_Settings;
use SAF\Framework\Setting\Custom_Settings_Controller;
use SAF\Framework\Tools\Color;
use SAF\Framework\View;
use SAF\Framework\View\Html\Template;
use SAF\Framework\Widget\Button;
use SAF\Framework\Widget\Button\Has_General_Buttons;
use stdClass;

/**
 * Import preview controller
 */
class Import_Preview_Controller implements Default_Feature_Controller, Has_General_Buttons
{

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $class_name string object or class name
	 * @param $parameters array parameters
	 * @param $settings   Custom_Settings|Import_Settings always null (unused)
	 * @return Button[]
	 */
	public function getGeneralButtons($class_name, $parameters, Custom_Settings $settings = null)
	{
		return [
			Feature::F_WRITE => new Button(
				'Save', View::link($class_name, Feature::F_IMPORT, 'preview'),
				Feature::F_CUSTOM_SAVE, [new Color(Color::BLUE), Target::MAIN, Tag::SUBMIT]
			),
			Feature::F_DELETE => new Button(
				'Delete', View::link($class_name, Feature::F_IMPORT, 'preview', ['delete_name' => true]),
				Feature::F_CUSTOM_DELETE, [new Color(Color::RED), Target::MAIN, Tag::SUBMIT]
			)
		];
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 * @todo factorize
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		// convert form files to worksheets and session files
		if ($files) {
			/** @var $import Import */
			$import = $parameters->getMainObject(Import::class);
			$import->class_name = $class_name;
			$form = (new Post_Files())->appendToForm($form, $files);
			foreach ($form as $file) {
				if ($file instanceof File) {
					if (!isset($session_files)) {
						/** @var $session_files Files */
						$session_files = Builder::create(Files::class);
					}
					$excel = Spreadsheet_File::fileToArray($file->temporary_file_name, $errors);
					$worksheet_number = 0;
					foreach ($excel as $temporary_file_name => $worksheet) {
						if (filesize($temporary_file_name) > 1) {
							$import_worksheet = Builder::create(Import_Worksheet::class, [
								$worksheet_number ++,
								Import_Settings_Builder::buildArray($worksheet, $class_name),
								$csv_file = Builder::create(File::class, [$temporary_file_name])
							]);
							$import_worksheet->errors = $errors;
							$session_files->files[] = $csv_file;
							$import->worksheets[] = $import_worksheet;
						}
					}
					// only one file once
					break;
				}
			}
			if (isset($session_files)) {
				Session::current()->set($session_files);
			}
		}
		// convert from form and session files to worksheets
		else {
			/** @var $files File[] */
			$files = Session::current()->get(Files::class)->files;
			$parameters->unshift($import = Import_Builder_Form::build($form, $files));
			$import->class_name = $class_name;
		}
		// prepare parameters
		$parameters = $parameters->getObjects();
		$general_buttons = $this->getGeneralButtons($class_name, $parameters);
		if (
			isset($parameters['constant_remove'])
			&& (strtoupper($parameters['constant_remove'][0]) === $parameters['constant_remove'][0])
		) {
			$parameters['constant_remove'] = rParse($parameters['constant_remove'], DOT);
		}
		foreach ($import->worksheets as $worksheet) {
			// apply controller parameters
			if (
				isset($parameters['constant_add'])
				&& isset($worksheet->settings->classes[$parameters['constant_add']])
			) {
				$worksheet->settings->classes[$parameters['constant_add']]->addConstant();
			}
			if (
				isset($parameters['constant_remove'])
				&& isset(
					$worksheet->settings->classes[lLastParse($parameters['constant_remove'], DOT, 1, false)]
				)
			) {
				$worksheet->settings->classes[lLastParse($parameters['constant_remove'], DOT, 1, false)]
					->removeConstant(rLastParse($parameters['constant_remove'], DOT, 1, true));
			}
			Custom_Settings_Controller::applyParametersToCustomSettings(
				$worksheet->settings, array_merge($form, $parameters)
			);
		}
		// recover empty Import_Settings (after loading empty one)
		/** @var $files File[] */
		$files = Session::current()->get(Files::class)->files;
		foreach ($import->worksheets as $worksheet_number => $worksheet) {
			if (empty($worksheet->settings->classes)) {
				$file = $files[$worksheet_number];
				$array = $file->getCsvContent();
				$import->worksheets[$worksheet_number] = new Import_Worksheet(
					$worksheet_number, Import_Settings_Builder::buildArray($array, $class_name), $file
				);
			}
		}
		// get general buttons and customized import settings
		foreach ($import->worksheets as $worksheet_number => $worksheet) {
			$customized_import_settings = $worksheet->settings->getCustomSettings();
			$worksheet_general_buttons = $general_buttons;
			if (!isset($customized_import_settings[$worksheet->settings->name])) {
				unset($worksheet_general_buttons['delete']);
			}
			$parameters['custom'][$worksheet_number] = new stdClass();
			$parameters['custom'][$worksheet_number]->customized_lists = $customized_import_settings;
			$parameters['custom'][$worksheet_number]->general_buttons = $worksheet_general_buttons;
			$parameters['custom'][$worksheet_number]->settings = $worksheet->settings;
			$parameters['custom'][$worksheet_number]->aliases_property = Import_Array::getPropertiesAlias(
				$worksheet->settings->getClassName()
			);
			$parameters['custom'][$worksheet_number]->properties_alias = array_flip(
				$parameters['custom'][$worksheet_number]->aliases_property
			);
		}
		// view
		$parameters[Template::TEMPLATE] = 'importPreview';
		return View::run($parameters, $form, $files, $class_name, Feature::F_IMPORT);
	}

}
