<?php
namespace SAF\Framework\Tests\Objects;

/**
 * A salesman with specific data for its link to a quote and with additional specific data
 *
 * The 'link' annotation allows to consider this class as a link class
 *
 * @link Quote_Salesman
 * @set Quotes_Salesmen_Additional
 */
class Quote_Salesman_Additional extends Quote_Salesman
{

	//------------------------------------------------------------------------------ $additional_text
	/**
	 * @var string
	 * @multiline
	 */
	public $additional_text;

}
