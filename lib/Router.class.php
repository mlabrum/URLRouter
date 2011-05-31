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
	* @param map $routes
	* @return URLRouter
	*/
	public function __construct(){
		if(self::$instance){
			throw new URLRouterMultipleInstancesException();
		}
		self::$instance = &$this;
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
	

}

class URLRouterMultipleInstancesException extends exception{}


?>