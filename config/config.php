<?php
session_start();
$hostname = $_SERVER['HTTP_HOST'];
if($hostname == 'localhost'){
    error_reporting(1);
    $host = 'http://localhost/task_mgmt/';
    $root = $_SERVER['DOCUMENT_ROOT'].'/task_mgmt/';  
    define('ROOT', $root); 
    define('HTTP', $host); 
}else{
    $host = $hostname;
    $root = $hostname;
    error_reporting(0);
}

$GLOBALS['configs'] = [	
	'database' => [
		'host' => 'localhost',
		'username' => 'root',
		'password' => '',
		'name' => 'task_mgmt',
        'port' => 3306,
	],
];

