<?php
namespace SAF\Framework\Reflection\Annotation\Template;

use SAF\Framework\Reflection\Interfaces\Reflection;

/**
 * A reflection context annotation needs either a property or a class to be properly built
 * Annotations class that are intended to work for both class and properties should implement this
 *
 * @see Class_Context_Annotation
 * @see Property_Context_Annotation
 */
interface Reflection_Context_Annotation
{

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value           string
	 * @param $class_property  Reflection contextual Reflection_Class or Reflection_Property object
	 * @param $annotation_name string
	 */
	public function __construct($value, Reflection $class_property, $annotation_name);

}
