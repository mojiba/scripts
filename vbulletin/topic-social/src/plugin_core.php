<?php

if (!defined('VB_AREA')) {
    exit;
}

function topicsocial_get_options()
{
    global $vbulletin;

    return isset($vbulletin->options) ? $vbulletin->options : array();
}

function topicsocial_is_enabled()
{
    $options = topicsocial_get_options();
    return !empty($options['topicsocial_enabled']);
}

function topicsocial_get_mode()
{
    $options = topicsocial_get_options();
    return !empty($options['topicsocial_mode']) ? $options['topicsocial_mode'] : 'manual';
}

function topicsocial_allow_resend()
{
    $options = topicsocial_get_options();
    return !empty($options['topicsocial_allow_resend']);
}

function topicsocial_get_monitored_forumids()
{
    $options = topicsocial_get_options();
    $raw = isset($options['topicsocial_forumids']) ? $options['topicsocial_forumids'] : '';

    $parts = preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    $forumids = array();

    foreach ($parts as $part) {
        $forumid = intval($part);
        if ($forumid > 0) {
            $forumids[$forumid] = $forumid;
        }
    }

    return $forumids;
}

function topicsocial_is_monitored_forum($forumid)
{
    $forumids = topicsocial_get_monitored_forumids();
    $forumid = intval($forumid);

    return isset($forumids[$forumid]);
}

function topicsocial_get_hashtags()
{
    return '#ad #anúncio';
}

function topicsocial_get_template()
{
    $options = topicsocial_get_options();

    if (!empty($options['topicsocial_message_template'])) {
        return $options['topicsocial_message_template'];
    }

    return "{title}\n\n{price}\n\n{url}\n\n{hashtags}";
}

function topicsocial_logging_enabled()
{
    $options = topicsocial_get_options();
    return !empty($options['topicsocial_log_enabled']);
}

function topicsocial_get_option($key, $default = null)
{
    $options = topicsocial_get_options();
    return isset($options[$key]) ? $options[$key] : $default;
}
