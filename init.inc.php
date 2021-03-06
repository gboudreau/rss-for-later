<?php

spl_autoload_register(function($className) {
    if (file_exists('classes/'.$className.'.php')) {
        require_once('classes/'.$className.'.php');
    }
    return FALSE;
});

try {
    DB::connect();
} catch (Exception $ex) {
    die("Couldn't connect to database. Please try again later.");
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
    $q = "SELECT * FROM users WHERE uuid = :uuid";
    $user = DB::getFirst($q, array('uuid' => $uuid));
}

function google_analytics() {
    if (!defined('Config::GOOGLE_ANALYTICS_ID') || Config::GOOGLE_ANALYTICS_ID == '') {
        return;
    }
    ?>
    <script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', '<?php echo Config::GOOGLE_ANALYTICS_ID ?>']);
    _gaq.push(['_trackPageview']);

    (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
    </script>
    <?php
}
?>