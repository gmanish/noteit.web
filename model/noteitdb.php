<?php
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "dbbase.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "shoplisttable.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "categorytable.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "shopitems.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "reports.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "geoip.inc");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "metadatatable.php");
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . "userinbox.php");


class Country {
	
	const kTable_CountryTable 	= 'countrytable';

	const kCol_CountryId		= 'id';
	const kCol_CountryCode 		= 'countryCode';
	const kCol_CountryName 		= 'countryName';
	const kCol_CurrencyId		= 'currencyid';
	
	const kDefault_CountryId	= '211';
	const kDefault_CountryCode	= 'US';
	const kDefault_CountryName	= 'UNITED STATES';
	const kDefault_CurrencyId	= '135';
	
	public $countryId			= 0;
	public $countryCode 		= "";
	public $countryName 		= "";
	public $currencyId 			= 0;
	
	public function __construct(
		$countryId,
		$countryCode, 
		$countryName,
		$currencyId) {
		
		$this->countryId		= $countryId;
		$this->countryCode 		= $countryCode;
		$this->countryName 		= $countryName;
		$this->currencyId		= $currencyId;
	}	
}

class Currency {
	
	const kTable_CurrencyTable	= 'currencytable';
	
	const kCol_CurrencyId 		= 'currencyid';
	const kCol_CurrencyCode 	= 'currencyCode';
	const kCol_CurrencySymbol 	= 'currencySymbol';
	const kCol_CurrencyIsRight 	= 'currencyIsRight';
	const kCol_CurrencyName 	= 'currencyName';
	
	public $currencyId			= 0;
	public $currencyCode 		= "";
	public $currencySymbol 		= "";
	public $currencyIsRight 	= 0;
	public $currencyName 		= "";
	
	public function __construct(
		$currencyId,
		$currencyCode, 
		$currencySymbol, 
		$currencyIsRight, 
		$currencyName) {

		$this->currencyId 		= $currencyId;
		$this->currencyCode 	= $currencyCode;
		$this->currencySymbol 	= $currencySymbol;
		$this->currencyIsRight 	= $currencyIsRight;
		$this->currencyName 	= $currencyName;
	}
}

class Unit
{
	public $unitID 				= 0;
	public $unitName 			= "";
	public $unitAbbreviation 	= "";
	public $unitType 			= 1; // Default Metric System
	
	public function __construct(
		$unitID, 
		$unitName,
		$unitAbbreviation, 
		$unitType)
	{
		$this->unitID = $unitID;
		$this->unitName = $unitName;
		$this->unitAbbreviation = $unitAbbreviation;
		$this->unitType = $unitType;
	}
}

class UserPreference {
	
	public $currencyId = 0;
	public $currencyCode = "";
	
	public function __construct($currencyId) {
		
		$this->currencyId = $currencyId;
	}
	
	public function get_currencycode() {
		
		if ($this->currencyCode == "") {
			return $this->currencyCode = NoteItDB::get_currencycode_for_id($this->currencyId);
		} else {
			return $this->currencyCode;
		}
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
	const kColCurrencyId		= 'currencyId';
	
	const kMIN_PASSWORD_LENGTH 	= 6;

	protected $db_userID;
	protected $db_username;
	protected $db_user_email;
	protected $shop_list_db;
	protected $cat_list_db;
    protected $shop_items_db;
	protected $user_pref;
	protected $reports;
	protected $metadata_db;
	protected $userinbox_db;
	
	protected function __construct($userID)
	{
        parent::__construct();

		$this->db_userID = $userID;
		$sql = sprintf("SELECT * from users WHERE userID=%d", $this->db_userID);
		$result = $this->get_db_con()->query($sql);
		
		if ($result != FALSE || mysqli_num_rows($result) == 1) {
			$row = $result->fetch_array();
			$this->db_username = $row['firstName'] . " " . $row['lastName'];
			$this->db_user_email = $row['emailID'];
			$this->user_pref = new UserPreference($row[self::kColCurrencyId]);
			$result->free();
		}
        else 
        	throw new Exception("Invalid User Credentials: " . $this->get_db_con()->error);

		$this->shop_list_db 	= new ShopListTable($this, 	$userID);
		$this->cat_list_db 		= new CategoryTable($this, 	$userID);
		$this->shop_items_db 	= new ShopItems($this, 		$userID, $this->user_pref);
		$this->reports 			= new Reports($this, 		$userID);
		$this->metadata_db 		= new MetadataTable($this, 	$userID);
		$this->userinbox_db 	= new UserInbox($this, 		$userID);
 	}
	
	public function __destruct() {
		parent::__destruct();
	}
	
	public function get_reports() {
		return $this->reports;
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
	
	public function get_db_useremail() {
		return $this->db_user_email;
	}
	
	public function get_db_userCurrency() {
		return $this->user_pref->currencyId;
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
    
	public function &get_metadata_table() {
		return $this->metadata_db;
	}
	
	public function &get_userinbox_table() {
		return $this->userinbox_db;
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
            	
             if ($db_con->connect_error) {
                 throw new Exception('Could not connect to Server: ' . $db_con->error);
			 }

			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
			}

			// Email ID is already registered??
			$sql = sprintf(
				"SELECT %s FROM %s WHERE %s='%s'", 
				self::kColUserEmail,
				self::kTableUsers,
				self::kColUserEmail,
				$db_con->escape_string($emailID));
			
			$result = $db_con->query($sql);
			if ($result ==  FALSE || mysqli_num_rows($result) > 0) {
                NI::TRACE("NoteItDb::register_user: " . $db_con->error, __FILE__, __LINE__);
				throw new Exception('This email ID is already registered');
            }
            
			if (strlen($password) < self::kMIN_PASSWORD_LENGTH) {
				throw new Exception("Password must be at least " . self::kMIN_PASSWORD_LENGTH . " characters in length");
			}
			
			if ($result) {
				$result->free();
			}
			
			global $config;
			$salt = $config['SALT'];
			$salted_hash = sha1($salt . $password);
			 
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			$nativeCountry = NoteItDB::list_country($ipAddress);
			$nativeCurrency = NoteItDB::get_currency_for_country($nativeCountry->countryCode);
				
			// try to register this user
			$sql = sprintf(
					"INSERT INTO `%s` (`%s`,`%s`,`%s`, `%s`, `%s`) 
					VALUES ('%s', '%s', '%s', UNHEX('%s'), %d)", 
					self::kTableUsers,
					self::kColUserEmail,
					self::kColUserFirstName,
					self::kColUserLastName,
					self::kColUserPassword,
					self::kColCurrencyId,
					$db_con->escape_string($emailID),
					$db_con->escape_string($firstName),
					$db_con->escape_string($lastName),
					$salted_hash,
					$nativeCurrency->currencyId);
			
			$noteit_db = NULL;
			$isTransactional = $db_con->autocommit(FALSE);
			if (!$isTransactional) {
				throw new Exception("Could Note Create Transaction.");
			}
			
			try {
				$result = $db_con->query($sql);
				if (!$result) {
					throw new Exception('Could not register given user: ' . $db_con->error);
				}

				$user_id = $db_con->insert_id;
				CategoryTable::createFactoryCategories($user_id, $db_con);
				
				$commit = $db_con->commit(); 
				if (!$commit) {
					throw new Exception('Could not Commit Transaction: ' . $db_con->error);
				}
				
				$db_con->close();
				$db_con = NULL;
				return $user_id;
				
			} catch (Exception $e) {
				if ($isTransactional && $db_con != NULL) {
					$db_con->rollback();
				}
				throw $e;	
			}			
		}
		catch(Exception $e) {
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
	public static function &login_user_email(
		$user_email, 
		$password, 
		$is_password_hashed) {
        
		$db_con = NULL;
		
		try {
	        	
	        global $config;
			if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Please provide a valid email id");
			}
	
			if (empty($password)) {
				throw new Exception("Password cannot be blank");
			}
			
	        $db_con = new MySQLi(
	        	$config['MYSQL_SERVER'], 
	        	$config['MYSQL_USER'], 
	        	$config['MYSQL_PASSWD'], 
	        	$config['MYSQL_DB']);
	    	
	        /*
	         * Use this instead of $db_con->connect_error if you need to ensure
	         * compatibility with PHP versions prior to 5.2.9 and 5.3.0.
	         */
	         if (mysqli_connect_error()) {
	             throw new Exception(
	             	'Could not connect to Server: ' . 
	             	mysqli_connect_error() . "(" . 
	             	mysqli_connect_errno() . ")");
			 }
			 
			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
			}
	
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
				$db_con->escape_string($salted_hash));
					
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
				if ($result) {
					$result->free();
				}
				
				throw new Exception("User email or password is incorrect");
			}
		} catch (Exception $e) {
			
			if ($db_con) {
				$db_con->close();
				$db_con = NULL;
			}
			
			throw $e;
		}
	}
	
	public function change_password($old_password, $new_password) {
		
		if (empty($new_password) || empty($old_password))
			throw new Exception("Password cannot be empty.");
		else if ($new_password == $old_password)
			throw new Exception("The new password cannot be the same as the old password."); 
		else if (strlen($new_password) < self::kMIN_PASSWORD_LENGTH)
			throw new Exception("Password must be at least " . self::kMIN_PASSWORD_LENGTH . " characters in length");
			
		global $config;
		$salt = $config['SALT'];
		$salted_hash_new = sha1($salt . $new_password);
		$salted_hash_old = sha1($salt . $old_password);

		$sql = 	sprintf("UPDATE `users`
						SET `userPassword`=UNHEX('%s') 
						WHERE `userID`=%d AND `userPassword`=UNHEX('%s')",
						$salted_hash_new,
						$this->db_userID,
						$salted_hash_old);
		echo($sql);
		$result = $this->get_db_con()->query($sql);
		if ($this->get_db_con()->affected_rows <= 0) {
			throw new Exception("Passwords do not match.");
		}
	}
	
	public function save_preferences($preferences) {
			
		if ($preferences != NULL) {
				
			$sql = sprintf(
				"UPDATE `users` 
				SET `" . self::kColCurrencyId . "`=%d 
				WHERE `" . self::kColUserID . "`=%d", 
				$preferences->currencyId,
				$this->db_userID);
				
			$result = $this->get_db_con()->query($sql);
			if (!$result) {
				throw new Exception("Error Saving Preference.");
			}
		}	
	}
	
	public static function list_country($ip_address)
	{
		global $config;
		
		$country = new Country(
				Country::kDefault_CountryId,
				Country::kDefault_CountryCode,
				Country::kDefault_CountryName,
				Country::kDefault_CurrencyId);
		
		if (!file_exists($config['GEOIP_DB'])) {
			throw new Exception("A required database was not found. Server Installation is corrupt.");
		}
		
		$gi = geoip_open($config['GEOIP_DB'], GEOIP_STANDARD);
		if ($gi != NULL) {
			
			$countryCode = geoip_country_code_by_addr($gi, $ip_address);
			if ($countryCode != ""){
				
				$db_con = NULL;
				
				try {
									
					$db_con = new MySQLi(
						$config['MYSQL_SERVER'], 
						$config['MYSQL_USER'], 
						$config['MYSQL_PASSWD'], 
						$config['MYSQL_DB']);
					
					if (mysqli_connect_error()) {
						throw new Exception('Could not connect to Server' . "(" . mysqli_connect_errno() . ")");
					} 
					
					if (!$db_con->set_charset("utf8")) {
						throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
					}
					
					$sql = sprintf("SELECT * FROM `countrytable`
									WHERE `countryCode`=UCASE('%s')",
								 	$countryCode);
					
					$result = $db_con->query($sql);
					if ($result && mysqli_num_rows($result) == 1) {
							
						while ($row = $result->fetch_array()){
							
							$country = new Country(
								$row[Country::kCol_CountryId],
								$row[Country::kCol_CountryCode],
								$row[Country::kCol_CountryName],
								$row[Country::kCol_CurrencyId]);
						}
			            $result->free();
					}
					
					$db_con->close();
					$db_con = NULL;
				} catch (Exception $e) {
					
					if ($db_con != NULL) {
						$db_con->close();
						$db_con = NULL;
					}
					
					throw $e;					
				}
			}

			geoip_close($gi);
			$gi = NULL;
		} else {
			throw new Exception("A required database was not found. Server Installation is corrupt.");
		}
		
		return $country;
	}

	public static function list_countries() {
        	
        global $config;
		$db_con = NULL;
		
		try {
	        $db_con = new MySQLi(
	        	$config['MYSQL_SERVER'], 
	        	$config['MYSQL_USER'], 
	        	$config['MYSQL_PASSWD'], 
	        	$config['MYSQL_DB']);
	    	
			if (mysqli_connect_error()) {
			    throw new Exception(
			    	'Could not connect to Server' . "(" . 
			    	mysqli_connect_errno() . ")");
			}

			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
			}
			
	 		$sql = sprintf("SELECT * FROM `countrytable` ORDER BY `countryName`");
			$result = $db_con->query($sql);
			
			$countries = array();
			if ($result && mysqli_num_rows($result) > 0) {
					
				while ($row = $result->fetch_array()){
					$country = new Country(
							$row[Country::kCol_CountryId],							
							$row[Country::kCol_CountryCode],
							$row[Country::kCol_CountryName],
							$row[Country::kCol_CurrencyId]);
						
					$countries[] = $country;
				}
				
	            $result->free();
				$db_con->close();
				$db_con = NULL;
				
				return $countries;
			} else {
				throw new Exception("Could not fetch currency related data.");
			}
		} catch (Exception $e) {
				
			$db_con->close();
			$db_con = NULL;
			throw $e;
		}		
	}

	public static function get_currency_for_country($countryCode) {

		global $config;
		$db_con = NULL;
		
		try {
			$db_con = new MySQLi(
					$config['MYSQL_SERVER'],
					$config['MYSQL_USER'],
					$config['MYSQL_PASSWD'],
					$config['MYSQL_DB']);
			
			if (mysqli_connect_error()) {
				throw new Exception(
						'Could not connect to Server' . "(" .
						mysqli_connect_errno() . ")");
			}
				
			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
			}
			
			$sql = sprintf("SELECT CUT.* 
					FROM `countrytable` AS COT
					INNER JOIN `currencytable` AS CUT
					ON COT.`currencyid` = CUT.`currencyid`
					WHERE COT.`countrycode` = '%s'", $countryCode);
			$result = $db_con->query($sql);
			
			$currency = NULL;
			if ($result && mysqli_num_rows($result) > 0) {
				
				while($row = $result->fetch_array()) {
					$currency = new Currency(
						$row[Currency::kCol_CurrencyId],
						$row[Currency::kCol_CurrencyCode],
						$row[Currency::kCol_CurrencySymbol],
						$row[Currency::kCol_CurrencyIsRight],
						$row[Currency::kCol_CurrencyName]);
					break;					
				}
			}
			return $currency;
			
		} catch (Exception $e) {
			
			$db_con->close();
			$db_con = NULL;
			throw $e;
		}
	}
	
	public static function get_currencycode_for_id($currencyId) {
		
		global $config;
		$db_con = NULL;
		
		try {
			$db_con = new MySQLi(
					$config['MYSQL_SERVER'],
					$config['MYSQL_USER'],
					$config['MYSQL_PASSWD'],
					$config['MYSQL_DB']);
		
			if (mysqli_connect_error()) {
				throw new Exception(
						'Could not connect to Server' . "(" .
						mysqli_connect_errno() . ")");
			}
				
			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');
			}
				
			$sql = sprintf("select `currencyCode` from `currencytable` where `currencyid`=%d", $currencyId);
			$result = $db_con->query($sql);
				
			$currencyCode = "";
			if ($result && mysqli_num_rows($result) > 0) {
					
				while ($row = $result->fetch_array()){
					$currencyCode = $row[Currency::kCol_CurrencyCode];
					break;
				}
		
				$result->free();
				$db_con->close();
				$db_con = NULL;
		
				return $currencyCode;
			} else {
				throw new Exception("Could not fetch currency related data.");
			}
		} catch (Exception $e) {
		
			$db_con->close();
			$db_con = NULL;
			throw $e;
		}
	}
	
	public static function list_currencies() {
        	
        global $config;
		$db_con = NULL;
		
		try {
	        $db_con = new MySQLi(
	        	$config['MYSQL_SERVER'], 
	        	$config['MYSQL_USER'], 
	        	$config['MYSQL_PASSWD'], 
	        	$config['MYSQL_DB']);
	    	
			if (mysqli_connect_error()) {
			    throw new Exception(
			    	'Could not connect to Server' . "(" . 
			    	mysqli_connect_errno() . ")");
			}
			
			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');			
			}
			
	 		$sql = sprintf("select distinct * from `currencytable` group by `currencyName`");
			$result = $db_con->query($sql);
			
			$currencies = array();
			if ($result && mysqli_num_rows($result) > 0) {
					
				while ($row = $result->fetch_array()){
					$currency = new Currency(
						$row[Currency::kCol_CurrencyId],
						$row[Currency::kCol_CurrencyCode],
						$row[Currency::kCol_CurrencySymbol],
						$row[Currency::kCol_CurrencyIsRight],
						$row[Currency::kCol_CurrencyName]);
						
					$currencies[] = $currency;
				}
				
	            $result->free();
				$db_con->close();
				$db_con = NULL;
				
				return $currencies;
			} else {
				throw new Exception("Could not fetch currency related data.");
			}
		} catch (Exception $e) {
				
			$db_con->close();
			$db_con = NULL;
			throw $e;
		}		
	}
	
	public function list_units($unit_type, &$functor_obj, $function_name='iterate_unit')
	{
		$sql = sprintf("SELECT * FROM `units` 
						ORDER BY 
							CASE WHEN `unitType`=0 THEN 1 
								 WHEN `unitType`=%d THEN 2 
								 ELSE 3  
							END", $unit_type);
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
	
	public function get_inbox($user_id) {
		
		return new UserInbox($this, $user_id);
	}	
	
	public static function do_forgot_password($email_id) {
		
		// it is important not to given back any feedback about whether the email id matched a 
		// record on our database or not. It does reduce the usability, but increases the security.
		
		$db_con = NULL;
		
		try {
		
			global $config;
			if (!filter_var($email_id, FILTER_VALIDATE_EMAIL)) {
				throw new Exception("Please provide a valid email id");
			}
		
			$db_con = new MySQLi(
					$config['MYSQL_SERVER'],
					$config['MYSQL_USER'],
					$config['MYSQL_PASSWD'],
					$config['MYSQL_DB']);
		
			/*
			 * Use this instead of $db_con->connect_error if you need to ensure
			* compatibility with PHP versions prior to 5.2.9 and 5.3.0.
			*/
			if (mysqli_connect_error()) {
				throw new Exception(
						'Could not connect to Server: ' .
						"(" . mysqli_connect_errno() . ")");
			}
		
			if (!$db_con->set_charset("utf8")) {
				throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');
			}
		
			$salt = $config['SALT'];
			$salted_hash = sha1($salt . self::rand_str(40));
			
			$sql = sprintf("SELECT 
								`userID`
							FROM 
								`users`
							WHERE 
								`emailID`='%s'",
							$db_con->escape_string($email_id));
			$result = $db_con->query($sql);
			
			if ($result != FALSE && mysqli_num_rows($result) == 1) {
				
				if ($row = mysqli_fetch_assoc($result)) {
					
					$result->free();
					$userID = intval($row[self::kColUserID]);

					$sql = sprintf("INSERT INTO 
										password_recovery (`user_id_FK`, `recovery_passwd`, `created_datetime`) 
									VALUES 
										(%d, UNHEX('%s'), now())
									ON DUPLICATE KEY UPDATE
										`recovery_passwd`=UNHEX('%s'), `created_datetime`=now()",
									$userID,
									$salted_hash,
									$salted_hash);
	
					$result = $db_con->query($sql);
					if ($result == TRUE) {
						// Don't return failure of error. We don't want people trying to guess what emails
						// are registered with us. This just adds a little more security at cost of usability.
 						if (!self::send_reset_email($email_id, $userID, $salted_hash))
							throw new Exception("Failed to send message");
					}
				}
			}
			
			//SET `userPassword`=UNHEX('%s')
			$db_con->close();
			$db_con = NULL;
				
		} catch (Exception $e) {
			
			if ($db_con != NULL) {
				$db_con->close();
				$db_con = NULL;
			}
				
			throw $e;
		}
	}
	
	public static function do_reset_password($userID, $token, $new_password) {
		
		$db_con = NULL;
		
		try {
		
			global $config;

			if (self::is_valid_password($new_password)) {
				
				$db_con = new MySQLi(
						$config['MYSQL_SERVER'],
						$config['MYSQL_USER'],
						$config['MYSQL_PASSWD'],
						$config['MYSQL_DB']);
			
				/*
				 * Use this instead of $db_con->connect_error if you need to ensure
				* compatibility with PHP versions prior to 5.2.9 and 5.3.0.
				*/
				if (mysqli_connect_error()) {
					throw new Exception(
							'Could not connect to Server: ' .
							"(" . mysqli_connect_errno() . ")");
				}
			
				if (!$db_con->set_charset("utf8")) {
					throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');
				}
			
				$salt = $config['SALT'];
				$salted_hash = sha1($salt . $new_password);
				
				// Begin Transaction
				$isTransactional = $db_con->autocommit(FALSE);
				if (!$isTransactional) {
					throw new Exception("Could Not Create Transaction");	
				}
					
				$sql = sprintf("UPDATE 
									`users` AS `usrs`
								INNER JOIN 
									`password_recovery` AS `pr` 
								ON 
									`usrs`.`userID`=`pr`.`user_id_FK` 
								SET 
									`userPassword`=UNHEX('%s')
								WHERE 
									`usrs`.`userID`=%d AND 
									`pr`.`recovery_passwd`=UNHEX('%s') AND 
									TIMESTAMPDIFF(HOUR, `pr`.`created_datetime`, NOW()) < 24",
								$salted_hash,
								$userID,
								$token);
				
				$result = $db_con->query($sql);
				if ($result == FALSE) {
					throw new Exception("Error in updating password. (" . $db_con->errno . ")");
				} else if ($db_con->affected_rows == 0) {
					throw new Exception("Could not set new password. The reset password link may 
							have expired or you may have used the same password as the old one.");
				}
				
				// Remove the reset password link from database
				$sql = sprintf("DELETE FROM 
									`password_recovery` 
								WHERE 
									`user_id_FK`=%d AND 
									`recovery_passwd`=UNHEX('%s')",
								$userID,
								$token);
				
				$result = $db_con->query($sql);
				if (!$result || $db_con->affected_rows <= 0) {
					throw new Exception("An unknown error occurred while trying to reset password.");
				}
				
				// End Transaction
				if ($isTransactional == TRUE) {
					$db_con->commit();
					$db_con->autocommit(TRUE);
				}
				
				$db_con->close();
				$db_con = NULL;
			}
				
		} catch (Exception $e) {
			
			// Rollback Transaction
			if ($isTransactional) {
				$db_con->rollback();
				$db_con->autocommit(TRUE);
			}
			
			if ($db_con != NULL) {
				$db_con->close();
				$db_con = NULL;
			}
				
			throw $e;
		}
	}
	
	protected static function rand_str($len, $norepeat = true)
	{
	    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	    $max = strlen($chars) - 1;
	
	    if ($norepeat && $len > $max + 1) {
	        throw new Exception("Non repetitive random string can't be longer than charset");
	    }
	
	    $rand_chars = array();
	
	    while ($len) {
	        $picked = $chars[mt_rand(0, $max)];
	
	        if ($norepeat) {
	            if (!array_key_exists($picked, $rand_chars)) {
	                $rand_chars[$picked] = true;
	                $len--;
	            }
	        }
	        else {
	            $rand_chars[] = $picked;
	            $len--;
	        }
	    }
	
	    return implode('', $norepeat ? array_keys($rand_chars) : $rand_chars);   
	}
	
	protected static function send_reset_email($email_id, $userID, $hashed_password) {
		
		global $config;
		
		$from = "NoteIt! <no-reply@geekjamboree.com>";
		$to = $email_id;
		$subject = "Please reset your password";
		
		$url = $config['SERVER_ADDRESS'];
		$url .= "/";
		$url .= $config['APP_DIR'];
		$url .= "/forgot_password.php?arg1=";
		$url .= $userID;
		$url .= "&arg2=";
		$url .= $hashed_password;
		
		$message = sprintf("
					Hello! 
					
					We received a request for lost password for this email ID. If you
					did not initiate the request, please ignore this email.
		
					If you wish to reset your password, please use the following link 
					within the next 24 hours:
		
					%s
				
					If you're unable to click on the link above, copy and paste the URL 
					into a new browser window instead.
				
					Sincerely,
					NoteIt! Team
					www.noteit-web.com
				
				
					-- This is an automated email, please do not reply --",
					$url);
		
		$headers = "From:" . $from;
		return mail($to, $subject, $message, $headers);
	}
	
	public static function send_invitation_email($email_id, $userName) {
		
		global $config;
		
		$from = "NoteIt! <no-reply@geekjamboree.com>";
		$to = $email_id;
		$subject = $userName;
		$subject .= " invites you";
				
		$message = sprintf("
				Hello!
					
				%s would like to invite you to use Note It - The most dynamic grocery 
				list management application available for Android devices.

				http://www.noteit-web.com
		
				If you're unable to click on the link above, copy and paste the URL
				into a new browser window instead.
		
				Sincerely,
				NoteIt! Team
				www.noteit-web.com",
				$userName);
		
		$headers = "From:" . $from;
		return mail($to, $subject, $message, $headers);
	}
	
	static function is_valid_password($password) {
		
		if (strlen($password) < self::kMIN_PASSWORD_LENGTH) {
			throw new Exception("Password must be at least " . self::kMIN_PASSWORD_LENGTH . " characters in length");
		}
		return true;
	}
}

?>