<?php

if (!defined('VB_AREA')) {
    exit;
}

require_once dirname(__FILE__) . '/plugin_core.php';
require_once dirname(__FILE__) . '/template_engine.php';
require_once dirname(__FILE__) . '/extractors.php';
require_once dirname(__FILE__) . '/publishers.php';
require_once dirname(__FILE__) . '/service.php';

function topicsocial_fetch_thread_by_id($threadid)
{
    global $vbulletin;

    $threadid = intval($threadid);
    if ($threadid <= 0 || empty($vbulletin->db)) {
        return array();
    }

    $sql = "
        SELECT threadid, forumid, title, postuserid, open, visible
        FROM " . TABLE_PREFIX . "thread
        WHERE threadid = " . $threadid . "
        LIMIT 1
    ";

    return $vbulletin->db->query_first($sql);
}

function topicsocial_fetch_firstpost_by_threadid($threadid)
{
    global $vbulletin;

    $threadid = intval($threadid);
    if ($threadid <= 0 || empty($vbulletin->db)) {
        return array();
    }

    $sql = "
        SELECT postid, threadid, title, pagetext, userid, visible
        FROM " . TABLE_PREFIX . "post
        WHERE threadid = " . $threadid . "
        ORDER BY postid ASC
        LIMIT 1
    ";

    return $vbulletin->db->query_first($sql);
}

function topicsocial_get_status_row($threadid)
{
    global $vbulletin;

    $threadid = intval($threadid);
    if ($threadid <= 0 || empty($vbulletin->db)) {
        return array();
    }

    $sql = "
        SELECT *
        FROM plugin_topicsocial_status
        WHERE threadid = " . $threadid . "
        LIMIT 1
    ";

    return $vbulletin->db->query_first($sql);
}

function topicsocial_should_auto_send(array $thread)
{
    if (!topicsocial_is_enabled()) {
        return false;
    }

    if (topicsocial_get_mode() !== 'auto') {
        return false;
    }

    if (empty($thread['forumid']) || !topicsocial_is_monitored_forum($thread['forumid'])) {
        return false;
    }

    return true;
}

function topicsocial_process_thread_by_id($threadid)
{
    $thread = topicsocial_fetch_thread_by_id($threadid);
    if (empty($thread) || empty($thread['threadid'])) {
        return array('success' => false, 'status' => 'thread_not_found');
    }

    $firstpost = topicsocial_fetch_firstpost_by_threadid($threadid);
    if (empty($firstpost) || empty($firstpost['postid'])) {
        return array('success' => false, 'status' => 'post_not_found');
    }

    return topicsocial_process_thread($thread, $firstpost);
}

function topicsocial_is_admin_user()
{
    global $vbulletin;

    if (empty($vbulletin->userinfo) || empty($vbulletin->userinfo['userid'])) {
        return false;
    }

    return !empty($vbulletin->userinfo['adminpermissions']);
}

function topicsocial_render_admin_box_html($threadid)
{
    $threadid = intval($threadid);
    if ($threadid <= 0 || !topicsocial_is_admin_user()) {
        return '';
    }

    $status = topicsocial_get_status_row($threadid);
    $statusText = 'Tópico ainda não enviado.';

    if (!empty($status['status'])) {
        switch ($status['status']) {
            case 'sent':
                $statusText = 'Enviado com sucesso para os canais configurados.';
                break;
            case 'partial':
                $statusText = 'Enviado parcialmente.';
                break;
            case 'error':
                $statusText = 'Erro no envio.';
                break;
            case 'skipped':
                $statusText = 'Envio ignorado.';
                break;
        }
    }

    $buttonLabel = !empty($status) && topicsocial_allow_resend()
        ? 'Reenviar para o X e Telegram'
        : 'Enviar para o X e Telegram';

    $actionUrl = 'misc.php?do=topicsocial_send&threadid=' . $threadid;

    $html = '';
    $html .= '<div class="smallfont" style="margin:10px 0;padding:10px;border:1px solid #ccc;background:#f8f8f8;">';
    $html .= '<strong>Topic Social</strong><br />';
    $html .= htmlspecialchars_uni($statusText) . '<br />';

    if (!empty($status['last_attempt_at'])) {
        $html .= 'Última tentativa: ' . htmlspecialchars_uni($status['last_attempt_at']) . '<br />';
    }

    if (!empty($status['last_error'])) {
        $html .= 'Erro: ' . nl2br(htmlspecialchars_uni($status['last_error'])) . '<br />';
    }

    $html .= '<a class="button" href="' . htmlspecialchars_uni($actionUrl) . '">' . htmlspecialchars_uni($buttonLabel) . '</a>';
    $html .= '</div>';

    return $html;
}
