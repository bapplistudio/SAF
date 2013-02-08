<?php
namespace SAF\Framework;
use AopJoinpoint;

class Mysql_Logger implements Plugin
{

	//------------------------------------------------------------------------------------- $continue
	/**
	 * If true, log will be displayed once each query is executed. If false, will be display at script's end.
	 *
	 * @var boolean
	 */
	private $continue = false;

	//----------------------------------------------------------------------------------- $errors_log
	/**
	 * The errors log
	 *
	 * Errors are full text looking like "errno: Error message [SQL Query]".
	 *
	 * @var string[]
	 */
	public $errors_log = array();

	//---------------------------------------------------------------------- $main_controller_counter
	/**
	 * Counts Main_Controller->run() recursivity, to avoid logging after each sub-call
	 *
	 * @var integer
	 */
	public $main_controller_counter = 0;

	//---------------------------------------------------------------------------------- $queries_log
	/**
	 * The queryies log
	 *
	 * All executed queries are logged here.
	 *
	 * @var string[]
	 */
	public $queries_log = array();

	//----------------------------------------------------------------------------------- __construct
	private function __construct() {}

	//------------------------------------------------------------------------ afterMainControllerRun
	/**
	 * After main controller run, display query log
	 */
	public function afterMainControllerRun()
	{
		$this->main_controller_counter--;
		if (!$this->main_controller_counter) {
			$this->dumpLog();
		}
	}

	//--------------------------------------------------------------------------------------- dumpLog
	/**
	 * Display query log
	 */
	public function dumpLog()
	{
		echo "<h3>Mysql log</h3>";
		echo "<div class=\"Mysql logger query\">\n";
		echo "<pre>" . print_r($this->queries_log, true) . "</pre>\n";
		echo " </div>\n";
	}

	//----------------------------------------------------------------------------------- getInstance
	/**
	 * Get the Mysql_Logger instance
	 *
	 * @return Mysql_Logger
	 */
	public static function getInstance()
	{
		static $instance = null;
		if (!isset($instance)) {
			$instance = new Mysql_Logger();
		}
		return $instance;
	}

	//--------------------------------------------------------------------------------------- onQuery
	/**
	 * Called each time before a mysql_query() call is done : log the query
	 *
	 * @param $joinpoint AopJoinpoint
	 */
	public function onQuery(AopJoinpoint $joinpoint)
	{
		$arguments = $joinpoint->getArguments();
		$log = $arguments[0];
		if ($this->continue) {
			echo "<div class=\"Mysql logger query\">" . $log . "</div>\n";
		}
		$this->queries_log[] = $log;
	}

	//--------------------------------------------------------------------------------------- onQuery
	/**
	 * Called each time after a mysql_query() call is done : log the error (if some)
	 *
	 * @param $joinpoint AopJoinpoint
	 */
	public function onError(AopJoinpoint $joinpoint)
	{
		$mysqli = $joinpoint->getObject();
		if ($mysqli->errno) {
			$arguments = $joinpoint->getArguments();
			$error = $mysqli->errno . ": " . $mysqli->error . "[" . $arguments[0] . "]";
			echo "<div class=\"Mysql logger error\">" . $error . "</div>\n";
			$this->errors_log[] = $error;
		}
	}

	//--------------------------------------------------------------------------- onMainControllerRun
	public function onMainControllerRun()
	{
		$this->main_controller_counter++;
	}

	//-------------------------------------------------------------------------------------- register
	public static function register($parameters = null)
	{
		$mysql_logger = self::getInstance();
		if (isset($parameters["continue"])) {
			$mysql_logger->continue = $parameters["continue"];
		}
		Aop::add("before", "mysqli->query()", array($mysql_logger, "onQuery"));
		Aop::add("after",  "mysqli->query()", array($mysql_logger, "onError"));
		if (!$mysql_logger->continue) {
			Aop::add("before",
				'SAF\Framework\Main_Controller->runController()',
				array($mysql_logger, "onMainControllerRun")
			);
			Aop::add("after",
				'SAF\Framework\Main_Controller->runController()',
				array($mysql_logger, "afterMainControllerRun")
			);
		}
	}

}
