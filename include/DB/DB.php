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
      $this->link = mysql_connect($this->server, $this->username, $this->password); 
      
      if ( !($this->link) ) {
      
        $this->error();
        
      } else {
      
        return true;
        
      }
  }

  public function selectdb()
  {
    $success	= mysql_select_db($this->dbname, $this->link);
    
    if ( !$success ) {
      
      $this->error();
      
    } else {
    
      return true;
      
    }
  }

  public function error() 
  { 
      // Closes the current connection
      mysql_close($this->link); 
      
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
