<?php

namespace URLRouter\Event;

use \Symfony\Component\EventDispatcher\Event;

class FilterControllerEvent extends Event{
	private $controller;

	public function getController(){
		return $this->controller;
	}
	
	public function setController($controller){
		$this->controller = $controller;
	}

}