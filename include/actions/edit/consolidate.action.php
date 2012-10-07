<?php

  $app = '_BACKEND';
  include_once('../../config.php');

  $result = Utils::consolidate();

  if ($result == null || $result == false){

    $result = array(
      'error' => 'Error consolidating database',
      'code'  => 2
    );

  }

  header('Content-type: application/json');
  print json_encode($result);
