<?php
/**
 * @package Phpf.Routes
 */

namespace Phpf;

use Phpf\Reflection\Callback;
use Phpf\Route\Route;

class Router implements Common\iEventable
{
	
	/**
	 * Event container object.
	 * @var \Phpf\EventContainer
	 */
	protected $events;
	
	/**
	 * Request object.
	 * @var \Phpf\Request
	 */
	protected $request;

	/**
	 * Response object.
	 * @var \Phpf\Response
	 */
	protected $response;

	/**
	 * Matched route object.
	 * @var \Phpf\Route\Route
	 */
	protected $route;
	
	/**
	 * Route objects.
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Route query variables.
	 * @var array
	 */
	protected $vars = array(
		'segment'	=> '([^_/][^/]+)', 
		'words'		=> '(\w\-+)', 
		'int'		=> '(\d+)', 
		'str'		=> '(.+?)', 
		'any'		=> '(.?.+?)', 
	);
	
	/**
	 * Constructor - takes Phpf\EventContainer as only argument by reference.
	 * 
	 * @param \Phpf\EventContainer &$events Events container.
	 */
	public function __construct(EventContainer &$events) {
		
		$this->events = &$events;

		// Default error event
		$this->on('http.404', function($event, $exception, $route, $request, $response) {
			if (! $event->isDefaultPrevented()) {
				$response->setBody($exception->getMessage());
				$response->send();
			}
		});
	}
	
	/**
	 * Matches a request URI and calls its method.
	 * 
	 * @param \Phpf\Request &$request Request object.
	 * @param \Phpf\Response &$response Response object.
	 * @return void
	 */
	public function dispatch(Request &$request, Response &$response) {
		
		$this->timer('start');
		
		$this->request = &$request;
		$this->response = &$response;
		
		if ($this->match()) {
			
			$this->timer('stop');
			
			if (isset($this->caught_by_endpoint)) {
				return $this->caught_by_endpoint;
			}
			
			return $this->route;
			
		} else {
			// Send 404 with UnknownRoute exception
			$this->error(404, new \Phpf\Route\Exception\UnknownRoute('Unknown route'), null);
		}
	}

	/**
	 * Gets Request object.
	 * 
	 * @return \Phpf\Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Gets Response object.
	 * 
	 * @return \Phpf\Response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Gets matched Route object
	 * 
	 * @return \Phpf\Route\Route
	 */
	public function getRoute() {
		return $this->route;
	}

	/**
	 * Returns array of route objects, their URI as the key.
	 * Can return a specified priority group, otherwise returns all.
	 * 
	 * @param int|null $priority Priority of routes to return, or null to return all (default).
	 * @return array Route objects.
	 */
	public function getRoutes($priority = null) {
		if ($priority !== null)
			return isset($this->routes[$priority]) ? $this->routes[$priority] : array();
		return $this->routes;
	}

	/**
	 * Returns regex for a query var name.
	 * 
	 * @param string $key Query var name.
	 * @return string Regex for var if set, otherwise empty string.
	 */
	public function getRegex($key) {
		return isset($this->vars[$key]) ? $this->vars[$key] : '';
	}

	/**
	 * Returns array of query vars and regexes
	 * 
	 * @return array Associative array of query var names and regexes.
	 */
	public function getVars() {
		return $this->vars;
	}

	/**
	 * Adds query var and regex
	 *
	 * @param string $name The query var name.
	 * @param string $regex The var's regex.
	 * @return $this
	 */
	public function addVar($name, $regex) {
		$this->vars[$name] = $regex;
		return $this;
	}

	/**
	 * Adds array of query vars and regexes
	 * 
	 * @param array $vars Associative array of var name/regex pairs.
	 * @return $this
	 */
	public function addVars(array $vars) {
		foreach ( $vars as $name => $regex ) {
			$this->addVar($name, $regex);
		}
		return $this;
	}

	/**
	 * Creates and adds a single route object.
	 * 
	 * @see \Phpf\Route\Route
	 * 
	 * @param string $uri URI path.
	 * @param array $args Route arguments passed to constructor.
	 * @param int $priority Route priority. Default 10.
	 * @return $this
	 */
	public function addRoute($uri, array $args, $priority = 10) {
		$route = new Route($uri, $args);
		$this->routes[$priority][$uri] = $route;
		return $this;
	}

	/**
	 * Adds a group of routes.
	 *
	 * Group can already exist in same or other grouping (priority).
	 *
	 * @param string $controller The lowercase controller name
	 * @param array $routes Array of 'route => callback'
	 * @param int $priority The group priority level
	 * @param string $position The routes' position within the group, if exists
	 * already
	 * @return boolean True, always.
	 */
	public function addRoutes(array $routes, $priority = 10) {

		$objects = array();

		foreach ( $routes as $uri => $args ) {
			$objects[$uri] = new Route($uri, $args);
		}

		if (empty($this->routes[$priority])) {
			$this->routes[$priority] = $objects;
		} else {
			$this->routes[$priority] = array_merge($objects, $this->routes[$priority]);
		}

		return true;
	}

	/** 
	 * &Alias of addRoute() 
	 */
	public function route($uri, array $args, $priority = 10) {
		return $this->addRoute($uri, $args, $priority);
	}

	/**
	 * Add a group of routes under an endpoint/namespace
	 * 
	 * @param string $path Endpoint path
	 * @param Closure $callback Closure that returns the routes
	 * @return $this
	 */
	public function endpoint($path, \Closure $callback) {
		$this->endpoints[$path] = $callback;
		return $this;
	}

	/**
	 * Set a controller class to use for the endpoint currently executing.
	 * 
	 * @see matchEndpoints()
	 * 
	 * @param string $class Controller classname.
	 * @return $this
	 */
	public function setController($class) {
		$this->ep_controller_class = $class;
		return $this;
	}

	/**
	 * Adds an action (event) callback. Also used for errors.
	 *
	 * Router events use the syntax 'router.<event>'
	 * 
	 * @param string $action Event name.
	 * @param mixed $call Callback to attach to event.
	 * @param int $priority Priority level of this attachment.
	 * @return $this
	 */
	public function on($action, $call, $priority = 10) {
		$this->events->on('router.'.$action, $call, $priority);
		return $this;
	}

	/**
	 * Calls action callback(s).
	 * 
	 * @param string $action Event name.
	 * @param mixed ... Arguments to pass to callbacks.
	 */
	public function trigger($action /* [, $arg1, ...] */) {
		$args = func_get_args();
		array_shift($args);
		$action = 'router.' . $action;
		return $this->events->triggerArray($action, $args);
	}

	/**
	 * Sends an error using an error handler based on status code, if exists.
	 *
	 * Router error events use the syntax 'router.http.<code>'
	 * 
	 * @param int $code HTTP response code, determines callbacks.
	 * @param Exception $e Exception thrown at the error.
	 * @param Route $route The route that has been matched, if set.
	 * @return void - Exits after trigger.
	 */
	public function error($code, \Exception $e, Route $route = null) {
		$this->response->setStatus($code);
		$this->trigger('http.'.$code, $e, $route, $this->request, $this->response);
		exit;
	}

	/**
	 * Matches request URI to a route.
	 * 
	 * Tries endpoint routes first, then static routes.
	 * 
	 * @return boolean True if match, otherwise false.
	 */
	protected function match() {

		$method = $this->request->getMethod();
		$uri = $this->request->getUri();

		if (! empty($this->endpoints)) {
			if ($this->matchEndpoints($uri, $method)) {
				return true;
			}
		}

		if (! empty($this->routes)) {
			// sort priority groups by descending priority
			ksort($this->routes);
			foreach ( $this->routes as $group ) {
				// iterate through the routes in each group
				foreach ( $group as $Route ) {
					if ($this->matchRoute($Route, $uri, $method)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Searches endpoints for route match.
	 * 
	 * @param string $uri Request URI.
	 * @param string $http_method Request HTTP method.
	 * @return boolean True if endpoint route matched, otherwise false.
	 */
	protected function matchEndpoints($uri, $http_method) {

		foreach ( $this->endpoints as $path => $closure ) {
				
			// check for endpoint match
			if (0 === strpos($uri, $path)) {

				// execute the closure to return the routes
				$routes = $closure($this);
				
				// allow endpoints to return an object instead of an array of routes, 
				// which will exit the routing process and subsequently return the object.
				// @see Phpf\Router::dispatch()
				if (is_object($routes)) {
					$this->caught_by_endpoint = $routes;
					return true;
				}
				
				$this->routes[$path] = array();
				
				// iterate through the array and create the route objects
				foreach ( $routes as $epUri => $array ) {
					
					// set action from path if author is lazy
					if (! isset($array['action'])) {
						if (ctype_alpha($slug = trim($epUri, '/'))) {
							$array['action'] = $slug;
						} else {
							continue; // non-alpha paths don't make good methods
						}
					}
					
					// set controller if missing
					if (! isset($array['controller'])) {
						if (isset($this->ep_controller_class)) {
							// Closure has set a controller class to use for all routes.
							$array['controller'] = $this->ep_controller_class;
							$array['callback'] = array($array['controller'], $array['action']);
						} else {
							continue; // I'm not a magician
						}
					}

					$array['endpoint'] = trim($path, '/');
					
					// create the route object
					$route = $this->routes[$path][$path.$epUri] = new Route($path.$epUri, $array);
					
					// match the route
					if ($this->matchRoute($route, $uri, $http_method)) {
						return true;
					}
				}

				unset($this->ep_controller_class);
			}
		}

		return false;
	}

	/**
	 * Determines if a given Route URI matches the request URI.
	 * If match, sets Router property $route and assembles the matched query
	 * vars and adds them to Request property $path_params. However, if
	 * the HTTP method is not allowed, a 405 Status error is returned.
	 * 
	 * @param Route $route The \Phpf\Route\Route object.
	 * @param string $uri Request URI.
	 * @param string $http_method Request HTTP method.
	 * @return boolean True if match and set up, otherwise false.
	 */
	protected function matchRoute(Route $route, $uri, $http_method) {

		$qvs = array();
		$route_uri = $this->parseRoute($route->uri, $qvs);
		
		if (preg_match('#^/?'.$route_uri.'/?$#i', $uri, $route_vars)) {
			
			// check if HTTP method is allowed
			if (! $route->isMethodAllowed($http_method)) {
				
				// send 405 with the 'Allow' header
				$exception = new \Phpf\Route\Exception\HttpMethodNotAllowed;
				$exception->setRequestedMethod($http_method);
				$exception->setAllowedMethods($route->getMethods());

				$this->response->addHeader('Allow', $exception->getAllowedMethodsString());

				$this->error(405, $exception, $route);
			}

			$this->route = $route;

			unset($route_vars[0]);

			if (! empty($qvs) && ! empty($route_vars)) {
				$this->request->setPathParams(array_combine($qvs, $route_vars));
			}

			return true;
		}

		return false;
	}

	/**
	 * Parses a route URI, changing query vars to regex and adding keys to $vars.
	 * 
	 * @param string $uri URI to parse.
	 * @param array &$vars Associative array of vars parsed from URI.
	 * @return string URI with var placeholders replaced with their corresponding regex.
	 */
	protected function parseRoute($uri, &$vars = array()) {
		
		// find vars either renamed or inline
		if (preg_match_all('/<(\w+):(.+?)>/', $uri, $M)) {
			
			// easier to use full match for str_replace() vs re-creating the pattern
			foreach ( $M[0] as $i => $str ) {

				if ('' !== $regex = $this->getRegex($M[2][$i])) {
					// Renamed: <id:int>
					$uri = str_replace($str, $regex, $uri);
					$vars[$M[2][$i]] = $M[1][$i];
				} else {
					// Inline: <year:[\d]{4}>
					$uri = str_replace($str, '('.$M[2][$i].')', $uri);
					$vars[$M[1][$i]] = $M[1][$i];
				}
			}
		}
		
		// find registered <var>'s
		if (preg_match_all('/<(\w+)>/', $uri, $M2)) {

			foreach ( $M2[1] as $i => $str ) {

				if ($regex = $this->getRegex($str)) {
					$uri = str_replace('<'.$str.'>', '('.$regex.')', $uri);
					$vars[$str] = $str;
				} else {
					trigger_error("Unknown route var '$str'.", E_USER_NOTICE);
				}
			}
		}

		return $uri;
	}

	/**
	 * Internal timer stop/start.
	 * 
	 * @param string One of 'start' or 'stop'.
	 * @return void
	 */
	protected function timer($start_stop) {
			
		$this->timer[$start_stop] = microtime(true);
			
		if (function_exists('timer_start')) {
			if ('start' === $start_stop) {
				timer_start('router');
			} else if ('stop' === $start_stop) {
				timer_end('router');
			}
		}
	}

}
