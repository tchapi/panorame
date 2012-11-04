<?php

  class Fill {

    private static function fillMySQLWithRandomStuff($limit){

      // LAST ID
      $lastId_query = "SELECT `id` FROM  `vertices` ORDER BY `id` DESC LIMIT 1";
      $exe = mysql_query($lastId_query);
      $row = mysql_fetch_array($exe, MYSQL_ASSOC);

      $availableFromId = $row['id'] + 1;

      $vertices = "INSERT INTO  `vertices` (`id`, `point` ,`elevation`) VALUES ";
      $edges = "INSERT INTO  `edges` (`from_id` ,`to_id`,`distance` ,`grade`, `type`) VALUES ";

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

    private static function fillMongoWithRandomStuff($limit){

      global $DBConnection;

      $db = $DBConnection->getDB();

      $db->resetError();

      $vertices = $db->vertices;
      $edges = $db->edges;

      // LAST ID
      $lastVertexIdElement = current(iterator_to_array($vertices->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
      $lastEdgeIdElement = current(iterator_to_array($edges->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));

      $lastVertexId = $lastVertexIdElement['_id'];
      $lastEdgeId = $lastEdgeIdElement['_id'];

      $currentVertexId = intval($lastVertexId + 1);
      $currentEdgeId = intval($lastEdgeId + 1);

      for($i = 0; $i < $limit; $i++){

        $randomLat_start = 48.830000 + mt_rand(0,50000) / 1000000;
        $randomLng_start = 2.280000 + mt_rand(0,130000) / 1000000;
        $randomAlt_start = mt_rand(0,50);

        $randomLat_dest = 48.830000 + mt_rand(0,50000) / 1000000;
        $randomLng_dest = 2.280000 + mt_rand(0,130000) / 1000000;
        $randomAlt_dest = mt_rand(0,50);

        $distance = self::haversine($randomLat_start, $randomLng_start, $randomLat_dest, $randomLng_dest);
        $grade = $randomAlt_dest - $randomAlt_start;

        $vertices->insert(array( "_id" => $currentVertexId, "lat" => $randomLat_start, "lng" => $randomLng_start, "alt" => $randomAlt_start));
        $vertices->insert(array( "_id" => $currentVertexId + 1, "lat" => $randomLat_dest, "lng" => $randomLng_dest, "alt" => $randomAlt_dest ));

        $edges->insert(array( "_id" => $currentEdgeId, "from_id" => $currentVertexId, "to_id" => $currentVertexId + 1, "distance" => $distance, "grade" => $grade, "type" => 2 ));

        $currentVertexId += 2;
        $currentEdgeId += 1;
      }

      $error = $db->lastError();
      $description = ($error['ok']!=1?$error:"OK");

      return array('success' => ($error['ok'] == 1), 'description' => $description );

    }

    private static function exportMySQLToMongo(){

      $exportJSON = "";

      // edges
      $edgesQuery = "SELECT id, is_deleted, from_id, to_id, type FROM `edges` WHERE is_deleted = 0";
      $exe = mysql_query($edgesQuery);

      while ($edge = mysql_fetch_array($exe, MYSQL_ASSOC)){

        $exportJSON .= 'db.getCollection("edges").insert({ "_id": '.$edge['id'].', "is_deleted": '.$edge['is_deleted'].', "from_id": '.$edge['from_id'].', "to_id": '.$edge['to_id'].', "type": '.$edge['type'].' });'."\n";
      }

      // vertices
      $verticesQuery = "SELECT X(point) AS lng, Y(point) AS lat, elevation, id, is_deleted FROM `vertices` WHERE is_deleted = 0";
      $exe = mysql_query($verticesQuery);

      while ($vertex = mysql_fetch_array($exe, MYSQL_ASSOC)){

        $exportJSON .= 'db.getCollection("vertices").insert({ "_id": '.$vertex['id'].', "is_deleted": '.$vertex['is_deleted'].', "alt": '.intval($vertex['elevation']).', "point": { "0": '.$vertex['lng'].', "1": '.$vertex['lat'].' } });'."\n";
      }

      return $exportJSON;

    }

    private static function exportMongoToMySQL(){

      global $DBConnection;

      $db = $DBConnection->getDB();

      $db->resetError();

      $exportSQL = "";

      $vertices = iterator_to_array($db->vertices->find(array("is_deleted" => 0)));
      $edges = iterator_to_array($db->edges->find(array("is_deleted" => 0)));

      foreach($vertices as $vertex){
        $exportSQL .= "INSERT INTO `vertices` (`id`, `point` ,`elevation`) VALUES (".$vertex['_id'].", GEOMFROMTEXT(  'POINT(".$vertex['point'][1]." ".$vertex['point'][0].")', 4326 ), ".$vertex['alt']."); "."\n";
      }

      foreach($edges as $edge){
        $exportSQL .= "INSERT INTO `edges` (`id`, `from_id` , `to_id`, `is_dirty`, `type`) VALUES (".$edge['_id'].", ".$edge['from_id'].", ".$edge['to_id'].", 1, ".$edge['type']."); "."\n";
      }
  
      return $exportSQL;

    }

    public static function export(){

      global $database;

      switch($database){
        case 'mysql':
          return self::exportMySQLToMongo();
          break;
        case 'mongo':
          return self::exportMongoToMySQL();
          break;
      }

      return false;

    }

    public static function fillWithRandomStuff($limit){

      global $database;
      
      switch($database){
        case 'mysql':
          return self::fillMySQLWithRandomStuff($limit);
          break;
        case 'mongo':
          return self::fillMongoWithRandomStuff($limit);
          break;
      }

      return false;

    }

  };


function doAction() {
  
  if (isset($_GET['random']) && $_GET['random'] == 1){

    $numberOfEdgesToInsert = isset($_GET['n'])?intval($_GET['n']):0;

    if ($numberOfEdgesToInsert > 0 && $numberOfEdgesToInsert < 100000) {
      // Come on, INSERT SOME FUCKING VERTICES AND EDGES !!
      return array( 'number' => $numberOfEdgesToInsert, 'result' => Fill::fillWithRandomStuff($_GET['n']));
    } else {
      // GO FUCK YOURSELF
      return array( 'number' => $numberOfEdgesToInsert, 'result' => array( 'state' => false, 'description' => 'Too many or too few!'));   
    }

  } elseif (isset($_GET['export']) && $_GET['export'] == 1){

    header('Content-type: text/plain');
    return Fill::export();

  }

}
