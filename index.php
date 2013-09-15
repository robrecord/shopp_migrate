<?php

$plugin_path = $this->thispluginpath;

require_once @$plugin_path.'Dev.class.php';

Dev::start(@$plugin_path, false, 'md');

require_once @$plugin_path.'Migrate.class.php';

if (PHP_SAPI != 'cli' && !DEV) {
	?><link rel="stylesheet" href="<?php echo $this->thispluginurl ?>dev.css"><?php
}

if (class_exists('Shopp_Migrate_Script')) {
	$Migrate = new Shopp_Migrate_Script(true, $plugin_path, $this->thispluginurl);

	$Migrate->save_wp_shopp_setting();
	$Migrate->reset_tables();
	$Migrate->copy_wp_shopp_setting();

	$Migrate->convert('wp_shopp_category');
	$Migrate->convert('wp_shopp_product');
	$Migrate->convert('shopp_order_only');
	$Migrate->convert('importer_edge_cat_map');
	$Migrate->convert('wp_shopp_catalog');
	$Migrate->convert('wp_shopp_images');
	$Migrate->convert('wp_shopp_price');
	$Migrate->convert('wp_shopp_summary');
	// $Migrate->reindex_products();
}

Dev::end('md');
?>

