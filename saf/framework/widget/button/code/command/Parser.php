<?php
namespace SAF\Framework\Widget\Button\Code\Command;

use SAF\Framework\Widget\Button\Code\Command;

/**
 * Command parser
 */
class Parser
{

	//----------------------------------------------------------------------------------------- parse
	/**
	 * @param $source string
	 * @param $condition boolean If true, consider the source is a condition
	 * @return Command|null null for nop
	 */
	public static function parse($source, $condition = false)
	{
		if (strpos($source, '=')) {
			list($property_name, $value) = explode('=', $source);
			if ($condition) {
				return new Equals(trim($property_name), trim($value));
			}
			else {
				return new Assign(trim($property_name), trim($value));
			}
		}
		return null;
	}

}