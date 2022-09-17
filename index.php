<?php
set_time_limit(0);

require( __DIR__ . '/vendor/autoload.php' );
require_once( __DIR__ . '/essentials.php' );
require_once( __DIR__ . '/config.php' );
require_once( __DIR__ . '/CacheManager.php' );

if( isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

$router = new \Bramus\Router\Router();

$router->all('(.*)', function() {
    global $config;

    $request = array();
    $request['method'] = strtoupper($_SERVER['REQUEST_METHOD']);
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

    if( $config['caching']['enabled'] == true ) {
        $cm = new CacheManager($config['caching']['driver']);

        $cache_key = "[" . $request['method'] . "] " . $request['endpoint'];
        /*
            Fix any unsupported characters as stated @ https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Unsupported-characters-in-key-identifiers
        */
        $cache_key = str_replace("/", "[phprp-sl]", $cache_key);
        $cache_key = str_replace("\\", "[phprp-bsl]", $cache_key);
        $cache_key = str_replace("{", "[phprp-bco]", $cache_key);
        $cache_key = str_replace("}", "[phprp-bce]", $cache_key);
        $cache_key = str_replace("(", "[phprp-bo]", $cache_key);
        $cache_key = str_replace(")", "[phprp-be]", $cache_key);
        $cache_key = str_replace("@", "[phprp-at]", $cache_key);
        $cache_key = str_replace(":", "[phprp-col]", $cache_key);

        if( $cm->has($cache_key) ) {
            $cached_response = $cm->get($cache_key);
            http_response_code(intval( $cached_response['http_code'] ));
            foreach( $cached_response['headers'] as $name => $value ) {
                header("$name: $value");
            }
            echo $cached_response['body'];
            die();
        }
    }
    
    $headers = requestHeaders();
    foreach( $headers as $name => $value ) {
        array_push($request['headers'], "$name: $value");
    }

    $res_headers = array();
    
    if( $config['proxy']['dest_type'] == "tcp" ) {
        $ch = curl_init( $config['proxy']['dest_scheme'] . '://' . $config['proxy']['tcp_host'] . ':' . $config['proxy']['tcp_port'] . $request['endpoint'] );
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['proxy']['dest_scheme'] . ':/proxy' . $request['endpoint']);
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $config['proxy']['unix_socket_path']);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request['method']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request['body']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if( $config['verify_ssl'] == true ) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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

        $res_headers[strtolower(trim($header[0]))] = trim($header[1]);
        
        return $len;
    });
    $response = curl_exec($ch);
    $res_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if( $config['caching']['enabled'] == true ) {
        $cachable_http_methods = array_change_key_case($config['caching']['methods'], CASE_UPPER);
        if( in_array($request['method'], $cachable_http_methods) ) {
            $to_cache = false;
            $cache_control_passed = true;

            if( $config['caching']['ignore_cache_controls'] == false ) {
                if( isset($res_headers['cache-control']) ) {
                    if( strpos($res_headers['cache-control'], 'private') !== false || strpos($res_headers['cache-control'], 'no-store') !== false || strpos($res_headers['cache-control'], 'no-cache') !== false || strpos($res_headers['cache-control'], 'max-age=0') !== false ) {
                        $cache_control_passed = false;
                    }
                }
            }
            
            if( $cache_control_passed == true ) {
                foreach( $config['caching']['rules'] as $rule ) {
                    if( preg_match($rule, $request['endpoint']) ) {
                        $to_cache = true;
                        break;
                    }
                }
            }

            if( $to_cache == true ) {
                $cm->set($cache_key, array(
                    "headers" => $res_headers,
                    "http_code" => intval( $res_httpcode ),
                    "body" => $response
                ), $config['caching']['ttl']);
            }
        }
    }

    http_response_code(intval( $res_httpcode ));
    foreach( $res_headers as $name => $value ) {
        header("$name: $value");
    }
    
    echo $response;
});

$router->run();