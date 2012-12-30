<?php
namespace SAF\Framework;

abstract class Html_Table_Section extends Dom_Element
{

	//----------------------------------------------------------------------------------------- $rows
	/**
	 * @contained
	 * @var multitype:Html_Table_Row
	 */
	private $rows = array();

	//------------------------------------------------------------------------------------ __toString
	public function __toString()
	{
		$this->setContent("\n" . join("\n", $this->rows) . "\n");
		return parent::__toString();
	}

	//---------------------------------------------------------------------------------------- addRow
	/**
	 * @param Html_Table_Row $row
	 */
	public function addRow(Html_Table_Row $row)
	{
		$this->rows[] = $row;
	}

}
