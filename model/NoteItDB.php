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

		$sql = "SELECT * from users WHERE userID=$this->db_userID";
		//echo $sql;
		$result = $this->get_db_con()->query($sql);
		if ($result != FALSE || mysqli_num_rows($result) == 1)
		{
			$row = $result->fetch_array();
			$this->db_username = $row['firstName'] . " " . $row['lastName'];
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
			$sql = 'SELECT ' . kColUserEmail . ' FROM ' . kTableUsers . ' WHERE (' . kColUserEmail . '=\'' . $emailID . '\')';
			NI::TRACE("NoteItDb::register_user: sql = " . $sql, __FILE__, __LINE__);

			$result = $db_con->query($sql);
			if ($result ==  FALSE || mysqli_num_rows($result) > 0)
            {
                NI::TRACE("NoteItDb::register_user: " . $db_con->error, __FILE__, __LINE__);
				throw new Exception('This email ID is already registered');
            }
            else
                NI::TRACE("NoteItDb::register_user: Found user", __FILE__, __LINE__);
            
			if ($result)
			{
				mysqli_free_result($result);
			}
			
			// try of register this user
			$sql = 'INSERT INTO ' . kTableUsers . ' ' . '(' . kColUserEmail . ',' . kColUserFirstName . ',' . kColUserLastName . ') ';
			$sql .= "VALUES ('$emailID', '$firstName', '$lastName')";
			
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

 //       if ($db_con->select_db(kTableUsers) == FALSE)
 //          throw new Exception("Could not connect to database: " . kTableUsers);
        
		$sql = 'SELECT userID FROM '. kTableUsers . ' WHERE ' . kColUserEmail . '=\'' . $user_email . '\'';
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
			throw new Exception("User email or password is incorrect");
		}
	}
}