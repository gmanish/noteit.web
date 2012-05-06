CREATE DATABASE IF NOT EXISTS `noteitdb` CHARSET = utf8;
USE noteitdb;

# -----------------------------------------------------------------
# Create the countrytable
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `countrytable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `countryCode` varchar(2) NOT NULL DEFAULT '',
  `countryName` varchar(50) DEFAULT NULL,
  `currencyid` smallint(2) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countryCurrency_UNIQUE` (`countryCode`,`currencyid`,`countryName`),
  KEY `Ref_CurrencyTable_CurrencyID` (`currencyid`),
  CONSTRAINT `Ref_CurrencyTable_CurrencyID` FOREIGN KEY (`currencyid`) REFERENCES `currencytable` (`currencyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the currencytable
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `currencytable` (
  `currencyid` smallint(2) unsigned NOT NULL AUTO_INCREMENT,
  `currencyCode` varchar(3) NOT NULL DEFAULT '',
  `currencySymbol` varchar(4) NOT NULL DEFAULT '',
  `currencyIsRight` tinyint(1) DEFAULT NULL,
  `currencyName` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`currencyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the units table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `units` (
  `unitID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unitName` varchar(20) NOT NULL COMMENT 'Full text of the unit name e.g. inches, feet, kilo gram',
  `unitAbbreviation` varchar(10) NOT NULL COMMENT 'short text e.g. in, ft, kg etc',
  `unitType` tinyint(3) unsigned zerofill NOT NULL COMMENT 'Type could be general, metric, imperial (etc -TBD)\\n0 -  general (applies to all unit types) \\n1 - metric\\n2 - imperial',
  PRIMARY KEY (`unitID`),
  UNIQUE KEY `unitName_UNIQUE` (`unitName`),
  UNIQUE KEY `unitAbbreviation_UNIQUE` (`unitAbbreviation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the shoplists table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `emailID` varchar(254) NOT NULL,
  `firstName` varchar(25) DEFAULT NULL,
  `lastName` varchar(25) DEFAULT NULL,
  `userPassword` binary(20) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `currencyId` smallint(2) unsigned NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `userID_UNIQUE` (`userID`),
  UNIQUE KEY `emailID_UNIQUE` (`emailID`),
  KEY `Ref_Currency_Id` (`currencyId`),
  CONSTRAINT `Ref_Currency_Id` FOREIGN KEY (`currencyId`) REFERENCES `currencytable` (`currencyid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the shoplists table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shoplists` (
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
CREATE TABLE IF NOT EXISTS `shopitemscatalog` (
  `itemID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `itemName` varchar(50) NOT NULL,
  `itemBarcode` varchar(15) DEFAULT '',
  `itemBarcodeFormat` tinyint(1) DEFAULT '1' COMMENT 'const BARCODE_FORMAT_UNKNOWN 	= 1; \nconst BARCODE_FORMAT_UPC_A	= 2;\nconst BARCODE_FORMAT_UPC_E	= 3;\nconst BARCODE_FORMAT_EAN_8	= 4;\nconst BARCODE_FORMAT_EAN_13	= 5;\nconst BARCODE_FORMAT_RSS_14	= 6',
  PRIMARY KEY (`itemID`),
  UNIQUE KEY `item_UNIQUE` (`itemBarcode`,`itemBarcodeFormat`,`itemName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the `shopitems` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitems` (
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

# -----------------------------------------------------------------
# Create the `shopitems_metadata` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitems_metadata` (
  `itemId_FK` int(11) unsigned NOT NULL,
  `userId_FK` int(11) unsigned NOT NULL,
  `vote` tinyint(4) DEFAULT NULL,
  `date_voted` datetime DEFAULT NULL,
  PRIMARY KEY (`itemId_FK`,`userId_FK`),
  KEY `Ref_UserId_FKA` (`userId_FK`),
  CONSTRAINT `Ref_itemId_FK` FOREIGN KEY (`itemId_FK`) REFERENCES `shopitemscatalog` (`itemID`),
  CONSTRAINT `Ref_UserId_FKA` FOREIGN KEY (`userId_FK`) REFERENCES `users` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Create the `shopitems_price` table
# -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shopitems_price` (
  `classID_FK` int(11) unsigned NOT NULL,
  `currencyCode_FK` varchar(3) NOT NULL,
  `unitID_FK` int(10) unsigned NOT NULL,
  `date_added` date NOT NULL,
  `itemPrice` decimal(11,2) unsigned NOT NULL,
  PRIMARY KEY (`classID_FK`,`currencyCode_FK`,`unitID_FK`,`date_added`),
  UNIQUE KEY `Ref_UNIQUE` (`classID_FK`,`currencyCode_FK`,`unitID_FK`,`date_added`),
  KEY `SIC_Ref_itemID` (`classID_FK`),
  KEY `CT_Ref_currencyCode` (`currencyCode_FK`),
  KEY `UNITS_Ref_unitID` (`unitID_FK`),
  CONSTRAINT `SIC_Ref_itemID` FOREIGN KEY (`classID_FK`) REFERENCES `shopitemscatalog` (`itemID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `UNITS_Ref_unitID` FOREIGN KEY (`unitID_FK`) REFERENCES `units` (`unitID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# -----------------------------------------------------------------
# Insert Countries And Currencies
# -----------------------------------------------------------------
LOCK TABLES `countrytable` WRITE;
/*!40000 ALTER TABLE `countrytable` DISABLE KEYS */;

INSERT INTO `countrytable` (`id`, `countryCode`, `countryName`, `currencyid`)
VALUES
	(64,'AD','ANDORRA',42),
	(15,'AE','UNITED ARAB EMIRATES',2),
	(16,'AF','AFGHANISTAN',3),
	(223,'AG','ANTIGUA AND BARBUDA',144),
	(224,'AI','ANGUILLA',144),
	(17,'AL','ALBANIA',4),
	(18,'AM','ARMENIA',5),
	(20,'AO','ANGOLA',7),
	(1,'AQ','ANTARCTICA',1),
	(21,'AR','ARGENTINA',8),
	(196,'AS','AMERICAN SAMOA',135),
	(65,'AT','AUSTRIA',42),
	(22,'AU','AUSTRALIA',9),
	(30,'AW','ARUBA',10),
	(2,'AX','LAND ISLANDS',135),
	(31,'AZ','AZERBAIJAN',11),
	(32,'BA','BOSNIA AND HERZEGOVINA',12),
	(33,'BB','BARBADOS',13),
	(34,'BD','BANGLADESH',14),
	(66,'BE','BELGIUM',42),
	(231,'BF','BURKINA FASO',145),
	(35,'BG','BULGARIA',15),
	(36,'BH','BAHRAIN',16),
	(37,'BI','BURUNDI',17),
	(232,'BJ','BENIN',145),
	(3,'BL','SAINT BARTHƒLEMY',135),
	(38,'BM','BERMUDA',18),
	(39,'BN','BRUNEI DARUSSALAM',19),
	(40,'BO','BOLIVIA, PLURINATIONAL STATE OF',20),
	(4,'BQ','BONAIRE, SINT EUSTATIUS AND SABA',135),
	(41,'BR','BRAZIL',21),
	(42,'BS','BAHAMAS',22),
	(112,'BT','BHUTAN',59),
	(150,'BV','BOUVET ISLAND',95),
	(43,'BW','BOTSWANA',23),
	(44,'BY','BELARUS',24),
	(45,'BZ','BELIZE',25),
	(46,'CA','CANADA',26),
	(23,'CC','COCOS (KEELING) ISLANDS',9),
	(5,'CD','CONGO, THE DEMOCRATIC REPUBLIC OF THE',135),
	(218,'CF','CENTRAL AFRICAN REPUBLIC',143),
	(6,'CG','CONGO',135),
	(48,'CH','SWITZERLAND',28),
	(154,'CK','COOK ISLANDS',97),
	(49,'CL','CHILE',29),
	(219,'CM','CAMEROON',143),
	(50,'CN','CHINA',30),
	(7,'CO','COLOMBIA',135),
	(51,'CR','COSTA RICA',31),
	(52,'CU','CUBA',32),
	(53,'CV','CAPE VERDE',33),
	(8,'CW','CURA‚AO',135),
	(24,'CX','CHRISTMAS ISLAND',9),
	(67,'CY','CYPRUS',42),
	(54,'CZ','CZECH REPUBLIC',34),
	(68,'DE','GERMANY',42),
	(55,'DJ','DJIBOUTI',35),
	(56,'DK','DENMARK',36),
	(225,'DM','DOMINICA',144),
	(59,'DO','DOMINICAN REPUBLIC',37),
	(60,'DZ','ALGERIA',38),
	(197,'EC','ECUADOR',135),
	(69,'EE','ESTONIA',42),
	(61,'EG','EGYPT',39),
	(133,'EH','WESTERN SAHARA',79),
	(62,'ER','ERITREA',40),
	(70,'ES','SPAIN',42),
	(63,'ET','ETHIOPIA',41),
	(71,'FI','FINLAND',42),
	(93,'FJ','FIJI',43),
	(94,'FK','FALKLAND ISLANDS (MALVINAS)',44),
	(198,'FM','MICRONESIA, FEDERATED STATES OF',135),
	(57,'FO','FAROE ISLANDS',36),
	(72,'FR','FRANCE',42),
	(220,'GA','GABON',143),
	(95,'GB','UNITED KINGDOM',45),
	(226,'GD','GRENADA',144),
	(99,'GE','GEORGIA',46),
	(73,'GF','FRENCH GUIANA',42),
	(96,'GG','GUERNSEY',45),
	(100,'GH','GHANA',47),
	(101,'GI','GIBRALTAR',48),
	(58,'GL','GREENLAND',36),
	(102,'GM','GAMBIA',49),
	(103,'GN','GUINEA',50),
	(74,'GP','GUADELOUPE',42),
	(221,'GQ','EQUATORIAL GUINEA',143),
	(75,'GR','GREECE',42),
	(9,'GS','SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',1),
	(104,'GT','GUATEMALA',51),
	(199,'GU','GUAM',135),
	(233,'GW','GUINEA-BISSAU',145),
	(105,'GY','GUYANA',52),
	(106,'HK','HONG KONG',53),
	(25,'HM','HEARD ISLAND AND MCDONALD ISLANDS',9),
	(107,'HN','HONDURAS',54),
	(108,'HR','CROATIA',55),
	(200,'HT','HAITI',135),
	(109,'HU','HUNGARY',56),
	(110,'ID','INDONESIA',57),
	(76,'IE','IRELAND',42),
	(111,'IL','ISRAEL',58),
	(97,'IM','ISLE OF MAN',45),
	(113,'IN','INDIA',59),
	(201,'IO','BRITISH INDIAN OCEAN TERRITORY',135),
	(114,'IQ','IRAQ',60),
	(115,'IR','IRAN, ISLAMIC REPUBLIC OF',61),
	(116,'IS','ICELAND',62),
	(77,'IT','ITALY',42),
	(98,'JE','JERSEY',45),
	(117,'JM','JAMAICA',63),
	(118,'JO','JORDAN',64),
	(119,'JP','JAPAN',65),
	(120,'KE','KENYA',66),
	(121,'KG','KYRGYZSTAN',67),
	(122,'KH','CAMBODIA',68),
	(26,'KI','KIRIBATI',9),
	(10,'KM','COMOROS',135),
	(227,'KN','SAINT KITTS AND NEVIS',144),
	(123,'KR','KOREA, REPUBLIC OF',69),
	(124,'KW','KUWAIT',70),
	(125,'KY','CAYMAN ISLANDS',71),
	(126,'KZ','KAZAKHSTAN',72),
	(127,'LB','LEBANON',73),
	(228,'LC','SAINT LUCIA',144),
	(47,'LI','LIECHTENSTEIN',27),
	(128,'LK','SRI LANKA',74),
	(129,'LR','LIBERIA',75),
	(242,'LS','LESOTHO',148),
	(130,'LT','LITHUANIA',76),
	(78,'LU','LUXEMBOURG',42),
	(131,'LV','LATVIA',77),
	(132,'LY','LIBYA',78),
	(134,'MA','MOROCCO',79),
	(79,'MC','MONACO',42),
	(135,'MD','MOLDOVA, REPUBLIC OF',80),
	(80,'ME','MONTENEGRO',42),
	(81,'MF','SAINT MARTIN (FRENCH PART)',42),
	(136,'MG','MADAGASCAR',81),
	(202,'MH','MARSHALL ISLANDS',135),
	(137,'MK','MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF',82),
	(234,'ML','MALI',145),
	(138,'MM','MYANMAR',83),
	(139,'MN','MONGOLIA',84),
	(140,'MO','MACAO',85),
	(203,'MP','NORTHERN MARIANA ISLANDS',135),
	(82,'MQ','MARTINIQUE',42),
	(141,'MR','MAURITANIA',86),
	(229,'MS','MONTSERRAT',144),
	(83,'MT','MALTA',42),
	(142,'MU','MAURITIUS',87),
	(143,'MV','MALDIVES',88),
	(144,'MW','MALAWI',89),
	(145,'MX','MEXICO',90),
	(146,'MY','MALAYSIA',91),
	(147,'MZ','MOZAMBIQUE',92),
	(243,'NA','NAMIBIA',148),
	(238,'NC','NEW CALEDONIA',146),
	(235,'NE','NIGER',145),
	(27,'NF','NORFOLK ISLAND',9),
	(148,'NG','NIGERIA',93),
	(149,'NI','NICARAGUA',94),
	(84,'NL','NETHERLANDS',42),
	(151,'NO','NORWAY',95),
	(153,'NP','NEPAL',96),
	(28,'NR','NAURU',9),
	(155,'NU','NIUE',97),
	(156,'NZ','NEW ZEALAND',97),
	(159,'OM','OMAN',98),
	(204,'PA','PANAMA',135),
	(160,'PE','PERU',99),
	(239,'PF','FRENCH POLYNESIA',146),
	(161,'PG','PAPUA NEW GUINEA',100),
	(162,'PH','PHILIPPINES',101),
	(163,'PK','PAKISTAN',102),
	(164,'PL','POLAND',103),
	(85,'PM','SAINT PIERRE AND MIQUELON',42),
	(157,'PN','PITCAIRN',97),
	(205,'PR','PUERTO RICO',135),
	(11,'PS','PALESTINIAN TERRITORY, OCCUPIED',1),
	(86,'PT','PORTUGAL',42),
	(206,'PW','PALAU',135),
	(165,'PY','PARAGUAY',104),
	(166,'QA','QATAR',105),
	(12,'RE','RƒUNION',135),
	(167,'RO','ROMANIA',106),
	(168,'RS','SERBIA',107),
	(169,'RU','RUSSIAN FEDERATION',108),
	(170,'RW','RWANDA',109),
	(171,'SA','SAUDI ARABIA',110),
	(172,'SB','SOLOMON ISLANDS',111),
	(173,'SC','SEYCHELLES',112),
	(174,'SD','SUDAN',113),
	(175,'SE','SWEDEN',114),
	(176,'SG','SINGAPORE',115),
	(177,'SH','SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA',116),
	(87,'SI','SLOVENIA',42),
	(152,'SJ','SVALBARD AND JAN MAYEN',95),
	(88,'SK','SLOVAKIA',42),
	(178,'SL','SIERRA LEONE',117),
	(89,'SM','SAN MARINO',42),
	(236,'SN','SENEGAL',145),
	(179,'SO','SOMALIA',118),
	(180,'SR','SURINAME',119),
	(181,'SS','SOUTH SUDAN',120),
	(182,'ST','SAO TOME AND PRINCIPE',121),
	(207,'SV','EL SALVADOR',135),
	(19,'SX','SINT MAARTEN (DUTCH PART)',6),
	(183,'SY','SYRIAN ARAB REPUBLIC',122),
	(184,'SZ','SWAZILAND',123),
	(208,'TC','TURKS AND CAICOS ISLANDS',135),
	(222,'TD','CHAD',143),
	(90,'TF','FRENCH SOUTHERN TERRITORIES',42),
	(237,'TG','TOGO',145),
	(185,'TH','THAILAND',124),
	(186,'TJ','TAJIKISTAN',125),
	(158,'TK','TOKELAU',97),
	(209,'TL','TIMOR-LESTE',135),
	(187,'TM','TURKMENISTAN',126),
	(188,'TN','TUNISIA',127),
	(189,'TO','TONGA',128),
	(190,'TR','TURKEY',129),
	(191,'TT','TRINIDAD AND TOBAGO',130),
	(29,'TV','TUVALU',9),
	(192,'TW','TAIWAN, PROVINCE OF CHINA',131),
	(193,'TZ','TANZANIA, UNITED REPUBLIC OF',132),
	(194,'UA','UKRAINE',133),
	(195,'UG','UGANDA',134),
	(210,'UM','UNITED STATES MINOR OUTLYING ISLANDS',135),
	(211,'US','UNITED STATES',136),
	(212,'UY','URUGUAY',137),
	(213,'UZ','UZBEKISTAN',138),
	(91,'VA','HOLY SEE (VATICAN CITY STATE)',42),
	(230,'VC','SAINT VINCENT AND THE GRENADINES',144),
	(214,'VE','VENEZUELA, BOLIVARIAN REPUBLIC OF',139),
	(13,'VG','VIRGIN ISLANDS, BRITISH',135),
	(14,'VI','VIRGIN ISLANDS, U.S.',135),
	(215,'VN','VIET NAM',140),
	(216,'VU','VANUATU',141),
	(240,'WF','WALLIS AND FUTUNA',146),
	(217,'WS','SAMOA',142),
	(241,'YE','YEMEN',147),
	(92,'YT','MAYOTTE',42),
	(244,'ZA','SOUTH AFRICA',148),
	(245,'ZM','ZAMBIA',149),
	(246,'ZW','ZIMBABWE',150);

UNLOCK TABLES;

LOCK TABLES `currencytable` WRITE;
/*!40000 ALTER TABLE `currencytable` DISABLE KEYS */;

INSERT INTO `currencytable` (`currencyid`, `currencyCode`, `currencySymbol`, `currencyIsRight`, `currencyName`)
VALUES
	(1,'N/A','N/A',0,'No universal currency'),
	(2,'AED','',0,'UAE Dirham'),
	(3,'AFN','؋',0,'Afghani'),
	(4,'ALL','Lek',0,'Lek'),
	(5,'AMD','',0,'Armenian Dram'),
	(6,'ANG','',0,'Netherlands Antillean Guilder'),
	(7,'AOA','',0,'Kwanza'),
	(8,'ARS','$',0,'Argentine Peso'),
	(9,'AUD','$',0,'Australian Dollar'),
	(10,'AWG','ƒ',0,'Aruban Florin'),
	(11,'AZN','ман',0,'Azerbaijanian Manat'),
	(12,'BAM','KM',0,'Convertible Mark'),
	(13,'BBD','$',0,'Barbados Dollar'),
	(14,'BDT','',0,'Taka'),
	(15,'BGN','лв',0,'Bulgarian Lev'),
	(16,'BHD','',0,'Bahraini Dinar'),
	(17,'BIF','FBu',0,'Burundi Franc'),
	(18,'BMD','$',0,'Bermudian Dollar'),
	(19,'BND','$',0,'Brunei Dollar'),
	(20,'BOV','',0,'Mvdol'),
	(21,'BRL','R$',0,'Brazilian Real'),
	(22,'BSD','$',0,'Bahamian Dollar'),
	(23,'BWP','P',0,'Pula'),
	(24,'BYR','p.',0,'Belarussian Ruble'),
	(25,'BZD','BZ$',0,'Belize Dollar'),
	(26,'CAD','$',0,'Canadian Dollar'),
	(27,'CHF','',0,'Swiss Franc'),
	(28,'CHW','CWF',0,'WIR Franc'),
	(29,'CLP','$',0,'Chilean Peso'),
	(30,'CNY','¥',0,'Yuan Renminbi'),
	(31,'CRC','₡',0,'Costa Rican Colon'),
	(32,'CUP','₱',0,'Cuban Peso'),
	(33,'CVE','CVE',0,'Cape Verde Escudo'),
	(34,'CZK','Kč',0,'Czech Koruna'),
	(35,'DJF','DJF',0,'Djibouti Franc'),
	(36,'DKK','kr',0,'Danish Krone'),
	(37,'DOP','RD$',0,'Dominican Peso'),
	(38,'DZD','دج',0,'Algerian Dinar'),
	(39,'EGP','£',0,'Egyptian Pound'),
	(40,'ERN','Nfk',0,'Nakfa'),
	(41,'ETB','Br',0,'Ethiopian Birr'),
	(42,'EUR','€',0,'Euro'),
	(43,'FJD','$',0,'Fiji Dollar'),
	(44,'FKP','£',0,'Falkland Islands Pound'),
	(45,'GBP','£',0,'Pound Sterling'),
	(46,'GEL','ლ',0,'Lari'),
	(47,'GHS','¢',0,'Ghana Cedi'),
	(48,'GIP','£',0,'Gibraltar Pound'),
	(49,'GMD','D',0,'Dalasi'),
	(50,'GNF','GF',0,'Guinea Franc'),
	(51,'GTQ','Q',0,'Quetzal'),
	(52,'GYD','$',0,'Guyana Dollar'),
	(53,'HKD','$',0,'Hong Kong Dollar'),
	(54,'HNL','L',0,'Lempira'),
	(55,'HRK','kn',0,'Croatian Kuna'),
	(56,'HUF','Ft',0,'Forint'),
	(57,'IDR','Rp',0,'Rupiah'),
	(58,'ILS','₪',0,'New Israeli Sheqel'),
	(59,'INR','Rs',0,'Indian Rupee'),
	(60,'IQD','ع.د',0,'Iraqi Dinar'),
	(61,'IRR','﷼',0,'Iranian Rial'),
	(62,'ISK','kr',0,'Iceland Krona'),
	(63,'JMD','J$',0,'Jamaican Dollar'),
	(64,'JOD','JD',0,'Jordanian Dinar'),
	(65,'JPY','¥',0,'Yen'),
	(66,'KES','KSh',0,'Kenyan Shilling'),
	(67,'KGS','лв',0,'Som'),
	(68,'KHR','៛',0,'Riel'),
	(69,'KRW','₩',0,'Won'),
	(70,'KWD','د.ك',0,'Kuwaiti Dinar'),
	(71,'KYD','$',0,'Cayman Islands Dollar'),
	(72,'KZT','лв',0,'Tenge'),
	(73,'LBP','£',0,'Lebanese Pound'),
	(74,'LKR','₨',0,'Sri Lanka Rupee'),
	(75,'LRD','$',0,'Liberian Dollar'),
	(76,'LTL','Lt',0,'Lithuanian Litas'),
	(77,'LVL','Ls',0,'Latvian Lats'),
	(78,'LYD','',0,'Libyan Dinar'),
	(79,'MAD','',0,'Moroccan Dirham'),
	(80,'MDL','',0,'Moldovan Leu'),
	(81,'MGA','',0,'Malagasy Ariary'),
	(82,'MKD','ден',0,'Denar'),
	(83,'MMK','K',0,'Kyat'),
	(84,'MNT','₮',0,'Tugrik'),
	(85,'MOP','',0,'Pataca'),
	(86,'MRO','',0,'Ouguiya'),
	(87,'MUR','₨',0,'Mauritius Rupee'),
	(88,'MVR','',0,'Rufiyaa'),
	(89,'MWK','MK',0,'Kwacha'),
	(90,'MXV','',0,'Mexican Unidad de Inversion (UDI)'),
	(91,'MYR','RM',0,'Malaysian Ringgit'),
	(92,'MZN','MT',0,'Mozambique Metical'),
	(93,'NGN','₦',0,'Naira'),
	(94,'NIO','C$',0,'Cordoba Oro'),
	(95,'NOK','kr',0,'Norwegian Krone'),
	(96,'NPR','Rs',0,'Nepalese Rupee'),
	(97,'NZD','',0,'New Zealand Dollar'),
	(98,'OMR','﷼',0,'Rial Omani'),
	(99,'PEN','S/.',0,'Nuevo Sol'),
	(100,'PGK','K',0,'Kina'),
	(101,'PHP','₱',0,'Philippine Peso'),
	(102,'PKR','₨',0,'Pakistan Rupee'),
	(103,'PLN','zł',0,'Zloty'),
	(104,'PYG','Gs',0,'Guarani'),
	(105,'QAR','﷼',0,'Qatari Rial'),
	(106,'RON','lei',0,'New Romanian Leu'),
	(107,'RSD','Дин.',0,'Serbian Dinar'),
	(108,'RUB','руб',0,'Russian Ruble'),
	(109,'RWF','FRw',0,'Rwanda Franc'),
	(110,'SAR','﷼',0,'Saudi Riyal'),
	(111,'SBD','$',0,'Solomon Islands Dollar'),
	(112,'SCR','₨',0,'Seychelles Rupee'),
	(113,'SDG','£',0,'Sudanese Pound'),
	(114,'SEK','kr',0,'Swedish Krona'),
	(115,'SGD','$',0,'Singapore Dollar'),
	(116,'SHP','£',0,'Saint Helena Pound'),
	(117,'SLL','Le',0,'Leone'),
	(118,'SOS','S',0,'Somali Shilling'),
	(119,'SRD','$',0,'Surinam Dollar'),
	(120,'SSP','£',0,'South Sudanese Pound'),
	(121,'STD','Db',0,'Dobra'),
	(122,'SYP','£',0,'Syrian Pound'),
	(123,'SZL','E',0,'Lilangeni'),
	(124,'THB','฿',0,'Baht'),
	(125,'TJS','',0,'Somoni'),
	(126,'TMT','',0,'Turkmenistan New Manat'),
	(127,'TND','د.ت',0,'Tunisian Dinar'),
	(128,'TOP','T$',0,'Pa’anga'),
	(129,'TRY','TL',0,'Turkish Lira'),
	(130,'TTD','TT$',0,'Trinidad and Tobago Dollar'),
	(131,'TWD','NT$',0,'New Taiwan Dollar'),
	(132,'TZS','TZS',0,'Tanzanian Shilling'),
	(133,'UAH','₴',0,'Hryvnia'),
	(134,'UGX','USh',0,'Uganda Shilling'),
	(135,'USD','$',0,'US Dollar'),
	(136,'USS','$',0,'US Dollar (Same day)'),
	(137,'UYU','$U',0,'Peso Uruguayo'),
	(138,'UZS','лв',0,'Uzbekistan Sum'),
	(139,'VEF','Bs',0,'Bolivar Fuerte'),
	(140,'VND','₫',0,'Dong'),
	(141,'VUV','',0,'Vatu'),
	(142,'WST','WS$',0,'Tala'),
	(143,'XAF','CFA',0,'CFA Franc BEAC'),
	(144,'XCD','$',0,'East Caribbean Dollar'),
	(145,'XOF','',0,'CFA Franc BCEAO'),
	(146,'XPF','',0,'CFP Franc'),
	(147,'YER','﷼',0,'Yemeni Rial'),
	(148,'ZAR','R',0,'Rand'),
	(149,'ZMK','',0,'Zambian Kwacha'),
	(150,'ZWL','Z$',0,'Zimbabwe Dollar');

/*!40000 ALTER TABLE `currencytable` ENABLE KEYS */;
UNLOCK TABLES;

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