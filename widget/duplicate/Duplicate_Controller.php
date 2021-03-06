<?php
namespace SAF\Framework\Widget\Duplicate;

use SAF\Framework\Controller\Parameters;
use SAF\Framework\Dao\Duplicator;
use SAF\Framework\Widget\Edit\Edit_Controller;

/**
 * Default duplicate controller
 *
 * Opens an edit form, filled with the data of an object, but without it's ids
 */
class Duplicate_Controller extends Edit_Controller
{

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(Parameters $parameters, $form, $class_name)
	{
		$object = $parameters->getMainObject($class_name);
		$duplicator = new Duplicator();
		$duplicator->createDuplicate($object);
		return parent::getViewParameters($parameters, $form, $class_name);
	}

}
