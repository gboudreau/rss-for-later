<?php

chdir('..');
require_once('init.inc.php');

if (isset($uuid)) {
    Twitter::downloadTimeline($uuid);
    exit();
}

$q = "SELECT uuid FROM users WHERE twitter_access_token IS NOT NULL ORDER BY id";
$uuids = DB::getAllValues($q);
foreach ($uuids as $uuid) {
    Twitter::downloadTimeline($uuid);
}

//$q = "SELECT uuid FROM users WHERE pocket_access_token IS NOT NULL ORDER BY id";
//$uuids = DB::getAllValues($q);
//foreach ($uuids as $uuid) {
//    RSS::downloadRSS($uuid);
//}

