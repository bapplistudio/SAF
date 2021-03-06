<?php
namespace SAF\Framework\Checker;

/**
 * This is an interface for auto-checked business objects
 *
 * @deprecated see Validator
 */
interface Checked
{

	//----------------------------------------------------------------------------------------- check
	/**
	 * Check current business object and returns check report
	 *
	 * @return Report
	 */
	public function check();

}
