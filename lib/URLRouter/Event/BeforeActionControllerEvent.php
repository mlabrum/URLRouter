<?php

namespace URLRouter\Event;

use \Symfony\Component\EventDispatcher\Event;

class BeforeActionControllerEvent extends Event{
	private $action;

	public function getAction(){
		return $this->action;
	}
	
	public function setAction($action){
		return $this->action;
	}

}