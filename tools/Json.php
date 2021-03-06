<?php
namespace SAF\Framework\Tools;

use SAF\Framework\Builder;

/**
 * Json utility class
 */
class Json
{

	//---------------------------------------------------------------------------------- decodeObject
	/**
	 * @param $encoded_string string
	 * @param $class_name     string
	 * @return array|object
	 */
	public function decodeObject($encoded_string, $class_name = null)
	{
		return isset($class_name)
			? Builder::fromArray($class_name, json_decode($encoded_string, true))
			: json_decode($encoded_string);
	}

	//---------------------------------------------------------------------------------- encodeObject
	/**
	 * @param $object array|object
	 * @return string
	 */
	public function encodeObject($object)
	{
		return json_encode($object);
	}

}
