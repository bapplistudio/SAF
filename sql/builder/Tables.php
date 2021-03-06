<?php
namespace SAF\Framework\Sql\Builder;

use SAF\Framework\Dao;
use SAF\Framework\Sql\Join\Joins;

/**
 * The SQL tables section (joins) of SQL queries builder
 */
class Tables
{

	//----------------------------------------------------------------------------------- $class_name
	/**
	 * @var string
	 */
	private $class_name;

	//---------------------------------------------------------------------------------------- $joins
	/**
	 * @var Joins
	 */
	private $joins;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct the SQL 'FROM' tables list section of a query
	 *
	 * @param $class_name string
	 * @param $joins      Joins
	 */
	public function __construct($class_name, Joins $joins = null)
	{
		$this->class_name = $class_name;
		$this->joins = $joins ? $joins : new Joins($class_name);
	}

	//----------------------------------------------------------------------------------------- build
	/**
	 * Build SQL tables list, based on calculated joins for where array properties paths
	 *
	 * @return string
	 */
	public function build()
	{
		$tables = BQ . Dao::current()->storeNameOf($this->class_name) . BQ . SP . 't0';
		foreach ($this->joins->getJoins() as $join) if ($join) {
			$tables .= $join->toSql();
		}
		return $tables;
	}

	//-------------------------------------------------------------------------------------- getJoins
	/**
	 * @return Joins
	 */
	public function getJoins()
	{
		return $this->joins;
	}

}
