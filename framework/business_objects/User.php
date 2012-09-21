<?php
namespace SAF\Framework;

class User
{
	use Current;

	//---------------------------------------------------------------------------------------- $login
	/**
	 * @var string
	 */
	public $login;

	//------------------------------------------------------------------------------------- $password
	/**
	 * @var string
	 */
	public $password;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Build a User object, optionnaly with it's login and password initialization
	 *
	 * @param string $login
	 * @param string $password
	 */
	public function __construct($login = "", $password = "")
	{
		$this->login    = $login;
		$this->password = $password;
	}

}
