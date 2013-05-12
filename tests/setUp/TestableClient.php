<?php

class TestableClient extends VGS_Client {

    /**
     * Populate this with mock data for when $_REQUEST would be used by SDK client
     * 
     * @var array
     */
    public $REQUEST = array();

    /**
     * Populate this with mock data for when $_SERVER would be used by SDK client
     * 
     * @var array
     */
    public $SERVER = array();


    /**
     * Get a paramter from $_REQUEST - overwriteable for testing
     * 
     * @param string $param name of key in $_REQUEST array
     * @return mixed/null 
     */
    protected function _getRequestParam($param) {
        return array_key_exists($param, $this->REQUEST) ? $this->REQUEST[$param] : null;
    }

    /**
     * Get a paramter from $_SERVER - overwriteable for testing
     * 
     * @param string $param name of key in $_SERVER array
     * @return mixed/null 
     */
    protected function _getServerParam($param) {
        return array_key_exists($param, $this->SERVER) ? $this->SERVER[$param] : null;
    }

    public function __call($name, $args) {
        if (method_exists($this, $name)) {
            return call_user_func_array(array($this,$name), $args);
        }
    }
}