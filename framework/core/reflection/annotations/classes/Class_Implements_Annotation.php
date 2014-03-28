<?php
namespace SAF\Framework;

/**
 * This must be used for traits that implement some interfaces
 * Builder will use it to implicitly add 'implements' clauses
 */
class Class_Implements_Annotation extends List_Annotation
{

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value string
	 */
	public function __construct($value)
	{
		parent::__construct($value);
		foreach ($this->values() as $key => $value) {
			if ($value[0] === BS) {
				$this->value[$key] = substr($value, 1);
			}
		}
	}

}
