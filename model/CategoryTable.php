<?php
require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/lib/NoteItCommon.php";
require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/TableBase.php";

class ListFunctorCategoryList
{
    public $_categoryList;

    function __construct(& $category_list)
    {
        $_categoryList = & $category_list;
    }
    

	public function iterate_row($category_ID, $category_Name, $user_ID)
	{
        $this->_categoryList[] = array(
            ShopListTable::kCol_ListID => $category_ID,
            ShopListTable::kCol_ListName => $category_Name,
            ShopListTable::kCol_UserID => $user_ID);

	}
}

class Category
{
    public $categoryID = 0;
    public $categoryName = "<Fill Name Here>";
    public $userID_FK = 0;

    function __construct($id, $name, $user_ID)
    {
        $this->categoryID = $id;
        $this->categoryName = $name;
        $this->categoryID = $user_ID;
    }
}

class CategoryTable extends TableBase
{
	const kTableName = 'shopitemcategories';
    const kCol_CategoryID = 'categoryID';
    const kCol_CategoryName = 'categoryName';
    const kCol_UserID = 'userID_FK';

	function __construct($user_ID)
	{
		parent::__construct(self::kTableName, $user_ID);
	}
	
	function list_all($current_user_only, &$functor_obj, $function_name='iterate_row')
	{
		if ($current_user_only == TRUE)
			$sql = sprintf(
					"SELECT * FROM `%s` WHERE `userID_FK`=%d ORDER BY `categoryName`", 
					parent::GetTableName(), 
					parent::GetUserID());
		else
			$sql = sprintf(
					"SELECT * FROM `%s` ORDER BY `categoryName`", 
					parent::GetTableName());
		
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
				$row[2]); // 'userID_FK
		}
		
		if ($result)
			$result->free();
	}

    function add_category($category_name)
    {
        $sql = sprintf(
				"SELECT add_category('%s', %d)", 
				$this->get_db_con()->escape_string($category_name), 
				parent::GetUserID());

        NI::TRACE($sql, __FILE__,  __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE || mysqli_num_rows($result) <= 0)
	    	throw new Exception("SQL exec failed (" . __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);
			
       	$row = $result->fetch_row();
		$result->free();
		return $row[0];
    }

    function remove_category($category_ID)
    {
        $sql = sprintf(
				"call delete_category(%d, %d)",
				$category_ID,
				$this->GetUserID());

		$result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception("Database operaion failed (" . __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);
    }

    function get_category($category_ID)
    {
        $sql = sprintf("SELECT * FROM `shopitemcategories` WHERE `categoryID`=%d LIMIT 1", $category_ID);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE || mysqli_num_rows($result) <= 0)
            throw new Exception("Database operation failed (" . __FILE__ . __LINE__ . ")" . $this->get_db_con()->error);

        $row = mysqli_fetch_array($result);
        $category = new Category($row[0], $row[1], $row[2]);
		$result->free();
        return $category;
    }
}
?>
