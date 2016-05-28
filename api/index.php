<?php

date_default_timezone_set('Europe/Stockholm');

try {
  // Create (connect to) SQLite database in file
  $db = new PDO('sqlite:dimebag.sqlite3');
  // Set errormode to exceptions
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
  // Print PDOException message
  echo $e->getMessage();

}

echo "Jea boii";
