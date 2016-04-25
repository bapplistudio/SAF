<?php
namespace SAF\Framework\Locale;

use DateTime;
use Exception;
use SAF\Framework\Tools\Date_Time;

/**
 * Date format locale features : changes date format to comply with user's locale configuration
 */
class Date_Format
{

	//--------------------------------------------------------------------------------------- $format
	/**
	 * @example 'd/m/Y' for the french date format, or 'm/d/Y' for the english one
	 * @var string
	 */
	public $format;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Constructor needs the locale format of the date
	 *
	 * Default date format, if none told, is ISO 'Y-m-d'
	 *
	 * @param $format string eg 'd/m/Y' for the french date format, or 'm/d/Y' for the english one
	 */
	public function __construct($format = null)
	{
		if (isset($format)) {
			$this->format = $format;
		}
		if (!isset($this->format)) {
			$this->format = 'Y-m-d';
		}
	}

	//---------------------------------------------------------------------------------- advancedDate
	/**
	 * @param $date   string an incomplete locale format date : day alone, year alone, compositions
	 * @param $joker  string if set, the character that replaces missing values, instead of current
	 * @return string the complete locale date eg 2015-30-25
	 */
	private function advancedDate($date, $joker = null)
	{
		// two values with a middle slash
		if (substr_count($date, SL) == 1) {
			list($one, $two) = explode(SL, $date);
			// the first number is a year : year/month
			if (strlen($one) > 2) {
				$date = sprintf('%04s-%02s-' . ($joker ? ($joker . $joker) : '01'), $one, $two);
			}
			// the second number is a year : month/year
			elseif (strlen($two) > 2) {
				$date = sprintf('%04s-%02s-' . ($joker ? ($joker . $joker) : '01'), $two, $one);
			}
			// these are small numbers : day/month or month/day, depending on the locale format
			elseif (strpos($this->format, 'd/m') !== false) {
				$date = sprintf(date('Y') . '-%02s-%02s', $two, $one);
			}
			else {
				$date = sprintf(date('Y') . '-%02s-%02s', $one, $two);
			}
		}
		//echo "date = $date<br>";
		// 1 or 2 digits : day alone : add current month/day and year
		if (in_array(strlen($date), [1, 2])) {
			$date = $joker
				// joker = search : this is a month
				? date('Y-' . sprintf('%02s', $date) . '-' . $joker . $joker)
				// no joker = input : this is the day of the current month
				: date('Y-m-' . sprintf('%02s', $date));
		}
		// 3 and more digits : year alone : add january the 1st
		elseif (is_numeric($date)) {
			$date = sprintf('%04s', $date) . '-01-01';
		}
		//echo "result = $date<br>";
		return $date;
	}

	//------------------------------------------------------------------------------------- appendMax
	/**
	 * Append max date / time to an incomplete ISO date
	 * eg 2015-10-01 will become 2015-10-01 23:59:59
	 *
	 * @param $date string
	 * @return string
	 */
	public function appendMax($date)
	{
		if (strlen($date) == 4) {
			$date .= '-12-31 23:59:59';
		}
		elseif (strlen($date) == 7) {
			$days_of_month = (new Date_Time($date . '-01'))->daysInMonth();
			$date .= '-' . $days_of_month . SP . '23:59:59';
		}
		elseif (strlen($date) == 10) {
			$date .= SP . '23:59:59';
		}
		elseif (strlen($date) >= 13) {
			while (strlen($date) < 19) {
				$date .= ':59';
			}
		}
		return $date;
	}

	//----------------------------------------------------------------------------------------- toIso
	/**
	 * Takes a locale date and make it ISO
	 *
	 * @param $date  string ie '12/25/2001' '12/25/2001 12:20' '12/25/2001 12:20:16'
	 * @param $max   boolean if true, the incomplete date will be completed to the max range
	 * eg '25/12/2001' will result in '2001-12-25 00:00:00' if false, '2001-12-25 23:59:59' if true
	 * @param $joker string if set, the character that replaces missing values, instead of current
	 * @return string ie '2001-12-25' '2001-12-25 12:20:00' '2001-12-25 12:20:16'
	 */
	public function toIso($date, $max = false, $joker = null)
	{
		if (empty($date)) {
			return '0000-00-00';
		}
		$date = $this->advancedDate($date, $joker);
		if (strlen($date) == 10) {
			if ($max) {
				$date .= SP . '23:59:59';
			}
			elseif ($joker) {
				$date .= SP . $joker . $joker . ':' . $joker . $joker . ':' . $joker . $joker;
			}
			elseif (strpos($date, '-') === false) {
				$datetime = DateTime::createFromFormat($this->format, $date);
				return $datetime ? $datetime->format('Y-m-d') : $date;
			}
			else {
				return $date . SP . (
					$joker
						? ($joker . $joker . ':' . $joker . $joker . ':' . $joker . $joker)
						: '00:00:00'
				);
			}
		}
		elseif (strpos($date, SP)) {
			list($date, $time) = explode(SP, $date);
			while (strlen($time) < 8) {
				$time .= $joker ? (':' . $joker . $joker) : ($max ? ':59' : ':00');
			}
			$datetime = DateTime::createFromFormat($this->format, $date);
			return trim($datetime ? ($datetime->format('Y-m-d') . SP . $time) : $date . SP . $time);
		}
		return $date;
	}

	//-------------------------------------------------------------------------------------- toLocale
	/**
	 * Takes an ISO date and make it locale
	 *
	 * @param $date string|Date_Time ie '2001-12-25' '2001-12-25 12:20:00' '2001-12-25 12:20:16'
	 * @return string '25/12/2011' '25/12/2001 12:20' '25/12/2001 12:20:16'
	 */
	public function toLocale($date)
	{
		// in case of $date being an object, ie Date_Time, get an ISO date only
		if ($date instanceof DateTime) {
			$date = $date->format('Y-m-d H:i:s');
		}
		try {
			if (empty($date) || (new Date_Time($date))->isMin()) {
				return '';
			}
		}
		catch (Exception $e) {
			return $date;
		}
		if (strlen($date) == 10) {
			return DateTime::createFromFormat('Y-m-d', $date)->format($this->format);
		}
		else {
			list($date, $time) = strpos($date, SP) ? explode(SP, $date) : [$date, ''];
			if ((strlen($time) == 8) && (substr($time, -3) == ':00')) {
				substr($time, 0, 5);
			}
			$result = ($date_time = DateTime::createFromFormat('Y-m-d', $date))
				? ($date_time->format($this->format) . SP . $time)
				: $date;
			if (substr($result, -9) == ' 00:00:00') {
				$result = substr($result, 0, -9);
			}
			elseif (substr($result, -3) == ':00') {
				$result = substr($result, 0, -3);
			}
			return $result;
		}
	}

}