<?php


namespace core\base;


use core\exceptions\ErrorException;
use core\web\Cookie;
use core\web\CookieCollection;
use core\web\HeaderCollection;

/**
 * @property HeaderCollection headers
 * @property array request
 * @property bool isPost
 * @property bool isAjax
 * @property bool isGet
 * @property array post
 * @property array get
 * @property array files
 * @property bool enableCookieValidation
 * @property string cookieValidationKey
 * @property string userLanguage
 * @property string url
 * @property string baseUrl
 * @property string scriptFile
 */
class Request extends BaseObject
{
    private $_scriptFile;

    private $_scriptUrl;

    private $_baseUrl;

    private $_isPost = false;

    private $_isGet = false;

    private $_get;

    private $_post;

    private $_files;

    private $_request;

    private $_url;

    private $_userLanguage;

    /**
     * @var CookieCollection
     */
    private $_cookies;

    private $_enableCookieValidation;

    private $_cookieValidationKey;

    /**
     * @var HeaderCollection
     */
    private $_headers;

    private $_hostInfo;

    private $_hostName;

    public $trustedHosts = [];

    public $secureHeaders = [
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',
        'Front-End-Https',
        'X-Rewrite-Url',
    ];

    public $ipHeaders = [
        'X-Forwarded-For',
    ];

    public $secureProtocolHeaders = [
        'X-Forwarded-Proto' => ['https'],
        'Front-End-Https' => ['on'],
    ];

    /**
     * @inheritdoc
     */
    public function init(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $this->_isPost = true;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'GET'){
            $this->_isGet = true;
        }
        $this->_userLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2);
        $this->_get = $_GET;
        $this->_post = $_POST;
        $this->_files = $_FILES;
        $this->parseModels();
        $this->_request = array_merge($this->_get, $this->_post, $this->_files);
    }
    /**
     * Returns current script file
     * @return string - current script filename
     * @throws ErrorException
     */
    public function getScriptFile()
    {
        if (isset($this->_scriptFile)) {
            return $this->_scriptFile;
        } elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        } else {
            throw new ErrorException('Unable to determine the entry script file path.');
        }
    }
    /**
     * Returns current script URL
     * @return mixed|string
     * @throws ErrorException
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && ($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            } else {
                throw new ErrorException('Unable to determine the entry script URL.');
            }
        }

        return $this->_scriptUrl;
    }
    /**
     * Returns base URL
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }

        return $this->_baseUrl;
    }
    /**
     * Indicates whether request is POST
     * @return bool
     */
    public function getIsPost(){
        return $this->_isPost;
    }

    /**
     * Indicates whether request is GET
     * @return bool
     */
    public function getIsGet(){
        return $this->_isGet;
    }

    /**
     * Returns post data
     * @return mixed
     */
    public function getPost(){
        return $this->_post;
    }

    /**
     * Returns all post data or specific post by name
     * @param string|null $name
     * @return null|string|array
     */
    public function post($name = null){
        if ($name == null){
            return $this->_post;
        }
        if (isset($this->_post[$name])){
            return $this->_post[$name];
        }
        return null;
    }
    /**
     * Returns get data
     * @return mixed
     */
    public function getGet(){
        return $this->_get;
    }
    /**
     * Returns all get data or specific get by name
     * @param string|null $name
     * @return null|string|array
     */
    public function get($name = null){
        if ($name == null){
            return $this->_post;
        }
        if (isset($this->_get[$name])){
            return $this->_get[$name];
        }
        return null;
    }
    /**
     * Returns files data
     * @return mixed
     */
    public function getFiles(){
        return $this->_files;
    }
    /**
     * Returns all files data or specific file by name
     * @param string|null $name
     * @return null|array
     */
    public function files($name = null){
        if ($name == null){
            return $this->_files;
        }
        if (isset($this->_files[$name])){
            return $this->_files[$name];
        }
        return null;
    }
    /**
     * Returns request data(POST + GET + FILES)
     * @return mixed
     */
    public function getRequest(){
        return $this->_request;
    }
    /**
     * Returns all request data or specific request by name
     * @param string|null $name
     * @return mixed
     */
    public function request($name = null){
        if ($name == null){
            return $this->_request;
        }
        if (isset($this->_request[$name])){
            return $this->_request[$name];
        }
        return null;
    }
    /**
     * Indicates whether request is AJAX
     * @return bool
     */
    public function getIsAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    /**
     * Indicates whether request is PJAX
     * @return bool
     */
    public function getIsPjax()
    {
        return $this->getIsAjax() && $this->headers->has('X-Pjax');
    }
    /**
     * Returns headers of request
     * @return HeaderCollection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection();
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                foreach ($headers as $name => $value) {
                    $this->_headers->add($name, $value);
                }
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
                foreach ($headers as $name => $value) {
                    $this->_headers->add($name, $value);
                }
            } else {
                foreach ($_SERVER as $name => $value) {
                    if (strncmp($name, 'HTTP_', 5) === 0) {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $this->_headers->add($name, $value);
                    }
                }
            }
            $this->filterHeaders($this->_headers);
        }
        return $this->_headers;
    }
    /**
     * Filter headers by request configuration
     * @param HeaderCollection $headerCollection
     */
    protected function filterHeaders(HeaderCollection $headerCollection)
    {
        // do not trust any of the [[secureHeaders]] by default
        $trustedHeaders = [];
        // check if the client is a trusted host
        if (!empty($this->trustedHosts)) {
            $host = $this->getRemoteHost();
            $ip = $this->getRemoteIP();
            foreach ($this->trustedHosts as $hostRegex => $headers) {
                if (!is_array($headers)) {
                    $hostRegex = $headers;
                    $headers = $this->secureHeaders;
                }
                if (preg_match($hostRegex, $host) || preg_match($hostRegex, $ip)) {
                    $trustedHeaders = $headers;
                    break;
                }
            }
        }
        // filter all secure headers unless they are trusted
        foreach ($this->secureHeaders as $secureHeader) {
            if (!in_array($secureHeader, $trustedHeaders)) {
                $headerCollection->remove($secureHeader);
            }
        }
    }
    /**
     * Returns Host information
     * @return null|string
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            $secure = $this->getIsSecureConnection();
            $http = $secure ? 'https' : 'http';
            if ($this->headers->has('Host')) {
                $this->_hostInfo = $http . '://' . $this->headers->get('Host');
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
                $port = $secure ? $this->getSecurePort() : $this->getPort();
                if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                    $this->_hostInfo .= ':' . $port;
                }
            }
        }
        return $this->_hostInfo;
    }
    /**
     * Set host information
     * @param $value
     */
    public function setHostInfo($value)
    {
        $this->_hostName = null;
        $this->_hostInfo = $value === null ? null : rtrim($value, '/');
    }
    /**
     * Returns host name
     * @return mixed
     */
    public function getHostName()
    {
        if ($this->_hostName === null) {
            $this->_hostName = parse_url($this->getHostInfo(), PHP_URL_HOST);
        }
        return $this->_hostName;
    }
    /**
     * Indicates whether connection is secure
     * @return bool
     */
    public function getIsSecureConnection()
    {
        if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)) {
            return true;
        }
        foreach ($this->secureProtocolHeaders as $header => $values) {
            if (($headerValue = $this->headers->get($header, null)) !== null) {
                foreach ($values as $value) {
                    if (strcasecmp($headerValue, $value) === 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    /**
     * Returns the server name.
     * @return string server name, null if not available
     */
    public function getServerName()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }
    /**
     * Returns the server port number.
     * @return int|null server port number, null if not available
     */
    public function getServerPort()
    {
        return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
    }
    /**
     * Returns the URL referrer.
     * @return string|null URL referrer, null if not available
     */
    public function getReferrer()
    {
        return $this->headers->get('Referer');
    }

    public function getOrigin()
    {
        return $this->getHeaders()->get('origin');
    }

    public function getUrl()
    {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }
        return $this->_url;
    }

    /**
     * Returns the user agent.
     * @return string|null user agent, null if not available
     */
    public function getUserAgent()
    {
        return $this->headers->get('User-Agent');
    }
    /**
     * Returns the user IP address.
     * The IP is determined using headers and / or `$_SERVER` variables.
     * @return string|null user IP address, null if not available
     */
    public function getUserIP()
    {
        foreach ($this->ipHeaders as $ipHeader) {
            if ($this->headers->has($ipHeader)) {
                return trim(explode(',', $this->headers->get($ipHeader))[0]);
            }
        }
        return $this->getRemoteIP();
    }
    /**
     * Returns the IP on the other end of this connection.
     * This is always the next hop, any headers are ignored.
     * @return string|null remote IP address, `null` if not available.
     */
    public function getRemoteIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }
    /**
     * Returns the host name of the other end of this connection.
     * This is always the next hop, any headers are ignored.
     * @return string|null remote host name, `null` if not available
     * @see getRemoteIP()
     */
    public function getRemoteHost()
    {
        return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
    }
    private $_port;
    /**
     * Returns the port to use for insecure requests.
     * Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     * @return int port number for insecure requests.
     * @see setPort()
     */
    public function getPort()
    {
        if ($this->_port === null) {
            $this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
        }
        return $this->_port;
    }
    /**
     * Sets the port to use for insecure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param int $value port number.
     */
    public function setPort($value)
    {
        if ($value != $this->_port) {
            $this->_port = (int) $value;
            $this->_hostInfo = null;
        }
    }
    private $_securePort;
    /**
     * Returns the port to use for secure requests.
     * Defaults to 443, or the port specified by the server if the current
     * request is secure.
     * @return int port number for secure requests.
     * @see setSecurePort()
     */
    public function getSecurePort()
    {
        if ($this->_securePort === null) {
            $this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
        }
        return $this->_securePort;
    }
    /**
     * Sets the port to use for secure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param int $value port number.
     */
    public function setSecurePort($value)
    {
        if ($value != $this->_securePort) {
            $this->_securePort = (int) $value;
            $this->_hostInfo = null;
        }
    }
    /**
     * @return string|null the username sent via HTTP authentication, `null` if the username is not given
     * @see getAuthCredentials() to get both username and password in one call
     */
    public function getAuthUser()
    {
        return $this->getAuthCredentials()[0];
    }
    /**
     * @return string|null the password sent via HTTP authentication, `null` if the password is not given
     * @see getAuthCredentials() to get both username and password in one call
     */
    public function getAuthPassword()
    {
        return $this->getAuthCredentials()[1];
    }
    /**
     * @return array that contains exactly two elements:
     * - 0: the username sent via HTTP authentication, `null` if the username is not given
     * - 1: the password sent via HTTP authentication, `null` if the password is not given
     * @see getAuthUser() to get only username
     * @see getAuthPassword() to get only password
     */
    public function getAuthCredentials()
    {
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
        if ($username !== null || $password !== null) {
            return [$username, $password];
        }
        /*
         * Apache with php-cgi does not pass HTTP Basic authentication to PHP by default.
         * To make it work, add the following line to to your .htaccess file:
         *
         * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
         */
        $auth_token = $this->getHeaders()->get('HTTP_AUTHORIZATION') ?: $this->getHeaders()->get('REDIRECT_HTTP_AUTHORIZATION');
        if ($auth_token !== null && strpos(strtolower($auth_token), 'basic') === 0) {
            $parts = array_map(function ($value) {
                return strlen($value) === 0 ? null : $value;
            }, explode(':', base64_decode(mb_substr($auth_token, 6)), 2));
            if (count($parts) < 2) {
                return [$parts[0], null];
            }
            return $parts;
        }
        return [null, null];
    }
    /**
     * @return CookieCollection
     */
    public function getCookies()
    {
        if ($this->_cookies === null) {
            $this->_cookies = new CookieCollection($this->loadCookies(), [
                'readOnly' => true,
            ]);
        }
        return $this->_cookies;
    }
    /**
     * @return mixed
     * @throws \Exception
     */
    public function getCookieValidationKey(){
        if ($this->_cookieValidationKey == null){
            if (isset(App::$instance->config['request']['cookieValidationKey'])){
                $this->_cookieValidationKey = App::$instance->config['request']['cookieValidationKey'];
            } else if ($this->enableCookieValidation){
                throw new \Exception(get_class($this) . '::cookieValidationKey must be configured with a secret key.');
            }
        }
        return $this->_cookieValidationKey;
    }
    /**
     * @return bool
     */
    public function getEnableCookieValidation(){

        if ($this->_enableCookieValidation === null){
            $this->_enableCookieValidation = (isset(App::$instance->config['request']['enableCookieValidation'])
                && gettype(App::$instance->config['request']['enableCookieValidation']) == 'boolean')
                ? App::$instance->config['request']['enableCookieValidation']
                : false;
        }
        return $this->_enableCookieValidation;
    }
    /**
     * @return mixed
     */
    public function getUserLanguage(){
        return $this->_userLanguage;
    }
    /**
     * @return array
     * @throws \Exception
     */
    protected function loadCookies()
    {
        $cookies = [];
        if ($this->enableCookieValidation) {
            if ($this->cookieValidationKey == '') {
                throw new \Exception(get_class($this) . '::cookieValidationKey must be configured with a secret key.');
            }
            foreach ($_COOKIE as $name => $value) {
                if (!is_string($value)) {
                    continue;
                }
                $data = App::$instance->getSecurity()->validateData($value, $this->cookieValidationKey);
                if ($data === false) {
                    continue;
                }
                $data = @unserialize($data);
                if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
                    $cookies[$name] = new Cookie([
                        'name' => $name,
                        'value' => $data[1],
                        'expire' => null,
                    ]);
                }
            }
        } else {
            foreach ($_COOKIE as $name => $value) {
                $cookies[$name] = new Cookie([
                    'name' => $name,
                    'value' => $value,
                    'expire' => null,
                ]);
            }
        }
        return $cookies;
    }
    /**
     * @return array|mixed|string
     * @throws \Exception
     */
    protected function resolveRequestUri()
    {
        if ($this->headers->has('X-Rewrite-Url')) { // IIS
            $requestUri = $this->headers->get('X-Rewrite-Url');
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new \Exception('Unable to determine the request URI.');
        }
        return $requestUri;
    }
    /**
     *
     */
    private function parseModels(){
        $this->parseVarsInArray('get');
        $this->parseVarsInArray('post');
        $this->parseVarsInArray('files');
    }
    /**
     * @param $arrayName
     */
    private function parseVarsInArray($arrayName){
        foreach ($this->$arrayName as $key => $value){
            if (substr($key,0,3) == 'crl'){
                $key = substr($key, 4);
                $parts = explode('_', $key);
                if (count($parts) > 1){
                    $this->{'_'.$arrayName}[$parts[0]][substr($key, strlen($parts[0]) + 1)] = $value;
                }
            }
        }
    }
}