<?php
namespace SAF\Framework\Reflection\Annotation\Tests\Replaces;

/**
 * Son test class
 */
class Son extends Parent_Class
{

	//---------------------------------------------------------------------------------- $replacement
	/**
	 * @replaces replaced
	 * @var string
	 */
	public $replacement;

}
