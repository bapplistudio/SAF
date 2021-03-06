<?php
namespace SAF\Framework\User;

use SAF\Framework\Controller\Feature_Controller;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Session;
use SAF\Framework\User;
use SAF\Framework\User\Authenticate\Authentication;
use SAF\Framework\View;

/**
 * Disconnects current user
 *
 * Do not call this if there is no current user.
 */
class Disconnect_Controller implements Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param Parameters $parameters
	 * @param array                 $form
	 * @param array                 $files
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files)
	{
		$parameters = $parameters->getObjects();
		$current_user = User::current();
		if (!isset($current_user)) {
			$current_user = new User();
		}
		Authentication::disconnect($current_user);
		array_unshift($parameters, $current_user);
		Session::current()->stop();
		return View::run($parameters, $form, $files, get_class($current_user), 'disconnect');
	}

}
