<?php 
function gen_signature($app_secret, $timestamp, $nonce) {
    $tmp = array($app_secret, $timestamp, $nonce);
    sort($tmp, SORT_STRING);
    return  sha1(implode($tmp));
}
function valid_signature($app_secret, $timestamp, $nonce, $signature) {
     $str = gen_signature($app_secret, $timestamp, $nonce);
    return  $str== $signature;
}
