<?php

/* *************************************************** */
/*                                                     */
/*                  CONNECTION HELPER                  */
/*                                                     */
/* *************************************************** */

class DBConnection
{

    private static $_instance;
    
    // Prevent unconfigured PDO instances!
    private function __construct($config)
    {
        $dsn = sprintf('mysql:dbname=%s;host=%s;', $config['database'], $config['hostname']);
        self::$_instance = new PDO($dsn, $config['username'], $config['password']);    
    }

    public static function db($config = null)
    {
        if(self::$_instance !== null || is_null($config) )
        {
            //We have already stored the object locally so just return it.
            //This is how the object always stays the same
            return self::$_instance;
        }
        
        new DBConnection($config); //Set the instance.
        return self::$_instance;
    }

}

/* *************************************************** */
/*                                                     */
/*               FIRST CONNECTION ROUTINE              */
/*                                                     */
/* *************************************************** */

$db = DBConnection::db(array(
  'hostname' => _server,
  'username' => _user,
  'password' => _password,
  'database' => _database
));
