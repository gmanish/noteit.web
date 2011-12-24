<?php
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "dbbase.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "shoplisttable.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "categorytable.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "shopitems.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "geoip.inc");

class Country {
	const kCol_CountryCode = 'countryCode';
	const kCol_CurrencyCode = 'currencyCode';
	const kCol_CurrencySymbol = 'currencySymbol';
	const kCol_CurrencyIsRight = 'currencyIsRight';
	const kCol_CurrencyName = 'currencyName';
	
	public $countryCode = "";
	public $currencyCode = "";
	public $currencySymbol = "";
	public $currencyIsRight = 0;
	public $currencyName = "";
	
	public function __construct(
		$countryCode, 
		$currencyCode, 
		$currencySymbol, 
		$currencyIsRight, 
		$currencyName) {
		$this->countryCode = $countryCode;
		$this->currencyCode = $currencyCode;
		$this->currencySymbol = $currencySymbol;
		$this->currencyIsRight = $currencyIsRight;
		$this->currencyName = $currencyName;
	}	
}

class Unit
{
	public $unitID = 0;
	public $unitName = "";
	public $unitAbbreviation = "";
	public $unitType = 1; // Default Metric System
	
	public function __construct($unitID, $unitName, $unitAbbreviation, $unitType)
	{
		$this->unitID = $unitID;
		$this->unitName = $unitName;
		$this->unitAbbreviation = $unitAbbreviation;
		$this->unitType = $unitType;
	}
}

class UserPreference {
	public $countryCode = "";
	public $currencyCode = "";
	
	public function __construct($countryCode, $currencyCode) {
		$this->countryCode = $countryCode;
		$this->currencyCode = $currencyCode;
	}	
}

class NoteItDB extends DbBase
{
    // Name of users Table columns
    const kTableUsers 			= 'users';
    const kColUserID 			= 'userID';
    const kColUserEmail 		= 'emailID';
    const kColUserFirstName		= 'firstName';
    const kColUserLastName		= 'lastName';
	const kColUserPassword 		= 'userPassword';
	const kColCountryCode		= 'countryCode';
	const kColCurrencyCode		= 'currencyCode';

	protected $db_userID;
	protected $db_username;
	protected $shop_list_db;
	protected $cat_list_db;
    protected $shop_items_db;
	protected $user_pref;
		
	protected function __construct($userID)
	{
        parent::__construct();

		$this->db_userID = $userID;
		$sql = sprintf("SELECT * from users WHERE userID=%d", $this->db_userID);
		$result = $this->get_db_con()->query($sql);
		
		if ($result != FALSE || mysqli_num_rows($result) == 1) {
			$row = $result->fetch_array();
			$this->db_username = $row['firstName'] . " " . $row['lastName'];
			$this->user_pref = new UserPreference(
							$row[Country::kCol_CountryCode], 
							$row[Country::kCol_CurrencyCode]);
			$result->free();
		}
        else 
        	throw new Exception("Invalid User Credentials: " . $this->get_db_con()->error);

		$this->shop_list_db = new ShopListTable($this, $userID);
		$this->cat_list_db = new CategoryTable($this, $userID);
		$this->shop_items_db = new ShopItems($this, $userID);
 	}
	
	public function get_user_pref() {
		return $this->user_pref;
	}
	
	public function get_db_userID() {
		return $this->db_userID;
	}
	
	public function get_db_username() {
		return $this->db_username;
	}
	
	public function &get_shoplist_table() {
		return $this->shop_list_db;
	}
	
	public function &get_catlist_table() {
		return $this->cat_list_db;
	}

    public function &get_shopitems_table() {
        return $this->shop_items_db;
    }
    
	public static function register_user(
								$userName,
								$password, 
								$emailID, 
								$firstName, 
								$lastName)
	{
        global $config;
		if (empty($emailID) || empty($password))
			throw new Exception("Email Id and/or password cannot be blank.");
			
		if (!filter_var($emailID, FILTER_VALIDATE_EMAIL))
			throw new Exception("Please provide a valid email id");
		
		$db_con = NULL;
		
		try
		{			
            $db_con = new MySQLi(
            	$config['MYSQL_SERVER'], 
            	$config['MYSQL_USER'], 
            	$config['MYSQL_PASSWD'], 
            	$config['MYSQL_DB']);
            	
             if ($db_con->connect_error)
                 throw new Exception('Could not connect to Server: ' . $db_con->error);

			// Email ID is already registered??
			$sql = sprintf(
				"SELECT %s FROM %s WHERE %s='%s'", 
				self::kColUserEmail,
				self::kTableUsers,
				self::kColUserEmail,
				$db_con->escape_string($emailID));
			
			$result = $db_con->query($sql);
			if ($result ==  FALSE || mysqli_num_rows($result) > 0)
            {
                NI::TRACE("NoteItDb::register_user: " . $db_con->error, __FILE__, __LINE__);
				throw new Exception('This email ID is already registered');
            }
            
			if ($result)
				$result->free();
			
			global $config;
			$salt = $config['SALT'];
			$salted_hash = sha1($salt . $password);
			
			// try to register this user
			$sql = sprintf(
				"INSERT INTO `%s` (`%s`,`%s`,`%s`, `%s`) 
				VALUES ('%s', '%s', '%s', UNHEX('%s'))", 
				self::kTableUsers,
				self::kColUserEmail,
				self::kColUserFirstName,
				self::kColUserLastName,
				self::kColUserPassword,
				$db_con->escape_string($emailID),
				$db_con->escape_string($firstName),
				$db_con->escape_string($lastName),
				$salted_hash);
			
			$result = $db_con->query($sql);
			if ($result == FALSE)
				throw new Exception('Could not register given user: ' . mysql_error());
			
			$db_con->close();
			$db_con = NULL;
		}
		catch(Exception $e)
		{
			if ($db_con != NULL) { 
				$db_con->close();
				$db_con = NULL;
			}
			throw $e;
		}
	}
	
	public static function &login_user_id($user_id) {
        $noteit_db = new NoteItDB($user_id);
        return $noteit_db;
	}
	
	// Returns self on true
	public static function &login_user_email($user_email, $password, $is_password_hashed) {
        	
        global $config;
		if (!filter_var($user_email, FILTER_VALIDATE_EMAIL))
			throw new Exception("Please provide a valid email id");

		if (empty($password))
			throw new Exception("Password cannot be blank");
		
        $db_con = new MySQLi(
        	$config['MYSQL_SERVER'], 
        	$config['MYSQL_USER'], 
        	$config['MYSQL_PASSWD'], 
        	$config['MYSQL_DB']);
    	
        /*
         * Use this instead of $db_con->connect_error if you need to ensure
         * compatibility with PHP versions prior to 5.2.9 and 5.3.0.
         */
         if (mysqli_connect_error())
             throw new Exception('Could not connect to Server: ' . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");

		if (!$is_password_hashed) {
			global $config;
			$salt = $config['SALT'];
			$salted_hash = sha1($salt . $password);
		} else {
			$salted_hash = $password;
		}
		
 		$sql = sprintf(
			"SELECT `userID` FROM `%s` WHERE `%s`='%s' AND `%s`=UNHEX('%s')", 
			self::kTableUsers,
			self::kColUserEmail,
			$db_con->escape_string($user_email),
			self::kColUserPassword,
			$salted_hash);
				
		$result = $db_con->query($sql);
		
		 // There should be one and only one user by this email ID
		if ($result && mysqli_num_rows($result) == 1) {
			$row = $result->fetch_array();
            $noteit_db = new NoteItDB($row[self::kColUserID]);
            $result->free();
			$db_con->close();
			$db_con = NULL;
			return $noteit_db;
		}
		else {
			if ($result) 
				$result->free();
			$db_con->close();
			$db_con = NULL;
			throw new Exception("User email or password is incorrect");
		}
	}
	
	public function save_preferences($preferences) {
		if ($preferences != NULL) {
			$sql = sprintf(
				"UPDATE `users` 
				SET `%s`='%s', `%s`='%s' 
				WHERE `%s`=%d", 
				self::kColCountryCode,
				$preferences->countryCode,
				self::kColCurrencyCode,
				$preferences->currencyCode,
				self::kColUserID,
				$this->db_userID);
				
			$result = $this->get_db_con()->query($sql);
			if (!$result)
				throw new Exception("Error Saving Preference.");
		}	
	}
	
	public static function list_country($ip_address)
	{
		global $config;
		$country = new Country("US", "USD", "$", 1, "US Dollar");
		if (!file_exists($config['GEOIP_DB']))
			throw new Exception("GeoIP Database Not Installed");
			
		$gi = geoip_open($config['GEOIP_DB'], GEOIP_STANDARD);
		if ($gi != NULL) {
			$countryCode = geoip_country_code_by_addr($gi, $ip_address);
			if ($countryCode != ""){
				global $config;
				
				$db_con = new MySQLi(
					$config['MYSQL_SERVER'], 
					$config['MYSQL_USER'], 
					$config['MYSQL_PASSWD'], 
					$config['MYSQL_DB']);
				
				if (mysqli_connect_error())
					throw new Exception('Could not connect to Server' . "(" . mysqli_connect_errno() . ")");
				
				$sql = sprintf("SELECT * FROM `countrytable`
								WHERE `countryCode`=UCASE('%s')",
							 	$countryCode);
				$result = $db_con->query($sql);
									
				if ($result && mysqli_num_rows($result) > 0) {
					while ($row = $result->fetch_array()){
						$country = new Country(
							$row[Country::kCol_CountryCode],
							$row[Country::kCol_CurrencyCode],
							$row[Country::kCol_CurrencySymbol],
							$row[Country::kCol_CurrencyIsRight],
							$row[Country::kCol_CurrencyName]);
					}
		            $result->free();
				}
				
				$db_con->close();
				$db_con = NULL;
			}

			geoip_close($gi);
			$gi = NULL;
		}
		
		return $country;
	}

	public static function list_countries() {
        global $config;

        $db_con = new MySQLi(
        	$config['MYSQL_SERVER'], 
        	$config['MYSQL_USER'], 
        	$config['MYSQL_PASSWD'], 
        	$config['MYSQL_DB']);
    	
         if (mysqli_connect_error())
             throw new Exception('Could not connect to Server' . "(" . mysqli_connect_errno() . ")");

 		$sql = sprintf("SELECT * FROM `countrytable`");
		$result = $db_con->query($sql);
		
		$countries = array();
		if ($result && mysqli_num_rows($result) > 0) {
			while ($row = $result->fetch_array()){
				$country = new Country(
					$row[Country::kCol_CountryCode],
					$row[Country::kCol_CurrencyCode],
					$row[Country::kCol_CurrencySymbol],
					$row[Country::kCol_CurrencyIsRight],
					$row[Country::kCol_CurrencyName]);
				$countries[] = $country;
			}
            $result->free();
			$db_con->close();
			$db_con = NULL;
			return $countries;
		}
		else {
			$db_con->close();
			$db_con = NULL;
			throw new Exception("Could not fetch currency related data.");
		}			
	}
	
	public function list_units($unit_type, &$functor_obj, $function_name='iterate_unit')
	{
		$sql = sprintf("SELECT * FROM `units` WHERE `unitType`=%d OR `unitType`=%d", $unit_type, 0);
		$result = $this->get_db_con()->query($sql);
		if ($result || mysqli_num_rows($this->get_db_con()) > 0)
		{
			while ($row = $result->fetch_array())
			{
				call_user_func(
					array($functor_obj, $function_name), // invoke the callback function
					new Unit($row[0], $row[1], $row[2], $row[3]));
			}
			$result->free();
		}
		else 
		{
			throw new Exception("Error Processing Request (" . __FILE__ . __LINE__ . ")");
		}
	}
}
?>
