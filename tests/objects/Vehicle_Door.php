<?php
namespace SAF\Framework\Tests\Objects;

use SAF\Framework\Mapper\Component;

/**
 * A vehicle door
 */
class Vehicle_Door
{
	use Component;

	//----------------------------------------------------------------------------------------- $code
	/**
	 * @var string
	 */
	public $code;

	//--------------------------------------------------------------------------------------- $pieces
	/**
	 * @link Collection
	 * @var Vehicle_Door_Piece[]
	 */
	public $pieces;

	//----------------------------------------------------------------------------------------- $side
	/**
	 * @values front-left, front-right, rear-left, rear-right, trunk
	 * @var string
	 */
	public $side;

	//-------------------------------------------------------------------------------------- $vehicle
	/**
	 * @composite
	 * @link Object
	 * @var Vehicle
	 */
	public $vehicle;

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return strval($this->side);
	}

}
