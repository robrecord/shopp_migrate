<?
require_once @$plugin_path.'Db.php';
if (!class_exists('ShoppData'))
	require_once @$plugin_path.'Shopp.Objects.php';

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
		$this->connect( 'seita_rackspace_clone', 'dbOld' );
		// $this->connect( 'seita_vanilla_125', 'dbNew' );
		$this->connect( 'seita_vanilla_migrate', 'dbTemp' );
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

	function reset_tables()
	{
		$result = $this->dbTemp->load_sql( $this->plugin_path . 'seita_vanilla_125.sql' );
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

	public function save_wp_shopp_setting()
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
			$select = $this->dbTemp->select( 'wp_shopp_meta', '*', $condition );
			while( $result = $select->fetch() ) { // false = fetch array
				// if( !strpos('catskin_importer_', $result->name ) )
				// {
					$this->saved_settings[] = new ShoppSettingMeta( $result );
				// }

			}
			unset( $result, $select );
		}
	}
	public function copy_wp_shopp_setting()
	{
		// transfer settings
		foreach( $this->saved_settings as $new_setting )
		{
			$this->dbTemp->create( 'wp_shopp_meta', (array) $new_setting );
		}
	}

	// public function convert_wp_shopp_setting()
	// {
	// 	// select shopp settings
	// 	$where_shopp_settings = array(	'context = :c'	=> array( 'c' => 'shopp' ),
	// 									'type = :t'		=> array( 't' => 'setting' ) );
	// 	// select image settings
	// 	$where_image_settings = array(	'context = :c'	=> array( 'c' => 'setting' ),
	// 									'type = :t'		=> array( 't' => 'image_setting' ) );
	// 	// transfer settings
	// 	foreach( array( $where_shopp_settings, $where_image_settings ) as $condition )
	// 	{
	// 		$select = $this->dbNew->select( 'wp_shopp_meta', '*', $condition );
	// 		while( $result = $select->fetch() ) { // false = fetch array
	// 			// if( !strpos('catskin_importer_', $result->name ) )
	// 			// {
	// 				$new_setting = new ShoppSettingMeta( $result );
	// 				$this->dbTemp->create( 'wp_shopp_meta', (array) $new_setting );
	// 			// }

	// 		}
	// 		unset( $result, $select );
	// 	}
	// }

	public function convert_wp_shopp_category()
	{
		// update_option('shopp_category_children', '');

		// load temp wordpress
		if( !$this->using_wordpress ) $this->load_wordpress( 'vanilla_migrate' );

		// get old products
		$old_shopp_categories = $this->dbOld->select( 'wp_shopp_category' )->all();

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

		// delete_option('shopp_category_children');

		$this->cache->old_categories = array();

		$sorted_categories = $this->organize( $old_shopp_categories );

		$this->category_heirarchy = array();

		$this->process_categories( $sorted_categories );

		delete_option('shopp_category_children');
		// wp_cache_flush();

		// TESTS

		// $this->compare( array( $old_shopp_categories, get_terms( array( 'shopp_category' ), 'hide_empty=0' ) ) );

		// $temp_category_meta = $this->dbTemp->read( 'wp_shopp_meta','category','context' )->all();
		// $new_category_meta = $this->dbNew->read( 'wp_shopp_meta','category','context' )->all();
		// $this->compare( array( $old_shopp_categories, $temp_category_meta, $new_category_meta ) );

		// $temp_term_taxonomy = $this->dbTemp->read( 'wp_term_taxonomy','shopp_category','taxonomy' )->all();
		// $temp_terms = $this->dbTemp->select( 'wp_terms' )->all();

		// $this->compare( array( $old_shopp_categories, $temp_category_meta, $new_category_meta ) );
		// $this->compare( array( $old_shopp_categories, get_terms( array( 'shopp_category' ), 'hide_empty=0' ) ) );

		// $new_term_taxonomy = $this->dbNew->read( 'wp_term_taxonomy','shopp_category','taxonomy' )->all();
		// $new_terms = $this->dbNew->select( 'wp_terms' )->all();
		// echo "term_taxonomy (temp, new)";
		// $this->compare( array( $temp_term_taxonomy, $new_term_taxonomy ) );
		// echo "terms (temp, new)";
		// $this->compare( array( $temp_terms, $new_terms ) );
	}

	public function process_categories( &$categories )
	{
		foreach( $categories as $category )
		{
			$this->process_category( $category );

			if( isset( $category->children ) )
			{
				foreach( $category->children as &$child_category )
				{
					$child_category->parent = $category->new_term_id;
					$this->category_heirarchy[ $child_category->parent ][] = $category->new_term_id;
				}
				$this->process_categories( $category->children );
			}
		}
	}

	public function process_category( &$category, $parent_id = 0 )
	{
		// create wordpress term - also creates term_taxonomy entry
		$new_term = $this->wordpress_add_category_term( $category );
		// convert category options to metadata in shopp_meta
		$this->create_shopp_category_meta( $category, $new_term[ 'term_id' ] );

		// // save so we can reference old ids (etc) later
		$category->new_term_id = $new_term[ 'term_id' ];
		$category->new_taxonomy_id = $new_term[ 'term_taxonomy_id' ];

		$this->cache->old_categories[ $category->id ] = $category;

		return $category;
	}


	function wordpress_add_category_term( $category, $args = null )
	{
		return wp_insert_term( $category->name, 'shopp_category', $args ? : array(
			'description'	=> $category->description,
			'parent'		=> $category->parent,
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

			$this->create_product_spec_meta( $product->id, $post_id );

			$product->new_post_id = $post_id;
			$this->cache->old_products[ $product->id ] = $product;

			// TODO - is this the best way? wont we miss some?
			wp_delete_object_term_relationships( $post_id, 'shopp_category' );

		}

		// TESTS

		// // test product posts
		// $temp_shopp_product_posts = $this->dbTemp->read( 'wp_posts', 'shopp_product', 'post_type' )->all();
		// $new_shopp_product_posts = $this->dbNew->read( 'wp_posts', 'shopp_product', 'post_type' )->all();
		// $this->compare( array( $old_shopp_products, $temp_shopp_product_posts, $new_shopp_product_posts ) );

		// // test product meta
		// $where_product_meta = array(
		// 	'context = :c' => array( 'c' => 'product' ),
		// 	'type = :t' => array( 't' => 'meta' )
		// 	);
		// $temp_category_meta = $this->dbTemp->select( 'wp_shopp_meta', '*', $where_product_meta )->all();
		// $new_category_meta = $this->dbNew->select( 'wp_shopp_meta', '*', $where_product_meta )->all();
		// $this->compare( array( $old_shopp_products, $temp_category_meta, $new_category_meta ) );
	}

	public function convert_shopp_order_only()
	{
		// var_dump( $this->cache->old_products );

		$order_only_posts = $this->dbOld->select( 'wp_shopp_order_only_items' );

		while( $order_only_post = $order_only_posts->fetch() )
		{
			// $old_product_id = $this->dbOld->read( 'wp_shopp_product', $order_only_post->id, 'id' )->column( 'id' );
			if( isset( $this->cache->old_products[ $order_only_post->id ] ) )
			{
				$order_only_post->id = $this->cache->old_products[ $order_only_post->id ]->new_post_id;
				$this->dbTemp->create( 'wp_shopp_order_only_items', (array) $order_only_post );
			}
		}

		$order_only_cats = $this->dbOld->select( 'wp_shopp_order_only_cats' );

		while( $order_only_cat = $order_only_cats->fetch() )
		{
			$this->dbTemp->create( 'wp_shopp_order_only_cats', (array) $order_only_cat );
		}

	}

	public function convert_importer_edge_cat_map()
	{
		$edge_cat_map = $this->dbOld->select( 'wp_shopp_edge_category_map' );

		while( $edge_cat_map_row = $edge_cat_map->fetch() )
		{
			if( $old_category = $this->cache->old_categories[ (int) $edge_cat_map_row->category ] )
			{
				$edge_cat_map_row->category = $old_category->new_term_id;
				$this->dbTemp->create( 'wp_shopp_edge_category_map', (array) $edge_cat_map_row );
			}
		}
		$edge_catalog = $this->dbOld->select( 'wp_shopp_edge_catalog' );

		while( $edge_catalog_row = $edge_catalog->fetch() )
		{
			if( $old_product = $this->cache->old_products[ (int) $edge_catalog_row->product ] )
			{
				$edge_catalog_row->product = $old_product->new_post_id;
				$this->dbTemp->create( 'wp_shopp_edge_catalog', (array) $edge_catalog_row );
			}
		}

		$edge_cats = $this->dbOld->select( 'wp_shopp_edge_category' );

		while( $edge_cat = $edge_cats->fetch() )
		{
			$this->dbTemp->create( 'wp_shopp_edge_category', (array) $edge_cat );
		}

	}

	public function convert_wp_shopp_catalog()
	{
		// add category
		$old_shopp_catalog = $this->dbOld->read( 'wp_shopp_catalog', 'category', 'type' );

		// $this->compare( array( $this->cache->old_products, $this->cache->old_categories ) );

		$new_relationships = array();
		while( $old_catalog_entry = $old_shopp_catalog->fetch() )
		{
			if( isset( $this->cache->old_products[ $old_catalog_entry->product ] ) )
			{
				$new_post_id = $this->cache->old_products[ $old_catalog_entry->product ]->new_post_id;

				if(
					isset( $this->cache->old_categories[ $old_catalog_entry->parent ] ) &&
					!(
						isset( $new_relationships[ $new_post_id ] ) &&
						in_array(
							( $this->cache->old_categories[ $old_catalog_entry->parent ]->new_term_id
							), $new_relationships[ $new_post_id ]
						)
					)
				) {
					$new_relationships[ $new_post_id ][] = $this->cache->old_categories[ $old_catalog_entry->parent ]->new_term_id;
				}
			}
		}

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


	public function convert_wp_shopp_images()
	{
		// get image storage setting for old & temp dbs
		// values are either FSStorage or DBStorage
		$this->image_storage_setting = new stdClass();

		$this->image_storage_setting->old =
			array_shift(
				$this->dbOld->select(
					'wp_shopp_setting', 'value', array(
						'name = :n'		=> array( 'n' => 'image_storage' )
					)
				)->fetch( false )
			)
		;

		$this->image_storage_setting->temp =
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

		foreach( $this->cache->old_products as $old_product )
		{
			$where_image_original = array(
				'name = :n' => array( 'n' => 'original' ),
				'type = :t' => array( 't' => 'image' ),
				'parent = :p' => array( 'p' => $old_product->id )
			);

			$images_meta = $this->dbOld->select( 'wp_shopp_meta','*', $where_image_original );
			while( $image_meta = $images_meta->fetch() )
			{
				// set parent id of image to new post id of its product

				$image_meta_values = unserialize( $image_meta->value );

				if ( $image_meta_values->storage === "DBStorage" )
				{
					$image_asset = $this->dbOld->select( 'wp_shopp_asset', 'data', array( 'id = :i' => array( 'i' => $image_meta_values->uri ) ) )->fetch();

				} else die( "Migration of images from FSStorage not yet supported. Meta ID: " . $image_meta->id );

				if( $image_asset && ( strlen( $image_asset->data ) > 0 ) )
				{
					$image_meta->parent = $old_product->new_post_id;

					$image_meta_values->size = (int) strlen( $image_asset->data );

					if ( $this->image_storage_setting->temp === "FSStorage" ) // using filesystem storage
					{
						$image_meta_values->uri = $image_meta_values->filename;
						$this->save_image_file( $image_asset->data, $image_meta_values->filename );
					}
					else // using database image storage
					{
						// unset asset id
						// put asset into temp db; get back new asset id
						$asset_id = $this->dbTemp->create( 'wp_shopp_asset', (array) $image_asset )->id();
						$image_meta_values->uri = $asset_id;
					}

					$image_meta_values->storage = $this->image_storage_setting->temp;
					foreach( $image_meta_values as &$value) if ($value==="") $value = null;
					$image_meta->value = serialize( $image_meta_values );

					// save original images in temp db
					unset( $image_meta->id );
					$image_meta->id = $this->dbTemp->create( 'wp_shopp_meta', (array) $image_meta )->id();
				}
			}
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
			'product' 	=> $this->cache->old_products[ $row->product ]->new_post_id,
			'shipping'	=> 2, // off
			'inventory' => 2, // on
			'stock'		=> 1 // 1 in stock
			// 'stocked'	=> 1  // unsure what this is used for
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

	public function organize($original_data_set, $root_id = 0)
	{
		foreach ($original_data_set as $row) {
			$id = $row->id;
			$data_set[$id] = $row;
		}

		foreach ($data_set as $id => &$row) {
			$parent_id = isset($row->parent) ? $row->parent : $root_id;
			if ($parent_id != $root_id) {
				$data_set[$parent_id]->children[$id] = &$row;
			}
		}

		foreach ($data_set as $id => &$row) {
			$parent_id = isset($row->parent) ? $row->parent : $root_id;
			if ($parent_id == $root_id) {
				$data_organized[$id] = $row;
			}
		}

		return $data_organized;
	}
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
