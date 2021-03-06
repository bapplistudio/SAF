<?php
namespace SAF\Framework\User\Group;

use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Session;
use SAF\Framework\Updater\Application_Updater;
use SAF\Framework\Updater\Updatable;

/**
 * User group administration plugin TEST
 *
 * Must be enabled if you enable a menu for administrators to configure user groups
 */
class Admin_Plugin implements Registerable, Updatable
{

	//-------------------------------------------------------------------------------------- register
	/**
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		/** @var $application_updater Application_Updater */
		$application_updater = Session::current()->plugins->get(Application_Updater::class);
		$application_updater->addUpdatable($this);
	}

	//---------------------------------------------------------------------------------------- update
	/**
	 * The full feature cache is update each time the application is updated
	 *
	 * @param $last_time integer
	 */
	public function update($last_time)
	{
		$feature_cache = new Feature_Cache();
		$feature_cache->reset();
	}

}
