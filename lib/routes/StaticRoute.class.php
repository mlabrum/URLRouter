<?php

/**
 * @author Matt Labrum <matt@labrum.me>
 * @license Beerware
 * @link url
 */
namespace URLRouter;
class StaticRoute extends Route{

	/**
	* Contains the options for the current route
	* @var Map
	*/
	protected $options = Array(
		"Path" => ""
	);

	/**
	* Contains the file to load
	* @var String
	*/
	private $file;

	/**
	* Initializes the route with the passed options and validators
	* @return Route
	*/
	public function __construct($file, $params, $validators){
		$this->file = $file;
		parent::__construct($params, $validators);
	}

	/**
	* Loads the file specified when creating the route
	* @return Route
	*/
	public function call(){
		if(file_exists($this->file)){
			require($this->file);
		}else{
			throw new FileDoesntExistException("File $file doesn't exist");
		}
	}
}


?>