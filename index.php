<?php

  /** Absolute path to the root directory. */
  if ( !defined('_PATH') )
        define('_PATH', dirname(__FILE__) . '/');

  require_once(_PATH.'config.php');
  require_once(_PATH.'include/controller/Controller.class.php');

  try {
    Controller::process();
  } catch (Exception $e){
    Controller::render('ajax/json', array('error' => $e->getMessage()));
  }
