<?php
if (true) {
	// Local Host
	$config = array(
		'APP_DIR'       		=> '~gmanish/noteit.web',
		'MYSQL_SERVER'  		=> '127.0.0.1',
		'MYSQL_USER'    		=> 'root',
		'MYSQL_PASSWD'  		=> 'pass123',
		'MYSQL_DB'      		=> 'noteitdb',
		'GEOIP_DB'				=> '/Users/gmanish/Sites/noteit.web/data/GeoIP.dat',
		'SALT'					=> 'G3480BFA037EE',
		'USE_STORED_PROCS'		=> FALSE);
} else {
	// geekjamboree.com
	$config = array(
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