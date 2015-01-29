<?php
namespace SAF\Framework\Updater;

use SAF\Framework\Application;
use SAF\Framework\Controller\Main;
use SAF\Framework\Controller\Needs_Main;
use SAF\Framework\Session;
use Serializable;

/**
 * The application updater plugin detects if the application needs to be updated, and launch updates
 * for all objects (independant or plugins) that process updates
 */
class Application_Updater implements Serializable
{

	//----------------------------------------------------------------- Application updater constants
	const LAST_UPDATE_FILE = 'last_update';
	const UPDATE_FILE      = 'update';

	//----------------------------------------------------------------------------------- $updatables
	/**
	 * An array of updatable objects or class names
	 *
	 * @var Updatable[]|string[]
	 */
	private $updatables = [];

	//---------------------------------------------------------------------------------- $update_time
	/**
	 * @var integer
	 */
	private $update_time;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Application updater constructor zaps the cache directory if '?Z' argument is sent
	 * This will result into a complete application cache rebuild
	 */
	public function __construct()
	{
		if (isset($_GET['Z'])) {
			@unlink($this->getLastUpdateFileName());
			touch(self::UPDATE_FILE);
		}
	}

	//---------------------------------------------------------------------------------- addUpdatable
	/**
	 * Adds an updatable object or class to the elements that need to be updated at each update
	 *
	 * This can be called before the application updater plugin is registered, all updatable objects
	 * will be kept
	 *
	 * @param $object Updatable|string object or class name
	 */
	public function addUpdatable($object)
	{
		$this->updatables[] = $object;
	}

	//------------------------------------------------------------------------------------ autoUpdate
	/**
	 * Check if application must be updated
	 *
	 * Update if update flag file found
	 * Does nothing if not
	 *
	 * @param $controller Main
	 * @return boolean true if updates were made
	 */
	public function autoUpdate(Main $controller)
	{
		if ($this->mustUpdate()) {
			$this->update($controller);
			$this->done();
			return true;
		}
		return false;
	}

	//------------------------------------------------------------------------------------------ done
	/**
	 * Tells the updater the update is done and application has been updated
	 *
	 * After this call, next call to mustUpdate() will return false, until next update is needed
	 */
	public function done()
	{
		$this->setLastUpdateTime($this->update_time);
		unset($this->update_time);
		@unlink(self::UPDATE_FILE);
	}

	//------------------------------------------------------------------------- getLastUpdateFileName
	/**
	 * @return string
	 */
	public function getLastUpdateFileName()
	{
		return Application::current()->getCacheDir() . SL . self::LAST_UPDATE_FILE;
	}

	//----------------------------------------------------------------------------- getLastUpdateTime
	/**
	 * @return integer last compile time
	 */
	public function getLastUpdateTime()
	{
		$file_name = $this->getLastUpdateFileName();
		if (!file_exists($file_name)) {
			return mktime(0, 0, 0, 0, 0, 0);
		}
		else {
			return filemtime($file_name);
		}
	}

	//------------------------------------------------------------------------------------ mustUpdate
	/**
	 * Returns true if the application must be updated
	 *
	 * @return boolean
	 */
	public function mustUpdate()
	{
		return file_exists(self::UPDATE_FILE);
	}

	//------------------------------------------------------------------------------------- serialize
	/**
	 * @return string the string representation of the object : only class names are kept
	 */
	public function serialize()
	{
		$updatables = [];
		foreach ($this->updatables as $updatable) {
			$updatables[] = is_object($updatable) ? get_class($updatable) : $updatable;
		}
		return serialize($updatables);
	}

	//----------------------------------------------------------------------------- setLastUpdateTime
	/**
	 * @param $update_time integer
	 */
	public function setLastUpdateTime($update_time)
	{
		$updated = $this->getLastUpdateFileName();
		touch($updated, $update_time);
	}

	//---------------------------------------------------------------------------------------- update
	/**
	 * Updates all registered updatable objects
	 *
	 * You should prefer call autoUpdate() to update the application only if needed
	 *
	 * @param $main_controller Main
	 */
	public function update(Main $main_controller)
	{
		$last_update_time = $this->getLastUpdateTime();
		if (!isset($this->update_time)) {
			$this->update_time = time();
		}
		foreach ($this->updatables as $key => $updatable) {
			if (is_string($updatable)) {
				$updatable = Session::current()->plugins->get($updatable);
				$this->updatables[$key] = $updatable;
			}
			if ($updatable instanceof Needs_Main) {
				$updatable->setMainController($main_controller);
			}
			$updatable->update($last_update_time);
		}
	}

	//----------------------------------------------------------------------------------- unserialize
	/**
	 * @param $serialized string the string representation of the object
	 */
	public function unserialize($serialized)
	{
		$this->updatables = unserialize($serialized);
	}

}
