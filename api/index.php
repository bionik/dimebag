<?php
//API for :DimeBag

//Design principles of this API architechture:
//Always respond in JSON
//Response data is stored on the $response variable. If response status is not set, show an error
//On succesful operation, set status as OK, on failure set status as FAIL

//:DimeBag specific principles:
//Action is chosen with the a (action) parameter of the request

//Disable caching
header("Pragma: no-cache");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

//Set response content type
header("Content-type: application/json");

//Set default timezone
date_default_timezone_set('Europe/Helsinki');

//Method for an immediate error message, also encoded in JSON
function crash($message){
  die(json_encode(array('status'=>'ERROR', 'message'=>$message)));

}

//Checks data is set
function c($variable){
  if(isset($variable) && $variable !== '') return true;
  return false;
}

//This variable will contain the response sent to the user
$response = array();

//Try to connect to the database, and create the tables if they do not exist.
try {
  //Create or connect to SQLite database in file.
  //NEEDS WRITE PERMISSIONS TO THE DIRECTORY, if the database file does not already exist.
  $db = new PDO('sqlite:dimebag.sqlite3');

  //Set error reporting mode
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  //Create tables
  $db->exec("CREATE TABLE IF NOT EXISTS user(
    id INTEGER PRIMARY KEY,
    nick TEXT,
    credit REAL,
    created_at TEXT,
    last_updated TEXT)"
  );

} catch(PDOException $e) {
  //On db connection error crash with an error message.
  crash($e->getMessage());

}

//Shorthand for the request
$r = $_REQUEST;

if(isset($r['a']) && $r['a'] != ''){

  if($r['a'] == 'getUsers'){
    $response['status'] = "OK";
    $response['users'] = array(array('id'=>1, 'nick'=>'bionik', 'credit'=>12.00),array('id'=>2, 'nick'=>'netl', 'credit'=>16.00));
  } else if($r['a'] == 'createUser'){
    //TODO
  }

}

if(!isset($response['status'])){
  //If response status was not set, we can assume a parameter error
  crash('PARAMETER_ERROR');

}

//Finally, JSON encode and print the response.
die(json_encode($response));