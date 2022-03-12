<?php

/**
 * Plugin Name: Soil by Root and Bloom
 */

add_action('wp', function () {
    require_once __DIR__ . '/vendor/autoload.php';
    new RootBloom\Soil();
});