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

    $threadid = isset($vbulletin->GPC['threadid']) ? intval($vbulletin->GPC['threadid']) : 0;
    if ($threadid <= 0) {
        return false;
    }

    $result = topicsocial_process_thread_by_id($threadid);

    $redirect = 'showthread.php?t=' . $threadid;
    if (!empty($result['success'])) {
        exec_header_redirect($redirect);
    }

    exec_header_redirect($redirect);
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
