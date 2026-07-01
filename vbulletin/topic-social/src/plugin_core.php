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

function topicsocial_get_supported_channels()
{
    return array(
        'telegram' => array(
            'label' => 'Telegram',
            'enabled_option' => 'topicsocial_telegram_enabled',
        ),
        'discord' => array(
            'label' => 'Discord',
            'enabled_option' => 'topicsocial_discord_enabled',
        ),
        'x' => array(
            'label' => 'X',
            'enabled_option' => 'topicsocial_x_enabled',
        ),
    );
}

function topicsocial_get_channel_config($channel)
{
    $channels = topicsocial_get_supported_channels();
    return isset($channels[$channel]) ? $channels[$channel] : array();
}

function topicsocial_is_channel_supported($channel)
{
    $config = topicsocial_get_channel_config($channel);
    return !empty($config);
}

function topicsocial_is_channel_enabled($channel)
{
    $config = topicsocial_get_channel_config($channel);
    if (empty($config) || empty($config['enabled_option'])) {
        return false;
    }

    return !empty(topicsocial_get_option($config['enabled_option'], 0));
}

function topicsocial_get_enabled_channels()
{
    $enabled = array();
    $channels = topicsocial_get_supported_channels();

    foreach ($channels as $channel => $config) {
        if (topicsocial_is_channel_enabled($channel)) {
            $enabled[$channel] = $config;
        }
    }

    return $enabled;
}
