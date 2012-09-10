<?php
namespace SAF\Framework;

class Sql_Select_Builder
{
	use Sql_Columns_Builder, Sql_Where_Builder;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Construct a SQL SELECT query
	 *
	 * Supported columns naming forms are :
	 * column_name : column_name must correspond to a property of class
	 * column.foreign_column : column must be a property of class, foreign_column must be a property of column's @var class
	 *
	 * @param string           $class base object class name
	 * @param multitype:string $properties properties paths list
	 * @param array            $where_array where array expression, indices are columns names
	 * @param Sql_Link         $sql_link
	 */
	public function __construct($class, $properties, $where_array = null, $sql_link = null)
	{
		$this->constructSqlColumnsBuilder($class, $properties);
		$this->constructSqlWhereBuilder($class, $where_array, $sql_link);
	}

	//-------------------------------------------------------------------------------------- getQuery
	/**
	 * Build and returns the full SQL SELECT query
	 *
	 * @return string
	 */
	public function buildQuery()
	{
		// call buildColumns() and buildWhere() before buildTables(), to get joins ready
		$columns = $this->buildColumns();
		$where   = $this->buildWhere();
		$tables  = $this->buildTables();
		return "SELECT " . $columns . " FROM " . $tables . $this->buildWhere();
	}

}
