<?php
namespace SAF\Framework\Traits;

use SAF\Framework\Tools\Date_Time;

/**
 * A trait for creation and modification date logged objects
 */
trait Date_Logged
{

	//---------------------------------------------------------------------------- $creation_datetime
	/**
	 * @link DateTime
	 * @var Date_Time
	 */
	public $creation_datetime;

	//------------------------------------------------------------------------ $modification_datetime
	/**
	 * @link DateTime
	 * @var Date_Time
	 */
	public $modification_datetime;

}