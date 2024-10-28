<?php
namespace Mewz\Framework\Util;

class Date
{
	/**
	 * @param string|int|\DateTimeInterface $datetime
	 * @param bool|\DateTimeZone $local
	 *
	 * @return \DateTimeImmutable
	 */
	public static function get($datetime = 'now', $local = true)
	{
		if ($datetime instanceof \DateTimeInterface) {
			if ($datetime instanceof \DateTimeImmutable) {
				$date = $datetime;
			} elseif ($datetime instanceof \DateTime) {
				$date = \DateTimeImmutable::createFromMutable($datetime);
			} else {
				$date = new \DateTimeImmutable('now', $datetime->getTimezone());
				$date = $date->setTimestamp($datetime->getTimestamp());
			}
		} else {
			if ($local instanceof \DateTimeZone) {
				$tz = $local;
			} else {
				$tz = $local ? self::local_timezone() : self::utc_timezone();
			}

			if (is_numeric($datetime)) {
				$date = (new \DateTimeImmutable('now', $tz));
				$date = $date->setTimestamp($datetime);
			} else {
				$date = new \DateTimeImmutable($datetime, $tz);
			}
		}

		return $date;
	}

	/**
	 * @param string|int|\DateTimeInterface $datetime
	 * @param bool|\DateTimeZone $local
	 *
	 * @return int
	 */
	public static function get_time($datetime = 'now', $local = true)
	{
	    return self::get($datetime, $local)->getTimestamp();
	}

	public static function local_offset()
	{
		static $offset;
		return $offset ??= (int)((float)get_option('gmt_offset') * HOUR_IN_SECONDS);
	}

	public static function local_time()
	{
		return time() + self::local_offset();
	}

	public static function local_strtotime($string)
	{
		return strtotime($string, self::local_time()) - self::local_offset();
	}

	public static function local_timezone()
	{
		static $tz;
		return $tz ??= new \DateTimeZone(wp_timezone_string());
	}

	public static function utc_timezone()
	{
		static $tz;
		return $tz ??= new \DateTimeZone('UTC');
	}

	public static function interval_to_time($interval, \DateTimeInterface $from = null)
	{
		if (!$interval instanceof \DateInterval) {
			$interval = new \DateInterval($interval);
		}

		$from ??= new \DateTime('@0');

		return $from->add($interval)->getTimestamp();
	}

	public static function interval_to_unit($interval)
	{
		if (!$interval instanceof \DateInterval) {
			$interval = new \DateInterval($interval);
		}

		if ($interval->y >= 1) {
			return [$interval->y, 'year'];
		} elseif ($interval->m >= 1) {
			return [$interval->m, 'month'];
		} elseif ($interval->d >= 1) {
			return [$interval->d, 'day'];
		} elseif ($interval->h >= 1) {
			return [$interval->h, 'hour'];
		} elseif ($interval->i >= 1) {
			return [$interval->i, 'minute'];
		} else {
			return [$interval->s, 'second'];
		}
	}

	public static function unit_to_seconds($unit, $number = 1)
	{
		$number = (int)$number;

	    switch ($unit) {
		    case 'year': return $number * YEAR_IN_SECONDS;
		    case 'month': return $number * MONTH_IN_SECONDS;
		    case 'day': return $number * DAY_IN_SECONDS;
		    case 'hour': return $number * HOUR_IN_SECONDS;
		    case 'minute': return $number * MINUTE_IN_SECONDS;
		    default: return $number;
	    }
	}

	public static function format_local($format, $offset = 0)
	{
	    return date($format, self::local_time() + $offset);
	}

	public static function i18n_format($format = 'full', $time = null, $add_offset = null, $sep = ' ')
	{
		if ($time === null) {
			$time = time();

			if ($add_offset === null) {
				$add_offset = true;
			}
		} elseif (is_string($time)) {
			$time = strtotime($time);
		} elseif ($time instanceof \DateTimeInterface) {
			$time = $time->getTimestamp();

			if ($add_offset === null) {
				$add_offset = true;
			}
		} else {
			$time = (int)$time;
		}

		if ($add_offset) {
			$time += self::local_offset();
		}

		switch ($format) {
			case 'full':
				$format = get_option('date_format') . $sep . get_option('time_format');
				break;
			case 'date':
				$format = get_option('date_format');
				break;
			case 'time':
				$format = get_option('time_format');
				break;
			case 'admin-full':
				$format = __('M j, Y @ H:i');
				break;
			case 'admin-short':
				$format = __('Y/m/d g:i:s a');
				break;
			case 'admin-date':
				$format = __('Y/m/d');
				break;
		}

		$format = apply_filters('wooify_date_i18n_format', $format, $time, $sep);

		return date_i18n($format, $time);
	}

	public static function i18n_number_of($num, $type)
	{
	    switch ($type) {
		    case 'second': return sprintf(_n('%s second', '%s seconds', $num), $num);
		    case 'min': return sprintf(_n('%s min', '%s mins', $num), $num);
		    case 'hour': return sprintf(_n('%s hour', '%s hours', $num), $num);
		    case 'day': return sprintf(_n('%s day', '%s days', $num), $num);
		    case 'week': return sprintf(_n('%s week', '%s weeks', $num), $num);
		    case 'month': return sprintf(_n('%s month', '%s months', $num), $num);
		    case 'year': return sprintf(_n('%s year', '%s years', $num), $num);
		    default: return false;
	    }
	}

	public static function wc_date(\DateTimeInterface $datetime)
	{
	    $wcdt = new \WC_DateTime('now', $datetime->getTimezone());
		$wcdt->setTimestamp($datetime->getTimestamp());

		return $wcdt;
	}
}
