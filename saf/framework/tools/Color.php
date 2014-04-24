<?php
namespace SAF\Framework\Tools;

/**
 * Colors manager
 */
class Color
{

	//---------------------------------------------------------------------------------------- $value
	/**
	 * The color value
	 *
	 * @var string
	 */
	public $value = 'white';

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value string
	 */
	public function __construct($value = null)
	{
		if (isset($value)) {
			$this->value = $value;
		}
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->value;
	}

}