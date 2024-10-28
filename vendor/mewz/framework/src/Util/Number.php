<?php
namespace Mewz\Framework\Util;

class Number
{
	public static $comma_point = false;

	/**
	 * @param numeric $value
	 *
	 * @return numeric-string
	 */
	public static function safe_decimal($value)
	{
		if (!is_numeric($value)) {
			return '';
		}

		$float = (float)$value;

		if ($float === -0.0) {
			return '0';
		}

		return self::$comma_point ? str_replace(',', '.', $float) : (string)$float;
	}

	/**
	 * @param numeric $value
	 * @param int $precision
	 *
	 * @return string
	 */
	public static function local_format($value, $precision = 5)
	{
		return rtrim(rtrim(number_format_i18n((float)$value, $precision), '0'), '., ');
	}
}

Number::$comma_point = ((string)0.1)[1] === ',';
