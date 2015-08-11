<?php
class Database
{

  private static $instance = array();
  private $connection;   
  private $result;
  private $row;
  private $sql;
  private $error;
  const STATS_QUERY = false;
  private static $__exe_queries = array();

  private function __construct($index)
  {
     global $dbdetails;
     $this->connection = new mysqli($dbdetails[$index]['host'], $dbdetails[$index]['user'], $dbdetails[$index]['password'], $dbdetails[$index]['database']);
        
  }
 
  private function __clone()
  {
    $this->connection->close();
    trigger_error('Clone is not allowed.', E_USER_ERROR);
  }

  public function __toString()
  {
    $this->connection->close();
    trigger_error('Print is not allowed.', E_USER_ERROR);
  }

  public static function getConnection($index = DB_SLAVE)
  {
  	$index = (!empty($index) ? $index : DB_SLAVE);
    if (empty(self::$instance[$index]) || empty(self::$instance[$index]->connection->client_info) ||  !is_object(self::$instance[$index]) )
    {
      self::$instance[$index] = new Database($index);
      mysqli_options( self::$instance[$index] , MYSQLI_OPT_CONNECT_TIMEOUT , 10);
    }
    
    return self::$instance[$index];
  }

  public function query($sql)
  {
    if ( is_object($this->result) )
    {
      $this->result->close();
    }
    
        
    $this->sql = $sql;
    //self::$__exe_queries[] = $sql;
    if ( $this->result = $this->connection->query($this->sql) )
    {
      return true;
    }
    else
    {
      $this->error = $this->connection->error;
      return false;
    } 
  }

  public function getRowCount()
  {
    if ( is_object($this->result) )
    {
      return $this->result->num_rows;
    }
  }

  public function getInsertedAutoId()
  {
    return $this->connection->insert_id;
  }

  public function getAffectedRowCount()
  {
    return $this->connection->affected_rows;
  }

  public function fetch()
  {
    if ( is_object($this->result) )
    {
    	return $this->result->fetch_array();
    }	
  }

  public function fetchAssoc()
  {
    if ( is_object($this->result) )
    {
    	return $this->result->fetch_array(MYSQLI_ASSOC);
    }	
  }

  public function getResultSet()
  {
    $resultSet = array();
	 if ( is_object($this->result) )
    {    
    	while ( $row = $this->result->fetch_array(MYSQLI_ASSOC) )
    	{
      	$resultSet[] = $row;
    	}
    }
    return $resultSet;
  }
    

  public function close()
  {
    
    if ( is_object($this->result) )
    {
      $this->result->close();
    }
    
    if ( is_object($this->connection) )
    {
        $this->connection->close();
    }
    
    
    self::debug_queries();
  }
    
  private static function debug_queries()
  { 
    if ( self::STATS_QUERY )
    {
      print "Total Quries: " . count(self::$__exe_queries) . "<br \>\n";
      foreach ( self::$__exe_queries as $q_key => $v_query )
      {
        print ($q_key + 1) . ') ' . $v_query . "<br />\n";
      }
      self::$__exe_queries = array();
    }
  }

  public function db_escape($string)
  {
    if ( !is_array($string) )
    {
      return $this->connection->real_escape_string(trim($string));
    }    
  }
  
  function __destruct(){
	if ( is_object($this->result) )
    {
      $this->result->close();
      unset($this->result);
    }
    
    if ( is_object($this->connection) )
    {	
        $this->connection->close();
        unset($this->connection);
    }
   
    
  }
  
  
}

// eof class
