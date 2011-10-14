<?php  

// error_reporting(E_ERROR | E_PARSE);
// error_reporting(0);  
// ini_set('file_uploads', 0); 

// echo ini_get('file_uploads', 'Off');   

// don't timeout!
set_time_limit(0); 

@ini_set('magic_quotes_runtime', 0);

// Kills the error from date(); Set it to your time zone.
// See: http://us2.php.net/manual/en/timezones.php
date_default_timezone_set('America/Chicago');

// Setup the log file.
$date = date("D, d M Y H:i:s");  

function not_supported($section, $referer = '/')
{
	return "<div class='not_supported' style='background-color: #F00; color: #FFF; text-align: center; padding: 10px; font-weight: bold;'>
				Sorry, ".$section." is not supported in this version. 
				<a href='".$referer."'>Return to ".$referer."</a>
		   </div>";
}

function debug($data, $name = 'Debugger')
{
  	if(!in_array(gettype($data), array('array', 'object', 'resource')))
	{
	  $data = trim($data, "\r\n\r\n");    
	}
	
	print_r("\r\n----- ".$name." -----\r\n\r\n");  
	print_r($data);
	print_r("\r\n\r\n----- ".$name." -----\r\n\r\n");  
}  

function remove_empty_array_items($array = array())
{
	$array = array_filter($array, 'trim'); 
	$array = array_filter($array);    
	return $array;
  
} 

// Record errors from PHP
function error_logger($errno, $errstr, $errfile, $errline)
{
    if ($errno != 2 && $errno != 8) {
        $output = fopen('php://stderr', 'w');
        fwrite($output, $errno . ' ' .$errstr .' - ' . $errfile .':'. $errline. "\r\n");
        fclose($output);
    }
    
	if($errno != 2) {  
		$system_directory = dirname(__FILE__);      
		chdir(dirname($system_directory));
		
        $error_log = fopen("logs/error_log.txt", "a");
        $date = date("D, d M Y H:i:s");
        fwrite($error_log, "[$date][$errline] $errno: $errstr ($errfile at line $errline)" . PHP_EOL . PHP_EOL);
        fclose($error_log);
    }
}

function request_log ($request_string) {  
	$system_directory = dirname(__FILE__);      
	chdir(dirname($system_directory));
        $log = fopen("logs/request_log.txt", "a");
        $date = date("D, d M Y H:i:s");
        fwrite($log, "[$date] $request_string" . PHP_EOL . PHP_EOL);
        fclose($log);
} 

function display_request($request)
{
	$date = date("[d/M/Y H:i:s]"); 
	$request = current(array_keys($request));  
	echo  gethostbyname(gethostname()) . ' ' .$date . ' "' . $request . '"' ."\r\n";
}



function process_headers($head) {
  $header_array = array();
  foreach ($head as $item) {
    list($key, $content) = explode(': ', $item);
    $header_array[trim($key)] = trim($content);
  }

  return $header_array;
} 

function socket_read_normal($socket, $end=array("\r", "\n")){
    if(is_array($end)){
        foreach($end as $k=>$v){
            $end[$k]=$v{0};
        }
        $string='';
        while(TRUE){
            $char=socket_read($socket,1);
            $string.=$char;
            foreach($end as $k=>$v){
                if($char==$v){
                    return $string;
                }
            }
        }
    }else{
        $endr=str_split($end);
        $try=count($endr);
        $string='';
        while(TRUE){
            $ver=0;
            foreach($endr as $k=>$v){
                $char=socket_read($socket,1);
                $string.=$char;
                if($char==$v){
                    $ver++;
                }else{
                    break;
                }
                if($ver==$try){
                    return $string;
                }
            }
        }
    }
}

function process_upload($request, $webroot) {

    $matches = array();
    $pattern = '/boundary=(.*)/';
    preg_match($pattern, $request, $matches);
    $boundary = $matches[1];  
 
    $pattern = '/Content-Disposition:.*filename=["](.*)["]/';
    preg_match($pattern, $request, $matches);   
    $filename = $matches[1];     

    $request = explode($boundary, $request);
    $content = $request[2];   

    $pattern = '/Content-Type: (.*)/';
    preg_match($pattern, $content, $matches);
    $filetype = $matches[1];   

    $pattern = '/Content-Disposition: form-data; name=["]([^"]*)["]; filename=["]([^"]*)["]/sm';
    preg_match($pattern, $content, $matches); 
    $name = $matches[1];   
    

	if(empty($boundary)) 
	{
		return FALSE;
	} 


    $vars = array();

    foreach($request as $line) {
        $matches = array();
        $pattern = '/Content-Disposition: form-data; name=["]([^"]*)["](.*)/sm';
        preg_match($pattern, $line, $matches);
        $var = $matches[1];

   
       $val = str_replace('; filename="'.$filename.'"', '', $matches[2]);
       $val = str_replace('Content-Type: '.$filetype, '', $val);
       $val = trim($val, "\x00..\x1F-");  
    
        $vars[$var] = $val;
    }  
      
	$vars = array_filter($vars); 
	
	foreach($vars as $key => $var)
	{
		if($key != $name) 
		{
			$boundary = trim($boundary);
			$vars[$key] = trim(str_replace('--'.$boundary, '', $var));    
		}
	} 
	  
  

    // If you want the server to treat downloads in the same manner PHP does,
    // use this code to store a temporary file. Then, fill out the $_FILES
    // array properly. As for cleaning up your temp file, best place would
    // probably be after the require for the uploadhandler.  

	$filename = substr(str_shuffle(implode('',array_merge(range('a','z'),range(0,9),range('A','Z')))), 0, 10);
	   
    $path = $webroot.'tmp/';   

    if(file_exists($path)) {
        $file = fopen($path.$filename, 'w');
    } else {
        mkdir($path);
        $file = fopen($path.$filename, 'w');
    }  
    fwrite($file, $vars[$name]);
    fclose($file);   

	unset($vars[$name]);  
	
	  
    // Certain lines are well known. File is first, accept is 3rd, POST is last.
    $file_string = $request[0];  
    
    // // The entire file name being requested:
    $pattern = '/[\/]([^\s?]*)/';
    if(preg_match($pattern, $file_string, $matches)) {
        // If we don't find a file match, use the index defined at the top of the file.
        $file = $matches[1];
        // Break it down into file . extension
        $pattern = '/^([^\s]*)[.]([^\s?]*?)$/';
        preg_match($pattern, $file, $matches);
        $real_filename = empty($matches[1]) ? $file : $matches[1]; 
        $extension =  !empty($matches[2]) ? '.' .$matches[2] : '';
    }
    
    $real_filename = urldecode($real_filename);
    $upload_handler = $webroot . $real_filename . $extension;
    
	$_FILES[$name]["name"] = $real_filename;				//the name of the uploaded file
	$_FILES[$name]["type"] = $filetype; 					//the type of the uploaded file
	$_FILES[$name]["size"] = filesize($path.$filename);		//the size in bytes of the uploaded file
	$_FILES[$name]["tmp_name"] = $path.$filename; 			//the name of the temporary copy of the file stored on the server
	$_FILES[$name]["error"] = '';							//the error code resulting from the file upload
  
    $post_string = http_build_query($vars);                                                                                                         
	return array('post_string' => $post_string, 'boundary' => $boundary);  
}

set_error_handler("error_logger");

$mime_type = array(
    'docx'  => 'application/msword',
    'doc'   => 'application/msword',
    'pdf'   => 'application/pdf',
    'txt'   => 'text/plain',
    'mp3'   => 'audio/mp3',
    'php'   => 'text/html',
    'html'  => 'text/html',
    'htm'   => 'text/html',
    'phtml' => 'text/html',
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'jpg'   => 'image/jpg',
    'png'   => 'image/png',
    'gif'   => 'image/gif',
    'ico'   => 'image/icon',
    ''      => 'text/html'
);

$defaults = array(
	'Server' => 'PHP '.phpversion()
);   
$address  = '127.0.0.1';
$web_dir = $argv[1];
$port =  $argv[2]; 

// Your starting directory. Trailing '/' required.
$webroot = realpath($web_dir).'/';  

// The default file to look for in your webroot.
$index = "index";
// And its extension
$index_extension = "php";  

$rewrite_engine = $argv[3];
//favicon\.ico 
$rewrite_condition = '/^(index\.php|images|frank|data|templates|document_library|assets|styles|js|sql|robots\.txt|test-js|music)/';  
$rewrite_rule = $index.'.'.$index_extension .'/';       

$socket = socket_create(AF_INET,SOCK_STREAM,getprotobyname('tcp')) or die("socket_create()failed."); 
if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) { 
    echo socket_strerror(socket_last_error($socket)); 
    exit; 
}
socket_bind($socket,$address,$port) or die("socket_bind()failed");
socket_listen($socket,0) or die("socket_listen()failed");

echo "Starting Server on $address on port $port\n";
echo "Web Root : $webroot\r\n\r\n";

if (!$socket) {
  echo "$errstr ($errno) - $errfile:$errline<br />" . PHP_EOL;
} else {


        $_POST = array();
        $_GET = array();    
        unset($conn);
        
        // Listen for a connection. 
	   
       do{   
	        
			//$conn = stream_socket_accept($socket, -1);     
			
		 if(($conn=socket_accept($socket))<0)
		 {
		  echo "socket_accept() failedn";
		  break;
		 }
   
		
            $_POST = array();
            $_GET = array();   

            unset($request);
            unset($inbound);
            unset($post_info);
            unset($server);
            $request = ""; 
			$temp = "";   
			
            $matches = array();
            $date = date("D, d M Y H:i:s");

            //Read the header.

 			$run = TRUE;  
			$content_length_pattern = "/Content-Length: ([0-9]*)/";  
			$end_of_header_pattern = "\r\n\r\n";
		    $boundary_pattern = '/boundary=(.*)/';  
			$form_pattern    = '/application\/x-www-form-urlencoded/';
		    
			$post_info = ''; 
			$buffer = 1;        
			
 
			 if(FALSE===($request = socket_read($conn, 2048)))
			 {
			  echo "socket_read() failedn";
			  break;
			 }  
			


			// debug($request, 'Request '); 
			// request_log($request);       
			// fclose($conn);  
                               
			
            // Lets set the server variables
            $pattern = "/Host: ([^\s?]*)/";
            if(preg_match($pattern, $request, $matches)) {
              list($host, $port) = explode(':', $matches[1]);
              $server['SERVER_NAME'] = $host; 
              $server['HTTP_HOST'] = $matches[1];
              if ($port) $server['SERVER_PORT'] = $port; 
            }  
            // thats the host, now the request method
            $pattern = "([^\s?]*)";
            if(preg_match($pattern, $request, $matches)) {
              $server['REQUEST_METHOD'] = $matches[0];
            }                       
                 
                // Find out if we have to parse POST info.
                $head = remove_empty_array_items(explode(PHP_EOL, $request));
                $headers_array = process_headers($head);  
											    
                $file_upload_post_string = process_upload($request, $webroot); 

				if($file_upload_post_string != FALSE)
				{
				 	
				   // $request = str_replace($post_info, '',  $request); 
				   $multipart = "Content-Type: multipart/form-data; boundary=".$file_upload_post_string['boundary']; 
				   $x_www_form_urlencoded = 'Content-Type: application/x-www-form-urlencoded';
				   // $request = str_replace($multipart , $x_www_form_urlencoded, $request); 
				} 
				
                // Split the request string into an array by line to make it easier to parse. 
                $request = remove_empty_array_items(explode(PHP_EOL, $request)); 
				  

                // Certain lines are well known. File is first, accept is 3rd, POST is last.
                $post_string = "";
                $get_string = "";
                $file_string = $request[0];
                $accept_string = $request[3];
                $post_string = ($file_upload_post_string != FALSE) ? $file_upload_post_string['post_string'] : array_pop($request);  
				$post_info = $post_string;  
							   
								
				if(strpos($post_info,$end_of_header_pattern)) 
				{
				  $post_info = str_replace($end_of_header_pattern, '', $post_info);  
				}  
				
				

                // The entire file name being requested:
                $pattern = '/[\/]([^\s?]*)/';
                if(preg_match($pattern, $file_string, $matches)) {
                    // If we don't find a file match, use the index defined at the top of the file.
                    //print_r($matches);
                    // this whole section needs rewriting!   
					
					if($rewrite_engine && !preg_match($rewrite_condition, $matches[1]))
					{
						$matches[1] =  $rewrite_rule . $matches[1];   
					} 
                         
                    if(empty($matches[1])) {
                        $file_name = $index;
                        $extension = $index_extension;
                    } else {
                        $file = $matches[1];
                        // Break it down into file . extension
                        $pattern = '/^([^\s]*)[.]([^\s]*?)[\/]([^\s]*)$/';
                        preg_match($pattern, $file, $matches);
                        //print_r($matches);
                        
                        $file_name = $matches[1];   
                        $extension = $matches[2];
                        $server['PATH_INFO'] = '/'.trim($matches[3], '/');
						$server['REQUEST_URI'] = $server['PATH_INFO'];
                        $server['SCRIPT_NAME'] = '/'.$file_name.'.'.$extension;
                        // remove the first part
                        $pattern = '/[\/]([^\s]*)/';
                        //preg_match($pattern, $file_string, $matches);
                        //$request_u_rI =  $matches[0];
                        if (count($matches) < 3) { 
                          //echo count($matches);
                          // this can probably be improved - its the same preg match as above, but with no path info
                          $pattern = '/^([^\s]*)[.]([^\s]*?)$/';
                          preg_match($pattern, $file, $matches);
                          //print_r($matches);

                          $file_name = $matches[1];
                          $extension = $matches[2];
                          $server['SCRIPT_NAME'] = '/'.$file_name.'.'.$extension;
                          // again this coule be rerwitten to check for a '.' at the top, instead of down here!!!
                          // lets see if everything else has failed - and then send it to index.php instead!
                          if (count($matches) < 1) {
                            $file_name = 'index';
                            $extension = 'php';
                            $server['SCRIPT_NAME'] = '/'.$file_name.'.'.$extension;
                            // redo the match from earlier!
                             $pattern = '/[\/]([^\s?]*)/';
                            preg_match($pattern, $file_string, $matches); 
                            $server['PATH_INFO'] = '/'.trim($matches[0], '/'); 
							$server['REQUEST_URI'] = $server['PATH_INFO'];
                          }
                        }
                    }
                }
                // lets do the cookies too
                foreach ($request as $header) {
                  $pattern = "/Cookie: (.*)/";
                  if(preg_match($pattern, $header, $matches)) {
                    $server['HTTP_COOKIE'] = $matches[1];
                  }
                }
                $file_name = urldecode($file_name);  

                $full_file_name = $webroot . $file_name . "." . $extension;
                // Get the mime types accepted.
                $pattern = '/Accept: ([^;]*)/';
                if(preg_match($pattern, $accept_string, $matches)) {
                    $accept = $matches[1];
                }

                // Get the query string.
                $pattern = '/[?]([^\s]*)/';
                if(preg_match($pattern, $file_string, $matches)) {
                    $get_string = $matches[1];
                    // Set the query string.
                    $server['QUERY_STRING'] = $get_string;
                }

             $server['HTTP_USER_AGENT'] = $headers_array['User-Agent'];
			 $server['HTTP_ACCEPT'] = $headers_array['Accept'];
			
            // lets do the server vars!
            if (isset($headers_array['X-Requested-With'])) $server['HTTP_X_REQUESTED_WITH'] = $headers_array['X-Requested-With'];
            $server_string = http_build_query($server); 
            // Return a 404 if the file was not found.
            if(!file_exists($full_file_name)) {
                $headers = array();
                $headers[] = "HTTP/1.1 404 NOT FOUND";
                $headers[] = "Date: $date";
                $headers[] = "Content-Type: " . $mime_type[$extension];

                $content = "ERROR 404: File $full_file_name Not Found. Web_root $webroot";
            } else if($full_file_name == 'scripts/download.php') {
                $content = get_content($full_file_name, urldecode($get_string), urldecode($post_info), $server_string);

                parse_str($get_string, $get);

                $headers = array();
                $headers[] = "HTTP/1.1 200 OK";
                $headers[] = "Date: $date";
                $headers[] = "Content-Length: " . (strlen($content));
                $headers[] = "Content-Type: " . $mime_type[$get['type']];

            } else {
                if($extension == 'php') {
	 			   
                    $raw_post = base64_encode($post_info);  

                    $content = get_content($full_file_name, urldecode($get_string), urldecode($post_info), urldecode($server_string), $raw_post);
                    $headers = array();
                    $headers[] = "HTTP/1.1 200 OK";
                    $headers[] = "Date: $date";
                    $headers[] = "Content-Length: " . (strlen($content)); 
                    $headers[] = "Content-Type: " . $mime_type[$extension];  
					$headers += $defaults;
                } else {
                      $content = file_get_contents($full_file_name);
                      $headers = array();
                      $headers[] = "HTTP/1.1 200 OK";
                      $headers[] = "Date: $date";
                      $headers[] = "Content-Length: " . (strlen($content));
                      $headers[] = "Content-Type: " . $mime_type[$extension];
                }
            }
				
			$code = current($headers); 
			$body = $content;  
			
			
 			$header = '';
			foreach ($headers as $k => $v) {
				$header .= $k.': '.$v."\r\n";
			}   
			
			
			 			
			foreach($request as $item)
			{
			   	if(strpos($item, 'multipart')) 
				{  

					$not_supported = not_supported('File Uploading', $headers_array['Referer']);  
	                // $body = $not_supported.$body;  
					$body = preg_replace('/<body>(.*)<\/body>/s', '<body>'. $not_supported.'$1 </body>' , $body);   
					break;
				}				
			}	
 
                           
            // // this is a hacky hack, but it needs rewriting completley
			$headers = implode("\r\n", $headers)."\r\n\r\n";

            socket_send($conn,  $headers, strlen($headers), 0); 

			socket_write($conn, $body, strlen($body));

	  		socket_close($conn);   
   
           	display_request($headers_array);


        }while(true);
        
  socket_close($socket);  
}

function get_content($file_name, $get_string, $post_string, $server_string, $raw_post) { 
    $get  = escapeshellarg(base64_encode('get=1&'  . $get_string));
    $post = escapeshellarg(base64_encode('post=1&' . $post_string));
    $server = escapeshellarg(base64_encode('server=1&' . $server_string));
    $raw = escapeshellarg('raw=1&' . $raw_post);

    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
       1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    );
    
    $php_loader_path =  realpath(dirname(__FILE__).'/loader.php');

    $php_cgi_path = 'php';
    $process = proc_open("$php_cgi_path $php_loader_path $file_name $get $post $server $raw", $descriptorspec, $pipes);
    request_log("$php_cgi_path $php_loader_path $file_name $get $post $server $raw");
    if (is_resource($process)) {

        $content = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // It is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $return_value = proc_close($process);
    }

    return $content;

}

?>