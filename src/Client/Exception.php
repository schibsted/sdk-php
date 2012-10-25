<?php
/**
 * Thrown when an API call returns an exception.
 */
class VGS_Client_Exception extends Exception {

    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;

    /**
     * The raw text response from the API server
     */
    protected $raw;
    /**
     * The raw text response from the API server
     */
    protected $code;
    /**
     * Make a new API Exception with the given result.
     *
     * @param Array $result the result from the API server
     */
    public function __construct($result, $raw = null) {
        $this->result = $result;
        $this->raw = $raw;
        $this->code = $code = isset($result['error_code']) ? $result['error_code'] : 0;
        if (isset($result['error_description'])) {
            // OAuth 2.0 Draft 10 style
            $msg = $result['error_description'];
        } else if (isset($result['error']) && is_array($result['error']) && isset($result['error']['description'])) {
            $this->code = $code = $result['error']['code'];
            $msg  = $result['error']['description'];
        } else if (isset($result['error']) && !is_array($result['error']) && strlen($result['error']) > 0) {
            $msg = $result['error'];
        } else {
            $msg = 'Unknown Error. Check getResult() and getRaw()';
        }
        parent::__construct($msg, intval($code));
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @returns Array the result from the API server
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Return the raw text response from the API server
     *
     * @return string
     */
    public function getRaw() {
        return $this->raw;
    }

    /**
     * Returns the associated type for the error. This will default to
     * 'Exception' when a type is not available.
     *
     * @return String
     */
    public function getType() {
        if (isset($this->result['error'])) {
            $error = $this->result['error'];
            if (is_string($error)) {
                // OAuth 2.0 Draft 10 style
                return $error;
            } else if (is_array($error)) {
                // OAuth 2.0 Draft 00 style
                if (isset($error['type'])) {
                    return $error['type'];
                }
            }
        }
        return 'Exception';
    }

    /**
     * To make debugging easier.
     *
     * @returns String the string representation of the error
     */
    public function __toString() {
        $str = $this->getType() . ': ';
        if ($this->code != 0) {
            $str .= $this->code . ': ';
        }
        return $str . $this->message;
    }
}