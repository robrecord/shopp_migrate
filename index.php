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

require_once('Migrate.class.php');

$Migrate = new Shopp_Migrate;

$table_name = 'shopp_meta';
$Migrate->convert($table_name);


?>
