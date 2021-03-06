<?php
	
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("controllerdefines.php");
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../model/noteitdb.php");

// [TODO] : Research if there is a better way to do this
function shopitem_obj_to_array(ShopItem $shop_item_obj) {
	
    $shop_item_array = array(
        ShopItems::kColInstanceID       => $shop_item_obj->_instance_id,
        ShopItems::kColUserID           => $shop_item_obj->_user_id,
        ShopItems::kColCategoryID       => $shop_item_obj->_category_id,
        ShopItems::kColItemName         => $shop_item_obj->_item_name,
        ShopItems::kColItemID           => $shop_item_obj->_item_id,
        ShopItems::kColDateAdded        => $shop_item_obj->_date_added,
        ShopItems::kColDatePurchased    => $shop_item_obj->_date_purchased,
        ShopItems::kColListID           => $shop_item_obj->_list_id,
        ShopItems::kColUnitCost         => $shop_item_obj->_unit_cost,
        ShopItems::kColQuantity         => $shop_item_obj->_quantity,
        ShopItems::kColUnitID           => $shop_item_obj->_unit_id,
		ShopItems::kColIsPurchased		=> $shop_item_obj->_is_purchased,
		ShopItems::kColIsAskLater		=> $shop_item_obj->_is_asklater,
		ShopItems::kColBarcode			=> $shop_item_obj->_barcode,
		ShopItems::kColBarcodeFormat	=> $shop_item_obj->_barcode_format,
		ShopItems::kColVoteCount		=> $shop_item_obj->_voteCount);
    
		if (get_class($shop_item_obj) == "ShopItemAndStats") {
			
			$shop_item_array[ShopItemsPrice::kStats_Mean] 				= $shop_item_obj->mean;
			$shop_item_array[ShopItemsPrice::kStats_SampleDeviation] 	= $shop_item_obj->sampleDeviation;
		}
		
    return $shop_item_array;
}

class ListFunctorShopItems {
	public $_items = array();

    function __construct(& $items_array) {
        $this->_items = & $items_array;
    }

    function iterate_row($shop_item) {

        NI::TRACE("ListFunctorShopItems::iterate_row, Obect passed:" . print_r($shop_item, TRUE), __FILE__, __LINE__);
        $thisItem = shopitem_obj_to_array($shop_item);
        $this->_items[] = $thisItem; //append this item to the array
    }
}

class ListFunctorUnits
{
    public $_units = array();

    function __construct(& $units_array) {
        $this->_units = & $units_array;
    }

    function iterate_unit($unit) {
        $this->_units[] = $unit; //append this item to the array
    }
}

class CommandHandlerBase
{
    public static function redirect_to_view(
        $view_ID, /* One of class Views */
        $handler_exit_status = HandleHandlerExitStatus::kCommandStatus_OK,
        $message = NULL,
        $params = NULL) {
			
        global $view_map;

        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/htmlheader.tphp"));

        if ($handler_exit_status == HandlerExitStatus::kCommandStatus_Error) {
            echo "<div class=\"headerBubble headerBubbleError\">";
            echo "Error: " . $message;
            echo "</div>";
        } else if ($handler_exit_status == HandlerExitStatus::kCommandStatus_Information) {
            echo "<div class=\"headerBubble headerBubbleInfo\">";
            echo "Information: " . $message;
            echo "</div>";
        }
        else if ($handler_exit_status == HandlerExitStatus::kCommandStatus_OK && !is_null($message)) {
            echo "<div class=\"headerBubble headerBubbleOK\">";
            echo "OK: " . $message;
            echo "</div>";
        }

        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/htmlheader.tphp"));
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/noteitbodybegin.tphp"));
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/noteitheader.tphp"));
		if ($handler_exit_status != HandlerExitStatus::kCommandStatus_Error) {
        	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ($view_map[$view_ID]));
		}
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/noteitfooter.tphp"));
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/noteitbodyend.tphp"));
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . ("../view/htmlfooter.tphp"));
    }

    public function redirect_to_url(
        $url,
        $params = NULL,
        $handler_exit_status = HandlerExitStatus::kCommandStatus_OK,
        $message = NULL) {
        
		if ($params != NULL)
            header('Location: ' . $url . "?" . http_build_query($params));
        else
            header('Location: ' . $url);
    }
}

class CommandHandler extends CommandHandlerBase {

    public static function do_register() {
    	
        $first_name = isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : "";
        $last_name 	= isset($_REQUEST['last_name']) ? $_REQUEST['last_name'] : "";
        $email_ID 	= isset($_REQUEST['email_ID']) ? $_REQUEST['email_ID'] : "";
        $password	= isset($_REQUEST['password']) ? $_REQUEST['password'] : "";

        try {
            $user_id = NoteItDB::register_user("", $password, $email_ID, $first_name, $last_name);
			if ($user_id <= 0) {
				throw new Exception("Could not Register User.");
			}

            // Success in registration
            $noteit_db = NoteItDB::login_user_email(
            	$email_ID, 
            	$password, 
            	FALSE);

            // Start a session for this user and store the id in a session variable
            $_SESSION['USER_ID'] = $noteit_db->get_db_userID();
						
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
            $noteit_db = NULL;
        
		} catch(Exception $e) {
			
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_login_json() {
    	
        $email_ID 			= isset($_REQUEST['email_ID']) ? $_REQUEST['email_ID'] : "";
		$password			= isset($_REQUEST['password']) ? $_REQUEST['password'] : "";
		$is_password_hashed = isset($_REQUEST['isHashedPassword']) ? $_REQUEST['isHashedPassword'] : 0;
		
        try {
            $noteit_db = NoteItDB::login_user_email(
            	$email_ID, 
            	$password, 
            	$is_password_hashed > 0 ? TRUE : FALSE);

			// Start a session for this user and store the id in a session variable
			$_SESSION['USER_ID'] = $noteit_db->get_db_userID();

            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $noteit_db->get_db_userID(),
				Command::$arg2 => $noteit_db->get_user_pref());

            echo(json_encode($arr));
            $noteit_db = NULL;
        }
        catch(Exception $e) {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }
    
    public static function do_login() {
        try {
			CommandHandlerBase::redirect_to_view(
				Views::kView_Dashboard,
				HandlerExitStatus::kCommandStatus_OK);
        }
        catch(Exception $e) {
//        	echo($e->getMessage());
            CommandHandlerBase::redirect_to_view(
                0,
                HandlerExitStatus::kCommandStatus_Error,
                $e->getMessage());
        }
    }

	public static function do_change_password() {
		
		try {
		    $user_ID = -1;
			$old_password = isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : "";
			$new_password = isset($_REQUEST[Command::$arg2]) ? $_REQUEST[Command::$arg2] : "";

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
            }

			if ($user_ID <= 0) {
            	throw new Exception("Session Expired. Please log in again.");
            }
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
           	$noteit_db->change_password($old_password, $new_password);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
			
		} catch (Exception $e) {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
		}
			
	}
	
	public static function do_save_prefs() {
       
        try
        {
			$currencyId = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : Country::kDefault_CurrencyId;

		    $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

        	if ($user_ID <= 0 || $currencyId <= 0)
				throw new Exception("Invalid Preferences");
			
			$prefs = new UserPreference($currencyId);
            $noteit_db = NoteItDB::login_user_id($user_ID);
           	$noteit_db->save_preferences($prefs);
            $noteit_db = NULL;
			$prefs = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
        
		} catch(Exception $e) {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}
    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_delete_shop_list() {
		
        $listID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;

        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

        	if ($listID <= 0 || $user_ID <= 0)
				throw new Exception("Error Processing Request");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
           	$noteit_db->get_shoplist_table()->remove_list($listID);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
			
        } catch(Exception $e) {
			
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_add_shop_list()
    {
        $list_name 	= isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : "";

        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = intval($_SESSION['USER_ID']);
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if($user_ID <= 0)
				throw new Exception("Error Processing Request");

            $noteit_db = NoteItDB::login_user_id($user_ID);
            $new_ID = $noteit_db->get_shoplist_table()->add_list($list_name);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $new_ID,
                Command::$arg2 => $list_name);

            echo(json_encode($arr));
        }
        catch(Exception $e) {
			
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
	public static function do_edit_shop_list()
	{
        $list_ID 	= isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
		$list_name	= isset($_REQUEST[Command::$arg2]) ? $_REQUEST[Command::$arg2] : "";
		
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = intval($_SESSION['USER_ID']);
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if($list_ID <= 0 || $user_ID <= 0)
				throw new Exception("Error Processing Request");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $noteit_db->get_shoplist_table()->edit_list($list_ID, $list_name);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $list_ID,
                Command::$arg2 => $list_name);

            echo(json_encode($arr));
			
        } catch(Exception $e) {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
	public static function do_get_pending_cost() {
        $list_ID 	= isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
		
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = intval($_SESSION['USER_ID']);
            else {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if($list_ID <= 0 || $user_ID <= 0)
				throw new Exception("Error Processing Request");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $pending_cost = $noteit_db->get_shopitems_table()->get_pending_cost($list_ID);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $pending_cost);

            echo(json_encode($arr));
			
        } catch(Exception $e) {
			
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}
	
    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_add_category()
    {
        $category_name 	= isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : "";

        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($user_ID <= 0)
				throw new Exception("Error Processing Request");
				
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $new_ID = $noteit_db->get_catlist_table()->add_category($category_name);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $new_ID,
                Command::$arg2 => $category_name);

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

	public static function do_edit_category()
	{
		try
		{
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			$category_id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			$category_name = $_REQUEST[Command::$arg2];
			
			if ($user_ID <= 0 || $category_id <= 0 || $category_name == "")
				throw new Exception("Error Processing Request");
				
            $noteit_db = NoteItDB::login_user_id($user_ID);
			$category = new Category($category_id, $user_ID, $category_name);
			$noteit_db->get_catlist_table()->edit_category(Category::CATEGORY_NAME, $category);
           	$noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
		}
		catch (Exception $e)
		{
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
		}
	}

   /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
	public static function do_reorder_category() {

		$category_id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
		$old_rank = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
		$new_rank = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
		$target_id = isset($_REQUEST[Command::$arg5]) ? intval($_REQUEST[Command::$arg5]) : 0;
		
		try {
           $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg4]) ? intval($_REQUEST[Command::$arg4]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($category_id <= 0 || $user_ID <= 0 || $old_rank < 0 || $new_rank < 0 || $target_id <= 0)
				throw new Exception("Error Processing Request");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $noteit_db->get_catlist_table()->reorder_category($category_id, $target_id, $old_rank, $new_rank);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
		} catch (Exception $e){
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
		}			
	}
	
   /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_delete_category()
    {
//            var_dump($_REQUEST);
        $listID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;

        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($listID <= 0 || $user_ID <= 0)
				throw new Exception("Error Processing Request (");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $noteit_db->get_catlist_table()->remove_category($listID);
            $noteit_db = NULL;

            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_list_shop_items()
    {
        $show_purchased_items = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 1;
        $list_id = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0; //Show All default
        $start_at = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
		$move_purchased_to_bottom = isset($_REQUEST[Command::$arg5]) ? intval($_REQUEST[Command::$arg5]) : 1;
		$num_rows_fetch = isset($_REQUEST[Command::$arg6]) ? intval($_REQUEST[Command::$arg6]) : 20;
		
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg4]) ? intval($_REQUEST[Command::$arg4]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($list_id <= 0 || $user_ID <= 0 || $start_at < 0)
				throw new Exception("Error Processing Request");
			
            $items_array = array();
            $shop_item_functor = new ListFunctorShopItems($items_array);
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $noteit_db->get_shopitems_table()->list_range(
                $show_purchased_items,
                $move_purchased_to_bottom,
                $list_id,
                $start_at,
                $num_rows_fetch,
                $shop_item_functor,
                'iterate_row');

            $noteit_db = NULL;

            NI::TRACE(print_r($items_array, TRUE), __FILE__, __LINE__);
            
            // Form a JSON string
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $shop_item_functor->_items);

            NI::TRACE('CommandHandler::do_list_shop_items returning array:' . print_r($shop_item_functor->_items, TRUE), __FILE__, __LINE__);

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

	public static function do_get_instances() {
        	
        $class_ID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
		$num_instances = isset($_REQUEST[Command::$arg2]) ? min(intval($_REQUEST[COmmand::$arg2]), 5) : 1;
		
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($user_ID <= 0 || $class_ID <= 0 || $num_instances <= 0)
				throw new Exception("Error Processing Request.");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
           	$items = array();
            $shop_item_functor = new ListFunctorShopItems($items);
            $noteit_db->get_shopitems_table()->list_instances(
            	$class_ID, 
            	$num_instances, 
            	$shop_item_functor, 
            	'iterate_row');
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $items);

            $json_str = json_encode($arr);
            $noteit_db = NULL;
            echo($json_str);
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}

	public static function do_search_barcode() {
		
		$barcode 		= isset($_REQUEST['arg1']) ? $_REQUEST['arg1'] : "";
		$barcodeFormat 	= isset($_REQUEST['arg2']) ? intval($_REQUEST['arg2']) : BarcodeFormat::BARCODE_FORMAT_UNKNOWN;
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID'])) {
                $user_ID = $_SESSION['USER_ID'];
			}
            else {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($user_ID <= 0 || $barcode == "") {
				throw new Exception("Error Processing Request.");
			}
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $item = $noteit_db->get_shopitems_table()->searchitem_barcode($barcode, $barcodeFormat);
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $item != NULL ? shopitem_obj_to_array($item) : "",
				Command::$arg2 => $item != NULL ? 1 : 0);

            $json_str = json_encode($arr);
            $noteit_db = NULL;
            echo($json_str);
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_get_shop_item()
    {
        $item_ID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;

        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($user_ID <= 0 || $item_ID <= 0)
				throw new Exception("Error Processing Request");
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $shop_item = $noteit_db->get_shopitems_table()->get_item($item_ID);
            $noteit_db = NULL;

            NI::TRACE(print_r($shop_item, TRUE), __FILE__, __LINE__);

            // Form a JSON string
            // [TODO] : Cleanup this messy copy into an array
            $item_array = array();
            $item_array[] = shopitem_obj_to_array($shop_item);
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $item_array);

            NI::TRACE('CommandHandler::do_get_shop_item returning:' . print_r($item_array, TRUE), __FILE__, __LINE__);

            $json_str = json_encode($arr);

            echo($json_str);
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }
    
    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_add_item()
    {
        $list_ID 			= isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 1;
        $category_ID 		= isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 1;
        $item_name 			= isset($_REQUEST[Command::$arg3]) ? $_REQUEST[Command::$arg3] : "";
        $item_quantity 		= isset($_REQUEST[Command::$arg4]) ? floatval($_REQUEST[Command::$arg4]) : 1.00;
        $item_unit_cost 	= isset($_REQUEST[Command::$arg5]) ? floatval($_REQUEST[Command::$arg5]) : 0.00;
        $item_unit_id 		= isset($_REQUEST[Command::$arg6]) ? intval($_REQUEST[Command::$arg6]) : 1;
		$item_ispurchased 	= isset($_REQUEST[Command::$arg8]) ? intval($_REQUEST[Command::$arg8]) : 0; // 0 or 1
		$item_isasklater 	= isset($_REQUEST[Command::$arg9]) ? intval($_REQUEST[Command::$arg9]) : 0; // 0 or 1
		$item_barcode 		= isset($_REQUEST[Command::$arg10]) ? $_REQUEST[Command::$arg10] : "";
		$item_barcodeformat	= isset($_REQUEST[Command::$arg11]) ? $_REQUEST[Command::$arg11] : BarcodeFormat::BARCODE_FORMAT_UNKNOWN;
		
       try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = intval($_SESSION['USER_ID']);
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg7]) ? intval($_REQUEST[Command::$arg7]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }
			
			if ($user_ID <= 0 		|| 
				$item_name == "" 	|| 
				$list_ID <= 0 		|| 
				$item_unit_id <= 0)
				throw new Exception("Error Processing Request");

            if ($item_name != "")
            {
                $noteit_db = NoteItDB::login_user_id($user_ID);
                $new_item = $noteit_db->get_shopitems_table()->add_item(
                    $list_ID,
                    $category_ID,
                    $item_name,
                    $item_unit_cost,
                    $item_quantity,
                    $item_unit_id,
                    $item_isasklater,
                    $item_barcode,
					$item_barcodeformat);
					
	            $noteit_db = NULL;

				$item_array = array();
				$item_array[] = shopitem_obj_to_array($new_item);

                $arr = array(
                    JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                    JSONCodes::kRetMessage => "",
                    Command::$arg1 => $item_array);

                echo(json_encode($arr));
            }
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

	/*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
	public static function do_edit_shop_item()
	{
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = intval($_SESSION['USER_ID']);
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg8]) ? intval($_REQUEST[Command::$arg8]) : 0;
                if ($user_ID <= 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			$edit_flags = 0;
			$item_id = 0;
			if (isset ($_REQUEST[Command::$arg1]))
			{
				$item_id = intval($_REQUEST[Command::$arg1]);
			}
			else 
				throw new Exception ("Invalid Item Index");

			$item = new ShopItem($item_id);
			
			// List ID
			if (isset ($_REQUEST[Command::$arg2]))
			{
				$item->_list_id = intval($_REQUEST[Command::$arg2]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_LISTID;
			}
			
			// Category ID
			if (isset ($_REQUEST[Command::$arg3]))
			{
				$item->_category_id = intval($_REQUEST[Command::$arg3]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_CATEGORYID;
			}
			
			// Item Name
			if (isset ($_REQUEST[Command::$arg4]))
			{
				$item->_item_name = $_REQUEST[Command::$arg4];
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_ITEMNAME;
			}
			
			// Item Quantity
			if (isset ($_REQUEST[Command::$arg5]))
			{
				$item->_quantity = floatval($_REQUEST[Command::$arg5]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_QUANTITY;
			}
			
			// Item Unit Cost
			if (isset ($_REQUEST[Command::$arg6]))
			{
				$item->_unit_cost = floatval($_REQUEST[Command::$arg6]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_UNITCOST;
			}
			
			// Ask Later
			if (isset($_REQUEST[Command::$arg10]))
			{
				$item->_is_asklater = intval($_REQUEST[Command::$arg10]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_ISASKLATER;
			}
			
			// Item Unit ID
			if (isset ($_REQUEST[Command::$arg7]))
			{
				$item->_unit_id = intval($_REQUEST[Command::$arg7]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_UNITID;
			}
			
			// Is Purchased?
			if (isset ($_REQUEST[Command::$arg9]))
			{
				$item->_is_purchased = intval($_REQUEST[Command::$arg9]);
				$edit_flags = $edit_flags | ShopItem::SHOPITEM_ISPURCHASED;
				
				if ($item->_is_purchased == TRUE) {
					$edit_flags = $edit_flags | SHOPItem::SHOPITEM_DATEPURCHASED;
					if (isset($_REQUEST[Command::$arg11]))
						$item->_date_purchased = new DateTime($_REQUEST[Command::$arg11]);
					else
						$item->_date_purchased = new DateTime();
				}
			}
			
			
			if ($edit_flags == 0)
				throw new Exception ("Nothing to Edit");
			
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$edited_item = $noteit_db->get_shopitems_table()->edit_item(
					$item->_instance_id, 
					$item, 
					$edit_flags);
            $noteit_db = NULL;
            
			$item_array = array();
			$item_array[] = shopitem_obj_to_array($edited_item);

            $arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                    JSONCodes::kRetMessage => "",
                    Command::$arg1 => $item_array);
            
			echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}
	
	/*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_delete_item()
    {
        $instance_id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 1;

        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($instance_id <= 0 || $user_ID <= 0)
				throw new Exception("Error Processing Request");
				
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $new_ID = $noteit_db->get_shopitems_table()->delete_item($instance_id);
            $noteit_db = NULL;

            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
	public static function do_copy_item()
	{
        $instance_id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
        $target_list = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
		
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }

			if ($instance_id <= 0 || $user_ID <= 0 || $target_list <= 0)
				throw new Exception("Error Processing Request");
				
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $new_ID = $noteit_db->get_shopitems_table()->copy_item($instance_id, $target_list);
            $noteit_db = NULL;

            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
	}

	public static function do_mark_all_done() {
		try {
			$list_Id = isset($_REQUEST['arg1']) ? intval($_REQUEST['arg1']) : 0;
			$done = isset($_REQUEST['arg2']) ? intval($_REQUEST['arg2']) : 1;
			$done = $done > 0 ? 1 : 0; // Clamp to 0 or 1
			$date_purchased = isset($_REQUEST[Command::$arg4]) ? new DateTime($_REQUEST[Command::$arg4]) : new DateTime();
			$user_Id = -1;
			
			if (isset($_SESSION['USER_ID']))
				$user_Id = $_SESSION['USER_ID'];
			else {
				$user_Id = isset($_REQUEST['arg3']) ? intval($_REQUEST['arg3']): 0;
				if ($user_Id == 0) {
					throw new Exception("Session Expired. Please log in again.");
				}
			}
			
			if ($user_Id <= 0 || $list_Id <=0)
				throw new Exception("Error Processing Request.", 1);
			
			$noteit_db = NoteItDB::login_user_id($user_Id);
			if ($noteit_db != NULL) {
				$noteit_db->get_shopitems_table()->mark_all_done($list_Id, $done, $date_purchased);
				$noteit_db = NULL;
			}
			
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "");

            echo(json_encode($arr));
		} catch (Exception $e) {
            $arr = array(
                JSONCodes::kRetVal => -1,
                JSONCodes::kRetMessage => "");
            echo(json_encode($arr));
		}
	}
	
    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_get_shop_list()
    {
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
                if ($user_ID == 0) {
                    throw new Exception("Session Expired. Please log in again.");
                }
            }
            
			if ($user_ID <= 0)
				throw new Exception("Error Processing Request");
			
			$fetch_count = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $shopList = array();
            $shoplist_functor = new ListFunctorShopList($shopList);
            $new_ID = $noteit_db->get_shoplist_table()->list_all($fetch_count, $shoplist_functor, "iterate_row");
            $noteit_db = NULL;

            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $shoplist_functor->_shoplist);

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }


    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_get_categories()
    {
        try
        {
            $user_ID = -1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
            }

            if ($user_ID <= 0) {
                throw new Exception("Session Expired. Please log in again.");
            }
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $categories = array();
            $categories_functor = new ListFunctorCategoryList($categories);
            $new_ID = $noteit_db->get_catlist_table()->list_all(TRUE, $categories_functor, "iterate_row");
            $noteit_db = NULL;

            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                JSONCodes::kRetMessage => "",
                Command::$arg1 => $categories_functor->_categoryList);

            echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                JSONCodes::kRetMessage => $e->getMessage());

            echo(json_encode($arr));
        }
    }

    /*  This function is called asynchronously. It's important to note that all output
        from this function should be JSON encoded. No returning HTML headers and Tags.
    */
    public static function do_get_category()
    {
        try
        {
            $user_ID = -1;
            $category_ID = 0;
            $category_ID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 1;

            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
            }

            if ($user_ID <= 0 || $category_ID <= 0) {
                throw new Exception("Session Expired. Please log in again.");
            }

            $noteit_db = NoteItDB::login_user_id($user_ID);
            $category = array();
            $category = $noteit_db->get_catlist_table()->get_category($category_ID);
            $noteit_db = NULL;
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $category);

             echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
        }
    }

	public static function do_suggest_items()
	{
       try
        {
            $user_ID = -1;
            $substring = isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : "";
			$max_items = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 10;
			
            if (isset($_SESSION['USER_ID']))
                $user_ID = $_SESSION['USER_ID'];
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
            }

            if ($user_ID <= 0) {
                throw new Exception("Session Expired. Please log in again.");
            }
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
            $suggestions = $noteit_db->get_shopitems_table()->suggest_item($substring, $max_items);
         	$noteit_db = NULL;
        	$arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $suggestions);

             echo(json_encode($arr));
        }
        catch(Exception $e)
        {
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
        }
	}

	public static function do_get_units()
	{
		try
		{
			$user_ID = -1;
			
            if (isset($_SESSION['USER_ID']))
			{
                $user_ID = $_SESSION['USER_ID'];
			}
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
            }
			
			$unit_type = isset($_REQUEST['arg1']) ? intval($_REQUEST['arg1']) : 0;
			
            if ($user_ID <= 0 || $unit_type <= 0) 
            {
                throw new Exception("Session Expired. Please log in again.");
            }
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
			$units = array();
			$functor = new ListFunctorUnits($units);
			$noteit_db->list_units($unit_type, $functor, 'iterate_unit');
            $noteit_db = NULL;
			
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $units);
			
			echo(json_encode($arr));
		}
		catch (exception $e)
		{
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}

	public static function do_get_countries() {
		
		$ipAddress = isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : $_SERVER['REMOTE_ADDR'];  
			
		try {
			$nativeCountry = NoteItDB::list_country($ipAddress);
			$countries = NoteItDB::list_countries();
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $ipAddress,
                 Command::$arg2 => $nativeCountry,
                 Command::$arg3 => $countries);
			
			echo(json_encode($arr));
		}
		catch (exception $e)
		{
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}
	
	public static function do_get_currencies() {
		
		$ipAddress = isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : $_SERVER['REMOTE_ADDR'];  
			
		try {
			$nativeCountry = NoteItDB::list_country($ipAddress);
			$nativeCurrency = NoteItDB::get_currency_for_country($nativeCountry->countryCode);
			$currencies = NoteItDB::list_currencies();
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $ipAddress,
                 Command::$arg2 => $nativeCurrency,
                 Command::$arg3 => $currencies);
			
			echo(json_encode($arr));
		}
		catch (exception $e)
		{
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}

	public static function do_item_report() {
		try
		{
			$user_ID = -1;
			
            if (isset($_SESSION['USER_ID']))
			{
                $user_ID = $_SESSION['USER_ID'];
			}
            else
            {
                $user_ID = isset($_REQUEST[Command::$arg4]) ? intval($_REQUEST[Command::$arg4]) : 0;
            }
			
			// Should be in YYYY-MM-DD format
			$date_from = isset($_REQUEST[Command::$arg1]) ? new DateTime($_REQUEST[Command::$arg1]) : NULL;
			$date_to = isset($_REQUEST[Command::$arg2]) ? new DateTime($_REQUEST[Command::$arg2]) : NULL;
			$is_purchased = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 1;
            if ($user_ID <= 0 || (empty($date_from) && empty($date_to))) {
                throw new Exception("Error Processing Request");
            }
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
			$report = $noteit_db->get_reports()->per_item($is_purchased, $date_from, $date_to);
            $noteit_db = NULL;
			
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $report);
			
			echo(json_encode($arr));
		}
		catch (exception $e)
		{
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}
	
	public static function do_category_report() {
		
		try
		{
			$user_ID = -1;
			
            if (isset($_SESSION['USER_ID'])) {
                $user_ID = $_SESSION['USER_ID'];
			} else {
                $user_ID = isset($_REQUEST[Command::$arg4]) ? intval($_REQUEST[Command::$arg4]) : 0;
            }
			
			// Should be in YYYY-MM-DD format
			$date_from = isset($_REQUEST[Command::$arg1]) ? new DateTime($_REQUEST[Command::$arg1]) : NULL;
			$date_to = isset($_REQUEST[Command::$arg2]) ? new DateTime($_REQUEST[Command::$arg2]) : NULL;
			$is_purchased = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 1;
            if ($user_ID <= 0 || (empty($date_from) && empty($date_to))) {
                throw new Exception("Error Processing Request");
            }
			
            $noteit_db = NoteItDB::login_user_id($user_ID);
			$report = $noteit_db->get_reports()->per_category($is_purchased, $date_from, $date_to);
            $noteit_db = NULL;
			
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
                 JSONCodes::kRetMessage => "",
                 Command::$arg1 => $report);
			
			echo(json_encode($arr));
		}
		catch (exception $e)
		{
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}

	public static function do_like_item() {
		try {
			$user_ID = -1;
			
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
			    $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			}
			
			$itemId = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			if ($user_ID <= 0 || $itemId <= 0) {
				throw new Exception("Error Processing Request.");
			}
			
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$likeCount = $noteit_db->get_metadata_table()->do_like_item($itemId);
			$noteit_db = NULL;
			
			$arr = array(
				JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
				JSONCodes::kRetMessage => "",
				ShopItems::kColVoteCount => $likeCount);
				
			echo(json_encode($arr));

		} catch (exception $e) {
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}

	public static function do_dislike_item() {
		try {
			$user_ID = -1;
			
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
			    $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			}
			
			$itemId = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			if ($user_ID <= 0 || $itemId <= 0) {
				throw new Exception("Error Processing Request.");
			}
			
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$likeCount = $noteit_db->get_metadata_table()->do_dislike_item($itemId);
			$noteit_db = NULL;
			
			$arr = array(
				JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
				JSONCodes::kRetMessage => "",
				ShopItems::kColVoteCount => $likeCount);
				
			echo(json_encode($arr));

		} catch (exception $e) {
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}
	
	public static function do_get_item_price() {
		try {
			$user_ID = -1;
			
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
			    $user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			}
			
			$itemId = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			if ($user_ID <= 0 || $itemId <= 0) {
				throw new Exception("Error Processing Request.");
			}

			$noteit_db = NoteItDB::login_user_id($user_ID);
			$price = $noteit_db->get_shopitems_table()->get_item_price($itemId);
			$noteit_db = NULL;
			
			$arr = array(
				JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_OK,
				JSONCodes::kRetMessage => "",
				Command::$arg1 => $price);
				
			echo(json_encode($arr));

		} catch (exception $e) {
            $arr = array(
                 JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
                 JSONCodes::kRetMessage => $e->getMessage());

             echo(json_encode($arr));
		}
	}
	
	public static function do_calculate_stats() {
		try {
			$user_Id = -1;
			
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
				$user_ID = isset($_REQUEST[Command::$arg4]) ? intval($_REQUEST[Command::$arg4]) : 0;
			}
				
			$classId 	= isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			$currencyId = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			$unitId 	= isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
			
// 			echo "Hello! :" . $classId . ":" . $currencyId . ":" . $unitId . ":" . $user_ID;
			if ($classId <= 0 || $currencyId <= 0 || $unitId <= 0 || $user_ID <= 0) {
				throw new Exception("Error Processing Request.");
			}
			
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$stats = $noteit_db->get_shopitems_table()->calculate_stats($classId, $currencyId, $unitId);
			$noteit_db = NULL;
				
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "",
					Command::$arg1			=> ($stats != NULL ? $stats->mean() : 0),
					Command::$arg2			=> ($stats != NULL ? $stats->sample_deviation() : 0));
			
			echo(json_encode($arr));
					
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
			
			echo(json_encode($arr));
		}
	}
	
	public static function do_get_msg_headers() {
		
		try {
			$user_Id = -1;
				
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
				$user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			}
		
			$ignore_read = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 1;
				
			if ($user_ID <= 0) {
				throw new Exception("Error Processing Request.");
			}
				
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$headers = $noteit_db->get_userinbox_table()->get_message_headers($ignore_read);
			$noteit_db = NULL;
		
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "",
					Command::$arg1			=> $headers);
				
			echo(json_encode($arr));
				
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
				
			echo(json_encode($arr));
		}
	}
	
	public static function do_get_msg() {

		try {
			$user_Id = -1;
		
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
				$user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			}
		
			$message_Id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
		
			if ($user_ID <= 0 || $message_Id <= 0) {
				throw new Exception("Error Processing Request.");
			}
		
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$message = $noteit_db->get_userinbox_table()->get_message($message_Id);
			$noteit_db = NULL;
		
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "",
					Command::$arg1			=> $message);
		
			echo(json_encode($arr));
		
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
		
			echo(json_encode($arr));
		}
	}
	
	public static function do_mark_msg_read() {
		
		try {
			$user_Id = -1;
		
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
				$user_ID = isset($_REQUEST[Command::$arg2]) ? intval($_REQUEST[Command::$arg2]) : 0;
			}
		
			$message_Id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
		
			if ($user_ID <= 0 || $message_Id <= 0) {
				throw new Exception("Error Processing Request.");
			}
		
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$message = $noteit_db->get_userinbox_table()->mark_read($message_Id);
			$noteit_db = NULL;
		
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "");
		
			echo(json_encode($arr));
		
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
		
			echo(json_encode($arr));
		}
	}
	
	public static function do_share_shop_list() {
		
		try {
			$user_Id = -1;
		
			if (isset($_SESSION['USER_ID'])) {
				$user_ID = $_SESSION['USER_ID'];
			} else {
				$user_ID = isset($_REQUEST[Command::$arg3]) ? intval($_REQUEST[Command::$arg3]) : 0;
			}
		
			$list_Id = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			$email_Id = isset($_REQUEST[Command::$arg2]) ? $_REQUEST[Command::$arg2] : "";
		
			if ($user_ID <= 0 || $email_Id == '' || $list_Id <= 0) {
				throw new Exception("Error Processing Request.");
			}
		
			$noteit_db = NoteItDB::login_user_id($user_ID);
			$message = $noteit_db->get_shoplist_table()->share_list($list_Id, $email_Id);
			$noteit_db = NULL;
		
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "");
		
			echo(json_encode($arr));
		
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
		
			echo(json_encode($arr));
		}
	}
	
	public static function do_forgot_password() {
		
		try {
			$email_Id = isset($_REQUEST[Command::$arg1]) ? $_REQUEST[Command::$arg1] : "";
		
			if ($email_Id == '') {
				throw new Exception("Error Processing Request.");
			}
		
			NoteItDB::do_forgot_password($email_Id);
			$noteit_db = NULL;
		
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "A mail has been sent to your email ID.");
		
			echo(json_encode($arr));
		
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
		
			echo(json_encode($arr));
		}
	}
	
	public static function do_reset_password() {
		
		try {
			$userID = isset($_REQUEST[Command::$arg1]) ? intval($_REQUEST[Command::$arg1]) : 0;
			$token = isset($_REQUEST[Command::$arg2]) ? $_REQUEST[Command::$arg2] : "";
			$new_password = isset($_REQUEST[Command::$arg3]) ? $_REQUEST[Command::$arg3] : "";
			
			if ($userID <= 0 || $token == '') {
				throw new Exception("Error Processing Request.");
			}
		
			NoteItDB::do_reset_password($userID, $token, $new_password);
			$noteit_db = NULL;
		
			$arr = array(
					JSONCodes::kRetVal 		=> HandlerExitStatus::kCommandStatus_OK,
					JSONCodes::kRetMessage	=> "");
		
			echo(json_encode($arr));
		
		} catch (exception $e) {
			$arr = array(
					JSONCodes::kRetVal => HandlerExitStatus::kCommandStatus_Error,
					JSONCodes::kRetMessage => $e->getMessage());
		
			echo(json_encode($arr));
		}
	}
} // class CommandHandler
	
	
?>