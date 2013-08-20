<style>
	.compare {
		display:-webkit-flex;
		/*word-wrap: break-word;*/
	}
	.compare > * {
		-webkit-flex:0 1 auto;
		overflow:scroll;
	}
</style>

<?php
// ini_set('xdebug.remote_mode', 'req');
ini_set('ignore_repeated_source', 'On');

// xdebug_start_code_coverage();

require_once('Migrate.class.php');

$Migrate = new Shopp_Migrate;

$Migrate->convert('shopp_setting');
// $Migrate->convert('shopp_meta');
// xdebug_enable();
xdebug_break();		// split meta data

// var_dump(xdebug_get_code_coverage());

?>
