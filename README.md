# URLRouter
 
This class lets you define the mapping of URL's to controllers and actions 
 
for example the URL  http://example.com/test/ could be automaticly routed to => TestController::test()
 
-----------------------
 
### Adding A Default Route:

The default route is named index

		$routes = Array(
			"index" => new URLRouter\Route()
		)

This will call IndexController::IndexAction() when the user requests /
 
### Adding Routes:
To add routes to the URLRouter you pass a Hash to the the URLRouter::addRoutes function, see the section "INITIALIZING THE ROUTER" to see how to correctly to that
	
		$routes = Array(
			"index" => new URLRouter\Route()
		)
 
-------------
### Types Of Routes:
Currently the router supports three types of routes (but its simple to create your own)
	
#### URLRouter\StaticRoute
This does as its name suggests, maps a route to a static file, this is mainly used to load legency code
		
For example, loading the old homepage for the index

		$routes = Array(
			"index" => new URLRouter\StaticRoute("old/home.php")
		);
	
		
An example that uses a Path

		$routes = Array(
			"old" =>  new URLRouter\StaticRoute("old/homepage.php", Array("Path" => "old"))
		);
		
A more complex example, since Static also supports dynamic paths

		$routes = Array(
			"old" =>  new URLRouter\StaticRoute("old/:page:.php", Array("Path" => "old/:page", "page" => "homepage"))
		);
		
Note the :page: replacement used in the first parameter, you can use any variable defined in the Path
		
The above example will map

		/old/ 		=> old/homepage.php
		/old/info	=> old/info.php
	
#### RouterRoute: 
This is the main type of route, it maps urls into classes and functions
		
For example mapping the following

		/ 		=> IndexController::IndexAction()
		/jobs 	=> JobsController::IndexAction()
		/jobs/info	=> JobsController::InfoAction()
		
So for example, our above routes would look like this in a $routes array

		$routes = Array(
			"index" => 	new URLRouter\Route(),
			"Jobs" => 	new URLRouter\Route("Jobs/:action")
		);
		
By using the :action variable inside the Jobs route, the class will automaticly call the correct JobsController::*Action() function, if :action is empty, then it defaults to Index
		
We can also set the default values of the variables

		$routes = Array(
			"index" => 	new URLRouter\Route(),
			"Jobs" => 	new URLRouter\Route("Jobs/:action/:blah", "Controller" => "Jobs", "Action" => "index", "blah" => "test")
		);
		
Note: you may notice we set the controller for the Jobs route, this is because the Controller defaults to Index, so if we want to use a different one, we must specify it

Now we can use the urls

		/Jobs/
		/Jobs/anything 
		/Jobs/info/anything
		
For the third route to access the info in the blah variable we use a JobsController::InfoAction function looking like
		
		class JobsController{
			InfoAction($router){
				echo $router->getParam("blah");
			}
		}
		
Now, how about if you want to validate the url variables?
For example, only allowing blah to contain numbers, for this we use the third parameter of the RouterRoute class, which is the "validators" of the variables
		
Types of validators
	Regular Expressions: 
		Simply define the mask as "/regexp/" and it will compare the variable against that regular expression
			
	Callbacks:
		Pass a valid php callback and that function will be passed that parameter to validate (the function is called with the parameters, urlPart and the name of the variable)
			
	Capture all: 
		This is a special mask, which allows you to define a mask which will capture the rest of the url, simply pass a single "$" and that variable will contain the rest of the url
		This is handy for allowing the / in the url
		
Example of a regular expression matching only numbers in the :blah variable:
		
		$routes = Array(
			"index" => 	new URLRouter\Route(),
			"Jobs" => 	new URLRouter\Route("Jobs/:action/:blah", "Controller" => "Jobs", "Action" => "index", "blah" => 0, Array("blah" => "/^[0-9]+$/"))
		);
		
#### URLRouter\ConditionalRoute
This route is exactly the same as RouterRoute except it allows you to define a callback which returns a boolean, if true it continues like RouterRoute, if false, it does nothing
		
For example to test if the user is logged in
		
		new URLRouter\ConditionalRoute("loggedIn", Array("Path" => "test/:param", "Action" => "xxx", "param" => "rawr"))

		function loggedIn($router, $route){
		    if(User::userIsLoggedIn){
				return true;
		    }
		    
		    //redirect takes a array of default replacement variables
		    $router->redirect($router->routes["login"]->redirect(Array("return_to" => $route->url)))
		    return false
		}

--------------------

### Initalizing The Router:
 
	 $router = new URLRouter\Router();
	 $router->setControllerDirectory('./controllers/') //directory where your classes are
		  ->addRoutes($routes)
		  ->match() //parses the url and finds the correct route
		  ->dispatch(); //calls that route
 
 
 
 Using RouterRoute you define Controllers which have Actions that get called
 
 for Example:
 
 IndexController.php
 
		class IndexController{
				public function IndexAction($URLRouter){
						echo "index";
				}
    
				public function SomeotherAction($URLRouter){
					echo $URLRouter->getParam("param");
    				}
    
   				//NoRoute is a special function called when the router cannot find a route
				public function NoRouteAction(){
					echo "404";
   				}  
		 }
 
 
### To add more special routes
 
		$routes = Array(
				"index" => new URLRouter\Route(),
				"someother" => new URLRouter\Route(Array("Path" => "test/:param", "Action" => "xxx", "param" => "rawr"))
		)
 
In the Path you can specify variable placeholders that start with : eg :test, these will be filled in with the value it matches in the url
If no value matches, then it will attempt to use one defined in the options array
 
 
You can also add validators which can perform special things on the variables
 
For Example:
 
		new URLRouter\Route(Array("Path" => "test/:param", "Action" => "xxx", "param" => "rawr"), Array("param" => "$"))
 
The special $ mask means, the param continues to the end of the url so test/:param will match test/1/2/3/4 and :param will contain 1/2/3/4
 
 
Otherwise you can pass a callback as a mask which will validate the mask
 
For Example:
 
		new URLRouter\Route(Array("Path" => "test/:param", "Action" => "xxx", "param" => "rawr"), Array("param" => "valid"))
 
		// Returns true or false if the param is correct
		function valid($urlpart, $paramName){
				if(doSomething($urlPart)){
						return true;
				}else{
						return false;
				}
		}
 
----------------------
### Other Route Classes 

#### URLRouter\StaticRoute which lets you load files, rather than calling functions
    
		new URLRouter\StaticRoute("test.php", Array("Path" => "test/:param", "Action" => "xxx", "param" => "rawr"))
    
This will call test.php if the url matches
    

#### URLRouter\ConditionalRoute lets you pass a callback and if the callback returns true, it does the same as RouterRoute, else it does nothing

For example to test if someone is logged in before serving a url

		new URLRouter\ConditionalRoute("loggedIn", Array("Path" => "test/:param", "Action" => "xxx", "param" => "rawr"))

		function loggedIn($URLRouter, $route){
   				if(userIsLoggedIn){
						return true;
				}else{
						//redirect the user to the login page
				}
				return false;
		}

----------------
 
#### .htaccess
For this to work, you'll need to place the following in your .htaccess file

		RewriteEngine on
		RewriteCond %{SCRIPT_FILENAME} !-f
		RewriteCond %{SCRIPT_FILENAME} !-d
		RewriteRule (.*) index.php/$1 [PT] 
