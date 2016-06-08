<?php
//API for DimeBag

//Design principles of this API architechture:
//Always respond in JSON
//Response data is stored on the $response variable. If response status is not set, show an error
//On succesful operation, set status as OK, on failure set status as FAIL

//DimeBag specific principles:
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

  $db->exec("CREATE TABLE IF NOT EXISTS log(
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    entry_type TEXT,
    credit REAL,
    ts TEXT)"
  );

} catch(PDOException $e) {
  //On db connection error crash with an error message.
  crash($e->getMessage());

}

function dblog($user_id, $entry_type, $credit_change){
  global $db;
  $query = $db->prepare('INSERT INTO log(user_id, entry_type, credit, ts)
    VALUES (?, ?, ?, ?);');

  try {
    $query->execute(array($user_id, $entry_type, $credit_change, time()));
  } catch(PDOException $e) {
    //On db connection error crash with an error message.
    crash($e->getMessage());
  }
}

//Shorthand for the request
$r = $_REQUEST;

if(isset($r['a']) && $r['a'] != ''){

  if($r['a'] == 'getUsers'){

    //Get users from db
    $query = $db->prepare('SELECT id, nick, credit
      FROM user;');

    $query->execute(array());

    $users = $query->fetchAll();

    //Respond with new user data
    $response['status'] = "OK";
    $response['users'] = $users;

  } else if($r['a'] == 'createUser' && isset($r['user'])) {
    $user = $r['user'];
    if(isset($user['nick']) && trim($user['nick']) !== '' && isset($user['credit'])) {

      //Save user to db
      $query = $db->prepare('INSERT INTO user(nick, credit, created_at, last_updated)
        VALUES (?, ?, ?, ?);');

      try {
        $query->execute(array($user['nick'], floatval($user['credit']), time(), 0));
      } catch(PDOException $e) {
        //On db connection error crash with an error message.
        crash($e->getMessage());
      }

      $userid = $db->lastInsertId();

      dblog($userid, 'CREATE_USER', floatval($user['credit']));

      //Get users from db
      $query = $db->prepare('SELECT id, nick, credit
        FROM user;');

      $query->execute(array());

      $users = $query->fetchAll();

      //Respond with new user data
      $response['status'] = "OK";
      $response['users'] = $users;

    }

  } else if($r['a'] == 'addCredit' && isset($r['userid']) && isset($r['amount'])) {
    $userid = (int)$r['userid'];
    $amount = floatval($r['amount']);
    if($userid !== 0 && $amount > 0) {

      //Get current user and it's balance
      $query = $db->prepare('SELECT credit FROM user WHERE id = ?;');

      try {
        $query->execute(array($userid));
      } catch(PDOException $e) {
        //On db connection error crash with an error message.
        crash($e->getMessage());
      }

      $rows = $query->fetchAll();
      if(count($rows) !== 1){
        crash('USER_ID_DOES_NOT_EXIST');
      }

      $user = $rows[0];


      //Calculate new credit
      $new_credit = $user['credit'] + $amount;

      //Save credit to db
      $query = $db->prepare('UPDATE user
        SET credit = ?, last_updated = ?
        WHERE id = ?');

      try {
        $query->execute(array($new_credit, time(), $userid));
      } catch(PDOException $e) {
        //On db connection error crash with an error message.
        crash($e->getMessage());
      }

      dblog($userid, 'ADD_CREDIT', $amount);

      //Respond with OK
      $response['status'] = "OK";

    }

  } else if($r['a'] == 'doPayment' && isset($r['userid']) && isset($r['price'])) {
    $userid = (int)$r['userid'];
    $price = floatval($r['price']);
    if($userid !== 0 && $price > 0) {

      //Get current user and it's balance
      $query = $db->prepare('SELECT credit FROM user WHERE id = ?;');

      try {
        $query->execute(array($userid));
      } catch(PDOException $e) {
        //On db connection error crash with an error message.
        crash($e->getMessage());
      }

      $rows = $query->fetchAll();
      if(count($rows) !== 1){
        crash('USER_ID_DOES_NOT_EXIST');
      }

      $user = $rows[0];

      //Calculate new credit
      $new_credit = $user['credit'] - $price;

      if($new_credit >= 0){
        //Save credit to db
        $query = $db->prepare('UPDATE user
          SET credit = ?, last_updated = ?
          WHERE id = ?');

        try {
          $query->execute(array($new_credit, time(), $userid));
        } catch(PDOException $e) {
          //On db connection error crash with an error message.
          crash($e->getMessage());
        }

        dblog($userid, 'USE_CREDIT', $price);

        //Respond with OK
        $response['status'] = "OK";

      } else {

        dblog($userid, 'NO_CREDIT', $price);

        crash('NO_CREDIT');

      }

    }

  }

}

if(!isset($response['status'])){
  //If response status was not set, we can assume a parameter error
  crash('PARAMETER_ERROR');

}

//Finally, JSON encode and print the response.
die(json_encode($response));