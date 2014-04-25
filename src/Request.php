<?php
/**
 * @package Phpf
 */

namespace Phpf;

class Request {
	
	/**
	 * HTTP request method
	 * @var string
	 */
	protected $method;
	
	/**
	 * URI path, stripped of query.
	 * @var string
	 */
	protected $uri;
	
	/**
	 * Query string
	 * @var string
	 */
	protected $query;
	
	/**
	 * HTTP headers
	 * @var array
	 */
	protected $headers;
	
	/**
	 * Cookies
	 * @var array
	 */
	protected $cookies;
	
	/**
	 * Files uploaded
	 * @var array
	 */
	protected $files;
	
	/**
	 * Session
	 * @var Phpf\Session
	 */
	protected $session;
	
	/**
	 * Query parameters.
	 * @var array
	 */
	protected $query_params;
	
	/**
	 * Body parameters.
	 * @var array
	 */
	protected $body_params;
	
	/**
	 * Path parameters.
	 * @var array
	 */
	protected $path_params;
	
	/**
	 * All parameters (query, path, and body) combined.
	 * @var array
	 */
	protected $params;
	
	/**
	 * Content type requested.
	 * @var string
	 */
	protected $content_type;
	
	/**
	 * Whether to globalize parameters to $_REQUEST.
	 * 
	 * Default true.
	 * 
	 * @var boolean
	 */
	protected $globalize = true;
	
	/**
	 * File extensions to strip from URLs, delimeted with "|".
	 * 
	 * If matched, sets $content_type property, and the Response
	 * will use it instead of negotiating the 'Accept' header.
	 * 
	 * Defaults are html, jsonp, json, and xml.
	 * 
	 * @var string
	 */
	protected $strip_extensions = 'html|jsonp|json|xml';
	
	/**
	 * Whether to allow method override via "X-Http-Method-Override" 
	 * header and "_method" query parameter.
	 * 
	 * Default for header is true.
	 * Default for parameter is false.
	 * 
	 * @var array
	 */
	protected $allow_method_override = array(
		'header' => true,
		'parameter' => false,
	);
	
	/**
	 * Indexed array of content types that will be honored.
	 * 
	 * Clients can request the corresponding MIME types 
	 * through the Accept header and/or file ext.
	 * 
	 * Defaults are html, json, jsonp, and xml.
	 * 
	 * @see \Phpf\Response
	 * 
	 * @var array
	 */
	protected $allow_content_types = array(
		'html' => true, 
		'json' => true, 
		'jsonp' => true, 
		'xml' => true,
	);
	
	/**
	 * Build the request using the (super) global variables.
	 * 
	 * @uses http_get_request_headers()
	 * @uses http_get_request_body()
	 * 
	 * @return \Phpf\Request
	 */
	public static function createFromGlobals() {
		
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] :'GET';
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		
		// Set request path
		if (isset($_SERVER['PATH_INFO'])) {
			$uri = urldecode($_SERVER['PATH_INFO']);
		} else {
			$uri = urldecode($_SERVER['REQUEST_URI']);
			// Remove query string from path
			if (false !== ($qpos = strpos($uri, '?'))) {
				$uri = substr($uri, 0, $qpos);	
			}
		}
		
		$headers = http_get_request_headers();
		
		// Set request body data as per RFC 3875 4.2, 4.3
		if ('HEAD' === $method || ('POST' === $method && empty($headers['content-length']))) {
			// HEAD requests have no body - ha!
			// POST requests must have content-length
			$data = array();
		} else if ('POST' === $method && isset($headers['content-type']) && 'multipart/form-data' === $headers['content-type']) {
			// Use php://input except for POST with enctype="multipart/form-data"
			// @see {@link http://us3.php.net/manual/en/wrappers.php.php}
			$data = $_POST;
		} else {
			parse_str(http_get_request_body(), $data);
		}
		
		return new static($method, $uri, $query, $headers, $data, $_COOKIE, $_FILES);
	}
	
	/**
	 * Build request from $server array
	 * 
	 * @param string $method HTTP method.
	 * @param string $uri Clean request path, with no beginning/ending slashes or query string.
	 * @param string $query Query string.
	 * @param array $headers Associative array of HTTP headers.
	 * @param array $body [Optional] Array of data (body).
	 * @param array $cookies [Optional] Associative array of cookies.
	 * @param array $files [Optional] Array of file uploads.
	 */
	public function __construct($method, $uri, $query, array $headers, array $body = null, array $cookies = null, array $files = null){
		
		// Clean and set request path
		$uri = trim(filter_var($uri, FILTER_SANITIZE_STRING), '/');
		
		// match file extensions to set content-type
		if (preg_match("/[\.|\/]($this->strip_extensions)/", $uri, $matches)) {
			$this->content_type = $matches[1];
			// remove extension and separator
			$uri = str_replace(substr($matches[0], 0, 1).$matches[1], '', $uri);
		}
		
		$this->uri = $uri;
		
		// set headers, cookies, files
		$this->headers = $headers;
		$this->cookies = $cookies;
		$this->files = $files;
		
		$this->query_params = array();
		
		// Set query string and params
		if (! empty($query)) {
			$this->query = html_entity_decode(urldecode($query));
			parse_str($this->query, $this->query_params);
		}
		
		// Set post data
		if (isset($body)) {
			$this->body_params = $body;
			$this->params = array_merge($this->query_params, $this->body_params);
		} else {
			$this->params = $this->query_params;
		}
		
		// Override request method if POST
		if ('POST' === $method) {
			// via header
			if ($this->isMethodOverrideAllowed('header') && isset($this->headers['x-http-method-override'])) {
				$method = $this->headers['x-http-method-override']; 
			}
			//via parameter
			if ($this->isMethodOverrideAllowed('parameter') && isset($this->query_params['_method'])) {
				$method = strtoupper($this->query_params['_method']);
			}
		}
		
		$this->method = $method;
		
		$this->globalize();
		
		return $this;
	}
	
	/**
	 * Returns a property or parameter value.
	 * 
	 * @param string $var Property or parameter name to retrieve.
	 * @return mixed Value of property or parameter, if set, otherwise null.
	 */
	public function __get($var) {
			
		if (isset($this->$var)) {
			return $this->$var;
		}
		
		if (isset($this->params[$var])) {
			return $this->params[$var];
		}
		
		return null;
	}
	
	/**
	 * Returns the request HTTP method.
	 * 
	 * @return string HTTP method.
	 */
	public function getMethod() {
		return $this->method;
	}
	
	/**
	 * Returns the request URI.
	 * 
	 * @return string URI
	 */
	public function getUri() {
		return $this->uri;	
	}
	
	/**
	 * Returns the request query string if set.
	 * 
	 * @return string Query
	 */
	public function getQuery() {
		return $this->query;	
	}
	
	/**
	 * Returns array of parsed headers.
	 * 
	 * @return array HTTP request headers.
	 */
	public function getHeaders() {
		return $this->headers;	
	}
	
	/**
	 * Returns a single HTTP header if set.
	 * 
	 * @param string $name Header name (lowercase).
	 * @return string Header value if set, otherwise null.
	 */
	public function getHeader($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : null;	
	}
	
	/**
	 * Returns the request cookies.
	 * 
	 * @return array Associative array of cookies sent with request.
	 */
	public function getCookies() {
		return $this->cookies;
	}
	
	/**
	 * Returns the request files.
	 * 
	 * @return array Files from $_FILES superglobal.
	 */
	public function getFiles() {
		return $this->files;
	}
	
	/**
	 * Sets matched route path parameters.
	 * 
	 * @param array $params Associative array of matched path parameters.
	 * @return $this
	 */
	public function setPathParams(array $params){
		$this->path_params = $params;
		$this->params = array_merge($this->params, $this->path_params);
		$this->globalize();
		return $this;
	}
	
	/**
	 * Returns all parameter values.
	 * 
	 * @return array Query, path, and body parameters.
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Returns a parameter value
	 * 
	 * @param string $name Parameter name
	 * @return mixed Parameter value.
	 */
	public function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * &Alias of getParam()
	 */
	public function param($name) {
		return $this->getParam($name);	
	}
	
	/**
	 * Returns true if a parameter is set.
	 * 
	 * @param string $name Parameter name
	 * @return boolean True if set, otherwise false.
	 */
	public function paramExists($name) {
		return isset($this->params[$name]);
	}
	
	/**
	 * Sets the session object.
	 * 
	 * @param Phpf\Session $session
	 * @return $this
	 */
	public function setSession(Session $session) {
		$this->session = $session;
		return $this;
	}
	
	/**
	 * Returns session object.
	 * 
	 * @param return SessionInterface
	 */
	public function getSession() {
		return isset($this->session) ? $this->session : null;
	}
	
	/**
	 * Returns true if session is set, otherwise false.
	 * 
	 * @return boolean
	 */
	public function sessionExists() {
		return isset($this->session);
	}
	
	/**
	 * Returns content type, if set.
	 * 
	 * @return string Content-type if set, otherwise null.
	 */
	public function getContentType() {
		return isset($this->content_type) ? $this->content_type : null;
	}
	
	/**
	 * Sets the requested content type if valid, for use by Response.
	 * 
	 * @param string $type Content type requested.
	 * @return boolean True if valid, otherwise false.
	 */
	public function setContentType($type) {
		
		if (isset($this->allow_content_types[$type])) {
			$this->content_type = $type;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns true if given content-type is valid for response.
	 * 
	 * @param string $type Content type.
	 * @return boolean True if allowed, otherwise false.
	 */
	public function isContentTypeAllowed($type) {
		return isset($this->allow_content_types[$type]);
	}
	
	/**
	 * Returns array of allowed content types.
	 * 
	 * @return array Indexed array of allowed content types.
	 */
	public function getAllowedContentTypes() {
		return array_keys($this->allow_content_types);
	}
	
	/**
	 * Sets whether to globalize the request params to $_REQUEST.
	 * 
	 * @param boolean $value True or false
	 * @return $this
	 */
	public function setGlobalize($value) {
		$this->globalize = (bool)$value;
		return $this;
	}
	
	/**
	 * Adds an extension to strip from URIs
	 * 
	 * @param string $extension Extension to strip from URIs.
	 * @return $this
	 */
	public function stripExtension($extension) {
		$extension = ltrim(strtolower($extension), '.');
		if (false === strpos($this->strip_extensions, $extension)) {
			$this->strip_extensions .= '|'.$extension;
		}
		return $this;
	}
	
	/**
	 * Whether request is a XML HTTP request.
	 * 
	 * @return boolean True if XMLHttpRequest, otherwise false.
	 */
	public function isXhr() {
		return isset($this->headers['x-requested-with']) && 'XMLHttpRequest' === $this->headers['x-requested-with'];
	}
	
	/**
	 * Boolean method/xhr checker.
	 * 
	 * @param string $thing HTTP method name, or 'xhr' or 'ajax'.
	 * @return boolean True if request is given thing, or null if unknown thing.
	 */
	public function is($thing) {
		switch(strtoupper($thing)) {
			case 'GET' :
				return 'GET' === $this->method;
			case 'POST' :
				return 'POST' === $this->method;
			case 'PUT' :
				return 'PUT' === $this->method;
			case 'HEAD' :
				return 'HEAD' === $this->method;
			case 'DELETE' :
				return 'DELETE' === $this->method;
			case 'OPTIONS' :
				return 'OPTIONS' === $this->method;
			case 'PATCH' :
				return 'PATCH' === $this->method;
			case 'XHR' :
			case 'AJAX' :
				return $this->isXhr();
			default :
				return null;
		}
	}
	
	/** Am I a GET request? */
	public function isGet() {
		return $this->is('GET');
	}
	
	/** Am I a POST request? */
	public function isPost() {
		return $this->is('POST');
	}
	
	/**  Am I a PUT request? */
	public function isPut() {
		return $this->is('PUT');
	}
	
	/** Am I a HEAD request? */
	public function isHead() {
		return $this->is('HEAD');
	}
	
	/**
	 * Allow HTTP method override via header or parameter.
	 * 
	 * @param string $where One of 'header' or 'parameter'.
	 * @return $this
	 */
	public function allowMethodOverride($where) {
		$this->allow_method_override[$where] = true;
		return $this;
	}
	
	/**
	 * Disallow HTTP method override via header or parameter.
	 * 
	 * @param string $where One of 'header' or 'parameter'.
	 * @return $this
	 */
	public function disallowMethodOverride($where) {
		$this->allow_method_override[$where] = false;
		return $this;
	}
	
	/**
	 * Whether to allow HTTP method override via header or parameter.
	 * 
	 * @param string $where One of 'header' or 'parameter'.
	 * @return boolean True if method override is permitted for given location, otherwise false.
	 */
	public function isMethodOverrideAllowed($where) {
		return $this->allow_method_override[$where];
	}
	
	/**
	 * Globalizes the request parameters, if set to do so.
	 * 
	 * @return $this
	 */
	protected function globalize() {
		if ($this->globalize) {
			$_REQUEST = $this->params;
		}
		return $this;
	}
	
}
