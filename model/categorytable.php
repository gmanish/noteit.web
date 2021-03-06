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

if(class_exists('CategoryTable') != TRUE) {
		
	class CategoryTable extends TableBase {
		
		const kUSE_STORED_PROC 	= FALSE;
			
		const kTableName 		= 'shopitemcategories';
	    const kCol_CategoryID 	= 'categoryID';
	    const kCol_CategoryName = 'categoryName';
	    const kCol_UserID 		= 'userID_FK';
		const kCol_CategoryRank = 'categoryRank';
		
		const kSystemCategory	= 1;
		
		function __construct($db_base, $user_ID) {
			parent::__construct($db_base, $user_ID);
		}
		
		protected function is_owned($categoryID) {
			
			$category = $this->get_category($categoryID);
			return ($category->userID_FK == self::GetUserID() && 
					$category->userID_FK != self::kSystemCategory);
		}
		
		function list_all(
			$current_user_only, 
			&$functor_obj, 
			$function_name='iterate_row') {
				
			if ($current_user_only == TRUE) {
				// Return all categories associated with items in lists shared with this user
				// Also return the categories created by this user
				// And return the system categories as well
				$sql = sprintf(
					"SELECT DISTINCT
						sic.categoryID, 
						sic.categoryName, 
						sic.userID_FK, 
						sic.categoryRank 
					FROM 
						shopitemcategories sic
					LEFT JOIN 
						shopitems si
					ON 
						sic.`categoryID`=si.`categoryID_FK`
					WHERE 
						`listID_FK` 
					IN (
						SELECT DISTINCT 
							`list_id_FK`
						FROM 
							shoplists_sharing
						WHERE 
							user_id_fk=%d
						) 
					OR 
						sic.`userID_FK`=%d OR 
						sic.userID_FK=%d
					ORDER BY (
						CASE WHEN 
							sic.userID_FK=%d 
						THEN 
							1
						ELSE 
							2
						END), 
						`categoryRank`", 
					parent::GetUserID(),
					parent::GetUserID(),
					self::kSystemCategory,
					parent::GetUserID());
			}
			else {
				throw new Exception("Not Implemented.");
				$sql = sprintf(
					"SELECT * FROM `%s` ORDER BY `categoryName`", 
					self::kTableName);
			}
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception("SQL exec failed (". $this->get_db_con()->errno . ")");
			}
	
			while ($row = mysqli_fetch_array($result)) {
	            	
				call_user_func(
					array($functor_obj, $function_name), // invoke the callback function
					$row[0], 	// 'categoryID 
					$row[1], 	// 'categoryName
					$row[2], 	// 'userID_FK
					$row[3]);	// 'categoryRank 
			}
			
			if ($result) {
				$result->free();
			}
		}
	
	    function add_category($category_name) {
				
			if (!self::kUSE_STORED_PROC) {
				
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
		
				//NI::TRACE_ALWAYS($sql, __FILE__, __LINE__);
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE && $this->get_db_con()->errno == 1062) {
					throw new Exception(
						"This Category already exists (" .
						$this->get_db_con()->errno . ")");
				}
				else if ($result == FALSE) {
					throw new Exception(
						"An error occurred. The Category could not be added (" . 
						$this->get_db_con()->errno . ")");
				}
										
				return $this->get_db_con()->insert_id;
			
			} else {	// self::kUSE_STORED_PROC
					    	
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
		
		function edit_category($bitMask, Category $category) {
			
			if (self::is_owned($category->categoryID)) {
				
				$prev_col_added = FALSE;
				$sql = sprintf("UPDATE `%s` SET ", self::kTableName);
	
				if ($bitMask & Category::CATEGORY_NAME) {
					$sql .= self::kCol_CategoryName . "='" . $category->_categoryName . "'";
					$prev_col_added = TRUE; 
				}
				
				if (!$prev_col_added ) {
					throw new Exception("Nothing to Edit (" . $this->get_db_con()->errno . ")");
				}
				
				$sql .= " WHERE `" . self::kCol_CategoryID . "`=" . $category->categoryID;
				$sql .= " AND `" . self::kCol_UserID . "`=" . $category->userID_FK;
				
				$result = $this->get_db_con()->query($sql);
				if ($result == FALSE) {
					throw new Exception("SQL exec failed (" . $this->get_db_con()->errno . ")");
				}
			} else {
				throw new Exception("You don't have permissions to perform this operation.");
			}			
		}
	
	    function remove_category($category_ID) {
	    	
			global $config;
			
			if (self::is_owned($category_ID)) {
							
				if ($config['USE_STORED_PROCS'] == FALSE) {
		    		
					$isTransactional = FALSE;
					
					try {
						$isTransactional = $this->get_db_con()->autocommit(FALSE);
						if ($isTransactional == FALSE) {
							throw new Exception("Failed to Create Transaction.");
						} 				
						
						$sql = sprintf("UPDATE `shopitems` 
							SET categoryID_FK=1 
							WHERE `userID_FK`=%d AND `categoryID_FK`=%d",
							parent::GetUserID(),
							$category_ID);
						
						$result = $this->get_db_con()->query($sql);
						if ($result == FALSE) {
							throw new Exception("Could not delete Category");
						}
						
						$sql = sprintf("DELETE `%s` FROM `%s` WHERE `%s`=%d AND `%s`=%d",
							self::kTableName,
							self::kTableName,
							self::kCol_CategoryID,
							$category_ID,
							self::kCol_UserID,
							parent::GetUserID());
		
						$result = $this->get_db_con()->query($sql);
						if ($result == FALSE) {
							throw new Exception("Could not delete Category");
						}
						
						$this->get_db_con()->commit();
						$this->get_db_con()->autocommit(TRUE);
						
					} catch (Exception $e) {
						
						if ($isTransactional) {
							$this->get_db_con()->rollback();
							$this->get_db_con()->autocommit(TRUE);
						}
						
						throw $e;
					}
				} else {
			        $sql = sprintf(
						"call delete_category(%d, %d)",
						$category_ID,
						$this->GetUserID());
			
					$result = $this->get_db_con()->query($sql);
			        if ($result == FALSE)
			            throw new Exception("Could not delete Category");
				}
			} else {
				throw new Exception("You don't have permissions to perform this operation.");
			} 
	    }
	
	    function get_category($category_ID) {
	    	
	        $sql = sprintf("SELECT * 
	        				FROM `shopitemcategories` 
	        				WHERE `categoryID`=%d 
	        				LIMIT 1", 
	        				$category_ID);
							
	        $result = $this->get_db_con()->query($sql);
	        if ($result == FALSE || mysqli_num_rows($result) <= 0) {
	            throw new Exception(
	            	"Database operation failed (" . 
	            	$this->get_db_con()->errno . ")");
			}
	
	        $row = mysqli_fetch_array($result);
	        $category = new Category(
	        	$row[self::kCol_CategoryID], 
	        	$row[self::kCol_UserID], 
	        	$row[self::kCol_CategoryName], 
	        	$row[self::kCol_CategoryRank]);
				
			$result->free();
	        return $category;
	    }
		
		function reorder_category($category_ID, $taget_category_ID, $old_rank, $new_rank) {
			
			if (!self::is_owned($category_ID))
				throw new Exception("You cannot reorder shared (owned by other users) categories.");

			if (!self::is_owned($taget_category_ID))
				throw new Exception("You cannot reorder your categories around shared (owned by other users) categories.");
			
			$sql = "";
			if ($old_rank < $new_rank) {
				
				$sql = sprintf("UPDATE `shopitemcategories`
								JOIN (
					  				SELECT categoryID, (`categoryRank` - 1) as new_rank
		  							FROM shopitemcategories
		  							WHERE `categoryRank` between %d + 1 AND %d AND `userID_FK`=%d
		  							UNION ALL
		  							SELECT %d as categoryID, %d as new_rank
								) as r
								USING (categoryID)
								SET `categoryRank` = new_rank", 
								$old_rank,
								$new_rank,
								self::GetUserID(),
								$category_ID,
								$new_rank);
								
			} else if ($old_rank > $new_rank) {
				
				$sql = sprintf("UPDATE `shopitemcategories` 
								JOIN ( 
									SELECT categoryID, (`categoryRank` + 1) as new_rank 
									FROM shopitemcategories 
									WHERE `categoryRank` between %d AND %d - 1 AND `userID_FK`=%d
									UNION ALL 
									SELECT %d as categoryID, %d as new_rank 
								) as r USING (categoryID) 
								SET `categoryRank` = new_rank",
								$new_rank,
								$old_rank,
								self::GetUserID(),
								$category_ID, 
								$new_rank);
			}
			
			$result = $this->get_db_con()->query($sql);
			if ($result == FALSE) {
				throw new Exception(
					"Database operation failed (" . $this->get_db_con()->errno . ")");
			}	
		}
		
		static function createFactoryCategories($user_id, $connection) {
				
			if ($user_id > 0 && $connection) {
					
				$sql = "INSERT INTO `shopitemcategories` (`categoryName`, `userID_FK`, `categoryRank`)
						VALUES
							('Apparel & Jewelry', 		$user_id, 1),
							('Bath & Beauty', 			$user_id, 2),
							('Baby Supplies', 			$user_id, 3),
							('Beverages', 				$user_id, 4),
							('Books & Magazines', 		$user_id, 5),
							('Breakfast & Cereals', 	$user_id, 6),
							('Condiments', 				$user_id, 7),
							('Dairy', 					$user_id, 8),
							('Electronics & Computers', $user_id, 9),
							('Everything Else', 		$user_id, 10),
							('Frozen Foods', 			$user_id, 11),
							('Fruits', 					$user_id, 12),
							('Furniture', 				$user_id, 13),
							('Games', 					$user_id, 14),
							('Household Supplies', 		$user_id, 15),
							('Meat & Fish', 			$user_id, 16),
							('Medical', 				$user_id, 17),
							('Mobiles & Cameras', 		$user_id, 18),
							('Music', 					$user_id, 19),
							('Movies', 					$user_id, 20),
							('Pet Supplies', 			$user_id, 21),
							('Snacks & Candy', 			$user_id, 22),
							('Supplies', 				$user_id, 23),
							('Toys & Hobbies', 			$user_id, 24),
							('Vegetables', 				$user_id, 25);";
				
				$result = $connection->query($sql);
				if (!$result) {
					throw new Exception(
						"Unable to Create Factory Categories for User (" . 
						$connection->errno . ")");
				}
			}
		}
	}
}

?>
