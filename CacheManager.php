<?php
use Phpfastcache\Helper\Psr16Adapter;

class CacheManager {
    private $adapter = NULL;

    public function __construct($driver = 'Files'){
        $this->adapter = new Psr16Adapter($driver);
    }

    public function has($key) {
        return $this->adapter->has($key);
    }

    public function set($key, $value, $expiry = 7200) {
        return $this->adapter->set($key, $value, $expiry);
    }

    public function get($key) {
        return $this->adapter->get($key);
    }

    public function delete($key) {
        return $this->adapter->delete($key);
    }

    public function clear() {
        return $this->adapter->clear();
    }
}