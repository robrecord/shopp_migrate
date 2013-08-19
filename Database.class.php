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
		    $this->_db->query($sql);
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

	public function fetch_array(&$query=null) {
		if (!$query) $query = &$this->_query;
		$query->fetch();
	    return $data;
	}

	public function fetch_array_assoc(&$query=null) {
		if (!$query) $query = &$this->_query;
		$query->fetch(PDO::FETCH_ASSOC);
	    return $data;
	}

	public function fetch_all_array($sql, $assoc = true) {
		$this->_query = $this->_db->query($sql);
		return $this->_query->fetchAll( $assoc ? PDO::FETCH_ASSOC : '' );
	}

	public function init_table($table_name, $class_name = 'Table')
	{
		if (!isset($this->$table_name)) {
			if (class_exists(ucfirst($table_name).'_'.$class_name)) {
			    $table = new $class_name($this);
			}
			else $table = new $class_name($this, $table_name, $this->_table_prefix);
			$this->$table_name = $table;
		}
		return $this->$table_name;
	}

	public function load_table($table_name) {
		$table = $this->init_table($table_name);
		$table->_result = $this->query("SELECT * FROM {$table->_name}");
		$table->_data = $this->fetch_all_array($table->_result);
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

class Database
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

	protected $_table_prefix = '';

	function __construct($host = "", $user = "", $pass = "", $name = "", $conn = true) {
	    if (!empty($host)) $this->_host = $host;
	    if (!empty($user)) $this->_user = $user;
	    if (!empty($pass)) $this->_pass = $pass;
	    if (!empty($name)) $this->_name = $name;
	    if ($conn == true) $this->connect();
	}

	function __destruct() {
	    @mysql_close($this->_link);
	}

	public function connect() {
	    if ($this->_link = mysql_connect($this->_host, $this->_user, $this->_pass, true)) {
	        if (!empty($this->_name)) {
	            if (!mysql_select_db($this->_name, $this->_link)) $this->exception("Could not connect to the database!");
	        }
	    } else {
	        $this->exception("Could not create database connection!");
	    }
	}

	public function close() {
	    @mysql_close($this->_link);
	}

	public function query($sql) {
	    if ($this->_query = @mysql_query($sql, $this->_link)) {
	        return $this->_query;
	    } else {
	    	var_dump($this->_query);
	        $this->exception("Could not query database!");
	        return false;
	    }
	}

	public function num_rows($qid) {
	    if (empty($qid)) {
	        $this->exception("Could not get number of rows because no query id was supplied!");
	        return false;
	    } else {
	        return mysql_num_rows($qid);
	    }
	}

	public function num_fields($qid) {
	    if (empty($qid)) {
	        $this->exception("Could not get number of fields because no query id was supplied!");
	        return false;
	    } else {
	        return mysql_num_fields($qid);
	    }
	}

	public function fetch_field($qid) {
	    if (empty($qid)) {
	        $this->exception("Could not fetch field because no query id was supplied!");
	        return false;
	    } else {
	        return mysql_fetch_field($qid);
	    }
	}

	public function fetch_array($qid) {
	    if (empty($qid)) {
	        $this->exception("Could not fetch array because no query id was supplied!");
	        return false;
	    } else {
	        $data = mysql_fetch_array($qid);
	    }
	    return $data;
	}

	public function fetch_array_assoc($qid) {
	    if (empty($qid)) {
	        $this->exception("Could not fetch array assoc because no query id was supplied!");
	        return false;
	    } else {
	        $data = mysql_fetch_array($qid, MYSQL_ASSOC);
	    }
	    return $data;
	}

	public function fetch_all_array($sql, $assoc = true) {
	    $data = array();
	    if ($qid = $this->query($sql)) {
	        if ($assoc) {
	            while ($row = $this->fetch_array_assoc($qid)) {
	                $data[] = $row;
	            }
	        } else {
	            while ($row = $this->fetch_array($qid)) {
	                $data[] = $row;
	            }
	        }
	    } else {
	        return false;
	    }
	    return $data;
	}

	function load_table($table_name) {
		$table = $this->init_table($table_name);
		$table->_result = $this->query("SELECT * FROM {$table->_name}");
		while ($values = $this->fetch_array_assoc($table->_result)) {
			var_dump($values);
			$table->add_row($values);
		}
		if (count($table->_data) < 1) echo "no data found in $table_name, {$this->_name}";

		return $table;
	}

	public function init_table($table_name, $class_name = 'Table')
	{
		if (!isset($this->$table_name)) {

			if (class_exists(ucfirst($table_name).'_'.$class_name)) {
			    $table = new $class_name($this);
			}
			else $table = new $class_name($this, $table_name, $this->_table_prefix);
			$this->$table_name = $table;
		}

		return $this->$table_name;
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
	    if ($id = mysql_insert_id()) {
	        return $id;
	    } else {
	        return false;
	    }
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

	private function exception($message) {
	    if ($this->_link) {
	        $this->_error = mysql_error($this->_link);
	        $this->_errno = mysql_errno($this->_link);
	    } else {
	        $this->_error = mysql_error();
	        $this->_errno = mysql_errno();
	    }
	    if (PHP_SAPI !== 'cli') {
	    ?>

	        <div class="alert-bad">
	            <div>
	                Database Error
	            </div>
	            <div>
	                Message: <?php echo $message; ?>
	            </div>
	            <?php if (strlen($this->_error) > 0): ?>
	                <div>
	                    <?php echo $this->_error; ?>
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
	       echo "MYSQL ERROR: " . ((isset($this->_error) && !empty($this->_error)) ? $this->_error:'') . "\n";
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
