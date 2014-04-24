<?php
namespace SAF\Framework\RAD;

use SAF\Framework\Mapper;

/**
 * A plugin component is used to store data or process, ie linked to a database
 *
 * @set RAD_Components
 */
class Component
{
	use Mapper\Component;

	//----------------------------------------------------------------------------------------- $name
	/**
	 * @var string
	 */
	public $name;

	//---------------------------------------------------------------------------------------- $rules
	/**
	 * @ling Collection
	 * @var Rule[]
	 */
	public $rules;

}