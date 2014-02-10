<?php
namespace SAF\AOP;

use SAF\Framework\Reflection_Parameter;

/**
 * Aspect weaver properties compiler
 */
class Properties_Compiler
{
	use Compiler_Toolbox;

	const DEBUG = false;

	//----------------------------------------------------------------------- $constructor_parameters
	/**
	 * @var string
	 */
	private $constructor_parameters;

	//------------------------------------------------------------------------------------ $construct
	/**
	 * __construct() inner code (one element per property)
	 *
	 * @var string[]
	 */
	private $construct;

	//------------------------------------------------------------------------------------------ $get
	/**
	 * __get() inner code (one element per property)
	 *
	 * @var string[]
	 */
	private $get;

	//------------------------------------------------------------------------------------------ $set
	/**
	 * __set() inner code (one element per property)
	 *
	 * @var string[]
	 */
	private $set;

	//--------------------------------------------------------------------------------------- $source
	/**
	 * @var Php_Source
	 */
	private $source;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class_name string
	 * @param $buffer     string
	 */
	public function __construct($class_name, &$buffer)
	{
		$this->source = new Php_Source($class_name, $buffer);
		$this->compileStart();
	}

	//--------------------------------------------------------------------------------- compileAdvice
	/**
	 * @param $property_name string
	 * @param $type          string
	 * @param $advice        string[]|object[]|string
	 * @return string
	 */
	private function compileAdvice($property_name, $type, $advice)
	{
		$class_name = $this->source->class_name;
		$code = '';

		/** @var $advice_class_name string */
		/** @var $advice_method_name string */
		/** @var $advice_function_name string */
		/** @var $advice_parameters Reflection_Parameter[] */
		/** @var $advice_string string "array($object_, 'methodName')" | "'functionName'" */
		/** @var $advice_has_return boolean */
		/** @var $is_advice_static boolean */
		list(
			$advice_class_name, $advice_method_name, $advice_function_name,
			$advice_parameters, $advice_string, $advice_has_return, $is_advice_static
		) = $this->decodeAdvice($advice, $class_name);

		// $advice_parameters_string, $joinpoint_code
		$joinpoint_code = '';
		if ($advice_parameters) {
			$advice_parameters_string = '$' . join(', $', array_keys($advice_parameters));
			if (isset($advice_parameters[$property_name])) {
				$advice_parameters_string = str_replace(
					'$' . $property_name, '$value', $advice_parameters_string
				);
			}
			if (isset($advice_parameters['result'])) {
				$advice_parameters_string = str_replace('$result', '$value', $advice_parameters_string);
			}
			if (isset($advice_parameters['object']) && !isset($parameters['object'])) {
				$advice_parameters_string = str_replace('$object', '$this', $advice_parameters_string);
			}
			if (isset($advice_parameters['joinpoint'])) {
				$joinpoint_parameters_string = 'array()';
				switch ($type) {
					case 'read':
						$joinpoint_code = '$joinpoint = new \SAF\AOP\Read_Property_Joinpoint('
							. ');';
						break;
					case 'write':
						$joinpoint_code = '$joinpoint = new \SAF\AOP\Write_Property_Joinpoint('
							. ');';
						break;
				}
			}
			if (
				isset($advice_parameters['property']) || isset($advice_parameters['type'])
				|| isset($advice_parameters['element_type']) || isset($advice_parameters['type_name'])
				|| isset($advice_parameters['element_type_name'])
			) {
				$code .= '
			$property = new \SAF\Framework\Reflection_Property(\''. $class_name . '\', \'' . $property_name . '\');';
			}
			if (
				isset($advice_parameters['type']) || isset($advice_parameters['type_name'])
				|| isset($advice_parameters['element_type_name'])
			) {
				$code .= '
			$type = $property->getType();';
			}
			if (isset($advice_parameters['element_type'])) {
				$code .= '
			$element_type = $property->getElementType();';
			}
			if (isset($advice_parameters['type_name'])) {
				$code .= '
			$type_name = $type->asString();';
			}
			if (isset($advice_parameters['element_type_name'])) {
				$code .= '
			$element_type_name = $type->getElementTypeAsString();';
			}
		}
		else {
			$advice_parameters_string = '';
		}

		return $code . $this->generateAdviceCode(
			$advice, $advice_class_name, $advice_method_name, $advice_function_name,
			$advice_parameters_string, $advice_has_return, $is_advice_static, $joinpoint_code,
			"\n\t\t\t", '$value'
		);
	}

	//---------------------------------------------------------------------------------- compileStart
	/**
	 * Start the compilation process : prepare methods
	 */
	private function compileStart()
	{
		$this->construct = array();
		$this->get       = array();
		$this->set       = array();
	}

	//----------------------------------------------------------------------------- compileProperties
	/**
	 * @param $property_name string
	 * @param $advices       array each element is an array($type, $callable)
	 */
	public function compileProperty($property_name, $advices)
	{
		$class_name = $this->source->class_name;

		$this->construct[$property_name] = '
		$value_ = isset($this->' . $property_name . ') ? $this->' . $property_name . ' : null;
		unset($this->' . $property_name . ');
		$this->_' . $property_name . ' = $value_;
';

		$else = $this->get ? 'else' : '';
		$code['get'] = '
		' . $else . 'if ($property_name == \'' . $property_name . '\') {';
		$code['set'] = '
		elseif ($property_name == \'' . $property_name . '\') {';

		foreach ($advices as $advice) {
			list($type, $callback) = $advice;
			$code[($type == 'read') ? 'get' : 'set'] .= $this->compileAdvice(
				$property_name, $type, $callback
			);
		}

		$code['get'] .= '
		}';
		$code['set'] .= '
		}';

		$this->get[$property_name] = $code['get'];
		$this->set[$property_name] = $code['set'];

		if (self::DEBUG) echo '<h3>Property ' . $class_name::$property_name . '</h3>';
	}

	//-------------------------------------------------------------------------------- get__cosntruct
	/**
	 * @return string
	 */
	private function get__construct()
	{
		$prototype = $this->source->getPrototype('__construct', true);
		if ($prototype) {
			if (isset($prototype['parent'])) {
				$call = '
		parent::__construct(';
			}
			else {
				$this->overrideMethod('__construct', $prototype['preg']);
				$call = '
		$this->__construct_99(';
			}
			$prototype = $prototype['prototype'];
			$this->constructor_parameters = join(
				', ', $this->source->getPrototypeParametersNames($prototype)
			);
			$prototype = $this->source->getDocComment('__construct') . $prototype;
			$call .= $this->constructor_parameters . ');';
		}
		else {
			$call = '';
			$prototype = '
	/**
	 * Weaved constructor
	 */
	public function __construct()
	{';
		}

		return $prototype . join('', $this->construct) . $call . '
	}
';
	}

	//-------------------------------------------------------------------------------------- get__get
	/**
	 * @return string
	 */
	private function get__get()
	{
		$class_name = $this->source->class_name;
		$prototype = $this->source->getPrototype('__get', true);
		if (isset($prototype['parent'])) {
			$code = 'parent::__get($property_name)';
		}
		elseif ($prototype) {
			$this->overrideMethod('__get', $prototype['preg']);
			$code = '$this->__get_99($property_name)';
		}
		else {
			$code = 'trigger_error(
				\'Undefined property: Plugin_Register::$\' . substr($property_name, 1), E_USER_NOTICE
			)';
		}
		return '
	/**
	 * @param $property_name string
	 * @return mixed
	 */
	public function __get($property_name)
	{
		if ($property_name[0] == \'_\') {
			' . $code . ';
			return null;
		}
		$_property_name = \'_\' . $property_name;
		$this->$property_name = $this->$_property_name;
		$value =& $this->$property_name;
		' . join('', $this->get) . '

		else {
			trigger_error(\'Undefined property: ' . $class_name . '::$\' . $property_name, E_USER_NOTICE);
			$value = null;
		}
		$this->$_property_name = $value;
		unset($this->$property_name);
		return $value;
	}
';
	}

	//------------------------------------------------------------------------------------ get__isset
	/**
	 * @return string
	 */
	private function get__isset()
	{
		$prototype = $this->source->getPrototype('__isset', true);
		if (isset($prototype['parent'])) {
			$code = 'parent::__isset($property_name)';
		}
		elseif ($prototype) {
			$this->overrideMethod('__isset', $prototype['preg']);
			$code = '$this->__isset_99($property_name)';
		}
		else {
			$code = 'isset($this->$property_name)';
		}
		return '
	/**
	 * @param $property_name string
	 * @return boolean
	 */
	public function __isset($property_name)
	{
		if ($property_name[0] == \'_\') {
			return ' . $code . ';
		}
		$_property_name = \'_\' . $property_name;
		return isset($this->$_property_name);
	}
';
	}

	//-------------------------------------------------------------------------------------- get__set
	/**
	 * @return string
	 */
	private function get__set()
	{
		$prototype = $this->source->getPrototype('__set', true);
		if (isset($prototype['parent'])) {
			$code = 'parent::__set($property_name, $value)';
		}
		elseif ($prototype) {
			$this->overrideMethod('__set', $prototype['preg']);
			$code = '$this->__set_99($property_name, $value)';
		}
		else {
			$code = '$this->$property_name = $value';
		}
		return '
	/**
	 * @param $property_name string
	 * @param $value         mixed
	 */
	public function __set($property_name, $value)
	{
		if ($property_name[0] == \'_\') {
			' . $code . ';
			return;
		}
		' . join('', $this->set) . '

		else {
			$this->$property_name = $value;
			return;
		}
		$_property_name = \'_\' . $property_name;
		$this->$_property_name = $value;
	}
';
	}

	//------------------------------------------------------------------------------------ get__unset
	/**
	 * @return string
	 */
	private function get__unset()
	{
		$prototype = $this->source->getPrototype('__unset', true);
		if (isset($prototype['parent'])) {
			$code = 'parent::__unset($property_name)';
		}
		elseif ($prototype) {
			$this->overrideMethod('__unset', $prototype['preg']);
			$code = '$this->__unset_99($property_name)';
		}
		else {
			$code = 'unset($this->$property_name)';
		}
		return '
	/**
	 * @param $property_name string
	 */
	public function __unset($property_name)
	{
		if ($property_name[0] == \'_\') {
			' . $code . ';
			return;
		}
		$_property_name = \'_\' . $property_name;
		unset($this->$_property_name);
	}
';
	}

	//---------------------------------------------------------------------------- getCompiledMethods
	/**
	 * Assembly and return of the compiled methods list
	 *
	 * @return string[] key is the name of the method, value is its code
	 */
	public function getCompiledMethods()
	{
		$methods['__construct'] = $this->get__construct();
		$methods['__get']       = $this->get__get();
		$methods['__isset']     = $this->get__isset();
		$methods['__set']       = $this->get__set();
		$methods['__unset']     = $this->get__unset();
		return $methods;
	}

	//-------------------------------------------------------------------------------- overrideMethod
	/**
	 * @param $method_name string
	 * @param $preg_expr   string
	 */
	public function overrideMethod($method_name, $preg_expr)
	{
		$buffer =& $this->source->buffer;
		$buffer = preg_replace(
			$preg_expr, "\n\t" . '/* $2*/ private $4' . $method_name . '_99' . '$7', $buffer
		);
	}

}