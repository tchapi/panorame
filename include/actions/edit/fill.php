<?php

  $app = '_BACKEND';
  include_once('../../config.php');

  class Test {
 
    public static function fillWithRandomStuff($limit){

      // LAST ID
      $lastId_query = "SELECT `id` FROM  `vertices` ORDER BY `id` DESC LIMIT 1";
      $exe = mysql_query($lastId_query);
      $row = mysql_fetch_array($exe, MYSQL_ASSOC);

      $availableFromId = $row['id'] + 1;

      $vertices = "INSERT INTO  `isocron`.`vertices` (`id`, `point` ,`elevation`) VALUES ";
      $edges = "INSERT INTO  `isocron`.`edges` (`from_id` ,`to_id`,`distance` ,`grade`, `type`) VALUES ";

      $currentId = $availableFromId;
      $currentNextId = $availableFromId + 1;

      for($i = $availableFromId; $i < $limit + $availableFromId; $i++){

        $randomLat_start = 48.830000 + mt_rand(0,50000) / 1000000;
        $randomLng_start = 2.280000 + mt_rand(0,130000) / 1000000;
        $randomAlt_start = mt_rand(-300,300);

        $randomLat_dest = 48.830000 + mt_rand(0,50000) / 1000000;
        $randomLng_dest = 2.280000 + mt_rand(0,130000) / 1000000;
        $randomAlt_dest = mt_rand(-300,300);

        $distance = DBUtils::haversine($randomLat_start, $randomLng_start, $randomLat_dest, $randomLng_dest);
        $grade = $randomAlt_dest - $randomAlt_start;

        if ($i != $availableFromId) $vertices .= ", ";
        $vertices .= sprintf("( %d, GEOMFROMTEXT(  'POINT(%F %F)', 4326 ) ,  '%d' ), ( %d, GEOMFROMTEXT(  'POINT(%F %F)', 4326 ) ,  '%d' )",
              mysql_real_escape_string($currentId),
              mysql_real_escape_string($randomLng_start),
              mysql_real_escape_string($randomLat_start),
              mysql_real_escape_string($randomAlt_start),
              mysql_real_escape_string($currentNextId),
              mysql_real_escape_string($randomLng_dest),
              mysql_real_escape_string($randomLat_dest),
              mysql_real_escape_string($randomAlt_dest));

        if ($i != $availableFromId) $edges .= ", ";
        $edges .= sprintf("( '%d', '%d', '%F', '%d', 0 )",
                mysql_real_escape_string($currentId),
                mysql_real_escape_string($currentNextId),
                mysql_real_escape_string($distance),
                mysql_real_escape_string($grade));

        $currentId += 2;
        $currentNextId += 2;
      }

      $exe = mysql_query($vertices);
      $error = !(mysql_errno() == 0);

      $exe = mysql_query($edges);
      $error = $error && !(mysql_errno() == 0);

      $description = ($error?mysql_error():"OK");

      return array('success' => !$error, 'description' => $description );

    }

  };

  $numberOfEdgesToInsert = isset($_GET['n'])?intval($_GET['n']):0;

  if ($numberOfEdgesToInsert > 0 && $numberOfEdgesToInsert < 100000) {
    // Come on, INSERT SOME FUCKING VERTICES AND EDGES !!
    header('Content-type: application/json');
    print json_encode(array( 'number' => $numberOfEdgesToInsert, 'result' => Test::fillWithRandomStuff($_GET['n'])));
  } else {
    // GO FUCK YOURSELF
    header('Content-type: application/json');
    print json_encode(array( 'number' => $numberOfEdgesToInsert, 'result' => array( 'state' => false, 'description' => 'Too many or too few!')));   
  }