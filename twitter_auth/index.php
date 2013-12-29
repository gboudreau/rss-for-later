<?php
chdir('..');
require_once('init.inc.php');

if (isset($_GET['oauth_verifier'])) {
    // Back from Twitter API
    list($access_token, $secret) = Twitter::getAccessToken($user->twitter_request_token, $_GET['oauth_verifier']);
    var_dump($access_token);
    var_dump($secret);

    if (!empty($access_token)) {
        $q = "UPDATE users SET twitter_access_token = :token, twitter_access_token_secret = :secret, twitter_request_token = NULL WHERE uuid = :uuid";
        DB::execute($q, array('token' => $access_token, 'secret' => $secret, 'uuid' => $uuid));

        Twitter::downloadTimeline($uuid, TRUE); // $initial_load = TRUE

        header("Location: /?uuid=$uuid");
    } else {
        echo "Fatal error while getting Twitter Access Token";
    }

    exit();
} else {
    $uuid = $_GET['uuid'];

    if (!isset($user->twitter_request_token)) {
        $user->twitter_request_token = Twitter::getRequestToken($uuid);
        if ($user->twitter_request_token === FALSE) {
            die("Fatal error while getting Twitter Request Token.");
        }
        $q = "UPDATE users SET twitter_request_token = :token WHERE uuid = :uuid";
        DB::execute($q, array('token' => $user->twitter_request_token, 'uuid' => $uuid));
    }

    $url = 'https://api.twitter.com/oauth/authorize?oauth_token=' . $user->twitter_request_token;
    header("Location: $url");
    exit();
}
