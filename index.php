<?php
set_time_limit(0);

require( __DIR__ . '/vendor/autoload.php' );
require_once( __DIR__ . '/essentials.php' );
require_once( __DIR__ . '/config.php' );

if( isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

$router = new \Bramus\Router\Router();

$router->all('(.*)', function() {
    global $config;

    $request = array();
    $request['method'] = $_SERVER['REQUEST_METHOD'];
    $request['body'] = file_get_contents('php://input');
    $request['endpoint'] = $_SERVER['REQUEST_URI'];
    $request['headers'] = array();
    if( $config['expose_client_ip'] == true ) {
        array_push($request['headers'], "X-Forwarded-For: " . $_SERVER['REMOTE_ADDR']);
        array_push($request['headers'], "X-Real-IP: " . $_SERVER['REMOTE_ADDR']);
    }
    foreach( $config['additional_headers'] as $name => $value ) {
        array_push($request['headers'], "$name: $value");
    }
    
    $headers = requestHeaders();
    foreach( $headers as $name => $value ) {
        array_push($request['headers'], "$name: $value");
    }

    $res_headers = array();
    
    $ch = curl_init( $config['proxy']['dest_scheme'] . '://' . $config['proxy']['dest_host'] . ':' . $config['proxy']['dest_port'] . $request['endpoint'] );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request['method']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request['body']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if( $config['verify_ssl'] == true ) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $config['timeouts']['connect']); 
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeouts']['global']);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$res_headers) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2)
        return $len;

        $res_headers[trim($header[0])] = trim($header[1]);
        
        return $len;
    });
    $response = curl_exec($ch);
    $res_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    http_response_code(intval( $res_httpcode ));
    foreach( $res_headers as $name => $value ) {
        header("$name: $value");
    }
    
    echo $response;
});

$router->run();