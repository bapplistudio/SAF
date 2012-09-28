<?php
namespace SAF\Framework;

class Sql_Join
{

	//------------------------------------------------------------------------------------- JOIN MODE
	const INNER = "INNER";
	const LEFT  = "LEFT";
	const OUTER = "OUTER";
	const RIGHT = "RIGHT";

	//------------------------------------------------------------------------------------- JOIN TYPE
	const OBJECT = "OBJECT";
	const SIMPLE = "SIMPLE";

	//-------------------------------------------------------------------------------- $foreign_alias
	/**
	 * Alias for foreign table (t1..tn)
	 *
	 * @var string
	 */
	public $foreign_alias;

	//------------------------------------------------------------------------------- $foreign_column
	/**
	 * Foreign column name
	 *
	 * @var string
	 */
	public $foreign_column;

	//-------------------------------------------------------------------------------- $foreign_table
	/**
	 * Foreign table name
	 *
	 * @var string
	 */
	public $foreign_table;

	//--------------------------------------------------------------------------------- $master_alias
	/**
	 * Alias for master table (t0..tn)
	 *
	 * @var string
	 */
	public $master_alias;

	//-------------------------------------------------------------------------------- $master_column
	/**
	 * Master column name
	 *
	 * @var string
	 */
	public $master_column;

	//----------------------------------------------------------------------------------------- $mode
	/**
	 * Join mode (Sql_Join::INNER, LEFT, OUTER or RIGHT)
	 *
	 * @var string
	 * @values INNER, LEFT, OUTER, RIGHT
	 */
	public $mode;

	//----------------------------------------------------------------------------------------- $type
	/**
	 * Join column type (Sql_Join::SIMPLE, OBJECT)
	 *
	 * @var string
	 * @values SIMPLE, OBJECT
	 */
	public $type = self::SIMPLE;

	//-------------------------------------------------------------------------------------- toString
	public function __toString()
	{
		return $this->mode
			. " $this->master_alias.$this->master_column"
			. " = $this->foreign_table $this->foreign_alias.$this->foreign_column"
			. " ($this->type)";
	}

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * Generate a new Sql_Join instance, with initialization of all it's properties
	 *
	 * @param integer $mode
	 * @param string  $master_alias
	 * @param string  $master_column
	 * @param string  $foreign_table
	 * @param string  $foreign_alias
	 * @param string  $foreign_column
	 * @return Sql_Join
	 */
	public static function newInstance(
		$mode, $master_alias, $master_column, $foreign_alias, $foreign_table, $foreign_column,
		$type = self::SIMPLE
	) {
		$sql_join = new Sql_Join();
		$sql_join->foreign_alias  = $foreign_alias;
		$sql_join->foreign_column = $foreign_column;
		$sql_join->foreign_table  = $foreign_table;
		$sql_join->master_alias   = $master_alias;
		$sql_join->master_column  = $master_column;
		$sql_join->mode           = $mode;
		$sql_join->type           = $type;
		return $sql_join;
	}

}
