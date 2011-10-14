<?

if ( ! function_exists('debug'))
{
    function debug($data)
    {
		echo "<pre>";
		print_r($data);
		echo "</pre>";
    }
} 

if ( ! function_exists('random_element'))
{
    function random_element($array)
    {
        if ( ! is_array($array))
        {
            return $array;
        }
        return $array[array_rand($array)];
    }
}

if ( ! function_exists('first'))
{
	function first($array = array())
	{
		return current($array);
	} 
}

if ( ! function_exists('last'))
{
	function last($array = array())
	{
		return end($array);
	} 
} 

if ( ! function_exists('twitter_name'))
{
	function twitter_name($user_name = NULL)
	{
		if(is_null($user_name) || empty($user_name))
		{
			return;
		}
		
		return '( @'. $user_name .' )';
	} 
}

 
if ( ! function_exists('plural'))
{
	function plural($num) 
	{
		if ($num != 1)
		{
			return "s";  
		}
	} 
}
 
if ( ! function_exists('get_relative_time'))
{
	function get_relative_time($date) 
	{
	    if(!is_numeric($date))
		{
		  $date = strtotime($date);   
		}
		
		$difference = time() - $date;
		
		if ($difference < 60)
		{
			return $difference . " second" . plural($difference) . " ago"; 
		}
		
		$difference = round($difference / 60);
		
		if ($difference < 60)  
		{
			return $difference . " minute" . plural($difference) . " ago"; 
		}
		
		$difference = round($difference / 60);
		
		if ($difference < 24)
		{
			return $difference . " hour" . plural($difference) . " ago"; 
		}
		
		$difference = round($difference / 24);
		
		if ($difference < 7)
		{
			return $difference . " day" . plural($difference) . " ago";  
		}
		
		$difference = round($difference / 7); 
		
		if ($difference < 4)
		{
			return $difference . " week" . plural($difference) . " ago";
		}
		return "on " . date("F j, Y", strtotime($date));
	} 
}    

if ( ! function_exists('twitterify'))  
{    
	function twitterify($string) 
	{
	  $string = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $string);
	  $string = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $string);
	  $string = preg_replace("/@(\w+)/", "<a href=\"http://twitter.com/#!/\\1\" target=\"_blank\">@\\1</a>", $string);
	  $string = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $string);
	  return $string;
	}
} 