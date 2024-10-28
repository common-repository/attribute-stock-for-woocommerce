<?php
namespace Mewz\QueryBuilder;

class DB
{
	/* Query types */
	const SELECT = 'SELECT';
	const INSERT = 'INSERT';
	const REPLACE = 'REPLACE';
	const UPDATE = 'UPDATE';
	const DELETE = 'DELETE';

	/** @var string Modifier for `bind()` method to skip preparing values */
	const RAW = 'RAW';

	/** @var string Modifier for `where()` method to get a NOT condition */
	const NOT = 'NOT';

	/** @var \wpdb */
	public static $wpdb;

	/** @var \mysqli */
	protected static $mysqli;

	/** @var array NOT variants of special operators */
	protected static $not_operators = [
		'=' => '!=',
		'IN' => 'NOT IN',
		'IS' => 'IS NOT',
		'LIKE' => 'NOT LIKE',
		'BETWEEN' => 'NOT BETWEEN',
		'EXIST' => 'NOT EXIST',
	];

	/**
	 * Bootstrap the class.
	 *
	 * @internal
	 */
	public static function __init()
	{
		global $wpdb;

		// save wpdb reference for easy access
		self::$wpdb = $wpdb;

		// save mysqli instance to escape strings directly
		if ($wpdb->use_mysqli) {
			self::$mysqli = $wpdb->dbh;
		}
	}

	/**
	 * Register tables with $wpdb. This should be done as early as possible.
	 *
	 * @param string|array $tables Table names without prefix
	 */
	public static function register($tables)
	{
		global $wpdb;

		foreach ((array)$tables as $table) {
			$wpdb->$table = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Start building your WordPress database query. Now in Technicolor!
	 *
	 * @example DB::table('users')->get(5);
	 * @example DB::table('posts', 'p')->newest('p.post_date')->one();
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return Query
	 */
	public static function table($name, $alias = null)
	{
		return (new Query())->table($name, $alias);
	}

	public static function select($expr, ...$values)
	{
		return (new Query())->select($expr, ...$values);
	}

	public static function insert($table, $data, $type = self::INSERT)
	{
		return (new Query())->table($table)->insert($data, $type);
	}

	/**
	 * Execute a SQL query with optional value bindings.
	 *
	 * @see DB::bind()
	 *
	 * @param string $sql
	 * @param mixed ...$values
	 *
	 * @return int|false
	 */
	public static function query($sql, ...$values)
	{
		if ($values) {
			$sql = self::bind($sql, ...$values);
		}

		return self::$wpdb->query($sql);
	}

	/**
	 * Prepare a SQL statement or phrase by binding raw values to it.
	 *
	 * Can use `DB::RAW` modifier to avoid escaping and quoting values. Useful for preparing LIKE strings.
	 * In this case the returned statement must still be escaped and quoted before using directly in a query.
	 *
	 * @example DB::bind('user_login = ?', $username) => "user_login = 'ninja\'87%'" <-- Escaped
	 * @example DB::bind(DB::RAW, '%?%', $wpdb->esc_like($username)) => "%ninja'87\%%" <-- Still needs escaping
	 *
	 * @param string $expr SQL query or expression containing ? placeholders for binding values to.
	 * @param mixed ...$values Raw values to be escaped, quoted and inserted into the query. If only a single value, it will replace all placeholders.
	 *
	 * @return string The prepared SQL string, ready to rock 'n roll.
	 */
	public static function bind($expr, ...$values)
	{
		if ($expr === self::RAW) {
			$expr = array_shift($values);
		} elseif ($values) {
			foreach ($values as &$v) {
				$v = self::value($v);
			}
		}

		if ($values) {
			if (!isset($values[1])) {
				return str_replace('?', $values[0], $expr);
			} else {
				return sprintf(str_replace(['%', '?'], ['%%', '%s'], $expr), ...$values);
			}
		}

		return $expr;
	}

	/**
	 * Build a formatted and escaped SQL condition to be used in a WHERE or HAVING clause.
	 *
	 * @example DB::where('name', $name) => "name = 'snowball'"
	 * @example DB::where('name', 'like', '%?%', $name) => "name LIKE '%snowball%'"
	 * @example DB::where('breed', ['tabby', 'bengal', $breed]) => "breed IN ('tabby', 'bengal', 'maine coon')"
	 * @example DB::where('cats.age', '>=', 3) => "cats.age >= 3"
	 * @example DB::where('age', 'NOT BETWEEN', 7, 9) => "age NOT BETWEEN 7 AND 9"
	 * @example DB::where('cuteness', '!=', null) => "cuteness IS NOT NULL"
	 * @example DB::where(DB::NOT, 'breed', ['tabby', 'bengal', $breed]) => "breed NOT IN ('tabby', 'bengal', 'maine coon')"
	 * @example DB::where(DB::NOT, 'age', 'BETWEEN', 7, 9) => "age NOT BETWEEN 7 AND 9"
	 * @example DB::where(DB::NOT, 'cuteness', false) => "cuteness IS NOT FALSE"
	 * @example DB::where(DB::NOT, 'cuteness', '=', false) => "cuteness != FALSE"
	 *
	 * @param string $column
	 * @param mixed ...$value
	 *
	 * @return string
	 */
	public static function where($column, ...$value)
	{
		$not_modifier = false;

		if ($column === self::NOT) {
			$not_modifier = true;
			$column = array_shift($value);
		}

		$value_count = count($value);

		if ($value_count > 1) {
			$operator = strtoupper($value[0]);
			$operand = $value[1];
			$prepend_not = $not_modifier;

			if ($not_modifier && isset(self::$not_operators[$operator])) {
				$operator = self::$not_operators[$operator];
				$prepend_not = false;
			}

			if (in_array($operator, ['LIKE', 'NOT LIKE'])) {
				if ($value_count > 2) {
					$like_values = array_slice($value, 2);

					foreach ($like_values as &$like_val) {
						if (is_string($like_val)) {
							$like_val = self::$wpdb->esc_like($like_val);
						}
					}

					$operand = self::bind(self::RAW, $operand, ...$like_values);
				}

				return "$column $operator " . self::value($operand);
			}

			if ($operand === null && $operator === '=') {
				$operand = 'NULL';
				$operator = 'IS';
			} else {
				$operand = self::value($operand);
			}

			if ($value_count > 2) {
				return "$column $operator $operand AND " . self::value($value[2]);
			} elseif ($prepend_not) {
				return "NOT $column $operator $operand";
			} else {
				return "$column $operator $operand";
			}
		} elseif ($value[0] === null) {
			return $not_modifier ? "$column IS NOT NULL" : "$column IS NULL";
		} else {
			$operand = $value[0];
			$operator = is_array($operand) ? 'IN' : '=';
			$operand = self::value($operand);

			if ($not_modifier) {
				if (isset(self::$not_operators[$operator])) {
					$operator = self::$not_operators[$operator];
				} else {
					return "NOT $column $operator $operand";
				}
			}

			return "$column $operator $operand";
		}
	}

	public static function prefix($table, $check_prefix = false)
	{
		if (isset(self::$wpdb->$table) && is_string(self::$wpdb->$table)) {
			$table = self::$wpdb->$table;
		} elseif (!$check_prefix || strpos($table, self::$wpdb->prefix) !== 0) {
			$table = self::$wpdb->prefix . $table;
		}

		return $table;
	}

	/**
	 * Escapes special characters in a string for use in a SQL statement.
	 *
	 * Roughly 10x faster than {@see esc_sql()} since we're not worrying
	 * about arrays, and more importantly we don't need to replace all
	 * '%' characters because we're not using {@see wpdb::prepare()}.
	 *
	 * Calls {@see mysqli::real_escape_string()} if using {@see \mysqli},
	 * otherwise falls back to calling {@see wpdb::_real_escape()}.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function esc($string)
	{
		if (self::$mysqli) {
			return self::$mysqli->real_escape_string($string);
		} else {
			return self::$wpdb->_real_escape($string);
		}
	}

	/**
	 * Converts, escapes and quotes a value for direct use in a SQL statement.
	 *
	 * PHP data types are converted to their appropriate SQL counterparts,
	 * i.e. null = NULL, string = 'string', number = 123, bool = TRUE/FALSE
	 *
	 * Arrays are flattened into a SQL list, e.g. (1, 2.34, 'string', TRUE)
	 *
	 * @see DB::esc()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function value($value)
	{
		if ($value === null) {
			$value = 'NULL';
		} elseif (is_string($value)) {
			$value = $value === '' ? "''" : "'" . self::esc($value) . "'";
		} elseif (is_array($value)) {
			foreach ($value as &$v) {
				$v = self::value($v);
			}
			$value = '(' . implode(', ', $value) . ')';
		} elseif (is_bool($value)) {
			$value = $value ? 'TRUE' : 'FALSE';
		} elseif ($value instanceof \DateTimeInterface) {
			$value = "'" . $value->format('Y-m-d H:i:s') . "'";
		} elseif ($value instanceof Query) {
			$value = "(" . $value->sql() . ")";
		} else {
			$value = (string)$value;
		}

		return $value;
	}

	/**
	 * Start a new database transaction.
	 */
	public static function transaction()
	{
		self::$wpdb->query('START TRANSACTION');
	}

	/**
	 * Commit a database transaction.
	 */
	public static function commit()
	{
		self::$wpdb->query('COMMIT');
	}

	/**
	 * Rollback a database transaction.
	 */
	public static function rollback()
	{
		self::$wpdb->query('ROLLBACK');
	}
}

DB::__init();
