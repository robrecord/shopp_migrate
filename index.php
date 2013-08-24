<?php

$plugin_path = $this->thispluginpath;

require_once @$plugin_path.'Dev.class.php';

Dev::start(@$plugin_path, true, 'md');

require_once @$plugin_path.'Migrate.class.php';

if (PHP_SAPI != 'cli' && !DEV) {
	?><link rel="stylesheet" href="<?php echo $this->thispluginurl ?>dev.css"><?php
}

if (class_exists('Shopp_Migrate_Script')) {
	$Migrate = new Shopp_Migrate_Script(true, $plugin_path, $this->thispluginurl);

	$Migrate->convert('wp_shopp_setting');
	$Migrate->convert('wp_shopp_category');
	$Migrate->convert('wp_shopp_product');
	$Migrate->convert('wp_shopp_catalog');
	$Migrate->convert('wp_shopp_meta_images');
	$Migrate->convert('wp_shopp_asset');
	$Migrate->convert('wp_shopp_price');
}
Dev::end('md');
?>

