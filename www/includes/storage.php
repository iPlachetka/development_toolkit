<?php 

define( 'DS', '/' ); 
 
/**
 * Storage
 *
 * Data storage done the my way!
 *
 * @package 	Storage
 * @author 		Maurice Calhoun <http://mauricecalhoun.com>
 * @version		1.0.0
 * @copyright 	 (c)2011 Maurice Calhoun
 */

class Storage
{
    /* --------------------------------------------------------------
     * VARIABLES
     * ------------------------------------------------------------ */
    
    public $data         = array( );
    public $results      = array( );
    public $debugging    = TRUE;
    public $raw_data    = '';
    public $handle      = FALSE;
    public $directory   = 'storage_bin';
    public $encrypt_key = 'abracadabra'; 

 

	public static function instance()
	{
		static $instance = null;
		
		if ($instance === null)
		{
			$instance = new Storage;
		}
		
		return $instance;
	}   
    
    /* --------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------ */
    
    public function __construct( $directory = NULL, $encrypt_key = NULL)
    {
        if( !is_null( $directory ) )
		{
			$this->directory = $directory;
		} 
		
        if( !is_null( $encrypt_key ) )
		{
			$this->encrypt_key = $encrypt_key;
		}		  
		
		$this->load_all();
    }
    
    /* --------------------------------------------------------------
     * STORAGE API
     * ------------------------------------------------------------ */
    public function get_all( )
    {
        return $this->data;
    }
    
    public function get( $key )
    {
        return ( isset( $this->data[ $key ] ) ) ? $this->data[ $key ] : FALSE;
    }
    
    public function exists( $key )
    {
        return ( isset( $this->data[ $key ] ) );
    }
    
    public function clear_all( )
    {
        $this->data = array( );
    }
    
    public function reload( )
    {
        $this->clear_all();
        $this->load_all();
    }
    
    /* --------------------------------------------------------------
     * FILE I/O
     * ------------------------------------------------------------ */
    
    public function load( $file, $directory = NULL )
    {
        if ( is_null( $directory ) )
        {
            $directory = $this->directory;
        }
        
        $this->file          = $directory . DS . $file . ".db";
        $this->raw_data      = $this->decrypt( $this->read_file( $this->file ) );
        $this->data[ $file ] = ( $this->raw_data ) ? unserialize( $this->raw_data ) : array( );     
    }
    
    public function load_all( $directory = NULL )
    {
        if ( is_null( $directory ) )
        {
            $directory = $this->directory;
        }
        
        foreach ( glob( $directory . DS . "*.db" ) as $filename )
        {
            $db_name = basename( $filename, '.' . pathinfo( $filename, PATHINFO_EXTENSION ) );
            
            $this->file     = $filename;
            $this->raw_data = $this->decrypt( $this->read_file( $this->file ) );
            
            $this->data[ $db_name ] = ( $this->raw_data ) ? unserialize( $this->raw_data ) : array( );
        }
        
    }
    
    private function commit( $table, $data, $directory = NULL )
    {
        if ( is_null( $directory ) )
        {
            $directory = $this->directory;
        }
        
        $file           = $directory . DS . $table . ".db";
        $this->raw_data = $this->encrypt( serialize( $data ) );
        
        $this->write_file( $file, $this->raw_data );
        
        $this->reload();
    }
    
    private function remove( $table, $directory = NULL )
    {
        if ( is_null( $directory ) )
        {
            $directory = $this->directory;
        }
        
        unlink( $directory . DS . $table . ".db" );
        $this->reload();
    }
    
    private function read_file( $file )
    {
        if ( !file_exists( $file ) )
        {
            return FALSE;
        }
        
        if ( function_exists( 'file_get_contents' ) )
        {
            return file_get_contents( $file );
        }
        
        if ( !$fp = @fopen( $file, 'rb' ) )
        {
            return FALSE;
        }
        
        flock( $fp, LOCK_SH );
        
        $data = '';
        if ( filesize( $file ) > 0 )
        {
            $data =& fread( $fp, filesize( $file ) );
        }
        
        flock( $fp, LOCK_UN );
        fclose( $fp );
        
        return $data;
    }
    
    private function write_file( $path, $data, $mode = 'wb' )
    {
        if ( !$fp = @fopen( $path, $mode ) )
        {
            return FALSE;
        }
        
        flock( $fp, LOCK_EX );
        fwrite( $fp, $data );
        flock( $fp, LOCK_UN );
        fclose( $fp );
        
        return TRUE;
    }
    
    /* --------------------------------------------------------------
     * CRUD - CREATE, READ, UPDATE, DELETE
     * ------------------------------------------------------------ */
    public function create( $table, $data = NULL )
    {
        $this->load( $table );
        $existing_table_data = $this->get( $table );
        if ( !is_null( $data ) )
        {
            $columns     = $this->table_columns( $table );
            $new_columns = $columns;
            if ( is_array( $columns ) )
            {
                foreach ( $data as $key => $value )
                {
                    if ( !in_array( $key, $columns ) )
                    {
                        $new_columns[ ] = $key;
                    }
                }
            }
            
            if ( !$this->is_array_equal( $columns, $new_columns ) )
            {
                $existing_table_data = $this->remove_all_empty( $table, $existing_table_data );
                $data                = array_merge( $existing_table_data, array( $data ) );
                $data                = array_merge( array(  $this->create_blank_columns_from_list( $new_columns ) ), $data );
            }
            else
            {
                $data = array_merge( $existing_table_data, array( $data ) );
            }
            
            $this->commit( $table, $data );
        }
    }
    
    public function read( $table, $conditions = NULL, $order_by = NULL, $limit = NULL )
    {
        $this->load( $table );
        $existing_table_data = $this->get( $table );
        if ( is_null( $conditions ) )
        {
            $process_results = $existing_table_data;
        }
        else
        {
            $process_results = $this->process( $existing_table_data, $conditions );
        }
        
        $results = $this->remove_all_empty( $table, $process_results );
        
        if ( !is_null( $order_by ) )
        {
            $results = $this->order_by( $results, $order_by[ 'field' ], $order_by[ 'direction' ] );
        }
        
        if ( !is_null( $limit ) )
        {
            $results = $this->splicer( $results, 0, $limit );
        }
        
        return $results;
    }
    
    
    public function update( $table, $data, $conditions = NULL )
    {
        $this->load( $table );
        $existing_table_data = $this->get( $table );
        
        $columns     = $this->table_columns( $table );
        $new_columns = $columns;
        foreach ( $data as $key => $value )
        {
            if ( !in_array( $key, $columns ) )
            {
                $new_columns[ ] = $key;
            }
        }
        
        if ( is_null( $conditions ) )
        {
            $process_results = $existing_table_data;
        }
        else
        {
            $process_results = $this->process( $existing_table_data, $conditions );
        }
        
        $process_results = $this->remove_all_empty( $table, $process_results );
        
        foreach ( $process_results as $key => $value )
        {
            $existing_table_data[ $key ] = array_merge( $existing_table_data[ $key ], $data );
        }
        
        if ( !$this->is_array_equal( $columns, $new_columns ) )
        {
            $existing_table_data = array_merge( array($this->create_blank_columns_from_list( $new_columns ) ), $existing_table_data );
        }
        
        $this->commit( $table, $existing_table_data );
    }
    
    public function delete( $table, $conditions = NULL )
    {
        $this->load( $table );
        $existing_table_data = $this->get( $table );
        if ( is_null( $conditions ) )
        {
            $process_results = $existing_table_data;
        }
        else
        {
            $process_results = $this->process( $existing_table_data, $conditions );
        }
        
        $process_results = $this->remove_all_empty( $table, $process_results );
        
        foreach ( $process_results as $key => $value )
        {
            unset( $existing_table_data[ $key ] );
        }
        
        $this->commit( $table, $existing_table_data );
    }
    
    /* --------------------------------------------------------------
     * Meta and Transactions
     * ------------------------------------------------------------ */
    
    public function show_tables( $directory = NULL )
    {
        $results = array( );
        
        if ( is_null( $directory ) )
        {
            $directory = $this->directory;
        }
        
        foreach ( glob( $directory . DS . "*.db" ) as $filename )
        {
            $db_name    = basename( $filename, '.' . pathinfo( $filename, PATHINFO_EXTENSION ) );
            $results[ ] = $db_name;
        }
        
        return $results;
    }

    public function table_columns( $name )
    {
        if ( $this->exists( $name ) )
        {
            $data = current( $this->get( $name ) );
            if ( !empty( $data ) )
            {
                return array_keys( $data );
            }
            
        }
        
        return array( );
    }
    
    public function create_table( $name, $columns = NULL )
    {
        if ( !$this->exists( $name ) )
        {
            if ( !is_null( $columns ) )
            {
                if ( !is_array( $columns ) )
                {
                    if ( !count( $columns = array_map( 'trim', explode( ',', $columns ) ) ) )
                    {
                        $columns[ ] = trim( $columns );
                    }
                }
                
                $temp = array( );
                foreach ( $columns as $column )
                {
                    $temp[ $column ] = '';
                }
                
                $columns = array(
                     $temp 
                );
                $this->commit( $name, $columns );
                return TRUE;
            }
            
            return FALSE;
        }
        
        return FALSE;
    }
    
    public function drop_table( $name )
    {
        if ( $this->exists( $name ) )
        {
            $this->remove( $name );
            return TRUE;
        }
        
        return FALSE;
    }
    
    public function truncate_table( $name )
    {
        if ( $this->exists( $name ) )
        {
            $this->commit( $name, $this->create_blank_columns( $name ) );
            return TRUE;
        }
        
        return FALSE;
    } 

    
    /* --------------------------------------------------------------
     * Private Helpers
     * ------------------------------------------------------------ */  
	 private function create_blank_columns( $table )
	 {
	     if ( $this->exists( $table ) )
	     {
	         $columns = $this->table_columns( $table );
	         $temp    = array( );
	         foreach ( $columns as $column )
	         {
	             $temp[ $column ] = '';
	         }
         
	         $columns = array(
	              $temp 
	         );
	         return $columns;
	     }
     
	     return FALSE;
	 }
 
	 private function create_blank_columns_from_list( $columns )
	 {
	     $results = array( );
	     if ( is_array( $columns ) )
	     {
	         foreach ( $columns as $column )
	         {
	             $results[ $column ] = '';
	         }
	     }
     
	     return $results;
	 }    
	 private function order_by( $array, $on, $order = 'ASC' )
	 {
	     $new_array      = array( );
	     $sortable_array = array( );
     
	     if ( count( $array ) > 0 )
	     {
	         foreach ( $array as $k => $v )
	         {
	             if ( is_array( $v ) )
	             {
	                 foreach ( $v as $k2 => $v2 )
	                 {
	                     if ( $k2 == $on )
	                     {
	                         $sortable_array[ $k ] = $v2;
	                     }
	                 }
	             }
	             else
	             {
	                 $sortable_array[ $k ] = $v;
	             }
	         }
         
	         switch ( $order )
	         {
	             case strtolower( $order ) == 'asc':
	                 asort( $sortable_array );
	                 break;
	             case strtolower( $order ) == 'desc':
	                 arsort( $sortable_array );
	                 break;
	             case strtolower( $order ) == 'rand':
	                 $this->randonmizer( $sortable_array );
	                 break;
	         }
         
	         foreach ( $sortable_array as $k => $v )
	         {
	             $new_array[ $k ] = $array[ $k ];
	         }
	     }
     
	     return $new_array;
	 }
 
	 private function remove_all_empty( $table, $data )
	 {
	     foreach ( $this->gather_all_empty( $table, $data ) as $key => $values )
	     {
	         unset( $data[ $key ] );
	     }
     
     
	     return $data;
	 }
 
 
	 private function gather_all_empty( $table, $data )
	 {
	     $results = array( );
	     $columns = $this->table_columns( $table );
     
	     $empty_columns = array( );
	     if ( is_array( $columns ) )
	     {
	         foreach ( $columns as $column )
	         {
	             $empty_columns[ ] = $column . "==''";
	         }
         
	         $conditional = implode( ' and ', $empty_columns );
         
	         $results = $this->process( $data, $conditional );
	     }
     
	     return $results;
	 }
 
	 private function process( $data, $expression )
	 {
	     $results    = array( );
	     $expression = preg_replace( "/([^\s]+?)(=|<|>|!)/", "\$a['$1']$2", $expression ); 
	     foreach ( $data as $key => $a )
	     {
	         if ( eval( "return $expression;" ) )
	         {
	             $results[ $key ] = $a;
	         }
	     }
	     return $results;
	 }
    
    private function is_array_equal( $one, $two )
    {
        return ( is_array( $one ) && is_array( $two ) && array_diff( $one, $two ) === array_diff( $two, $one ) );
    }
    
    private function randonmizer( &$array )
    {
        $copy = array( );
        while ( count( $array ) )
        {
            $element          = array_rand( $array );
            $copy[ $element ] = $array[ $element ];
            unset( $array[ $element ] );
        }
        
        $array = $copy;
    }
    
    private function splicer( $array, $start, $end )
    {
        if ( $start < 0 )
            $start = 0;
        if ( $end > count( $array ) )
            $end = count( $array );
        
        $new = array( );
        $i   = 0;
        
        foreach ( $array as $key => $value )
        {
            if ( $i >= $start && $i < $end )
            {
                $new[ $key ] = $value;
            }
            
            $i++;
        }
        
        return ( $new );
    }
    
    /* --------------------------------------------------------------
     * Security
     * ------------------------------------------------------------ */
    
    private function encrypt( $string )
    {
        $key    = $this->encrypt_key; 
		if(!is_string($key) || $key == NULL || empty($key))
		{
			return $string;
		}   
		
        $result = '';
        for ( $i = 0; $i < strlen( $string ); $i++ )
        {
            $char    = substr( $string, $i, 1 );
            $keychar = substr( $key, ( $i % strlen( $key ) ) - 1, 1 );
            $char    = chr( ord( $char ) + ord( $keychar ) );
            $result .= $char;
        }
        return base64_encode( $result );
    }
    
    private function decrypt( $string )
    {
        $key    = $this->encrypt_key;  
		
		if(!is_string($key) || $key == NULL || empty($key))
		{
			return $string;
		}
		
        $result = '';
        $string = base64_decode( $string );
        for ( $i = 0; $i < strlen( $string ); $i++ )
        {
            $char    = substr( $string, $i, 1 );
            $keychar = substr( $key, ( $i % strlen( $key ) ) - 1, 1 );
            $char    = chr( ord( $char ) - ord( $keychar ) );
            $result .= $char;
        }
        return $result;
    }
    
    /* --------------------------------------------------------------
     * DEBUG
     * ------------------------------------------------------------ */
    
    public function debug( $data = array( ), $title = NULL )
    {
        if ( $this->debugging )
        {
            if ( !is_null( $title ) )
            {
                echo "<p> ------------------- " . strtoupper( $title ) . " ------------------- </p>";
            }
            
            echo "<pre>" . print_r( $data, TRUE ) . "</pre>";
        }
    }
    
}

$storage = Storage::instance();