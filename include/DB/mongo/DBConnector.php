<?php

/* *************************************************** */
/*                                                     */
/*                  CONNECTION HELPER                  */
/*                                                     */
/* *************************************************** */

class DB {

  private $link; 
  private $server;
  private $username;
  private $password;
  private $dbname;

  public function __construct($server, $username, $password, $dbname) {
    $this->server = $server;
    $this->username = $username;
    $this->password = $password;
    $this->dbname = $dbname;
   
  }
  
  public function connect() 
  { 
    try {

      $this->link = new Mongo("mongodb://".$this->username.":".$this->password."@".$this->server);
      return true
    
    } catch ( MongoConnectionException $e ) {
    
      $this->error();

    }
  }

  public function selectdb()
  { 
    try {

      $success  = $this->link->selectDB($this->dbname); 
      return true;
    
    } catch ( MongoConnectionException $e ) {
    
      $this->error();

    }
  }

  public function error() 
  { 
      // Closes the current connection
      // $this->link->lastError(); 
      print "Mongo DB Error : "."(no code)";
      die(0);
  }
  
}


/* *************************************************** */
/*                                                     */
/*                  CONNECTION ROUTINE                 */
/*                                                     */
/* *************************************************** */

$DBConnection = new DB($server, $user, $password, $database);

if ($DBConnection->connect()) $DBConnection->selectdb();
