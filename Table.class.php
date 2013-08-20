<?

/**
*
*/
class Table extends Data_Set
{
	public $_name;
	private $_db;

	function __construct(&$db, $name = null, $prefix = "")
	{
		if ($name) $this->_name = $prefix.$name;
		if ($db) $this->_db = $db;
		parent::__construct();
	}

	public function insert(array $data)
	{
		list ($fields, $values) = $this->array_to_sql($data);
		$sql = "INSERT INTO `{$this->_name}` ($fields) VALUES ($values)";
		$this->_db->query($sql);
	}

	public function array_to_sql(array $data)
	{
		$fields = '`'.implode(array_keys($data), '`, `').'`';
		$values = array_values($data);
		foreach ($values as &$value) {
			$value = json_encode($value);
		}
		// $values = array_walk($values, 'json_encode');
		$values = implode($values, ', ');

		return array($fields, $values);

	}

	// public function columns($table_name)
	// {
	// 	$table = $this->table($table_name);

	// 	if ($this->query = @mysql_query("SELECT * FROM {$table->name}", $this->_link)) {
	// 	    return $this->query;
	// 	} else {
	// 	    $this->exception("Could not get table column names!");
	// 	    return false;
	// 	}
	// 	/* get column metadata */
	// 	$i = 0;
	// 	while ($column < $this->num_fields($this->query)) {
	// 	    $meta = $this->fetch_field($this->query, $column);
	// 	    $table->columns[] = $meta->name;
	// 	    $i++;
	// 	}
	// 	mysql_free_result($this->query);
	// 	return $table->columns;
	// }
}

/**
*
*/
class Data_Set
{
	private $_primary_key = 'id';
	private $_root_id = 0;
	public $_data;
	private $_row;

	function __construct()
	{
		$this->_data = array();
	}

	public function extract($key, $default = false)
	{
		if (!array_key_exists($key, $this->_row))
			return $default;
		return $this->_row[$key];
	}

	public function extract_unset($key, $default = false)
	{
		if (($value = $this->extract($key, $default)) !== false)
			unset($this->_row[$key]);
		return $value;
	}

	public function add_row($row)
	{
		$this->_data[] = $row;
		// unset($this->row);
	}

	public function organize($parent_key, $root_id = null)
	{
		if (!is_null($root_id)) $this->_root_id = $root_id;

		$data = $this->_data;

		foreach ($data as &$this->_row) {
			$id = $this->extract_unset($this->primary_key);
			$data[$id] = $this->_row;
		}

		foreach ($data as $id => &$this->_row) {
			$parent_id = $this->extract($parent_key, $this->_root_id);
			if ($parent_id != $this->_root_id) {
				unset($this->_row[$parent_key]);
				$data[$parent_id]['children'][$id] = &$this->_row;
			}
		}
		foreach ($data as $id => &$this->_row) {
			$parent_id = $this->extract($parent_key, $this->_root_id);
			if ($parent_id == $this->_root_id) {
				unset($this->_row[$parent_key]);
				$this->data_organized[$id] = $this->_row;
			}
		}
		unset($this->_row);
		return $this->_data_organized;
	}


	protected function ksortRecursive(array $data = array())
	{
		if (empty($data)) $data = $this->_data;
		foreach ($data as $key => $nestedArray) {
			if (is_array($nestedArray) && !empty($nestedArray)) {
				$this->_data_organized[$key] = $this->ksortRecursive($nestedArray);
			}
		}
		return ksort($this->_data_organized);
	}

	public function order_by($order_by, array $data = array())
	{
		if (empty($data)) $data = $this->_data;

		$sortArray = array();
		foreach($data as $values){
		    foreach($values as $key=>$value){
		        if(!isset($sortArray[$key]))
		            $sortArray[$key] = array();
		        $sortArray[$key][] = $value;
		    }
		}
		array_multisort($sortArray[$order_by],SORT_ASC,$data);

		return $this->_data_organized = $data;
	}
	public function reorder($keys, array $data = array())
	{
		if (empty($data)) $data = $this->_data;
		$reordered = array();
		foreach($keys as $key)
		    $reordered[$key] = $data[$key];
		return $this->_data_organized = $reordered;
	}

	public function objectify($key = 'name')
	{
		$data = $this->order_by($key);
		foreach ($data as $row) {
			$this->{$row['type']}[$row['id']] = $row;
		}
	}

}
