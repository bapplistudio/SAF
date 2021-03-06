<?php
namespace SAF\Framework\Dao\Func;

use SAF\Framework\Sql\Builder\Columns;

/**
 * Dao Left function
 */
class Left extends Column
{

	//--------------------------------------------------------------------------------------- $length
	/**
	 * @var integer
	 */
	public $length;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $length integer
	 */
	public function __construct($length)
	{
		$this->length = $length;
	}

	//----------------------------------------------------------------------------------------- toSql
	/**
	 * Returns the Dao function as SQL
	 *
	 * @param $builder       Columns the sql data link
	 * @param $property_path string escaped sql, name of the column
	 * @return string
	 */
	public function toSql(Columns $builder, $property_path)
	{
		return $this->quickSql($builder, $property_path, 'LEFT', [$this->length]);
	}

}
