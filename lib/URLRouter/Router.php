<?php

/**
 * @author Matt Labrum <matt@labrum.me>
 * @license Beerware
 * @link url
 */
namespace URLRouter;
class Router{

	/**
	* Stores the path of the controller directory
	* @var String
	*/
	private $controllerDirectory = './controllers/';
	
	/**
	* Stores the array of route objects
	* @var Array
	*/	
	public $routes = Array();
	
	/**
	* Stores the current route
	* @var Route
	*/	
	public $route;
	
	/**
	 * Stores the current routes name
	 * @var String
	 */
	public $route_name = false;
	
	/**
	* Stores the instance of the router for the singleton design pattern
	* @var object
	*/
	static $instance; 
	
	/**
	 * Wraps a Controller in this class before being called
	 * @var String
	 */
	public $controller_wrapper_class	= false;
	
	/**
	 * An instance of the symfony EventDispatcher
	 */
	public $event_dispatcher		= false;
	
	const RESOLVE_CONTROLLER		= 'URLRouter.ResolveController';
	const BEFORE_CALL_ACTION		= 'URLRoute.BeforeCallAction';
	
	/**
	* Adds the passed map of routes into the router
	* @throws MultipleInstancesException
	* @return URLRouter
	*/
	public function __construct(){
		if(self::$instance){
			throw new MultipleInstancesException();
		}
		self::$instance = &$this;
	}
	
	public function wrap($obj){
		if(is_string($obj)){
			$obj = new $obj;
		}
		if($this->controller_wrapper_class){
			return new $this->controller_wrapper_class($obj);
		}
		return $obj;
	}
	
	/**
	* Returns the current instance of the URLRouter, creating one if one doesn't exist
	* @return Router
	*/
	public static function getInstance(){
		if(self::$instance){
			return self::$instance;
		}else{
			self::$instance = new self();
			return self::$instance;
		}
	}
	
	public static function redirect($url){
		header("location: " . str_replace("./", self::fullBaseURL(), $url));
		@session_write_close();
		exit;
	}
	
	/**
	* Sets the directory that the controller files are located
	* @param string $directory
	* @return Router
	*/
	public function setControllerDirectory($directory){
		$this->controllerDirectory = $directory;
		return $this;
	}
	
	/**
	* Gets the directory that the controller files are located
	* @return String
	*/
	public function getControllerDirectory(){
		return $this->controllerDirectory;
	}
	
	/**
	 * Returns the file path for the controller
	 * @param String $name
	 */
	public function getControllerFile($name){
		return $this->getControllerDirectory() . $name . ".php";
	}
	
	/**
	 * Returns the controllers class name
	 * @param string $name
	 */
	public function getControllerClassName($name){
		return ucfirst($name) . "Controller";
	}
	
	
	/**
	* Adds the passed map of routes into the router
	* @param map $routes
	* @return Router
	*/
	public function addRoutes($routes){
		$this->routes += $routes;
		return $this;
	}
	
	/**
	 * Returns the full base url with server details
	 * @return string
	 */
	public static function fullBaseURL(){
		return 'http' .(empty($_SERVER['HTTPS']) ? "" : "s") . '://'. $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), "\\") . '/';
	}
	
	/**
	* Gets the base url without the filename
	* @return string
	*/
	public function baseURL(){
		//$url = str_replace(strrchr($_SERVER['SCRIPT_NAME'], "/"), '', $_SERVER['SCRIPT_NAME']);
		$url = str_replace("index.php", "", $_SERVER['SCRIPT_NAME']);
		
		return $url == "/" ? "/" : $url . '/';
	}
	
	/**
	* Gets the base uri without the base and without the querystring
	* @return string
	*/
	public function getRequestUri(){
		$url 		= $_SERVER['REQUEST_URI'];
		$parseout 	= $this->baseURL();
		
		if($parseout != "/"){
			$url = preg_replace("/^" . preg_quote($parseout, "/") . "/", "", $url);
		}
		$url = trim($url, "/");
		$url = preg_replace("/\?.*$/", "", $url);
		
		return empty($url) ? "/" : $url;
	}
	
	public $is_sub_request = false;
	
	public function subrequest($url, $options=Array()){
		$old_request			= $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = $url;
		
		// Save the current post
		if(isset($options['POST'])){
			$old_post	= $_POST;
			$_POST		= $options['POST'];	
		}
		
		$this->is_sub_request = true;
		
		// Clone the current URLRouter
		$router = clone self::getInstance();
		
		$router->match();
		$ret = $router->dispatch();
		
		$this->is_sub_request = false;
		
		// Restore post
		if(isset($options['POST'])){
			$_POST = $old_post;
		}
		
		// Restore the old request URI
		$_SERVER['REQUEST_URI'] = $old_request;
		return $ret;
	}
	
	
	/**
	* Parses the url and selects the best route from {@link $routes}, if no route is found, then it will use the default route of 
	* if no url matches and the uri is empty then it will call IndexController::IndexAction otherwise it will call IndexController::404Action
	* @return URLRouter
	*/
	public function match(){
		$uri = $this->getRequestUri();
		
		foreach($this->routes as $name => $route){
			if($route->parse($uri)){
				$this->route		= $route;
				$this->route_name	= $name;
				return $this;
			}
		}
	
		// Default index and none of the routes picked it up
		if($uri == "/"){
			if(isset($this->routes['index'])){
				$this->route		= $this->routes['index'];
				$this->route_name	= "index";
				return $this;
			}
		}
		
		// If none of the routes matched
		$this->route = new Route\Route(); // Add empty "index" route
		if(!empty($uri)){
			$this->route->setAction("NoRoute");
		}
		
		return $this;
	}
    
	/**
	 * Returns if the current request is an XMLHTTPRequest
	 */
	public static function isXMLHTTPRequest(){
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
			return true;
		}
		return false;
	}
	
	/**
	 * Send a http 500 code back
	 */
	public static function sendHttpInternalServerError(){
		header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
	}
	
    /**
	* Calls the saved route in $route
	* @throws NoRouteException
	*/
	public function dispatch(){
		if($this->route instanceof Route\Route){
			return $this->route->call();
		}else{
			throw new NoRouteException;
		}
	}
	
	/**
	 * returns the route by the name, $name
	 */
	public static function route($name){
		return self::getInstance()->routes[$name];
	}
	
	/**
	 * Registers the router allowing the creation of {route} tags
	 */
	public function register_smarty($smarty){
		$smarty->registerPlugin("function", "route", Array(&$this , "smarty_plugin_route_tag"), true);
	}
	
	/**
	 * Handles the smarty {route} tag
	 * @param Array $args
	 * @param Smarty $smarty
	 */
	public function smarty_plugin_route_tag($args, $smarty){
		// php/smarty bug where $this = $smarty :/
		$router = Router::getInstance();
		$options = array_merge(Array(
			"get"		=> Array(),
			"keep_get"	=> false,
		), $args);
		
		if($options['keep_get']){
			$options['get'] = array_merge($_GET, $options['get']);
		}

		if(!isset($options['route']) || $options['name'] == "route"){
			$route = $router->route;
		}else{
			if(isset($router->routes[$options['route']])){
				$route = $router->routes[$options['route']];
			}else{
				throw new NoRouteException("no route by that name");
			}
		}
		
		if(!empty($options['get'])){
			$options['_GET'] = $options['get'];
		}
		
		unset($options['get']);
		unset($options['keep_get']);
		
		return $route->create($options);
	}

}

class MultipleInstancesException extends \Exception{}
class NoRouteException extends \Exception{}

?>