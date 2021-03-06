<?php
namespace SAF\Framework\Dao\Cache;

use SAF\Framework\Tools\Date_Time;

/**
 * An object stored into the cache
 */
class Cached
{

	//----------------------------------------------------------------------------------------- $date
	/**
	 * When has it been accessed the last time
	 *
	 * @var Date_Time
	 */
	public $date;

	//--------------------------------------------------------------------------------------- $object
	/**
	 * The cached object
	 *
	 * @var object
	 */
	public $object;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $object object
	 */
	public function __construct($object = null)
	{
		if (isset($object)) {
			$this->object = $object;
		}
		if (!isset($this->date)) {
			$this->date = new Date_Time();
		}
	}

	//---------------------------------------------------------------------------------------- access
	/**
	 * Call this each time the object is accessed : its access date-time will be updated
	 */
	public function access()
	{
		$this->date = new Date_Time();
	}

}
