<?php

namespace URLRouter;
class AjaxRoute extends Route{
	private $isMultiCall = false;
	
	public function __construct($params=Array(), $validators=Array()){
		parent::__construct($params, $validators);
	}

	public function setAllowedControllers($controllers){
		$this->options['AllowedControllers'] = $controllers;
	}

	public function getAllowedControllers(){
		return $this->options['AllowedControllers'];
	}

	/**
	 * Add support for Ajax controller initialization
	 * (non-PHPdoc)
	 * @see URLRouter.Route::getControllerInstance()
	 */
	public function getControllerInstance(){
		if(empty($this->options['Controller'])){
			if(!isset($_POST['MultiCall'])){
				$this->options['Controller']	= $_POST['Controller']; 
				$this->options['Action']		= $_POST['Action'];

				return parent::getControllerInstance();
			}else{
				// MultiCall always requires login
				return false;
			}
		}else{
			return parent::getControllerInstance();
		}
	}
	
	public function call($controller = false, $action = false, $data= false, $returnJson = false){
		if(Router::isXMLHTTPRequest()){

			// Handles an array of requests inside the MultiCall
			if(isset($_POST['MultiCall'])){
				$multiCall = $_POST['MultiCall'];
				$this->isMultiCall = true;
				unset($_POST['MultiCall']);
				
				$result = Array();
				foreach($multiCall as $i => $call){
					$result[$i] = $this->call(ucfirst($call['Controller']), $call['Action'], $call['data'], true);
				}
				
				header("Content-type: application/json");
				echo json_encode($result);
				
				exit;
			}
			
			if($controller && $action && $data){
				
			}else if($_SERVER['REQUEST_METHOD'] == "GET"){
				$controller = !empty($this->options['Controller']) ? ucfirst($this->options['Controller']) : false;
				$action		= !empty($this->options['Action']) ? $this->options['Action'] : false;
			}else{
				$controller = isset($_POST['Controller']) ? $_POST['Controller'] : (!empty($this->options['Controller']) ? ucfirst($this->options['Controller']) : false);
				$action		= isset($_POST['Action']) ? $_POST['Action'] : (!empty($this->options['Action']) ? $this->options['Action'] : false);
				$data		= isset($_POST['data']) ? $_POST['data'] : false;
			}
			
			
			if($controller){
				if(isset($this->options['AllowedControllers']) && !in_array($controller, $this->options['AllowedControllers'])){
					$this->badRequest("$controller: Invalid Controller");
				}
				
				if($data){
					$this->options = array_merge($this->options, $data);
				}
				
				$this->options['Action']		= 'Remote' . ucfirst($action);
				$this->options['Controller']	= $controller;
				$this->options['Throw404']		= true;
				
				try{
					$data = parent::call();
					
					if($returnJson){
						return $data;
					}else if(!empty($data)){
						header("Content-type: application/json");
						echo json_encode($data);
					}
				}catch(\Exception $e){
					$error = Array("status" => 'error', 'type' => get_class($e), "message" => $e->getMessage());
					
					if($this->isMultiCall){
						return $error;
					}else{
						$this->badRequest(json_encode($error));
					}
				}
			}else{
				$this->badRequest("No Controller specified");
			}
		}else{
			$this->badRequest("Must be a XMLHTTPRequest Request");
		}
	}
	
	private function badRequest($msg=""){
		header("HTTP/1.1 400 Bad Request");
		echo $msg;
		exit;
	}
}

?>