<?php
/**
 * @package Phpf.Routes
 */

namespace Phpf;

use Phpf\EventContainer;
use Phpf\Reflection\Callback;
use Phpf\Route\Route;

class Router implements Common\iEventable
{

	protected $routes = array();

	protected $vars = array(
		'segment'	=> '([^_/][^/]+)', 
		'words'		=> '(\w\-+)', 
		'int'		=> '(\d+)', 
		'str'		=> '(.+?)', 
		'any'		=> '(.?.+?)', 
	);

	protected $request;

	protected $response;

	protected $route;

	protected $events;

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
	 * Matches and routes a request URI.
	 */
	public function dispatch(Request &$request, Response &$response) {
		
		$this->timer('start');
		
		$this->request = &$request;
		$this->response = &$response;
		
		if ($this->match()) {
			
			$this->timer('stop');
			
			if (isset($this->caught_by_endpoint)) {
				return $this->caught_by_endpoint->invoke($request, $response, $this);
			}
			
			$reflect = new Callback($this->route->getCallback());
			$this->trigger('dispatch:before', $this->route, $request, $response);

			try {
				// match request params with callback params, fill in defaults
				$reflect->reflectParameters($request->getParams());
				
			} catch (\Phpf\Reflection\Exception\MissingParam $e) {
				// missing a required parameter, throw 404
				$msg = str_replace('reflection', 'required route', $e->getMessage());
				$exception = new Route\Exception\MissingParam($msg);
				$this->error(404, $exception, $this->route);
			}
			
			// Invoke the callback
			$reflect->invoke();
			
			// Allow the route to be caught before being sent
			$this->trigger('dispatch', $this->route, $request, $response);
			
			// Do post-dispatching actions
			$this->trigger('dispatch:after', $this->route, $request, $response);
		
		} else {
			// Send 404 with UnknownRoute exception
			$this->error(404, new Route\Exception\UnknownRoute('Unknown route'), null);
		}
	}

	/**
	 * Gets Request object
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Gets Response object
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Gets matched Route object
	 */
	public function getRoute() {
		return $this->route;
	}

	/**
	 * Returns array of route objects, their URI as the key.
	 * Can return a specified priority group, otherwise returns all.
	 */
	public function getRoutes($priority = null) {
		if ($priority !== null)
			return isset($this->routes[$priority]) ? $this->routes[$priority] : array();
		return $this->routes;
	}

	/**
	 * Returns regex for a query var
	 */
	public function getRegex($key) {
		return isset($this->vars[$key]) ? $this->vars[$key] : '';
	}

	/**
	 * Returns array of query vars and regexes
	 */
	public function getVars() {
		return $this->vars;
	}

	/**
	 * Adds query var and regex
	 *
	 * @param string $name The query var name
	 * @param string $regex The var's regex
	 */
	public function addVar($name, $regex) {
		$this->vars[$name] = $regex;
		return $this;
	}

	/**
	 * Adds array of query vars and regexes
	 */
	public function addVars(array $vars) {
		foreach ( $vars as $name => $regex ) {
			$this->addVar($name, $regex);
		}
		return $this;
	}

	/**
	 * Adds a single route
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

	/** @alias addRoute() */
	public function route($uri, array $args, $priority = 10) {
		return $this->addRoute($uri, $args, $priority);
	}

	/**
	 * Add a group of routes under an endpoint/namespace
	 * 
	 * @param string $path Endpoint path
	 * @param Closure $callback Closure that returns the routes
	 */
	public function endpoint($path, \Closure $callback) {
		$this->endpoints[$path] = $callback;
		return $this;
	}

	/**
	 * Set a controller class to use for the current endpoint.
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
	 * Adds an extension to strip from URIs
	 * @return $this
	 */
	public function stripExtension($extension) {
		$this->strip_extensions .= '|'.ltrim($extension, '.');
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
	 * Timer
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

	/**
	 * Matches request URI to a route.
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
			ksort($this->routes);
			foreach ( $this->routes as $group ) {
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
	 */
	protected function matchEndpoints($uri, $http_method) {

		foreach ( $this->endpoints as $path => $closure ) {

			if (0 === strpos($uri, $path)) {

				$this->routes[$path] = array();
				$routes = $closure($this);
				
				if (is_object($routes)) {
					$this->caught_by_endpoint = $routes;
					return true;
				}

				foreach ( $routes as $epUri => $array ) {
					
					if (! isset($array['action'])) {
						if (ctype_alpha($slug = trim($epUri, '/'))) {
							$array['action'] = $slug;
						} else {
							continue;
						}
					}
					
					if (isset($this->ep_controller_class) && ! isset($array['controller'])) {
						// Closure has set a controller class to use for all routes.
						$array['controller'] = $this->ep_controller_class;
						$array['callback'] = array($array['controller'], $array['action']);
					}

					$array['endpoint'] = trim($path, '/');

					$route = $this->routes[$path][$path.$epUri] = new Route($path.$epUri, $array);

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
	 */
	protected function matchRoute(Route $route, $uri, $http_method) {

		$qvs = array();
		$route_uri = $this->parseRoute($route->uri, $qvs);

		if (preg_match('#^/?'.$route_uri.'/?$#i', $uri, $route_vars)) {

			if (! $route->isMethodAllowed($http_method)) {

				$exception = new Route\Exception\HttpMethodNotAllowed;
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
	 * Matches filetypes at the end of a string (usually URI) and removes them.
	 */
	protected function stripExtensions($string, &$match = null) {

		if (preg_match("/[\.|\/]($this->strip_extensions)/", $string, $matches)) {
			$match = $matches[1];
			// remove extension and separator
			$string = str_replace(substr($matches[0], 0, 1).$match, '', $string);
		}

		return $string;
	}

}
