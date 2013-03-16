<?php

if (!empty($_GET['debug_sql'])) {
	define('DEBUGSQL', TRUE);
} else {
	define('DEBUGSQL', FALSE);
}

require_once('classes/Config.php');
require_once('classes/DB.php');
require_once('classes/PocketAPI.php');
require_once('classes/RSS.php');

$DB = new DB();
try {
	$DB->connect();
} catch (Exception $ex) {
    die("Can't connect to database: error -1");
}

if (get_magic_quotes_gpc()) {
	if (!empty($_POST)) {
		$_POST = array_map('stripslashes', $_POST);
	}
	if (!empty($_GET)) {
		$_GET = array_map('stripslashes', $_GET);
	}
}

if (isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];
}
if (isset($_POST['uuid'])) {
    $uuid = $_POST['uuid'];
}

if (isset($uuid)) {
    $q = "SELECT * FROM users WHERE uuid = ':uuid'";
    $user = DB::getFirst($q, array('uuid' => $uuid));
}

?>