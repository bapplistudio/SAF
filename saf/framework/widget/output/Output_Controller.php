<?php
namespace SAF\Framework\widget\output;

use SAF\Framework\Controller\Default_Feature_Controller;
use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Print_Model;
use SAF\Framework\Tools\Color;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Namespaces;
use SAF\Framework\View;
use SAF\Framework\Widget\Button;
use SAF\Framework\Widget\Tab;
use SAF\Framework\Widget\Tab\Tabs_Builder_Object;

/**
 * All output controllers should extend from this at it offers standard output elements methods and structure
 */
class Output_Controller implements Default_Feature_Controller
{

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $object object|string object or class name
	 * @param $parameters string[] parameters
	 * @return Button[]
	 */
	protected function getGeneralButtons(
		$object, /** @noinspection PhpUnusedParameterInspection */ $parameters
	) {
		return [
			new Button('Close', View::link(Names::classToSet(get_class($object))), 'close',
				[new Color('close'), '#main']
			),
			new Button('Edit', View::link($object, 'edit'), 'edit',
				[new Color('green'), '#main']
			),
			new Button('Print', View::link($object, 'print'), 'print',
				[new Color('blue'), '#main', 'sub_buttons' => [
					new Button(
						'Models',
						View::link(
							Names::classToSet(Print_Model::class), Feature::F_LIST,
							Namespaces::shortClassName(get_class($object))
						),
						'models',
						'#main'
					)
				]]
			)
		];
	}

	//----------------------------------------------------------------------------- getPropertiesList
	/**
	 * @param $class_name string
	 * @return string[] property names list
	 */
	protected function getPropertiesList(
		/** @noinspection PhpUnusedParameterInspection */
		$class_name
	) {
		return null;
	}

	//--------------------------------------------------------------------------------------- getTabs
	/**
	 * Get output tabs list for a given object
	 *
	 * @param $object object
	 * @param $properties string[] Can be null
	 * @return Tab[]
	 */
	protected function getTabs($object, $properties)
	{
		$tab = new Tab('main');
		$tab->includes = Tabs_Builder_Object::buildObject($object, $properties);
		return $tab;
	}

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(
		/** @noinspection PhpUnusedParameterInspection */
		Parameters $parameters, $form, $class_name
	) {
		$parameters = $parameters->getObjects();
		$object = reset($parameters);
		if (empty($object) || !is_object($object) || (get_class($object) !== $class_name)) {
			$object = new $class_name();
			$parameters = array_merge([$class_name => $object], $parameters);
		}
		$parameters['general_buttons']   = $this->getGeneralButtons($object, $parameters);
		$parameters['properties_filter'] = $this->getPropertiesList($class_name);
		$parameters['tabs']              = $this->getTabs($object, $parameters['properties_filter']);
		return $parameters;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for default output view controller
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		$parameters = $this->getViewParameters($parameters, $form, $class_name);
		return View::run($parameters, $form, $files, $class_name, Feature::F_OUTPUT);
	}

}