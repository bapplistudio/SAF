<?php
namespace SAF\Framework\Widget\Edit;

use SAF\Framework\Builder;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\View\Html\Builder\Map;
use SAF\Framework\View\Html\Dom\Table\Body;
use SAF\Framework\View\Html\Dom\Table\Row;
use SAF\Framework\View\Html\Dom\Table\Standard_Cell;

/**
 * Takes a map of objects and build a HTML edit subform containing their data
 */
class Html_Builder_Map extends Map
{

	//-------------------------------------------------------------------------------------- $preprop
	/**
	 * Property name prefix
	 *
	 * @var string
	 */
	public $preprop;

	//------------------------------------------------------------------------------------- $template
	/**
	 * @var Html_Template
	 */
	private $template = null;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $property Reflection_Property
	 * @param $map      object[]
	 * @param $preprop  string
	 */
	public function __construct(Reflection_Property $property, $map, $preprop = null)
	{
		parent::__construct($property, $map);
		$this->preprop = $preprop;
	}

	//------------------------------------------------------------------------------------- buildBody
	/**
	 * @return Body
	 */
	protected function buildBody()
	{
		$body = parent::buildBody();
		$row = $this->buildRow(Builder::create($this->class_name));
		$row->addClass('new');
		$body->addRow($row);
		return $body;
	}

	//------------------------------------------------------------------------------------- buildCell
	/**
	 * @param $object object
	 * @return Standard_Cell
	 */
	protected function buildCell($object)
	{
		$property = $this->property;
		$value = $object;
		$preprop = $this->preprop ?: $property->name;
		$input = (new Html_Builder_Type('', $property->getType()->getElementType(), $value, $preprop))
			->setTemplate($this->template)
			->build();
		return new Standard_Cell($input);
	}

	//------------------------------------------------------------------------------------- buildHead
	/**
	 * @return string
	 */
	protected function buildHead()
	{
		$head = parent::buildHead();
		foreach ($head->rows as $row) {
			$row->addCell(new Standard_Cell(''));
		}
		return $head;
	}

	//-------------------------------------------------------------------------------------- buildRow
	/**
	 * @param $object object
	 * @return Row
	 */
	protected function buildRow($object)
	{
		$row = parent::buildRow($object);
		$cell = new Standard_Cell('-');
		$cell->setAttribute('title', '|remove line|');
		$cell->addClass('minus');
		$row->addCell($cell);
		return $row;
	}

	//----------------------------------------------------------------------------------- setTemplate
	/**
	 * @param $template Html_Template
	 * @return Html_Builder_Type
	 */
	public function setTemplate(Html_Template $template)
	{
		$this->template = $template;
		return $this;
	}

}
