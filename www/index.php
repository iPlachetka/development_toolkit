<?php               
error_reporting(E_ALL);		
ini_set('display_errors', '1');      
define('BASE_PATH', dirname(__FILE__).'/'); 

foreach(glob(BASE_PATH.'/includes/*.php') as $include_file)  
{  
    include_once($include_file); 
}    

// $hashtag = 'pursue';
// $badger_logo = "<img src='/images/badger_logo_small_size.png' height='100' style='vertical-align:middle;' alt='$hashtag' title='$hashtag'/>" ;
// $big_badger_logo = "<img src='/images/badger_logo_small_size.png' style='vertical-align:middle;' alt='$hashtag' title='$hashtag'/>" ;    

$storage->directory =  BASE_PATH.'storage_bin';   

before('*', function(){  
		if(condition('page', params())){
		   render('template', params());  
		}   
});  
// 
// before(function(){
// });  
// 
// before(function(){
// 		echo "Maurice! ";   
// });   
// 
// after(function(){
// 		echo "Good";
// }); 
// 
// after(function(){
// 		echo " Bye";
// });  

configure(function(){ 
	settings('template_engine', new Mustache); 
	settings('application_name', 'My App'); 
	
	settings('hashtag', 'pursue');
	settings('badger_logo', "<img src='/images/badger_logo_small_size.png' height='100' style='vertical-align:middle;' alt='settings('hashtag')' title='settings('hashtag')'/>" );
	settings('big_badger_logo', "<img src='/images/badger_logo_small_size.png' style='vertical-align:middle;' alt='settings('hashtag')' title='settings('hashtag')'/>");
	    
	settings('views', dirname(__FILE__) . '/templates');   
	
	settings('extension', function($file =  __FILE__){
			return '.'.end(explode('.',$file));
		});  
  

	condition('page', function($page){  
	   return stristr($page['request'], '404') !== false;
	}); 
	
	condition('pass', function($name){  
	   return ($name == 'Frank') ? true : false;
	});  
	
	condition('is_number', function($name){  
	   return (is_numeric($name)) ? true : false;
	});

	condition('user_agent_contains', function($agent){  
	   return stristr($_SERVER['HTTP_USER_AGENT'], $agent) !== false;
	}); 
	
});  


get('/', function(){    	  
    if(condition('user_agent_contains', 'safari'))
	{
	 	class Chris extends Mustache {
		    public $name = "Chris";
		    public $value = 10000;

		    public function taxed_value() {
		        return $this->value - ($this->value * 0.4);
		    }

		    public $in_ca = true;
		} 
		
		$chris =  new Chris();  
		$page = render('mustach_template', $chris, TRUE); 
		generic_markup('This is a test', $page);    
	}  
	
});

template('mustach_template', function(){    
	return render('/views/pages/test', array(), TRUE);
});

// ajax('/albums' , function(){  
//     header('Content-type: application/json');
//     render('/views/pages/albums.json');   
// });

 

// get('/pursue', function(){
// 	extract(get_user_defined_vars());
// 
// 	$json = file_get_contents("http://search.twitter.com/search.json?lang=all&rpp=100&q=%23" . settings('hashtag'));
// 	$json = json_decode($json,TRUE); 	
// 	
// 	$item['text'] =  settings('badger_logo');
// 	$item['page_name'] = 'Pursued';   
// 	
// 	if(count($json['results']))
// 	{
// 		$item = random_element($json['results']); 
// 		$item['text'] = preg_replace('/#'. settings('hashtag') .'/i', settings('badger_logo'), twitterify($item['text']));  
// 		$user_data = file_get_contents("http://api.twitter.com/1/users/show.json?screen_name=".$item['from_user']."&include_entities=true"); 
// 		$user_data = json_decode($user_data,TRUE);  
// 		$item = array_merge($user_data, $item); 
// 		
// 		$item['page_name'] = 'Pursue by:';
// 	}  
// 	
// 	$item['count'] = count($json['results']);   
// 		
// 	$item['content_for_layout'] = render('/views/pages/tweet', $item, TRUE);  
// 		  
// 	render('views/templates/main', $item);    
// });

// get('/reader' , function(){      
// 	  extract(get_user_defined_vars());  
// 	$gr->grEmail = 'mauricecalhoun@gmail.com';
// 	$gr->grPasswd = '2220straussstreet';
// 
//      $gr->login();
// 	 $results = $gr->get_liked();  
// 	 $starred = $results['items'];  
//  
// 	 $items = array();      
//   
//    	 foreach($starred as $key => $item)
// 	 {
// 	    if(array_key_exists('summary', $item))
// 		{
// 		 	$item['content'] = $item['summary'];  
// 		}   
// 	 	$items[$key] = render('/views/pages/feeds', $item, TRUE);      
// 	 } 
// 	    
// 	  
// 		
// 	  $data['content_for_layout'] = implode('<hr/>', $items);
// 	  render('views/templates/main', $data);  
// 	
// });

// get('/please', function(){  
// 	echo settings('extension', array('/favicon.ico')); 
// 
// 			render('views/pages/form', array());     
// 			// echo "<pre>";
// 			// print_r(params());
// 			// echo "</pre>"; 
// 			// echo "<hr/>";
// 			// 
// 			// 		 call_route('/test/Mr/Maurice/Calhoun/Sr');    
// 			// 
// 			// echo "<hr/>";
// 			// echo "<pre>";
// 			// print_r(params());
// 			// echo "</pre>";  
// });    

// get('/guess/:who', function($name){
// 	pass(condition('pass', $name));  
// 	echo 'You got me! ' . $name;
// }); 

// get('/guess/([\w-]+)', function($name){   
// 	pass(condition('is_number', $name));
// 	echo 'You missed! ' . $name;   
// }); 
    
// get('/guess/*', function($name){    	
// 	  echo  'Hey! '. ucwords($name); 
// });
       

// post('/test/:name/([\w-]+)/([\w-]+)/*', function($name,$middle,$last,$suffix){
//  	extract(get_user_defined_vars()); 
// 		echo "<pre>";
// 		print_r($name); 
// 		echo " ";
// 		print_r($last);   
// 		echo " ";
// 		print_r($suffix); 
// 		echo "</pre>";  
// 		
// 		//raise_error(403); 
// 				
// 		// call_route('GET', '/reader');
// 		// 
// 		echo "<pre>";
// 		print_r(params());
// 		echo "</pre>";  
// });


template('template', function($locals){    
	// echo "<pre>";
	// print_r($locals);
	// echo "</pre>"; 
});  


// error('/404', function($params){    
// 	extract(get_user_defined_vars()); 
// 
// 	$item['site_image'] = settings('big_badger_logo');     
// 	$item['request'] = params('attempted_request');  
// 
// 	$item['page_name'] = 'Page cannot be found!!!';
// 	
// 	$item['content_for_layout'] = render('views/pages/404', $item, TRUE); 
// 	
// 	render('views/templates/main', $item);		
// });  

// error('403', function($params){    
// 	extract(get_user_defined_vars()); 
// 
// 	$item['site_image'] = settings('big_badger_logo');      
// 	$item['request'] = params('attempted_request');  
// 	
// 	$item['page_name'] = 'Page cannot be found!!!';
// 	
// 	$item['content_for_layout'] = render('views/pages/404', $item, TRUE); 
// 	
// 	render('views/templates/main', $item);		
// });


// not_found(function($params){    
// 	extract(get_user_defined_vars()); 
// 
// 	$item['site_image'] = settings('big_badger_logo');     
// 	$item['request'] = params('attempted_request');  
// 
// 	$item['page_name'] = 'Page cannot be found!!!';
// 	
// 	$item['content_for_layout'] = render('views/pages/404', $item, TRUE); 
// 	
// 	render('views/templates/main', $item);		
// });   