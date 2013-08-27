<?
require_once @$plugin_path.'Db.php';

/**
*
*/
class myDb extends External_Db
{
	public function load_sql( $file, $delimiter = ';' )
	{
	    set_time_limit(0);
	    if( is_file( $file ) === true )
	    {
	        $file = fopen( $file, 'r' );
	        if( is_resource( $file ) === true )
	        {
	            $query = array();
	            while( feof( $file ) === false )
	            {
	                $query[] = fgets( $file );
	                if( preg_match( '~' . preg_quote( $delimiter, '~' ) . '\s*$~iS', end( $query ) ) === 1 )
	                {
	                    $query = trim( implode( '', $query ) );
	                    $result = $this->raw( $query );
	                }
	                if( is_string( $query ) === true )
		                $query = array();
	            }
	            return fclose( $file );
	        }
	    }
	    return false;
	}
}
/**
*
*/
class Shopp_Migrate_Script
{
	private $dev = true;
	private $dbOld;
	private $dbNew;
	private $dbTemp;
	private $plugin_path;
	private $plugin_url;
	private $using_wordpress;
	private $cache;

	protected $config = array(
		'driver' => 'mysql'
		, 'host'   => 'localhost'
		, 'user'   => 'wordpress'
		, 'pass'  => 'w0rdpr355'
	);

	function __construct( $using_wordpress = false, $plugin_path = null, $plugin_url )
	{
		$this->using_wordpress = $using_wordpress;
		$this->plugin_path = $plugin_path;
		$this->plugin_url = $plugin_url;

		$this->cache = new stdClass();

		// if( $plugin_path ) $this->plugin_path = $plugin_path;
		// $this->connect( 'seita_vanilla_119', 'dbOld' );
		$this->connect( 'seita_hostgator_clone', 'dbOld' );
		$this->connect( 'seita_vanilla_125', 'dbNew' );
		$this->connect( 'seita_vanilla_migrate', 'dbTemp' );
		$this->dbTemp->load_sql( $plugin_path . 'seita_vanilla_125.sql' );
	}

	function connect( $db_name, $var_name )
	{
		extract( $this->config );
		$this->$var_name = new myDb( $driver, $host, $db_name, $user, $pass );
		// var_dump($this->dbOld);die;
	}

	function load_wordpress( $wp_install )
	{

		// require_once( "../{$wp_install}/wp-load.php" );
		require_once( "../../../wp-load.php" );
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	public function convert( $table_name, $compare = false )
	{
		$this->compare = $compare;

		switch( $table_name )
		{

		default:
			$convert_method = "convert_{$table_name}";
			if( method_exists( $this, $convert_method ) )
			{
				$this->$convert_method();
			}
			else {
				$data = $this->dbOld->select( $table_name )->all(false);

				$convert_row_method = "convert_{$table_name}_row";
				if( method_exists( $this, $convert_row_method ) ) array_walk( $data,
					array( $this, $convert_row_method )
				);

				foreach( $data as $row )
					$this->dbTemp->create( $table_name, (array) $row );
			}
			break;
		}
	}

	public function convert_wp_shopp_setting()
	{
		// select shopp settings
		$where_shopp_settings = array(	'context = :c'	=> array( 'c' => 'shopp' ),
										'type = :t'		=> array( 't' => 'setting' ) );
		// select image settings
		$where_image_settings = array(	'context = :c'	=> array( 'c' => 'setting' ),
										'type = :t'		=> array( 't' => 'image_setting' ) );
		// transfer settings
		foreach( array( $where_shopp_settings, $where_image_settings ) as $condition )
		{
			$select = $this->dbNew->select( 'wp_shopp_meta', '*', $condition );
			while( $result = $select->fetch() ) { // false = fetch array
				$new_setting = new ShoppSettingMeta( $result );
				$this->dbTemp->create( 'wp_shopp_meta', (array) $new_setting );
			}
			unset( $result, $select );
		}
	}

	public function convert_wp_shopp_category()
	{
		// load temp wordpress
		if( !$this->using_wordpress ) $this->load_wordpress( 'vanilla_migrate' );

		// get old products
		$old_shopp_categories = $this->dbOld->select( 'wp_shopp_category' )->all();

		$new_term_taxonomy = $this->dbNew->read( 'wp_term_taxonomy','shopp_category','taxonomy' )->all();
		$new_terms = $this->dbNew->select( 'wp_terms' )->all();

		// check shopp_category taxonomy exists
		if( !taxonomy_exists( 'shopp_category' ) )
		{
			die( "shopp_category taxanomy not registered!" );
		}

		// delete previous run's shopp categories
		foreach( get_terms( array( 'shopp_category' ), 'hide_empty=0' ) as $category )
		{
			wp_delete_term( (int) $category->term_id, 'shopp_category' );
		}

		$this->cache->old_categories = array();

		// add new categories
		foreach( $old_shopp_categories as $category )
		{
			// create wordpress term - also creates term_taxonomy entry
			$new_term = $this->wordpress_add_category_term( $category );

			var_dump($new_term, $category);

			// convert category options to metadata in shopp_meta
			$this->create_shopp_category_meta( $category, $new_term[ 'term_id' ] );

			// // save so we can reference old ids (etc) later
			$category->new_term_id = $new_term[ 'term_id' ];
			$category->new_taxonomy_id = $new_term[ 'term_taxonomy_id' ];

			$this->cache->old_categories[ $category->id ] = $category;
			if( $category->parent > 0 )
				$old_child_categories[ $category->parent ] = $category;
		}

		if( !empty( $old_child_categories ) )
		{
			foreach( $old_child_categories as $old_category_parent_id => $old_child_category )
			{
				// get parent from cache using array key
				$new_parent_term_id = $this->cache->old_categories[ $old_category_parent_id ]->new_term_id;

				// get child from previously set array
				$new_term_id = $old_child_category->new_term_id;

				// add to new array for later
				$shopp_category_children[ $new_parent_term_id ][] = $new_term_id;

				// update child terms with parent id
				wp_update_term( $new_term_id, 'shopp_category', array( 'parent' => $new_parent_term_id ) );
			}

			// update cached category children in wp_options
			$this->dbTemp->update(
				'wp_options', array(
					'option_value' => serialize( $shopp_category_children ) ),
				'shopp_category_children', 'option_name'
			);
		}



		// TESTS

		// $this->compare( array( $old_shopp_categories, get_terms( array( 'shopp_category' ), 'hide_empty=0' ) ) );

		// $temp_category_meta = $this->dbTemp->read( 'wp_shopp_meta','category','context' )->all();
		// $new_category_meta = $this->dbNew->read( 'wp_shopp_meta','category','context' )->all();
		// $this->compare( array( $old_shopp_categories, $temp_category_meta, $new_category_meta ) );

		$temp_term_taxonomy = $this->dbTemp->read( 'wp_term_taxonomy','shopp_category','taxonomy' )->all();
		$temp_terms = $this->dbTemp->select( 'wp_terms' )->all();
		// $this->compare( array( $old_shopp_categories, $temp_category_meta, $new_category_meta ) );
		// $this->compare( array( $old_shopp_categories, get_terms( array( 'shopp_category' ), 'hide_empty=0' ) ) );
		echo "term_taxonomy (temp, new)";
		$this->compare( array( $temp_term_taxonomy, $new_term_taxonomy ) );
		echo "terms (temp, new)";
		$this->compare( array( $temp_terms, $new_terms ) );
	}

	function wordpress_add_category_term( $category, $parent = 0, $args = null )
	{
		return wp_insert_term( $category->name, 'shopp_category', $args ? : array(
	        'description'	=> $category->description,
	        'parent'		=> $parent,
	        'slug'			=> $category->slug
	    ) );
	}

	function create_shopp_category_meta( $category, $term_id )
	{
		$category_meta_options = new ShoppCategorySpecMetaRows( $category );



		foreach( (array) $category_meta_options as $meta_name => $meta_value )
		{
			$this->create_wp_shopp_meta_row( new ShoppCategoryMeta( null, array(
				'name'		=> $meta_name,
				'value'		=> $meta_value,
				'parent'	=> $term_id,
				'created'	=> $category->created,
				'modified'	=> $category->modified
			) ) );
		}
		// $this->create_category_spec_meta($category->id, $term_id);
	}

	function create_wp_shopp_meta_row( $new_meta_data )
	{
		return $this->dbTemp->create( 'wp_shopp_meta', (array) $new_meta_data );
	}

	public function convert_wp_shopp_product()
	{
		// load temp wordpress
		if( !$this->using_wordpress ) $this->load_wordpress( 'vanilla_migrate' );

		// delete temp posts
		$temp_shopp_product_posts_ids = $this->dbTemp->read( 'wp_posts', 'shopp_product', 'post_type' )->column( 'ID' );

		foreach( $temp_shopp_product_posts_ids as $id )
		{
			wp_delete_post( (int) $id, true );
		}

		// get old products
		$old_shopp_products = $this->dbOld->select( 'wp_shopp_product' )->all();

		$this->cache->old_products = array();

		// convert products to posts
		foreach( $old_shopp_products as $product )
		{
			$post_id = $this->wordpress_add_product_post(
				$product->name,
				$product->description,
				$product->summary,
				$product->created,
				$product->modified )
			;

			// convert category options to metadata in shopp_meta
			$this->create_new_shopp_product_meta( $product, $post_id );

			$this->create_product_spec_meta($product->id, $post_id);

			$product->new_post_id = $post_id;
			$this->cache->old_products[ $product->id ] = $product;

			wp_delete_object_term_relationships( $post_id, 'shopp_category' );

		}

		// TESTS

		// // test product posts
		$temp_shopp_product_posts = $this->dbTemp->read( 'wp_posts', 'shopp_product', 'post_type' )->all();
		$new_shopp_product_posts = $this->dbNew->read( 'wp_posts', 'shopp_product', 'post_type' )->all();
		$this->compare( array( $old_shopp_products, $temp_shopp_product_posts, $new_shopp_product_posts ) );

		// // test product meta
		// $where_product_meta = array(
		// 	'context = :c' => array( 'c' => 'product' ),
		// 	'type = :t' => array( 't' => 'meta' )
		// 	);
		// $temp_category_meta = $this->dbTemp->select( 'wp_shopp_meta', '*', $where_product_meta )->all();
		// $new_category_meta = $this->dbNew->select( 'wp_shopp_meta', '*', $where_product_meta )->all();
		// $this->compare( array( $old_shopp_products, $temp_category_meta, $new_category_meta ) );
	}

	public function convert_wp_shopp_catalog()
	{
		// add category
		$old_shopp_catalog = $this->dbOld->read( 'wp_shopp_catalog', 'category', 'type' );

		// $this->compare( array( $this->cache->old_products, $this->cache->old_categories ) );

		$new_relationships = array();
		while( $old_catalog_entry = $old_shopp_catalog->fetch() )
		{
			$new_post_id = $this->cache->old_products[ $old_catalog_entry->product ]->new_post_id;
			$new_relationships[ $new_post_id ][] = $this->cache->old_categories[ $old_catalog_entry->parent ]->new_term_id;
		}
		unset( $original_image, $original_images );

		// now we can create a relaytionship entry matching a new product to a new category
		foreach( $new_relationships as $new_post_id => $new_term_ids )
		{
			wp_set_object_terms( $new_post_id, $new_term_ids, 'shopp_category', true );
		}

		// TESTS
		// $this->compare( array( $old_shopp_categories, get_terms( array( 'shopp_category' ), 'hide_empty=0' ) ) );

		// $temp_term_relationships = $this->dbTemp->select( 'wp_term_relationships' )->all();
		// $new_term_relationships = $this->dbNew->select( 'wp_term_relationships' )->all();

		// // test product category
		// $this->compare( array(
		// 	$this->dbOld->select( 'wp_shopp_product' )->all(),
		// 	$this->dbOld->select( 'wp_shopp_catalog' )->all(),
		// 	$this->dbOld->select( 'wp_shopp_category' )->all()
		// ) );

		// $temp_shopp_category_taxonomy_ids = $this->dbTemp->read( 'wp_term_taxonomy', 'shopp_category', 'taxonomy' )->column( 'term_taxonomy_id' );
		// foreach( $temp_shopp_category_taxonomy_ids as $term_taxonomy_id ) {
		// 	$temp_term_relationship_set = $this->dbTemp->read( 'wp_term_relationships', $term_taxonomy_id, 'term_taxonomy_id' )->column( 'object_id' );
		// 	if( !empty( $temp_term_relationship_set ) ) $temp_post_category_relationships[ $term_taxonomy_id ] = $temp_term_relationship_set;
		// }
		// $this->compare( array(
		// 	$this->dbTemp->read( 'wp_posts', 'shopp_product', 'post_type' )->column( 'ID' ),
		// 	$temp_post_category_relationships,
		// 	$this->dbTemp->read( 'wp_term_taxonomy', 'shopp_category', 'taxonomy' )->all()
		// ) );
	}

	function wordpress_add_product_post( $title, $content, $summary = null, $date_created = null, $date_modified = null, $publish = 'publish' )
	{
		return wp_insert_post( array(
	        'post_title'        => $title,
	        'post_content'      => $content,
	        'post_excerpt'		=> $summary,
	        'post_status'       => $publish,
	        'post_author'       => 1,
	        'post_type'			=> 'shopp_product',
	        'comment_status'	=> 'closed',
	        'ping_status'		=> 'closed',
	        'post_date'			=> $date_created,
	        'post_modified'		=> $date_modified
	    ) );
	}

	function create_new_shopp_product_meta( $product, $post_id )
	{
		$product_meta_options = array(
			'processing'	=> 'off',
			'minprocess'	=> '1d',
			'maxprocess'	=> '1d',
			'options'		=> ( $product->options === '' ) ?
				serialize(array()) : $product->options
		);
		foreach( $product_meta_options as $meta_name => $meta_value )
		{
			$this->create_wp_shopp_meta_row(
				new ShoppProductMeta( null, array(
					'name'		=> $meta_name,
					'value'		=> $meta_value,
					'created'	=> $product->created,
					'modified'	=> $product->modified,
					'parent'	=> $post_id
				)
			) );
		}
	}

	function create_new_shopp_price_meta( $price_row, $price_row_id )
	{

		// name: settings, options
		// settings: dimensions -> weight
		// 			recurring
		// options: 1,2

		$price_meta['settings'] = serialize( array( 'dimensions' => array( 'weight' => (float) $price_row->weight ) ) );

		if( !empty($price_row->options) )
			$price_meta['options'] = $price_row->options;

		foreach( $price_meta as $meta_name => $meta_value )
		{
			$this->create_wp_shopp_meta_row(
				new ShoppPriceMeta( null, array(
					'name'		=> $meta_name,
					'value'		=> $meta_value,
					'created'	=> $price_row->created,
					'modified'	=> $price_row->modified,
					'parent'	=> $price_row_id
				)
			) );
		}
	}


	public function create_product_spec_meta($old_id, $new_id) {
		$old_specs = $this->dbOld->select( 'wp_shopp_meta', '*', array(
			'type = :t'		=> array( 't' => 'spec' ),
			'context = :c'	=> array( 'c' => 'product' ),
			'parent = :p'	=> array( 'p' => $old_id )
				)
			);
		while( $old_spec = $old_specs->fetch() )
			$this->create_wp_shopp_meta_row( new ShoppProductSpecMeta( $old_spec, array( 'parent' => $new_id ) ) );
	}

	public function convert_wp_shopp_asset()
	{
		// get original images ( not cached )
		$where_image_original = array(
			'name = :n' => array( 'n' => 'original' ),
			'type = :t' => array( 't' => 'image' )
		);

		// edit original images
		$original_images = $this->dbOld->select( 'wp_shopp_meta', '*', $where_image_original );
		while( $original_image = $original_images->fetch() )
		{
			// new ShoppCategoryImageMeta( $original_image );
			$image_meta_values = unserialize($original_image->value);
			$image_meta_rows[$original_image->id] = $image_meta_values;
			// Dev::output_to_json( $image_meta_value, 'original_images');
		}
		unset( $original_images, $original_image );

		// get image storage setting for old & temp dbs
		// values are either FSStorage or DBStorage

		$old_image_storage_setting =
			array_shift(
				$this->dbOld->select(
					'wp_shopp_setting', 'value', array(
						'name = :n'		=> array( 'n' => 'image_storage' )
					)
				)->fetch( false )
			)
		;
		$temp_image_storage_setting =
			array_shift(
				$this->dbTemp->select(
					'wp_shopp_meta', 'value', array(
						'name = :n'		=> array( 'n' => 'image_storage' ),
						'type = :t'		=> array( 't' => 'setting' ),
						'context = :c'	=> array( 'c' => 'shopp' )
					)
				)->fetch( false )
			)
		;

		foreach( $image_meta_rows as $image_meta_id => $image_meta_values )
		{
			// find which asset(s) is/are referenced by each old image meta row
			$image_assets = $this->dbOld->read( 'wp_shopp_asset', $image_meta_id, 'id' );

			// with each image
			while( $image_asset = $image_assets->fetch() )
			{
				if( isset( $this->cache->old_original_images[$image_meta_id]->new_image_id ) )
				{
					if ($temp_image_storage_setting === "FSStorage") // using filesystem storage
					{
						$image_meta_values->uri = $image_meta_values->filename;
						$this->save_image_file( $image_asset->data, $image_meta_values->filename );
					}
					else // using database image storage
					{
						// unset asset id
						unset($image_asset->id);
						// put asset into temp db; get back new asset id
						$asset_id = $this->dbTemp->create( 'wp_shopp_asset', ((array) $image_asset))->id();
						$image_meta_values->uri = $asset_id;
					}

					$image_meta_values->storage = $temp_image_storage_setting;

					$new_image_meta_id =
						$this->cache->old_original_images[$image_meta_id]->new_image_id;

					// update image meta row to reference correct asset by id
					$test = $this->dbTemp->update(
						'wp_shopp_meta', array(
							'value' => serialize( $image_meta_values )
						), $new_image_meta_id, 'id'
					);
				}


				// (asset id is saved as 'uri', in serialized data from 'value' field)
			}
			unset( $image_asset, $image_assets );
		}
	}

	public function convert_wp_shopp_meta_images()
	{
		if (empty($this->cache->old_original_images)) {
			// get original images ( not cached )
			$where_image_original = array(
				'name = :n' => array( 'n' => 'original' ),
				'type = :t' => array( 't' => 'image' )
			);

			// edit original images
			$original_images = $this->dbOld->select( 'wp_shopp_meta','*', $where_image_original );
			while( $original_image = $original_images->fetch() )
			{
				$new_original_image = $original_image;

				// set parent id of image to new post id of its product
				if( isset( $this->cache->old_products[ $original_image->parent ]->new_post_id ) )
				{
					$new_original_image->parent = $this->cache->old_products[ $original_image->parent ]->new_post_id;
					// each image will get a new id
					$old_image_id = $original_image->id;
					unset( $new_original_image->id );

					// save original images in temp db
					$new_image_id = $this->dbTemp->create( 'wp_shopp_meta', (array) $new_original_image )->id();

					// cache the old data
					$original_image->new_parent_id = $new_original_image->parent;
					$original_image->new_image_id = $new_image_id;
					$this->cache->old_original_images[$old_image_id] = $original_image;
				}
			}
			unset( $original_image, $original_images);
		}
	}

	public function convert_wp_shopp_price()
	{
		$price_rows = $this->dbOld->select( 'wp_shopp_price' )->all();

		foreach( $price_rows as &$old_price_row )
		{

			$price_row = $this->convert_wp_shopp_price_row( $old_price_row );
			$price_id = $this->dbTemp->create( 'wp_shopp_price', (array) $price_row )->id();
			if( $price_row->type != 'N/A' ) {
				$this->create_new_shopp_price_meta( $old_price_row, $price_id );
			}
		}
	}

	public function convert_wp_shopp_price_row( $row )
	{
		$row = new ShoppPriceRow( $row, array(
			'product' 	=> $this->cache->old_products[ $row->product ]->new_post_id
		));

		return $row;
	}

	public function convert_wp_shopp_summary()
	{
		foreach ($this->cache->old_products as $old_product_id => $product) {
			$product->id = $product->new_post_id;
			unset($product->new_post_id);
			$Product = new Product($product->id);
			$Product->load_data();
			// $Product->resum();
			// $Product->load_sold();
			// $Product->sumup();
			// $this->dbTemp->create( 'wp_shopp_summary', (array) $summary_row );
		}
	}

	public function save_image_file( &$asset, $filename )
	{
		$image_storage_path = $this->get_image_storage_path();
		if( !file_exists($image_storage_path) )
		{
			mkdir($image_storage_path, 0775);
		}

		$file = $image_storage_path . $filename;
		if( !file_exists($file) )
		{
			$file_connection = fopen($file, 'w');
			// Write the contents to the file
			fwrite($file_connection, $asset);
			fclose($file_connection);
		}
	}

	public function get_image_storage_path()
	{
		if( !isset( $this->fs_storage_setting ) ) {
			$this->fs_storage_setting = unserialize(
				array_shift(
					$this->dbTemp->select(
						'wp_shopp_meta', 'value', array(
							'name = :n'		=> array( 'n' => 'FSStorage' ),
							'type = :t'		=> array( 't' => 'setting' ),
							'context = :c'	=> array( 'c' => 'shopp' )
						)
					)->fetch( false )
				)
			);
		}
		$image_storage_path = $this->fs_storage_setting['path']['image'];
		if ($image_storage_path[0] === '.')
		{
			$image_storage_path = WP_CONTENT_DIR . '/' . substr($image_storage_path, 2);
		}
		if( substr($image_storage_path, -1) !== '/' ) $image_storage_path .= '/';

		return $image_storage_path;
	}

	public function get_old_product_by_id( $old_product_id )
	{

	}

	public function reorder_by_keys( &$row, $row_key, $keys )
	{
		$key_order = array_keys( $keys );
		$sorted = array();
		foreach( $key_order as $key )
		{
		    $sorted[ $key ] = $row[ $key ];
		}
		$row = $sorted;
		return $row;
	}

	public function order_by_columns( &$data )
	{
		foreach( $data as $key => $row )
		{
			$type[ $key ] = $row[ 'type' ];
			$name[ $key ] = $row[ 'name' ];
		}

		// Sort the data with volume descending, edition ascending
		// Add $data as the last parameter, to sort by the common key
		array_multisort( $type, SORT_ASC, $name, SORT_ASC, $data );
	}

	function compare( $data_sets, $horizontal = true )
	{
		if( PHP_SAPI != 'cli' )
		{
			?>
			<div class="compare <?= $horizontal ? 'horizontal' : 'vertical' ?>">
			<?php foreach( $data_sets as $set ): ?>
				<div><?php var_dump( $set ) ?></div>
			<?php endforeach?>
			</div>
			<?php
		}
		foreach($data_sets as $name => $set) {
			xdebug_debug_zval_stdout( 'set' );
			Dev::output_to_json( $set, is_string($name) ? : 'output');
		}
	}

	public function reindex_products()
	{
		global $wpdb;
		if (!class_exists('ContentParser'))
			require(SHOPP_MODEL_PATH.'/Search.php');
		new ContentParser();

		$set = 10;
		$index_table = DatabaseObject::tablename(ContentIndex::$table);

		$total = DB::query("SELECT count(*) AS products,now() as start FROM $wpdb->posts WHERE post_type='".Product::$posttype."'");
		if (empty($total->products)) die('No products to index');
		$indexed = 0;
		for ($i = 0; $i*$set < $total->products; $i++) {
			$products = DB::query("SELECT ID FROM $wpdb->posts WHERE post_type='".Product::$posttype."' LIMIT ".($i*$set).",$set",'array','col','ID');
			foreach ($products as $id) {
				$Indexer = new IndexProduct($id);
				$Indexer->index();
				$indexed++;
			}
		}

	}
}

class ShoppData
{
	public function __construct( $object = null, $properties = array() )
	{
		if( is_object($object) ) $this->_copy( $object );
		foreach ( $properties as $key => $value )
			$this->$key = $value;
	}

	public function set($key, $value=null)
	{
		if( is_array($key) ) foreach( $key as $k => $v) $this->set($k, $v);
		else $this->$key = $value;
		return $this;
	}

	public function _copy( &$object )
	{
		foreach ( array_keys( (array) $this ) as $key )
			if( isset( $object->$key ) && !is_null( $object->$key ) )
				$this->$key = $object->$key;
		return $this;
	}

	function createInstance($className, array $arguments = array())
	{
	    if(class_exists($className)) {
	        return call_user_func_array(array(
	            new ReflectionClass($className), 'newInstance'),
	            $arguments);
	    }
	    return false;
	}
}

class DatedShoppData extends ShoppData
{
	public $created;
	public $modified;

	function __construct() {
		call_user_func_array(array('parent', '__construct'), func_get_args());
		foreach ( array( 'created', 'modified' ) as $var)
			if( empty($this->$var) ) $this->$var = date('Y-m-d H:i:s');
	}
}

class ShoppMeta extends DatedShoppData
{
	public $parent = 0;
	public $context = 'product';
	public $type = 'meta';
	public $name;
	public $value;
	public $numeral = 0.0;
	public $sortorder = 0;

	public function parent($parent_id)
	{
		$this->parent = (int) $parent_id;
	}
}

class ShoppSettingMeta extends ShoppMeta
{
	public $context = 'shopp';
	public $type = 'setting';
}


class ShoppProductImageMeta extends ShoppMeta
{
	public $type = 'image';
	public $name = 'original';
}

class ShoppImageSettingMeta extends ShoppMeta
{
	public $context = 'setting';
	public $type = 'image_setting';
}

class ShoppSpecMeta extends ShoppMeta
{
	public $type = 'spec';
}

class ShoppProductMeta extends ShoppMeta
{
	// public $context = 'product';
}

class ShoppCategoryMeta extends ShoppMeta
{
	public $context = 'category';
}

class ShoppPriceMeta extends ShoppMeta
{
	public $context = 'price';
}

class ShoppProductSpecMeta extends ShoppSpecMeta
{
	// public $context = 'product';
}

class ShoppCategorySpecMeta extends ShoppSpecMeta
{
	public $context = 'category';
}

class ShoppCategorySpecMetaRows extends ShoppData
{
	public $spectemplate;
	public $facetedmenus;
	public $variations;
	public $pricerange;
	public $priceranges;
	public $specs;
	public $options;
	public $prices;
	public $priority = 0;

	function __construct() {
		call_user_func_array(array('parent', '__construct'), func_get_args());
		foreach ( array( 'options', 'prices' ) as $var)
			if( empty($this->$var) ) $this->$var = serialize(array());
	}
}

class ShoppPriceRow extends DatedShoppData
{
	public $product		= 0;
	public $context		= 'price';
	public $type		= 0;
	public $optionkey	= 0;
	public $label		= '';
	public $sku			= '';
	public $price		= 0.0;
	public $saleprice	= 0.0;
	public $promoprice	= 0.0;
	public $cost		= 0.0;
	public $shipfee		= 0.0;
	public $stock		= 0;
	public $stocked		= 0;
	public $inventory;
	public $sale;
	public $shipping;
	public $tax;
	public $discounts	= '';
	public $sortorder	= 0;

	public function _copy( &$object )
	{
		parent::_copy( $object );
		$this->stocked = $this->stock;
		return $this;
	}
}

class ShoppSummaryRow extends ShoppData
{
	public $product = 0;
	public $sold = 0;
	public $grossed = 0.0;
	public $maxprice = 0.0;
	public $minprice = 0.0;
	public $ranges;
	public $taxed = null;
	public $lowstock;
	public $stock;
	public $inventory = 0;
	public $featured;
	public $variants;
	public $addons;
	public $sale;
	public $freeship;
	public $modified;

	function __construct() {
		call_user_func_array(array('parent', '__construct'), func_get_args());
		if( empty($this->modified) ) $this->modified = date('Y-m-d H:i:s');
	}

}


?>

