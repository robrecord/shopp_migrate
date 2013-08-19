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
	// public $Shopp_125;
	// public $Shopp_119;

	public $Shopp;

	function __construct()
	{
		// $this->Shopp_119 = new Shopp_119_Database();
		// $this->Shopp_125 = new Shopp_125_Database();
		$this->Shopp = new stdClass;
		$this->Shopp->old = new Shopp_119_Database();
		$this->Shopp->new = new Shopp_125_Database();

		$this->Shopp->converted = new Shopp_Migrate_Database();
	}

	// public function test1()
	// {
	// 	$db = &$this->Shopp_119;
	// 	$array = $db->fetch_all_array("SELECT * FROM wp_shopp_meta");
	// 	var_dump($array);
	// }
	// public function test2()
	// {
	// 	$db = &$this->Shopp_125;
	// 	$array = $db->fetch_all_array("SELECT * FROM wp_shopp_meta");
	// 	var_dump($array);
	// }
	// public function test3()
	// {
	// 	$db = &$this->Shopp_119;
	// 	$result = $db->query("SELECT * FROM wp_shopp_meta");
	// 	$data = new Table('id');
	// 	while ($values = $db->fetch_array_assoc($result)) {
	// 		$data->add_row($values);
	// 	}

	// 	$data->organize('parent');
	// 	var_dump($data->data_organized);
	// }
	// public function test4()
	// {
	// 	$table_name = 'shopp_price';

	// 	$dbOld = &$this->Shopp_119;
	// 	$tableOld = $dbOld->init_table($table_name,'id');
	// 	$result = $dbOld->query("SELECT * FROM {$tableOld->name}");
	// 	while ($values = $dbOld->fetch_array_assoc($result)) {
	// 		$tableOld->add_row($values);
	// 	}
	// 	// $data->data['options']
	// 	// $data->organize('parent');
	// 	var_dump($dbOld);
	// 	// var_dump($tableOld);

	// 	$dbNew = &$this->Shopp_125;
	// 	$tableNew = $dbNew->init_table($table_name,'id');
	// 	$result = $dbNew->query("SELECT * FROM {$tableNew->name}");
	// 	while ($values = $dbNew->fetch_array_assoc($result)) {
	// 		$tableNew->add_row($values);
	// 	}
	// 	var_dump($dbNew);

	// 	// var_dump($tableNew);

	// }

	// public function test5()
	// {
	// 	$table_name = 'shopp_price';

	// 	$tableOld = $this->Shopp_119->load_table($table_name);
	// 	$tableNew = $this->Shopp_125->load_table($table_name);

	// 	var_dump($tableOld);
	// 	var_dump($tableNew);

	// }

	// public function test6()
	// {
	// 	$table_name = 'shopp_price';

	// 	$tableOld = $this->Shopp->old->load_table($table_name);
	// 	$tableNew = $this->Shopp->new>load_table($table_name);

	// 	var_dump($tableOld);
	// 	var_dump($tableNew);

	// }


	public function convert($table_name)
	{
		$oldDB = &$this->Shopp->old;
		$newDB = &$this->Shopp->new;
		$conDB = &$this->Shopp->converted;


		$oldDB->load_table($table_name);
		$newDB->load_table($table_name);
		$conDB->load_table($table_name);



		switch ($table_name) {
			case 'shopp_meta':

				// split meta data
				$oldDB->$table_name->objectify();
				$newDB->$table_name->objectify();

				// SETTINGS

				$oldDB->load_table('shopp_setting');

				$old_shopp_meta = &$oldDB->$table_name;
				$new_shopp_meta = &$newDB->$table_name;
				$converted_shopp_meta = &$conDB->$table_name;

				$converted_shopp_meta->setting = $oldDB->shopp_setting->order_by('name');

				array_walk($converted_shopp_meta->setting, array($this, 'convert_shopp_setting'));
				array_walk($converted_shopp_meta->setting, array($this, 'reorder_by_keys'),  $new_shopp_meta->_data[0]);

				// $converted_shopp_meta->data = array_merge($settings, $table->old->data);

				// $this->order_by_columns($converted_shopp_meta->data);
				// $this->order_by_columns($table->new->data);

				// IMAGES

				// get image storage id
				$result = $oldDB->query("SELECT `id` FROM wp_shopp_setting WHERE `name` = 'image_storage'");
				$image_storage = $oldDB->fetch_array($result);
				$image_storage_id = $image_storage[0];

				// get original images
				$result = $oldDB->query("SELECT `id` FROM wp_shopp_meta WHERE `name` = 'original'");
				while ($values = $oldDB->fetch_array($result)) {
					$originals_ids[] = $values[0];
				}

				$converted_shopp_meta->image = $old_shopp_meta->image;

				// $sql = "INSET INTO "

				// set original image parent ids
				foreach ($converted_shopp_meta->image as &$values) {
					if (in_array($values['id'], $originals_ids)) {
						$values['parent'] = $image_storage_id;
					}
				}
				foreach ($converted_shopp_meta->image as $row) {
					$converted_shopp_meta->insert($row);
				}

				break;

			default:
				$converted_shopp_meta->_data = $table->old->_data;
				array_walk($converted_shopp_meta->_data, array($this, 'convert_'.$table_name));
				break;
		}

		$this->compare($converted_shopp_meta->image, $new_shopp_meta->image);
		// $this->compare($converted_shopp_meta->setting, $new_shopp_meta->setting);

		$converted_shopp_meta->image->save();

		return $converted_shopp_meta;
	}


	public function convert_shopp_price(&$row)
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
	public function convert_shopp_setting(&$row)
	{
		unset($row['autoload']);
		$row['context'] = 'shopp';
		$row['type'] = 'setting';
		$row['sortorder'] = '0';
		$row['parent'] = '0';
		$row['numeral'] = '0.000000';

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
	public function convert_shopp_meta(&$row)
	{
		// $row['cost'] = '0.000000';
		// unset($row['dimensions']);
		// unset($row['donation']);
		// $row['discounts'] = '';
		// unset($row['options']);
		// $row['promoprice'] = '0.000000';
		// $row['stocked'] = '0';
		// unset($row['weight']);
		ksort($row);
		return $row;
	}
	function compare($converted, $new) {
		?>
		<div class="compare">
			<div><?php var_dump($converted) ?></div>
			<div><?php var_dump($new) ?></div>
		</div>

		<?php
	}

}
?>

