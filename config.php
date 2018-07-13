<?php
$login_name = 'serveradmin';  		// query login info
$login_password = 'rgDRpNLR'; 		// =||=
$ip = 'localhost';            		// ex. 127.0.0.1/ 254.13.121.12 
$query_port = '10011';		  		// default 10011
$virtualserver_port = '9987'; 		// default 9987
$bot_name = 'Room creator';         // bot name
$agroup = '5';						// channel admin group id
$domain = 'localhost';				// your ip/ domain
$path = '/ts3/index.php';			// Where is script located (include directorys only)
$mysqlcfg["host"] = 'localhost';	// MySQL HOST
$mysqlcfg["database"] = 'rooms';	// database name
$mysqlcfg["user"] = 'root';			// Username
$mysqlcfg["password"] = '';			// Mysql user password
$debug = 'false';					// If enabled it will show warnings/ errors

//Database
$db = new mysqli($mysqlcfg["host"], $mysqlcfg["user"], $mysqlcfg["password"], $mysqlcfg["database"]);
//FRAMEWORK
$filename = 'src/TeamSpeak3/TeamSpeak3.php';

if (file_exists($filename)) {
    require_once($filename);
} else {
    die ("The file $filename does not exist");
}

// Get real user ip
function getIp(){
    switch(true){
      case (!empty($_SERVER['HTTP_X_REAL_IP'])) : return $_SERVER['HTTP_X_REAL_IP'];
      case (!empty($_SERVER['HTTP_CLIENT_IP'])) : return $_SERVER['HTTP_CLIENT_IP'];
      case (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) : return $_SERVER['HTTP_X_FORWARDED_FOR'];
      default : return $_SERVER['REMOTE_ADDR'];
    }
 }

?>