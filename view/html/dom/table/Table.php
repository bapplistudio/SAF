<?php
namespace SAF\Framework\View\Html\Dom;

use SAF\Framework\View\Html\Dom\Table\Body;
use SAF\Framework\View\Html\Dom\Table\Head;

/**
 * A DOM element class for HTML tables <table>
 */
class Table extends Element
{

	//----------------------------------------------------------------------------------------- $body
	/**
	 * @var Body
	 */
	public $body;

	//----------------------------------------------------------------------------------------- $head
	/**
	 * @var Head
	 */
	public $head;

	//----------------------------------------------------------------------------------- __construct
	/**
	 */
	public function __construct()
	{
		parent::__construct('table');
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		$content = '';
		if (isset($this->head)) $content .= LF . $this->head;
		if (isset($this->body)) $content .= LF . $this->body;
		$this->setContent($content . LF);
		return LF . parent::__toString() . LF;
	}

}
