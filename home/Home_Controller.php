<?php
namespace SAF\Framework\Home;

use SAF\Framework\Application;
use SAF\Framework\Controller\Feature_Controller;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\View;

/**
 * Application home page view controller
 */
class Home_Controller implements Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files)
	{
		$parameters->unshift(Application::current());
		return View::run(
			$parameters->getObjects(), $form, $files, get_class(Application::current()), 'home'
		);
	}

}
