<?php
namespace SAF\Plugins;

use SAF\AOP\IWeaver;

/**
 * Plugin register structure
 */
class Register
{

	//------------------------------------------------------------------------------------------ $aop
	/**
	 * @var IWeaver
	 */
	public $aop;

	//-------------------------------------------------------------------------------- $configuration
	/**
	 * @getter getConfiguration
	 * @setter setConfiguration
	 * @var array|string
	 */
	public $configuration;

	//------------------------------------------------------------------------------------------ $get
	/**
	 * @var boolean
	 */
	private $get;

	//---------------------------------------------------------------------------------------- $level
	/**
	 * @values core, highest, higher, high, normal, low, lower, lowest
	 * @var string
	 */
	public $level;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $configuration array|string
	 * @param $aop           IWeaver
	 */
	public function __construct($configuration = null, IWeaver $aop = null)
	{
		if (isset($aop))           $this->aop           = $aop;
		if (isset($configuration)) $this->configuration = $configuration;
	}

	//------------------------------------------------------------------------------ getConfiguration
	/**
	 * @return array|string
	 */
	private function getConfiguration()
	{
		if (!$this->get) {
			if (!is_array($this->configuration)) {
				$this->configuration = isset($this->configuration)
					? array($this->configuration => true)
					: array();
			}
			foreach ($this->configuration as $key => $value) {
				if (is_numeric($key) && is_string($value)) {
					unset($this->configuration[$key]);
					$this->configuration[$value] = true;
				}
			}
			$this->get = true;
		}
		return $this->configuration;
	}

	//------------------------------------------------------------------------------ setConfiguration
	/**
	 * @param $configuration array|string
	 */
	private function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
		$this->get = false;
	}

}