<?php

chdir('..');
require_once('init.inc.php');

if (!isset($uuid)) {
    if (!empty($_GET['shared'])) {
        $q = "SELECT * FROM users WHERE id = ':id'";
        $user = DB::getFirst($q, array('id' => substr(base64_decode($_GET['shared']), strlen(Config::YAHOO_PIPE_ID))));
    } else {
        die('Missing UUID parameter.');
    }
}
if (!isset($_GET['aid'])) {
    die('Missing Article ID (aid) parameter.');
}

$q = "SELECT content FROM local_articles WHERE user_id = ':user_id' AND id = :article_id";
$content = DB::getFirstValue($q, array('user_id' => $user->id, 'article_id' => $_GET['aid']));

if (!$content) {
    header('HTTP/1.0 404 Not Found');
    die("Article not found.");
}

header('Content-type: text/html; charset=utf-8');
echo $content;

?>