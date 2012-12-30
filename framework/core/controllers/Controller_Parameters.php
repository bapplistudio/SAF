<?php
namespace SAF\Framework;

require_once "framework/core/mappers/Getter.php";
require_once "framework/core/toolbox/Namespaces.php";

class Controller_Parameters
{

	//-------------------------------------------------------------------------------------- $objects
	/**
	 * @var multitype:object indices are parameters names (ie object class short name)
	 */
	private $objects = array();

	//----------------------------------------------------------------------------------- $parameters
	/**
	 * @var multitype:integer indices are parameters names (ie object class short name)
	 */
	private $parameters = array();

	//-------------------------------------------------------------------------------------- addValue
	/**
	 * Adds a parameter without name value
	 *
	 * @param mixed $value
	 * @return Controller_Parameters
	 */
	public function addValue($parameter_value)
	{
		$this->parameters[] = $parameter_value;
		return $this;
	}

	//------------------------------------------------------------------------------------- getObject
	/**
	 * Get URI parameter as an object
	 *
	 * Object is of class $parameter name, and is read from current data link using the parameter
	 * value as identifier.
	 *
	 * @param string $parameter_name
	 * @return object
	 */
	public function getObject($parameter_name)
	{
		if (isset($this->objects[$parameter_name])) {
			// parameter is in cache
			$object = $this->objects[$parameter_name];
		}
		elseif (is_numeric($this->parameters[$parameter_name])) {
			$class_name = Namespaces::fullClassName($parameter_name);
			if (class_exists($class_name)) {
				// object parameter
				$object = Getter::getObject($this->parameters[$parameter_name] + 0, $class_name);
				$this->objects[$parameter_name] = $object;
			} else {
				// free parameter
				$object = $this->parameters[$parameter_name];
				$this->objects[$parameter_name] = $object;
			}
		} else {
			// text parameter
			$object = $this->parameters[$parameter_name];
			$this->objects[$parameter_name] = $object;
		}
		return $object;
	}

	//------------------------------------------------------------------------------------ getObjects
	/**
	 * Get parameters list as objects
	 *
	 * @return multitype:object indiced by parameter name
	 */
	public function getObjects()
	{
		$parameters = array();
		foreach (array_keys($this->parameters) as $parameter_name) {
			$parameters[$parameter_name] = $this->getObject($parameter_name);
		}
		return $parameters;
	}

	//------------------------------------------------------------------------------- getRawParameter
	/**
	 * Get URI parameter raw value, as it was on original URI
	 *
	 * @param string  $parameter_name
	 * @return mixed
	 */
	public function getRawParameter($parameter_name)
	{
		return $this->parameters[$parameter_name];
	}

	//--------------------------------------------------------------------------------- getParameters
	/**
	 * Get URI parameters raw values, as they were on original URI
	 *
	 * @return multitype:mixed indice is the parameter name
	 */
	public function getRawParameters()
	{
		return $this->parameters;
	}

	//------------------------------------------------------------------------------------------- set
	/**
	 * Set URI parameter raw value
	 *
	 * @param string  $name
	 * @param integer $value
	 * @return Controller_Parameters
	 */
	public function set($parameter_name, $parameter_value)
	{
		$this->parameters[$parameter_name] = $parameter_value;
		return $this;
	}

}
