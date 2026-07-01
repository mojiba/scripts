<?php

if (!defined('VB_AREA')) {
    exit;
}

function topicsocial_render_template($template, array $vars)
{
    $rendered = strtr($template, array(
        '{title}' => isset($vars['title']) ? $vars['title'] : '',
        '{price}' => isset($vars['price']) ? $vars['price'] : '',
        '{url}' => isset($vars['url']) ? $vars['url'] : '',
        '{hashtags}' => isset($vars['hashtags']) ? $vars['hashtags'] : '',
    ));

    $rendered = preg_replace("/\n{3,}/", "\n\n", $rendered);
    $rendered = trim($rendered);

    return $rendered;
}
