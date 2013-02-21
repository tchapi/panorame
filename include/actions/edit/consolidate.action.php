<?php

function doAction() {
  
  $result = AdminUtils::consolidate();

  if ($result == null || $result == false){

    $result = array(
      'error' => 'Error consolidating database',
      'code'  => 2
    );

  }

  return $result;
}
