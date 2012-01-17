<?php
// Country and Currency data obtained from ISO website in xls and csv format
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

$file = fopen("/Users/gmanish/Documents/countries.txt", "r") 
	or exit("Unable to open countries.txt!");
	
while(!feof($file))
{
  	$line = fgets($file);
	$pieces = explode(";", $line);
	$sql = "INSERT INTO countrytable (countryName, countryCode) 
			VALUES ('" . $pieces[0] . "','" . $pieces[1] . "')";
	$result = $db_con->query($sql);
	if (!$result)
		echo "Could not import ", $pieces[0], " ", $pieces[1], $db_con->error . "\n";
}
fclose($file);

$file = fopen("/Users/gmanish/Documents/currencies.csv", "r") 
	or exit("Unable to open currencies file!");
	
while(!feof($file))
{
  	$line = fgets($file);
	$pieces = explode(";", $line);
	$sql = sprintf("UPDATE countrytable 
					SET currencyName = '%s', currencyCode = '%s' 
					WHERE countryName = '%s'",
					$pieces[1], $pieces[2], $pieces[0]);
	$result = $db_con->query($sql);
	if (!$result)
		echo "Could not import ", $pieces[0], " ", $pieces[1], $db_con->error . "\n";
}
$db_con->close();
fclose($file);

?>