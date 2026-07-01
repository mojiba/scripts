<?php

if (!defined('VB_AREA')) {
    exit;
}

function topicsocial_extract_first_image_url($text)
{
    if (!is_string($text) || $text === '') {
        return '';
    }

    if (preg_match('/\[img\](.*?)\[\/img\]/is', $text, $matches)) {
        return trim($matches[1]);
    }

    if (preg_match('/https?:\/\/[^\s\]]+\.(?:jpg|jpeg|png|gif|webp)/i', $text, $matches)) {
        return trim($matches[0]);
    }

    return '';
}

function topicsocial_extract_first_url($text)
{
    if (!is_string($text) || $text === '') {
        return '';
    }

    if (preg_match('/https?:\/\/[^\s\]]+/i', $text, $matches)) {
        return trim($matches[0]);
    }

    return '';
}

function topicsocial_extract_price($title, $message)
{
    $price = topicsocial_extract_price_from_text($title);
    if ($price !== '') {
        return $price;
    }

    return topicsocial_extract_price_from_text($message);
}

function topicsocial_extract_price_from_text($text)
{
    if (!is_string($text) || $text === '') {
        return '';
    }

    $patterns = array(
        '/R\$\s*[0-9\.,]+/u',
        '/[0-9\.,]+\s*reais/u',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[0]);
        }
    }

    return '';
}

function topicsocial_strip_price_from_title($title, $price)
{
    if (!is_string($title) || $title === '' || !is_string($price) || $price === '') {
        return trim((string) $title);
    }

    $clean = str_replace($price, '', $title);
    $clean = preg_replace('/\s{2,}/', ' ', $clean);
    $clean = trim($clean, " \t\n\r\0\x0B-–|:");

    return $clean;
}
