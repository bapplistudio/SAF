<?php
namespace SAF\Framework;

class Dom_Element
{

	//----------------------------------------------------------------------------------- $attributes
	/**
	 * Available attributes
	 *
	 * @var multitype:Dom_Attribute key is the attribute name
	 */
	private $attributes = array();

	//-------------------------------------------------------------------------------------- $content
	/**
	 * @var string
	 */
	private $content;

	//----------------------------------------------------------------------------------------- $name
	/**
	 * @var string
	 */
	private $name;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param string $name
	 */
	public function __construct($name = null)
	{
		if(isset($name)) $this->name = $name;
	}

	//-------------------------------------------------------------------------------------- addClass
	/**
	 * @param string $class_name
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
			return $class;
		}
	}

	//---------------------------------------------------------------------------------- getAttribute
	/**
	 * @param string $name
	 * @return Dom_Attribute
	 */
	public function getAttribute($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}

	//--------------------------------------------------------------------------------- getAttributes
	/**
	 * @param string $name
	 * @return multitype::Dom_Attribute
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	//---------------------------------------------------------------------------------- setAttribute
	/**
	 * @param string $name
	 * @param string $value
	 * @return Dom_Attribute
	 */
	public function setAttribute($name, $value)
	{
		return $this->setAttributeNode(new Dom_Attribute($name, $value));
	}

	//---------------------------------------------------------------------------------- setAttribute
	/**
	 * @param Dom_Attribute $attr
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

	//------------------------------------------------------------------------------------ __toString
	public function __toString()
	{
		return "<" . $this->name . ($this->attributes ? (" " . join(" ", $this->attributes)) : "") . ">"
			. (isset($this->content) ? ($this->content . "</" . $this->name . ">") : "");
	}

}
