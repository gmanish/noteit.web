<?php
require_once( $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/lib/NoteItCommon.php");
require_once( $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/DbBase.php");
require_once( $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/ShopListTable.php");
require_once( $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/CategoryTable.php");
require_once( $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/ShopItems.php");

// Name of users Table columns
const kTableUsers 			= 'users';
const kColUserID 			= 'userID';
const kColUserEmail 		= 'emailID';
const kColUserFirstName		= 'firstName';
const kColUserLastName		= 'lastName';

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

class NoteItDB extends DbBase
{
	protected $db_userID;
	protected $db_username;
	protected $shop_list_db;
	protected $cat_list_db;
    protected $shop_items_db;
	
	protected function __construct($userID)
	{
        parent::__construct();

		$this->db_userID = $userID;
		$this->shop_list_db = new ShopListTable($userID);
		$this->cat_list_db = new CategoryTable($userID);
		$this->shop_items_db = new ShopItems($userID);

		$sql = sprintf("SELECT * from users WHERE userID=%d", $this->db_userID);
		//echo $sql;
		$result = $this->get_db_con()->query($sql);
		if ($result != FALSE || mysqli_num_rows($result) == 1)
		{
			$row = $result->fetch_array();
			$this->db_username = $row['firstName'] . " " . $row['lastName'];
			$result->free();
		}
        else throw new Exception("Invalid user credentials: " . $this->get_db_con()->error);
 	}
	
	public function get_db_userID()
	{
		return $this->db_userID;
	}
	
	public function get_db_username()
	{
		return $this->db_username;
	}
	
	public function &get_shoplist_table()
	{
		return $this->shop_list_db;
	}
	
	public function &get_catlist_table()
	{
		return $this->cat_list_db;
	}

    public function &get_shopitems_table()
    {
        return $this->shop_items_db;
    }
    
	public static function register_user($userName, $emailID, $firstName, $lastName)
	{
		if (is_null($firstName) || is_null($lastName) || is_null($emailID))
			throw new Exception("Please fill all required fields.");
			
		if (!filter_var($emailID, FILTER_VALIDATE_EMAIL))
			throw new Exception("Please provide a valid email id");
		
		try
		{	
            $db_con = new MySQLi(kServer, kUserName, kPassword, kDatabaseName);
             if ($db_con->connect_error)
                 throw new Exception('Could not connect to Server: ' . $db_con->error);
            else
                NI::TRACE("NoteItDb::register_user: connected to db", __FILE__, __LINE__);

			// Email ID is already registered??
			$sql = sprintf(
					"SELECT %s FROM %s WHERE %s='%s'", 
					kColUserEmail, 
					kTableUsers, 
					kColUserEmail, 
					$db_con->escape_string($emailID));
			
			NI::TRACE("NoteItDb::register_user: sql = " . $sql, __FILE__, __LINE__);

			$result = $db_con->query($sql);
			if ($result ==  FALSE || mysqli_num_rows($result) > 0)
            {
                NI::TRACE("NoteItDb::register_user: " . $db_con->error, __FILE__, __LINE__);
				throw new Exception('This email ID is already registered');
            }
            
			if ($result)
				$result->free();
			
			// try of register this user
			$sql = sprintf("INSERT INTO `%s` (`%s`,`%s`,`%s`) VALUES ('%s', '%s', '%s')", 
					kTableUsers, 
					kColUserEmail, 
					kColUserFirstName, 
					kColUserLastName,
					$db_con->escape_string($emailID),
					$db_con->escape_string($firstName),
					$db_con->escape_string($lastName));
			
			$result = $db_con->query($sql);
			if ($result == FALSE)
				throw new Exception('Could not register given user: ' . mysql_error());

		}
		catch(Exception $e)
		{
			throw $e;
		}
	}
	
	public static function &login_user_id($user_id)
	{
        $noteit_db = new NoteItDB($user_id);
        return $noteit_db;
	}
	
	// Returns self on true
	public static function &login_user_email($user_email)
	{
		if (!filter_var($user_email, FILTER_VALIDATE_EMAIL))
			throw new Exception("Please provide a valid email id");

        $db_con = new MySQLi(kServer, kUserName, kPassword, kDatabaseName);
        /*
         * Use this instead of $db_con->connect_error if you need to ensure
         * compatibility with PHP versions prior to 5.2.9 and 5.3.0.
         */
         if (mysqli_connect_error())
             throw new Exception('Could not connect to Server: ' . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");

 		$sql = sprintf(
				"SELECT `userID` FROM `%s` WHERE `%s`='%s'", 
				kTableUsers, 
				kColUserEmail, 
				$db_con->escape_string($user_email));
		$result = $db_con->query($sql);
		if ($result && mysqli_num_rows($result) == 1) // There should be one and only one user by this email ID
		{
			$row = $result->fetch_array();
            $noteit_db = new NoteItDB($row[kColUserID]);
            $result->free();
			return $noteit_db;
		}
		else
		{
			if ($result)
				$result->free();
			throw new Exception("User email or password is incorrect");
		}
	}
	
	public static function logCountryInfo($ip_address)
	{
		try
		{
			$country_id = 0;
//			echo('IP: ' . $ip_address);
			$xml = simplexml_load_file('http://www.ipgp.net/api/xml/'. '122.167.174.175' .'/AZE3dafAqD'); //AZE3dafAqD = API key assigned to geekjamboree@gmail.com
//			echo('Country Code: ' . $xml->Code);
//			echo('Country: ' . $xml->Country);
			//. $xml->Ip . $xml->Country . $xml->City . $xml->Code . $xml->Country . $xml->Isp . $xml->Lat . $xml->Lng;
	        $db_con = new MySQLi(kServer, kUserName, kPassword, kDatabaseName);
			$sql = 'SELECT `countryID` FROM `countryTable` WHERE countryName="' . $db_con->escape_string($xml->Country) . '"';
			
//			echo('SQL Search: ' . $sql);
			$result = $db_con->query($sql);
			if ($result && mysqli_num_rows($result) == 1)
			{
				// The country is present in our database
				$row = $result->fetch_array();
				$country_id = $row['countryID'];
				$result->free();
//				echo('Found country in DB. ID=' . $country_id);
			}
			else
			{
				// The country is not present in our database, enter one
				$sql = sprintf('INSERT INTO `countryTable` ' . 
						'(countryName, countryCC, currency, currencyCode, ' . 
						'displayCurrencyToLeft, currencySymbol)' . 
						' VALUES ("%s", "%s", "", "", ' .
						'"1", "")', $xml->Country, $xml->Code);
//				echo('SQL INSERT: ' . $sql);
				$sql = $db_con->query($sql);
				if ($sql == false)
					throw new Exception ("Could not update country table");
				
				$country_id = $db_con->insert_id;
//				echo('Inserted country in DB. ID=' . $country_id);
			}
			
			return $country_id;
		}
		catch (Exception $e)
		{
			echo('Unknown Exception' . $e->getMessage());
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