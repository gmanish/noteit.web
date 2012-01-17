CREATE DATABASE IF NOT EXISTS `noteitdb` CHARSET = utf8;
USE noteitdb;

CREATE TABLE `countrytable` (
  `countryCode` varchar(2) NOT NULL DEFAULT '',
  `currencyCode` varchar(3) NOT NULL,
  `currencySymbol` varchar(4) NOT NULL DEFAULT '',
  `currencyIsRight` tinyint(4) DEFAULT '0',
  `currencyName` varchar(45) DEFAULT 'US Dollar',
  `countryName` varchar(50) DEFAULT NULL,
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
CREATE TABLE `shopitemscatalog` (
  `itemID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `itemName` varchar(50) NOT NULL,
  `itemBarcode` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`itemID`),
  UNIQUE KEY `itemName` (`itemName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
INSERT INTO `countrytable` (`countryCode`, `currencyCode`, `currencySymbol`, `currencyIsRight`, `currencyName`, `countryName`)
VALUES
	('AF', 'AFN', '؋', 0, 'Afghani', 'AFGHANISTAN'),
	('DZ', 'DZD', 'دج', 0, 'Algerian Dinar', 'ALGERIA'),
	('AR', 'ARS', '$', 0, 'Argentine Peso', 'ARGENTINA'),
	('AM', 'AMD', '', 0, 'Armenian Dram', 'ARMENIA'),
	('AW', 'AWG', 'ƒ', 0, 'Aruban Florin', 'ARUBA'),
	('AU', 'AUD', '$', 0, 'Australian Dollar', 'AUSTRALIA'),
	('KI', 'AUD', '$', 0, 'Australian Dollar', 'KIRIBATI'),
	('NR', 'AUD', '$', 0, 'Australian Dollar', 'NAURU'),
	('CC', 'AUD', '$', 0, 'Australian Dollar', 'COCOS (KEELING) ISLANDS'),
	('TV', 'AUD', '$', 0, 'Australian Dollar', 'TUVALU'),
	('CX', 'AUD', '$', 0, 'Australian Dollar', 'CHRISTMAS ISLAND'),
	('HM', 'AUD', '$', 0, 'Australian Dollar', 'HEARD ISLAND AND MCDONALD ISLANDS'),
	('NF', 'AUD', '$', 0, 'Australian Dollar', 'NORFOLK ISLAND'),
	('AZ', 'AZN', 'ман', 0, 'Azerbaijanian Manat', 'AZERBAIJAN'),
	('BS', 'BSD', '$', 0, 'Bahamian Dollar', 'BAHAMAS'),
	('BH', 'BHD', '', 0, 'Bahraini Dinar', 'BAHRAIN'),
	('TH', 'THB', '฿', 0, 'Baht', 'THAILAND'),
	('BB', 'BBD', '$', 0, 'Barbados Dollar', 'BARBADOS'),
	('BY', 'BYR', 'p.', 0, 'Belarussian Ruble', 'BELARUS'),
	('BZ', 'BZD', 'BZ$', 0, 'Belize Dollar', 'BELIZE'),
	('BM', 'BMD', '$', 0, 'Bermudian Dollar', 'BERMUDA'),
	('VE', 'VEF', 'Bs', 0, 'Bolivar Fuerte', 'VENEZUELA, BOLIVARIAN REPUBLIC OF'),
	('BR', 'BRL', 'R$', 0, 'Brazilian Real', 'BRAZIL'),
	('BN', 'BND', '$', 0, 'Brunei Dollar', 'BRUNEI DARUSSALAM'),
	('BG', 'BGN', 'лв', 0, 'Bulgarian Lev', 'BULGARIA'),
	('BI', 'BIF', 'FBu', 0, 'Burundi Franc', 'BURUNDI'),
	('CA', 'CAD', '$', 0, 'Canadian Dollar', 'CANADA'),
	('CV', 'CVE', 'CVE', 0, 'Cape Verde Escudo', 'CAPE VERDE'),
	('KY', 'KYD', '$', 0, 'Cayman Islands Dollar', 'CAYMAN ISLANDS'),
	('GW', 'XOF', '', 0, 'CFA Franc BCEAO', 'GUINEA-BISSAU'),
	('ML', 'XOF', '', 0, 'CFA Franc BCEAO', 'MALI'),
	('SN', 'XOF', '', 0, 'CFA Franc BCEAO', 'SENEGAL'),
	('NE', 'XOF', '', 0, 'CFA Franc BCEAO', 'NIGER'),
	('TG', 'XOF', '', 0, 'CFA Franc BCEAO', 'TOGO'),
	('BJ', 'XOF', '', 0, 'CFA Franc BCEAO', 'BENIN'),
	('BF', 'XOF', '', 0, 'CFA Franc BCEAO', 'BURKINA FASO'),
	('GA', 'XAF', '', 0, 'CFA Franc BEAC', 'GABON'),
	('CM', 'XAF', '', 0, 'CFA Franc BEAC', 'CAMEROON'),
	('CF', 'XAF', 'CFA', 0, 'CFA Franc BEAC', 'CENTRAL AFRICAN REPUBLIC'),
	('TD', 'XAF', 'FCFA', 0, 'CFA Franc BEAC', 'CHAD'),
	('GQ', 'XAF', '', 0, 'CFA Franc BEAC', 'EQUATORIAL GUINEA'),
	('NC', 'XPF', '', 0, 'CFP Franc', 'NEW CALEDONIA'),
	('WF', 'XPF', '', 0, 'CFP Franc', 'WALLIS AND FUTUNA'),
	('PF', 'XPF', '', 0, 'CFP Franc', 'FRENCH POLYNESIA'),
	('CL', 'CLP', '$', 0, 'Chilean Peso', 'CHILE'),
	('BA', 'BAM', 'KM', 0, 'Convertible Mark', 'BOSNIA AND HERZEGOVINA'),
	('NI', 'NIO', 'C$', 0, 'Cordoba Oro', 'NICARAGUA'),
	('CR', 'CRC', '₡', 0, 'Costa Rican Colon', 'COSTA RICA'),
	('HR', 'HRK', 'kn', 0, 'Croatian Kuna', 'CROATIA'),
	('CU', 'CUP', '₱', 0, 'Cuban Peso', 'CUBA'),
	('CZ', 'CZK', 'Kč', 0, 'Czech Koruna', 'CZECH REPUBLIC'),
	('GM', 'GMD', 'D', 0, 'Dalasi', 'GAMBIA'),
	('GL', 'DKK', 'kr', 0, 'Danish Krone', 'GREENLAND'),
	('FO', 'DKK', 'kr', 0, 'Danish Krone', 'FAROE ISLANDS'),
	('DK', 'DKK', 'kr', 0, 'Danish Krone', 'DENMARK'),
	('MK', 'MKD', 'ден', 0, 'Denar', 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF'),
	('DJ', 'DJF', 'DJF', 0, 'Djibouti Franc', 'DJIBOUTI'),
	('ST', 'STD', 'Db', 0, 'Dobra', 'SAO TOME AND PRINCIPE'),
	('DO', 'DOP', 'RD$', 0, 'Dominican Peso', 'DOMINICAN REPUBLIC'),
	('VN', 'VND', '₫', 0, 'Dong', 'VIET NAM'),
	('VC', 'XCD', '$', 0, 'East Caribbean Dollar', 'SAINT VINCENT AND THE GRENADINES'),
	('KN', 'XCD', '$', 0, 'East Caribbean Dollar', 'SAINT KITTS AND NEVIS'),
	('AG', 'XCD', '$', 0, 'East Caribbean Dollar', 'ANTIGUA AND BARBUDA'),
	('GD', 'XCD', '$', 0, 'East Caribbean Dollar', 'GRENADA'),
	('LC', 'XCD', '$', 0, 'East Caribbean Dollar', 'SAINT LUCIA'),
	('AI', 'XCD', '$', 0, 'East Caribbean Dollar', 'ANGUILLA'),
	('MS', 'XCD', '$', 0, 'East Caribbean Dollar', 'MONTSERRAT'),
	('DM', 'XCD', '$', 0, 'East Caribbean Dollar', 'DOMINICA'),
	('EG', 'EGP', '£', 0, 'Egyptian Pound', 'EGYPT'),
	('ET', 'ETB', 'Br', 0, 'Ethiopian Birr', 'ETHIOPIA'),
	('LU', 'EUR', '€', 0, 'Euro', 'LUXEMBOURG'),
	('GP', 'EUR', '€', 0, 'Euro', 'GUADELOUPE'),
	('MF', 'EUR', '€', 0, 'Euro', 'SAINT MARTIN (FRENCH PART)'),
	('MC', 'EUR', '€', 0, 'Euro', 'MONACO'),
	('GR', 'EUR', '€', 0, 'Euro', 'GREECE'),
	('ME', 'EUR', '€', 0, 'Euro', 'MONTENEGRO'),
	('IT', 'EUR', '€', 0, 'Euro', 'ITALY'),
	('MQ', 'EUR', '€', 0, 'Euro', 'MARTINIQUE'),
	('IE', 'EUR', '€', 0, 'Euro', 'IRELAND'),
	('MT', 'EUR', '€', 0, 'Euro', 'MALTA'),
	('ES', 'EUR', '€', 0, 'Euro', 'SPAIN'),
	('EE', 'EUR', '€', 0, 'Euro', 'ESTONIA'),
	('PT', 'EUR', '€', 0, 'Euro', 'PORTUGAL'),
	('AD', 'EUR', '€', 0, 'Euro', 'ANDORRA'),
	('SI', 'EUR', '€', 0, 'Euro', 'SLOVENIA'),
	('DE', 'EUR', '€', 0, 'Euro', 'GERMANY'),
	('CY', 'EUR', '€', 0, 'Euro', 'CYPRUS'),
	('BE', 'EUR', '€', 0, 'Euro', 'BELGIUM'),
	('AT', 'EUR', '€', 0, 'Euro', 'AUSTRIA'),
	('TF', 'EUR', '€', 0, 'Euro', 'FRENCH SOUTHERN TERRITORIES'),
	('SM', 'EUR', '€', 0, 'Euro', 'SAN MARINO'),
	('PM', 'EUR', '€', 0, 'Euro', 'SAINT PIERRE AND MIQUELON'),
	('SK', 'EUR', '€', 0, 'Euro', 'SLOVAKIA'),
	('NL', 'EUR', '€', 0, 'Euro', 'NETHERLANDS');

INSERT INTO `countrytable` (`countryCode`, `currencyCode`, `currencySymbol`, `currencyIsRight`, `currencyName`, `countryName`)
VALUES
	('GF', 'EUR', '€', 0, 'Euro', 'FRENCH GUIANA'),
	('FR', 'EUR', '€', 0, 'Euro', 'FRANCE'),
	('FI', 'EUR', '€', 0, 'Euro', 'FINLAND'),
	('VA', 'EUR', '€', 0, 'Euro', 'HOLY SEE (VATICAN CITY STATE)'),
	('YT', 'EUR', '€', 0, 'Euro', 'MAYOTTE'),
	('FK', 'FKP', '£', 0, 'Falkland Islands Pound', 'FALKLAND ISLANDS (MALVINAS)'),
	('FJ', 'FJD', '$', 0, 'Fiji Dollar', 'FIJI'),
	('HU', 'HUF', 'Ft', 0, 'Forint', 'HUNGARY'),
	('GH', 'GHS', '¢', 0, 'Ghana Cedi', 'GHANA'),
	('GI', 'GIP', '£', 0, 'Gibraltar Pound', 'GIBRALTAR'),
	('PY', 'PYG', 'Gs', 0, 'Guarani', 'PARAGUAY'),
	('GN', 'GNF', 'GF', 0, 'Guinea Franc', 'GUINEA'),
	('GY', 'GYD', '$', 0, 'Guyana Dollar', 'GUYANA'),
	('HK', 'HKD', '$', 0, 'Hong Kong Dollar', 'HONG KONG'),
	('UA', 'UAH', '₴', 0, 'Hryvnia', 'UKRAINE'),
	('IS', 'ISK', 'kr', 0, 'Iceland Krona', 'ICELAND'),
	('IN', 'INR', 'Rs', 0, 'Indian Rupee', 'INDIA'),
	('BT', 'INR', 'Rs', 0, 'Indian Rupee', 'BHUTAN'),
	('IR', 'IRR', '﷼', 0, 'Iranian Rial', 'IRAN, ISLAMIC REPUBLIC OF'),
	('IQ', 'IQD', 'ع.د', 0, 'Iraqi Dinar', 'IRAQ'),
	('JM', 'JMD', 'J$', 0, 'Jamaican Dollar', 'JAMAICA'),
	('JO', 'JOD', 'JD', 0, 'Jordanian Dinar', 'JORDAN'),
	('KE', 'KES', 'KSh', 0, 'Kenyan Shilling', 'KENYA'),
	('PG', 'PGK', 'K', 0, 'Kina', 'PAPUA NEW GUINEA'),
	('KW', 'KWD', 'د.ك', 0, 'Kuwaiti Dinar', 'KUWAIT'),
	('MW', 'MWK', 'MK', 0, 'Kwacha', 'MALAWI'),
	('AO', 'AOA', '', 0, 'Kwanza', 'ANGOLA'),
	('MM', 'MMK', 'K', 0, 'Kyat', 'MYANMAR'),
	('GE', 'GEL', 'ლ', 0, 'Lari', 'GEORGIA'),
	('LV', 'LVL', 'Ls', 0, 'Latvian Lats', 'LATVIA'),
	('LB', 'LBP', '£', 0, 'Lebanese Pound', 'LEBANON'),
	('AL', 'ALL', 'Lek', 0, 'Lek', 'ALBANIA'),
	('HN', 'HNL', 'L', 0, 'Lempira', 'HONDURAS'),
	('SL', 'SLL', 'Le', 0, 'Leone', 'SIERRA LEONE'),
	('LR', 'LRD', '$', 0, 'Liberian Dollar', 'LIBERIA'),
	('LY', 'LYD', '', 0, 'Libyan Dinar', 'LIBYA'),
	('SZ', 'SZL', 'E', 0, 'Lilangeni', 'SWAZILAND'),
	('LT', 'LTL', 'Lt', 0, 'Lithuanian Litas', 'LITHUANIA'),
	('MG', 'MGA', '', 0, 'Malagasy Ariary', 'MADAGASCAR'),
	('MY', 'MYR', 'RM', 0, 'Malaysian Ringgit', 'MALAYSIA'),
	('MU', 'MUR', '₨', 0, 'Mauritius Rupee', 'MAURITIUS'),
	('MX', 'MXV', '', 0, 'Mexican Unidad de Inversion (UDI)', 'MEXICO'),
	('MD', 'MDL', '', 0, 'Moldovan Leu', 'MOLDOVA, REPUBLIC OF'),
	('MA', 'MAD', '', 0, 'Moroccan Dirham', 'MOROCCO'),
	('EH', 'MAD', '', 0, 'Moroccan Dirham', 'WESTERN SAHARA'),
	('MZ', 'MZN', 'MT', 0, 'Mozambique Metical', 'MOZAMBIQUE'),
	('BO', 'BOV', '', 0, 'Mvdol', 'BOLIVIA, PLURINATIONAL STATE OF'),
	('NG', 'NGN', '₦', 0, 'Naira', 'NIGERIA'),
	('ER', 'ERN', 'Nfk', 0, 'Nakfa', 'ERITREA'),
	('NP', 'NPR', 'Rs', 0, 'Nepalese Rupee', 'NEPAL'),
	('SX', 'ANG', '', 0, 'Netherlands Antillean Guilder', 'SINT MAARTEN (DUTCH PART)'),
	('IL', 'ILS', '₪', 0, 'New Israeli Sheqel', 'ISRAEL'),
	('RO', 'RON', 'lei', 0, 'New Romanian Leu', 'ROMANIA'),
	('TW', 'TWD', 'NT$', 0, 'New Taiwan Dollar', 'TAIWAN, PROVINCE OF CHINA'),
	('NZ', 'NZD', '$', 0, 'New Zealand Dollar', 'NEW ZEALAND'),
	('NU', 'NZD', '$', 0, 'New Zealand Dollar', 'NIUE'),
	('TK', 'NZD', '$', 0, 'New Zealand Dollar', 'TOKELAU'),
	('CK', 'NZD', '', 0, 'New Zealand Dollar', 'COOK ISLANDS'),
	('PN', 'NZD', '$', 0, 'New Zealand Dollar', 'PITCAIRN'),
	('AQ', '', '', 0, 'No universal currency', 'ANTARCTICA'),
	('PS', '', '', 0, 'No universal currency', 'PALESTINIAN TERRITORY, OCCUPIED'),
	('GS', '', '', 0, 'No universal currency', 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS'),
	('NO', 'NOK', 'kr', 0, 'Norwegian Krone', 'NORWAY'),
	('BV', 'NOK', 'kr', 0, 'Norwegian Krone', 'BOUVET ISLAND'),
	('SJ', 'NOK', 'kr', 0, 'Norwegian Krone', 'SVALBARD AND JAN MAYEN'),
	('PE', 'PEN', 'S/.', 0, 'Nuevo Sol', 'PERU'),
	('MR', 'MRO', '', 0, 'Ouguiya', 'MAURITANIA'),
	('PK', 'PKR', '₨', 0, 'Pakistan Rupee', 'PAKISTAN'),
	('MO', 'MOP', '', 0, 'Pataca', 'MACAO'),
	('TO', 'TOP', 'T$', 0, 'Pa’anga', 'TONGA'),
	('UY', 'UYU', '$U', 0, 'Peso Uruguayo', 'URUGUAY'),
	('PH', 'PHP', '₱', 0, 'Philippine Peso', 'PHILIPPINES'),
	('GB', 'GBP', '£', 0, 'Pound Sterling', 'UNITED KINGDOM'),
	('JE', 'GBP', '£', 0, 'Pound Sterling', 'JERSEY'),
	('GG', 'GBP', '£', 0, 'Pound Sterling', 'GUERNSEY'),
	('IM', 'GBP', '£', 0, 'Pound Sterling', 'ISLE OF MAN'),
	('BW', 'BWP', 'P', 0, 'Pula', 'BOTSWANA'),
	('QA', 'QAR', '﷼', 0, 'Qatari Rial', 'QATAR'),
	('GT', 'GTQ', 'Q', 0, 'Quetzal', 'GUATEMALA'),
	('LS', 'ZAR', 'R', 0, 'Rand', 'LESOTHO'),
	('ZA', 'ZAR', 'R', 0, 'Rand', 'SOUTH AFRICA'),
	('NA', 'ZAR', 'R', 0, 'Rand', 'NAMIBIA'),
	('OM', 'OMR', '﷼', 0, 'Rial Omani', 'OMAN'),
	('KH', 'KHR', '៛', 0, 'Riel', 'CAMBODIA'),
	('MV', 'MVR', '', 0, 'Rufiyaa', 'MALDIVES'),
	('ID', 'IDR', 'Rp', 0, 'Rupiah', 'INDONESIA'),
	('RU', 'RUB', 'руб', 0, 'Russian Ruble', 'RUSSIAN FEDERATION'),
	('RW', 'RWF', 'FRw', 0, 'Rwanda Franc', 'RWANDA'),
	('SH', 'SHP', '£', 0, 'Saint Helena Pound', 'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA'),
	('SA', 'SAR', '﷼', 0, 'Saudi Riyal', 'SAUDI ARABIA'),
	('RS', 'RSD', 'Дин.', 0, 'Serbian Dinar', 'SERBIA'),
	('SC', 'SCR', '₨', 0, 'Seychelles Rupee', 'SEYCHELLES'),
	('SG', 'SGD', '$', 0, 'Singapore Dollar', 'SINGAPORE'),
	('SB', 'SBD', '$', 0, 'Solomon Islands Dollar', 'SOLOMON ISLANDS'),
	('KG', 'KGS', 'лв', 0, 'Som', 'KYRGYZSTAN'),
	('SO', 'SOS', 'S', 0, 'Somali Shilling', 'SOMALIA');

INSERT INTO `countrytable` (`countryCode`, `currencyCode`, `currencySymbol`, `currencyIsRight`, `currencyName`, `countryName`)
VALUES
	('TJ', 'TJS', '', 0, 'Somoni', 'TAJIKISTAN'),
	('SS', 'SSP', '£', 0, 'South Sudanese Pound', 'SOUTH SUDAN'),
	('LK', 'LKR', '₨', 0, 'Sri Lanka Rupee', 'SRI LANKA'),
	('SD', 'SDG', '£', 0, 'Sudanese Pound', 'SUDAN'),
	('SR', 'SRD', '$', 0, 'Surinam Dollar', 'SURINAME'),
	('SE', 'SEK', 'kr', 0, 'Swedish Krona', 'SWEDEN'),
	('LI', 'CHF', '', 0, 'Swiss Franc', 'LIECHTENSTEIN'),
	('SY', 'SYP', '£', 0, 'Syrian Pound', 'SYRIAN ARAB REPUBLIC'),
	('BD', 'BDT', '', 0, 'Taka', 'BANGLADESH'),
	('WS', 'WST', 'WS$', 0, 'Tala', 'SAMOA'),
	('TZ', 'TZS', 'TZS', 0, 'Tanzanian Shilling', 'TANZANIA, UNITED REPUBLIC OF'),
	('KZ', 'KZT', 'лв', 0, 'Tenge', 'KAZAKHSTAN'),
	('TT', 'TTD', 'TT$', 0, 'Trinidad and Tobago Dollar', 'TRINIDAD AND TOBAGO'),
	('MN', 'MNT', '₮', 0, 'Tugrik', 'MONGOLIA'),
	('TN', 'TND', 'د.ت', 0, 'Tunisian Dinar', 'TUNISIA'),
	('TR', 'TRY', 'TL', 0, 'Turkish Lira', 'TURKEY'),
	('TM', 'TMT', '', 0, 'Turkmenistan New Manat', 'TURKMENISTAN'),
	('AE', 'AED', '', 0, 'UAE Dirham', 'UNITED ARAB EMIRATES'),
	('UG', 'UGX', 'USh', 0, 'Uganda Shilling', 'UGANDA'),
	('UM', 'USD', '$', 0, 'US Dollar', 'UNITED STATES MINOR OUTLYING ISLANDS'),
	('VI', '', '$', 0, 'US Dollar', 'VIRGIN ISLANDS, U.S.'),
	('AS', 'USD', '$', 0, 'US Dollar', 'AMERICAN SAMOA'),
	('TL', 'USD', '$', 0, 'US Dollar', 'TIMOR-LESTE'),
	('BL', '', '$', 0, 'US Dollar', 'SAINT BARTHƒLEMY'),
	('VG', '', '$', 0, 'US Dollar', 'VIRGIN ISLANDS, BRITISH'),
	('AX', '', '$', 0, 'US Dollar', 'LAND ISLANDS'),
	('BQ', '', '$', 0, 'US Dollar', 'BONAIRE, SINT EUSTATIUS AND SABA'),
	('FM', 'USD', '$', 0, 'US Dollar', 'MICRONESIA, FEDERATED STATES OF'),
	('PR', 'USD', '$', 0, 'US Dollar', 'PUERTO RICO'),
	('EC', 'USD', '$', 0, 'US Dollar', 'ECUADOR'),
	('PA', 'USD', '$', 0, 'US Dollar', 'PANAMA'),
	('PW', 'USD', '$', 0, 'US Dollar', 'PALAU'),
	('CW', '', '$', 0, 'US Dollar', 'CURA‚AO'),
	('RE', '', '$', 0, 'US Dollar', 'RƒUNION'),
	('CO', '', '$', 0, 'US Dollar', 'COLOMBIA'),
	('MP', 'USD', '$', 0, 'US Dollar', 'NORTHERN MARIANA ISLANDS'),
	('HT', 'USD', '$', 0, 'US Dollar', 'HAITI'),
	('CD', '', '$', 0, 'US Dollar', 'CONGO, THE DEMOCRATIC REPUBLIC OF THE'),
	('TC', 'USD', '$', 0, 'US Dollar', 'TURKS AND CAICOS ISLANDS'),
	('MH', 'USD', '$', 0, 'US Dollar', 'MARSHALL ISLANDS'),
	('KM', '', '$', 0, 'US Dollar', 'COMOROS'),
	('SV', 'USD', '$', 0, 'US Dollar', 'EL SALVADOR'),
	('CG', '', '$', 0, 'US Dollar', 'CONGO'),
	('GU', 'USD', '$', 0, 'US Dollar', 'GUAM'),
	('IO', 'USD', '$', 0, 'US Dollar', 'BRITISH INDIAN OCEAN TERRITORY'),
	('US', 'USS', '$', 0, 'US Dollar (Same day)', 'UNITED STATES'),
	('UZ', 'UZS', 'лв', 0, 'Uzbekistan Sum', 'UZBEKISTAN'),
	('VU', 'VUV', '', 0, 'Vatu', 'VANUATU'),
	('CH', 'CHW', 'CWF', 0, 'WIR Franc', 'SWITZERLAND'),
	('KR', 'KRW', '₩', 0, 'Won', 'KOREA, REPUBLIC OF'),
	('YE', 'YER', '﷼', 0, 'Yemeni Rial', 'YEMEN'),
	('JP', 'JPY', '¥', 0, 'Yen', 'JAPAN'),
	('CN', 'CNY', '¥', 0, 'Yuan Renminbi', 'CHINA'),
	('ZM', 'ZMK', '', 0, 'Zambian Kwacha', 'ZAMBIA'),
	('ZW', 'ZWL', 'Z$', 0, 'Zimbabwe Dollar', 'ZIMBABWE'),
	('PL', 'PLN', 'zł', 0, 'Zloty', 'POLAND');



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

# Uncategorized is a special category. It is assumed to be present in the db with id = 1
INSERT INTO `shopitemcategories` (`categoryID`, `categoryName`, `userID_FK`, `categoryRank`)
VALUES
	(1, 'Uncategorized', 1, 1);