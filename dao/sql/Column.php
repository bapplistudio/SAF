<?php
namespace SAF\Framework\Dao\Sql;

use SAF\Framework\Tools\Field;

/**
 * A common interface for Dao column object representation
 */
interface Column extends Field
{

	//------------------------------------------------------------------------------------- canBeNull
	/**
	 * @return boolean
	 */
	public function canBeNull();

	//----------------------------------------------------------------------------------------- equiv
	/**
	 * Returns true if the column is an equivalent of the other column
	 *
	 * @param $column Column
	 */
	public function equiv($column);

	//------------------------------------------------------------------------------- getDefaultValue
	/**
	 * Gets the default value of a field
	 * @return mixed
	 */
	public function getDefaultValue();

	//--------------------------------------------------------------------------------- getSqlPostfix
	/**
	 * Gets the SQL postfix for the column
	 *
	 * @return string
	 */
	public function getSqlPostfix();

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
	 * @returns string
	 */
	public function toSql();

}
