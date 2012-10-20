<?php

  $app = '_BACKEND';
  include_once('../../config.php');

  $result = DBUtils::getTypes();

  header('Content-type: application/json');
  print json_encode($result);
