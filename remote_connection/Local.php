<?php
namespace SAF\Framework\Remote_Connection;

use SAF\Framework\Remote_Connection;

/**
 * Local file system connection
 */
class Local implements Remote_Connection
{

	//----------------------------------------------------------------------------------------- $path
	/**
	 * @var string
	 */
	public $path;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $path string
	 */
	public function __construct($path = null)
	{
		if (isset($path)) {
			$this->path = $path;
		}
	}

	//--------------------------------------------------------------------------------------- connect
	public function connect()
	{
		// TODO: Implement connect() method.
	}

	//---------------------------------------------------------------------------------------- delete
	/**
	 * @param $file string
	 */
	public function delete($file)
	{
		// TODO: Implement delete() method.
	}

	//------------------------------------------------------------------------------------------- dir
	/**
	 * @param $path string
	 */
	public function dir($path)
	{
		// TODO: Implement dir() method.
	}

	//------------------------------------------------------------------------------------ disconnect
	public function disconnect()
	{
		// TODO: Implement disconnect() method.
	}

	//----------------------------------------------------------------------------------------- mkdir
	/**
	 * @param $path string
	 */
	public function mkdir($path)
	{
		// TODO: Implement mkdir() method.
	}

	//--------------------------------------------------------------------------------------- receive
	/**
	 * @param $distant string
	 * @param $local   string
	 */
	public function receive($distant, $local)
	{
		// TODO: Implement receive() method.
	}

	//------------------------------------------------------------------------------------------ send
	/**
	 * @param $local   string
	 * @param $distant string
	 */
	public function send($local, $distant)
	{
		// TODO: Implement send() method.
	}

}
