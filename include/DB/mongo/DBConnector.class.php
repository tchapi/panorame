<?php

/* *************************************************** */
/*                                                     */
/*                  CONNECTION HELPER                  */
/*                                                     */
/* *************************************************** */

class DBConnector {

  private $link; 
  private $server;
  private $username;
  private $password;
  private $dbname;

  private $db;

  public function __construct($server, $username, $password, $dbname) {
    $this->server = $server;
    $this->username = $username;
    $this->password = $password;
    $this->dbname = $dbname;
   
  }
  
  public function connect() 
  { 
    try {

      $this->link = new Mongo("mongodb://".$this->username.":".$this->password."@".$this->server."/".$this->dbname);
      return true;
    
    } catch ( MongoConnectionException $e ) {
      
      var_dump($e);
      $this->error();

    }
  }

  public function selectdb()
  { 
    try {

      $this->db  = $this->link->selectDB($this->dbname); 
      return true;
    
    } catch ( MongoConnectionException $e ) {
    
      $this->error();

    }
  }

  public function error() 
  { 
      // Closes the current connection
      print "Mongo DB Error : ".$this->link->getLastError();
      die(0);
  }

  public function getDB(){

    return $this->db;
  
  }
  
}


/* *************************************************** */
/*                                                     */
/*                  CONNECTION ROUTINE                 */
/*                                                     */
/* *************************************************** */

$DBConnection = new DBConnector($server, $user, $password, $database);

if ($DBConnection->connect()) $DBConnection->selectdb();
