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

        $q = "SELECT uf.* FROM users u JOIN users_feeds uf ON (u.id = uf.user_id) WHERE u.uuid = :uuid AND uf.xmlUrl != 'https://twitter.com'";
        $feeds = DB::getAll($q, array('uuid' => $uuid));

        foreach ($feeds as $feed) {
            static::log("[$feed->title] $feed->xmlUrl");

            $rss_parser = new RSSParser($feed->xmlUrl);
            if (!$rss_parser) {
                continue;
            }
            $feedXml = $rss_parser->getRawOutput();
            if (!$feedXml) {
                error_log("Error downloading RSS from $feed->xmlUrl.");
                continue;
            }

            $feedXml = $feedXml['RSS']['CHANNEL'][0];
            static::log("  Received " . count($feedXml['ITEM']) . " items.");

            if (!is_array($feedXml['ITEM'])) {
                continue;
            }

            $hashes = array();
            $items = array();
            foreach ($feedXml['ITEM'] as $item) {
                if (empty($item['LINK'])) {
                    continue;
                }
                if (is_array($item['DESCRIPTION'])) {
                    $item['DESCRIPTION'] = array_shift($item['DESCRIPTION']);
                }
                $hash = static::get_item_hash($item);
                $hashes[$hash] = TRUE;
                $items[$hash] = (object) array(
                    'url' => $item['LINK'],
                    'title' => stripslashes($item['TITLE']),
                    'tags' => array('RSS', stripslashes($feedXml['TITLE'])),
                    'content' => '<h2>' . stripslashes($item['TITLE']) . '</h2>' . html_entity_decode(stripslashes($item['DESCRIPTION']), ENT_COMPAT | ENT_HTML401, 'UTF-8')
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

                    if (!$initial_load && $feed->mirror_articles_locally == 'true') {
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
        $item['LINK'] = preg_replace('/&ei=[^&]+/', '', $item['LINK']);

        $hash = md5($item['LINK'] . @$item['GUID'] . @$item['PUBDATE']);
        //static::log("  Hash for (link: " . $item['LINK'] . ", guid: " . @$item['GUID'] . ", pubDate: " . @$item['PUBDATE'] . "): $hash");
        return $hash;
	}

    private static function log($text) {
        echo "$text\n";
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === FALSE) {
            echo "<br/>";
        }
    }
}
