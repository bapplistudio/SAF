<?php
namespace SAF\Framework;

/**
 * The available applications configurations management class
 */
class Configurations
{

	//------------------------------------------------------------------------------- $configurations
	/**
	 * Configuration objects array, indice is configuration name
	 *
	 * @var Configuration[]
	 */
	private $configurations;

	//-------------------------------------------------------------------------- getAllConfigurations
	/**
	 * Get all the loaded configurations
	 *
	 * @return Configuration[]
	 */
	public function getAllConfigurations()
	{
		return $this->configurations;
	}

	//------------------------------------------------------------------------------ getConfiguration
	/**
	 * Get the named $configuration_name Configuration object
	 *
	 * @param $configuration_name string
	 * @return Configuration
	 */
	public function getConfiguration($configuration_name)
	{
		return $this->configurations[$configuration_name];
	}

	//------------------------------------------------------------------------------------------ load
	/**
	 * Load the config.php configuration file an store it into the configurations list
	 *
	 * If a default configuration is set into the loaded configuration file, current configuration is
	 * switched to this configuration.
	 *
	 * @param $file_name string
	 * @return Configuration
	 */
	public function load($file_name = "config.php")
	{
		$config = array();
		/** @noinspection PhpIncludeInspection */
		include $file_name;
		$configurations = array();
		foreach ($config as $config_name => $config_options) {
			if (isset($config_options["extends"])) {
				$extends_array = is_array($config_options["extends"])
					? $config_options["extends"]
					: array($config_options["extends"]);
				unset($config_options["extends"]);
				foreach ($extends_array as $extends) {
					$config_options = arrayMergeRecursive($configurations[$extends], $config_options);
				}
			}
			if (!isset($config_options["app"])) {
				$config_options["app"] = $config_name;
			}
			if (!isset($config_options["author"])) {
				$config_options["author"] = "SAF";
			}
			$configurations[$config_name] = $config_options;
		}
		$this->configurations = array();
		foreach ($configurations as $config_name => $config_options) {
			$this->configurations[$config_name] = new Configuration($config_options);
		}
		return end($this->configurations);
	}

}
