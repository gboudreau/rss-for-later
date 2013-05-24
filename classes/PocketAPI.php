<?php

/**
 * Handles the communication with the Pocket API.
 *
 * @author bougu
 */

class PocketAPI {
    public static function sendToPocket($access_token, $items) {
        $method_url = 'https://getpocket.com/v3/send';
        $payload = array('actions' => array());

        foreach ($items as $item) {
            $action = array(
                'action' => 'add',
                'url' => $item->url
            );
            if (!empty($item->title)) {
                $action['title'] = $item->title;
            }
            if (!empty($item->tags)) {
                $action['tags'] = $item->tags;
            }
            $payload['actions'][] = $action;
        }
        self::preparePayload($payload, $access_token);
        $response = self::doRequest($method_url, json_encode($payload));
        // @TODO Error handling
        return $response;
    }

    public static function getRedirectURL($uuid) {
        return CONFIG::BASE_URL . '/pocket_auth/?uuid=' . $uuid . '&authorized=y';
    }

    public static function getRequestToken($uuid) {
        $payload = array(
            'consumer_key' => Config::POCKET_API_KEY,
            'redirect_uri' => self::getRedirectURL($uuid)
        );
        $response = self::doRequest('https://getpocket.com/v3/oauth/request', json_encode($payload));
        // @TODO Error handling
        return $response->code;
    }

    public static function getAccessToken($request_code) {
        $payload = array(
            'consumer_key' => Config::POCKET_API_KEY,
            'code' => $request_code
        );
        $response = self::doRequest('https://getpocket.com/v3/oauth/authorize', json_encode($payload));
        // @TODO Error handling
        return $response->access_token;
    }

    private static function preparePayload(&$payload, $access_token) {
        $payload['consumer_key'] = Config::POCKET_API_KEY;
        $payload['access_token'] = $access_token;
    }

    private static function doRequest($url, $json) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=UTF-8", "X-Accept: application/json"));
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200 || !$response) {
            error_log("Error using cURL to connect to the Pocket API: " . curl_error($ch) . ". HTTP response code: " . $info['http_code']);
            return FALSE;
        }
        return json_decode($response);
    }
}

?>
