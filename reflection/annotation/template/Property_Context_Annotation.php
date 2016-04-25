<?php
namespace SAF\Framework\Reflection\Annotation\Template;

use SAF\Framework\Reflection\Interfaces\Reflection_Property;

/**
 * A property context annotation needs the property to be properly built
 */
interface Property_Context_Annotation
{

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value    string
	 * @param $property Reflection_Property ie the contextual Reflection_Property object
	 */
	public function __construct($value, Reflection_Property $property);

}