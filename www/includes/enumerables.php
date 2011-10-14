<? 
// Enumerable   

if( !function_exists('puts') )
{
	function puts($var) {  
		echo "<pre>";
		
	    if (is_array($var))
	        print(json_encode($var) . "\n");

	    else if (is_string($var) || is_numeric($var))
	        echo $var . "\n";
	    else if (is_bool($var))
	        echo ($var) ? "true\n" : "false\n";
	    else
	        var_dump($var); 
		
		echo "</pre>";
	}   
	
}  

if( !function_exists('p') )
{
	function p($var)
	{
		puts($var);
	}
} 

if( !function_exists('func') ) 
{
	function func($arg, $code)
	{
		return create_function($arg, $code);
	}
}  

if( !function_exists('block') ) 
{
	function block()
	{
		return func_get_args();
	}
} 


if( !function_exists('array_collect') )
{
    /**
     * map elements
     *
     * Returns a new array with the results of running function once for every element in enum. 
	 * @example array_collect(array(1,2,3,4), function($i) { return $i*$i; });       
 	 * @example array_collect(array('cat', 'dog', 'wombat'), function ($v) { return 'cat'; }); 
     * @param array
     *
     * @param function $lambda takes an element and returns an element
     * @return array
     */	
	function array_collect($arr, $lambda) {
	   	$lambda = process_anonymous_function($lambda);  
		
	    return array_map($lambda, $arr);
	} 
}

if( !function_exists('array_detect') )
{ 
    /**
     * find an element
     *
     * Passes each entry in enum to function. Returns the first for which function is not false.
     * If no object matches, calls $ifnone and returns its result when it is specified, or returns nil   
	 * @example array_find(range(1, 100), function ($i) { return $i%5==0 && $i%7==0; }); 
     * @param array
     *
     * @param function $lambda takes element, returns boolean
     * @param function $ifnone function to call if no elements are found
     * @return object|null returns null if nothing is found
     */
	
	function array_detect($arr,$lambda,$ifnone=null) {      
		  $lambda = process_anonymous_function($lambda);  
		
	      foreach ($arr as $value)
	          if (call_user_func($lambda, $value))
	              return $value;

	      if (is_callable($ifnone))
	          return call_user_func($ifnone);

	      return null;
	} 
} 

if( !function_exists('array_find') )
{  
    /**
     * alias for detect()
     *
     * @see detect()
     * @return object|null
     *
     */	
	function array_find($lambda, $arr,$ifnone=null) {
		return array_detect($lambda, $arr,$ifnone=null);
	} 
}

if( !function_exists('array_each_cons') )
{  
    /**
     * "sliding window"
     *
     * Iterates the given function for each array of consecutive <$n> elements. 
	 * @example array_each_cons(range(1, 10), 3, function ($a) { p($a); }); 
     *  
     * @param array
     * @param int $n
     * @param function $lambda takes an element 
	 * @return void  
     */
	
	function array_each_cons($arr,$n, $lambda) {     
		$lambda = process_anonymous_function($lambda);
		
		$size = count($arr);
	    $cons = array();
	    for ($i=0; $i<$size-$n; $i++) {
	        $inner = array();
	        for ($j=0; $j<$n; $j++)
	            $inner[$j] = $arr[$i+$j];

	        $cons[$i] = $inner;
	    }
	    array_walk($cons, $lambda);
	} 
}  

if( !function_exists('array_each_slice') )
{ 
    /**
     * "sliding slicer"
     *
     * Iterates the given function for each slice of <$n> elements. 
	 * @example array_each_slice(range(1, 10), 3, function ($a) { p($a); }); 
     *  
     * @param array
     * @param int $n
     * @param function $lambda takes an element   
	 * @return void  
     */
	
	function array_each_slice($arr, $n, $lambda) { 
		$lambda = process_anonymous_function($lambda);
 
		$size = count($arr);
	    $slices = array();
	    for ($i=0; $i<$size; $i+=$n) {
	        $slice = array_slice($arr, $i, $n);
	        $slices[$i] = $slice;
	    }
	    array_walk($slices, $lambda);
	}
} 

if( !function_exists('array_each_with_index') )
{ 
    /**
     *
     * Calls function with two arguments, the item and its index, for each item in enum. 
	 * @example 
	 * array_each_with_index(array('cat', 'dog', 'wombat'), function($item, $index){ $hash[$item] = $index; });
	 * print_r($hash);
     *  
     * @param array
     * @param function $lambda takes item and index
	 * @return void  
     */	
	function array_each_with_index($arr,$lambda) {  
		$lambda = process_anonymous_function($lambda);

	    foreach ($arr as $index => $item) {
	        call_user_func($lambda, $item, $index);
	    }
	}
}

if( !function_exists('array_each') )
{ 
	/**   
     * Iterates over an array.   
	 *  
     * @param array
     * @param function $lambda
	 * @example array_each(array('cat', 'dog', 'wombat'), function($item) { print_r($index); }); 
 	 * @return void  
	 */
	
	function array_each($arr, $lambda) {   
		$lambda = process_anonymous_function($lambda);

	    array_walk($arr, $lambda);
	} 
}  

if( !function_exists('array_find_all') )
{ 
   /**
     *
     * Returns an array containing all elements of enum for which function is not false    
	 * @example array_find_all(range(1, 10), function ($i) { return $i%3==0; })
     *
	 * @param array     
     * @param function $lambda, takes element and returns a boolean
     * @return array
     */	
	function array_find_all($arr, $lambda) {   
		$lambda = process_anonymous_function($lambda);

	    return array_values(array_filter($arr, $lambda));
	}
} 

if( !function_exists('array_select') )
{ 
    /**
     * find all elements
     *
     * alias for find_all() 
     *
	 * @param array     
	 * @param function $lambda takes and returns an element
     * @return array
     */
	
	function array_select($arr,$lambda) { 
	    return array_find_all($arr, $lambda);
	} 
} 
 
if( !function_exists('array_grep') )
{  
    /**
     * Filter using grep
     * 
     * Returns an array of every element in enum for which element matches the pattern.
     * If the optional function is supplied, each matching element is passed to it,
     * and the function's result is stored in the output array.
	 * @example array_grep(array('SEEK_SET', 'SEEK_CUR', 'SEEK_END'), '/SEEK/')  
	 * @example array_grep(array('SEEK_SET', 'SEEK_CUR', 'SEEK_END'), '/SEEK/', function ($v) { echo strlen($v) . ' '})
     * 
	 * @param array     
	 * @param string Regular Expression
	 * @param function $lambda takes and returns an element
     * @return array
     */	
	function array_grep($arr, $pattern, $lambda=null) {  
		$lambda = process_anonymous_function($lambda);

	    $match = preg_grep($pattern, $arr);
    
	    if (!is_callable($lambda))
	        return $match;
	    else
	        return array_map($lambda, $match);
	} 
} 
 
if( !function_exists('array_member') )
{ 
    /**
     *
     * Returns true if any member of enum equals obj. 
	 * @example array_member(array('SEEK_SET', 'SEEK_CUR', 'SEEK_END'), 'SEEK_NO_FURTHER')    
	 * @example array_member(array('SEEK_SET', 'SEEK_CUR', 'SEEK_END'), 'SEEK_SET')
     * 
	 * @param array
	 * @param mixed $obj the object to look for
     * @return bool true if found 
     */
	function array_member($arr, $obj) {
	    return in_array($obj, $arr);
	}
} 
 
if( !function_exists('array_inject') )
{
	/**
     * Also known as fold or reduce
     *
     * Combines the elements of enum by applying the function to an accumulator
     * value ($memo) and each element in turn. At each step, $memo is set to the
     * value returned by the function. The optional argument lets you supply an initial
     * value for $memo. Otherwise it uses the first element of the collection
     * as a the initial value (and skips that element while iterating).
     * 
	 * @param array     
     * @param function $lambda takes $memo and element, returns update to $memo
     * @param int $initial
     * @return int 
     */

	function array_inject($arr, $lambda, $initial=null) {  
		$lambda = process_anonymous_function($lambda);

	    if ($initial == null) {
	        $first = array_shift($arr);
	        $result = array_reduce($arr, $lambda, $first);
	        array_unshift($arr, $first);

	        return $result;
	    } else {
	        return array_reduce($arr, $lambda, $initial);
	    }
	} 
} 

if( !function_exists('array_partition') )
{ 
    /**
     *
     * partition into true and false arrays
     *
     * Returns two arrays, the first containing the elements of enum for which
     * the function evaluates to true, the second containing the rest.  
	 * @example array_partition(range(1, 6), function ($i) { return ($i&1) == 0; })
     * 
	 * @param array
     * @param function $lambda takes element, returns boolean
     * @return array array[0] is thet true part
     */
	
	function array_partition($arr,$lambda) { 
		$lambda = process_anonymous_function($lambda);

	    $true  = array();
	    $false = array();

	    foreach ($arr as $value) {
	        if (call_user_func($lambda, $value))
	            $true[] = $value;
	        else
	            $false[] = $value;
	    }

	    return array($true, $false);
	} 
} 

if( !function_exists('array_reject') )
{ 
    /**
     *
     * Returns an array for all elements of enum for which function is false .
	 * @example array_reject(range(1, 10), function ($i) { return $i%3 == 0; })
     *
     * @see array_find_all() 
     * @param array
     * @param function $lambda takes element, returns boolean
     * @return array
     */
	
	function array_reject($arr, $lambda) {    
		$lambda = process_anonymous_function($lambda);

	    $result = array();
	    foreach ($arr as $value) {
	        if (!call_user_func($lambda, $value))
	            $result[] = $value;
	    }
	    return $result;
	}
} 

if( !function_exists('array_sort') )
{ 
    /**
     *
     * Returns an array containing the items in enum sorted, either according to
     *  their comparison method, or by using the results of the supplied function.
     *
     * The function should return -1, 0, or +1 depending on the comparison between a and b  
	 * @example array_sort(array(4, 6, 1, 3, 2, 5), function ($a, $b) {if ($a==$b) { return 0; } return ($a > $b) ? -1 : 1;})
	 * @example array_sort(array(4, 6, 1, 3, 2, 5))
     *
     * @param array
     * @param function $lambda takes objects $a and $b, returns -1, 0 or +1
     * @return array
     */	
	function array_sort($arr,$lambda=null) {  
		$lambda = process_anonymous_function($lambda);

	    $sort = $arr;    
	
	    if (is_callable($lambda))
	        usort($sort, $lambda);
	    else
	        sort($sort);

	    return $sort;
	} 
} 

if( !function_exists('array_max') )
{  
	/**
     *
     * Returns the object in enum with the maximum value.
     *
     * If no function is supplied, uses default comparison method. 
	 * @example array_max(array(4, 6, 1, 3, 2, 5), function ($a, $b) {if ($a==$b) { return 0; } return ($a > $b) ? -1 : 1;})
	 * @example array_max(array(4, 6, 1, 3, 2, 5))
     *
     * @param array
     * @param function $lambda takes objects $a and $b, returns -1, 0 or +1
     * @return array
     */

	function array_max($arr,$lambda=null) {  
		$lambda = process_anonymous_function($lambda);

	    if (!is_callable($lambda))
	        return max($arr);

	    $sorted = array_sort($arr, $lambda);
	    return end($sorted);
	} 
} 

if( !function_exists('array_min') )
{  
    /**
     *
     * Returns the object in enum with the minimum value.
     *
     * If no function is supplied, uses default comparison method. 
	 * @example array_min(array(4, 6, 1, 3, 2, 5), function ($a, $b) {if ($a==$b) { return 0; } return ($a > $b) ? -1 : 1;})
	 * @example array_min(array(4, 6, 1, 3, 2, 5))
     *
     * @param array
     * @param function $lambda takes objects $a and $b, returns -1, 0 or +1
     * @return array
     */
	
	function array_min($arr,$lambda=null) { 
		$lambda = process_anonymous_function($lambda);

	    if (!is_callable($lambda))
	        return min($arr);

	    $sorted = array_sort($arr, $lambda);
	    return current($sorted);
	} 
} 

if( !function_exists('array_sort_by') )
{ 
    /**
     *
     * Sorts enum using a set of keys generated by mapping the values in enum through the given function.
	 * @example array_sort_by(array('fig', 'pear', 'apple'), function ($word) { return strlen($word); })
	 *
     * @param array
     * @param function $lambda takes and returns element  
     * @return array
     */
	
	function array_sort_by($arr,$lambda) {  
		$lambda = process_anonymous_function($lambda);

	    foreach ($arr as $value) {
	        $sort[$value] = call_user_func($lambda, $value);
	    }
	    asort($sort);
	    return array_values(array_flip($sort));
	} 
} 

if( !function_exists('array_zip') )
{
   /**
    * Ruby's handy zip()
    *
    * Converts any arguments to arrays, then merges elements of enum with
    * corresponding elements from each argument. This generates a sequence of
    * $size n-element arrays, where n is one more that the count of arguments.
    *  If the size of any argument is less than $size, null values are supplied.
	*
	* @example array_zip(array(4,5,6), array(7,8,9,3));
    * @param array $arr1, array $arr2...
    * @return array
    */

	function array_zip() {   
		$lambda = process_anonymous_function($lambda);

	    $args = func_get_args(); 
		$arr =  array_shift($args);
	    array_unshift($args, $arr);

	    $max = max(array_map('count', $args));
	    $zipped = array();

	    for ($i=0; $i<$max; $i++) {
	        for ($j=0; $j<count($args); $j++) {
	            $val = (isset($args[$j][$i])) ? $args[$j][$i] : null;
	            $zipped[$i][$j] = $val;
	        }
	    }

	    //if (!is_callable($zipped))
	        return $zipped;
	    //else
	    //    array_walk($lambda, $zipped);
	}
}

if( !function_exists('array_all') )
{
	/**
	 * all in collection?
	 *
	 * Passes each element of the collection to the given function. The method
	 * returns true if the function never returns false or null.
	 * 
	 * If the function is not given, an implicit
	 * function ($v) { return ($v !== null && $v !== false) is added<br />
	 * (that is all() will return true only if none of the collection members are false or null.) 
	 * @example array_all(array('ant', 'bear', 'cat'), function($word) { return strlen($word)>=3; }); // 
     * @example array_all(array(null, true, 99)); 
	 *
	 * @param $arr: The array to work with.
	 * @param function $lambda takes an element, returns a bool
	 * @return boolean
	 */	
	function array_all($arr, $lambda = null )
	{     
		$lambda = process_anonymous_function($lambda);

        if (!is_callable($lambda)) {
            foreach ($arr as $value)
                if ($value === false || $value === null)
                    return false;
        } else {
            foreach ($arr as $value)
                if (!call_user_func($lambda, $value))
                    return false;
        }
        return true;
	}
}

if( !function_exists('array_any') )
{
 	
 /**
  * any in collection?
  *
  * Passes each element of the collection to the given function. The method
  * returns true if the function ever returns a value other than false or null.
  *
  * If the function is not given, an implicit
  * function ($v) { return ($v !== null && $v !== false) is added.<br />
  * (that is any() will return true only if one of the collection members is not false or null.)   
  * @example array_any(array('ant', 'bear', 'cat'), function($word) { return strlen($word)>=3; }); // true    
  * @example array_any(array(null, true, 99)); 
  *  
  * @param $arr: The array to work with.
  * @param function $lambda takes element and returns a boolean
  * @return boolean
  */    
	function array_any($arr, $lambda = null )
	{  
		$lambda = process_anonymous_function($lambda);

        // these differ from PHP's "falsy" values
        if (!is_callable($lambda)) {
            foreach ($arr as $value)
                if ($value !== null && $value !== false)
                    return true;
        } else {
            foreach ($arr as $value)
                if (call_user_func($lambda, $value))
                    return true;
        }
        return false;
	}
}

if( !function_exists('array_flatten') )
{
	/**
	 * Returns array that is a one-dimensional flattening of the passed array. (Recursively)
	 *
	 * @param $arr: The array to work with
  	 * @return array
	 */
	function array_flatten($arr) {
	    $flattened = array();
	    if (is_array($arr)) {
	        foreach ($arr as $value) {
	            $flattened = array_merge($flattened, array_flatten($value));
	        }
	    } else {
	        $flattened[] = $arr;
	    }

	    return $flattened;
	} 
}


if( !function_exists('array_insert') )
{
	/**
	 * Inserts supplied value(s) on the specified index into the array  
	 * @param $arr: The array to work with.
	 * @param $index: The index where to add the value.
	 * @param $value: The value to add.
  	 * @return array
	 */
	function array_insert($arr, $index, $values )
	{
		if( func_num_args() > 3 )
			$values = array_slice( func_get_args(), 2 );
		array_splice($arr, ($index<0 ? $index+1 : $index), 0, $values );
		return $arr;
	}
} 

// Pass anonymous function (lambda) as parameter prior to PHP 5.3

function process_anonymous_function($function = NULL) {
  // If parameter passed is a string and a function by that name exists (as
  // should exist with create_function), call the function  

 if (is_string($function) && function_exists($function)) {
    return $function;
  } 
  
 if(is_array($function))
 {
	list($arguments, $code) = $function; 
	if(end(str_split($code)) != ';')
	{
	  $code = $code . ";";  
	}
	return create_function($arguments, $code);
 } 
 
 return $function;

}
  

/** Examples

	<pre>
	<?php

	include "enum.php";

	echo "<b>any() and all()</b>\n";
	$list = new enum(array('ant', 'bear', 'cat'));

	*******************************************
	*  Passing functions - "like" Ruby blocks *
	*******************************************

	// function arguments can be passed as anoynoums functions (PHP >= 5.3.0)
	p($list->any(function ($word) {return strlen($word) >= 3;}));

	// these can also be assigned to a variable for re-use
	$longerThan3 = function ($word) {return strlen($word) >= 4;};
	p($list->any($longerThan3));

	// or pass the function name as a string (older versions of PHP)
	function longerThan2 ($word) {
	    return strlen($word) >= 3;
	}
	p($list->all('longerThan2'));

	p($list->all($longerThan3));

	$list = new enum(array(null, true, 99));
	p($list->any());
	p($list->all());

	echo "\n<b>each_with_index()</b>\n";
	$list = new enum(array('cat', 'dog', 'wombat'));

	*******************************************
	* Inheriting variables from parent scope  *
	*******************************************

	// Using references you can modify variables in the parent scope
	$hash = array();
	// note the use (&$hash) part
	$list->each_with_index(function($item, $index) use (&$hash) { $hash[$item] = $index; });
	p($hash);

	echo "\n<b>collect() and map()</b>\n";

	$multiply = function($i) { return $i*$i; };
	$list = new enum(array(1,2,3,4));
	p($list->collect($multiply));
	p($list->map(function ($v) { return 'cat'; }));

	echo "\n<b>detect() and find()</b>";
	$list = new enum(range(1, 10));
	p($list->detect(function ($i) { return $i%5==0 && $i%7==0; }));
	$list = new enum(range(1, 100));
	p($list->find(function ($i) { return $i%5==0 && $i%7==0; }));

	echo "\n<b>each_cons()</b>\n";
	$list = new enum(range(1, 10));
	$list->each_cons(3, function ($a) { p($a); });

	echo "\n<b>each_slice()</b>\n";
	$list->each_slice(3, function ($a) { p($a); });

	echo "\n<b>find_all()</b>\n";
	$list = new enum(range(1, 10));
	p($list->find_all(function ($i) { return $i%3==0; }));

	echo "\n<b>grep()</b>\n";
	$io_cons = array('SEEK_SET', 'SEEK_CUR', 'SEEK_END');
	$list = new enum($io_cons);
	p($list->grep('/SEEK/'));
	$list->grep('/SEEK/', function ($v) { echo strlen($v) . ' '; });

	echo "\n\n<b>member()</b>\n";
	p($list->member('SEEK_SET'));
	p($list->member('SEEK_NO_FURTHER'));

	echo "\n<b>partition()</b>\n";
	$list = new enum(range(1, 6));
	p($list->partition(function ($i) { return ($i&1) == 0; }));

	echo "\n<b>reject()</b>\n";
	$list = new enum(range(1, 10));
	p($list->reject(function ($i) { return $i%3 == 0; }));

	$reverse =
	    function ($a, $b) {
	            if ($a==$b) return 0;
	            return ($a > $b) ? -1 : 1;
	    };

	echo "\n<b>sort()</b>\n";
	$list = new enum(array(4, 6, 1, 3, 2, 5));
	p($list->sort());
	p($list->sort($reverse));

	echo "\n<b>min()</b>\n";
	p($list->max());
	p($list->max($reverse));

	echo "\n<b>max()</b>\n";
	p($list->min());
	p($list->min($reverse));

	echo "\n<b>sort_by()</b>\n";
	$list = new enum(array('fig', 'pear', 'apple'));
	p($list->sort_by(function ($word) { return strlen($word); }));

	echo "\n<b>zip()</b>\n";
	$list = new enum(array(1,2,3));
	p($list->zip(array(4,5,6), array(7,8,9,3)));

	$list = new enum(array(1,2,3));
	p($list->zip());

	$list = new enum(explode("\n", "cat\ndog"));
	p($list->zip(array(1)));

	?>
	</pre>

**/