<?php
    require_once('sessions/session.inc.php');

    alt_session_configure();

    session_name('PHPREWARDID');
    session_start();

    echo '<pre>';
    echo 'Session started<br>';
    dump_session(session_id());

    echo 'Session Values:<br>';
    var_dump($_SESSION);

    $_SESSION['var1'] = 'value1'; //change, refresh!
    $_SESSION['var2'] = 'value2'; //change, refresh!

    echo 'Session Values after modification:<br>';
    var_dump($_SESSION);
    echo '</pre>';
?>