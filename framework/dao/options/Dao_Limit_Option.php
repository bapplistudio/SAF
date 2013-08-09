<?php
namespace SAF\Framework;

/**
 * A DAO limit option
 */
class Dao_Limit_Option implements Dao_Option
{

	//----------------------------------------------------------------------------------------- $from
	/**
	 * If set, Dao queries will start only from the $from'th element
	 *
	 * @example Dao::readAll('SAF\Framework\User', Dao::limit(2, 10));
	 * Will return 10 read users objects, starting with the second read user
	 * @var integer
	 */
	public $from;

	//---------------------------------------------------------------------------------------- $count
	/**
	 * If set, Dao queries will work only on $count elements
	 *
	 * @example Dao::readAll('SAF\Framework\User', Dao::limit(10));
	 * Will return the 10 first read users objects
	 * @mandatory
	 * @var integer
	 */
	public $count;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Constructs a DAO limit option
	 * if only one parameter is given, it will be the value for $count and $from will be null
	 *
	 * @example Dao::readAll('SAF\Framework\User', Dao::limit(2, 10));
	 * Will return 10 read users objects, starting with the second read user
	 * @example Dao::readAll('SAF\Framework\User', Dao::limit(10));
	 * Will return the 10 first read users objects
	 *
	 * @param $from  integer The offset of the first object to return
	 * (or the maximum number of objects to return if $count is null)
	 * @param $count integer The maximum number of objects to return
	 */
	public function __construct($from = null, $count = null)
	{
		if (isset($from)) {
			if (isset($count)) {
				$this->from = $from;
			}
			else {
				$this->count = $from;
			}
		}
		if (isset($count)) {
			$this->count = $count;
		}
	}

}