<?php
namespace SAF\Framework\Dao\Data_Link;

/**
 * A transactional data link defines a data link that accepts transactional calls (maybe virtual...)
 */
interface Transactional
{

	//----------------------------------------------------------------------------------------- begin
	/**
	 * Begin a transaction (non-transactional SQL engines will do nothing and return null)
	 *
	 * @return boolean | null true if begin succeeds, false if error, null if not a transactional SQL engine
	 */
	public function begin();

	//---------------------------------------------------------------------------------------- commit
	/**
	 * Commit a transaction (non-transactional SQL engines will do nothing and return null)
	 *
	 * @param $flush boolean
	 * @return boolean | null true if commit succeeds, false if error, null if not a transactional SQL engine
	 */
	public function commit($flush = false);

	//-------------------------------------------------------------------------------------- rollback
	/**
	 * Rollback a transaction (non-transactional SQL engines will do nothing and return null)
	 *
	 * @return boolean | null true if commit succeeds, false if error, null if not a transactional SQL engine
	 */
	public function rollback();

}
