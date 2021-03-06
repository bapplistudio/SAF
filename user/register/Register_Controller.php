<?php
namespace SAF\Framework\User\Register;

use SAF\Framework\Builder;
use SAF\Framework\Controller\Feature_Controller;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\User;
use SAF\Framework\User\Authenticate\Authentication;
use SAF\Framework\View;
use SAF\Framework\View\Html\Template;

/**
 * The user register controller offers a registration form view
 */
class Register_Controller implements Feature_Controller
{

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(
		Parameters $parameters,
		/** @noinspection PhpUnusedParameterInspection */ $form,
		$class_name
	) {
		$parameters = $parameters->getObjects();
		$object = reset($parameters);
		if (empty($object) || !is_object($object) || !is_a($object, $class_name, true)) {
			$object = Builder::create($class_name);
			$parameters = array_merge([$class_name => $object], $parameters);
		}
		return $parameters;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files)
	{
		$current = User::current();
		if ($current) {
			Authentication::disconnect(User::current());
		}
		$parameters = $this->getViewParameters($parameters, $form, User::class);
		if (isset($form['login']) && isset($form['password'])) {
			$user = null;
			$errors_messages = Authentication::controlRegisterFormParameters($form);
			if (!$errors_messages && empty($errors_messages)) {
				if (Authentication::controlNameNotUsed($form['login'])) {
					$user = Authentication::register($form);
				}
			}
			if ($user) {
				$parameters[Template::TEMPLATE] = 'confirm';
				return View::run($parameters, $form, $files, User::class, 'register');
			}
			else {
				$parameters['errors'] = $errors_messages;
				$parameters[Template::TEMPLATE] = 'error';
				return View::run($parameters, $form, $files, User::class, 'register');
			}
		}
		else {
			$parameters['inputs'] = Authentication::getRegisterInputs();
			return View::run($parameters, $form, $files, User::class, 'register');
		}
	}

}
