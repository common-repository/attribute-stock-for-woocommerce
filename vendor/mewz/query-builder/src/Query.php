<?php
namespace Mewz\QueryBuilder;

class Query
{
	/** @var array One or more tables to use in the query */
	protected $tables = [];

	/** @var array Columns for SELECT clause */
	protected $select = [];

	/** @var bool Flag for DISTINCT in SELECT clause */
	protected $distinct = false;

	/** @var array Stores key/value pairs for INSERT/UPDATE */
	protected $data = [];

	/** @var array Table JOIN clauses */
	protected $joins = [];

	/** @var array Conditions for WHERE clause */
	protected $where = [];

	/** @var array Columns to GROUP BY */
	protected $groupby = [];

	/** @var array Conditions for HAVING clause */
	protected $having = [];

	/** @var array Columns and orders to ORDER BY */
	protected $orderby = [];

	/** @var int Number of rows to retrieve */
	protected $limit;

	/** @var int Number of rows to offset results by */
	protected $offset;

	/** @var array Operator to use for the next condition */
	protected $operator = 'AND';

	/** @var array Whether the next condition should use the NOT modifier */
	protected $not = false;

	/** @var string The output type for row data */
	protected $output = OBJECT;

	/** @var array Temporarily stores original clause data when overriding */
	protected $override = [];

	/* Clause Methods */

	/**
	 * Add a table to the query.
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return $this
	 */
	public function table($name, $alias = null)
	{
		$this->tables[] = [$name, $alias];

		return $this;
	}

	/**
	 * Add a table to the query. Alias for {@see Query::table()}.
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return $this
	 */
	public function from($name, $alias = null)
	{
		$this->tables[] = [$name, $alias];

		return $this;
	}

	public function select($expr, ...$values)
	{
		$this->select[] = $values ? [$expr, $values] : $expr;

		return $this;
	}

	public function columns(...$names)
	{
		array_push($this->select, ...$names);

		return $this;
	}

	public function distinct()
	{
		$this->distinct = true;

		return $this;
	}

	/**
	 * Add data to be used in an INSERT/UPDATE query.
	 *
	 * @param array|string $data [column => value] pairs or raw strings (UPDATE only)
	 *
	 * @return $this
	 */
	public function data($data)
	{
		$this->data = $this->data ? array_merge($this->data, (array)$data) : (array)$data;

		return $this;
	}

	/**
	 * Add data to be used in an INSERT/UPDATE query.
	 *
	 * @param array|string $data [column => value] pairs or raw strings (UPDATE only)
	 *
	 * @return $this
	 */
	public function set($data)
	{
		$this->data = $this->data ? array_merge($this->data, (array)$data) : (array)$data;

		return $this;
	}

	/**
	 * Add an INNER JOIN.
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return $this
	 */
	public function join($name, $alias = null)
	{
		$this->joins[] = ['INNER JOIN', [$name, $alias]];

		return $this;
	}

	/**
	 * Add a LEFT JOIN.
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return $this
	 */
	public function left_join($name, $alias = null)
	{
		$this->joins[] = ['LEFT JOIN', [$name, $alias]];

		return $this;
	}

	/**
	 * Add a RIGHT JOIN.
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return $this
	 */
	public function right_join($name, $alias = null)
	{
		$this->joins[] = ['RIGHT JOIN', [$name, $alias]];

		return $this;
	}

	/**
	 * Add a CROSS JOIN.
	 *
	 * @param string $name The table name, with or without prefix
	 * @param string|true $alias Optional table alias, or true to use $name as provided (without prefixing)
	 *
	 * @return $this
	 */
	public function cross_join($name, $alias = null)
	{
		$this->joins[] = ['CROSS JOIN', [$name, $alias]];

		return $this;
	}

	/**
	 * Add an ON clause to the last JOIN.
	 *
	 * @param string ...$condition Values passed directly to {@see DB::bind()}
	 *
	 * @return Query
	 */
	public function on(...$condition)
	{
		end($this->joins);
		$this->joins[key($this->joins)][2] = $condition;

		return $this;
	}

	public function groupby($expr, ...$values)
	{
		$this->groupby[] = $values ? [$expr, $values] : $expr;

		return $this;
	}

	public function orderby($expr, ...$values)
	{
		$this->orderby[] = $values ? [$expr, $values] : $expr;

		return $this;
	}

	public function asc($expr, ...$values)
	{
		return $this->orderby($expr, ...$values);
	}

	public function desc($expr, ...$values)
	{
		return $this->orderby($expr . ' DESC', ...$values);
	}

	public function oldest($expr, ...$values)
	{
		return $this->orderby($expr, ...$values);
	}

	public function newest($expr, ...$values)
	{
		return $this->orderby($expr . ' DESC', ...$values);
	}

	public function limit($limit, $offset = null)
	{
		$this->limit = (int)$limit;

		if ($offset !== null) {
			$this->offset = (int)$offset;
		}

		return $this;
	}

	public function offset($offset)
	{
		$this->offset = (int)$offset;

		return $this;
	}

	/**
	 * Set the output type for row data.
	 *
	 * @see wpdb::get_results()
	 *
	 * @param string $type OBJECT | OBJECT_K | ARRAY_A | ARRAY_N
	 *
	 * @return $this
	 */
	public function output($type)
	{
		$this->output = $type;

		return $this;
	}

	/* Query Methods */

	/**
	 * @see wpdb::get_results()
	 *
	 * @param string $output OBJECT | OBJECT_K | ARRAY_A | ARRAY_N
	 *
	 * @return array|null
	 */
	public function get($output = null)
	{
		$sql = $this->build_select_query();
		$output ??= $this->output;

		return $sql ? DB::$wpdb->get_results($sql, $output) : null;
	}

	/**
	 * @see wpdb::get_results()
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array|null
	 */
	public function get_limit($limit = null, $offset = null)
	{
		if ($limit !== null) {
			$this->override('limit', $limit);
		}

		if ($offset !== null) {
			$this->override('offset', $offset);
		}

		return $this->get();
	}

	/**
	 * @see wpdb::get_results()
	 * @see Query::page()
	 *
	 * @param int $page_number
	 * @param int $per_page
	 *
	 * @return array|null
	 */
	public function page($page_number = 1, $per_page = null)
	{
		$limit = $per_page === null ? (int)get_option('posts_per_page') : (int)$per_page;
		$offset = ((int)$page_number - 1) * $limit;

		return $this->get_limit($limit, $offset);
	}

	/**
	 * @see wpdb::get_row()
	 *
	 * @param int $row_num
	 * @param int $limit
	 * @param string $output OBJECT | OBJECT_K | ARRAY_A | ARRAY_N
	 *
	 * @return array|null
	 */
	public function row($row_num = 0, $limit = null, $output = null)
	{
		if ($limit) {
			$this->override('limit', (int)$limit);
		}

		$sql = $this->build_select_query();
		$output ??= $this->output;

		return $sql ? DB::$wpdb->get_row($sql, $output, $row_num) : null;
	}

	/**
	 * The same as `row()` with LIMIT 1 added.
	 *
	 * @see row()
	 *
	 * @param string $output OBJECT | OBJECT_K | ARRAY_A | ARRAY_N
	 *
	 * @return array|null
	 */
	public function one($output = null)
	{
		if ($output !== null) {
			$this->override('output', $output);
		}

		return $this->row(0, 1);
	}

	/**
	 * @see wpdb::get_col()
	 *
	 * @param string|int $column
	 *
	 * @return array
	 */
	public function col($column = null)
	{
		$x = 0;

		if ($column !== null) {
			if (is_int($column)) {
				$x = $column;
			} else {
				$this->override('select', [$column]);
			}
		}

		$sql = $this->build_select_query();

		return $sql ? DB::$wpdb->get_col($sql, $x) : [];
	}

	/**
	 * @param string $key_col
	 * @param string $value_col
	 *
	 * @return array|null
	 */
	public function pairs($key_col = null, $value_col = null)
	{
		$sql = $this->build_select_query();
		if (!$sql) return [];

		$rows = DB::$wpdb->get_results($sql, ARRAY_A);
		if (empty($rows)) return $rows;

		if ($key_col !== null && $value_col !== null) {
			$pairs = array_column($rows, $value_col, $key_col);
		} else {
			$pairs = [];

			foreach ($rows as $row) {
				$pairs[current($row)] = next($row);
			}
		}

		return $pairs;
	}

	/**
	 * @see wpdb::get_var()
	 *
	 * @param string $column
	 *
	 * @return string|null
	 */
	public function var($column = null)
	{
		$this->override('limit', 1);

		if ($column !== null) {
			$this->override('select', [$column]);
		}

		$sql = $this->build_select_query();

		return $sql ? DB::$wpdb->get_var($sql) : null;
	}

	/**
	 * Gets the first row matching a condition.
	 *
	 * Shorthand for {@see where()} and {@see one()}, without changing the query data.
	 *
	 * @example DB::table('users')->find('user_id', 3);
	 *
	 * @param string $column
	 * @param mixed ...$value
	 *
	 * @return array|null
	 */
	public function find($column, ...$value)
	{
		$this->override('where');
		$this->where($column, ...$value);

		return $this->one();
	}

	/**
	 * Gets all rows matching a condition.
	 *
	 * Shorthand for {@see where()} and {@see get()}, without changing the query data.
	 *
	 * @example DB::table('posts')->find_all('post_author', [3, 4, 5]);
	 *
	 * @param string $column
	 * @param mixed ...$value
	 *
	 * @return array|null
	 */
	public function find_all($column, ...$value)
	{
		$this->override('where');
		$this->where($column, ...$value);

		return $this->get();
	}

	/**
	 * Counts the number of records for the current query.
	 *
	 * Query example: SELECT COUNT(*) FROM ...
	 *
	 * @param string $column The column to count (default '*')
	 * @param bool $subquery Count final/grouped rows from sub-query
	 *
	 * @return int|array|null The record count, array of counts (when using GROUP BY), or null on failure
	 */
	public function count($column = '*', $subquery = false)
	{
		if (!$subquery) {
			$this->override('select', ["COUNT($column)"]);
		}

		if ($sql = $this->build_select_query()) {
			if ($subquery) {
				$sql = "SELECT COUNT($column) FROM (\n{$sql}\n) __results";
			}

			$results = DB::$wpdb->get_col($sql);

			if ($results) {
				return count($results) === 1 ? (int)$results[0] : array_keys(array_flip($results));
			}
		}

		return null;
	}

	/**
	 * Efficiently checks if any records exist for the current query.
	 *
	 * Query example: SELECT EXISTS (SELECT * FROM ...)
	 *
	 * @param int $limit
	 *
	 * @return bool|null True/false if records exist, or null on failure
	 */
	public function exists($limit = 1)
	{
		if ($limit) {
			$this->override('limit', $limit);
		}

		if ($sql = $this->build_select_query()) {
			$sql = "SELECT EXISTS (\n$sql\n) AS result";
			$result = DB::$wpdb->get_var($sql);

			if ($result !== null) {
				return (bool)$result;
			}
		}

		return null;
	}

	#TODO: Add more aggregate query methods (https://laravel.com/docs/queries#aggregates)

	public function insert($data = [], $type = DB::INSERT)
	{
		if (empty($this->tables) || (empty($data) && empty($this->data))) {
			return false;
		}

		if ($data) {
			$this->override('data');
			$this->data($data);
		}

		$sql = $this->build_insert_query($type);

		if ($this->override) {
			$this->override_reset();
		}

		$result = DB::$wpdb->query($sql);

		return $result && DB::$wpdb->insert_id ? DB::$wpdb->insert_id : $result;
	}

	public function replace($data = [])
	{
		return $this->insert($data, DB::REPLACE);
	}

	public function update($data = [], $all_rows = false)
	{
		if (empty($this->tables) || (empty($data) && empty($this->data)) || (!$all_rows && empty($this->where))) {
			return false;
		}

		if ($data) {
			$this->override('data');
			$this->data($data);
		}

		$sql = $this->build_update_query();

		if ($this->override) {
			$this->override_reset();
		}

		return DB::$wpdb->query($sql);
	}

	public function delete()
	{
		$sql = $this->build_delete_query();

		return $sql ? DB::$wpdb->query($sql) : false;
	}

	public function alter($query)
	{
		if (empty($this->tables)) return false;

		$sql = 'ALTER TABLE ' . $this->get_main_table('name') . ' ' . $query;

		return DB::$wpdb->query($sql);
	}

	public function optimize()
	{
		if (empty($this->tables)) return false;

		$sql = 'OPTIMIZE TABLE ' . $this->get_main_table('name');

		return DB::$wpdb->query($sql);
	}

	/**
	 * Truncates one or more tables, i.e. deletes all rows and resets auto-increment value.
	 *
	 * It goes without saying, but don't fuck around when using this method!
	 *
	 * @param bool $all_tables Whether to truncate all added tables or just the first (main) table.
	 *                         This is mainly for a bit of added protection against unintentional data loss.
	 *
	 * @return bool True on successful truncate, false on error
	 */
	public function truncate($all_tables = false)
	{
		if (empty($this->tables)) return false;

		if ($all_tables) {
			$sql = [];

			foreach ($this->tables as $table) {
				$table = $this->build_table($table, 'name');
				$sql[] = "TRUNCATE TABLE $table;";
			}

			$sql = implode("\n", $sql);
		} else {
			$sql = 'TRUNCATE TABLE ' . $this->get_main_table('name');
		}

		return DB::$wpdb->query($sql);
	}

	/**
	 * Drops one or more tables.
	 *
	 * It goes without saying, but don't fuck around when using this method!
	 *
	 * @param bool $if_exists Toggle 'IF EXISTS' after 'DROP TABLE'
	 * @param bool $all_tables Whether to drop all added tables or just the first (main) table.
	 *                         This is mainly for a bit of added protection against unintentional data loss.
	 *
	 * @return bool True on successful drop, false on error
	 */
	public function drop($if_exists = true, $all_tables = false)
	{
		if (empty($this->tables)) return false;

		$if_exists = $if_exists ? 'IF EXISTS ' : '';

		if ($all_tables) {
			$sql = [];

			foreach ($this->tables as $table) {
				$table = $this->build_table($table, 'name');
				$sql[] = "DROP TABLE $if_exists$table;";
			}

			$sql = implode("\n", $sql);
		} else {
			$sql = 'DROP TABLE ' . $if_exists . $this->get_main_table('name');
		}

		return DB::$wpdb->query($sql);
	}

	/* Utility Methods */

	public function clear($prop)
	{
		$this->$prop = is_array($this->$prop) ? [] : null;

		return $this;
	}

	public function inspect($prop = null)
	{
		if ($prop !== null) {
			return $this->$prop;
		} else {
			return get_object_vars($this);
		}
	}

	protected function override($prop, $value = null)
	{
		$this->override[$prop] = $this->$prop;

		if ($value !== null) {
			$this->$prop = $value;
		}
	}

	protected function override_reset()
	{
		foreach ($this->override as $prop => $value) {
			$this->$prop = $value;
		}

		$this->override = [];
	}

	protected function get_main_table($part = null)
	{
		if (empty($this->tables)) return false;

		return $this->build_table($this->tables[0], $part);
	}

	/* Build Methods */

	protected function build_tables($part = null)
	{
		$tables = [];

		foreach ($this->tables as $table) {
			$tables[] = $this->build_table($table, $part);
		}

		return implode(', ', $tables);
	}

	/**
	 * @param array $table [$name, $alias]
	 * @param null $part Optionally get only 'name' or 'alias'
	 *
	 * @return string
	 */
	protected function build_table(array $table, $part = null)
	{
		[$name, $alias] = $table;

		if ($alias !== true) {
			if ($part === 'alias') {
				if ($alias) {
					$name = $alias;
				}
			} else {
				$name = DB::prefix($name);

				if ($part !== 'name' && $alias) {
					$name .= ' AS ' . $alias;
				}
			}
		}

		return $name;
	}

	protected function build_select()
	{
		$sql = 'SELECT ';

		if ($this->distinct) {
			$sql .= 'DISTINCT ';
		}

		if ($this->select) {
			$terms = [];

			foreach ($this->select as $term) {
				$terms[] = is_array($term) ? DB::bind($term[0], ...$term[1]) : $term;
			}

			return $sql . implode(', ', $terms);
		} else {
			return $sql . '*';
		}
	}

	protected function build_joins()
	{
		$sql = [];

		foreach ($this->joins as $join) {
			$clause = $join[0] . ' ' . $this->build_table($join[1]);

			if (!empty($join[2])) {
				$clause .= ' ON ' . DB::bind(...$join[2]);
			}

			$sql[] = $clause;
		}

		return implode("\n", $sql);
	}

	protected function build_set()
	{
		$sql = [];

		foreach ($this->data as $key => $value) {
			$sql[] = is_int($key) ? $value : ($key . ' = ' . DB::value($value));
		}

		return 'SET ' . implode(', ', $sql);
	}

	protected function build_insert()
	{
		$data_rows = !is_array(current($this->data)) ? [$this->data] : array_values($this->data);
		$keys = $value_rows = [];

		foreach ($data_rows[0] as $key => $value) {
			$keys[] = $key;
		}

		foreach ($data_rows as $row) {
			$value_rows[] = DB::value($row);
		}

		return '(' . implode(', ', $keys) . ") VALUES\n" . implode(",\n", $value_rows) . ';';
	}

	protected function build_groupby()
	{
		$terms = [];

		foreach ($this->groupby as $term) {
			$terms[] = is_array($term) ? DB::bind($term[0], ...$term[1]) : $term;
		}

		return 'GROUP BY ' . implode(', ', $terms);
	}

	protected function build_orderby()
	{
		$terms = [];

		foreach ($this->orderby as $term) {
			$terms[] = is_array($term) ? DB::bind($term[0], ...$term[1]) : $term;
		}

		return 'ORDER BY ' . implode(', ', $terms);
	}

	protected function build_where()
	{
		return 'WHERE ' . $this->build_conditions($this->where);
	}

	protected function build_having()
	{
		return 'HAVING ' . $this->build_conditions($this->having);
	}

	protected function build_conditions($args)
	{
		$conditions = [];

		foreach ($args as $arg) {
			$not = isset($arg[3]) && $arg[3] === 'NOT' ? 'NOT ' : '';

			if (!$arg[1]) {
				$condition = '(' . $not . $arg[0] . ')';
			} elseif (strpos($arg[0], '?') !== false) {
				$condition = '(' . $not . DB::bind($arg[0], ...$arg[1]) . ')';
			} elseif ($not) {
				$condition = DB::where(DB::NOT, $arg[0], ...$arg[1]);
			} else {
				$condition = DB::where($arg[0], ...$arg[1]);
			}

			if (isset($conditions[0])) {
				$operator = $arg[2] ?? 'AND';
				$conditions[] = "\n $operator ";
			}

			$conditions[] = $condition;
		}

		return implode('', $conditions);
	}

	protected function build_select_query()
	{
		if (empty($this->tables)) return false;

		$sql[] = $this->build_select();
		$sql[] = 'FROM ' . $this->build_tables();

		if ($this->joins) $sql[] = $this->build_joins();
		if ($this->where) $sql[] = $this->build_where();
		if ($this->groupby) $sql[] = $this->build_groupby();
		if ($this->having) $sql[] = $this->build_having();
		if ($this->orderby) $sql[] = $this->build_orderby();

		if ($this->limit !== null) $sql[] = 'LIMIT ' . $this->limit;
		if ($this->offset !== null) $sql[] = 'OFFSET ' . $this->offset;

		// reset overrides here once instead of doing it in all of the different select methods
		if ($this->override) {
			$this->override_reset();
		}

		return implode("\n", $sql);
	}

	protected function build_insert_query($type = DB::INSERT)
	{
		if (empty($this->tables) || empty($this->data)) {
			return false;
		}

		$table = $this->get_main_table('name');
		$insert = $this->build_insert();

		$type = $type === DB::REPLACE ? 'REPLACE' : 'INSERT';

		return "$type INTO $table $insert";
	}

	protected function build_update_query()
	{
		if (empty($this->tables) || empty($this->data)) {
			return false;
		}

		$sql[] = 'UPDATE ' . $this->build_tables();

		if ($this->joins) {
			$sql[] = $this->build_joins();
		}

		$sql[] = $this->build_set();

		if ($this->where) {
			$sql[] = $this->build_where();
		}

		return implode("\n", $sql);
	}

	protected function build_delete_query()
	{
		if (empty($this->tables)) return false;

		$sql = 'DELETE';

		if ($this->joins || count($this->tables) > 1) {
			$sql .= ' ' . $this->build_tables('alias');
		}

		$sql .= ' FROM ' . $this->get_main_table();

		if ($this->joins) {
			$sql .= "\n" . $this->build_joins();
		}

		if ($this->where) {
			$sql .= "\n" . $this->build_where();
		}

		return $sql;
	}

	/* Condition Operators / Modifiers */

	public function or()
	{
		$this->operator = 'OR';

		return $this;
	}

	public function and()
	{
		$this->operator = 'AND';

		return $this;
	}

	public function not()
	{
		$this->not = true;

		return $this;
	}

	/* Condition Methods (WHERE/HAVING) */

	protected function condition($column, $values = [], $not = null)
	{
		$condition = [$column, $values, $this->operator];

		if ($not ?? $this->not) {
			$condition[] = 'NOT';
			$this->not = false;
		}

		return $condition;
	}

	public function where($column, ...$value)
	{
		$this->where[] = $this->condition($column, $value);

		return $this;
	}

	public function having($column, ...$value)
	{
		$this->having[] = $this->condition($column, $value);

		return $this;
	}

	public function where_raw($sql, ...$value)
	{
		$this->where[] = $this->condition(DB::bind(DB::RAW, $sql, ...$value));

		return $this;
	}

	public function having_raw($sql, ...$value)
	{
		$this->having[] = $this->condition(DB::bind(DB::RAW, $sql, ...$value));

		return $this;
	}

	public function where_not($column, ...$value)
	{
		$this->where[] = $this->condition($column, $value, true);

		return $this;
	}

	public function having_not($column, ...$value)
	{
		$this->having[] = $this->condition($column, $value, true);

		return $this;
	}

	public function is_null($column)
	{
		$this->where[] = $this->condition($this->not ? "$column IS NOT NULL" : "$column IS NULL");

		return $this;
	}

	public function not_null($column)
	{
		$this->where[] = $this->condition("$column IS NOT NULL");

		return $this;
	}

	public function between($column, $from, $to)
	{
		$this->where[] = $this->condition($column, ['BETWEEN', $from, $to]);

		return $this;
	}

	public function like($column, $format, ...$bindings)
	{
		array_unshift($bindings, 'LIKE', $format);

		$this->where[] = $this->condition($column, $bindings);

		return $this;
	}

	public function starts_with($column, $value)
	{
		$this->where[] = $this->condition($column, ['LIKE', '?%', $value]);

		return $this;
	}

	public function ends_with($column, $value)
	{
		$this->where[] = $this->condition($column, ['LIKE', '%?', $value]);

		return $this;
	}

	public function contains($column, $value)
	{
		$this->where[] = $this->condition($column, ['LIKE', '%?%', $value]);

		return $this;
	}

	/* Dump Methods */

	/**
	 * Return the full SQL statement.
	 *
	 * @param string $type DB::SELECT | DB::INSERT | DB::REPLACE | DB::UPDATE | DB::DELETE
	 *
	 * @return string
	 */
	public function sql($type = DB::SELECT)
	{
		switch ($type) {
			case DB::SELECT:
				return $this->build_select_query();
			case DB::INSERT:
				return $this->build_insert_query();
			case DB::REPLACE:
				return $this->build_insert_query(DB::REPLACE);
			case DB::UPDATE:
				return $this->build_update_query();
			case DB::DELETE:
				return $this->build_delete_query();
			default:
				return '';
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->sql();
	}
}
