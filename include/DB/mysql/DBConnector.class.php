<?php

/* *************************************************** */
/*                                                     */
/*                  CONNECTION HELPER                  */
/*                                                     */
/* *************************************************** */

class DBConnector {

  public $link; 
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
      $this->link = new mysqli($this->server, $this->username, $this->password); 
      
      if (mysqli_connect_errno()) {
      
        $this->error();
        
      } else {
      
        return true;
        
      }
  }

  public function selectdb()
  {
    $this->link->select_db($this->dbname);
    
    if (mysqli_connect_errno()) {
   
      $this->error();

      
    } else {
    
      return true;
      
    }
  }

  public function error() 
  { 
      
      // Closes the current connection
      printf("MySQL error: %s\n", mysqli_connect_error());
      $this->link->close(); 

      die(0);
  }
  
}
