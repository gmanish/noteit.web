<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../lib/noteitcommon.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "tablebase.php";

if(class_exists('ListFunctorCategoryList') != TRUE)
{
		
	class ListFunctorCategoryList {
	    public $_categoryList;
	
	    function __construct(& $category_list)  {
	        $this->_categoryList = & $category_list;
	    }
	    
	
		public function iterate_row($category_ID, $category_Name, $user_ID, $rank) {
	        $this->_categoryList[] = array(
	            ShopListTable::kCol_ListID => $category_ID,
	            ShopListTable::kCol_ListName => $category_Name,
	            ShopListTable::kCol_UserID => $user_ID,
				CategoryTable::kCol_CategoryRank => $rank);
		}
	}
}

if(class_exists('Category') != TRUE)
{
	class Category {
	    const CATEGORY_NAME = 1;    // 1 << 0
	
	    public $categoryID = 0;
	    public $userID_FK = 0;
	    public $_categoryName;
		public $rank = 0;
	
	    public function __construct(
	    	$id, 
	    	$user_ID = 0,
			$name = '',
			$rank = 0) {
	        $this->categoryID = $id;
	        $this->_categoryName = $name;
	        $this->userID_FK = $user_ID;
			$this->rank = $rank;
	    }
	}
}

if(class_exists('CategoryTable') != TRUE)
{
	class CategoryTable extends TableBase {
			
		const kTableName 		= 'shopitemcategories';
	    const kCol_CategoryID 	= 'categoryID';
	    const kCol_CategoryName = 'categoryName';
	    const kCol_UserID 		= 'userID_FK';
		const kCol_CategoryRank = 'categoryRank';
		
		function __construct($db_base, $user_ID) {
			parent::__construct($db_base, $user_ID);
		}
		
		function list_all($current_user_only, &$functor_obj, $function_name='iterate_row') {
			if ($current_user_only == TRUE)
				$sql = sprintf(
						"SELECT * FROM `%s` WHERE `userID_FK`=%d ORDER BY `categoryRank`", 
						self::kTableName, 
						parent::GetUserID());
			else
				$sql = sprintf(
						"SELECT * FROM `%s` ORDER BY `categoryName`", 
						self::kTableName);
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE)
				throw new Exception("SQL exec failed (". __FILE__ . __LINE__ . "): $this->get_db_con()->error");
	
			while ($row = mysqli_fetch_array($result))
			{
	            NI::TRACE($row, __FILE__, __LINE__);
				call_user_func(
					array($functor_obj, $function_name), // invoke the callback function
					$row[0], // 'categoryID 
					$row[1], // 'categoryName
					$row[2], // 'userID_FK
					$row[3]);// 'categoryRank 
			}
			
			if ($result)
				$result->free();
		}
	
	    function add_category($category_name) {
			if (1) {
				
				$sql = sprintf("INSERT INTO `%s` (`%s`, `%s`, `%s`) 
								SELECT '%s', %d, (MAX(`%s`) + 1) AS rank
								FROM `%s`
								WHERE `%s`=%d", 
					self::kTableName,
					self::kCol_CategoryName,
					self::kCol_UserID,
					self::kCol_CategoryRank,
					$this->get_db_con()->escape_string($category_name),
					parent::GetUserID(),
					self::kCol_CategoryRank,
					self::kTableName,
					self::kCol_UserID,
					parent::GetUserID());
		
				NI::TRACE_ALWAYS($sql, __FILE__, __LINE__);
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE && $this->get_db_con()->errno == 1062)
					throw new Exception(
						"This Category already exists (" .
						$this->get_db_con()->errno . ")");
				else if ($result == FALSE)
					throw new Exception(
						"An error occurred. The Category could not be added (" . 
						$this->get_db_con()->errno . ")");
										
				return $this->get_db_con()->insert_id;
			
			} else {
					    	
		        $sql = sprintf(
						"SELECT add_category('%s', %d)", 
						$this->get_db_con()->escape_string($category_name), 
						parent::GetUserID());
		
		        NI::TRACE($sql, __FILE__,  __LINE__);
		        $result = $this->get_db_con()->query($sql);
		        if ($result == FALSE || mysqli_num_rows($result) <= 0)
			    	throw new Exception(
			    		"An error occurred. The category could not be added (" . 
			    		$this->get_db_con()->errno . ")");
					
		       	$row = $result->fetch_row();
				$result->free();
				return $row[0];
			}
	    }
		
		function edit_category($bitMask, $category) {
			$sql = sprintf("UPDATE `%s` SET ", self::kTableName);
			$prev_col_added = FALSE;
			if ($bitMask & Category::CATEGORY_NAME)
			{
				$sql .= self::kCol_CategoryName . "='" . $category->_categoryName . "'";
				$prev_col_added = TRUE; 
			}
			
			if (!$prev_col_added )
				throw new Exception("Nothing to Edit (" . __FILE__ . __LINE__ . ")");
			
			$sql .= " WHERE `" . self::kCol_CategoryID . "`=" . $category->categoryID;
			$sql .= " AND `" . self::kCol_UserID . "`=" . $category->userID_FK;
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE)
				throw new Exception("SQL exec failed (" . __FILE__ . __LINE__ . ")" . $this->get_db_con()->error);			
		}
	
	    function remove_category($category_ID) {
	    	if (1) {
				$sql = sprintf("UPDATE `shopitemscatalog` 
					SET categoryID_FK=1 
					WHERE `userID_FK`=%d AND `categoryID_FK`=%d",
					parent::GetUserID(),
					$category_ID);
				
//				NI::TRACE_ALWAYS($sql, __FILE__, __LINE__);
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE)
					throw new Exception("Could not delete Category");

				$sql = sprintf("UPDATE `shopitems` 
					SET categoryID_FK=1 
					WHERE `userID_FK`=%d AND `categoryID_FK`=%d",
					parent::GetUserID(),
					$category_ID);
				
//				NI::TRACE_ALWAYS($sql, __FILE__, __LINE__);
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE)
					throw new Exception("Could not delete Category");
				
				$sql = sprintf("DELETE `%s` FROM `%s` WHERE `%s`=%d AND `%s`=%d",
					self::kTableName,
					self::kTableName,
					self::kCol_CategoryID,
					$category_ID,
					self::kCol_UserID,
					parent::GetUserID());

//				NI::TRACE_ALWAYS($sql, __FILE__, __LINE__);
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE)
					throw new Exception("Could not delete Category");
			} else {
		        $sql = sprintf(
					"call delete_category(%d, %d)",
					$category_ID,
					$this->GetUserID());
		
				$result = $this->get_db_con()->query($sql);
		        if ($result == FALSE)
		            throw new Exception("Could not delete Category");
			}
	    }
	
	    function get_category($category_ID) {
	    	
	        $sql = sprintf("SELECT * FROM `shopitemcategories` WHERE `categoryID`=%d LIMIT 1", $category_ID);
	        $result = $this->get_db_con()->query($sql);
	        if ($result == FALSE || mysqli_num_rows($result) <= 0)
	            throw new Exception("Database operation failed (" . __FILE__ . __LINE__ . ")" . $this->get_db_con()->error);
	
	        $row = mysqli_fetch_array($result);
	        $category = new Category($row[0], $row[1], $row[2], $row[3]);
			$result->free();
	        return $category;
	    }
		
		function reorder_category($category_ID, $old_rank, $new_rank) {
			
			$sql = "";
			if ($old_rank < $new_rank) {
				$sql = sprintf("UPDATE `shopitemcategories`
								JOIN (
					  				SELECT categoryID, (`categoryRank` - 1) as new_rank
		  							FROM shopitemcategories
		  							WHERE `categoryRank` between %d + 1 AND %d
		  							UNION ALL
		  							SELECT %d as categoryID, %d as new_rank
								) as r
								USING (categoryID)
								SET `categoryRank` = new_rank", 
								$old_rank,
								$new_rank,
								$category_ID,
								$new_rank);
			} else if ($old_rank > $new_rank) {
				$sql = sprintf("UPDATE `shopitemcategories` 
								JOIN ( 
									SELECT categoryID, (`categoryRank` + 1) as new_rank 
									FROM shopitemcategories 
									WHERE `categoryRank` between %d AND %d - 1 
									UNION ALL 
									SELECT %d as categoryID, %d as new_rank 
								) as r USING (categoryID) 
								SET `categoryRank` = new_rank",
								$new_rank,
								$old_rank,
								$category_ID, 
								$new_rank);
			}
			
			//NI::TRACE_ALWAYS($sql, __FILE__, __LINE__);
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE)
				throw new Exception("Database operation failed :" . $this->get_db_con()->error . $this->get_db_con()->errno);
		}
	}
}
?>
