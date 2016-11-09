<?php
namespace atomar;

/**
 * Implements hook_permission()
 */
function permission() {
    // return string array of permissions to be created
    return array();
}

function pre_process_boot() {

}

function post_process_boot() {

}

function pre_process_page() {

}

/**
 * Implements hook_menu()
 */
function menu() {
    // return an array of menu items
    return array();
}

/**
 * Implements hook_url()
 */
function url() {
    $urls = array();
    return $urls;
}

/**
 * Implements hook_libraries()
 */
function libraries() {
    return array();
}

/**
 * Implements hook_cron()
 */
function cron() {
    // execute execute cron operations here
}

/**
 * Implements hook_twig_function()
 */
function twig_function() {
    // return an array of key value pairs.
    // key: twig_function_name
    // value: actual_function_name
    // You may use object functions as well
    // e.g. ObjectClass::actual_function_name
    return array();
}