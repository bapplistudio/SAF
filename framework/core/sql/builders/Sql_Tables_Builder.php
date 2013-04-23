<?php
namespace SAF\Framework;

class Sql_Tables_Builder
{

	//----------------------------------------------------------------------------------- $class_name
	/**
	 * @var string
	 */
	private $class_name;

	//---------------------------------------------------------------------------------------- $joins
	/**
	 * @var Sql_Joins
	 */
	private $joins;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct the SQL "FROM" tables list section of a query
	 *
	 * @param $class_name string
	 * @param $joins      Sql_Joins
	 */
	public function __construct($class_name, Sql_Joins $joins = null)
	{
		$this->class_name = $class_name;
		$this->joins = $joins ? $joins : new Sql_Joins($class_name);
	}

	//----------------------------------------------------------------------------------------- build
	/**
	 * Build SQL tables list, based on calculated joins for where array properties paths
	 *
	 * @return string
	 */
	public function build()
	{
		$tables = "`" . Dao::current()->storeNameOf($this->class_name) . "` t0";
		foreach ($this->joins->getJoins() as $join) if ($join) {
			$tables .= " $join->mode JOIN `$join->foreign_table` $join->foreign_alias"
				. " ON $join->foreign_alias.$join->foreign_column = $join->master_alias.$join->master_column";
		}
		return $tables;
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * @return Sql_Joins
	 */
	public function getJoins()
	{
		return $this->joins;
	}

}