<?php
namespace URLRouter\Route;

use URLRouter\Router;

class Route{

	/**
	* Contains the options for the current route including the Path, Controller and Action
	* @var Map
	*/
	protected $options = Array(
		"Path" 			=> "",
		"Controller"	=> "Index",
		"Action" 		=> "Index",
		"Throw404"		=> false
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
		$this->options 			= Array_merge($this->options, $options);
		$this->defaultOptions 	= $this->options;
		$this->validators 		= $validators;
	}
	
	/**
	 * Creates an instance of the Route class, helpful for chaining by URLRouter\Route::create()->method(), because new URLRouter\Route()->method() isn't valid
	 */
	public static function initialize($options=Array(), $validators=Array()){
		return new self($options, $validators);
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
	 * Sets a option parameter/url parameter
	 * @param String $param
	 * @param String $value
	 */
	public function setParam($param, $value){
		if(is_array($param)){
			$this->options = array_merge($this->options, $param);
		}else{
			$this->options[$param] = $value;
		}
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
				
				// parse optional tags ()
				if(strpos($part, "(") !== false){
					
					/* test if the string is the same */
					$r = str_replace(")", ")?", $part);
					if(preg_match("/" . str_replace(Array("\\(", "\\)", "\\?"), Array("(", ")", "?"), preg_quote($r)) ."/", $urlparts[$i])){
						// it matches so add it in
						
						$part = $urlparts[$i];
					}else{
						return false; // not the same
					}
				}
				
				
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

	private $controllerInstance			 = Array();
	
	protected function controllerResolverEvent($toCall){
		$router	= Router::getInstance();
		if($router->event_dispatcher){
			$event	= new \URLRouter\Event\FilterControllerEvent();
			$event->setController($toCall);
			$router->event_dispatcher->dispatch(Router::RESOLVE_CONTROLLER, $event);
			return $event->getController();
		}
		
		return $toCall;
	}
	
	/**
	 * Creates an Instance of the controller
	 * @throws FileDoesntExistException, UnknownClassException
	 */
	public function getControllerInstance(){
		$router		= Router::getInstance();
		
		$className 	= $router->getControllerClassName($this->options['Controller']);
		$actionName	= ucfirst($this->options['Action']) . "Action";
		
		if(isset($this->options['Namespace'])){
			$className = $this->options['Namespace'] . '\\' . $className;
		}
		
		return Array(new $className($actionName), $actionName);
	}
	
	
	/**
	* Includes the controller file and then calls the  action associated with the route
	* @throws NoRouteException
	*/
	public function call($options=false){
		$router	= Router::getInstance();
		
		// Get the controller instance
		$toCall 			= $this->getControllerInstance();
		
		// Call the event handler to get the modified tocall
		$toCall				= $this->controllerResolverEvent($toCall);

		// Support passing in custom options
		if($options){
			$old_options	= $this->options;
			$this->options	=  array_merge($this->options, $options);
		}
		
		try{
			if(is_array($toCall)){
				list($class, $actionName) = $toCall;
				
				if(!method_exists($class, "__call") && !method_exists($class, $actionName)){
					throw new NoRouteException("Method $actionName or __call or NoRouteAction doesn't exist in class " . get_class($class));
				}
			}
			
			$ret = call_user_func($toCall, $router, $this);
			
			// Restore options
			if($options){
				$this->options = $old_options;
			}
			
			return $ret;
		}catch(\Exception $e){
			if(method_exists($toCall[0], "CatchException")){
				call_user_func(Array($toCall[0], "CatchException"), $e, $router, $this);	
			}else{
				throw $e;
			}
		}
		
	}
	
	
	/**
	* Creates a url by using the route and the passed params
	* @param Map $params
	* @param Boolean $useDefaultRouteVars
	* @return String
	*/
	public function create($params, $mergeCurrent=true, $useDefaultRouteVars = true){
		$uri = Router::getInstance()->baseURL();
		
		$extra = "";

		if(isset($params['_GET'])){
			if(isset($params['_GET']['url_page'])){
				unset($params['_GET']['url_page']);
			}		
			
			$query_strings = Array();
			foreach($params['_GET'] as $name => $value){
				if(!empty($value)){
					$query_strings[] = sprintf("%s=%s", $name, $value);
				}else{
					if(!isset($params['_NO_EMPTY'])){
						$query_strings[] = $name;
					}
				}
			}
			if(!empty($query_strings)){
				$extra = "?";
				$extra .= implode("&", $query_strings);
			}
			unset($params['_GET']);
		}
		
		if(empty($this->options["Path"])){
			return $uri;
		}else{		    
			if($useDefaultRouteVars){
				$options = array_merge($this->defaultOptions, $params);
			}

			if($mergeCurrent){
				$options = array_merge($this->options, $params);
			}else{
				$options = $params;
			}

			$route = $this->getParam("Path");

			// Remove optional bits
			$route = preg_replace("/\(.*?\)/", "", $route);
			
			if(preg_match_all("/:([^\/]*)/", $route, $matches)){
				foreach($matches[1] as $match){
					if(isset($options[$match])){
						$item	= $options[$match];
						$route	= $this->str_replace_once(":" . $match, strtolower($item), $route);
					}else{
						// Replace it with nothing
						$route = $this->str_replace_once(":" . $match, "", $route);
					}
				}
			}

			$base = "";
			if(empty($options['only_uri'])){
				$base = 'http' . (empty($_SERVER['HTTPS']) ? "" : "s") . '://' . $_SERVER['HTTP_HOST'];
			}else{
				$uri = "/";
			}


			if(isset($options['#'])){
				$extra .= "#" . $options['#'];
			}
			
			return $base . $uri . trim($route, "/") . $extra;
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

class UnknownClassException extends \Exception{}
class FileDoesntExistException extends \Exception{}


?>