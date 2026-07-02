<?php

if (!defined('VB_AREA')) {
    exit;
}

require_once dirname(__FILE__) . '/vbulletin_integration.php';

function topicsocial_hook_newthread_complete($threadid)
{
    $threadid = intval($threadid);
    if ($threadid <= 0) {
        return false;
    }

    $thread = topicsocial_fetch_thread_by_id($threadid);
    if (empty($thread) || !topicsocial_should_auto_send($thread)) {
        return false;
    }

    topicsocial_process_thread_by_id($threadid);
    return true;
}

function topicsocial_handle_manual_send_request()
{
    global $vbulletin;

    if (!topicsocial_is_admin_user()) {
        return false;
    }

    if (!topicsocial_is_manual_request()) {
        return false;
    }

    $threadid = isset($vbulletin->GPC['threadid']) ? intval($vbulletin->GPC['threadid']) : 0;
    if ($threadid <= 0) {
        return false;
    }

    topicsocial_process_thread_by_id($threadid);
    exec_header_redirect('showthread.php?t=' . $threadid);
    return true;
}

function topicsocial_append_admin_box_to_output($threadid, $existingHtml)
{
    $box = topicsocial_render_admin_box_html($threadid);
    if ($box === '') {
        return $existingHtml;
    }

    return $box . $existingHtml;
}

function topicsocial_hook_showthread_start()
{
    if (topicsocial_is_manual_request()) {
        return topicsocial_handle_manual_send_request();
    }

    return false;
}
