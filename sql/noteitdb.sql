DROP DATABASE IF EXISTS `noteitdb`;
CREATE DATABASE `noteitdb` CHARSET = latin1;
USE noteitdb;

# -----------------------------------------------------------------
# Create the units table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `units`(
  `unitID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `unitName` VARCHAR(20) NOT NULL COMMENT 'Full text of the unit name e.g. inches, feet, kilo gram',
  `unitAbbreviation` VARCHAR(10) NOT NULL COMMENT 'short text e.g. in, ft, kg etc',
  `unitType` TINYINT(3) UNSIGNED ZEROFILL NOT NULL COMMENT 'Type could be general, metric, imperial (etc -TBD)\\n0 -  general (applies to all unit types) \\n1 - metric\\n2 - imperial',
  PRIMARY KEY (`unitID`),
  UNIQUE KEY `unitName_UNIQUE` (`unitName`),
  UNIQUE KEY `unitAbbreviation_UNIQUE` (`unitAbbreviation`)
) ENGINE = INNODB DEFAULT CHARSET = latin1;

# -----------------------------------------------------------------
# Create the users table. Note that AUTO_INCREMENT=0 (for `root` ID=0)
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users`(
  `userID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Surrogate Key for User ID',
  `emailID` VARCHAR(256) NOT NULL COMMENT 'PK email ID',
  `firstName` VARCHAR(25) DEFAULT NULL,
  `lastName` VARCHAR(25) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `userID_UNIQUE` (`userID`),
  UNIQUE KEY `emailID_UNIQUE` (`emailID`)
) ENGINE = INNODB AUTO_INCREMENT=0 DEFAULT CHARSET = latin1;

# -----------------------------------------------------------------
# Create the shoplists table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shoplists`(
  `listID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `listName` VARCHAR(25) NOT NULL,
  `userID_FK` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`listID`),
  KEY `ref_userID_FK` (`userID_FK`),
  CONSTRAINT `ref_userID_FK` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = INNODB DEFAULT CHARSET = latin1;

# -----------------------------------------------------------------
# Create the shopitemcategories table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitemcategories`(
  `categoryID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `categoryName` VARCHAR(25) NOT NULL,
  `userID_FK` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`userID_FK`, `categoryName`),
  UNIQUE KEY `categoryID_UNIQUE` (`categoryID`),
  KEY `Ref_07` (`userID_FK`),
  CONSTRAINT `ref_userID` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = INNODB DEFAULT CHARSET = latin1;

# -----------------------------------------------------------------
# Create the `shopitemscatalog` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitemscatalog`(
  `itemID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `itemName` VARCHAR(25) NOT NULL,
  `itemPrice` DECIMAL(11, 2) UNSIGNED DEFAULT NULL,
  `userID_FK` INT(11) UNSIGNED NOT NULL,
  `categoryID_FK` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`userID_FK`, `itemName`),
  UNIQUE KEY `itemID_UNIQUE` (`itemID`),
  KEY `Ref_01` (`userID_FK`),
  KEY `Ref_02` (`categoryID_FK`),
  CONSTRAINT `Ref_01` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ref_02` FOREIGN KEY (`categoryID_FK`) REFERENCES `shopitemcategories` (`categoryID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = INNODB DEFAULT CHARSET = latin1;

# -----------------------------------------------------------------
# Create the `shopitems` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitems`(
  `instanceID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userID_FK` INT(11) UNSIGNED NOT NULL,
  `itemID_FK` INT(11) UNSIGNED NOT NULL,
  `dateAdded` DATE NOT NULL,
  `datePurchased` DATE DEFAULT NULL,
  `listID_FK` INT(11) UNSIGNED NOT NULL,
  `unitCost` DECIMAL(11, 2) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'Cost per unit. So:\\n   total cost = cost  X quantity',
  `quantity` DECIMAL(11, 2) UNSIGNED ZEROFILL DEFAULT NULL,
  `unitID_FK` INT(10) UNSIGNED NOT NULL,
  `categoryID_FK` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`instanceID`),
  UNIQUE KEY `instanceID_UNIQUE` (`instanceID`),
  KEY `Ref_01_FK` (`userID_FK`),
  KEY `Ref_02_FK` (`itemID_FK`),
  KEY `Ref_03_FK` (`listID_FK`),
  KEY `Ref_04_FK` (`unitID_FK`),
  KEY `Ref_05_FK` (`categoryID_FK`),
  CONSTRAINT `Ref_01_FK` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ref_02_FK` FOREIGN KEY (`itemID_FK`) REFERENCES `shopitemscatalog` (`itemID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ref_03_FK` FOREIGN KEY (`listID_FK`) REFERENCES `shoplists` (`listID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ref_04_FK` FOREIGN KEY (`unitID_FK`) REFERENCES `units` (`unitID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ref_05_FK` FOREIGN KEY (`categoryID_FK`) REFERENCES `shopitemcategories` (`categoryID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = INNODB DEFAULT CHARSET = latin1;

# -----------------------------------------------------------------
# Create the procedure `add_category`
# -----------------------------------------------------------------
DELIMITER //
CREATE 
  DEFINER = `root`@`localhost` 
  PROCEDURE `add_category`(IN `categoryName` VARCHAR(25), IN `userID` INT)
BEGIN
  
  DECLARE newIndex INT;
  
  SELECT (max(categoryID) + 1)
  INTO newIndex
  FROM shopitemcategories;

  INSERT INTO shopitemcategories (categoryID, categoryName, userID_FK) 
  VALUES (newIndex, categoryName, userID);

END
//

# -----------------------------------------------------------------
# Create the procedure `add_shop_item`
# -----------------------------------------------------------------
DELIMITER //
CREATE 
  DEFINER = `root`@`localhost` 
  FUNCTION `add_shop_item`(`userID` INT,
    `listID` INT,
    `categoryID` INT,
    `inItemName` VARCHAR(50),
    `unitCost` DECIMAL(11, 2),
    `quantity` DECIMAL(11, 2),
    `unitID` INT) 
  RETURNS INT(11)
  DETERMINISTIC
  COMMENT 'Adds a new item in the shopping list. Returns ID of the new Item. NOTE: The item must already be present in the shopitemscatalog or should be added there as well.'
BEGIN
  DECLARE thisItemID INT;

  -- We need an exact match on `itemName`
  SELECT `itemID`
  INTO thisItemID
  FROM `shopitemscatalog`
  WHERE `itemName` = inItemName AND `userID_FK` = userID
  LIMIT 1;

  IF thisItemID IS NULL THEN
    -- A record of this item does not exist in the `shopitemcatelog` table; create one
    INSERT INTO `shopitemscatalog` (
        `itemName`, 
        `itemPrice`, 
        `userID_FK`, 
        `categoryID_FK`) 
    VALUES (inItemName, unitCost, userID, categoryID);

    SET thisItemID = @@last_insert_id;
  END IF;

  INSERT INTO `shopitems` (
      `userID_FK`, 
      `itemID_FK`, 
      `dateAdded`, 
      `listID_FK`, 
      `unitCost`, 
      `quantity`, 
      `unitID_FK`, 
      `categoryID_FK`) 
  VALUES (userID, thisItemID, curdate(), listID, unitCost, quantity, unitID, categoryID);

  RETURN @@last_insert_id;
END
//

# -----------------------------------------------------------------
# Create the procedure `delete_category`
# -----------------------------------------------------------------
DELIMITER //
CREATE 
  DEFINER = `root`@`localhost` 
  PROCEDURE `delete_category`(
    category_ID INT,
    user_ID INT)
BEGIN
  -- Delete all items contained in this list.
  UPDATE `shopitemscatalog`
  SET `shopitemscatalog`.`categoryID_FK` = 0
  WHERE 
    `shopitemscatalog`.`userID_FK` = user_ID 
    AND
    `shopitemscatalog`.`categoryID_FK` = category_ID;

  DELETE `shopitemcategories`
  FROM
    `shopitemcategories`
  WHERE
    `shopitemcategories`.`categoryID` = category_ID
    AND
    `shopitemcategories`.`userID_FK` = user_ID;
END
//

# -----------------------------------------------------------------
# Create the procedure `delete_shopping_list`
# -----------------------------------------------------------------
DELIMITER //
CREATE 
  DEFINER = `root`@`localhost` 
  PROCEDURE `delete_shopping_list`(
    list_ID INT,
    user_ID INT)
BEGIN
  -- Delete all items contained in this list.
  DELETE shoplists, shopitems
  FROM shoplists
  LEFT JOIN shopitems
  ON shoplists.listID = shopitems.listID_FK
    AND shoplists.userID_FK = shopitems.userID_FK
  WHERE shoplists.listID = list_ID AND shoplists.userID_FK = user_ID;

END
//

DELIMITER ;

# -----------------------------------------------------------------
# Populate factory users into the users table. User with ID = 0 is
# the `root` user. There are special cases throughout code for this user.
# [TODO]: How to force `root` user to have id=0??
# -----------------------------------------------------------------
INSERT INTO `users` (`emailID`, `firstName`, `lastName`) VALUES ('mrmangu@hotmail.com', 'Manish', 'Gupta');
INSERT INTO `users` (`emailID`, `firstName`, `lastName`) VALUES ('gmanish@gmail.com', 'Manish', 'Gupta');

# -----------------------------------------------------------------
# Populate factory units into the units table
#
# NOTE: Keep this list sorted alphabetically
# -----------------------------------------------------------------
# Common units (Have the unitType = 0)
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('unit', 'unit', 0);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('pair', 'pair', 0);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('package', 'package', 0);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('piece', 'piece', 0);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('dozen', 'dozen', 0);

# SI/Metric Units (Have the unitType = 1)
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('meter', 'm', 1);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('kilogram', 'kg', 1);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('square meter', 'm2', 1);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('gram', 'gm', 1);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('litre', 'l', 1);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('millimeter', 'mm', 1);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('centimeter', 'cm', 1);

# Imperial Units (Have the unitType = 2)
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('foot', 'ft', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('inch', 'in', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('yard', 'yd', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('mile', 'mi', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('ounce', 'oz', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('pound', 'lb', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('pint', 'pt', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('quart', 'qt', 2);
INSERT INTO `noteitdb`.`units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('gallon', 'gal', 2);

# -----------------------------------------------------------------
# Populate factory categories into the shopitemcategories table. These
# categories belong to `root` user with ID = 0 and are visible to all.
#
# NOTE: Keep this list sorted alphabetically
# -----------------------------------------------------------------
INSERT INTO `shopitemcategories` (`categoryName`, `userID_FK`)
VALUES
	('Uncategorized', 1),
	('Apparel & Jewelry', 1),
	('Bath & Beauty', 1),
	('Baby Supplies', 1),
	('Beverages', 1),
	('Books & Magazines', 1),
	('Breakfast & Cereals', 1),
	('Condiments', 1),
	('Dairy', 1),
	('Electronics & Computers', 1),
	('Everything Else', 1),
	('Frozen Foods', 1),
	('Fruits', 1),
	('Furniture', 1),
	('Games', 1),
	('Housewares', 1),
	('Meat & Fish', 1),
	('Medical', 1),
	('Mobiles & Cameras', 1),
	('Music', 1),
	('Movies', 1),
	('Pet Supplies', 1),
	('Snacks & Candy', 1),
	('Supplies', 1),
	('Toys & Hobbies', 1),
	('Vegetables', 1);
