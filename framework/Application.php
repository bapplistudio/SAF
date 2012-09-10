<?php
namespace SAF\Framework;

require_once "framework/classes/Configuration.php";
require_once "framework/classes/toolbox/string.php";

abstract class Application
{

	//----------------------------------------------------------------------------------- $namespaces
	/**
	 * Namespaces list cache : initialized at first use
	 *
	 * @var multitype:string
	 */
	protected static $namespaces;

	//-------------------------------------------------------------------------------- getDirectories
	/**
	 * This is called by getSourceDirectories() for recursive directories reading.
	 * 
	 * @param  string $path base path
	 * @return multitype:string an array of directories names
	 */
	private static function getDirectories($path)
	{
		$directories = array($path);
		$dir = dir($path);
		while ($entry = $dir->read()) {
			if (is_dir("$path/$entry") && ($entry[0] != ".")) {
				$directories = array_merge($directories, Application::getDirectories("$path/$entry"));
			}
		}
		return $directories;
	}

	//-------------------------------------------------------------------------- getSourceDirectories
	/**
	 * Returns the full directory list for the application, including parent's applications directory.
	 *
	 * Directory names are sorted from higher-level application to basis SAF "framework" directory.
	 * Inside an application, directories are sorted randomly (according to how the php Directory->read() call works).
	 *
	 * Paths are relative to the SAF index.php base script position.
	 *
	 * @param unknown_type $application_name
	 * @return multitype:string
	 */
	public static function getSourceDirectories($application_name)
	{
		$app_dir = strtolower($application_name);
		$directories = array();
		if ($application_name != "Framework") {
			$extends = mParse(file_get_contents("{$app_dir}/Application.php"),
				" extends SAF\\", "\\Application"
			);
			$directories = Application::getSourceDirectories($extends);
		}
		return array_merge(Application::getDirectories($app_dir), $directories);
	}

	//--------------------------------------------------------------------------------- getNamespaces
	/**
	 * Returns the used namespaces list for the application, including parent's applications namespaces.
	 *
	 * Namespaces strings are sorted from higher-level application to basis "SAF\Framework" namespace.
	 * An empty namespace will always be given first.
	 *
	 * @return multitype:string
	 */
	public static function getNamespaces()
	{
		if (!Application::$namespaces) {
			$application_name = Configuration::getCurrent()->getApplicationName();
			$app_dir = strtolower($application_name);
			Application::$namespaces = array(""); 
			$application = $application_name;
			while ($application != "Framework") {
				Application::$namespaces[] = "SAF\\" . $application;
				$application =  mParse(file_get_contents("{$app_dir}/Application.php"),
					" extends SAF\\", "\\Application"
				);
			}
			Application::$namespaces[] = __NAMESPACE__;
			// TODO should found another way to make it smarter (prehaps framework_test application ?)
			Application::$namespaces[] = __NAMESPACE__ . "\\Tests";
		}
		return Application::$namespaces;
	}

}
