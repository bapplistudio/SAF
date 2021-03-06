<?php
namespace SAF\Framework\Reflection\Annotation\Property;

use SAF\Framework\Reflection\Annotation;
use SAF\Framework\Reflection\Annotation\Template\Types_Annotation;

/**
 * Tells the remover which method must be called to remove a component object from a composite class
 * for this property.
 *
 * The remover must be a current object's method, or a static method from another class.
 *
 * This can be used into classes that use trait Remover only.
 */
class Remover_Annotation extends Annotation
{
	use Types_Annotation;

}
