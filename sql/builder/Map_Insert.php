<?php
namespace SAF\Framework\Sql\Builder;

use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Sql\Value;

/**
 * SQL insert queries builder for a mapped object
 */
class Map_Insert
{

	//------------------------------------------------------------------------------------- $property
	/**
	 * @var Reflection_Property
	 */
	private $property;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $property Reflection_Property
	 */
	public function __construct(Reflection_Property $property)
	{
		$this->property = $property;
	}

	//------------------------------------------------------------------------------------ buildQuery
	/**
	 * @param $object         object
	 * @param $foreign_object object
	 * @return string
	 */
	public function buildQuery($object, $foreign_object)
	{
		list($table, $field1, $field2, $id1, $id2) = Map::sqlElementsOf(
			$object, $this->property, $foreign_object
		);
		if ($this->property->getType()->getElementTypeAsString() == 'object') {
			$class_field = substr($field2, 3) . '_class';
			return 'INSERT INTO' . SP . BQ . $table . BQ
			. LF . 'SET ' . $field1 . ' = ' . $id1 . ', ' . $field2 . ' = ' . $id2
			. ', ' . $class_field . ' = ' . Value::escape(get_class($foreign_object));
		}
		else {
			return 'INSERT INTO' . SP . BQ . $table . BQ
				. LF . 'SET ' . $field1 . ' = ' . $id1 . ', ' . $field2 . ' = ' . $id2;
		}
	}

}
