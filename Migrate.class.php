<?
require_once('Database.class.php');

class Shopp_Database extends WP_Database {

}

class Shopp_119_Database extends Shopp_Database {

    protected $_name = 'seita_vanilla_119';

}

class Shopp_125_Database extends Shopp_Database {

	protected $_name = 'seita_vanilla_125';

}
class Shopp_Migrate_Database extends Shopp_Database {

	protected $_name = 'seita_vanilla_migrate';

	function __construct()
	{
		parent::__construct();
		$this->load_sql('seita_vanilla_125.sql');
	}

}
/**
*
*/
class Shopp_Migrate
{
	public $dbOld;
	public $dbNew;
	public $dbTemp;

	function __construct()
	{
		$this->dbOld = new Shopp_119_Database();
		$this->dbNew = new Shopp_125_Database();
		$this->dbTemp = new Shopp_Migrate_Database();
	}

	public function convert($table_name, $compare = false)
	{
		$this->compare = $compare;

		switch ($table_name) {
			case 'shopp_meta':
				$this->convert_shopp_meta();
				break;

			case 'shopp_setting':
				$this->convert_shopp_setting();
				break;

			default:
				$this->load_all_tables($table_name);

				$this->dbTemp->$table_name->_data = $this->dbNew->$table_name->_data;
				array_walk($this->dbTemp->$table_name->_data, array($this, 'convert_'.$table_name));
				break;
		}


	}

	public function load_all_tables($table_name)
	{
		$this->dbOld->load_table($table_name);
		$this->dbNew->load_table($table_name);
		$this->dbTemp->load_table($table_name);
	}

	public function convert_shopp_setting()
	{
		$this->dbOld->load_table('shopp_setting');
		$this->dbNew->load_table('shopp_meta');
		$this->dbTemp->load_table('shopp_meta');

		$old_shopp_setting = &$this->dbOld->shopp_setting;
		$new_shopp_meta = $this->dbNew->shopp_meta->order_by('id');
		$working_shopp_meta = &$this->dbTemp->shopp_meta;

		// $new_shopp_meta->objectify();

		$working_shopp_meta->setting = $new_shopp_meta;

		array_walk($working_shopp_meta->setting, array($this, 'convert_shopp_setting_row'));
		array_walk($working_shopp_meta->setting, array($this, 'reorder_by_keys'),  $new_shopp_meta[0]);

		// $working_shopp_meta->data = array_merge($settings, $table->old->data);

		// $this->order_by_columns($working_shopp_meta->data);
		// $this->order_by_columns($table->new->data);

		foreach ($working_shopp_meta->setting as $row) {
			$working_shopp_meta->insert($row);
		}

		if ($this->compare) $this->compare($this->dbOld->shopp_setting, $this->dbTemp->shopp_meta, $this->dbNew->shopp_meta);
	}

	public function convert_shopp_meta()
	{
		$this->load_all_tables('shopp_meta');

		$old_shopp_meta = &$this->dbOld->shopp_meta;
		$new_shopp_meta = &$this->dbNew->shopp_meta;
		$working_shopp_meta = &$this->dbTemp->shopp_meta;

		// split meta data
		$old_shopp_meta->objectify();
		$new_shopp_meta->objectify();

		// IMAGES

		// get image storage id
		$request = $this->dbOld->query("SELECT `id` FROM wp_shopp_setting WHERE `name` = 'image_storage'");
		$image_storage = $request->fetch();
		$image_storage_id = $image_storage[0];

		// get original images
		$request = $this->dbOld->query("SELECT `id` FROM wp_shopp_meta WHERE `name` = 'original'");
		while ($values = $request->fetch()) {
			$originals_ids[] = $values[0];
		}

		$working_shopp_meta->image = $old_shopp_meta->image;

		// $sql = "INSET INTO "

		// set original image parent ids
		foreach ($working_shopp_meta->image as &$values) {
			if (in_array($values['id'], $originals_ids)) {
				$values['parent'] = $image_storage_id;
			}
		}
		foreach ($working_shopp_meta->image as $row) {
			$working_shopp_meta->insert($row);
		}

		// $working_shopp_meta->image->save();

		if ($this->compare) $this->compare($this->dbTemp->shopp_meta, $this->dbNew->$shopp_meta);

	}



	public function convert_shopp_price_row(&$row)
	{
		$row['cost'] = '0.000000';
		unset($row['dimensions']);
		unset($row['donation']);
		$row['discounts'] = '';
		unset($row['options']);
		$row['promoprice'] = '0.000000';
		$row['stocked'] = '0';
		unset($row['weight']);
		ksort($row);
		return $row;
	}
	public function convert_shopp_setting_row(&$row)
	{
		unset($row['autoload']);
		$row['context'] = 'shopp';
		$row['type'] = 'setting';
		$row['sortorder'] = '0';
		$row['parent'] = '0';
		$row['numeral'] = '0.000000';

		return $row;
	}
	public function convert_shopp_meta_row(&$row)
	{

		ksort($row);
		return $row;
	}
	public function reorder_by_keys(&$row, $row_key, $keys)
	{
		$key_order = array_keys($keys);
		$sorted = array();
		foreach($key_order as $key) {
		    $sorted[$key] = $row[$key];
		}
		$row = $sorted;
		return $row;
	}
	public function order_by_columns(&$data)
	{
		foreach ($data as $key => $row) {
			$type[$key] = $row['type'];
			$name[$key] = $row['name'];
		}
		// Sort the data with volume descending, edition ascending
		// Add $data as the last parameter, to sort by the common key
		array_multisort($type, SORT_ASC, $name, SORT_ASC, $data);
	}

	function compare($working, $new) {
		?>
		<div class="compare">
			<div><?php var_dump($working) ?></div>
			<div><?php var_dump($new) ?></div>
		</div>

		<?php
	}

}
?>

