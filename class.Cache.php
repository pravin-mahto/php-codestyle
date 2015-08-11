<?php

/*
 *
 * Usage:
 *
  <?php

  //store the variable
  Cache::set('key','abc');

  //increment/decrement the integer value
  Cache::increment('key');
  Cache::decrement('key');

  //fetch the value by it's key
  echo Cache::get('key');


  //delete the data
  echo Cache::delete('key');

  //Clear the cache memory on all servers
  Cache::flush();

  ?>

  Cache::replace() and Cache::add are implemented also.

  More information can be obtained here:
  http://www.danga.com/memcached/
  http://www.php.net/memcache

 */

/**
 * The class makes it easier to work with memcached servers and provides hints in the IDE like Zend Studio
 * @version 1
 *
 */
class Cache {

    /**
     * @var Memcache the Memcache instance
     */
    protected $mc_servers = null;

    /**
     * @var Memcache the Memcache instance
     */
    static $instance;

    /**
     * @var boolean whether to use a persistent connection
     */
    public $persistent = false;

    /**
     * @var integer value in seconds which will be used for connecting to the server
     */
    public $timeout = 1;

    /**
     * @var integer how often a failed server will be retried (in seconds)
     */
    public $retryInterval = 15; //no retrived

    /**
     * Singleton to call from all other functions
     */

    static function singleton() {
        self::$instance ||
                self::$instance = new Cache();
        return self::$instance;
    }

    /**
     * memcached servers
     */
    protected function __construct() {
        //Write here where from to get the servers list from
       
            $servers = array(
                array(
                    'host' => 'localhost-1',
                    'port' => 11211,
                    'weight' => 60,
                )
				,
                array(
                    'host' => 'localhost-2',
                    'port' => 11211,
                    'weight' => 40,
                ) 
            );
        
        if (!$servers) {
            trigger_error('No memcache servers to connect', E_USER_WARNING);
        }

        $this->mc_servers = new Memcache;
        if (is_array($servers)) {
            foreach ($servers as $key => $value) {
                if ($value['host'] != '') {
                    $this->mc_servers->addServer($value['host'], $value['port'], $this->persistent, $value['weight'], $this->timeout, $this->retryInterval, true);
                }
            }
        }
    }

    /**
     * Returns the resource for the memcache connection
     *
     * @return object memcache
     */
    protected function getMemcacheLink() {
        return $this->mc_servers;
    }

    /**
     * Clear the cache
     *
     * @return void
     */
    static function flush() {
        $x = self::singleton()->mc_servers->flush();
    }

    /**
     * Returns the value stored in the memory by it's key
     *
     * @param string $key
     * @return mix
     */
    static function get($key) {
        return self::singleton()->getMemcacheLink()->get($key);
    }

    /**
     * Store the value in the memcache memory (overwrite if key exists)
     *
     * @param string $key
     * @param mix $var
     * @param bool $compress
     * @param int $expire (seconds before item expires)
     * @return bool
     */
    static function set($key, $var, $compress = 0, $expire = 0) {
        return self::singleton()->getMemcacheLink()->set($key, $var, $compress ? MEMCACHE_COMPRESSED : null, $expire);
    }

    /**
     * Set the value in memcache if the value does not exist; returns FALSE if value exists
     *
     * @param sting $key
     * @param mix $var
     * @param bool $compress
     * @param int $expire
     * @return bool
     */
    static function add($key, $var, $compress = 0, $expire = 0) {
        return self::singleton()->getMemcacheLink()->add($key, $var, $compress ? MEMCACHE_COMPRESSED : null, $expire);
    }

    /**
     * Replace an existing value
     *
     * @param string $key
     * @param mix $var
     * @param bool $compress
     * @param int $expire
     * @return bool
     */
    static function replace($key, $var, $compress = 0, $expire = 0) {
        return self::singleton()->getMemcacheLink()->replace($key, $var, $compress ? MEMCACHE_COMPRESSED : null, $expire);
    }

    /**
     * Delete a record or set a timeout
     *
     * @param string $key
     * @param int $timeout
     * @return bool
     */
    static function delete($key, $timeout = 0) {
        return self::singleton()->getMemcacheLink()->delete($key, $timeout);
    }

    /**
     * Increment an existing integer value
     *
     * @param string $key
     * @param mix $value
     * @return bool
     */
    static function increment($key, $value = 1) {
        return self::singleton()->getMemcacheLink()->increment($key, $value);
    }

    /**
     * Decrement an existing value
     *
     * @param string $key
     * @param mix $value
     * @return bool
     */
    static function decrement($key, $value = 1) {
        return self::singleton()->getMemcacheLink()->decrement($key, $value);
    }

    /**
     * delete by type
     *
     * @param string $key
     * @param mix $value
     * @return bool
     */
    static function deleteCacheByType($type) {
        foreach (self::singleton()->getMemcacheLink()->getExtendedStats('slabs') as $slabs) {
            foreach (array_keys($slabs) as $slabId) {
                if (!is_numeric($slabId)) {
                    continue;
                }

                foreach (self::singleton()->getMemcacheLink()->getExtendedStats('cachedump', $slabId) as $stats) {
                    if (!is_array($stats)) {
                        continue;
                    }
                    foreach (array_keys($stats) as $key) {
                        foreach ($type as $data) {
                            if (strpos($key, $data) !== false) {
                                self::delete($key);
                                echo $key . ' - Data Delete in Memcache <br />';
                            }
                        }
                    }
                }
            }
        }
    }

/**
 * get Info
 *
 * @return Array
 */
static function info() {
	$dataArr = self::singleton()->getMemcacheLink()->getExtendedStats();
	foreach($dataArr as $server => $status){
		
		echo '<table cellpadding="2" cellspacing="2" width="600" style="border:2px solid #000;">';
		echo'<tr><th align="center"  colspan="2"><strong>Server Info</strong></th></tr>'; 		
		echo "<tr><td >Memcache Server :</td><td> <b>".$server."</b></td></tr>";
        echo "<tr><td>Memcache Server version:</td><td> ".$status ["version"]."</td></tr>";
        echo "<tr><td>Process id of this server process </td><td>".$status ["pid"]."</td></tr>";
        echo "<tr><td>Number of seconds this server has been running </td><td>".$status ["uptime"]."</td></tr>";
        echo "<tr><td>Accumulated user time for this process </td><td>".$status ["rusage_user"]." seconds</td></tr>";
        echo "<tr><td>Accumulated system time for this process </td><td>".$status ["rusage_system"]." seconds</td></tr>";
        echo "<tr><td>Total number of items stored by this server ever since it started </td><td>".$status ["total_items"]."</td></tr>";
        echo "<tr><td>Number of open connections </td><td>".$status ["curr_connections"]."</td></tr>";
        echo "<tr><td>Total number of connections opened since the server started running </td><td>".$status ["total_connections"]."</td></tr>";
        echo "<tr><td>Number of connection structures allocated by the server </td><td>".$status ["connection_structures"]."</td></tr>";
        echo "<tr><td>Cumulative number of retrieval requests </td><td>".$status ["cmd_get"]."</td></tr>";
        echo "<tr><td> Cumulative number of storage requests </td><td>".$status ["cmd_set"]."</td></tr>";
 
        $percCacheHit=((real)$status ["get_hits"]/ (real)$status ["cmd_get"] *100);
        $percCacheHit=round($percCacheHit,3); 
        $percCacheMiss=100-$percCacheHit; 

        echo "<tr><td>Number of keys that have been requested and found present </td><td>".$status ["get_hits"]." ($percCacheHit%)</td></tr>";
        echo "<tr><td>Number of items that have been requested and not found </td><td>".$status ["get_misses"]."($percCacheMiss%)</td></tr>";
 
        $MBRead= (real)$status["bytes_read"]/(1024*1024); 

        echo "<tr><td>Total number of bytes read by this server from network </td><td>".$MBRead." Mega Bytes</td></tr>";
         $MBWrite=(real) $status["bytes_written"]/(1024*1024) ; 
        echo "<tr><td>Total number of bytes sent by this server to network </td><td>".$MBWrite." Mega Bytes</td></tr>";
        $MBSize=(real) $status["limit_maxbytes"]/(1024*1024) ; 
        echo "<tr><td>Number of bytes this server is allowed to use for storage.</td><td>".$MBSize." Mega Bytes</td></tr>";
        echo "<tr><td>Number of valid items removed from cache to free memory for new items.</td><td>".$status ["evictions"]."</td></tr>";
 
		echo "</table>"; 
	}
	
}
function __destruct(){
	self::singleton()->mc_servers->close(); 
}

function closeConnection(){
	self::singleton()->mc_servers->close(); 
}
//class end
}

?>
