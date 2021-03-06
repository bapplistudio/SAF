<?php
namespace SAF\Framework\Reflection\Annotation\Property;

use SAF\Framework\Reflection\Annotation\Template\Boolean_Annotation;
use SAF\Framework\Reflection\Annotation\Template\Property_Context_Annotation;
use SAF\Framework\Reflection\Interfaces\Reflection_Property;

/**
 * This tells that the property can take the null value as a valid value (default is false)
 */
class Null_Annotation extends Boolean_Annotation implements Property_Context_Annotation
{

	const NULL = 'null';

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value    string
	 * @param $property Reflection_Property ie the contextual Reflection_Property object
	 */
	public function __construct($value, Reflection_Property $property)
	{
		parent::__construct($value);
		// default value for @null is true when the property links to a non mandatory object
		if (
			!$this->value
			&& !$property->getAnnotation('mandatory')->value
			&& ($property->getAnnotation('link')->value == Link_Annotation::OBJECT)
		) {
			$this->value = true;
		}
	}

}
