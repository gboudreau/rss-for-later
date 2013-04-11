<?php

chdir('..');
require_once('init.inc.php');

if (isset($uuid)) {
    RSS::downloadRSS($uuid);
    Twitter::downloadTimeline($uuid);
    exit();
}

$q = "SELECT uuid FROM users WHERE pocket_access_token IS NOT NULL";
$uuids = DB::getAllValues($q);
foreach ($uuids as $uuid) {
    RSS::downloadRSS($uuid);
}

$q = "SELECT uuid FROM users WHERE twitter_access_token IS NOT NULL";
$uuids = DB::getAllValues($q);
foreach ($uuids as $uuid) {
    Twitter::downloadTimeline($uuid);
}

?>