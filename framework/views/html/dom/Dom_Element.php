<?php
namespace SAF\Framework;

abstract class Dom_Element
{

	//----------------------------------------------------------------------------------- $attributes
	/**
	 * Available attributes
	 *
	 * @var Dom_Attribute[] key is the attribute name
	 */
	private $attributes = array();

	//-------------------------------------------------------------------------------------- $content
	/**
	 * @var string
	 */
	private $content;

	//-------------------------------------------------------------------------------------- $end_tag
	/**
	 * @var boolean
	 */
	protected $end_tag;

	//----------------------------------------------------------------------------------------- $name
	/**
	 * @var string
	 */
	private $name;

	//--------------------------------------------------------------------------------------- $styles
	/**
	 * @var string[]
	 */
	private $styles = array();

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $name    string
	 * @param $end_tag boolean
	 */
	public function __construct($name = null, $end_tag = true)
	{
		if (isset($name)) $this->name = $name;
		$this->end_tag = $end_tag;
	}

	//-------------------------------------------------------------------------------------- addClass
	/**
	 * @param $class_name string
	 * @return Dom_Attribute
	 */
	public function addClass($class_name)
	{
		$class = $this->getAttribute("class");
		if (!isset($class)) {
			return $this->setAttribute("class", $class_name);
		}
		elseif (strpos(" " . $class->value . " ", $class_name) === false) {
			$class->value .= " " . $class_name;
		}
		return $class;
	}

	//---------------------------------------------------------------------------------- getAttribute
	/**
	 * @param $name string
	 * @return Dom_Attribute
	 */
	public function getAttribute($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}

	//--------------------------------------------------------------------------------- getAttributes
	/**
	 * @param $name string
	 * @return :Dom_Attribute[]
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	//---------------------------------------------------------------------------------- setAttribute
	/**
	 * @param $name string
	 * @param $value string
	 * @return Dom_Attribute
	 */
	public function setAttribute($name, $value)
	{
		if ($name == "name") {
			$value = str_replace(".", ">", $value);
		}
		return $this->setAttributeNode(new Dom_Attribute($name, $value));
	}

	//---------------------------------------------------------------------------------- setAttribute
	/**
	 * @param $attr Dom_Attribute
	 * @return Dom_Attribute
	 */
	public function setAttributeNode(Dom_Attribute $attr)
	{
		return $this->attributes[$attr->name] = $attr;
	}

	//------------------------------------------------------------------------------------ setContent
	public function setContent($content)
	{
		$this->content = $content;
	}

	//-------------------------------------------------------------------------------------- setStyle
	public function setStyle($key, $value)
	{
		$this->styles[$key] = new Dom_Style($key, $value);
	}

	//------------------------------------------------------------------------------------ __toString
	public function __toString()
	{
		if ($this->styles) {
			$this->setAttribute("style", join("; ", $this->styles));
		}
		return "<" . $this->name . ($this->attributes ? (" " . join(" ", $this->attributes)) : "") . ">"
			. (($this->end_tag || isset($this->content)) ? ($this->content . "</" . $this->name . ">") : "");
	}

}
