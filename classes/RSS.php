<?php

/**
 * Handles downloading RSS feeds, and sending them to Pocket as needed.
 *
 * @author bougu
 */

class RSS {

    public static function downloadRSS($uuid, $initial_load=FALSE) {
        $q = "SELECT * FROM users WHERE uuid = :uuid";
        $user = DB::getFirst($q, array('uuid' => $uuid));

        $user_id = $user->id;

        $q = "SELECT uf.* FROM users u JOIN users_feeds uf ON (u.id = uf.user_id) WHERE u.uuid = :uuid";
        $feeds = DB::getAll($q, array('uuid' => $uuid));

        foreach ($feeds as $feed) {
            static::log("[$feed->title] $feed->xmlUrl");

            $url = 'http://pipes.yahoo.com/pipes/pipe.run?_id=' . Config::YAHOO_PIPE_INJECT_ID . '&_render=php&feedTitle=' . urlencode($feed->title) . '&feedUrl=' . urlencode($feed->xmlUrl);

            $response = static::curl_get_content($url);
            if (!$response) {
                continue;
            }
            $feedXml = unserialize($response);
            if (!$feedXml) {
                error_log("Error de-serializing response from Yahoo Pipes:");
                error_log(var_export($response, TRUE));
                continue;
            }

            static::log("  Received " . $feedXml['count'] . " items.");

            $hashes = array();
            $items = array();
            foreach ($feedXml['value']['items'] as $item) {
                if (empty($item['link'])) {
                    continue;
                }
                if (is_array($item['description'])) {
                    $item['description'] = array_shift($item['description']);
                }
                $hash = static::get_item_hash($item);
                $hashes[$hash] = TRUE;
                $items[$hash] = (object) array(
                    'url' => $item['link'],
                    'title' => $item['title'],
                    'tags' => array('RSS', $item['feedTitle']),
                    'content' => '<h2>' . $item['title'] . '</h2>' . $item['description']
                );
            }

            if (!empty($hashes)) {
                $q = "SELECT hash FROM users_articles WHERE user_id = :user_id AND hash IN ('" . implode("','", array_keys($hashes)) . "')";
                $known_hashes = DB::getAllValues($q, array('user_id' => $user_id));
                foreach ($known_hashes as $known_hash) {
                    unset($hashes[$known_hash]);
                    unset($items[$known_hash]);
                }
                static::log("  " . count($hashes) . " of those items are new.");

                // New articles
                $values = array();
                foreach (array_keys($hashes) as $hash) {
                    $values[$hash] = "($user_id,$feed->id,'$hash')";
                    static::log("  New article: " . $items[$hash]->title);

                    if ($feed->mirror_articles_locally == 'true') {
                        // Save the article content locally, and send to Pocket this new URL.
                        $q = "INSERT INTO local_articles SET user_id = :user_id, feed_id = :feed_id, content=:content";
                        $local_article_id = DB::insert($q, array('user_id' => $user->id, 'feed_id' => $feed->id, 'content' => $items[$hash]->content));

                        $items[$hash]->url = str_replace(array('$hash', '$aid'), array(trim(base64_encode(Config::SHARING_SALT . $user->id), '='), $local_article_id), Config::LOCAL_COPY_URL);

                        static::log("  Will use local URL: " . $items[$hash]->url);
                    }
                }

                if (!$initial_load && !empty($items)) {
                    // Send items to Pocket
                    static::log("  Sending to Pocket API...");
                    $result = PocketAPI::sendToPocket($user->pocket_access_token, array_values($items));
                    if (!$result) {
                        return;
                    }
                }

                if (!empty($values)) {
                    $q = "INSERT IGNORE INTO users_articles (user_id, feed_id, hash) VALUES " . implode(',', array_values($values));
                    DB::execute($q);
                }
            }
        }
    }

    private static function get_item_hash($item) {
        // Bad, bad Google Alerts, inserting random IDs in links!
        $item['link'] = preg_replace('/&ei=[^&]+/', '', $item['link']);

        $hash = md5($item['link'] . @$item['guid']['content'] . @$item['pubDate'] . @$item['y:id']['value']);
        //static::log("  Hash for (link: " . $item['link'] . ", guid: " . @$item['guid']['content'] . ", pubDate: " . @$item['pubDate'] . ", y:id: " . @$item['y:id']['value'] . "): $hash");
        return $hash;
	}

    private static function log($text) {
        echo "$text\n";
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === FALSE) {
            echo "<br/>";
        }
    }

    private static function curl_get_content($url) {
        echo('curl -si ' . escapeshellarg($url)."\n");
        $response = shell_exec('curl -si ' . escapeshellarg($url));
        $response = explode("\n", $response);
        $status_code = trim(array_shift($response));
        if (preg_match('@HTTP/1\.. ([0-9]+) .+@', $status_code, $re)) {
            $http_status = $re[1];
        } else {
            static::log("cURL (command-line) error: status code not found in first line of response: $status_code");
            return FALSE;
        }
        if ($http_status != 200) {
            static::log("cURL (command-line) error: status code = $http_status");
            return FALSE;
        }
        while (count($response) > 0 && preg_match('/[^ ]+: .+/', trim($response[0]))) {
            array_shift($response);
        }
        if (count($response) > 0 && trim($response[0]) == '') {
            array_shift($response);
        }
        $data = implode("\n", $response);
        return $data;
    }
}
?>
