<?php

if (!defined('VB_AREA')) {
    exit;
}

function topicsocial_http_post_json($url, array $payload, array $headers = array())
{
    $ch = curl_init($url);
    $json = json_encode($payload);

    $requestHeaders = array_merge(array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json),
    ), $headers);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array(
        'success' => ($response !== false && $httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error,
    );
}

function topicsocial_publish_to_telegram(array $payload)
{
    $token = topicsocial_get_option('topicsocial_telegram_bot_token', '');
    $chatId = topicsocial_get_option('topicsocial_telegram_chat_id', '');

    if ($token === '' || $chatId === '') {
        return array('success' => false, 'error' => 'Telegram credentials not configured.');
    }

    $hasImage = !empty($payload['image_url']);
    if ($hasImage) {
        $url = 'https://api.telegram.org/bot' . $token . '/sendPhoto';
        $body = array(
            'chat_id' => $chatId,
            'photo' => $payload['image_url'],
            'caption' => isset($payload['text']) ? $payload['text'] : '',
        );

        return topicsocial_http_post_json($url, $body);
    }

    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $body = array(
        'chat_id' => $chatId,
        'text' => isset($payload['text']) ? $payload['text'] : '',
        'disable_web_page_preview' => false,
    );

    return topicsocial_http_post_json($url, $body);
}

function topicsocial_publish_to_x(array $payload)
{
    return array(
        'success' => false,
        'error' => 'X publishing not implemented yet.',
    );
}

function topicsocial_publish_to_channel($channel, array $payload)
{
    switch ($channel) {
        case 'telegram':
            return topicsocial_publish_to_telegram($payload);

        case 'x':
            return topicsocial_publish_to_x($payload);
    }

    return array(
        'success' => false,
        'error' => 'Unsupported channel: ' . $channel,
    );
}

function topicsocial_shorten_url($url)
{
    if (!topicsocial_get_option('topicsocial_cuttly_enabled', 0)) {
        return $url;
    }

    $apiKey = topicsocial_get_option('topicsocial_cuttly_api_key', '');
    if ($apiKey === '' || $url === '') {
        return $url;
    }

    $endpoint = 'https://cutt.ly/api/api.php?key=' . urlencode($apiKey) . '&short=' . urlencode($url);
    $response = @file_get_contents($endpoint);

    if (!$response) {
        return $url;
    }

    $json = json_decode($response, true);
    if (!is_array($json) || empty($json['url']['shortLink'])) {
        return $url;
    }

    return $json['url']['shortLink'];
}
