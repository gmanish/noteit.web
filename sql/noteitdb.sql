DROP DATABASE IF EXISTS `noteitdb`;
CREATE DATABASE `noteitdb` CHARSET = utf8;
USE noteitdb;


CREATE TABLE IF NOT EXISTS `countrytable` (
  `countryCode` varchar(3) NOT NULL,
  `currencyCode` varchar(3) NOT NULL,
  `currencySymbol` varchar(3) NOT NULL,
  `currencyIsRight` tinyint(4) DEFAULT '0',
  `currencyName` varchar(45) DEFAULT 'US Dollar',
  PRIMARY KEY (`countryCode`,`currencyCode`),
  UNIQUE KEY `countryCode_UNIQUE` (`countryCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
) ENGINE = INNODB DEFAULT CHARSET = utf8;

# -----------------------------------------------------------------
# Create the shoplists table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `emailID` varchar(254) NOT NULL,
  `firstName` varchar(25) DEFAULT NULL,
  `lastName` varchar(25) DEFAULT NULL,
  `countryCode` varchar(3) NOT NULL DEFAULT 'US',
  `currencyCode` varchar(3) NOT NULL DEFAULT 'USD',
  `userPassword` binary(20) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `userID_UNIQUE` (`userID`),
  UNIQUE KEY `emailID_UNIQUE` (`emailID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the shoplists table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shoplists`(
  `listID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `listName` VARCHAR(50) NOT NULL,
  `userID_FK` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`listID`),
  KEY `ref_userID_FK` (`userID_FK`),
  CONSTRAINT `ref_userID_FK` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = INNODB DEFAULT CHARSET = utf8;

# -----------------------------------------------------------------
# Create the shopitemcategories table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitemcategories` (
  `categoryID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `categoryName` varchar(50) NOT NULL,
  `userID_FK` int(11) unsigned NOT NULL,
  `categoryRank` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID_FK`,`categoryName`),
  UNIQUE KEY `categoryID_UNIQUE` (`categoryID`),
  KEY `Ref_07` (`userID_FK`),
  CONSTRAINT `ref_userID` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the `shopitemscatalog` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitemscatalog`(
  `itemID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `itemName` VARCHAR(50) NOT NULL,
  `itemPrice` DECIMAL(11, 2) UNSIGNED DEFAULT NULL,
  `userID_FK` INT(11) UNSIGNED NOT NULL,
  `categoryID_FK` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`userID_FK`, `itemName`),
  UNIQUE KEY `itemID_UNIQUE` (`itemID`),
  KEY `Ref_01` (`userID_FK`),
  KEY `Ref_02` (`categoryID_FK`),
  CONSTRAINT `Ref_01` FOREIGN KEY (`userID_FK`) REFERENCES `users` (`userID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Ref_02` FOREIGN KEY (`categoryID_FK`) REFERENCES `shopitemcategories` (`categoryID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = INNODB DEFAULT CHARSET = utf8;

# -----------------------------------------------------------------
# Create the `shopitems` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS`shopitems` (
  `instanceID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID_FK` int(11) unsigned NOT NULL,
  `itemID_FK` int(11) unsigned NOT NULL,
  `dateAdded` date NOT NULL,
  `datePurchased` date DEFAULT NULL,
  `listID_FK` int(11) unsigned NOT NULL,
  `unitCost` decimal(11,2) unsigned zerofill DEFAULT NULL COMMENT 'Cost per unit. So:\\n   total cost = cost  X quantity',
  `quantity` decimal(11,2) unsigned zerofill DEFAULT NULL,
  `unitID_FK` int(10) unsigned NOT NULL,
  `categoryID_FK` int(11) unsigned NOT NULL DEFAULT '0',
  `isPurchased` tinyint(1) DEFAULT '0',
  `isAskLater` tinyint(1) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------------------------------
-- Routine DDL
-- --------------------------------------------------------------------------------
DELIMITER //

CREATE DEFINER=`root`@`localhost` FUNCTION `add_category`(
  `categoryName` VARCHAR(25), 
  `userID` INT) RETURNS int(11)
   DETERMINISTIC
 BEGIN
  
  DECLARE newIndex INT;
  
  SELECT (max(categoryID) + 1)
  INTO newIndex
  FROM shopitemcategories;

  INSERT INTO shopitemcategories (categoryID, categoryName, userID_FK) 
  VALUES (newIndex, categoryName, userID);

  return newIndex;

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
    `unitID` INT,
    `isAskLater` TINYINT) 
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
      `categoryID_FK`,
      `isAskLater`) 
  VALUES (userID, thisItemID, curdate(), listID, unitCost, quantity, unitID, categoryID, isAskLater);

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
DELETE 

FROM shopitems
WHERE shopitems.userID_FK = user_ID AND shopitems.`listID_FK` = list_ID;

DELETE 
FROM shoplists
WHERE shoplists.`listID` = list_ID AND shoplists.`userID_FK` = user_ID;

-- Delete all items contained in this list.
-- DELETE shopitems, shoplists
--  FROM shopitems
--  LEFT JOIN shoplists
--  ON shopitems.listID_FK = shoplists.listID
--    AND shopitems.userID_FK = shoplists.userID_FK
--  WHERE shoplists.listID = list_ID AND shoplists.userID_FK = user_ID;

END
//

DELIMITER ;

# -----------------------------------------------------------------
# Insert Countries And Currencies
# -----------------------------------------------------------------
INSERT INTO `countrytable` (
    `countryCode`, 
    `currencyCode`, 
    `currencySymbol`, 
    `currencyIsRight`, 
    `currencyName`)
VALUES 
    ('IN', 'INR', 'Rs ', 0, 'India Rupee'),
    ('US', 'USD', '$', 0, 'US Dollar');

# -----------------------------------------------------------------
# Populate factory users into the users table. User with ID = 0 is
# the `root` user. There are special cases throughout code for this user.
# [TODO]: How to force `root` user to have id=0??
# -----------------------------------------------------------------
INSERT INTO `users` (`emailID`, `firstName`, `lastName`, `userPassword`) VALUES ('mrmangu@hotmail.com', 'Manish', 'Gupta', UNHEX('3530280EDB5AA715929266D1D6F0423ABC27B104'));
INSERT INTO `users` (`emailID`, `firstName`, `lastName`, `userPassword`) VALUES ('gmanish@gmail.com', 'Manish', 'Gupta', UNHEX('3530280EDB5AA715929266D1D6F0423ABC27B104'));

# -----------------------------------------------------------------
# Populate factory units into the units table
#
# NOTE: Keep this list sorted alphabetically
# -----------------------------------------------------------------
# Common units (Have the unitType = 0)
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('unit', 'unit', 0);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('pair', 'pair', 0);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('package', 'package', 0);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('piece', 'piece', 0);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('dozen', 'dozen', 0);

# SI/Metric Units (Have the unitType = 1)
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('meter', 'm', 1);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('kilogram', 'kg', 1);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('square meter', 'm2', 1);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('gram', 'gm', 1);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('litre', 'l', 1);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('millimeter', 'mm', 1);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('centimeter', 'cm', 1);

# Imperial Units (Have the unitType = 2)
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('foot', 'ft', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('inch', 'in', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('yard', 'yd', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('mile', 'mi', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('ounce', 'oz', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('pound', 'lb', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('pint', 'pt', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('quart', 'qt', 2);
INSERT INTO `units` (`unitName`, `unitAbbreviation`, `unitType`) VALUES ('gallon', 'gal', 2);

# -----------------------------------------------------------------
# Populate factory categories into the shopitemcategories table. These
# categories belong to `root` user with ID = 0 and are visible to all.
#
# NOTE: Keep this list sorted alphabetically
# -----------------------------------------------------------------
INSERT INTO `shopitemcategories` (`categoryName`, `userID_FK`, `categoryRank`)
VALUES
	('Uncategorized', 1, 1),
	('Apparel & Jewelry', 1, 2),
	('Bath & Beauty', 1, 3),
	('Baby Supplies', 1, 4),
	('Beverages', 1, 5),
	('Books & Magazines', 1, 6),
	('Breakfast & Cereals', 1, 7),
	('Condiments', 1, 8),
	('Dairy', 1, 9),
	('Electronics & Computers', 1, 10),
	('Everything Else', 1, 11),
	('Frozen Foods', 1, 12),
	('Fruits', 1, 13),
	('Furniture', 1, 14),
	('Games', 1, 15),
	('Housewares', 1, 16),
	('Meat & Fish', 1, 17),
	('Medical', 1, 18),
	('Mobiles & Cameras', 1, 19),
	('Music', 1, 20),
	('Movies', 1, 21),
	('Pet Supplies', 1, 22),
	('Snacks & Candy', 1, 23),
	('Supplies', 1, 24),
	('Toys & Hobbies', 1, 25),
	('Vegetables', 1, 26);
