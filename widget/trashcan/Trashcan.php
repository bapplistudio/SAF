<?php
namespace SAF\Framework\Widget;

use SAF\Framework\Plugin;

/**
 * The trashcan widget enables dropping objects for deletion
 */
class Trashcan implements Plugin
{

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'trashcan';
	}

	//------------------------------------------------------------------------------------------ drop
	/**
	 * Drop an object into the trashcan (store then delete)
	 *
	 * @param $object object
	 */
	public function drop($object)
	{
		echo '<pre>drop into trashcan ' . print_r($object, true) . '</pre>';
	}

	//----------------------------------------------------------------------------------------- store
	/**
	 * Store an object into the trashcan
	 *
	 * @param $object object
	 */
	public function store($object)
	{
		echo '<pre>store into trashcan ' . print_r($object, true) . '</pre>';
	}

}
