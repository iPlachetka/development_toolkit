<?php
/**
 * Sammy - A bare-bones PHP version of the Ruby Sinatra framework. Inspired by Dan Horrigan
 *
 * @version		1.0
 * @author		Maurice Calhoun
 * @license		MIT License
 * @copyright	2011 Maurice Calhoun
 */ 

/**
  * Helpers
**/

function generic_markup($title, $body)
{
	return Sammy::generate_template_markup($title,$body);
}  

function routes($method = 'GET')
{
	return(array_keys(Sammy::$routes[$method]));
}

function route_found()
{
	return Sammy::$route_found;
}     

function status_codes($number)
{
	$status_codes = array(
						// Informational 1xx
						100 => 'Continue',
						101 => 'Switching Protocols',
						// Successful 2xx
						200 => 'OK',
						201 => 'Created',
						202 => 'Accepted',
						203 => 'Non-Authoritative Information',
						204 => 'No Content',
						205 => 'Reset Content',
						206 => 'Partial Content',
						// Redirection 3xx
						300 => 'Multiple Choices',
						301 => 'Moved Permanently',
						302 => 'Found',
						303 => 'See Other',
						304 => 'Not Modified',
						305 => 'Use Proxy',
						307 => 'Temporary Redirect',
						// Client Error 4xx
						400 => 'Bad Request',
						401 => 'Unauthorized',
						402 => 'Payment Required',
						403 => 'Forbidden',
						404 => 'Not Found',
						405 => 'Method Not Allowed',
						406 => 'Not Acceptable',
						407 => 'Proxy Authentication Required',
						408 => 'Request Timeout',
						409 => 'Conflict',
						410 => 'Gone',
						411 => 'Length Required',
						412 => 'Precondition Failed',
						413 => 'Request Entity Too Large',
						414 => 'Request-URI Too Long',
						415 => 'Unsupported Media Type',
						416 => 'Request Range Not Satisfiable',
						417 => 'Expectation Failed',
						// Server Error 5xx
						500 => 'Internal Server Error',
						501 => 'Not Implemented',
						502 => 'Bad Gateway',
						503 => 'Service Unavailable',
						504 => 'Gateway Timeout',
						505 => 'HTTP Version Not Supported'
	                ); 
	
	if(array_key_exists($number, $status_codes))
	{
		return array($number => $status_codes[$number]);
	}  
	
	return FALSE;
}  

function halt()
{
	$args = func_get_args();
	$status = status_codes(200);
	$headers = array('Content-Type' => 'text/plain');
	$body = '';

	foreach($args as $arg)
	{
		if(is_numeric($arg))
		{
			$status = status_codes($arg);
		}
		elseif(is_array($arg))
		{
			$headers = $arg; 
		}
		elseif(is_string($arg) || is_object($arg))
		{
			$body = $arg;  
		} 

	}

	if($status){
		header('HTTP/1.1 '.current(array_keys($status))." " . current(array_values($status)));
	}

	foreach($headers as $type => $header)
	{
		header("$type: $header", current(array_keys($status))); 
	}
    
	if(empty($body))
	{
		if(array_key_exists(current(array_keys($status)), Sammy::$errors))
		{
			raise_error(current(array_keys($status)));   
		}
		else
		{
		   echo current(array_values($status)); 
		}
	}
	elseif(is_object($body))
	{
	   echo call_user_func($body);  
	}   
	else
	{
		echo($body); 
	} 
    
	Sammy::$halted = TRUE;
	die(); 
}

function get_user_defined_vars() 
{ 
    
	$results = array();
	$super_globals = array(
							'_ENV',
							'_POST',
							'_GET',
							'_COOKIE',
							'_SERVER',
							'_FILES',
							'_REQUEST',
							'GLOBALS',
							'HTTP_ENV_VARS',
							'HTTP_POST_VARS',
							'HTTP_GET_VARS',
							'HTTP_COOKIE_VARS',
							'HTTP_SERVER_VARS',
							'HTTP_POST_FILES' 
					);  
					
	  $super_globals_vars  = $GLOBALS;  
	
	  foreach($super_globals_vars as $key => $super_globals_var)
	  {
			if(!in_array($key,  $super_global))
			{
			   $results[$key] =  $super_globals_var;
			}
	  } 

	  return $results; 

} 

function raise_error($name, $die = FALSE, $arguments = array())
{   	
	call_user_func_array(Sammy::$errors[$name], $arguments); 
	if($die)
	{
		die();
	} 
} 

function pass($condition = FALSE)
{		
	if(is_object($condition))
	{
		$condition = call_user_func($condition);
	}
	
	if(gettype($condition) != 'boolean')
	{
		$condition = FALSE;  
	} 	    
				
	if(!$condition)
	{
		foreach(Sammy::$raw_request as $method => $data)
		{
		   unset(Sammy::$routes[$method][$data]); 
		}  
		call_route(Sammy::$request_uri);  
		die();    
	}

}

function call_route($request, $arguments = array())
{    
    $all_routes = array(); 
	$all_methods = array(); 
	foreach(Sammy::$routes as $key => $routes)
	{
		if(!empty($routes))
		{
			$all_routes = array_merge($all_routes, $routes);    
			foreach($routes as $route_name => $route_value)
			{
			  $all_methods[$route_name] = $key;   
			}
			
		}
	}  
       
	if(($matched = Sammy::reverse_preg_match_array($request, array_keys($all_routes))) && $matched !== false)
	{
			foreach($matched as $match)
			{
			 	list($changes,$params,$sammy_params, $match) = Sammy::process_params($request, array($match)); 
				Sammy::$params['params']['request'] = $request;      
				Sammy::raw_request($all_methods[$match], $match);    
				call_user_func_array($all_routes[$match], $params);   
				Sammy::$params['params']['request'] = Sammy::$params['params']['attempted_request'];   
				break;   
			}
	}
	else
	{
	   raise_error(404); 
	}  
	
	
}
  
function configure($function)
{
	call_user_func($function);
}    

function template($name, $function)
{   
	Sammy::add_template($name, $function);  
}  
  
function render($name, $options=array(), $return = FALSE, $method = 'render')
{ 

	if(!is_null(settings('template_engine')) && !empty($options))
	{
	   $template  = Sammy::render_template($name, array(), true);  
	   $template_engine = settings('template_engine');  

		if ($return === TRUE) 
		{
			return call_user_func_array(array($template_engine,$method), array($template,$options)); 
		}
		else
		{
		   echo call_user_func_array(array($template_engine, $method), array($template,$options)); 
		  return;  
		}  
	} 
	
	
	if ($return === TRUE) 
	{
		return Sammy::render_template($name, $options, $return);  
	}
	
	Sammy::render_template($name, $options, $return);
} 

function not_found($function)
{
	if(array_key_exists('404', Sammy::$errors))
	{
		Sammy::add_error('not_found', $function); 
	}
	else
	{
	  	Sammy::add_error(404, $function); 
	}
	
} 

function error($name, $function)
{
	Sammy::add_error($name, $function);
}   

function params($name = null)
{  
   	$request = '/' . ltrim(Sammy::$params['params']['request'], '/'); 
	
	$all_routes = array();
	foreach(Sammy::$routes as $routes)
	{
		if(!empty($routes))
		{
			$all_routes = array_merge($all_routes, $routes);
		}
	}
	 
	$data = Sammy::$params; 
	      
	if(($matched = Sammy::reverse_preg_match_array($request, array_keys($all_routes))) && $matched !== false)
	{    
		list($changes,$params,$sammy_params, $matched) =  Sammy::process_params($request, $matched); 
		
		if(Sammy::$params['params']['request'] != Sammy::$params['params']['attempted_request'])
		{
			$data = array_merge_recursive(Sammy::$params, $sammy_params);   
		}
	}
	
	return (is_null($name)) ? $data : $data['params'][$name];
}     

function get_segements($index = null)
{   
	return (is_null($index)) ? Sammy::$segments : Sammy::$segments[$index];
} 

function condition($name, $callback)
{  
	if(empty($name) || empty($callback))
	{
	   return; 
	}   
	
	$conditions = Sammy::$conditions;
	if(array_key_exists($name, $conditions))
	{
		if(!is_array($callback))
		{
		   $callback = array($callback); 
		}
		return call_user_func_array($conditions[$name], $callback);      
	}   
	else
	{
	   Sammy::add_condition($name,$callback);    
	}
} 

function settings($name, $values = array())
{
  	if(empty($name))
	{
	   return; 
	}  
	
	$settings = Sammy::$settings;   
	
	if(is_array($settings) && array_key_exists($name, $settings))
	{
		
		if(is_callable($settings[$name]))  
		{
		     return call_user_func_array($settings[$name], $values);  
		}
	      return $settings[$name];
	}   
	else
	{
	   Sammy::add_setting($name,$values);    
	}	
}



/**
  * Filters
**/       

function before($filter = '', $function = '')
{     
	if(empty($function))
	{
	 	$function = $filter;    
		Sammy::add_filter('before', 'all', $function);      
	}
	else
	{
	  Sammy::add_filter('before', $filter, $function);    
	}
	
}

function after($filter = '', $function = '')
{
	if(empty($function))
	{
	 	$function = $filter;
		Sammy::add_filter('after', 'all', $function);      
	}
	else
	{
	  Sammy::add_filter('after', $filter, $function);    
	}
}

/**
  * Routes
**/

function get($route, $callback, $conditions = array())
{
	Sammy::add_route('GET', $route, $callback);  	
}

function post($route, $callback, $conditions = array())
{
	Sammy::add_route('POST', $route, $callback);  	
}

function put($route, $callback, $conditions = array())
{
	Sammy::add_route('PUT', $route, $callback);  	
}

function delete($route, $callback, $conditions = array())
{
	Sammy::add_route('DELETE', $route, $callback);  	
}  

function ajax($route, $callback, $conditions = array()) 
{
	Sammy::add_route('AJAX', $route, $callback);  	
} 

class Sammy {
	
	public static $route_found = false;   
	public static $halted = false;   
	public static $segments = '';
	public static $method = '';  
	public static $request_uri = '';  
	public static $is_matched = false;
	public static $params;
	public static $ob_level;  
	public static $conditions;
    public static $settings;
 	public static $raw_request;
    public static $template_engine = NULL;    
    public static $application_name = 'Application';  
	
	private static $body = '';
	public static $errors = array();   
	
 
	 
	private static $templates = array();
	public static $filters = array(
		'before' => array(),
		'after' => array()
	);
	
	public static $routes = array(
		'GET' => array(),
		'POST' => array(),
		'PUT' => array(),
		'DELETE' => array(),
		'AJAX'	=> array()
	);	
		
	public static function instance()
	{
		static $instance = null;
		
		if ($instance === null)
		{
			$instance = new Sammy;
		}
		
		return $instance;
	}
	
	public static function add_route($method, $route, $function){
		self::$routes[$method][$route] = $function;
	} 
	
	public static function add_setting($name, $value){
		self::$settings[$name] = $value;
	} 
	
	public static function add_condition($name, $function){
		self::$conditions[$name] = $function;
	}	
	
	public static function add_error($error, $function){
		self::$errors[$error] = $function;
	}
	
	public static function raw_request($method, $route){
		self::$raw_request[$method] = $route;
	} 
	
	public static function generate_template_markup( $title, $body ) {
        $html = "<html><head><title>$title</title><style>body{margin:0 auto;text-align:left;padding:30px;width:600px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}code{ font-family:Monaco,Verdana,Sans-serif;  font-size:12px;  background-color:#f9f9f9;  border:1px solid #D0D0D0;  color:#002166;  display:block;  margin:14px 0 14px 0;  padding:12px 10px 12px 10px}pre{ font-family:Monaco,Verdana,Sans-serif;  font-size:12px;  background-color:#f9f9f9;  border:1px solid #D0D0D0;  color:#002166;  display:block;  margin:14px 0 14px 0;  padding:12px 10px 12px 10px}
		</style></head><body>";
        $html .= "<h1>$title</h1>";
        $html .= $body;
        $html .= '</body></html>';
        echo $html;     
		exit;

    }      

	public static function run($options = array())
	{
		if(self::$halted)
		{
			return;
		} 
				
		$method = self::get_method(); 
		$request = self::get_request_uri();   
		
		$addition_params = array(
								'method' => $method,
								'request'=> $request,
								'attempted_request' => $request
		);
		
		$results = self::process($method, $request); 
		if(!$results)
		{
		  $results = array();  
		} 	
			 
		$results = array_merge($results, $addition_params);  
				
		if(array_key_exists('params', self::$params))
		{
		    self::$params['params'] =  array_merge(self::$params['params'], $addition_params);     
		}  
		else
		{
		   self::$params['params'] =  $addition_params;      
		} 
		
		if(!count(array_filter(self::$routes)))
		{
			
			self::generate_template_markup('Welcome To ' .self::$application_name , '<h2>Try This </h2><code>
			get("/", function(){
				echo "Hello World"; 
			}); 
			</code> 
			<h2>or</h2>
			<pre>function home()
{
 echo "Hello World";   
}	
		
get("/", "home");</pre>');
			exit;
		} 
		
		if ( ! self::$route_found)
		{
			$results['params'] = array($addition_params); 
			$common_error_found = array_intersect_key(self::$errors, array_flip(array('not_found', 404))); 
			
			if(!empty(self::$errors) && $common_error_found)
			{
				foreach(self::$errors as $error_name => $error)     //array_key_exists('404', self::$errors)
				{
						if(in_array($error_name, array('not_found', 404)))
						{
							$results['callback'] = self::$errors[$error_name]; 
							$results['request'] = $error_name; 
						}
				}  
			}
			elseif($four_oh_four = current(preg_grep( '/(404|not_found|page_not_found)/', array_keys(self::$routes[$method]))))
			{
				$results['callback'] = self::$routes[$method][$four_oh_four];  
				$results['request'] = $four_oh_four;   
				
			} 
			else
			{
				$results['callback'] = create_function('', 'echo "We couldn\'t find that page.";');  
				
				self::generate_template_markup('404 Page Not Found', '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p>');
			} 
            
			self::$params['params'] =   current($results['params']);  
			self::$params['params']['request'] = $results['request'];  
		} 
		
		if($method != 'AJAX')
		{
			foreach(self::$filters['before'] as $before)
			{
			    if($before['route'] == 'all')
			   	{
				  call_user_func($before['function']); 
				}				
			}
			 
			foreach(self::$filters['before'] as $before)
			{
			    if($before['route'] != 'all' && self::reverse_preg_match_array($results['request'], array($before['route'])))
			   	{
					call_user_func_array($before['function'], $results['params']);  
				}
				
			}  
						
			call_user_func_array($results['callback'], $results['params']); 

			foreach(self::$filters['after'] as $after)
			{
				if($after['route'] == 'all')
			   	{
				  call_user_func($after['function']); 
				} 
			} 
			
 			foreach(self::$filters['after'] as $after)
			{
				if($after['route'] != 'all' && self::reverse_preg_match_array($results['request'], array($after['route'])))
				{
					call_user_func_array($after['function'], $results['params']);  
				}
			}
		} 
		else
		{
			call_user_func_array($results['callback'], $results['params']); 
		}
		
	    ob_end_flush();
	}
	
	public static function add_template($name, $function){ 
		self::$templates[$name] = $function;   
	}	  
	
	public static function add_filter($type, $filter, $function){ 
			
			array_push(self::$filters[$type], array('route' => $filter, 'function' => $function));   
	  }	
	
	public static function render_template($template_name, $vars = array() , $return = FALSE)
	{
		$locals = $vars;  
		
	    $types = array(
			'with_ext' 	=>  BASE_PATH.$template_name.'.php',  
			'without_ext'  =>	BASE_PATH.$template_name 
		);
		
		$exist = FALSE;
		foreach($types as $key => $value)
		{
			if(file_exists($value))
			{
			  $exist = $value;
			  break;  
			}
		}

		if(!$exist == FALSE)
		{
             		
			if(is_array($locals) && count($locals) > 0) 
			{
			  extract($locals, EXTR_PREFIX_SAME, "wddx");   
			}   

			ob_start();     

			require($exist); 

			if ($return === TRUE)
			{		
				$buffer = ob_get_contents(); 
				@ob_end_clean();  				
				return $buffer;
			}

			if (ob_get_level())
			{
				ob_end_flush();
			}
			else
			{
				@ob_end_clean();     
			}   
		}
		else
		{
			$template = $template_name;   
			if(array_key_exists($template, self::$templates))
			{
				if ($return === TRUE) 
				{
					return call_user_func_array(self::$templates[$template], array($locals)); 
				} 

			   	call_user_func_array(self::$templates[$template], array($locals));				
			}   
			
		} 
	}	
	
	public function url_difference($url_1, $url_2)
	{
		if($url_1 == $url_2)
		{
			return array();
		}
			
		$differences = array();
		$url_1 = array_filter(explode('/', $url_1));
		$url_2 = array_filter(explode('/', $url_2));
		                                 	
		if(count($url_1) == count($url_2))
		{  	
			foreach($url_1 as $key => $url_1_item)
			{
				if($url_2[$key] !== $url_1_item)
				{
					if(array_key_exists($url_2[$key], $differences))
					{
						if(!is_array($differences[$url_2[$key]]))
						{
							$differences[$url_2[$key]] = array($differences[$url_2[$key]],$url_1_item); 
						}
						else
						{
						  $differences[$url_2[$key]][] = $url_1_item;   
						}
					}
					else
					{
						$differences[$url_2[$key]] = $url_1_item; 
					}
				}
			}
		
			return array_filter($differences);  
		} 
		
		return false;
		
		
	}  
	
	public function reverse_preg_match_array($string, $regex_array, $also_match = array('#\*(/|$)#', '/:[A-Za-z0-9_-]+/'))	
	{
		$matches = array(); 
		
		foreach($regex_array as $regex){ 
		
			$new_regex = $regex; 

			foreach($also_match as $match) 
			{
				$new_regex = preg_replace($match, '.*?', $new_regex);   
    		    	 
				if(preg_match("#^$new_regex$#", $string))
				{ 
					if(!in_array($regex, $matches))
					{
					   $matches[] = $regex; 
					}
				}  

			} 
		
		} 
				 		  
		return (empty($matches)) ? FALSE : $matches; 
	}
	
	public static function process_params($request, $matched)
	{    
		$matched = end($matched);   

		$changes = self::url_difference($request, $matched);  

		$params = array();  
		$temp_params['params'] = array(
						'splat' => array(),
						'captures' => array()
						);

	foreach($changes as $index => $value)
		{  
			if(preg_match('/^:/', $index))
			{
				$index = preg_replace('/^:/', '', $index);
				$params = array_merge($params, array($value)); 
				$temp_params['params'][$index] = $value;

			}
			elseif($index == '*')
			{ 
				if(count($value) > 1)
				{
				  $temp_params['params']['splat'] = array_merge($temp_params['params']['splat'], $value); 
				  $params = array_merge($params, $value); 
				} 
				else
				{
				   $temp_params['params']['splat'] = $value; 
				   $params = array_merge($params, array($value)); 
				}

			} 
			else
			{ 
				if(count($value) > 1)
				{
				   $temp_params['params']['captures'] = array_merge($temp_params['params']['captures'], $value);   
				   $params = array_merge($params, $value);   
				} 
				else
				{
				  $temp_params['params']['captures'] = $value; 
				  $params = array_merge($params, array($value));   
				} 
			}
		}   
        
		
		return array($changes, $params, $temp_params, $matched);   
	}	
	      

	public static function process($method, $request)
	{		
		$callback = false;
		
		$temp_instance = array();    
		
		$parameter_names = array(); 
		$parameter_values = array();  
		
		$params = array(); 
		
		$temp_instance = array(
						'splat' => array(),
						'captures' => array()
						);   

		if(($matched = self::reverse_preg_match_array($request, array_keys(self::$routes[$method]))) && $matched !== false)
		{

			foreach($matched as $match)
			{
			 
				list($changes,$params, $temp_instance , $match) = self::process_params($request, array($match));   
			
   	 			self::$params = $temp_instance; 
 			
				if($changes !== false && $method == self::get_method())
				{
					$callback =  self::$routes[$method][$match];
					self::$is_matched = true;
				 
				}  
				
				break;
			
			}   
			
		}   

		if (self::$route_found ||  ! self::$is_matched || $callback === false)
		{
			return false;
		}  
			   
		self::$route_found = $match; 
				   
		self::$params = self::get_incoming_parameters($method);   
		self::$segments = self::$segments;  
				
		$temp_instance['params'][$method] = self::$params;  

		self::$params = $temp_instance; 
		
		self::$raw_request = array($method => $match); 
		
		return array('callback' => $callback , 'params' => $params, 'raw_request' => array($method => $match));
	}
		
	public function __construct()
	{
		ob_start(); 
		self::get_request_uri();  
		
		self::$segments = explode('/', trim(self::$request_uri, '/'));
		self::$method = self::get_method();      
	  	self::$ob_level  = ob_get_level();     

	    
	
	 	self::$params = array(); 
		self::$conditions = array();     
	}

	public function segment($num)
	{
		return isset(self::$segments[$num - 1]) ? self::$segments[$num - 1] : null;
	}
	
	protected function get_request_uri()
	{
 		$request = $_SERVER['REQUEST_URI'];   
	    $pos = strpos($request, '?');
	    if ($pos) {$request = substr($request, 0, $pos);}

	    self::$request_uri = $request;
	   	return self::$request_uri; 
	}

	protected function get_method()
	{
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&  $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
		{
		  return 'AJAX';  
		}   
		
		if($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['_method']) && $_POST['_method'] === 'PUT')) 
		{
			return 'PUT';
		}
		
		if($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) 
		{
			return 'DELETE';
		}
		
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	}
	
	public function get_incoming_parameters($request_method)
	{				
		switch ($request_method)
		{
			case 'AJAX':
				 return self::get_incoming_parameters($_SERVER['REQUEST_METHOD']);
				 break;
			case 'GET':   
				return ($_SERVER['REQUEST_METHOD'] === 'GET') ? self::$params['params'] : false;   
				break;
			case 'POST':  
				return ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : false; 
				break;
			case 'PUT': 
			case 'DELETE': 
 				parse_str(@file_get_contents('php://input'), $data);   
				parse_str(@$HTTP_RAW_POST_DATA, $HTTP_RAW_POST_DATA); 
				parse_str(@$GLOBALS['HTTP_RAW_POST_DATA'], $GLOBALS['HTTP_RAW_POST_DATA']);
				
				foreach(array($data, $HTTP_RAW_POST_DATA, $GLOBALS['HTTP_RAW_POST_DATA'], $_POST) as $raw_data)
				{
					if(!empty($raw_data)) 
					{
						return $raw_data;	
					}
				}
				break;
		}  
		
	}
	
}

$sammy = Sammy::instance();  

register_shutdown_function(array($sammy,"run"), E_ALL);