<?php
    
    date_default_timezone_set('UTC');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // load the composer stuff  
    require_once('vendor/autoload.php');
    require_once('../config_vars.php'); // home of db_config[] & out of git
    
    // sessions are initiated on access tokens
    // so we want them to expire when the browser
    // is closed - kind of like basic auth.
    session_set_cookie_params(0);
    session_start();
    

    // create and initialise the database connection
    $mysqli = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);    
    
    // connect to the database
    if ($mysqli->connect_error) {
      $returnObject['error'] = $mysqli->connect_error;
      sendResults($returnObject);
    }
    
    if (!$mysqli->set_charset("utf8")) {
      printf("Error loading character set utf8: %s\n", $mysqli->error);
    }

?>