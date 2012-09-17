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

      $this->link = new Mongo(); 
    
    } catch ( MongoConnectionException $e ) {
    
      $this->error();

    }
  }

  public function selectdb()
  { 
    try {

      $success  = $this->link->selectDB($this->dbname); 
    
    } catch ( MongoConnectionException $e ) {
    
      $this->error();

    }
  }

  public function error() 
  { 
      // Closes the current connection
      // $this->link->lastError(); 
      
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
