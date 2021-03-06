<?php
namespace SAF\Framework\Dao\Mysql;

use SAF\Framework\AOP\Joinpoint\Before_Method;
use SAF\Framework\Controller\Main;
use SAF\Framework\Controller\Parameter;
use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Tools\Contextual_Mysqli;

/**
 * Mysql delete-and-replace feature
 *
 * When a mysql error #1451 'a foreign key constraint fails' occurs on a 'DELETE' query, a
 * little HTML form explains the error and proposes to replace the record by another one,
 * selected into a combo-box.
 */
class Delete_And_Replace implements Registerable
{

	//------------------------------------------------------------------------------------- extractId
	/**
	 * Extract the object identifier from a short standard DELETE query string
	 *
	 * @param $query string
	 * @return integer
	 */
	private function extractId($query)
	{
		$id = rLastParse($query, LF . 'WHERE id = ');
		return is_numeric($id) ? intval($id) : null;
	}

	//---------------------------------------------------------------------------------- onQueryError
	/**
	 * @param $object    Contextual_Mysqli
	 * @param $query     string
	 * @param $joinpoint Before_Method
	 */
	public function onQueryError(Contextual_Mysqli $object, $query, Before_Method $joinpoint)
	{
		if (
			in_array(
				$object->last_errno,
				[Errors::ER_ROW_IS_REFERENCED, Errors::ER_ROW_IS_REFERENCED_2]
			)
			&& $object->context
			&& is_string($object->context)
			&& $object->isDelete($query)
		) {
			$id = $this->extractId($query);
			if ($id) {
				$controller_uri = SL . $object->context . SL . $id . SL . 'deleteAndReplace';
				echo (new Main())->runController($controller_uri, [Parameter::AS_WIDGET => true]);
				$joinpoint->stop = true;
			}
		}
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$register->aop->beforeMethod([Contextual_Mysqli::class, 'queryError'], [$this, 'onQueryError']);
	}

}
