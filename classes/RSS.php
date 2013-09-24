<?php

/**
 * Handles downloading RSS feeds, and sending them to Pocket as needed.
 *
 * @author bougu
 */

class RSS {

    public static function downloadRSS($uuid, $initial_load=FALSE) {
        $q = "SELECT * FROM users WHERE uuid = ':uuid'";
        $user = DB::getFirst($q, array('uuid' => $uuid));

        $user_id = $user->id;

        $q = "SELECT uf.* FROM users u JOIN users_feeds uf ON (u.id = uf.user_id) WHERE u.uuid = ':uuid'";
        $feeds = DB::getAll($q, array('uuid' => $uuid));

        foreach ($feeds as $feed) {
            self::log("[$feed->title] $feed->xmlUrl");

            $url = 'http://pipes.yahoo.com/pipes/pipe.run?_id=' . Config::YAHOO_PIPE_INJECT_ID . '&_render=php&feedTitle=' . urlencode($feed->title) . '&feedUrl=' . urlencode($feed->xmlUrl);

            $response = file_get_contents($url);
            $feedXml = unserialize($response);

            self::log("  Received " . $feedXml['count'] . " items.");

            $hashes = array();
            $items = array();
            foreach ($feedXml['value']['items'] as $item) {
                if (empty($item['link'])) {
                    continue;
                }
                $hash = self::get_item_hash($item);
                $hashes[$hash] = TRUE;
                $items[$hash] = (object) array(
                    'url' => $item['link'],
                    'title' => $item['title'],
                    'tags' => array('RSS', $item['feedTitle']),
                    'content' => '<h2>' . $item['title'] . '</h2>' . $item['description'] // . '<br/><br/>[' . $item['link'] . '|' . @$item['guid']['content'] . '|' . @$item['pubDate'] . '|' . @$item['y:id']['value'].']'
                );
            }

            if (!empty($hashes)) {
                $q = "SELECT hash FROM users_articles WHERE user_id = :user_id AND hash IN ('" . implode("','", array_keys($hashes)) . "')";
                $known_hashes = DB::getAllValues($q, array('user_id' => $user_id));
                foreach ($known_hashes as $known_hash) {
                    unset($hashes[$known_hash]);
                    unset($items[$known_hash]);
                }
                self::log("  " . count($hashes) . " of those items are new.");

                // New articles
                $values = array();
                foreach (array_keys($hashes) as $hash) {
                    $values[$hash] = "($user_id,$feed->id,'$hash')";
                    self::log("  New article: " . $items[$hash]->title);

                    if ($feed->mirror_articles_locally == 'true') {
                        // Save the article content locally, and send to Pocket this new URL.
                        $q = "INSERT INTO local_articles SET user_id = ':user_id', feed_id = :feed_id, content=':content'";
                        $local_article_id = DB::insert($q, array('user_id' => $user->id, 'feed_id' => $feed->id, 'content' => $items[$hash]->content));

                        $items[$hash]->url = str_replace(array('$hash', '$aid'), array(trim(base64_encode(Config::SHARING_SALT . $user->id), '='), $local_article_id), Config::LOCAL_COPY_URL);

                        self::log("  Will use local URL: " . $items[$hash]->url);
                    }
                }

                if (!$initial_load && !empty($items)) {
                    // Send items to Pocket
                    self::log("  Sending to Pocket API...");
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

    public function get_item_hash($item) {
        return md5($item['link'] . @$item['guid']['content'] . @$item['pubDate'] . @$item['y:id']['value']);
	}

    private static function log($text) {
        echo "$text\n";
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === FALSE) {
            echo "<br/>";
        }
    }
}
?>
