<?php

class gr {

   //configuration options
   public $userAgent = 'tuts+rss+bot';
   public $proxy = 0;
   public $proxyUrl = '';
   public $grEmail = '';
   public $grPasswd = '';

   //object to store logged in user information
   public $userInfo = '';
   
   //base urls for api access
   protected $_urlBase = 'https://www.google.com';
   protected $_urlApi = 'http://www.google.com/reader/api/0';
   protected $_urlAuth = 'https://www.google.com/accounts/ClientLogin';
   protected $_urlToken = 'https://www.google.com/reader/api/0/token';
   protected $_urlUserInfo = 'https://www.google.com/reader/api/0/user-info';
   protected $_urlTag = 'https://www.google.com/reader/api/0/tag';
   protected $_urlSubscription = 'https://www.google.com/reader/api/0/subscription';
   protected $_urlStream = 'https://www.google.com/reader/api/0/stream';
   protected $_urlFriend = 'https://www.google.com/reader/api/0/friend';

   // variables for authentication
   protected $auth = '';
   protected $token = '';
   
   function login() {
      // do a quick check to see if a token is already available and below timeout
      // not technically neccessary but helps keep network transactions down.
      if (isset($_SESSION['refresh'])) {
         if ($_SESSION['refresh'] >= time()) { $refresh = 0; } else { $refresh = 1; }
      } else {
         $refresh = 1;
      }
      
      if ($refresh == 0) {
         //pull the auth and userinfo from the session
         $this->auth = $_SESSION['AUTH'];
         $this->token = $_SESSION['TOKEN'];
         $this->userInfo = $_SESSION['userInfo'];
      } else {
         // setup the login post data.  case-sensitive
         $data = '&Email='.$this->grEmail.'&Passwd='.$this->grPasswd.'&service=reader&source='.$this->userAgent.'&continue=http://www.google.com';
         
         // use the post_anon_url to authenticate against googles authentication service
         $result = $this->post_anon_url($this->_urlAuth,$data);
         
         // Get the Auth token from the results
         preg_match('/Auth=(\S*)/', $result, $match);
         $this->auth = $match[1];

         // grab the write token for use in editting the content in google reader
         $this->token = $this->get_url($this->_urlToken);
         
         // get user information for use later
         $this->userInfo = json_decode($this->get_url($this->_urlUserInfo));
         
         // save it all to session variables so we don't have to auth again.
         $_SESSION['AUTH'] = $this->auth;
         $_SESSION['TOKEN'] = $this->token;
         $_SESSION['userInfo'] = $this->userInfo;
         // set the timeout to reauth to 5 minutes
         $_SESSION['refresh'] = time() + 300;
      }
   }

   function get_subscriptions() {
      $result = $this->get_url($this->_urlSubscription.'/list?output=json');
      return json_decode($result);
      //return json_decode($result);
   }
   
   function get_tags() {
      $result = $this->get_url($this->_urlTag.'/list?output=json');
      return json_decode($result);
   }  

   function get_starred($amount = 20) {    
	  $ck = time() * 1000;      
      $result = $this->get_url($this->_urlStream.'/contents/user/-/state/com.google/starred?n='.$amount.'&ck='.$ck.'&client='.$this->userAgent);
      return json_decode($result,TRUE);
   } 

   function get_liked($amount = 20) {    
	  $ck = time() * 1000;      
      $result = $this->get_url($this->_urlStream.'/contents/user/-/state/com.google/like?n='.$amount.'&ck='.$ck.'&client='.$this->userAgent);
      return json_decode($result,TRUE);
   }
   
   function get_friends() {
      $result = $this->get_url($this->_urlFriend.'/list?output=json');
      return json_decode($result);
   }

   function get_stream_items(
         $stream = '',
         $xt_a = array('user/-/state/com.google/read'),
         $daysago = 3, 
         $n = 20, 
         $magic = True) {
      // conver spaces to %20 becuase google doesn't like spaces.
      $stream = str_replace(' ','%20',$stream);
      $ot = time() - ($daysago * 86400);
      $ck = time() * 1000;
      if ($magic == True) { $r = 'a'; } else { $r = 'n'; }  // sort by magic $r = 'o'
      $xt = '';
      foreach($xt_a as $key=>$value) { $xt .= '&xt='.$value; } 
      $url = $this->_urlStream.'/contents/'.$stream.'?ot='.$ot.'&r='.$r.$xt.'&n='.$n.'&ck='.$ck.'&client='.$this->userAgent;
      $result = $this->get_url($url);
      //echo $url;
      return json_decode($result);
   }
   
   function set_article_read($id,$stream) {
      $url = $this->_urlApi . '/edit-tag?pos=0&client=' . $this->userAgent;
      $data = 'a=user/-/state/com.google/read&async=true&s='.$stream.'&i='.$id.'&T='.$this->token;
      return $this->post_url($url,$data);
   }

   function set_article_starred($id,$stream) {
      $url = $this->_urlApi . '/edit-tag?pos=0&client=' . $this->userAgent;
      $data = 'a=user/-/state/com.google/starred&async=true&s='.$stream.'&i='.$id.'&T='.$this->token;
      return $this->post_url($url,$data);
   }

   function set_article_broadcast($id,$stream) {
      $url = $this->_urlApi . '/edit-tag?pos=0&client=' . $this->userAgent;
      $data = 'a=user/-/state/com.google/broadcast&async=true&s='.$stream.'&i='.$id.'&T='.$this->token;
      return $this->post_url($url,$data);
   }

   function set_article_review($id,$stream) {
      $url = $this->_urlApi . '/edit-tag?pos=0&client=' . $this->userAgent;
      $data = 'a=user/'.$this->userInfo->userId.'/label/Review&async=true&s='.$stream.'&i='.$id.'&T='.$this->token;
      return $this->post_url($url,$data);
   }


   function get_url($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);      
      if ($this->proxy == 1) { curl_setopt($ch, CURLOPT_PROXY, $this->proxyUrl); }
      curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Authorization: GoogleLogin auth=' . $this->auth));
      curl_setopt ($ch, CURLOPT_USERAGENT, $this->userAgent);
      curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return $result;
   }

   function get_anon_url($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);      
      if ($this->proxy == 1) { curl_setopt($ch, CURLOPT_PROXY, $this->proxyUrl); }
      curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt ($ch, CURLOPT_USERAGENT, $this->userAgent);
      curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
   }
   
   function post_url($url,$data) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);      
      curl_setopt ($ch, CURLOPT_POST, true);
      if ($this->proxy == 1) { curl_setopt($ch, CURLOPT_PROXY, $this->proxyUrl); }
      curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Authorization: GoogleLogin auth=' . $this->auth));
      curl_setopt ($ch, CURLOPT_USERAGENT, $this->userAgent);
      curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
   }
   
   function post_anon_url($url,$data) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);      
      curl_setopt ($ch, CURLOPT_POST, true);
      if ($this->proxy == 1) { curl_setopt($ch, CURLOPT_PROXY, $this->proxyUrl); }
      curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt ($ch, CURLOPT_USERAGENT, $this->userAgent);
      curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
   }
   
   
}  
 

$gr = new gr;
$gr->proxy = 0;

?>