<?php
require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/lib/NoteItCommon.php";
require_once $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "noteit.web/model/TableBase.php";


class ShopItem
{
    const SHOPITEM_ITEMID           = 1;    // 1 << 0
    const SHOPITEM_USERID           = 2;    // 1 << 1
    const SHOPITEM_LISTID           = 4;    // 1 << 2
    const SHOPITEM_CATEGORYID       = 8;    // 1 << 3
    const SHOPITEM_ITEMNAME         = 16;   // 1 << 4
    const SHOPITEM_UNITCOST         = 32;   // 1 << 5
    const SHOPITEM_QUANTITY         = 64;   // 1 << 6
    const SHOPITEM_UNITID           = 128;  // 1 << 7
    const SHOPITEM_DATEADDED        = 256;  // 1 << 8
    const SHOPITEM_DATEPURCHASED    = 512;  // 1 << 9

    public $_instance_id = 0;
    public $_item_id = 0;
    public $_user_id = 0;
    public $_list_id = 0;
    public $_category_id = 0;
    public $_item_name = '';
    public $_unit_cost = 0.00;
    public $_quantity = 0.00;
    public $_unit_id = 0;
    public $_date_added;
    public $_date_purchased;

    function __construct(
        $instance_id,
        $item_id,
        $user_id,
        $list_id,
        $category_id,
        $item_name,
        $unit_cost = 0.00,
        $quantity = 1.00,
        $unit_id = 3 /* General Unit */)
    {
        NI::TRACE('ShopItem::__construct', __FILE__, __LINE__);
        $this->_instance_id = $instance_id;
        $this->_item_id = $item_id;
        $this->_user_id = $user_id;
        $this->_list_id = $list_id;
        $this->_category_id = $category_id;
        $this->_item_name = $item_name;
        $this->_unit_cost = $unit_cost;
        $this->_quantity = $quantity;
        $this->_unit_id = $unit_id;
        NI::TRACE('User ID: ' . $this->_user_id, __FILE__, __LINE__);
    }
}

class ShopItems extends TableBase
{
    const kTableName        = 'shopitems';
    const kColInstanceID    = 'instanceID';
    const kColUserID        = 'userID_FK';
    const kColItemID        = 'itemID_FK';
    const kColItemName      = 'itemName';
    const kColDateAdded     = 'dateAdded';
    const kColDatePurchased = 'datePurchased';
    const kColListID        = 'listID_FK';
    const kColUnitCost      = 'unitCost';
    const kColQuantity      = 'quantity';
    const kColUnitID        = 'unitID_FK';
    const kColCategoryID= 'categoryID_FK';

    function __construct($user_ID)
    {
        parent::__construct(self::kTableName, $user_ID);
    }

    function add_item($list_id, $category_id, $item_name, $unit_cost, $item_quantity, $unit_id)
    {
        $sql = "select add_shop_item(" .
               parent::GetUserID() . ", " .
               $list_id . ", " .
               $category_id . ", " .
               "'" . $item_name . "', " .
               $unit_cost . ", " .
               $item_quantity . ", " .
               $unit_id . ")";

        NI::TRACE($sql, __FILE__,  __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception("SQL exec failed (" . __FILE__ . __LINE__ . "): " . $this->get_db_con()->error . " (" . $this->get_db_con()->errno . ")");

        $row = $result->fetch_row();
        return $row[0];
    }

    function list_range(
        $show_purchased_items,
        $list_id,
        $start_at,
        $num_rows_fetch,
        & $functor_obj,
        $function_name='iterate_row')
    {
        $sql = sprintf("SELECT si.*, sic.itemName " .
                "FROM `%s` AS si " .
                "inner join `shopitemscatalog` AS sic " .
                "ON si.itemID_FK = sic.itemID " .
                "where si.userID_FK = %d and si.listID_FK = %d",
                parent::GetTableName(),
                parent::GetUserID(),
                $list_id);

        if ($show_purchased_items == FALSE) // Hide items that have been purchased
            $sql = $sql . " AND si.datePurchased IS NULL";

        $sql = $sql . " LIMIT " . $start_at . ", " . $num_rows_fetch;  // Fech 20 items starting from row $start_at

        NI::TRACE('ShopItems::list_range SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception("SQL exec failed (". __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);

        NI::TRACE($sql, __FILE__, __LINE__);
        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
            NI::TRACE('ShopItems::list_range() returned row: ' . print_r($row, true), __FILE__, __LINE__);
            $thisItem = new ShopItem(
                $row[self::kColInstanceID],
                $row[self::kColItemID],
                $row[self::kColUserID],
                $row[self::kColListID],
                $row[self::kColCategoryID],
                $row[self::kColItemName],
                $row[self::kColUnitCost],  // unit cost
                $row[self::kColQuantity],
                $row[self::kColUnitID]);

            call_user_func(
                array($functor_obj, $function_name), // invoke the callback function
                $thisItem);

            $thisItem = NULL;
        }

        if ($result)
            mysqli_free_result($result);
    }

    function get_item($instance_id)
    {
        $sql = sprintf("SELECT si.*, sic.itemName " .
                "FROM `%s` AS si " .
                "inner join `shopitemscatalog` AS sic " .
                "ON si.itemID_FK = sic.itemID " .
                "where si.userID_FK = %d and si.instanceID = %d " .
                "LIMIT 1",
                parent::GetTableName(),
                parent::GetUserID(),
                $instance_id);

        NI::TRACE('ShopItems::get_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception("SQL exec failed (". __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);

        while ($row = mysqli_fetch_array($result, MYSQL_ASSOC))
        {
            NI::TRACE('ShopItems::get_item() returned row: ' . print_r($row, true), __FILE__, __LINE__);
            $thisItem = new ShopItem(
                $row[self::kColInstanceID],
                $row[self::kColItemID],
                $row[self::kColUserID],
                $row[self::kColListID],
                $row[self::kColCategoryID],
                $row[self::kColItemName],
                $row[self::kColUnitCost],  // unit cost
                $row[self::kColQuantity],
                $row[self::kColUnitID]);

            if ($result)
                mysqli_free_result($result);

            return $thisItem;
        }
    }

    function edit_item($instance_id, $item, $item_flags /* one of SHOPITEM_* flags */)
    {
        $sql = "UPDATE " + parent::GetTableName();
        $sql = "SET ";
        $prev_column_added = false;

        if ($item_flags & SHOPITEM_CATEGORYID)
        {
            $sql = " " + self::kColCategoryID + "=" + $item->_category_id;
            $prev_column_added = true;
        }
        else
            $prev_column_added = false;

        if ($item_flags & SHOPITEM_ITEMNAME)
        {
            if ($prev_column_added)
                $sql += ", ";
            $sql += " " + self::kColItemName + "=" + $item->_item_name;
            $prev_column_added = true;
        }
        else
            $prev_column_added = false;

        if ($item_flags & SHOPITEM_UNITCOST)
        {
            if ($prev_column_added)
                $sql += ", ";
            $sql = " " + self::kColUnitCost + "=" + $item->_unit_cost;
            $prev_column_added = true;
        }
        else
            $prev_column_added = false;

        if ($item_flags & SHOPITEM_QUANTITY)
        {
            if ($prev_column_added)
                 $sql += ", ";
            $sql = " " + self::kColQuantity + "=" + $_item->_quantity;
            $prev_column_added = true;
        }
        else
            $prev_column_added = false;

        if ($item_flags & SHOPITEM_UNITID)
        {
            if ($prev_column_added)
                $sql += ", ";
            $sql = " " + self::kColUnitID + "=" + $_item->_unit_id;
            $prev_column_added = true;
        }
        else
            $prev_column_added = true;

        $sql += "  WHERE " + self::kColInstanceID + "=" + $_item->_instance_id;

        NI::TRACE('ShopItems::edit_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception("SQL exec failed (". __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);
    }

    function delete_item($instance_id)
    {
        $sql = sprintf("DELETE FROM `%s` " .
                "where %s = %d and %s = %d ",
                parent::GetTableName(),
                self::kColUserID,
                parent::GetUserID(),
                self::kColInstanceID,
                $instance_id);

        NI::TRACE('ShopItems::delete_item SQL: ' . $sql, __FILE__, __LINE__);
        $result = $this->get_db_con()->query($sql);
        if ($result == FALSE)
            throw new Exception("SQL exec failed (". __FILE__ . __LINE__ . "): " . $this->get_db_con()->error);
    }

}