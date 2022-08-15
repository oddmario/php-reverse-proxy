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
    'verify_ssl' => true,
    'expose_client_ip' => true,
    'additional_headers' => array(
        /* Example:
        "My-Header" => "My Value"
        */
    )
);