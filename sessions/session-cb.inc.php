<?php

  function dump_session($sessionId = '1')
  {
    $host='couchbase://couchbase.abq.website.com';
    $bucketName = 'default';
    $key = 'session:' . $sessionId;
    //$key = "session:pgjl50tc72m0jbnqfh3uv2uq57";
    $result = null;

    echo '<pre>';

    // Connect to Couchbase Server
    $cluster = new CouchbaseCluster("couchbase://couchbase.abq.website.com");
    if ( $cluster !== FALSE ) {
        $bucket = $cluster->openBucket($bucketName);
        $query = CouchbaseN1qlQuery::fromString("SELECT * FROM `$bucketName` USE KEYS '$key'");
        var_dump($query);

        $result = $bucket->query($query);
        $rc = $result->metrics["resultCount"];

        echo "Result Set: \n";
        var_dump($result);

        echo "Result Count: ";
        print_r($rc);

        echo "\n";
        echo "Key: ";
        print_r($result->rows[0]->default->key);
        echo "\n";
        echo "Session Data: ";
        print_r($result->rows[0]->default->sessionData);
        echo "\n";
    }

    echo '</pre>';
    return $result;

  }

  // 5.4 Way : implements SessionHandlerInterface
  class ALTCouchbaseSessionHandler implements SessionHandlerInterface
  {

    // The Couchbase host and port.
    protected $_host = null;

    // Holds the Couchbase connection.
    protected $_cluster= null;

    // The Couchbase bucket connection.
    protected $_bucket = null;

    // The Couchbase bucket name.
    protected $_bucketName = null;

    // The prefix to be used in Couchbase keynames.
    protected $_keyPrefix = 'session:';

    // Define a expiration time of 10 minutes.
    protected $_expire = 600;

    // What value is returned when the value is missing from CB?
    protected $_missing_session_value = "";

    /**
    * Set the default configuration params on init.
    */
    public function __construct(
      $couchbase_session_expiration=null,
      $missing_session_value="",
      $couchbase_session_key_prefix=null,
      $host='couchbase://couchbase.abq.onlineregister.com',
      $bucketName = 'default') {

      $this->_host = $host;
      $this->_bucketName = $bucketName;

      if(!isset($couchbase_session_expiration)) {
        $couchbase_session_expiration = ini_get('session.gc_maxlifetime');
      }

      $this->_expire = (int)$couchbase_session_expiration;

      if(isset($couchbase_session_key_prefix)) {
        $this->_keyPrefix = $this->_keyPrefix . ":" . $couchbase_session_key_prefix . ":";
      }

      $this->_missing_session_value = $missing_session_value;
    }

    /**
    * Open the connection to Couchbase (called by PHP on `session_start()`)
    */
    public function open($savePath, $sessionName) {
      try {
        $this->_cluster = new CouchbaseCluster($this->_host);
        if ( $this->_cluster !== FALSE ) {
          $this->_bucket = $this->_cluster->openBucket($this->_bucketName);
          return true;
        }
        //error_log("Couchbase session handler connection failed." );
      } catch(CouchbaseException $e) {
        error_log("Couchbase Connection Exception : " . $e->getMessage() );
      }

      // make sure this is null so we don't do other silly stuff later on.
      unset($this->_cluster);
      unset($this->_bucket);
      return false;
    }

    /**
    * Close the connection. Called by PHP when the script ends.
    */
    public function close() {
      unset($this->_cluster);
      unset($this->_bucket);
      return true;
    }

    /**
    * Read data from the session.
    */
    public function read($sessionId) {
      if( isset( $this->_bucket ) ) {
        $key = $this->_keyPrefix . $sessionId;
        $result = null;

        $bucketName = $this->_bucketName;

        //echo "reading session key: $key \n";
        try {
          $query = CouchbaseN1qlQuery::fromString("SELECT * FROM `$this->_bucketName` USE KEYS '$key'");
          $result = $this->_bucket->query($query);
          //var_dump($result);
          $rc = $result->metrics["resultCount"];

          if($rc != '1'){
            return $this->_missing_session_value;
          }
        } catch(CouchbaseException $e) {
          error_log("Couchbase Read Exception : " . $e->getMessage() );
        }

        /*
        if(isset($result->rows[0]->default->sessionData)) {
          echo "found session data: \n";
          print_r($result->rows[0]->default->sessionData);
        }
        */

        // Did you know this was called the Elvis operator? me neither. http://en.wikipedia.org/wiki/Elvis_operator
        return isset($result->rows[0]->default->sessionData) ? $result->rows[0]->default->sessionData : $this->_missing_session_value; // was null
      }

      return $this->_missing_session_value;
    }

    /**
    * Write data to the session.
    */
    public function write($sessionId, $sessionData) {
      if(isset($this->_bucket)) {
        $key = $this->_keyPrefix . $sessionId;
        $result = null;

        //echo "writing session key: $key \n";
        //echo "writing session data: $sessionData \n";
        try {
          //$result = $this->_connection->set($key, $sessionData, $this->_expire);
          $result = $this->_bucket->upsert($key, array("key" => $key, "sessionData" => $sessionData), array('expiry' => $this->_expire ));
        } catch(CouchbaseException $e) {
          error_log("Couchbase Write Exception : " . $e->getMessage() );
        }
        return $result ? true : false;

      }

      return false;
    }

    /**
    * Delete data from the session.
    */
    public function destroy($sessionId) {
      if(isset($this->_bucket)) {
        $key = $this->_keyPrefix . $sessionId;
        $result = null;

        //echo "reading session key: $key \n";
        try {
          $result = $this->_bucket->remove($key);
        } catch(CouchbaseException $e) {
          error_log("Couchbase Destroy Exception : " . $e->getMessage() );
        }
        return $result ? true : false;
      }

      return false;
    }

    /**
    * Run the garbage collection.
    */
    public function gc($maxLifetime) {
      return true; // Just return true couchbase will handle the clean up
    }

  }

?>