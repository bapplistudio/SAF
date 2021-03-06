<?php
namespace SAF\Framework\Http;

use SAF\Framework\Builder;
use SAF\Framework\Tools\Stringable;
use SAF\Framework\Traits\Has_Name;

/**
 * HTTP header cookie object
 */
class Cookie implements Stringable
{
	use Has_Name;

	//---------------------------------------------------------------------------------------- $value
	/**
	 * @var string
	 */
	public $value;

	//----------------------------------------------------------------------------------- $properties
	/**
	 * @var string[]
	 */
	public $properties;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $name       string
	 * @param $value      string
	 * @param $properties string[]
	 */
	public function __construct($name = null, $value = null, $properties = null)
	{
		if (isset($name))              $this->name       = $name;
		if (isset($value))             $this->value      = $value;
		if (isset($properties))        $this->properties = $properties;
		if (!isset($this->properties)) $this->properties = [];
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		$string = $this->name . '=' . $this->value;
		foreach ($this->properties as $key => $value) {
			$string .= '; ' . $key . '=' . $value;
		}
		return $string;
	}

	//------------------------------------------------------------------------------------ fromString
	/**
	 * @param $string string
	 * @return self
	 */
	public static function fromString($string)
	{
		$cookie = Builder::create(get_called_class());
		$cookie->properties = [];
		foreach (explode(';', $string) as $element) {
			list($key, $value) = explode('=', $element);
			if (!isset($cookie->name)) {
				$cookie->name  = trim($key);
				$cookie->value = trim($value);
			}
			else {
				$cookie->properties[trim($key)] = trim($value);
			}
		}
		return $cookie;
	}

}
