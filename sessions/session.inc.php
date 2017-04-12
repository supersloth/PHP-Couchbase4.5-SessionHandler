<?

  function alt_session_configure($expires=null,$missingSessionValue="")
  {
    require_once('session-cb.inc.php');

    $couchbaseSessionHandlerSession = new ALTCouchbaseSessionHandler($expires,$missingSessionValue);

    // # new 5.4 way
    // http://php.net/manual/en/function.session-set-save-handler.php
    session_set_save_handler($couchbaseSessionHandlerSession, true);

  }

?>




