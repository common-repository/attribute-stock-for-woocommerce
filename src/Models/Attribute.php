<?php
namespace Mewz\WCAS\Models;

class Attribute
{
	public int $id;
	public string $name;
	public string $taxonomy;
	public string $label;
	public string $orderby;

	/**
	 * @param int $id
	 * @param string $name
	 * @param string $label
	 * @param string $orderby
	 */
	public function __construct($id, $name, $label = null, $orderby = 'menu_order')
	{
		$this->id = $id;
		$this->name = $name;
		$this->taxonomy = 'pa_' . $name;
		$this->label = (string)$label;
		$this->orderby = $orderby;
	}

	/**
	 * @param object{attribute_id: string, attribute_name: string, attribute_label: string, attribute_orderby: string} $result
	 *
	 * @return static
	 */
	public static function from_result($result)
	{
	    return new static(
			$result->attribute_id,
			$result->attribute_name,
			$result->attribute_label,
			$result->attribute_orderby,
	    );
	}
}
