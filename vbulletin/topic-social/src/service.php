<?php

if (!defined('VB_AREA')) {
    exit;
}

function topicsocial_build_thread_url(array $thread)
{
    $threadid = isset($thread['threadid']) ? intval($thread['threadid']) : 0;
    if ($threadid <= 0) {
        return '';
    }

    $options = topicsocial_get_options();
    $bburl = isset($options['bburl']) ? rtrim($options['bburl'], '/') : '';

    if ($bburl === '') {
        return '';
    }

    return $bburl . '/showthread.php?t=' . $threadid;
}

function topicsocial_build_payload(array $thread, array $firstpost)
{
    $rawTitle = isset($thread['title']) ? $thread['title'] : '';
    $rawMessage = isset($firstpost['pagetext']) ? $firstpost['pagetext'] : '';

    $price = topicsocial_extract_price($rawTitle, $rawMessage);
    $title = topicsocial_strip_price_from_title($rawTitle, $price);
    $url = topicsocial_build_thread_url($thread);
    $url = topicsocial_shorten_url($url);
    $imageUrl = topicsocial_extract_first_image_url($rawMessage);

    $vars = array(
        'title' => $title,
        'price' => $price,
        'url' => $url,
        'hashtags' => topicsocial_get_hashtags(),
    );

    $text = topicsocial_render_template(topicsocial_get_template(), $vars);

    return array(
        'threadid' => isset($thread['threadid']) ? intval($thread['threadid']) : 0,
        'forumid' => isset($thread['forumid']) ? intval($thread['forumid']) : 0,
        'title' => $title,
        'raw_title' => $rawTitle,
        'price' => $price,
        'url' => $url,
        'image_url' => $imageUrl,
        'text' => $text,
    );
}

function topicsocial_publish_payload(array $payload)
{
    $result = array(
        'telegram' => array('success' => false, 'skipped' => true),
        'x' => array('success' => false, 'skipped' => true),
        'overall_status' => 'pending',
    );

    if (topicsocial_get_option('topicsocial_telegram_enabled', 0)) {
        $telegramResult = topicsocial_publish_to_telegram($payload);
        $telegramResult['skipped'] = false;
        $result['telegram'] = $telegramResult;
    }

    if (topicsocial_get_option('topicsocial_x_enabled', 0)) {
        $xResult = topicsocial_publish_to_x($payload);
        $xResult['skipped'] = false;
        $result['x'] = $xResult;
    }

    $telegramSent = (!empty($result['telegram']['success']));
    $xSent = (!empty($result['x']['success']));
    $telegramEnabled = topicsocial_get_option('topicsocial_telegram_enabled', 0) ? true : false;
    $xEnabled = topicsocial_get_option('topicsocial_x_enabled', 0) ? true : false;

    if (($telegramEnabled && $telegramSent) && ($xEnabled && $xSent)) {
        $result['overall_status'] = 'sent';
    } elseif (($telegramEnabled && $telegramSent) || ($xEnabled && $xSent)) {
        $result['overall_status'] = 'partial';
    } elseif (!$telegramEnabled && !$xEnabled) {
        $result['overall_status'] = 'skipped';
    } else {
        $result['overall_status'] = 'error';
    }

    return $result;
}

function topicsocial_save_publish_status($threadid, array $payload, array $result)
{
    global $vbulletin;

    $threadid = intval($threadid);
    if ($threadid <= 0 || empty($vbulletin->db)) {
        return false;
    }

    $db = $vbulletin->db;
    $attemptedAt = date('Y-m-d H:i:s');
    $successAt = ($result['overall_status'] === 'sent' || $result['overall_status'] === 'partial') ? $attemptedAt : null;

    $status = $db->escape_string($result['overall_status']);
    $lastMessageText = $db->escape_string(isset($payload['text']) ? $payload['text'] : '');
    $lastUrl = $db->escape_string(isset($payload['url']) ? $payload['url'] : '');
    $lastImageUrl = $db->escape_string(isset($payload['image_url']) ? $payload['image_url'] : '');
    $lastError = $db->escape_string(topicsocial_collect_errors($result));

    $sentToTelegram = !empty($result['telegram']['success']) ? 1 : 0;
    $sentToX = !empty($result['x']['success']) ? 1 : 0;

    $sql = "
        INSERT INTO plugin_topicsocial_status
            (threadid, last_attempt_at, last_success_at, status, sent_to_telegram, sent_to_x, last_message_text, last_url, last_image_url, last_error)
        VALUES
            ($threadid, '" . $attemptedAt . "', " . ($successAt ? "'" . $successAt . "'" : "NULL") . ", '" . $status . "', $sentToTelegram, $sentToX, '" . $lastMessageText . "', '" . $lastUrl . "', '" . $lastImageUrl . "', '" . $lastError . "')
        ON DUPLICATE KEY UPDATE
            last_attempt_at = VALUES(last_attempt_at),
            last_success_at = VALUES(last_success_at),
            status = VALUES(status),
            sent_to_telegram = VALUES(sent_to_telegram),
            sent_to_x = VALUES(sent_to_x),
            last_message_text = VALUES(last_message_text),
            last_url = VALUES(last_url),
            last_image_url = VALUES(last_image_url),
            last_error = VALUES(last_error)
    ";

    $db->query_write($sql);

    if (topicsocial_logging_enabled()) {
        topicsocial_insert_log_row($threadid, 'telegram', $payload, $result['telegram']);
        topicsocial_insert_log_row($threadid, 'x', $payload, $result['x']);
    }

    return true;
}

function topicsocial_insert_log_row($threadid, $channel, array $payload, array $channelResult)
{
    global $vbulletin;

    $threadid = intval($threadid);
    if ($threadid <= 0 || empty($vbulletin->db)) {
        return false;
    }

    if (!empty($channelResult['skipped'])) {
        return true;
    }

    $db = $vbulletin->db;
    $attemptedAt = date('Y-m-d H:i:s');
    $channel = $db->escape_string($channel);
    $success = !empty($channelResult['success']) ? 1 : 0;
    $messageText = $db->escape_string(isset($payload['text']) ? $payload['text'] : '');
    $targetUrl = $db->escape_string(isset($payload['url']) ? $payload['url'] : '');
    $imageUrl = $db->escape_string(isset($payload['image_url']) ? $payload['image_url'] : '');
    $responseText = $db->escape_string(isset($channelResult['response']) ? $channelResult['response'] : '');
    $errorText = $db->escape_string(isset($channelResult['error']) ? $channelResult['error'] : '');

    $sql = "
        INSERT INTO plugin_topicsocial_log
            (threadid, channel, attempted_at, success, message_text, target_url, image_url, response_text, error_text)
        VALUES
            ($threadid, '" . $channel . "', '" . $attemptedAt . "', $success, '" . $messageText . "', '" . $targetUrl . "', '" . $imageUrl . "', '" . $responseText . "', '" . $errorText . "')
    ";

    $db->query_write($sql);

    return true;
}

function topicsocial_collect_errors(array $result)
{
    $errors = array();

    if (!empty($result['telegram']['error'])) {
        $errors[] = 'telegram: ' . $result['telegram']['error'];
    }

    if (!empty($result['x']['error'])) {
        $errors[] = 'x: ' . $result['x']['error'];
    }

    return implode("\n", $errors);
}

function topicsocial_process_thread(array $thread, array $firstpost)
{
    $threadid = isset($thread['threadid']) ? intval($thread['threadid']) : 0;
    $forumid = isset($thread['forumid']) ? intval($thread['forumid']) : 0;

    if (!topicsocial_is_enabled()) {
        return array('success' => false, 'status' => 'disabled');
    }

    if ($threadid <= 0 || $forumid <= 0) {
        return array('success' => false, 'status' => 'invalid');
    }

    if (!topicsocial_is_monitored_forum($forumid)) {
        return array('success' => false, 'status' => 'unmonitored');
    }

    $payload = topicsocial_build_payload($thread, $firstpost);
    $result = topicsocial_publish_payload($payload);
    topicsocial_save_publish_status($threadid, $payload, $result);

    return array(
        'success' => ($result['overall_status'] === 'sent' || $result['overall_status'] === 'partial'),
        'status' => $result['overall_status'],
        'payload' => $payload,
        'result' => $result,
    );
}
