<?php
require_once('init.inc.php');

// AJAX requests
if (!empty($_POST['mirror_articles_locally'])) {
    $q = "SELECT id FROM users WHERE uuid = ':uuid'";
    $user_id = DB::getFirstValue($q, array('uuid' => $uuid));
    if (!$user_id) {
        die("Unknown secret.");
    }

    $q = "UPDATE users_feeds SET mirror_articles_locally = ':mirror_articles_locally' WHERE id = :feed_id AND user_id = :user_id";
    DB::execute($q, array('feed_id' => $_POST['feed_id'], 'mirror_articles_locally' => $_POST['mirror_articles_locally'], 'user_id' => $user_id));

    header('Content-type: text/plain; charset=UTF-8');
    die('true');
}
?>
<?php header('Content-type: text/html; charset=UTF-8') ?>
<html>
<head>
    <title>RSS-For-Later - Read your RSS in Pocket</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <link rel="shortcut icon" href="/img/google-reader-logo.png" />
    <style type="text/css">
        footer {
             color: grey;
             position: fixed;
             bottom: 10px;
             right: 10px;
             text-align: right;
             background-color: #EEE;
             border: 1px solid grey;
             padding: 5px;
             font-size: 13px;
        }
        h2 {
            color: #ff3956;
        }
        a {
            color: #e2b518;
        }
    </style>
    <?php google_analytics() ?>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
</head>
<body>
    <a href="https://github.com/gboudreau/rss-for-later" target="_blank"><img style="position: absolute; top: 0; right: 0; border: 0;" src="/img/forkme_left_red_aa0000.png" alt="Fork me on GitHub"></a>
<?php

if (!empty($_GET['subscribe'])) {
    $_POST['xmlUrl'] = $_GET['subscribe'];
}

if (!empty($_POST['email'])) {
    $uuid = gen_uuid();
    $q = "INSERT INTO users SET email = ':email', uuid = ':uuid'";
    try {
        DB::insert($q, array('email' => $_POST['email'], 'uuid' => $uuid));
        header('Location: /?uuid=' . $uuid);
        exit();
    } catch (Exception $ex) {
        // @TODO
    }
} else if (!empty($_POST['delete'])) {
    $q = "SELECT id FROM users WHERE uuid = ':uuid'";
    $user_id = DB::getFirstValue($q, array('uuid' => $uuid));
    if (!$user_id) {
        die("Unknown secret.");
    }

    $q = "DELETE FROM users_feeds WHERE id = :feed_id AND user_id = :user_id";
    DB::execute($q, array('feed_id' => $_POST['feed_id'], 'user_id' => $user_id));
    echo "Successfully unsubscribed from <a href='" . htmlentities($_POST['xmlUrl'], ENT_QUOTES, 'UTF-8') . "'>" . htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8') . "</a>";
} else if (!empty($_POST['xmlUrl'])) {
    $q = "SELECT id FROM users WHERE uuid = ':uuid'";
    $user_id = DB::getFirstValue($q, array('uuid' => $uuid));
    if (!$user_id) {
        die("Unknown secret.");
    }

    if (empty($_POST['title'])) {
        // Get Title from feed
        require_once('libs/simplepie_1.3.1.compiled.php');
        $feed = new SimplePie();
        $feed->set_feed_url($_POST['xmlUrl']);
        $feed->init();
        $_POST['title'] = $feed->get_title();
    }

    $q = "INSERT INTO users_feeds SET xmlUrl = ':xmlUrl', title = ':title', user_id = :user_id";
    DB::insert($q, array('xmlUrl' => $_POST['xmlUrl'], 'title' => $_POST['title'], 'user_id' => $user_id));
    echo "Successfully subscribed to <a href='" . htmlentities($_POST['xmlUrl'], ENT_QUOTES, 'UTF-8') . "'>" . htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8') . "</a>";
} else if (isset($_FILES['opml']['name'])) {
    $q = "SELECT id FROM users WHERE uuid = ':uuid'";
    $user_id = DB::getFirstValue($q, array('uuid' => $uuid));
    if (!$user_id) {
        die("Unknown secret.");
    }

    $xml = simplexml_load_file($_FILES['opml']['tmp_name']);
    $subs = 0;
    foreach ($xml->body->outline as $subscription) {
        $title = (string) $subscription['title'];
        $xmlUrl = (string) $subscription['xmlUrl'];
        $q = "INSERT INTO users_feeds SET title = ':title', xmlUrl = ':xmlUrl', user_id = :user_id";
        try {
            DB::insert($q, array('title' => $title, 'xmlUrl' => $xmlUrl, 'user_id' => $user_id));
            $subs++;
        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }
    }
    echo "Successfully imported $subs subscriptions.";
    echo "Initiating an initial load of all your subscriptions.";
    echo "<!--";
    RSS::downloadRSS($uuid, TRUE); // $initial_load = TRUE
    echo "-->";
}

function gen_uuid() {
    return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}
?>

<h2>RSS-For-Later</h2>
<div><em>Read your RSS (and Twitter) feeds in Pocket</em></div>
<br/><br/>
<?php if (isset($uuid)): ?>
    <div>
        Important: Use this secret URL to come back to your account later:<br/>
        &nbsp; <a href="http://rss-for-later.pommepause.com/?uuid=<?php echo $uuid ?>">http://rss-for-later.pommepause.com/?uuid=<?php echo $uuid ?></a>
    </div>
    <br/>
    <div>
        Tip: If you have the Chrome <a href="https://chrome.google.com/webstore/detail/rss-subscription-extensio/nlbjncdgjeocebhnmkbbbdekmmmcbfjd" target="_blank">RSS Subscription Extension (by Google)</a> installed (or an equivalent extension in other browsers), you can subscribe to feeds using it by configuring the following URL:<br/>
        &nbsp; http://rss-for-later.pommepause.com/?uuid=<?php echo $uuid ?>&subscribe=%s<br/>
    </div>
<?php else: ?>
    <form action="" method="post">
        <input type="text" name="email" placeholder="email address" /><br/>
        <input type="submit" value="Create Account" />
    </form>
<?php endif; ?>

<?php if (isset($uuid)): ?>
    <?php
    $q = "SELECT uf.* FROM users u JOIN users_feeds uf ON (u.id = uf.user_id) WHERE u.uuid = ':uuid' ORDER BY uf.title";
    $feeds = DB::getAll($q, array('uuid' => $uuid));
    ?>

    <h3>OPML</h3>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="uuid" value="<?php echo $uuid ?>" />
        <input type="file" name="opml" />
        <input type="submit" value="Upload OPML" />
    </form>

    <?php if (count($feeds) > 0): ?>
        <div><a href="<?php echo str_replace('$uuid', $uuid, Config::OPML_URL) ?>">Download OPML</a></div>
    <?php endif; ?>

    <h3>Pocket Integration</h3>
    <div>
        <?php if (empty($user->pocket_access_token)): ?>
            <input type="button" onclick="window.location.href='/pocket_auth?uuid=<?php echo $uuid ?>'" value="Connect to Pocket" />
        <?php else: ?>
            Already connected to Pocket.
        <?php endif; ?>
    </div>

    <?php if (defined('Config::TWITTER_API_KEY') && Config::TWITTER_API_KEY != ''): ?>
        <h3>Twitter Integration</h3>
        <div>Connect to Twitter to also send new Tweets to Pocket.</div>
        <div>
            <?php if (empty($user->twitter_access_token)): ?>
                <input type="button" onclick="window.location.href='/twitter_auth?uuid=<?php echo $uuid ?>'" value="Connect to Twitter" />
            <?php else: ?>
                Already connected to Twitter.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h3>Feeds</h3>
    <?php if (count($feeds) > 0): ?>
        <div>
            <a href="/cron/?uuid=<?php echo $uuid ?>" target="_blank">Refresh Feeds & Send to Pocket</a>
        </div>
    <?php endif; ?>
    <ul>
        <li>
            <form action="" method="post">
                <input type="hidden" name="uuid" value="<?php echo $uuid ?>" />
                <input type="text" name="title" placeholder="Feed Title (optional)" />
                <input type="text" name="xmlUrl" placeholder="Feed URL" />
                <input type="submit" value="Subscribe" />
            </form>
        </li>
        <?php foreach ($feeds as $feed): ?>
            <li>
                <form action="" method="post">
                    <input type="hidden" name="uuid" value="<?php echo $uuid ?>" />
                    <input type="hidden" name="feed_id" value="<?php echo $feed->id ?>" />
                    <input type="hidden" name="title" value="<?php echo htmlentities($feed->title, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="xmlUrl" value="<?php echo htmlentities($feed->xmlUrl, ENT_QUOTES, 'UTF-8') ?>" />
                    <a href="<?php echo htmlentities($feed->xmlUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank"><?php echo htmlentities($feed->title, ENT_COMPAT, 'UTF-8') ?></a>
                    <input type="submit" name="delete" onclick="if(!confirm('Are you sure?')){return false;}" value="Unsubscribe" /><br/>
                    Send to Pocket:
                    [<input type="radio" onchange="save_mirror_articles(<?php echo $feed->id ?>, 'false')" name="mirror_articles_locally" value="false" id="mirror_articles_locally-false" <?php if ($feed->mirror_articles_locally == 'false') { echo 'checked="checked"'; } ?> /><label for="mirror_articles_locally-false">Links</label> |
                    <input type="radio" onchange="save_mirror_articles(<?php echo $feed->id ?>, 'true')" name="mirror_articles_locally" value="true" id="mirror_articles_locally-true" <?php if ($feed->mirror_articles_locally == 'true') { echo 'checked="checked"'; } ?> /><label for="mirror_articles_locally-true">Content</label>]
                    of articles
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <script>
    function save_mirror_articles(feed_id, mirror_articles_locally) {
        $.ajax({
            type: 'POST',
            url: '/?debug_sql=y',
            data: 'uuid=<?php echo $uuid ?>&feed_id=' + feed_id + '&mirror_articles_locally=' + mirror_articles_locally
        });
    }
    </script>
<?php endif; ?>
        <br/>
        <footer>RSS-For-Later was created in a couple hours. Comments about the UI are NOT welcome! :)<br/>by <a href="http://www.pommepause.com/" rel="author">Guillaume Boudreau</a> | <a href="http://www.pommepause.com/contact.php">Contact</a></footer>
</body>
</html>
