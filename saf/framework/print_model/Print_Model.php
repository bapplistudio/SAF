<?php
namespace SAF\Framework;

/**
 * A print model gives the way to print an object of a given class
 *
 * @representative class
 */
class Print_Model
{

	//---------------------------------------------------------------------------------------- $class
	/**
	 * @var string
	 */
	public $class;

	//---------------------------------------------------------------------------------------- $pages
	/**
	 * @link Collection
	 * @var \SAF\Framework\Print_Model\Page[]
	 */
	public $pages;

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return strval($this->class);
	}

}