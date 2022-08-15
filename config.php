<?php
$config = array(
    'proxy' => array(
        "dest_host" => "127.0.0.1",
        "dest_port" => 7777,
        "dest_scheme" => "http"
    ),
    'timeouts' => array(
        'connect' => 0, // in seconds, 0 means no timeout
        'global' => 600 // in seconds
    ),
    "caching" => array(
        "enabled" => true,
        "driver" => "Files",
        "methods" => array('GET'),
        "rules" => array(
            "/.png$/",
            "/.gif$/",
            "/.ico$/",
            "/.jpg$/",
            "/.jpeg$/",
            "/.woff$/",
            "/.woff2$/",
            "/.svg$/",
            "/.tif$/",
            "/.tiff$/",
            "/.ttf$/",
            "/.webp$/",
            "/.otf$/",
            "/.css$/",
            "/.js$/"
        ),
        "ttl" => 7200, // in seconds,
        "ignore_cache_controls" => false
    ),
    'verify_ssl' => true,
    'expose_client_ip' => true,
    'additional_headers' => array(
        /* Example:
        "My-Header" => "My Value"
        */
    )
);