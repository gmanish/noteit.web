<?php
if (true) {
	// Local Host
	$config = array(
		'SERVER_ADDRESS'		=> 'http://127.0.0.1', // Without ending slash
		'APP_DIR'       		=> 'noteit.web',
		'MYSQL_SERVER'  		=> '127.0.0.1',
		'MYSQL_USER'    		=> 'root',
		'MYSQL_PASSWD'  		=> 'pass123',
		'MYSQL_DB'      		=> 'noteitdb',
		'GEOIP_DB'				=> 'C:\Users\mgupta\Sources\www\noteit.web\data\GeoIP.dat',
		'GEOIP_DB'				=> '/Users/gmanish/Sites/noteit.web/data/GeoIP.dat', // Full path to GeoIP.dat file from root
		'SALT'					=> 'G3480BFA037EE',
		'USE_STORED_PROCS'		=> FALSE);
} else {
	// geekjamboree.com
	$config = array(
		'SERVER_ADDRESS'		=> 'http://geekjamboree.com', // Without ending slash
		'APP_DIR'       		=> '',
		'MYSQL_SERVER'  		=> 'localhost',
		'MYSQL_USER'    		=> 'geekjgsf_root',
		'MYSQL_PASSWD'  		=> 'pass123',
		'MYSQL_DB'      		=> 'geekjgsf_noteitdb',
		'GEOIP_DB'				=> '/home/geekjgsf/public_html/data/GeoIP.dat',
		'SALT'					=> 'G3480BFA037EE',
		'USE_STORED_PROCS'		=> FALSE);
}
?>