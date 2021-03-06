<?php
namespace SAF\Framework\Sql;

use SAF\Framework\Dao;
use SAF\Framework\Tools\Date_Time;

/**
 * Sql value tool methods
 */
abstract class Value
{

	//---------------------------------------------------------------------------------------- escape
	/**
	 * Escapes a column value, in order to insert it into a SQL query
	 * Adds quotes around string / escaped values
	 *
	 * @param $value            mixed
	 * @param $double_backquote boolean
	 * @return string
	 */
	public static function escape($value, $double_backquote = false)
	{
		// no is_numeric(), as sql numeric search make numeric conversion of string fields
		// ie WHERE NAME = 500 instead of '500' will give you '500' and '500L', which is not correct
		if (
			is_float($value)
			|| is_integer($value)
			|| (is_numeric($value) && $value{0} && (strpos($value, 'E') === false))
		) {
			$string_value = strval($value);
		}
		elseif (is_bool($value)) {
			$string_value = ($value ? '1' : '0');
		}
		elseif ($value === null) {
			$string_value = 'NULL';
		}
		elseif (is_array($value)) {
			$do = false;
			$string_value = '';
			foreach ($value as $object_value) {
				if ($object_value !== null) {
					if ($do) $string_value .= ',';
					$string_value .= str_replace(DQ, DQ . DQ, $object_value);
					$do = true;
				}
			}
			$string_value = substr($string_value, 2);
		}
		elseif ($value instanceof Date_Time) {
			$string_value = DQ . ($value->toISO() ?: '0000-00-00 00:00:00') . DQ;
		}
		else {
			if ((substr($value, 0, 2) === ('X' . Q)) && (substr($value, -1) === Q)) {
				$string_value = $value;
			}
			else {
				$string_value = DQ . Dao::current()->escapeString($value) . DQ;
			}
		}
		return $double_backquote
			? str_replace(BS, BS . BS, str_replace(BS . DQ, DQ . DQ, $string_value))
			: $string_value;
	}

	//---------------------------------------------------------------------------------------- isLike
	/**
	 * Returns true if value represents a 'LIKE' expression
	 *
	 * Checks if value contains non-escaped '%' or '_'.
	 *
	 * @param $value mixed
	 * @return string
	 */
	public static function isLike($value)
	{
		return (substr_count($value, '%') > substr_count($value, BS . '%'))
			|| (substr_count($value, '_') > substr_count($value, BS . '_'));
	}

}
