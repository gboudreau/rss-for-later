<?php
chdir('..');
require_once('init.inc.php');

if (isset($_GET['authorized'])) {
    // Back from Pocket Auth API
    $access_token = PocketAPI::getAccessToken($user->pocket_request_token);

    $q = "UPDATE users SET pocket_access_token = ':token', pocket_request_token = NULL WHERE uuid = ':uuid'";
    DB::execute($q, array('token' => $access_token, 'uuid' => $uuid));

    header("Location: /?uuid=$uuid");
    exit();
} else {
    $uuid = $_GET['uuid'];

    if (!isset($user->pocket_request_token)) {
        $user->pocket_request_token = PocketAPI::getRequestToken($uuid);

        $q = "UPDATE users SET pocket_request_token = ':token' WHERE uuid = ':uuid'";
        DB::execute($q, array('token' => $user->pocket_request_token, 'uuid' => $uuid));
    }

    $url = 'https://getpocket.com/auth/authorize?request_token=' . $user->pocket_request_token . '&redirect_uri=' . urlencode(PocketAPI::getRedirectURL($uuid));
    header("Location: $url");
    exit();
}
