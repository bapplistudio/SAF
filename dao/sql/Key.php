<?php
namespace SAF\Framework\Dao\Sql;

use SAF\Framework\Tools\Field;

/**
 * A common interface for Dao key object representation
 */
interface Key extends Field
{

	//----------------------------------------------------------------------------------------- equiv
	/**
	 * Returns true if the key is an equivalent of the other key
	 *
	 * @param $key Key
	 */
	public function equiv($key);

	//------------------------------------------------------------------------------------ getSqlType
	/**
	 * Gets the SQL version of the type
	 *
	 * @return string
	 */
	public function getSqlType();

	//----------------------------------------------------------------------------------------- toSql
	/**
	 * Gets the SQL version of the column definition
	 *
	 * @return string
	 */
	public function toSql();

}
