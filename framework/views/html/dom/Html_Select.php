<?php
namespace SAF\Framework;

/**
 * A DOM element class for HTML form select inputs <select><option>...</select>
 */
class Html_Select extends Dom_Element
{

	//------------------------------------------------------------------------------------- $selected
	/**
	 * @var string
	 */
	private $selected;

	//--------------------------------------------------------------------------------------- $values
	/**
	 * @var string[]
	 */
	private $values = array();

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $name     string
	 * @param $values   string[]
	 * @param $selected string
	 * @param $id       string
	 */
	public function __construct($name = null, $values = null, $selected = null, $id = null)
	{
		parent::__construct("select", true);
		if (isset($id))       $this->setAttribute("id",   $id);
		if (isset($name))     $this->setAttribute("name", $name);
		if (isset($values))   $this->values = $values;
		if (isset($selected)) $this->selected($selected);
	}

	//-------------------------------------------------------------------------------------- addValue
	/**
	 * Adds a value
	 *
	 * @param $value   string
	 * @param $caption string
	 */
	public function addValue($value, $caption = null)
	{
		$this->values[$value] = isset($caption) ? $caption : $value;
		$this->setContent(null);
	}

	//------------------------------------------------------------------------------------ getContent
	/**
	 * The getter for $content
	 *
	 * @return string
	 */
	public function getContent()
	{
		$content = parent::getContent();
		if (!isset($content)) {
			$values = $this->values;
			asort($values);
			if (isset($values[""])) {
				$value = $values[""];
				unset($values[""]);
				$values = array("" => $value) + $values;
			}
			$content = "";
			$selected = $this->selected();
			foreach ($values as $value => $caption) {
				$html_option = new Html_Option($value, $caption);
				if ($value === $selected) {
					$html_option->setAttribute("selected");
				}
				$content .= strval($html_option);
			}
			$this->setContent($content);
		}
		return $content;
	}

	//-------------------------------------------------------------------------------------- selected
	/**
	 * @param $selected string if not set, selected will return current value without removing it
	 * @return string
	 */
	public function selected($selected = null)
	{
		if (isset($selected)) {
			$this->selected = $selected;
		}
		return $this->selected;
	}

}
