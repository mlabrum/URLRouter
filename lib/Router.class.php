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
	private $routes = Array();
	
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
	* @return URLRouter
	*/
	public function getInstance(){
		if(self::$instance){
			return self::$instance;
		}else{
			self::$instance = new self();
			return self::$instance;
		}
	}
	
	/**
	* Sets the directory that the controller files are located
	* @param string $directory
	*/
	public function setControllerDirectory($directory){
		$this->controllerDirectory = $directory;
	}
	
	/**
	* Adds the passed map of routes into the router
	* @param map $routes
	*/
	public function addRoutes($routes){
		$this->routes += $routes;
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
	protected function getRequestUri(){
		$url 		= $_SERVER['REQUEST_URI'];
		$parseout 	= $this->baseURL();
		
		if($parseout != "/"){
			$url = preg_replace("/^" . preg_quote($parseout, "/") . "/", "", $url);
		}
		
		return preg_replace("/\?". preg_quote($_SERVER['QUERY_STRING'])."$/", "", $url);
	}
    
	

}

class MultipleInstancesException extends \Exception{}

?>