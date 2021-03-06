<?php
namespace SAF\Framework\Dao\Mysql;

use mysqli_result;
use SAF\Framework\AOP\Joinpoint\Before_Method;
use SAF\Framework\Dao;
use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Tools\Contextual_Mysqli;
use SAF\Framework\User;

/**
 * Mysql\Reconnect plugin allow auto-reconnect when a server disconnected error is thrown
 */
class Reconnect implements Registerable
{

	//---------------------------------------------------------------------------- onMysqliQueryError
	/**
	 * This is called after each mysql query error in order to reconnect lost connexion to server
	 *
	 * @param $object    Contextual_Mysqli
	 * @param $query     string
	 * @param $result    mysqli_result|boolean
	 * @param $joinpoint Before_Method
	 */
	public function onMysqliQueryError(
		Contextual_Mysqli &$object, $query, &$result, Before_Method $joinpoint
	) {
		$mysqli =& $object;
		if (in_array($mysqli->last_errno, [Errors::CR_SERVER_GONE_ERROR, Errors::CR_SERVER_LOST])) {
			// wait 1 second an try to reconnect
			sleep(1);
			if (!$mysqli->ping()) {
				if (!$mysqli->reconnect()) {
					trigger_error(
						'$mysqli->ping() and reconnect() failed after a server gone error', E_USER_ERROR
					);
				}
			}
			$result = $mysqli->query($query);
			if (!$mysqli->last_errno && !$mysqli->last_error) {
				$joinpoint->stop = true;
			}
		}
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Registration code for the plugin
	 *
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$aop = $register->aop;
		$aop->beforeMethod([Contextual_Mysqli::class, 'queryError'], [$this, 'onMysqliQueryError']);
	}

	//------------------------------------------------------------------------------------------ test
	/**
	 * A functional test for reconnect :
	 * Executes a query once per seconds during one minute.
	 * Try to kill the running mysql thread during the test : the connexion should come back.
	 */
	public function test()
	{
		$time = time();
		for ($i = 1; $i <= 15; $i ++) {
			/** @var $dao Link */
			$dao = Dao::current();
			$users = $dao->query('SELECT * FROM users', User::class);
			$user = reset($users);
			if (
				!is_a($user, User::class)
				|| $dao->getConnection()->last_errno
				|| $dao->getConnection()->last_error
			) {
				return $i . ' : query error '
					. $dao->getConnection()->last_errno . SP . $dao->getConnection()->last_error;
			}
			if ($i < 60) {
				sleep(1);
			}
		}
		return 'OK. Took ' . (time() - $time) . ' seconds.';
	}

}
