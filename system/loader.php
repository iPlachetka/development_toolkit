<?php
    ini_set('soap.wsdl_cache_ttl', 1);
    ini_set('soap.wsdl_cache_limit', 0);   

   	error_reporting(E_ERROR | E_PARSE);

    set_error_handler("error_logger");
    
    $filename = $argv[1];
    if(isset($argv[2])) {
        parse_str(base64_decode($argv[2]), $_GET);
        unset($_GET['get']);
    }
    if(isset($argv[3])) {
        parse_str(base64_decode($argv[3]), $_POST);
        unset($_POST['post']);
    }
    
    if(isset($argv[4])) {
        parse_str(base64_decode($argv[4]),$_SERVER);
        unset($_SERVER['server']);    
// print_r($_SERVER);
        foreach ($_SERVER as $key => $server_var) {
          //  being lazy
          $search = 'amp;';
          $replace = '';
          $_SERVER[str_replace($search,$replace,$key)] = str_replace("\r", '',$server_var);
          //unset($_SERVER[$key]);
        }
    }
    
    if(isset($argv[5])) {
        parse_str($argv[5], $raw);
        unset($raw['raw']);
        //print_r($raw); 
       // $GLOBALS['HTTP_RAW_POST_DATA'] = 
    }
    
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
      $cookie_array = explode('=', $cookie);
      $_COOKIE[trim($cookie_array[0])] = $cookie_array[1];//str_replace('\r', '', $cookie_array[1]);
    }
     
    /*
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0) Gecko/20100101 Firefox/4.0';
    $_SERVER['HTTP_KEEP_ALIVE'] = '115';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['SERVER_PORT'] = '4001';
    $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8';
    */
    //$_SERVER['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
    //$HTTP_RAW_POST_DATA = file_get_contents('php://input');       
	//print_r($argv[5]);
    $GLOBALS['HTTP_RAW_POST_DATA'] =  str_replace('raw=1&', '', $argv[5]);
    
    $GLOBALS['HTTP_RAW_POST_DATA'] = base64_decode($GLOBALS['HTTP_RAW_POST_DATA']); 
    
    //echo  $GLOBALS['HTTP_RAW_POST_DATA']; 
    //echo  $GLOBALS['HTTP_RAW_POST_DATA'];
    //$_SERVER['SCRIPT_NAME'] = 'frontend_dev.php';
    
    //$_SERVER['REQUEST_URI'] = $argv[4];
   // $_SERVER['PATH_INFO'] = $argv[4];
    //$_SERVER['SCRIPT_NAME'] = '/frontend_dev.php';
    //$_SERVER['PHP_SELF'] = 'fsdsa.php';
    //$_SERVER['HTTP_COOKIE'] = $argv[5];
    //print_r($_SERVER);
    require($filename);
    //print_r($_COOKIE);
?>