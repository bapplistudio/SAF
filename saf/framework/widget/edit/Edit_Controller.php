<?php
namespace SAF\Framework\Widget\Edit;

use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Controller\Target;
use SAF\Framework\Tools\Color;
use SAF\Framework\Tools\Names;
use SAF\Framework\View;
use SAF\Framework\Widget\Button;
use SAF\Framework\Widget\Output\Output_Controller;

/**
 * The default edit controller, when no edit controller is set for the class
 */
class Edit_Controller extends Output_Controller
{

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $object     object|string object or class name
	 * @param $parameters array parameters
	 * @return Button[]
	 */
	public function getGeneralButtons($object, $parameters)
	{
		list($close_link, $follows) = $this->prepareThen(
			$object,
			$parameters,
			View::link(Names::classToSet(is_object($object) ? get_class($object) : $object))
		);
		$buttons = parent::getGeneralButtons($object, $parameters);
		unset($buttons[Feature::F_EDIT]);
		unset($buttons[Feature::F_PRINT]);
		$fill_combo = isset($parameters['fill_combo'])
			? ['fill_combo' => $parameters['fill_combo']]
			: [];
		return array_merge($buttons, [
			Feature::F_CLOSE => new Button(
				'Close',
				$close_link,
				Feature::F_CLOSE,
				[new Color(Feature::F_CLOSE), Target::MAIN]
			),
			Feature::F_WRITE => new Button(
				'Write',
				View::link($object, Feature::F_WRITE, null, array_merge($fill_combo, $follows)),
				Feature::F_WRITE,
				[new Color(Color::GREEN), Target::MESSAGES, '.submit']
			)
		]);
	}

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(Parameters $parameters, $form, $class_name)
	{
		$parameters->set('editing',        true);
		$parameters->set(Feature::FEATURE, Feature::F_EDIT);
		$parameters = parent::getViewParameters($parameters, $form, $class_name);
		$parameters['template_namespace'] = __NAMESPACE__;
		return $parameters;
	}

}
