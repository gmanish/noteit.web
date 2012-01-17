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
	('AL', 'ALL', 'Lek', 0, 'Lek', 'ALBANIA'),
	('DZ', 'DZD', 'دج', 0, 'Algerian Dinar', 'ALGERIA'),
	('AS', 'USD', '$', 0, 'US Dollar', 'AMERICAN SAMOA'),
	('AD', 'EUR', '€', 0, 'Euro', 'ANDORRA'),
	('AO', 'AOA', '', 0, 'Kwanza', 'ANGOLA'),
	('AI', 'XCD', '$', 0, 'East Caribbean Dollar', 'ANGUILLA'),
	('AQ', '', '', 0, 'No universal currency', 'ANTARCTICA'),
	('AG', 'XCD', '$', 0, 'East Caribbean Dollar', 'ANTIGUA AND BARBUDA'),
	('AR', 'ARS', '$', 0, 'Argentine Peso', 'ARGENTINA'),
	('AM', 'AMD', '', 0, 'Armenian Dram', 'ARMENIA'),
	('AW', 'AWG', 'ƒ', 0, 'Aruban Florin', 'ARUBA'),
	('AU', 'AUD', '$', 0, 'Australian Dollar', 'AUSTRALIA'),
	('AT', 'EUR', '€', 0, 'Euro', 'AUSTRIA'),
	('AZ', 'AZN', 'ман', 0, 'Azerbaijanian Manat', 'AZERBAIJAN'),
	('BS', 'BSD', '$', 0, 'Bahamian Dollar', 'BAHAMAS'),
	('BH', 'BHD', '', 0, 'Bahraini Dinar', 'BAHRAIN'),
	('BD', 'BDT', '', 0, 'Taka', 'BANGLADESH'),
	('BB', 'BBD', '$', 0, 'Barbados Dollar', 'BARBADOS'),
	('BY', 'BYR', 'p.', 0, 'Belarussian Ruble', 'BELARUS'),
	('BE', 'EUR', '€', 0, 'Euro', 'BELGIUM'),
	('BZ', 'BZD', 'BZ$', 0, 'Belize Dollar', 'BELIZE'),
	('BJ', 'XOF', '', 0, 'CFA Franc BCEAO', 'BENIN'),
	('BM', 'BMD', '$', 0, 'Bermudian Dollar', 'BERMUDA'),
	('BT', 'INR', 'Rs', 0, 'Indian Rupee', 'BHUTAN'),
	('BO', 'BOV', '', 0, 'Mvdol', 'BOLIVIA, PLURINATIONAL STATE OF'),
	('BQ', '', '$', 0, 'US Dollar', 'BONAIRE, SINT EUSTATIUS AND SABA'),
	('BA', 'BAM', 'KM', 0, 'Convertible Mark', 'BOSNIA AND HERZEGOVINA'),
	('BW', 'BWP', 'P', 0, 'Pula', 'BOTSWANA'),
	('BV', 'NOK', 'kr', 0, 'Norwegian Krone', 'BOUVET ISLAND'),
	('BR', 'BRL', 'R$', 0, 'Brazilian Real', 'BRAZIL'),
	('IO', 'USD', '$', 0, 'US Dollar', 'BRITISH INDIAN OCEAN TERRITORY'),
	('BN', 'BND', '$', 0, 'Brunei Dollar', 'BRUNEI DARUSSALAM'),
	('BG', 'BGN', 'лв', 0, 'Bulgarian Lev', 'BULGARIA'),
	('BF', 'XOF', '', 0, 'CFA Franc BCEAO', 'BURKINA FASO'),
	('BI', 'BIF', 'FBu', 0, 'Burundi Franc', 'BURUNDI'),
	('KH', 'KHR', '៛', 0, 'Riel', 'CAMBODIA'),
	('CM', 'XAF', '', 0, 'CFA Franc BEAC', 'CAMEROON'),
	('CA', 'CAD', '$', 0, 'Canadian Dollar', 'CANADA'),
	('CV', 'CVE', 'CVE', 0, 'Cape Verde Escudo', 'CAPE VERDE'),
	('KY', 'KYD', '$', 0, 'Cayman Islands Dollar', 'CAYMAN ISLANDS'),
	('CF', 'XAF', 'CFA', 0, 'CFA Franc BEAC', 'CENTRAL AFRICAN REPUBLIC'),
	('TD', 'XAF', 'FCFA', 0, 'CFA Franc BEAC', 'CHAD'),
	('CL', 'CLP', '$', 0, 'Chilean Peso', 'CHILE'),
	('CN', 'CNY', '¥', 0, 'Yuan Renminbi', 'CHINA'),
	('CX', 'AUD', '$', 0, 'Australian Dollar', 'CHRISTMAS ISLAND'),
	('CC', 'AUD', '$', 0, 'Australian Dollar', 'COCOS (KEELING) ISLANDS'),
	('CO', '', '$', 0, 'US Dollar', 'COLOMBIA'),
	('KM', '', '$', 0, 'US Dollar', 'COMOROS'),
	('CG', '', '$', 0, 'US Dollar', 'CONGO'),
	('CD', '', '$', 0, 'US Dollar', 'CONGO, THE DEMOCRATIC REPUBLIC OF THE'),
	('CK', 'NZD', '', 0, ' New Zealand Dollar', 'COOK ISLANDS'),
	('CR', 'CRC', '₡', 0, ' Costa Rican Colon', 'COSTA RICA'),
	('HR', 'HRK', 'kn', 0, 'Croatian Kuna', 'CROATIA'),
	('CU', 'CUP', '₱', 0, 'Cuban Peso', 'CUBA'),
	('CW', '', '$', 0, 'US Dollar', 'CURA‚AO'),
	('CY', 'EUR', '€', 0, 'Euro', 'CYPRUS'),
	('CZ', 'CZK', 'Kč', 0, 'Czech Koruna', 'CZECH REPUBLIC'),
	('DK', 'DKK', 'kr', 0, 'Danish Krone', 'DENMARK'),
	('DJ', 'DJF', 'DJF', 0, 'Djibouti Franc', 'DJIBOUTI'),
	('DM', 'XCD', '$', 0, 'East Caribbean Dollar', 'DOMINICA'),
	('DO', 'DOP', 'RD$', 0, 'Dominican Peso', 'DOMINICAN REPUBLIC'),
	('EC', 'USD', '$', 0, 'US Dollar', 'ECUADOR'),
	('EG', 'EGP', '£', 0, 'Egyptian Pound', 'EGYPT'),
	('SV', 'USD', '$', 0, 'US Dollar', 'EL SALVADOR'),
	('GQ', 'XAF', '', 0, 'CFA Franc BEAC', 'EQUATORIAL GUINEA'),
	('ER', 'ERN', 'Nfk', 0, 'Nakfa', 'ERITREA'),
	('EE', 'EUR', '€', 0, 'Euro', 'ESTONIA'),
	('ET', 'ETB', 'Br', 0, 'Ethiopian Birr', 'ETHIOPIA'),
	('FK', 'FKP', '£', 0, 'Falkland Islands Pound', 'FALKLAND ISLANDS (MALVINAS)'),
	('FO', 'DKK', 'kr', 0, 'Danish Krone', 'FAROE ISLANDS'),
	('FJ', 'FJD', '$', 0, 'Fiji Dollar', 'FIJI'),
	('FI', 'EUR', '€', 0, 'Euro', 'FINLAND'),
	('FR', 'EUR', '€', 0, 'Euro', 'FRANCE'),
	('GF', 'EUR', '€', 0, 'Euro', 'FRENCH GUIANA'),
	('PF', 'XPF', '', 0, 'CFP Franc', 'FRENCH POLYNESIA'),
	('TF', 'EUR', '€', 0, 'Euro', 'FRENCH SOUTHERN TERRITORIES'),
	('GA', 'XAF', '', 0, 'CFA Franc BEAC', 'GABON'),
	('GM', 'GMD', 'D', 0, 'Dalasi', 'GAMBIA'),
	('GE', 'GEL', 'ლ', 0, 'Lari', 'GEORGIA'),
	('DE', 'EUR', '€', 0, 'Euro', 'GERMANY'),
	('GH', 'GHS', '¢', 0, 'Ghana Cedi', 'GHANA'),
	('GI', 'GIP', '£', 0, 'Gibraltar Pound', 'GIBRALTAR'),
	('GR', 'EUR', '€', 0, 'Euro', 'GREECE'),
	('GL', 'DKK', 'kr', 0, 'Danish Krone', 'GREENLAND'),
	('GD', 'XCD', '$', 0, 'East Caribbean Dollar', 'GRENADA'),
	('GP', 'EUR', '€', 0, 'Euro', 'GUADELOUPE'),
	('GU', 'USD', '$', 0, 'US Dollar', 'GUAM'),
	('GT', 'GTQ', 'Q', 0, 'Quetzal', 'GUATEMALA'),
	('GG', 'GBP', '£', 0, 'Pound Sterling', 'GUERNSEY'),
	('GN', 'GNF', 'GF', 0, 'Guinea Franc', 'GUINEA'),
	('GW', 'XOF', '', 0, 'CFA Franc BCEAO', 'GUINEA-BISSAU'),
	('GY', 'GYD', '$', 0, 'Guyana Dollar', 'GUYANA'),
	('HT', 'USD', '$', 0, 'US Dollar', 'HAITI'),
	('HM', 'AUD', '$', 0, 'Australian Dollar', 'HEARD ISLAND AND MCDONALD ISLANDS'),
	('VA', 'EUR', '€', 0, 'Euro', 'HOLY SEE (VATICAN CITY STATE)');

INSERT INTO `countrytable` (`countryCode`, `currencyCode`, `currencySymbol`, `currencyIsRight`, `currencyName`, `countryName`)
VALUES
	('HN', 'HNL', 'L', 0, 'Lempira', 'HONDURAS'),
	('HK', 'HKD', '$', 0, 'Hong Kong Dollar', 'HONG KONG'),
	('HU', 'HUF', 'Ft', 0, 'Forint', 'HUNGARY'),
	('IS', 'ISK', 'kr', 0, 'Iceland Krona', 'ICELAND'),
	('IN', 'INR', 'Rs', 0, 'Indian Rupee', 'INDIA'),
	('ID', 'IDR', 'Rp', 0, 'Rupiah', 'INDONESIA'),
	('IR', 'IRR', '﷼', 0, 'Iranian Rial', 'IRAN, ISLAMIC REPUBLIC OF'),
	('IQ', 'IQD', 'ع.د', 0, 'Iraqi Dinar', 'IRAQ'),
	('IE', 'EUR', '€', 0, 'Euro', 'IRELAND'),
	('IM', 'GBP', '£', 0, 'Pound Sterling', 'ISLE OF MAN'),
	('IL', 'ILS', '₪', 0, 'New Israeli Sheqel', 'ISRAEL'),
	('IT', 'EUR', '€', 0, 'Euro', 'ITALY'),
	('JM', 'JMD', 'J$', 0, 'Jamaican Dollar', 'JAMAICA'),
	('JP', 'JPY', '¥', 0, 'Yen', 'JAPAN'),
	('JE', 'GBP', '£', 0, 'Pound Sterling', 'JERSEY'),
	('JO', 'JOD', 'JD', 0, 'Jordanian Dinar', 'JORDAN'),
	('KZ', 'KZT', 'лв', 0, 'Tenge', 'KAZAKHSTAN'),
	('KE', 'KES', 'KSh', 0, 'Kenyan Shilling', 'KENYA'),
	('KI', 'AUD', '$', 0, 'Australian Dollar', 'KIRIBATI'),
	('KR', 'KRW', '₩', 0, 'Won', 'KOREA, REPUBLIC OF'),
	('KW', 'KWD', 'د.ك', 0, 'Kuwaiti Dinar', 'KUWAIT'),
	('KG', 'KGS', 'лв', 0, 'Som', 'KYRGYZSTAN'),
	('LV', 'LVL', 'Ls', 0, 'Latvian Lats', 'LATVIA'),
	('LB', 'LBP', '£', 0, 'Lebanese Pound', 'LEBANON'),
	('LS', 'ZAR', 'R', 0, 'Rand', 'LESOTHO'),
	('LR', 'LRD', '$', 0, 'Liberian Dollar', 'LIBERIA'),
	('LY', 'LYD', '', 0, 'Libyan Dinar', 'LIBYA'),
	('LI', 'CHF', '', 0, 'Swiss Franc', 'LIECHTENSTEIN'),
	('LT', 'LTL', 'Lt', 0, 'Lithuanian Litas', 'LITHUANIA'),
	('LU', 'EUR', '€', 0, 'Euro', 'LUXEMBOURG'),
	('MO', 'MOP', '', 0, 'Pataca', 'MACAO'),
	('MK', 'MKD', 'ден', 0, 'Denar', 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF'),
	('MG', 'MGA', '', 0, 'Malagasy Ariary', 'MADAGASCAR'),
	('MW', 'MWK', 'MK', 0, 'Kwacha', 'MALAWI'),
	('MY', 'MYR', 'RM', 0, 'Malaysian Ringgit', 'MALAYSIA'),
	('MV', 'MVR', '', 0, 'Rufiyaa', 'MALDIVES'),
	('ML', 'XOF', '', 0, 'CFA Franc BCEAO', 'MALI'),
	('MT', 'EUR', '€', 0, 'Euro', 'MALTA'),
	('MH', 'USD', '$', 0, 'US Dollar', 'MARSHALL ISLANDS'),
	('MQ', 'EUR', '€', 0, 'Euro', 'MARTINIQUE'),
	('MR', 'MRO', '', 0, 'Ouguiya', 'MAURITANIA'),
	('MU', 'MUR', '₨', 0, 'Mauritius Rupee', 'MAURITIUS'),
	('YT', 'EUR', '€', 0, 'Euro', 'MAYOTTE'),
	('MX', 'MXV', '', 0, 'Mexican Unidad de Inversion (UDI)', 'MEXICO'),
	('FM', 'USD', '$', 0, 'US Dollar', 'MICRONESIA, FEDERATED STATES OF'),
	('MD', 'MDL', '', 0, 'Moldovan Leu', 'MOLDOVA, REPUBLIC OF'),
	('MC', 'EUR', '€', 0, 'Euro', 'MONACO'),
	('MN', 'MNT', '₮', 0, 'Tugrik', 'MONGOLIA'),
	('ME', 'EUR', '€', 0, 'Euro', 'MONTENEGRO'),
	('MS', 'XCD', '$', 0, 'East Caribbean Dollar', 'MONTSERRAT'),
	('MA', 'MAD', '', 0, 'Moroccan Dirham', 'MOROCCO'),
	('MZ', 'MZN', 'MT', 0, 'Mozambique Metical', 'MOZAMBIQUE'),
	('MM', 'MMK', 'K', 0, 'Kyat', 'MYANMAR'),
	('NA', 'ZAR', 'R', 0, 'Rand', 'NAMIBIA'),
	('NR', 'AUD', '$', 0, 'Australian Dollar', 'NAURU'),
	('NP', 'NPR', 'Rs', 0, 'Nepalese Rupee', 'NEPAL'),
	('NL', 'EUR', '€', 0, 'Euro', 'NETHERLANDS'),
	('NC', 'XPF', '', 0, 'CFP Franc', 'NEW CALEDONIA'),
	('NZ', 'NZD', '$', 0, 'New Zealand Dollar', 'NEW ZEALAND'),
	('NI', 'NIO', 'C$', 0, 'Cordoba Oro', 'NICARAGUA'),
	('NE', 'XOF', '', 0, 'CFA Franc BCEAO', 'NIGER'),
	('NG', 'NGN', '₦', 0, 'Naira', 'NIGERIA'),
	('NU', 'NZD', '$', 0, 'New Zealand Dollar', 'NIUE'),
	('NF', 'AUD', '$', 0, 'Australian Dollar', 'NORFOLK ISLAND'),
	('MP', 'USD', '$', 0, 'US Dollar', 'NORTHERN MARIANA ISLANDS'),
	('NO', 'NOK', 'kr', 0, 'Norwegian Krone', 'NORWAY'),
	('OM', 'OMR', '﷼', 0, 'Rial Omani', 'OMAN'),
	('PK', 'PKR', '₨', 0, 'Pakistan Rupee', 'PAKISTAN'),
	('PW', 'USD', '$', 0, 'US Dollar', 'PALAU'),
	('PS', '', '', 0, 'No universal currency', 'PALESTINIAN TERRITORY, OCCUPIED'),
	('PA', 'USD', '$', 0, 'US Dollar', 'PANAMA'),
	('PG', 'PGK', 'K', 0, 'Kina', 'PAPUA NEW GUINEA'),
	('PY', 'PYG', 'Gs', 0, 'Guarani', 'PARAGUAY'),
	('PE', 'PEN', 'S/.', 0, 'Nuevo Sol', 'PERU'),
	('PH', 'PHP', '₱', 0, 'Philippine Peso', 'PHILIPPINES'),
	('PN', 'NZD', '$', 0, 'New Zealand Dollar', 'PITCAIRN'),
	('PL', 'PLN', 'zł', 0, 'Zloty', 'POLAND'),
	('PT', 'EUR', '€', 0, 'Euro', 'PORTUGAL'),
	('PR', 'USD', '$', 0, 'US Dollar', 'PUERTO RICO'),
	('QA', 'QAR', '﷼', 0, 'Qatari Rial', 'QATAR'),
	('RO', 'RON', 'lei', 0, 'New Romanian Leu', 'ROMANIA'),
	('RU', 'RUB', 'руб', 0, 'Russian Ruble', 'RUSSIAN FEDERATION'),
	('RW', 'RWF', 'FRw', 0, 'Rwanda Franc', 'RWANDA'),
	('RE', '', '$', 0, 'US Dollar', 'RƒUNION'),
	('BL', '', '$', 0, 'US Dollar', 'SAINT BARTHƒLEMY'),
	('SH', 'SHP', '£', 0, 'Saint Helena Pound', 'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA'),
	('KN', 'XCD', '$', 0, 'East Caribbean Dollar', 'SAINT KITTS AND NEVIS'),
	('LC', 'XCD', '$', 0, 'East Caribbean Dollar', 'SAINT LUCIA'),
	('MF', 'EUR', '€', 0, 'Euro', 'SAINT MARTIN (FRENCH PART)'),
	('PM', 'EUR', '€', 0, 'Euro', 'SAINT PIERRE AND MIQUELON'),
	('VC', 'XCD', '$', 0, 'East Caribbean Dollar', 'SAINT VINCENT AND THE GRENADINES'),
	('WS', 'WST', 'WS$', 0, 'Tala', 'SAMOA'),
	('SM', 'EUR', '€', 0, 'Euro', 'SAN MARINO'),
	('ST', 'STD', 'Db', 0, 'Dobra', 'SAO TOME AND PRINCIPE'),
	('SA', 'SAR', '﷼', 0, 'Saudi Riyal', 'SAUDI ARABIA'),
	('SN', 'XOF', '', 0, 'CFA Franc BCEAO', 'SENEGAL'),
	('RS', 'RSD', 'Дин.', 0, 'Serbian Dinar', 'SERBIA'),
	('SC', 'SCR', '₨', 0, 'Seychelles Rupee', 'SEYCHELLES');

INSERT INTO `countrytable` (`countryCode`, `currencyCode`, `currencySymbol`, `currencyIsRight`, `currencyName`, `countryName`)
VALUES
	('SL', 'SLL', 'Le', 0, 'Leone', 'SIERRA LEONE'),
	('SG', 'SGD', '$', 0, 'Singapore Dollar', 'SINGAPORE'),
	('SX', 'ANG', '', 0, 'Netherlands Antillean Guilder', 'SINT MAARTEN (DUTCH PART)'),
	('SK', 'EUR', '€', 0, 'Euro', 'SLOVAKIA'),
	('SI', 'EUR', '€', 0, 'Euro', 'SLOVENIA'),
	('SB', 'SBD', '$', 0, 'Solomon Islands Dollar', 'SOLOMON ISLANDS'),
	('SO', 'SOS', 'S', 0, 'Somali Shilling', 'SOMALIA'),
	('ZA', 'ZAR', 'R', 0, 'Rand', 'SOUTH AFRICA'),
	('GS', '', '', 0, 'No universal currency', 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS'),
	('SS', 'SSP', '£', 0, 'South Sudanese Pound', 'SOUTH SUDAN'),
	('ES', 'EUR', '€', 0, 'Euro', 'SPAIN'),
	('LK', 'LKR', '₨', 0, 'Sri Lanka Rupee', 'SRI LANKA'),
	('SD', 'SDG', '£', 0, 'Sudanese Pound', 'SUDAN'),
	('SR', 'SRD', '$', 0, 'Surinam Dollar', 'SURINAME'),
	('SJ', 'NOK', 'kr', 0, 'Norwegian Krone', 'SVALBARD AND JAN MAYEN'),
	('SZ', 'SZL', 'E', 0, 'Lilangeni', 'SWAZILAND'),
	('SE', 'SEK', 'kr', 0, 'Swedish Krona', 'SWEDEN'),
	('CH', 'CHW', 'CWF', 0, 'WIR Franc', 'SWITZERLAND'),
	('SY', 'SYP', '£', 0, 'Syrian Pound', 'SYRIAN ARAB REPUBLIC'),
	('TW', 'TWD', 'NT$', 0, 'New Taiwan Dollar', 'TAIWAN, PROVINCE OF CHINA'),
	('TJ', 'TJS', '', 0, 'Somoni', 'TAJIKISTAN'),
	('TZ', 'TZS', 'TZS', 0, 'Tanzanian Shilling', 'TANZANIA, UNITED REPUBLIC OF'),
	('TH', 'THB', '฿', 0, 'Baht', 'THAILAND'),
	('TL', 'USD', '$', 0, 'US Dollar', 'TIMOR-LESTE'),
	('TG', 'XOF', '', 0, 'CFA Franc BCEAO', 'TOGO'),
	('TK', 'NZD', '$', 0, 'New Zealand Dollar', 'TOKELAU'),
	('TO', 'TOP', 'T$', 0, 'Pa’anga', 'TONGA'),
	('TT', 'TTD', 'TT$', 0, 'Trinidad and Tobago Dollar', 'TRINIDAD AND TOBAGO'),
	('TN', 'TND', 'د.ت', 0, 'Tunisian Dinar', 'TUNISIA'),
	('TR', 'TRY', 'TL', 0, 'Turkish Lira', 'TURKEY'),
	('TM', 'TMT', '', 0, 'Turkmenistan New Manat', 'TURKMENISTAN'),
	('TC', 'USD', '$', 0, 'US Dollar', 'TURKS AND CAICOS ISLANDS'),
	('TV', 'AUD', '$', 0, 'Australian Dollar', 'TUVALU'),
	('UG', 'UGX', 'USh', 0, 'Uganda Shilling', 'UGANDA'),
	('UA', 'UAH', '₴', 0, 'Hryvnia', 'UKRAINE'),
	('AE', 'AED', '', 0, 'UAE Dirham', 'UNITED ARAB EMIRATES'),
	('GB', 'GBP', '£', 0, 'Pound Sterling', 'UNITED KINGDOM'),
	('US', 'USS', '$', 0, 'US Dollar (Same day)', 'UNITED STATES'),
	('UM', 'USD', '$', 0, 'US Dollar', 'UNITED STATES MINOR OUTLYING ISLANDS'),
	('UY', 'UYU', '$U', 0, 'Peso Uruguayo', 'URUGUAY'),
	('UZ', 'UZS', 'лв', 0, 'Uzbekistan Sum', 'UZBEKISTAN'),
	('VU', 'VUV', '', 0, 'Vatu', 'VANUATU'),
	('VE', 'VEF', 'Bs', 0, 'Bolivar Fuerte', 'VENEZUELA, BOLIVARIAN REPUBLIC OF'),
	('VN', 'VND', '₫', 0, 'Dong', 'VIET NAM'),
	('VG', '', '$', 0, 'US Dollar', 'VIRGIN ISLANDS, BRITISH'),
	('VI', '', '$', 0, 'US Dollar', 'VIRGIN ISLANDS, U.S.'),
	('WF', 'XPF', '', 0, 'CFP Franc', 'WALLIS AND FUTUNA'),
	('EH', 'MAD', '', 0, 'Moroccan Dirham', 'WESTERN SAHARA'),
	('YE', 'YER', '﷼', 0, 'Yemeni Rial', 'YEMEN'),
	('ZM', 'ZMK', '', 0, 'Zambian Kwacha', 'ZAMBIA'),
	('ZW', 'ZWL', 'Z$', 0, 'Zimbabwe Dollar', 'ZIMBABWE'),
	('AX', '', '$', 0, 'US Dollar', 'LAND ISLANDS');


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
