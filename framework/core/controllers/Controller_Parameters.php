<?php
namespace SAF\Framework;

/**
 * Controller parameters contains what objects are passed into the controller's URI
 */
class Controller_Parameters
{

	//-------------------------------------------------------------------------------------- $objects
	/**
	 * @var object[] indices are parameters names (ie object class short name)
	 */
	private $objects = array();

	//----------------------------------------------------------------------------------- $parameters
	/**
	 * @var integer[] indices are parameters names (ie object class short name)
	 */
	private $parameters = array();

	//------------------------------------------------------------------------------------------ $uri
	/**
	 * The controller URI that is originator of these parameters (if set)
	 *
	 * @var Controller_Uri
	 */
	public $uri;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $uri Controller_Uri
	 */
	public function __construct(Controller_Uri $uri = null)
	{
		if (isset($uri)) $this->uri = $uri;
	}

	//-------------------------------------------------------------------------------------- addValue
	/**
	 * Adds a parameter without name value
	 *
	 * @param $parameter_value mixed
	 * @return Controller_Parameters
	 */
	public function addValue($parameter_value)
	{
		$this->parameters[] = $parameter_value;
		return $this;
	}

	//----------------------------------------------------------------------------------------- count
	/**
	 * Gets parameters count
	 *
	 * @return integer
	 */
	public function count()
	{
		return count($this->parameters);
	}

	//--------------------------------------------------------------------------------- getMainObject
	/**
	 * Gets the main object from the parameters
	 * If no main object is set (eq first parameter is not an object), create it using class name
	 * Beware : the create object will then automatically be added on beggining of the parameters list
	 *
	 * @param $class_name string|object
	 * @return object
	 */
	public function getMainObject($class_name = null)
	{
		$object = reset($this->parameters);
		if (!$object || !is_object($object) || (isset($class_name) && !is_a($object, $class_name))) {
			$object = is_object($class_name) ? $class_name : (
				(isset($class_name) && class_exists($class_name))
				? Builder::create($class_name)
				: Set::instantiate($class_name)
			);
			$this->parameters = array_merge(array(get_class($object) => $object), $this->parameters);
		}
		return $object;
	}

	//------------------------------------------------------------------------------------- getObject
	/**
	 * Gets URI parameter as an object
	 *
	 * Object is of class $parameter name, and is read from current data link using the parameter
	 * value as identifier.
	 *
	 * @param $parameter_name string
	 * @return object
	 */
	public function getObject($parameter_name)
	{
		if (isset($this->objects[$parameter_name])) {
			// parameter is in cache
			$object = $this->objects[$parameter_name];
		}
		elseif (is_numeric($this->getRawParameter($parameter_name))) {
			$class_name = Namespaces::fullClassName($parameter_name);
			if (class_exists($class_name)) {
				// object parameter
				$object = Getter::getObject($this->getRawParameter($parameter_name) + 0, $class_name);
				$this->objects[$parameter_name] = $object;
			}
			else {
				// free parameter
				$object = $this->getRawParameter($parameter_name);
				$this->objects[$parameter_name] = $object;
			}
		}
		else {
			// text parameter
			$object = $this->getRawParameter($parameter_name);
			$this->objects[$parameter_name] = $object;
		}
		return $object;
	}

	//------------------------------------------------------------------------------------ getObjects
	/**
	 * Gets parameters list as objects
	 *
	 * @return mixed[] indiced by parameter name
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
	 * Gets URI parameter raw value, as it was on original URI
	 *
	 * @param $parameter_name string
	 * @return mixed
	 */
	public function getRawParameter($parameter_name)
	{
		return isset($this->parameters[$parameter_name]) ? $this->parameters[$parameter_name] : null;
	}

	//--------------------------------------------------------------------------------- getParameters
	/**
	 * Gets URI parameters raw values, as they were on original URI
	 *
	 * @return mixed[] indice is the parameter name
	 */
	public function getRawParameters()
	{
		return $this->parameters;
	}

	//-------------------------------------------------------------------------- getUnnamedParameters
	/**
	 * Gets URI parameters raw values, only those which have no name
	 */
	public function getUnnamedParameters()
	{
		return arrayUnnamedValues($this->parameters);
	}

	//---------------------------------------------------------------------------------------- remove
	/**
	 * Removes a parameter
	 *
	 * @param $key integer|string
	 */
	public function remove($key)
	{
		if (isset($this->parameters[$key])) {
			unset($this->parameters[$key]);
		}
	}

	//------------------------------------------------------------------------------------------- set
	/**
	 * Sets URI parameter raw value
	 *
	 * @param $parameter_name  string
	 * @param $parameter_value mixed
	 * @return Controller_Parameters
	 */
	public function set($parameter_name, $parameter_value)
	{
		$this->parameters[$parameter_name] = $parameter_value;
		return $this;
	}

	//----------------------------------------------------------------------------------------- shift
	/**
	 * Returns and remove the first parameter
	 *
	 * @return mixed
	 */
	public function shift()
	{
		return array_shift($this->parameters);
	}

	//------------------------------------------------------------------------------------ shiftNamed
	/**
	 * Returns and remove the first parameter which key is not an integer and value is not an object
	 *
	 * @return string[] first element is the name of the parameter, second element is its value
	 */
	public function shiftNamed()
	{
		foreach ($this->parameters as $key => $value) {
			if (!is_numeric($key) && !is_object($value)) {
				unset($this->parameters[$key]);
				return array($key, $value);
			}
		}
		return null;
	}

	//----------------------------------------------------------------------------------- shiftObject
	/**
	 * Returns and remove the first parameter which is an object
	 *
	 * @return object
	 */
	public function shiftObject()
	{
		foreach ($this->parameters as $key => $value) {
			if (is_object($value)) {
				unset($this->parameters[$key]);
				return $value;
			}
		}
		return null;
	}

	//---------------------------------------------------------------------------------- shiftUnnamed
	/**
	 * Returns and remove the first unnamed parameter (which key is an integer and value is not an object)
	 *
	 * @return mixed|null
	 */
	public function shiftUnnamed()
	{
		foreach ($this->parameters as $key => $value) {
			if (is_numeric($key) && !is_object($value)) {
				unset($this->parameters[$key]);
				return $value;
			}
		}
		return null;
	}

	//-------------------------------------------------------------------------------- unshiftUnnamed
	/**
	 * Adds an unnamed parameter as first parameter
	 *
	 * @param $parameter_value mixed
	 */
	public function unshiftUnnamed($parameter_value)
	{
		array_unshift($this->parameters, $parameter_value);
	}

	//----------------------------------------------------------------------------------------- toGet
	/**
	 * Changes named parameters (which name is not numeric and value not object) into a "get-like"
	 * argument
	 *
	 * @param boolean $shift if true, get elements will be removed from parameters
	 * @return array
	 */
	public function toGet($shift = false)
	{
		$get = array();
		foreach ($this->parameters as $key => $value) {
			if (!is_numeric($key) && !is_object($value)) {
				$get[$key] = $value;
				if ($shift) {
					unset($this->parameters[$key]);
				}
			}
		}
		return $get;
	}

	//--------------------------------------------------------------------------------------- unshift
	/**
	 * Unshifts a parameter at beginning of the parameters array
	 *
	 * @param $parameter_value mixed
	 */
	public function unshift($parameter_value)
	{
		if (is_object($parameter_value)) {
			$this->parameters = arrayMergeRecursive(
				array(get_class($parameter_value) => $parameter_value), $this->parameters
			);
		}
		else {
			$this->unshiftUnnamed($parameter_value);
		}
	}

}
