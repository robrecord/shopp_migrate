<?

require_once('Table.class.php');

/**
*
*/
class PDO_Database
{
	protected $_charset = 'utf8';
	protected $_collate = '';
	protected $_host;
	protected $_user;
	protected $_pass;
	protected $_name;

	private $_link;
	private $_error;
	private $_errno;
	private $_query;
	private $_db;

	protected $_table_prefix = '';

	function __construct() {
	    $this->connect();
	}

	// function __destruct() {
	//     @mysql_close($this->_link);
	// }

	public function connect() {
		$this->_db = new PDO("mysql:host={$this->_host};dbname={$this->_name}", $this->_user, $this->_pass, array(
			PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
	}

	public function close() {
	    // @mysql_close($this->_link);
	}

	public function query($sql) {

		try {
		    return $this->_db->query($sql);
		} catch(PDOException $ex) {
	        $this->exception("Could not query database!", $ex);
	        return false;
		}

	        // return $this->_query;
	}

	public function num_rows() {
	}

	public function num_fields() {
	}

	public function fetch_field($qid) {
	}

	public function fetch_array($query = null) {
		if ($query) $request = $this->query($query);
		return $request->fetch();
	}

	public function fetch_array_assoc($query = null) {
		if ($query) $request = $this->query($query);
		return $request->fetch(PDO::FETCH_ASSOC);
	}

	public function fetch_all_array($query = null, $assoc = true) {
		if ($query) $request = $this->query($query);
		return $request->fetchAll( $assoc ? PDO::FETCH_ASSOC : '' );
	}

	public function init_table($table_name, $class_name = 'Table')
	{
		if (!isset($this->$table_name)) {
			if (class_exists(ucfirst($table_name).'_'.$class_name)) {
			    $this->$table_name = new $class_name(&$this);
			}
			else $this->$table_name = new $class_name(&$this, $table_name, $this->_table_prefix);
		}
		return $this->$table_name;
	}

	public function load_table($table_name) {
		$table = $this->init_table($table_name);
		$request = $this->_db->query("SELECT * FROM {$table->_name}");
		$table->_data = $request->fetchAll(PDO::FETCH_ASSOC);
		if (count($table->_data) < 1) echo "no data found in $table_name, {$this->_name}";
		return $table;
	}


	function __autoload($class)
	{
	    include($class . '.php');

	    // Check to see whether the include declared the class
	    if (!class_exists($class, false)) {
	        trigger_error("Unable to load class: $class", E_USER_WARNING);
	    }
	}

	public function last_id() {

	}

	public function load_sql($file, $delimiter = ';')
	{
	    set_time_limit(0);
	    if (is_file($file) === true)
	    {
	        $file = fopen($file, 'r');
	        if (is_resource($file) === true)
	        {
	            $query = array();
	            while (feof($file) === false)
	            {
	                $query[] = fgets($file);
	                if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1)
	                {
	                    $query = trim(implode('', $query));
	                    $result = $this->query($query);
	                }
	                if (is_string($query) === true)
		                $query = array();
	            }
	            return fclose($file);
	        }
	    }
	    return false;
	}

	private function exception($message, $ex = null) {

		$error = @$ex->getMessage();

	    if (PHP_SAPI !== 'cli') {
	    ?>

	        <div class="alert-bad">
	            <div>
	                Database Error
	            </div>
	            <div>
	                Message: <?php echo $message; ?>
	            </div>
	            <?php if (strlen($error) > 0): ?>
	                <div>
	                    <?php echo $error; ?>
	                </div>
	            <?php endif; ?>
	            <div>
	                Script: <?php echo @$_SERVER['REQUEST_URI']; ?>
	            </div>
	            <?php if (strlen(@$_SERVER['HTTP_REFERER']) > 0): ?>
	                <div>
	                    <?php echo @$_SERVER['HTTP_REFERER']; ?>
	                </div>
	            <?php endif ?>
	        </div>
	    <?php
	    } else {
	       echo "MYSQL ERROR: " . ((isset($error) && !empty($error)) ? $error:'') . "\n";
	    }
	}

}


class WP_Database extends PDO_Database {
	/** MySQL database username */
	protected $_user = 'wordpress';

	/** MySQL database password */
	protected $_pass = 'w0rdpr355';

	/** MySQL hostname */
	protected $_host = 'localhost';

	protected $_table_prefix = 'wp_';

}

?>
