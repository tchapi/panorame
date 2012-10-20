<?php

  $app = '_BACKEND';
  include_once('../config.php');

  $result = DBUtils::getMeansAndSpeeds();

  header('Content-type: application/json');
  print json_encode($result);
