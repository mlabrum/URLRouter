<?php

/**
 * @author Matt Labrum <matt@labrum.me>
 * @license Beerware
 * @link url
 */
namespace URLRouter;
class ConditionalRoute extends Route{
	/**
	* Contains the callback to call to check if we should run this route
	* @var Function
	*/
	private $callback;

	/**
	* Initializes the route with the passed options and validators
	* @return Route
	*/
	public function __construct($callback, $params, $validators=Array()){
		$this->callback = $callback;
		parent::__construct($params, $validators);
	}

	/**
	* Runs the callback function, checking if it returns true, if so it runs the route
	* @throws UnknownClassException
	*/
	public function call($options=false){
		if(is_callable($this->callback)){
			if(call_user_func($this->callback, Router::getInstance(), $this)){
				parent::call($options);
			}
		}else{
			throw new UnknownClassException("Unable to call callback");
		}
	}
}

?>