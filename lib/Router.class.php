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
		$url = str_replace(strrchr($_SERVER['SCRIPT_NAME'], "/"), '', $_SERVER['SCRIPT_NAME']);
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
	
		// If none of the routes matched
		$this->route = new Route(); // Add empty "index" route
		if(!empty($uri)){
			$this->route->setAction("NoRoute");
		}
		
		return $this;
	}
    
        /**
	* Calls the saved route in $route
	* @throws NoRouteException
	*/
	public function dispatch(){
		if($this->route instanceof Route){
			$this->route->call();
		}else{
			throw new NoRouteException;
		}
	}

}

class MultipleInstancesException extends \Exception{}
class NoRouteException extends \Exception{}

?>