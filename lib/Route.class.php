<?php
namespace URLRouter;
class Route{

	/**
	* Contains the options for the current route including the Path, Controller and Action
	* @var Map
	*/
	protected $options = Array(
		"Path" => "",
		"Controller" => "Index",
		"Action" => "Index"
	);
	
	/**
	* Contains the default options for the current route including the Path, Controller and Action
	* @var Map
	*/
	protected $defaultOptions;
	
	/**
	* Contains the options for the current route including the Path, Controller and Action
	* @var Map
	*/
	protected $validators;
	
	/**
	* Initializes the route with the passed options and validators
	* @return Route
	*/
	public function __construct($options=Array(), $validators=Array()){
		$this->options 		= Array_merge($this->options, $options);
		$this->defaultOptions 	= $this->options;
		$this->validators 		= $validators;
	}
	
	/**
	* Sets the current action 
	* @param String $action
	*/
	public function setAction($action){
		$this->options['Action'] = $action;
		return $this;
	}
	
	/**
	* Sets the current path
	* @param String $path
	*/
	public function setPath($path){
		$this->options['Path'] = $path;
		return $this;
	}
	
	/**
	* Sets the current controller
	* @param String $controller
	*/
	public function setController($controller){
		$this->options['Controller'] = $controller;
		return $this;
	}
	
	/**
	* returns an option parameter/url parameter
	* @param String $param
	* @return String
	*/
	public function getParam($param){
		if(isset($this->options[$param])){
			return $this->options[$param];
		}else{
			return false;
		}
	}
	
	
	/**
	* Tests if the passed $str is a url variable
	* @param String $str
	* @return Boolean
	*/
	protected function isUrlVar($str){
		return (strlen($str) > 0 && ($str[0] == ":"));
	}
	
	/**
	* Removes : from a url variable
	* @param String $var
	* @return String
	*/
	protected function stripUrlVar($var){
		return trim($var, ":");
	}
	
	
	
	/**
	* Parses the passed uri against the route returning true if it matches
	* @param String $uri
	* @return Boolean
	*/
	public function parse($uri){
		if($uri == $this->options["Path"]){
			return true;
		}elseif(empty($uri)){
			return false;
		}else{
			$params	= $this->options;
			$parts	= explode("/", $params["Path"]); // Create an array of path parts
			$urlparts 	= empty($uri) ? Array() :  explode("/", $uri); // Create an array of url parts
		    
		    
			// Check if the validators doesnt have a $ (meaning match everything after) and if url parts are greater than the path parts
			if((array_search("$", $this->validators) === false) && (count($urlparts) > count($parts))){
					return false;
			}
		    
			for($i=0;$i<count($parts);$i++){
				$part = $parts[$i];
			
				if($this->isUrlvar($part)){
					$spart = $this->stripUrlVar($part);
					if(isset($urlparts[$i])){
						if(isset($this->validators[$spart])){
							if($validator = $this->validators[$spart]){
							//handle the $ to the end of the url character
								if($validator === "$"){
									$temp = $urlparts;
									array_splice($temp, 0, $i);
									$urlpart = implode("/", $temp);
									$params[$spart] = $urlpart;
									break;
									
								}else{
									if(is_callable($validator) && !call_user_func($validator, $urlparts[$i], $spart)){ //handle custom param parsing
										//custom mask check failed, so return
										return false;
									}
								}
							}
						}
						$params[$spart] = $urlparts[$i];
					}else{
						//url doesnt contain a match for it, so look it up in the options
						if(isset($this->options[$spart])){
							continue;
						}
						return false;
					}
				}else{
					if(!isset($urlparts[$i]) || $part != $urlparts[$i]){
						return false;
					}
				}
			}
			$this->options = $params;
			return true;
		}
		return false;
	}

	/**
	* Includes the controller file and then calls the  action associated with the route
	* @throws NoRouteException, UnknownClassException, FileDoesntExistException
	*/
	public function call(){
		$className 	= ucfirst($this->options['Controller']) . "Controller";
		$actionName 	= ucfirst($this->options['Action']) . "Action";
		$file 			= Router::getInstance()->getControllerDirectory() . $className . ".php";
		
		if(file_exists($file)){
			include_once($file);
			if(class_exists($className)){
				$class = new $className();
		
				if(method_exists($class, "__call") || method_exists($class, $actionName)){
					call_user_func(Array($class, $actionName), Router::getInstance());
				}else if(method_exists($class, "NoRouteAction")){
					call_user_func(Array($class, "NoRouteAction"), Router::getInstance());   
				}else{
					throw new NoRouteException("Method $actionName or __call or NoRouteAction doesn't exist in class $className");
				}
			}else{
				throw new UnknownClassException("Class $className doesn't exist in file $file");
			}
		}else{
			throw new FileDoesntExistException("File $file doesn't exist");
		}
	}
	
	
	/**
	* Creates a url by using the route and the passed params
	* @param Map $params
	* @param Boolean $useDefaultRouteVars
	* @return String
	*/
	public function create($params, $useDefaultRouteVars = false){
		$uri = Router::getInstance()->baseURL();
		if(empty($this->options["Path"])){
			return $uri;
		}else{
			$options = $this->options;
		    
			if($useDefaultRouteVars){
				$options = $this->defaultOptions;
			}

			$route = $this->getParam("Path");
			if(preg_match_all("/:([^\/]*)/", $route, $matches)){
				foreach($matches[1] as $match){
					if(isset($options[$match])){
						$item	= $options[$match];
						$route	= $this->str_replace_once(":" . $match, $item, $route);
					}
				}
			}
			return 'http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://' . $_SERVER['HTTP_HOST'] . $uri . trim($route, "/");
		}
	}

	/**
	* Searches the $subject for $find then replaces it with $replace
	* @param String $find
	* @param String $replace
	* @param String $subject
	* @return String
	*/
	private function str_replace_once($find, $replace, $subject){
		if($pos = strpos($subject, $find)){
			return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($find));
		}else{
			return $subject;
		}
	}

}


class NoRouteException extends \Exception{}
class UnknownClassException extends \Exception{}
class FileDoesntExistException extends \Exception{}


?>