<?php

/**
 * Handles downloading Twitter Home Timeline, and sending new tweets to Pocket as needed.
 *
 * @author bougu
 */

require_once('libs/tmhOAuth.php');

class Twitter {

    public static function downloadTimeline($uuid, $initial_load=FALSE) {
        $q = "SELECT * FROM users WHERE uuid = :uuid";
        $user = DB::getFirst($q, array('uuid' => $uuid));

        $q = "SELECT id, mirror_articles_locally FROM users_feeds WHERE user_id = :user_id AND title = 'Twitter'";
        $feed = DB::getFirst($q, array('user_id' => $user->id));

        if (!$feed) {
            $q = "INSERT INTO users_feeds SET user_id = :user_id, title = 'Twitter', xmlUrl = 'https://twitter.com'";
            $feed_id = DB::insert($q, array('user_id' => $user->id));
            $feed = (object) array('id' => $feed_id, 'mirror_articles_locally' => 'false');
        }

        $tweets = self::getHomeTimeline($user->twitter_access_token, $user->twitter_access_token_secret);

        if ($tweets === FALSE) {
            return;
        }

        self::log("  Received " . count($tweets) . " tweets.");

        $hashes = array();
        $items = array();
        foreach ($tweets as $tweet) {
            $hash = static::get_item_hash($tweet);
            $hashes[$hash] = TRUE;

            $url = "https://twitter.com/" . $tweet->user->screen_name . "/status/" . $tweet->id_str;

            $items[$hash] = (object) array(
                'url' => $url,
                'title' => "@" . $tweet->user->name . " says",
                'tags' => array('Twitter')
            );
        }

        if (!empty($hashes)) {
            $q = "SELECT hash FROM users_articles WHERE user_id = :user_id AND hash IN ('" . implode("','", array_keys($hashes)) . "')";
            $known_hashes = DB::getAllValues($q, array('user_id' => $user->id));
            foreach ($known_hashes as $known_hash) {
                unset($hashes[$known_hash]);
                unset($items[$known_hash]);
            }
            self::log("  " . count($hashes) . " of those items are new.");

            // New articles
            $values = array();
            foreach (array_keys($hashes) as $hash) {
                $values[$hash] = "($user->id,$feed->id,'$hash')";
                self::log("  New article: " . $items[$hash]->title);

                if ($feed->mirror_articles_locally == 'true') {
                    // Save the article content locally, and send to Pocket this new URL.
                    $q = "INSERT INTO local_articles SET user_id = :user_id, feed_id = :feed_id, content=:content";
                    $local_article_id = DB::insert($q, array('user_id' => $user->id, 'feed_id' => $feed->id, 'content' => $items[$hash]->content));

                    $items[$hash]->url = str_replace(array('$uuid', '$aid'), array($uuid, $local_article_id), Config::LOCAL_COPY_URL);
                    self::log("  Will use local URL: " . $items[$hash]->url);
                }
            }

            if (!$initial_load && !empty($items)) {
                // Send items to Pocket
                self::log("  Sending to Pocket API...");
                PocketAPI::sendToPocket($user->pocket_access_token, array_values($items));
            }

            if (!empty($values)) {
                $q = "INSERT IGNORE INTO users_articles (user_id, feed_id, hash) VALUES " . implode(',', array_values($values));
                DB::execute($q);
            }
        }
    }

    private static function TwitterAPI($access_token=null, $access_token_secret=null) {
        $options = array(
            'consumer_key'    => Config::TWITTER_API_KEY,
            'consumer_secret' => Config::TWITTER_API_SECRET
        );
        if (!empty($access_token)) {
            $options['user_token'] = $access_token;
        }
        if (!empty($access_token_secret)) {
            $options['user_secret'] = $access_token_secret;
        }
        return new tmhOAuth($options);
    }

    public static function getHomeTimeline($access_token, $access_token_secret) {
        $tmhOAuth = self::TwitterAPI($access_token, $access_token_secret);

        $request_params = array(
            'contributor_details' => 'false',
            'count' => 200,
            'exclude_replies' => 'true',
            'include_entities' => 'false'
        );

        $code = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/statuses/home_timeline'), $request_params);

        if ($code == 200) {
            $response = json_decode($tmhOAuth->response['response']);
        } else {
            error_log("TwitterAPI error (code 1-$code): " . $tmhOAuth->response['response'] . ". Access token: $access_token");
            if ($code == 401) {
                $q = "UPDATE users SET twitter_access_token = NULL WHERE twitter_access_token = :access_token";
                DB::execute($q, array('access_token' => $access_token));
            }
            $response = FALSE;
        }
        return $response;
    }

    private static function get_item_hash($item) {
        return md5($item->id_str);
	}

    private static function log($text) {
        echo "$text\n";
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === FALSE) {
            echo "<br/>";
        }
    }

    public static function getRedirectURL($uuid) {
        return Config::BASE_URL . '/twitter_auth/?uuid=' . $uuid;
    }

    public static function getRequestToken($uuid) {
        $tmhOAuth = self::TwitterAPI();
        $code = $tmhOAuth->request('POST', 'https://api.twitter.com/oauth/request_token', array('oauth_callback' => self::getRedirectURL($uuid)));

        if ($code == 200) {
            parse_str($tmhOAuth->response['response'], $response);
            return $response['oauth_token'];
        } else {
            error_log("TwitterAPI error (code 2-$code): " . $tmhOAuth->response['response']);
        }
        return FALSE;
    }

    public static function getAccessToken($request_token, $oauth_verifier) {
        $tmhOAuth = self::TwitterAPI($request_token);
        $code = $tmhOAuth->request('POST', 'https://api.twitter.com/oauth/access_token', array('oauth_verifier' => $oauth_verifier));

        if ($code == 200) {
            parse_str($tmhOAuth->response['response'], $response);
            return array($response['oauth_token'], $response['oauth_token_secret']);
        } else {
            error_log("TwitterAPI error (code 3-$code): " . $tmhOAuth->response['response']);
        }
        return FALSE;
    }
}
?>
