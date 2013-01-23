<?php
namespace SAF\Framework;
use AopJoinpoint;

class Aop_Dynamics implements Plugin
{
	use Current { current as private pCurrent; }

	//---------------------------------------------------------------------------------------- $links
	/**
	 * Aop dynamic links list
	 *
	 * Each entry is an array which elements are each an Aop entry array :
	 * 0 : "after", "after_returning", "after_throwing", "around", "before"
	 * 1 : the pointcut class name (can be short or long)
	 * 2 : the pointcut method (if terminated by "()") or property name (else)
	 * 3 : the advice class name (can be short or long)
	 * 4 : the name of the static method to call into the advice class
	 *
	 * @var array[] key is the short / long class name
	 */
	private $links = array();

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Constructor with default links
	 *
	 * @param array[] $links
	 */
	public function __construct($links = array())
	{
		$this->links = $links;
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Add dynamic links to current list
	 *
	 * Each entry is an array which elements are each an Aop entry array :
	 * 0 : "after", "after_returning", "after_throwing", "around", "before"
	 * 1 : the pointcut class name (can be short or long)
	 * 2 : the pointcut method (if terminated by "()") or property name (else)
	 * 3 : the advice class name (can be short or long)
	 * 4 : the name of the static method to call into the advice class
	 *
	 * @param array[] $links key is the short / long class name
	 */
	public function add($links)
	{
		$this->links = arrayMergeRecursive($this->links, $links);
	}

	//--------------------------------------------------------------------------------------- current
	/**
	 * @param Aop_Dynamics $set_current
	 * @return Aop_Dynamics
	 */
	public static function current(Aop_Dynamics $set_current = null)
	{
		return self::pCurrent($set_current);
	}

	//------------------------------------------------------------------------------------- linkClass
	/**
	 * Register callback advices for joinpoints associated to the class name 
	 *
	 * @param string $class_name class name can be short or long
	 */
	private function linkClass($class_name)
	{
		$class_names = array(
			Namespaces::shortClassName($class_name), Namespaces::fullClassName($class_name)
		);
		foreach ($class_names as $class_name) {
			if (isset($this->links[$class_name])) {
				foreach ($this->links[$class_name] as $link) {
					Aop::add(
						$link[0],
						Namespaces::fullClassName($link[1]) . "::" . $link[2],
						array(Namespaces::fullClassName($link[3]), $link[4])
					);
				}
			}
		}
	}

	//---------------------------------------------------------------------------------- linkClassAop
	/**
	 * Register callback advices for joinpoints associated to the $joinpoint->getReturnedValue() class name
	 *
	 * This is the joinpoint form of linkClass(), designed to be called at Autoloader::autoload()'s end
	 *
	 * @param AopJoinpoint $joinpoint
	 */
	public static function linkClassAop(AopJoinpoint $joinpoint)
	{
		$class_name = $joinpoint->getReturnedValue();
		if ($class_name) {
			$current = Aop_dynamics::current();
			if (isset($current)) {
				$current->linkClass($class_name);
			}
		}
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Register Aop_Dynamics : for each new autoloaded class, jointpoints will be dynamically added using linkClass()
	 */
	public static function register()
	{
		Aop::add("after", __NAMESPACE__ . "\\Autoloader->autoload()", array(__CLASS__, "linkClassAop"));
	}

}
