<?php
/** @see VGS_Client_Exception */
require_once dirname(__FILE__).'/Client/Exception.php';

/**
 * SPiD PHP SDK
 */
if (!function_exists('curl_init')) {
    throw new Exception('SPiD needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
    throw new Exception('SPiD needs the JSON PHP extension.');
}

/**
 * Provides access to the SPiD Platform.
 */
class VGS_Client {
    /**
     * Constructor key constants
     */
    const CLIENT_ID         = 'client_id';
    const CLIENT_SECRET     = 'client_secret';
    const CLIENT_SIGN_SECRET= 'sig_secret';
    const REDIRECT_URI      = 'redirect_uri';
    const DOMAIN            = 'domain';
    const COOKIE            = 'cookie';
    const PRODUCTION        = 'production';
    const DEBUG             = 'debug';
    const HTTPS             = 'https';
    const XITI              = 'xiti';
    const PRODUCTION_DOMAIN = 'production_domain';
    const STAGING_DOMAIN    = 'staging_domain';
    const API_VERSION       = 'api_version';
    const CONTEXT_CLIENT    = 'context_client_id';

    /**
     * SDK Version.
     */
    const VERSION = '2.2';

    /**
     * Oauth Token URL
     * @var string
     */
    const _VGS_OAUTH_TOKEN_URL = '/oauth/token';

    /**
     * Default staging server domain
     * @var string
     */
    const _VGS_DEFAULT_STAGING_DOMAIN = 'stage.payment.schibsted.no';

    /**
     * Default production server domain
     * @var string
     */
    const _VGS_DEFAULT_PRODUCTION_DOMAIN = 'payment.schibsted.no';

    /**
     * Default options for curl.
     */
    public static $CURL_OPTS = array(
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'spid-php-2.2'
     );

    /**
     * List of query parameters that get automatically dropped when rebuilding
     * the current URI.
     */
    protected static $DROP_QUERY_PARAMS = array(
            'code',
            'error',
            'session',
            'signed_request');

    /**
     * Contains the raw string result
     * @var string
     */
    public $raw;

    /**
     * Contains the full json decoded container result
     * @var array
     */
    public $container;

    /**
     * Debugging
     */
    protected $debug = false;
    protected $timer = array();
    protected $last_result_code = null;

    /**
     * HTTPS comunication ON/OFF
     */
    protected $https = true;

    /**
     * Production Server
     */
    protected $production = false;

    /**
     * Production Server domain
     */
    protected $production_domain = self::_VGS_DEFAULT_PRODUCTION_DOMAIN;

    /**
     * Staging Server domain
     */
    protected $staging_domain = self::_VGS_DEFAULT_STAGING_DOMAIN;

    /**
     * Use API Version
     */
    protected $api_version = null;

    /**
     * The Client ID.
     */
    protected $client_id;

    /**
     * The Client Secret.
     */
    protected $client_secret;

    /**
     * The Client Secret.
     */
    protected $client_sign_secret;

    /**
     * The Context Client ID.
     */
    protected $context_client_id;

    /**
     * The Redirect URI.
     */
    protected $redirect_uri;

    /**
     * The active user session, if one is available.
     */
    protected $session;

    /**
     * The data from the signed_request token.
     */
    protected $signedRequest;

    /**
     * Indicates that we already loaded the session as best as we could.
     */
    protected $sessionLoaded = false;

    /**
     * Indicates if Cookie support should be enabled.
     */
    protected $cookieSupport = false;

    /**
     * Base domain for the Cookie.
     */
    protected $baseDomain = '';

	/**
     * Access token (used in server 2 server calls)
     */
    protected $accessToken = false;

    /**
     * Refresh token (used in server 2 server calls)
     */
    protected $refreshToken = false;

	/**
     * Xiti configuration array
     * Used for tracking analytics between client and service
     */
    protected $xiti = array();

    /**
     * Indicates if the CURL based @ syntax for file uploads is enabled.
     */
    protected $fileUploadSupport = false;

    /**
     * URL Argument Separator used by http_build_query
     *
     * & or &amp;
     *
     * @var string;
     */
    public $argSeparator = '&amp;';

    public static $errors = array();

    /**
     * Time between last curl request and curl response
     *
     * @var int
     */
    protected $latency;

    /**
     * Initialize a SPiD Application.
     *
     * The configuration:
     * - client_id: the application ID
     * - client_secret: the application secret
     * - redirect_uri: the application redirect URI
     * - cookie: (optional) boolean true to enable cookie support
     * - domain: (optional) domain for the cookie
     * - api_version: (optional) which version of the API to make requests to
     * - debug: (optional) debugs all calls
     * - https: (optional) force https on/off (default on)
     * - file_upload: (optional) boolean indicating if file uploads are enabled
     *
     * @param Array $config the application configuration
     */
    public function __construct($config) {
        $this->debug = (isset($config['debug']) && $config['debug'] == true)?true:false;
        $this->setProduction((boolean) $config['production']);
        $this->https = (isset($config['https']) && $config['https'] == false)?false:true;
        if ($this->production) {
        	$this->https = true; // always defaults to https on production
        }
        if (!empty($config['production_domain'])) {
        	$this->production_domain = $config['production_domain'];
        }
        if (!empty($config['staging_domain'])) {
            $this->staging_domain = $config['staging_domain'];
        }
        if (!empty($config['api_version'])) {
            $this->api_version = $config['api_version'];
        }
        $this->setClientID($config['client_id']);
        $this->setClientSecret($config['client_secret']);
        if (!empty($config[static::CLIENT_SIGN_SECRET])) {
            $this->setClientSignSecret($config[static::CLIENT_SIGN_SECRET]);
        }

        $this->setRedirectUri($config['redirect_uri']);
        if (isset($config['cookie'])) {
            $this->setCookieSupport($config['cookie']);
        }
        if (isset($config['domain'])) {
            $this->setBaseDomain($config['domain']);
        }
        if (isset($config['file_upload'])) {
            $this->setFileUploadSupport($config['file_upload']);
        }
        if (isset($config['xiti'])) {
            $this->xiti = $config['xiti'];
        }
        if (isset($config[static::CONTEXT_CLIENT])) {
            $this->context_client_id = $config[static::CONTEXT_CLIENT];
        }
    }

    public function getDebugInfo() {
        $elapsed = array();
        if ($this->debug) {
        	if (is_array($this->timer) && count($this->timer) > 0) {
        		foreach ($this->timer as $function => $entries) {
                    if (is_array($entries['elapsed']) && count($entries['elapsed']) > 0) {
                        $return_vals = (array_sum($entries['elapsed']) / count($entries['elapsed']));
                    } elseif (is_array($entries['elapsed']) && count($entries['elapsed']) == 1) {
                        $return_vals = current($entries['elapsed']);
                    } else {
                        $return_vals = FALSE;
                    }
		            $elapsed[$function] = sprintf("%.4f", $return_vals)." seconds";
                }
        	}
        }
        return array('Average' => $elapsed,'Times' => $this->timer);
    }

    public function encodeSerializedUrlVariable($var) {
        return strtr(base64_encode(addslashes(gzcompress(serialize($var),9))), '+/=', '-_,');
    }

    public function getServerURL() {
        if ($this->isLive()) {
            return (($this->https)?'https://':'http://').$this->production_domain;
        } else {
            return (($this->https)?'https://':'http://').$this->staging_domain;
        }
    }
    private function getBaseURL($name = 'www') {
        switch ($name) {
            case 'api':
            case 'api_read':
                return self::getServerURL() . '/api/' . ($this->api_version == null ? '' : $this->api_version . '/');
                break;
            case 'www':
            default:
                return self::getServerURL() . '/';
                break;
        }
    }

    private function getTokenURL() {
        return self::getServerURL().self::_VGS_OAUTH_TOKEN_URL;
    }

    /**
     * Set the Client ID.
     *
     * @param string $client_id the Client ID
     */
    public function setClientID($client_id) {
        $this->client_id = $client_id;
        return $this;
    }

    /**
     * Get the Client ID.
     *
     * @return string the Client ID
     */
    public function getClientID() {
        return $this->client_id;
    }

    /**
     * Set the Context Client ID.
     *
     * @param string $context_client_id the Client ID
     */
    public function setContextClientID($context_client_id) {
        $this->context_client_id = $context_client_id;
    }

    /**
     * Get the Client ID.
     *
     * @return string the Client ID
     */
    public function getContextClientID() {
        return $this->context_client_id;
    }

    /**
     * Set the Client Secret.
     *
     * @param string $client_secret the Client Secret
     */
    public function setClientSecret($client_secret) {
        $this->client_secret = $client_secret;
        return $this;
    }

    /**
     * Get the Client Secret.
     *
     * @return string the Client Secret
     */
    public function getClientSecret() {
        return $this->client_secret;
    }

    /**
     * Set the Client Signature Secret.
     *
     * @param string $client_sign_secret the Client Secret
     */
    public function setClientSignSecret($client_sign_secret) {
        $this->client_sign_secret = $client_sign_secret;
        return $this;
    }

    /**
     * Get the Client Secret.
     *
     * @return string the Client Secret
     */
    public function getClientSignSecret() {
        return $this->client_sign_secret;
    }

    /**
     * Make requests to the production servers
     *
     * @param boolean $production
     */
    public function setProduction($production = false) {
        $this->production = $production;
    }

    /**
     * Set the Redirect URI.
     *
     * @param string $redirect_uri
     */
    public function setRedirectUri($redirect_uri) {
        $this->redirect_uri = $redirect_uri;
        return $this;
    }

    public function isLive() {
        return $this->production;
    }

    /**
     * Get the Redirect URI.
     *
     * @return string Redirect URI
     */
    public function getRedirectUri() {
        return $this->redirect_uri;
    }

    /**
     * Set the Cookie Support status.
     *
     * @param Boolean $cookieSupport the Cookie Support status
     */
    public function setCookieSupport($cookieSupport) {
        $this->cookieSupport = $cookieSupport;
        return $this;
    }

    /**
     * Get the Cookie Support status.
     *
     * @return Boolean the Cookie Support status
     */
    public function useCookieSupport() {
        return $this->cookieSupport;
    }

    /**
     * Set the base domain for the Cookie.
     *
     * @param String $domain the base domain
     */
    public function setBaseDomain($domain) {
        $this->baseDomain = $domain;
        return $this;
    }

    /**
     * Get the base domain for the Cookie.
     *
     * @return String the base domain
     */
    public function getBaseDomain() {
        return $this->baseDomain;
    }

    /**
     * Set the file upload support status.
     *
     * @param String $domain the base domain
     */
    public function setFileUploadSupport($fileUploadSupport) {
        $this->fileUploadSupport = $fileUploadSupport;
        return $this;
    }

    /**
     * Get the file upload support status.
     *
     * @return String the base domain
     */
    public function useFileUploadSupport() {
        return $this->fileUploadSupport;
    }

	/**
     * Sets xiti analytics array.
     *
     * @return void
     */
    public function setXitiConfiguration($config) {
        $this->xiti = array_merge((array) $this->xiti, (array) $config);
        return $this;
    }

    /**
     * Returns xiti analytics array.
     *
     * @return 	String Encoded Xiti configuration
     */
    public function getXitiConfiguration() {
        return $this->encodeSerializedUrlVariable($this->xiti);
    }
    /**
     * Get the data from a signed_request token
     *
     * @return String the base domain
     */
    public function getSignedRequest() {
        if (!$this->signedRequest) {
            if (isset($_REQUEST['signed_request'])) {
                $this->signedRequest = $this->parseSignedRequest($_REQUEST['signed_request']);
            }
        }
        return $this->signedRequest;
    }

    /**
     * Set the Session.
     *
     * @param Array $session the session
     * @param Boolean $write_cookie indicate if a cookie should be written. this
     * value is ignored if cookie support has been disabled.
     */
    public function setSession($session = null, $write_cookie = true) {
        if ($this->debug) { $start = microtime(true); }
        $session = $this->validateSessionObject($session);
        $this->sessionLoaded = true;
        $this->session = $session;
        if ($write_cookie) {
            $this->setCookieFromSession($session);
        }
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
        return $this;
    }

    /**
     * Get the session object. This will automatically look for a signed session
     * sent via the signed_request, Cookie or Query Parameters if needed.
     *
     * @return Array the session
     */
    public function getSession() {
        if ($this->debug) { $start = microtime(true); }
        if (!$this->sessionLoaded) {
            $session = null;
            $write_cookie = true;

            $parsed_url = parse_url($this->redirect_uri);
            $parsed_path = '/';
            if (!empty($parsed_url['path'])) {
                $parsed_path = ($parsed_url['path'] != '/')?rtrim($parsed_url['path'], '/'):'/';
            }

            if (!$session && stristr($_SERVER['REQUEST_URI'],$parsed_path) && isset($_REQUEST['code'])) {
                $ret = $this->getAccessToken($_REQUEST['code']);
                if (is_array($ret) && isset($ret['access_token'])) {
                    $session = $this->signSession($ret); // Signs session for security
                }
            }
            // try loading session from signed_request in $_REQUEST
            $signedRequest = $this->getSignedRequest();
            if ($signedRequest) {
                // sig is good, use the signedRequest
                $session = $this->createSessionFromSignedRequest($signedRequest);
            }
            // try loading session from $_REQUEST
            if (!$session && isset($_REQUEST['session'])) {
                $session = json_decode(get_magic_quotes_gpc() ? stripslashes($_REQUEST['session']) : $_REQUEST['session'], true);
                $session = $this->validateSessionObject($session);
            }
            // try loading session from cookie if necessary
            if (!$session && $this->useCookieSupport()) {
                $cookieName = $this->getSessionCookieName();
                if (isset($_COOKIE[$cookieName])) {
                    $session = array();
                    parse_str(trim(get_magic_quotes_gpc() ? stripslashes($_COOKIE[$cookieName]) : $_COOKIE[$cookieName], '"'), $session);
                    $session = $this->validateSessionObject($session);
                    // write only if we need to delete a invalid session cookie
                    $write_cookie = empty($session);
                }
            }
            $this->setSession($session, $write_cookie);
        }
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
        return $this->session;
    }

    /**
     * Get the User ID from the session.
     * @return String the UID if available
     */
    public function getUserId() {
        $session = $this->getSession();
        return $session ? $session['user_id'] : 0;
    }

	/**
     * Get all verified emails for the logged in user.
     * @return array
     */
    public function getVerifiedEmails() {
        $emails = array();
        try {
        	$user = $this->api('/me');
            if (isset($user['emails']) && is_array($user['emails']) && count($user['emails']) > 0) {
            	foreach ($user['emails'] as $key => $value) {
            		if (isset($value['verified']) && $value['verified'] == true) {
            			$emails[] = $value['value'];
            		}
            	}
            }
        } catch (VGS_Client_Exception $e) {
            self::errorLog('Exception thrown when getting logged in user:'. $e->getMessage());
        }

        return $emails;
    }

    public function isEmailVerified($email) {
        try {
        	$user = $this->api('/user/'.$email);
            if (isset($user['emails']) && is_array($user['emails']) && count($user['emails']) > 0) {
            	foreach ($user['emails'] as $key => $value) {
            		if (isset($value['verified']) && $value['verified'] == true && $value['value'] == $email) {
            			return true;
            		}
            	}
            }
        } catch (VGS_Client_Exception $e) {
            self::errorLog('Exception thrown when getting logged in user:'. $e->getMessage());
        }
        return false;
    }

    /**
     * Gets a OAuth access token.
     *
     * @return String the access token
     */
    public function getAccessToken($code = null) {
        if ($this->debug) { $start = microtime(true); }
        if ($code) {
            // todo get access_token via authorization_code request
            $params['client_id']     = $this->getClientID();
            $params['client_secret'] = $this->getClientSecret();
            $params['redirect_uri']  = $this->getRedirectUri();
            $params['grant_type']    = 'authorization_code';
            $params['scope']         = '';
            $params['state']         = '';
            $params['code']          = $code;
            $access_token = (array) json_decode($this->makeRequest($this->getTokenURL(), $params));
        } else {
            if ($this->accessToken) {
            	return $this->accessToken;
            } else {
                $session = $this->getSession();
                // either user session signed, or app signed
                if (isset($session['access_token'])) {
                    $access_token = $session['access_token'];
                } else {
                    // No success! Defaults to
                    $access_token = $this->getClientID();
                }
            }
        }
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
        return $access_token;
    }

    /**
     * Gets a Fresh OAuth access token based on a refresh token
     *
     * @return String the refresh token
     */
    public function refreshAccessToken($refresh_token = null) {
        $return = array();
        if ($refresh_token) {
            // todo get access_token via refresh_token request
            $params['client_id']     = $this->getClientID();
            $params['client_secret'] = $this->getClientSecret();
            $params['redirect_uri']  = $this->getRedirectUri();
            $params['grant_type']    = 'refresh_token';
            $params['scope']         = '';
            $params['state']         = '';
            $params['refresh_token'] = $refresh_token;
            $return = (array) json_decode($this->makeRequest($this->getTokenURL(), $params));
        }
        if (is_array($return) && isset($return['access_token'])) {
            $this->session = $return;
            if (isset($return['access_token'])) {
                $this->setAccessToken($return['access_token']);
            }
            if (isset($return['refresh_token'])) {
                $this->setRefreshToken($return['refresh_token']);
            }
        } else {
            // No success! Defaults to
            $this->setAccessToken($this->getClientID());
        }
        return $this->getAccessToken();
    }

    /**
     * Auth function for starting server to server communication
     * Get an OAuth access token associated with your application via the OAuth Client Credentials Flow.
     * OAuth access tokens have no active user session, but allow you to make administrative calls that
     * do not require an active user. You can obtain an access token for your application using the
     * auth() function. After receiving an access token you can use the api() function for all calls
     * that do not require an active user session.
     * @return String Oauth Token on success
     */
    public function auth($token = false) {
        if ($token) {
        	$this->setAccessToken($token);
        } else {
            $params['client_id']     = $this->getClientID();
            $params['client_secret'] = $this->getClientSecret();
            $params['redirect_uri']  = $this->getRedirectUri();
            $params['grant_type']    = 'client_credentials';
            $params['scope']         = '';
            $params['state']         = '';

            $return = (array) json_decode($this->makeRequest($this->getTokenURL(), $params));
            if (is_array($return) && isset($return['access_token'])) {
                if (isset($return['access_token'])) {
                    $this->setAccessToken($return['access_token']);
                }
                if (isset($return['refresh_token'])) {
                    $this->setRefreshToken($return['refresh_token']);
                }
            } else {
                // No success! Defaults to
                $this->setAccessToken($this->getClientID());
            }
        }
        return $this->getAccessToken();
    }

    /**
     * Sets a server to server access code
     * @param String Oauth Token on success
     */
    public function setAccessToken($token = false) {
        $this->accessToken = $token;
    }
    /**
     * Sets a server to server refresh token
     * @param String Oauth Refresh Token on success
     */
    public function setRefreshToken($token = false) {
        $this->refreshToken = $token;
    }
    /**
     * Gets the server to server refresh token that was received with the latest access token
     * @return String Oauth Refresh Token on success
     */
    public function getRefreshToken() {
        return $this->refreshToken;
    }

    /**
     * Get a Login URI for use with redirects. By default, full page redirect is
     * assumed. If you are using the generated URI with a window.open() call in
     * JavaScript, you can pass in display=popup as part of the $params.
     *
     * The parameters:
     * - redirect_uri: the URI to go to after a successful login
     * - cancel_url: the URI to go to after the user cancels
     * - display: can be "page" (default, full page) or "popup"
     *
     * @param Array $params provide custom parameters
     * @return String the URI for the login flow
     */
    public function getLoginURI($params = array()) {
        $currentUrl = $this->getCurrentURI();
        return $this->getUrl('www', 'login', array_merge(array(
                'client_id' => $this->getClientID(),
                'response_type' => 'code',
                'redirect_uri' => $currentUrl,
                'flow' => 'signup',
                'xiti' => $this->getXitiConfiguration(),
                'v' => self::VERSION), $params));
    }
	/**
     * Get a Signup URI for use with redirects. By default, full page redirect is
     * assumed. If you are using the generated URI with a window.open() call in
     * JavaScript, you can pass in display=popup as part of the $params.
     *
     * The parameters:
     * - redirect_uri: the URI to go to after a successful login
     * - cancel_url: the URI to go to after the user cancels
     * - display: can be "page" (default, full page) or "popup"
     *
     * @param Array $params provide custom parameters
     * @return String the URI for the login flow
     */
    public function getSignupURI($params = array()) {
        $currentUrl = $this->getCurrentURI();
        return $this->getUrl('www', 'signup', array_merge(array(
                'client_id' => $this->getClientID(),
                'response_type' => 'code',
                'redirect_uri' => $currentUrl,
                'flow' => 'signup',
                'xiti' => $this->getXitiConfiguration(),
                'v' => self::VERSION), $params));
    }

    /**
     * Get the URI for redirecting the user to his VG Konto account page
     * @return String the URI for the account page
     */
    public function getAccountURI($params = array()) {
        $currentUrl = $this->getCurrentURI();
        return $this->getUrl('www', 'account', array_merge(array(
                'client_id' => $this->getClientID(),
        		'response_type' => 'code',
                'redirect_uri' => $currentUrl,
                'xiti' => $this->getXitiConfiguration(),
                ), $params));
    }

    /**
     * Get the URI for redirecting the user to his VG Konto purchase history page
     * @return String the URI for the purchase history page
     */
    public function getPurchaseHistoryURI($params = array()) {
        $currentUrl = $this->getCurrentURI();
        return $this->getUrl('www', 'account/purchasehistory', array_merge(array(
                'client_id' => $this->getClientID(),
        		'response_type' => 'code',
                'redirect_uri' => $currentUrl,
                'xiti' => $this->getXitiConfiguration(),
                ), $params));
    }

    /**
     * Get the API URI
     * @return String the API URI
     */
    public function getApiURI($path, $params = array()) {
        return $this->getUrl('api', $path, array_merge(array('oauth_token' => $this->getAccessToken()),$params));
    }

    /**
     * Get a Logout URI suitable for use with redirects.
     *
     * The parameters:
     * - redirect_uri: the URI to go to after a successful logout
     *
     * @param Array $params provide custom parameters
     * @return String the URI for the logout flow
     */
    public function getLogoutURI($params = array()) {
        return $this->getUrl('www', 'logout', array_merge(array(
                'redirect_uri'=> $this->getCurrentURI(),
        		'xiti' => $this->getXitiConfiguration(),
                'oauth_token' => $this->getAccessToken()), $params));
    }

    /**
     * Get a Purchase URI suitable for use with redirects.
     *
     * The parameters:
     * - product_id: preselect a specific product (skip choose product step)
     *
     * @param Array $params provide custom parameters
     * @return string URI to product purchase
     */
    public function getPurchaseURI($params = array()) {
        $uri = array(
        		'flow'		  => 'payment',
        		'client_id' => $this->getClientID(),
        		'response_type' => 'code',
        		'redirect_uri'=> $this->getCurrentURI(),
        		'xiti' => $this->getXitiConfiguration()
        );

        $merged_uri = array_merge($uri, $params);
        return $this->getUrl('www', 'auth/start', $merged_uri);
    }

    /**
     * Get a login status URI to fetch the status from SPiD.
     *
     * The parameters:
     * - ok_session: the URI to go to if a session is found
     * - no_session: the URI to go to if the user is not connected
     * - no_user: the URI to go to if the user is not signed into SPiD
     *
     * @param Array $params provide custom parameters
     * @return String the URI for the logout flow
     */
    public function getLoginStatusUrl($params = array()) {
        return $this->getUrl('www', 'login_status', array_merge(array(
                'client_id'       => $this->getClientID(),
                'no_session'      => $this->getCurrentURI(),
                'no_user'         => $this->getCurrentURI(),
                'ok_session'      => $this->getCurrentURI(),
                'session_version' => 1), $params));
    }

    /**
     * Make an API call.
     *
     * @param Array $params the API call parameters
     * @return mixed the decoded response
     */
    public function api(/* polymorphic */) {
        $args = func_get_args();
        return call_user_func_array(array(
                $this,
                '_restserver'), $args);
    }

    /**
     * Invoke the REST API.
     *
     * @param String $path the path (required)
     * @param String $method the http method (default 'GET')
     * @param Array $params the query/post data
     * @return the decoded response object
     * @throws VGS_Client_Exception
     */
    protected function _restserver($path, $method = 'GET', $params = array(), $getParams = array()) {
        $this->container = null;
        if ($this->debug) { $start = microtime(true); }
        if (is_array($method) && empty($params)) {
            $params = $method;
            $method = 'GET';
        }
        if ($method == 'PUT') {
            $method = 'POST';
        }
        $getParams['method'] = $method; // method override as we always do a POST
        $uri = $this->getUrl('api', $path);
        $result = $this->_oauthRequest($uri, $params, $getParams);

        if (floatval($this->api_version) >= 2) {
            $container = json_decode($result, true);
            if ($container && array_key_exists('name', $container) && $container['name'] == 'SPP Container') {
                $this->container = $container;
            }
        }

        preg_match("/\.(json|jsonp|html|xml|serialize|php|csv|tgz)$/", $path, $matches);
        if ($matches) {
            switch ($matches[1]) {
                case 'json':
                    $result = json_decode($result, true);
                    break;
                default:
                    if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
                    return $result;
                    break;
            }
        } else
            $result = json_decode($result, true);

        // results are returned, errors are thrown
        if (is_array($result) && isset($result['error']) && $result['error']) {
            $e = new VGS_Client_Exception($result, $this->raw);
            switch ($e->getType()) {
                case 'ApiException':
                    break;
                // OAuth 2.0 Draft 00 style
                case 'OAuthException':
                // OAuth 2.0 Draft 10 style
                case 'invalid_token':
                    $this->setSession(null);
            }
            throw $e;
        }
        if (floatval($this->api_version) >= 2 && $result && is_array($result) && array_key_exists('name', $result) && $result['name'] == 'SPP Container') {
            if (isset($result['sig']) && isset($result['algorithm'])) {
                $result = $this->validateAndDecodeSignedRequest($result['sig'], $result['data'], $result['algorithm']);
            } else {
                $result = $result['data'];
            }
        }
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
        return $result;
    }

    public function getLastMeta() {
        if ($this->container && is_array($this->container) && isset($this->container['meta'])) {
            return $this->container['meta'];
        }
    }
    public function getLastError() {
        if ($this->container && is_array($this->container) && isset($this->container['error'])) {
            return $this->container['error'];
        }
    }
    public function getLastDebug() {
        if ($this->container && is_array($this->container) && isset($this->container['debug'])) {
            return $this->container['debug'];
        }
    }
    public function getLastContainerType() {
        return ($this->container && is_array($this->container) && isset($this->container['type'])) ? $this->container['type'] : null;
    }
    public function getLastContainerObject() {
        return ($this->container && is_array($this->container) && isset($this->container['object'])) ? $this->container['object'] : null;
    }
    public function getLastHttpCode() {
        return ($this->container && is_array($this->container) && isset($this->container['code'])) ? $this->container['code'] : null;
    }
    public function getLastRequestMeta() {
        return ($this->container && is_array($this->container) && isset($this->container['request'])) ? $this->container['request'] : null;
    }
    public function getLastLatency() {
        return $this->latency;
    }
    /**
     * Make a OAuth Request
     *
     * @param String $path the path (required)
     * @param Array $params the query/post data
     * @return the decoded response object
     * @throws VGS_Client_Exception
     */
    protected function _oauthRequest($uri, $params, $getParams = array()) {
        if ($this->debug) { $start = microtime(true); }
        if (!isset($getParams['oauth_token']) && !isset($params['oauth_token'])) {
            $params['oauth_token'] = $this->getAccessToken();
        }
        // json_encode all params values that are not strings
        foreach ((array)$params as $key => $value) {
            if (!is_string($value)) {
                $params[$key] = json_encode($value);
            }
        }
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
        return $this->makeRequest($uri, $params, null, $getParams);
    }

    /**
     * Makes an HTTP request. This method can be overriden by subclasses if
     * developers want to do fancier things or use something other than curl to
     * make the request.
     *
     * @param String $uri the URI to make the request to
     * @param Array $params the parameters to use for the POST body
     * @param CurlHandler $ch optional initialized curl handle
     * @return String the response text
     */
    protected function makeRequest($uri, $params, $ch = null, $getParams = array()) {
        $start = microtime(true);
        if (!$ch) {
            $ch = curl_init();
        }
        $opts = self::$CURL_OPTS;
        if ($this->useFileUploadSupport()) {
            $opts[CURLOPT_POSTFIELDS] = $params;
        } else {
            if (!isset($getParams['contextClientId']) && $this->getContextClientID()) {
                 $getParams['contextClientId'] = $this->getContextClientID();
            }
            if (isset($getParams['method']) && strtoupper($getParams['method']) == 'GET') {
                if ($params && is_array($params)) foreach ($params as $k => $v) $getParams[$k] = $v;
                $uri = $uri . (strpos($uri, '?') === FALSE ? '?' : '') . http_build_query($getParams, null, '&');
            } else {
                $uri = $uri . (strpos($uri, '?') === FALSE ? '?' : '') . http_build_query($getParams, null, '&');
                $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
            }
        }
        $opts[CURLOPT_URL] = $uri;
        // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        if (isset($opts[CURLOPT_HTTPHEADER])) {
            $existing_headers = $opts[CURLOPT_HTTPHEADER];
            $existing_headers[] = 'Expect:';
            $opts[CURLOPT_HTTPHEADER] = $existing_headers;
        } else {
            $opts[CURLOPT_HTTPHEADER] = array(
                    'Expect:');
        }
        curl_setopt_array($ch, $opts);
        $result = $this->raw = curl_exec($ch);
        $info   = curl_getinfo($ch);
        $this->last_result_code = $info['http_code'];

        /*
        if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
          self::errorLog('Invalid or no certificate authority found, using bundled information');
          curl_setopt($ch, CURLOPT_CAINFO,
                      dirname(__FILE__) . '/vgs_ca_chain_bundle.crt');
          $result = curl_exec($ch);
        }
    	*/
        $this->latency = microtime(true)-$start;
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = $this->latency; }

        if ($result === false) {

            $e = new VGS_Client_Exception(array(
                    'error_code' => curl_errno($ch),
                    'error' => array(
                            'message' => curl_error($ch),
                            'type' => 'CurlException'
                    )
            ));
            curl_close($ch);
            throw $e;
        } elseif (isset($info['http_code']) && $info['http_code'] >= 400) {
            $result = json_decode($result, true) ?: $result;
            if (is_array($result) && isset($result['name']) && $result['name'] == 'SPP Container') {
                $this->container = $result;
            }
            $e = new VGS_Client_Exception($result, $this->raw);
            curl_close($ch);
            throw $e;
        }

        curl_close($ch);
        return $result;
    }

    /**
     * The name of the Cookie that contains the session.
     *
     * @return String the cookie name
     */
    protected function getSessionCookieName() {
        return 'vgs_' . $this->getClientID();
    }

    /**
     * Delete Session
     */
    public function deleteSession() {
        $this->setCookieFromSession();
        unset($_COOKIE[$this->getSessionCookieName()]);
    }

    /**
     * Set a JS Cookie based on the _passed in_ session. It does not use the
     * currently stored session -- you need to explicitly pass it in.
     *
     * @param Array $session the session to use for setting the cookie
     */
    protected function setCookieFromSession($session = null) {
        if ($this->debug) { $start = microtime(true); }
        if (!$this->useCookieSupport()) {
            return;
        }
        $now = time();
        $cookieName = $this->getSessionCookieName();
        $value = 'deleted';
        $expires_in = $now - 3600*25; // Expires in all 25 timezones
        $domain = $this->getBaseDomain();
        if ($session) {
            $value = '"' . http_build_query($session, null, '&') . '"';
            if (isset($session['base_domain'])) {
                $domain = $session['base_domain'];
            }
            $expires_in = $now + $session['expires_in']; // Defaults to client server time
        }
        // prepend dot if a domain is found
        if ($domain) {
            $domain = '.' . $domain;
        }
        // if an existing cookie is not set, we dont need to delete it
        if ($value == 'deleted' && empty($_COOKIE[$cookieName])) {
            return;
        }
        if (headers_sent()) {
            self::errorLog('Could not set cookie. Headers already sent.');
             // ignore for code coverage as we will never be able to setcookie in a CLI
        // environment
        // @codeCoverageIgnoreStart
        } else {
            setcookie($cookieName, $value, $expires_in, '/', $domain);
        }
        // @codeCoverageIgnoreEnd
        if ($this->debug) { $this->timer[__FUNCTION__]['elapsed'][] = microtime(true)-$start; }
    }

    /**
     * Validates a session_version=1 style session object.
     *
     * @param Array $session the session object
     * @return Array the session object if it validates, null otherwise
     */
    protected function validateSessionObject($session) {
        // make sure some essential fields exist
        if (is_array($session) && isset($session['access_token']) && isset($session['sig'])) {
            // validate the signature
            $session_without_sig = $session;
            unset($session_without_sig['sig']);
            $expected_sig = self::generateSignature($session_without_sig, $this->getClientSecret());
            if ($session['sig'] != $expected_sig) {
                self::errorLog('Got invalid session signature in cookie.');
                $session = null;
            }
            // check expiration time
        } else {
            $session = null;
        }
        return $session;
    }

    /**
     * Returns something that looks like our JS session object from the
     * signed token's data
     *
     * TODO: Nuke this once the login flow uses OAuth2
     *
     * @param Array the output of getSignedRequest
     * @return Array Something that will work as a session
     */
    protected function createSessionFromSignedRequest($data) {
        if (!isset($data['oauth_token'])) {
            return null;
        }
        $session = array(
            'user_id'         => isset($data['user_id'])?$data['user_id']:0,
            'access_token'    => $data['oauth_token'],
            'expires_in'      => $data['expires_in'],
        	'server_time'     => $data['server_time'],
        );
        // put a real sig, so that validateSignature works
        $session['sig'] = self::generateSignature($session, $this->getClientSecret());
        return $session;
    }

    /**
     * Signs the session data
     *
     * @param 	Array Unsigned session
     * @return 	Array Signed session
     */
    protected function signSession($data) {
        if (!isset($data['access_token'])) {
            return null;
        }
        $session = array(
            'user_id'         => isset($data['user_id'])?$data['user_id']:0,
            'access_token'    => $data['access_token'],
            'expires_in'      => $data['expires_in'],
            'server_time'     => $data['server_time'],
            'refresh_token'   => $data['refresh_token'],
        );
        // put a real sig, so that validateSignature works
        $session['sig'] = self::generateSignature($session, $this->getClientSecret());
        return $session;
    }

    /**
     * Parses a signed_request and validates the signature.
     *
     * @param String A signed token
     * @return Array the payload inside it or null if the sig is wrong
     */
    public function parseSignedRequest($signed_request) {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);
        $data = json_decode(self::base64UrlDecode($payload), true);
        $algorithm = strtoupper($data['algorithm']);
        return $this->validateAndDecodeSignedRequest($encoded_sig, $payload, $algorithm);
    }


    private function recursiveHash($data) {
        if (!is_array($data)) {
            return $data;
        }
        $ret = "";
        uksort($data, 'strnatcmp');
        foreach ($data as $v) {
            $ret .= $this->recursiveHash($v);
        }
        return $ret;
    }

    public function createHash($data) {
        $string = $this->recursiveHash($data);
        $secret = $this->getClientSignSecret();
        return self::base64UrlEncode(hash_hmac("sha256", $string, $secret, true));
    }

    public function validateAndDecodeSignedRequest($encoded_signature, $payload, $algorithm = 'HMAC-SHA256') {
        $sig = self::base64UrlDecode($encoded_signature);
        $data = json_decode(self::base64UrlDecode($payload), true);
        $string = $this->recursiveHash($data);

        switch ($algorithm) {
            case 'HMAC-SHA256' :
                $expected_sig = hash_hmac('sha256', $string, $this->getClientSignSecret(), true);

                // check sig
                if ($sig !== $expected_sig) {
                    self::errorLog('Bad Signed JSON signature!');
                    return null;
                }
                return $data;
                break;
            default:
                self::errorLog('Unknown algorithm. Expected HMAC-SHA256');
                return null;
                break;

        }
    }

    /**
     * Build the URI for given domain alias, path and parameters.
     *
     * @param $name String the name of the domain
     * @param $path String optional path (without a leading slash)
     * @param $params Array optional query parameters
     * @return String the URI for the given parameters
     */
    protected function getUrl($name, $path = '', $params = array()) {
        $uri = self::getBaseURL($name);
        if ($path) {
            if ($path[0] === '/') {
                $path = substr($path, 1);
            }
            $uri .= $path;
        }
        if ($params) {
            $uri .= '?' . http_build_query($params, null, $this->argSeparator);
        }
        return $uri;
    }

    /**
     * Returns the Current URI, stripping it of known parameters that should
     * not persist.
     *
     * @return String the current URI
     */
    public function getCurrentURI($extra_params = array(), $drop_params = array()) {
        $drop_params = array_merge(self::$DROP_QUERY_PARAMS, $drop_params);
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
        $currentUrl = $protocol . (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'') . (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'');
        $parts = parse_url($currentUrl);
        // drop known params
        $query = '';
        if (!empty($parts['query'])) {
            $params = array();
            parse_str($parts['query'], $params);
            $params = array_merge($params, $extra_params);
            //print_r($params);
            foreach ($drop_params as $key) {
                unset($params[$key]);
            }
            if (!empty($params)) {
                $query = '?' . http_build_query($params, null, $this->argSeparator);
            }
        } elseif (!empty($extra_params)) {
            $query = '?' . http_build_query($extra_params, null, $this->argSeparator);
        }
        // use port if non default
        $port = isset($parts['port']) && (($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443)) ? ':' . $parts['port'] : '';
        // rebuild
        return $protocol . (isset($parts['host'])?$parts['host']:'') . $port . (isset($parts['path'])?$parts['path']:'') . $query;
    }

    /**
     * Returns the result code of the last request.
     *
     * @return int
     */
    public function getLastResultCode() {
        return $this->last_result_code;
    }
    /**
     * Generate a signature for the given params and secret.
     *
     * @param Array $params the parameters to sign
     * @param String $secret the secret to sign with
     * @return String the generated signature
     */
    protected static function generateSignature($params, $secret) {
        // work with sorted data
        ksort($params);
        // generate the base string
        $base_string = '';
        foreach ($params as $key => $value) {
            $base_string .= $key . '=' . $value;
        }
        $base_string .= $secret;
        return md5($base_string);
    }

    /**
     * Prints to the error log if you aren't in command line mode.
     *
     * @param String log message
     */
    protected static function errorLog($msg) {
        // disable error log if we are running in a CLI environment
        // @codeCoverageIgnoreStart
        if (php_sapi_name() != 'cli') {
            error_log($msg);
        }
         // uncomment this if you want to see the errors on the page
         self::$errors[] = $msg;
    // print 'error_log: '.$msg."\n";
    // @codeCoverageIgnoreEnd
    }

    /**
     * Base64 encoding that doesn't need to be urlencode()ed.
     * Exactly the same as base64_encode except it uses
     * - instead of +
     * _ instead of /
     *
     * @param String base64UrlEncodeded string
     */
    protected static function base64UrlDecode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Base64 encoding that doesn't need to be urlencode()ed.
     * Exactly the same as base64_encode except it uses
     * - instead of +
     * _ instead of /
     *
     * @param string $input string
     * @return string base64 encoded string
     */
    protected static function base64UrlEncode($input) {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
