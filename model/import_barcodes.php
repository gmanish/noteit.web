<?php
ini_set("auto_detect_line_endings", true);
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../config/config.php');
global $config;

$db_con = new MySQLi(
   	$config['MYSQL_SERVER'], 
   	$config['MYSQL_USER'], 
   	$config['MYSQL_PASSWD'], 
   	$config['MYSQL_DB']);
		
if ($db_con->connect_error) {
   exit('Could not connect to Server: ' . $this->db_con->error);
}

$file = fopen("../data/smart_ean_codes.csv", "r") 
	or exit("Unable to open Barcode file!");

echo "<html><body>";
echo "<table border=\"1\" cellpadding=\"5px\">";
while(!feof($file))
{
  	$line = fgets($file);
	$pieces = explode(",", $line);
	$sql = sprintf("INSERT IGNORE INTO `shopitemscatalog`
					(`itemName`, `itemBarcode`, `itemBarcodeFormat`)  
					VALUES('%s', '%s', %d)", 
					$pieces[1], $pieces[0], 5 /*EAN 13 Barcode */);
	$result = $db_con->query($sql);
	echo "<tr>";
	if (!$result)
		echo "Could not import ", $pieces[0], " ", $pieces[1], $db_con->error . "\n";
	else 
		echo "<td>", $pieces[0], "</td><td>", $pieces[1], "</td>";
//	echo $line, "<br>";
	echo "</tr>";
}
echo "</table>";
echo "</html></body>";
$db_con->close();
fclose($file);
?>