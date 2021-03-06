<?php
namespace SAF\Framework\Widget\Data_List;

use Exception;
use SAF\Framework\Dao\Func;
use SAF\Framework\Dao\Func\Logical;
use SAF\Framework\Dao\Option;
use SAF\Framework\Locale\Loc;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Reflection\Type;
use SAF\Framework\Tools\Date_Time;
use SAF\Framework\Widget\Data_List\Search_Parameters_Parser\Date;
use SAF\Framework\Widget\Data_List\Search_Parameters_Parser\Type_Boolean;

/**
 * Search parameters parser
 *
 * - Search grammar (and so algorithm):
 *
 * spaces are optionals
 * search       = orexpr
 * orexpr       = andexpr [, andexpr [...]]]
 * andexpr      = notexpr [& notexpr [...]]
 * notexpr      = ["!"]complexvalue
 * complexvalue = singlevalue
 * singlevalue  = emptyword | scalar
 * emptyword    = "empty" | "null" | localized equivalent
 * scalar       = sentence that may contains wildcards
 * joker        = "?" | "*" | "%" | "_"
 *
 * - Especially for Boolean :
 * singlevalue  = emptyword | booleanvalue
 * booleanvalue = booleanword
 *              | "0" | "1" | number != 0 (for true)
 * booleanword  = "no" | "yes" | "n" | "y" | "false" | "true"
 *
 * - Especially for Date_Time, Float, Integer, String fields :
 * complexvalue = range | singlevalue
 * range        = minrgnvalue "-" maxrngvalue
 *
 * - Especially for Float, Integer, String fields :
 * minrngvalue  = scalar
 * maxrngvalue  = scalar
 *
 * - Especially for Date_Time
 * singlevalue  = dateperiod   (that will be updated to both a min and a max value of the period)
 * minrngvalue  = daterngvalue (that will be updated to its min value)
 * maxrngvalue  = daterngvalue (that will be updated to its max value)
 * daterngvalue = dateperiod without any joker
 * dateperiod   = dateword | emptyword | wildcard
 *              | [d]d/[m]m/yyyy
 *              | [m]m/yyyy | yyyy/[m]m (means from 01/mm/yyyy to 31!/mm/yyyy) 3-4 chars mandatory for yyyy
 *              | [d]d/[m]m         (means implicit current year)
 *              | yyyy              (means from 01/01/yyyy to 31/12/yyyy) 3-4 chars mandatory
 *              | [d]d              (means implicit current month and year)
 *              | "y" [+|-] integer (means from 01/01/yyyy to 31/12/yyyy)
 *              | "m" [+|-] integer (means from 01/mm/currentyear to 31!/mm/currentyear)
 *              | "d" [+|-] integer (means implicit current month and year)
 * dateword     = "current year" | "current month" | localized equivalent
 *              | "today" | "current day" | localized equivalent
 *              | "now" (means with current time?)
 * dd           = #[0-3?]?[0-9?]|*#  |  "d" (+|-) integer
 * mm           = #[0-1?]?[0-9?]|*# | "m" (+|-) integer
 * yyyy         = #[0-9?]{4}|*# | "y" (+|-) integer //is it possible to check year about "*" only? we can not be sure this is a year!
 *
 * If there is any joker (*?) on a dd, mmm or yyyy, it will be converted to a LIKE search
 * Otherwise any date will be converted to a period from midnight to 23h59:59
 * For ranges, min date will be converted to midnight and maxdate to 23h59:59
 *
 * TODO naming conventions for properties : $property_name
 * TODO alphabetical order of the methods
 *
 * Parse user-input search strings to get valid search arrays in return
 */
class Search_Parameters_Parser
{
	use Date;
	use Type_Boolean;

	//------------------------------------------------------------------------------- MAX_RANGE_VALUE
	const MAX_RANGE_VALUE = 1;

	//------------------------------------------------------------------------------- MIN_RANGE_VALUE
	const MIN_RANGE_VALUE = -1;

	//----------------------------------------------------------------------------- NOT_A_RANGE_VALUE
	const NOT_A_RANGE_VALUE = 0;

	//---------------------------------------------------------------------------------------- $class
	/**
	 * @var Reflection_Class
	 */
	public $class;

	//--------------------------------------------------------------------------------------- $search
	/**
	 * @var array
	 */
	public $search;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class_name string
	 * @param $search     array user-input search string
	 */
	public function __construct($class_name, $search)
	{
		$this->class  = new Reflection_Class($class_name);
		$this->search = $search;
		$this->initDates();
	}

	//-------------------------------------------------------------------------------------- applyAnd
	/**
	 * @param $search_value string
	 * @param $property     Reflection_Property
	 * @return Logical
	 */
	protected function applyAnd($search_value, Reflection_Property $property)
	{
		if (is_string($search_value) && (strpos($search_value, '&') !== false)) {
			$and = [];
			foreach (explode('&', $search_value) as $search) {
				$and[] = $this->applyNot($search, $property);
			}
			return Func::andOp($and);
		}
		else {
			$search = $this->applyNot($search_value, $property);
			return $search;
		}
	}

	//----------------------------------------------------------------------------- applyComplexValue
	/**
	 * @param $search_value string
	 * @param $property     Reflection_Property
	 * @return mixed        Range | Func | scalar
	 * @throws Data_List_Exception
	 */
	protected function applyComplexValue($search_value, Reflection_Property $property)
	{
		if ($this->isRange($search_value, $property)) {
			if ($this->hasRange($property)) {
				$search = $this->applyRange($search_value, $property);
			}
			else {
				throw new Data_List_Exception($search_value, Loc::tr('Range not permitted'));
			}
		}
		else {
			$search = $this->applySingleValue($search_value, $property);
		}
		return $search;
	}

	//-------------------------------------------------------------------------------- applyEmptyWord
	/**
	 * If expression is a date empty word, convert to corresponding value
	 *
	 * @param $expression string
	 * @return mixed|boolean false
	 */
	protected function applyEmptyWord($expression)
	{
		if ($this->isEmptyWord($expression)) {
			return Func::isNull();
		}
		// not an empty word
		return false;
	}

	//----------------------------------------------------------------------------------- applyJokers
	/**
	 * @param $search_value   string
	 * @param $is_range_value boolean  true if we parse a range value
	 * @return string
	 */
	protected function applyJokers($search_value, $is_range_value = false)
	{
		if (is_string($search_value)) {
			//$search = str_replace(['*', '?'], ['%', '_'], $search_value);
			$search = preg_replace(['/[*%]/', '/[?_]/'], ['%', '_'], $search_value, -1, $count);
			if ($count && !$is_range_value) {
				$search = Func::like($search);
			} /*else {
				$search = Func::equal($search);
			}*/
			return $search;
		}
		return $search_value;
	}

	//-------------------------------------------------------------------------------------- applyNot
	/**
	 * @param $search_value string
	 * @param $property     Reflection_Property
	 * @return Logical
	 */
	protected function applyNot($search_value, Reflection_Property $property)
	{
		if (is_string($search_value) && (substr(trim($search_value), 0, 1) === '!')) {
			$search_value = substr(trim($search_value), 1);
			$search = $this->applyComplexValue($search_value, $property);
			if ($search instanceof Func\Negate) {
				$search->negate();
			} else {
				$search = Func::notEqual($search);
			}
		} else {
			$search = $this->applyComplexValue($search_value, $property);
		}
		return $search;
	}

	//--------------------------------------------------------------------------------------- applyOr
	/**
	 * @param $search_value string
	 * @param $property     Reflection_Property
	 * @return Logical
	 */
	protected function applyOr($search_value, Reflection_Property $property)
	{
		if (is_string($search_value) && (strpos($search_value, ',') !== false)) {
			$or = [];
			foreach (explode(',', $search_value) as $search) {
				$or[] = $this->applyAnd($search, $property);
			}
			return Func::orOp($or);
		}
		else {
			$search = $this->applyAnd($search_value, $property);
			return $search;
		}
	}

	//------------------------------------------------------------------------------------ applyRange
	/**
	 * Apply a range expression on search string. The range is supposed to exist !
	 *
	 * @param $search_value string|Option
	 * @param $property     Reflection_Property
	 * @return Func\Range
	 * @throws Data_List_Exception
	 */
	protected function applyRange($search_value, Reflection_Property $property)
	{
		$range    = $this->getRangeParts($search_value, $property);
		$range[0] = $this->applyRangeValue($range[0], $property, self::MIN_RANGE_VALUE);
		$range[1] = $this->applyRangeValue($range[1], $property, self::MAX_RANGE_VALUE);
		if ($range[0] === false || $range[1] === false) {
			throw new Data_List_Exception(
				$search_value, Loc::tr('Error in range expression or range must have 2 parts only')
			);
		}
		return new Func\Range($range[0], $range[1]);
	}

	//------------------------------------------------------------------------------- applyRangeValue
	/**
	 * @param $search_value string|Option
	 * @param $property     Reflection_Property
	 * @param $min_max      integer  ::MIN_RANGE_VALUE | ::MAX_RANGE_VALUE | ::NOT_A_RANGE_VALUE
	 * @return mixed
	 */
	protected function applyRangeValue($search_value, Reflection_Property $property, $min_max)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// Date_Time type
			case Date_Time::class:
				$search = $this->applyDateRangeValue($search_value, $min_max);
				break;
			// Float | Integer | String types
			//case in_array($type_string, [Type::FLOAT, Type::INTEGER, Type::STRING]): {
			default:
				$search = $this->applyScalar($search_value, $property, true);
				break;
		}
		return $search;
	}

	//----------------------------------------------------------------------------------- applyScalar
	/**
	 * @param $search_value   string
	 * @param $property       Reflection_Property
	 * @param $is_range_value boolean  true if we parse a range value
	 * @return string
	 */
	protected function applyScalar(
		/** @noinspection PhpUnusedParameterInspection */
		$search_value, Reflection_Property $property, $is_range_value = false
	) {
		// check if we are on a enum field with @values list of values
		$values = $property->getListAnnotation('values')->values();
		if (count($values)) {
			//we do not apply wildcards, we want search for this exact value
			return Func::equal($search_value);
		}
		return $this->applyJokers($search_value, $is_range_value);
	}

	//------------------------------------------------------------------------------ applySingleValue
	/**
	 * @param $search_value string|Option
	 * @param $property     Reflection_Property
	 * @return mixed
	 * @throws Data_List_Exception
	 */
	protected function applySingleValue($search_value, Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// boolean type
			case Type::BOOLEAN: {
				if (($search = $this->applyEmptyWord($search_value)) !== false) {
					break;
				}
				$search = $this->applyBooleanValue($search_value);
				break;
			}
			// Date_Time type
			case Date_Time::class: {
				$search = $this->applyDatePeriod($search_value);
				break;
			}
			// Float | Integer | String types
			//case in_array($type_string, [Type::FLOAT, Type::INTEGER, Type::STRING]): {
			default: {
				if (($search = $this->applyEmptyWord($search_value)) !== false) {
					break;
				}
				$search = $this->applyScalar($search_value, $property);
				break;
			}
		}
		if ($search === false) {
			throw new Data_List_Exception($search_value, Loc::tr('Error in expression'));
		}
		return $search;
	}

	//---------------------------------------------------------------------------- getCompressedWords
	/**
	 * Trim words, removes spaces and some chars like apostrophe, then transliterate to remove accents
	 *
	 * @param $words string[]
	 * @return string[]
	 */
	protected function getCompressedWords($words)
	{
		array_walk($words, function(&$word) {
			/**
			 * TODO iconv with //TRANSLIT requires that locale is different than C or Posix. To Do: a better support!!
			 * See: http://php.net/manual/en/function.iconv.php#74101
			 */
			$word = preg_replace('/\s|\'/', '', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $word)));
		});
		return $words;
	}

	//--------------------------------------------------------------------------------- getRangeParts
	/**
	 * Apply a range expression on search string. The range is supposed to exist !
	 *
	 * @param $search_value string|Option
	 * @param $property     Reflection_Property
	 * @return array
	 * @throws Data_List_Exception
	 */
	protected function getRangeParts($search_value, Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// Date_Time type
			case Date_Time::class:
				// Take care of char of formulas on expr like 'm-3-m', '01/m-2/2015-01/m-2/2016'...
				// pattern of a date that may contain formula
				$pattern = $this->getDateSubPattern();
				// We should analyse 1st the right pattern to solve cases like 1/5/y-1/7/y
				// We should parse like min=1/5/y and max=1/7/y
				// and not parse like min=1/5/y-1 and max=/7/y
				$pattern_right = "/[-](\\s* $pattern \\s* )$/x";
				$found = preg_match($pattern_right, $search_value, $matches);
				if ($found) {
					$max = trim($matches[1]);
					$min = trim(substr($search_value, 0, -(strlen($matches[1]) + 1)));
					$range = [$min, $max];
				}
				else {
					throw new Data_List_Exception(
						$search_value, Loc::tr('Error in range expression or range must have 2 parts only')
					);
				}
				break;
			// Float | Integer | String types
			// case in_array($type_string, [Type::FLOAT, Type::INTEGER, Type::STRING]): {
			default:
				$range = explode('-', $search_value, 2);
				// Check we have only two parts in the range!
				if (implode('-', $range) !== $search_value) {
					throw new Data_List_Exception($search_value, Loc::tr('Range must have 2 parts only'));
				}
				break;
		}
		return $range;
	}

	//-------------------------------------------------------------------------------------- hasJoker
	/**
	 * Check if expression has any wildcard
	 *
	 * @param $search_value string
	 * @return boolean
	 */
	protected function hasJoker($search_value)
	{
		return preg_match('/[*?%_]/', $search_value)
			? true
			: false;
	}

	//-------------------------------------------------------------------------------------- hasRange
	/**
	 * Checks if a property has right to have range in search string
	 *
	 * @param $property Reflection_Property
	 * @return boolean true if range supported and authorized
	 */
	protected function hasRange(Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		return ($property->getAnnotation('search_range')->value !== false)
			&& in_array($type_string, [Date_Time::class, Type::FLOAT, Type::INTEGER, Type::STRING]);
	}

	//----------------------------------------------------------------------------------- isEmptyWord
	/** Check if expression is an empty word
	 * @param $expr string
	 * @return boolean true if empty word
	 */
	protected function isEmptyWord($expr)
	{
		/**
		 * TODO iconv with //TRANSLIT requires that locale is different than C or Posix. To Do: a better support !!
		 * See: http://php.net/manual/en/function.iconv.php#74101
		 */
		$word = preg_replace(
			'/\s|\'/', '', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', Loc::rtr($expr)))
		);
		return in_array($word, ['empty', 'none', 'null']);
	}

	//--------------------------------------------------------------------------------------- isRange
	/**
	 * Check if expression is a range expression
	 *
	 * @param $search_value string
	 * @param $property     Reflection_Property
	 * @return boolean
	 */
	protected function isRange($search_value, Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// Date_Time type
			case Date_Time::class: {
				if (
					is_string($search_value)
					// take care of formula that may contains char '-'
					&& !$this->isASingleDateFormula($search_value)
					&& (strpos($search_value, '-') !== false)
				) {
					return true;
				}
				break;
			}
			default: {
				if (is_string($search_value) && (strpos($search_value, '-') !== false)) {
					return true;
				}
				break;
			}
		}
		return false;
	}

	//----------------------------------------------------------------------------------------- parse
	/**
	 * @return array search-compatible search array
	 */
	public function parse()
	{
		$search = $this->search;
		$to_unset = [];
		foreach ($search as $property_path => &$search_value) {
			$property = new Reflection_Property($this->class->name, $property_path);
			if (strlen($search_value)) {
				$this->parseField($search_value, $property);
				// if search has been transformed to empty string, we cancel search for this column
				if (is_string($search_value) && !strlen($search_value)) {
					$to_unset[] = $property_path;
				}
			}
		}
		foreach ($to_unset as $property_path) {
			unset($search[$property_path]);
		}
		return $search;
	}

	//------------------------------------------------------------------------------------ parseField
	/**
	 * @param $search_value string
	 * @param $property Reflection_Property
	 */
	protected function parseField(&$search_value, Reflection_Property $property)
	{
		try {
			$search_value = $this->applyOr($search_value, $property);
		}
		catch (Exception $e) {
			$search_value = $e;
		}
	}

}
