<?php

class DBUtils {

  /*
   * GET The means availables and their respective speeds
   */
  public static function getMeansAndSpeeds(){

    $getMeansAndSpeeds_query = "SELECT m.`id` AS id, m.`description` AS description, GROUP_CONCAT(CONCAT('{\"type_id\":', s.`type_id`, ', \"speeds\": [', s.`flat_speed`, ',', s.`grade_speed`, ']}')) AS explorables
                       FROM `means` m
                       LEFT JOIN `speeds` s ON (m.`id`= s.`mean_id`)
                       GROUP BY mean_id ";

    $getMeansAndSpeeds_result = mysql_query($getMeansAndSpeeds_query);

    // Returns true if the query was well executed
    if (!$getMeansAndSpeeds_result || $getMeansAndSpeeds_result == false ) {
      return false;
    } else {
      // Fetch the types
      $means = array();
        while ($row = mysql_fetch_array($getMeansAndSpeeds_result, MYSQL_ASSOC)) {
          
          $explorables = array();

          if ($row["explorables"] == null){
            $explorables = null;
          } else {
            $explorablesAsPHPArray = json_decode('['.$row["explorables"].']');
            foreach ($explorablesAsPHPArray as $explorable){
              $explorables[$explorable->type_id] = $explorable->speeds;
            }
          }

          array_push($means, array('id' => $row['id'], 'description' => $row['description'], 'explorables' => $explorables));
        
        }
      }

      return $means;

  }
  
  /*
   * Restricts a query for a vertex (of table v) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictForVertex($query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

      // Poi should be included
      $where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat),
            mysql_real_escape_string($SE_lng),
            mysql_real_escape_string($SE_lat),
            mysql_real_escape_string($POI_lng),
            mysql_real_escape_string($POI_lat),
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat));

    } else {

      // Just a polyline without the POI
      $where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat),
            mysql_real_escape_string($SE_lng),
            mysql_real_escape_string($SE_lat),
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat));
      
    }

    return $query.$where_clause;
  }

  /*
   * Restricts a query for an edge (of table v and v_dest) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictForEdgeBBox($query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

      // Poi should be included
      $where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat),
            mysql_real_escape_string($SE_lng),
            mysql_real_escape_string($SE_lat),
            mysql_real_escape_string($POI_lng),
            mysql_real_escape_string($POI_lat),
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat));

    } else {

      // Just a polygon without the POI
      $where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat),
            mysql_real_escape_string($SE_lng),
            mysql_real_escape_string($SE_lat),
            mysql_real_escape_string($NW_lng),
            mysql_real_escape_string($NW_lat));   

    }

    return $query.$where_clause;
  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    $getVerticesIn_query = sprintf("SELECT `id`, Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` v");

    $getVerticesIn_query = self::restrictForVertex($getVerticesIn_query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    $getVerticesIn_query .= " AND v.`is_deleted` = 0";

    $queryResult = mysql_query($getVerticesIn_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the vertices
      $verticesArray = array();
      while ($row = mysql_fetch_array($queryResult, MYSQL_ASSOC)) {
      $verticesArray[] = array(
                    'id' => intval($row["id"]), 
                    'point' => array(
                      'lat' => floatval($row["lat"]),
                      'lng' => floatval($row["lng"]),
                      'alt' => intval($row["alt"])
                    )
                  );
      }
      return $verticesArray;
    }

  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   * AND their 1th children
   */
  public static function getVerticesAndChildrenIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    // Extends the bounds
    $b = GeoUtils::extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng);

    $getVerticesAndChildrenIn_query = sprintf("SELECT v.`id` AS id, Y(v.`point`) AS lat, X(v.`point`) AS lng, v.`elevation` AS alt, 
                    group_concat(CONCAT('{\"id\":',e.`to_id`, ', \"path_id\":', e.`id`, ', \"distance\":', e.`distance`, ', \"grade\":', e.`grade`, ', \"type\":', e.`type`,', \"secable\":', t.`secable`, '}')) AS children FROM `vertices` v
                    LEFT JOIN `edges` e ON (e.`from_id` = v.`id` AND e.`is_deleted` =0)
                    JOIN `types` t ON (e.`type` = t.`id`)");

    $getVerticesAndChildrenIn_query  = self::restrictForVertex($getVerticesAndChildrenIn_query, $b['NW_lat'], $b['NW_lng'], $b['SE_lat'], $b['SE_lng'], $POI_lat, $POI_lng);
    $getVerticesAndChildrenIn_query .= " GROUP BY v.`id`";

    $queryResult = mysql_query($getVerticesAndChildrenIn_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the vertices
      $vertices = array();
      while ($row = mysql_fetch_array($queryResult, MYSQL_ASSOC)) {
        
        if ($row["children"] == null){
          $childrenAsPHPArray = null;
        } else {
          $childrenAsPHPArray = json_decode('['.$row["children"].']');
        }

        $vertices[intval($row["id"])] = array( 
                  'point' => array(
                    'lat' => floatval($row["lat"]),
                    'lng' => floatval($row["lng"]),
                    'alt' => intval($row["alt"])
                  ),
                  'children' => $childrenAsPHPArray
                );
      }

      return $vertices;
    }

  }

  /*
   * GET ALL THE EDGES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    $getEdgesIn_query = "SELECT e.`id` AS id, Y(v.`point`) AS lat_start, X(v.`point`) AS lng_start, v.`elevation` AS alt_start, v.`id` AS id_start,
                  Y(v_dest.`point`) AS lat_dest, X(v_dest.`point`) AS lng_dest, v_dest.`elevation` AS alt_dest, v_dest.`id` AS id_dest,
                  e.`distance` AS distance, e.`grade` AS grade, e.`type` AS type
                  FROM `edges` e
                  INNER JOIN `vertices` v ON v.`id` = e.`from_id`
                  INNER JOIN `vertices` v_dest ON v_dest.`id` = e.`to_id`";

    $getEdgesIn_query  = self::restrictForEdgeBBox($getEdgesIn_query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    $getEdgesIn_query .= " AND e.`is_deleted` = 0";
    
    $queryResult = mysql_query($getEdgesIn_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the edges
      $edges = array();
      while ($row = mysql_fetch_array($queryResult, MYSQL_ASSOC)) {
        $edges[] = array(
                  'id' => intval($row["id"]), 
                  'start' => array(
                    'id' => intval($row["id_start"]),
                    'point' => array(
                      'lat' => floatval($row["lat_start"]),
                      'lng' => floatval($row['lng_start']),
                      'alt' => intval($row["alt_start"])
                    )
                  ),
                  'dest' => array(
                    'id' => intval($row["id_dest"]),
                    'point' => array(
                      'lat' => floatval($row["lat_dest"]),
                      'lng' => floatval($row['lng_dest']),
                      'alt' => intval($row["alt_dest"])
                    )
                  ),
                  'distance' => floatval($row['distance']),
                  'grade' => intval($row['grade']),
                  'type' => intval($row['type'])
                );
      }
      return $edges;
    }

  }

  /*
   * GET The closest vertex from a given lat / lng couple within a x meters radius
   */
  public static function build_sorter($lat, $lng) {
    return function ($a, $b) use ($lat, $lng)
    {
      $a_ = GeoUtils::haversine($a['point']['lat'], $a['point']['lng'], $lat, $lng);
      $b_ = GeoUtils::haversine($b['point']['lat'], $b['point']['lng'], $lat, $lng);

      if ($a_ == $b_) { return 0; }
      return ($a_ < $b_) ? -1 : 1;
    };
  }

  public static function getClosestVertex($lat, $lng, $radius_in_m){

    $ratio = $radius_in_m/_earth_radius;
    $lat_rad = $lat*pi()/180;
    $lng_rad = $lng*pi()/180;
    $cos_lat_rad = cos($lat_rad);
    $sin_lat_rad = sin($lat_rad);

    $brng = (315/180)*pi(); // bearing is -45°
    $NW_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
    $NW_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($NW_lat*pi()/180)))* 180 / pi();

    $brng = 135/180*pi(); // bearing is 135°
    $SE_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
    $SE_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($SE_lat*pi()/180)))* 180 / pi();

    $vertices = self::getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, null, null);

    // If we have results
    if (isset($vertices[0])){

      // Sort (quick sort)
      usort($vertices, self::build_sorter($lat, $lng));
      
      $closest = $vertices[0];
      $closest['distance'] = GeoUtils::haversine($lat, $lng, $closest['point']['lat'], $closest['point']['lng']);

      return $closest;

    } else {

      return null;

    }
  }



/* ======================================================================== *
 *                                                                          *
 *                            ADMIN FUNCTIONS                               *
 *                                                                          *
 * ======================================================================== */ 


  /*
   * GET The types availables
   */
  public static function getTypes(){

    $getTypes_query = "SELECT `id`, `description`, `slug` from `types` where `editable` = 1;";
    $queryResult = mysql_query($getTypes_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the types
      $types = array();
        while ($row = mysql_fetch_array($queryResult, MYSQL_ASSOC)) {
          array_push($types, array('id' => $row['id'], 'description' => $row['description'], 'slug' => $row['slug']));
        }
      }

      return $types;

  }
  

  /*
   * Updating a vertex couple
   */
  public static function updateVertexCouple($start_id, $start_lat, $start_lng, $start_alt, $dest_id, $dest_lat, $dest_lng, $dest_alt, $edge_id){

    $startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
    $destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

    if ($startExistsAlready == null || intval($startExistsAlready['id']) == $start_id) {
      // Updating start vertex
      $updateVertex1_query = sprintf("UPDATE `vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = %d WHERE `id` = %d;",
                mysql_real_escape_string($start_lng),
                mysql_real_escape_string($start_lat),
                mysql_real_escape_string($start_alt),
                mysql_real_escape_string($start_id));

      $updateVertex1_result = mysql_query($updateVertex1_query);
    } else {
      // We must update all the edges that use this id with the new start_id
      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `from_id` = %d WHERE `from_id` = %d;",
              mysql_real_escape_string(intval($startExistsAlready['id'])),
              mysql_real_escape_string($start_id));

      $changeEdge_result = mysql_query($changeEdge_query);

      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `to_id` = %d WHERE `to_id` = %d;",
              mysql_real_escape_string(intval($startExistsAlready['id'])),
              mysql_real_escape_string($start_id));

      $changeEdge_result = mysql_query($changeEdge_query);
    }

    if ($destExistsAlready == null || intval($destExistsAlready['id']) == $dest_id) {
      // Updating destination vertex
      $updateVertex2_query = sprintf("UPDATE `vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = %d WHERE `id` = %d;",
                mysql_real_escape_string($dest_lng),
                mysql_real_escape_string($dest_lat),
                mysql_real_escape_string($dest_alt),
                mysql_real_escape_string($dest_id));

      $updateVertex2_result = mysql_query($updateVertex2_query);
    } else {
      // We must update all the edges that use this id with the new dest_id
      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `from_id` = %d WHERE `from_id` = %d;",
              mysql_real_escape_string(intval($destExistsAlready['id'])),
              mysql_real_escape_string($dest_id));

      $changeEdge_result = mysql_query($changeEdge_query);

      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `to_id` = %d WHERE `to_id` = %d;",
              mysql_real_escape_string(intval($destExistsAlready['id'])),
              mysql_real_escape_string($dest_id));

      $changeEdge_result = mysql_query($changeEdge_query);
    }

    // We should tag the edges containing these points as 'dirty'
    $tagEdgesAsDirty_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, WHERE `from_id` IN (%d,%d) OR `to_id` IN (%d,%d);",
              mysql_real_escape_string($start_id),
              mysql_real_escape_string($dest_id),
              mysql_real_escape_string($start_id),
              mysql_real_escape_string($dest_id));

    $result = mysql_query($tagEdgesAsDirty_query);

    return array(
      '1_updating_first_vertex' => $updateVertex1_result,
      '2_updating_second_vertex' => $updateVertex2_result,
      '3_tagging_edges_as_dirty' => $result
      );
  }

  /*
   * Deleting an edge
   */
  public static function deleteEdge($edge_id){

    // Get the two vertices of the edge
    $selectVertices = sprintf("SELECT `from_id`, `to_id` FROM `edges` WHERE `id` = %d;",
                      mysql_real_escape_string($edge_id));

    $vertices = mysql_query($selectVertices);

    if ($vertices != false) {
      $row = mysql_fetch_array($vertices, MYSQL_ASSOC);
      $from_id = intval($row['from_id']);
      $to_id = intval($row['to_id']);
    } else {
      return false;
    }
    
    // Deletes the edge
    $deleteEdge_query = sprintf("UPDATE `edges` SET `is_deleted` = 1, `is_dirty`= 1 WHERE `id` = %d;",
              mysql_real_escape_string($edge_id));
    $delete_result = mysql_query($deleteEdge_query);
    
    // Checks if any edge still uses vertex with id 'from_id'
    $fromIdCheck_query = sprintf("SELECT `id` FROM `edges` WHERE `is_deleted` = 0 AND (`from_id` = %d OR `to_id`= %d);",
              mysql_real_escape_string($from_id),
              mysql_real_escape_string($from_id));
    $fromIdCheck_result = mysql_query($fromIdCheck_query);

    if (mysql_num_rows($fromIdCheck_result) === 0){

      // the vertex is not used anymore
      $deleteFromId_query = sprintf("UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` = %d;",
            mysql_real_escape_string($from_id));
      $deleteFromId_result = mysql_query($deleteFromId_query);

    }

    // Checks if any edge still uses vertex with id 'to_id'
    $toIdCheck_query = sprintf("SELECT `id` FROM `edges` WHERE `is_deleted` = 0 AND (`from_id` = %d OR `to_id`= %d);",
              mysql_real_escape_string($to_id),
              mysql_real_escape_string($to_id));
    $toIdCheck_result = mysql_query($toIdCheck_query);

    if (mysql_num_rows($toIdCheck_result) === 0){

      // the vertex is not used anymore
      $deleteToId_query = sprintf("UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` = %d;",
            mysql_real_escape_string($to_id));
      $deleteToId_result = mysql_query($deleteToId_query);

    }

    return array(
      '1_select_vertices' => ($vertices!==false)?true:false,
      '2_delete_edge' => $delete_result,
      '3_formId_check' => $fromIdCheck_result,
      '4_delete_fromId_vertex' => $deleteFromId_result,
      '5_toId_check' => $toIdCheck_result,
      '6_delete_toId_vertex' => $deleteToId_result
      );
  }

  /*
   * Cutting an edge into two edges
   */
  public static function cutEdge($start_id, $dest_id, $new_lat, $new_lng, $new_alt, $edge_id){

    $newVertexAlreadyExists = self::getClosestVertex($new_lat, $new_lng, _closestPointRadius_edit);

    if ($newVertexAlreadyExists == null) {

      // Creates the new vertex and retrieves its id
      $newVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), %d);",
                mysql_real_escape_string($new_lng),
                mysql_real_escape_string($new_lat),
                mysql_real_escape_string($new_alt));
      $newVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              mysql_real_escape_string($new_lng),
              mysql_real_escape_string($new_lat));
      
      mysql_query('BEGIN');
      $newVertex_insert_result = mysql_query($newVertex_query);
      $newVertex_fetch_result  = mysql_query($newVertex_query_fetch);
      mysql_query('COMMIT');

      if( $newVertex_fetch_result !== false){
        $row = mysql_fetch_array($newVertex_fetch_result, MYSQL_ASSOC);
        $newVertex_id = intval($row['id']);
      } else {
        return false;
      }
      
    } else {
      $newVertex_id = intval($newVertexAlreadyExists['id']);
    }
    
    // Gets the two previous vertices
    $getStartVertex_query = sprintf("SELECT Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` WHERE `id` = %d;",
                            mysql_real_escape_string($start_id));
    $getStartVertex_result = mysql_query($getStartVertex_query);
    
    if ($getStartVertex_result !== false){
      $row = mysql_fetch_array($getStartVertex_result, MYSQL_ASSOC);
      $start_lat = floatval($row['lat']);
      $start_lng = floatval($row['lng']);
      $start_alt = intval($row['alt']);
    } else { return false; }

    $getDestVertex_query = sprintf("SELECT Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` WHERE `id` = %d;",
              mysql_real_escape_string($dest_id));
    $getDestVertex_result = mysql_query($getDestVertex_query);
    
    if ($getDestVertex_result !== false){
      $row = mysql_fetch_array($getDestVertex_result, MYSQL_ASSOC);
      $dest_lat = floatval($row['lat']);
      $dest_lng = floatval($row['lng']);
      $dest_alt = intval($row['alt']);
    } else { return false; }
    
    
    // -------------------------------
    // Update edge from start ---> new 
    $startNew_distance = GeoUtils::haversine($start_lat, $start_lng, $new_lat, $new_lng);
    $startNew_grade = $new_alt - $start_alt;

    $startNew_query = sprintf("UPDATE `edges` SET `distance` = %F, `grade`= %d, `to_id`= %d, `is_dirty`= 0 WHERE `id` = %d;",
              mysql_real_escape_string($startNew_distance),
              mysql_real_escape_string($startNew_grade),
              mysql_real_escape_string($newVertex_id),
              mysql_real_escape_string($edge_id));

    $startNew_result = mysql_query($startNew_query);

    // Retrieves type that we are going to use for the new edge
    $getType_query = sprintf("SELECT `type` FROM `edges` WHERE `id` = %d;",
                mysql_real_escape_string($edge_id));
    $getType_result = mysql_query($getType_query);
    
    if ($getType_result !== false){
      $row = mysql_fetch_array($getType_result, MYSQL_ASSOC);
      $type = intval($row['type']);
    } else { return false; }
    
    // -------------------------------
    // Update edge from new ---> dest 
    $newDest_distance = GeoUtils::haversine($new_lat, $new_lng, $dest_lat, $dest_lng);
    $newDest_grade = $dest_alt - $new_alt;

    $newDest_query = sprintf("INSERT INTO `edges` (`from_id`, `to_id`, `distance`, `grade`, `type`, `is_dirty`) 
                 VALUES ('%d', '%d', '%F', '%d', '%d', 0);",
            mysql_real_escape_string($newVertex_id),
            mysql_real_escape_string($dest_id),
            mysql_real_escape_string($newDest_distance),
            mysql_real_escape_string($newDest_grade),
            mysql_real_escape_string($type), 0);

    $newDest_result = mysql_query($newDest_query);

    return array(
      '1_insert_new_vertex' => $newVertex_insert_result,
      '2_fetch_new_vertex' => ($newVertex_fetch_result!==false)?true:false,
      '3_get_start_vertex' => ($getStartVertex_result!==false)?true:false,
      '4_get_dest_vertex' => ($getDestVertex_result!==false)?true:false,
      '5_start_new' => $startNew_result,
      '6_getType' => ($getType_result!==false)?true:false,
      '7_new_dest' => $newDest_result
      );
  }


  /*
   * Adding an edge
   */
  public static function addEdge($start_lat, $start_lng, $start_alt, $dest_lat, $dest_lng, $dest_alt, $type){

    $startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
    $destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

    // Create start vertex if it doesn't exist
    if ($startExistsAlready == null) {
      $startVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), '%d');",
              mysql_real_escape_string($start_lng),
              mysql_real_escape_string($start_lat),
              mysql_real_escape_string($start_alt));
      $startVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              mysql_real_escape_string($start_lng),
              mysql_real_escape_string($start_lat));
              
      mysql_query('BEGIN');
      $startVertex_insert_result = mysql_query($startVertex_query);
      $startVertex_fetch_result  = mysql_query($startVertex_query_fetch);
      mysql_query('COMMIT');

      if ($startVertex_fetch_result !== false){
        $row = mysql_fetch_array($startVertex_fetch_result, MYSQL_ASSOC);
        $startVertex_id = intval($row['id']);
      } else { return false; }
      
    } else {
      $startVertex_id = intval($startExistsAlready['id']);
    }

    // Create dest vertex if it doesn't exist
    if ($destExistsAlready == null) {

      $destVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), '%d');",
            mysql_real_escape_string($dest_lng),
            mysql_real_escape_string($dest_lat),
            mysql_real_escape_string($dest_alt));
      $destVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              mysql_real_escape_string($dest_lng),
              mysql_real_escape_string($dest_lat));
              
      mysql_query('BEGIN');
      $destVertex_insert_result = mysql_query($destVertex_query);
      $destVertex_fetch_result  = mysql_query($destVertex_query_fetch);
      mysql_query('COMMIT');

      if ($destVertex_fetch_result !== false){
        $row = mysql_fetch_array($destVertex_fetch_result, MYSQL_ASSOC);
        $destVertex_id = intval($row['id']);
      } else { return false;}
      
    } else {
      $destVertex_id = intval($destExistsAlready['id']);
    }

    // Creates the edge
    $distance = GeoUtils::haversine($start_lat, $start_lng, $dest_lat, $dest_lng);
    $grade = $dest_alt - $start_alt;

    $createEdge_query = sprintf("INSERT INTO `edges` (`from_id`, `to_id`, `distance`, `grade`, `type`) 
                 VALUES ('%d', '%d', '%F', '%d', '%d');",
            mysql_real_escape_string($startVertex_id),
            mysql_real_escape_string($destVertex_id),
            mysql_real_escape_string($distance),
            mysql_real_escape_string($grade),
            mysql_real_escape_string($type));

    // Executes the query
    $createEdge_result = mysql_query($createEdge_query);

    return array(
      '1_start_alreadyExisted' => !empty($startExistsAlready)?true:false,
      '2_dest_alreadyExisted' => !empty($destExistsAlready)?true:false,
      '3_start_insert' => $startVertex_insert_result,
      '4_start_fetch' => ($startVertex_fetch_result!==false)?true:false,
      '5_dest_insert' => $destVertex_insert_result,
      '6_dest_fetch' => ($destVertex_fetch_result!==false)?true:false,
      '7_create_edge' => $createEdge_result
    );

  }

  /*
   * Consolidate the database
   */
  public static function consolidate(){

    // Finds orphan vertices and deletes them
    $findOrphans_query = "UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` NOT IN 
              (SELECT `from_id` AS `id` FROM `edges` UNION
                SELECT `to_id` AS `id` FROM `edges`)";

    // Executes the query
    $findOrphans_result = mysql_query($findOrphans_query);

    // Finds edges of zero distance
    $zeroDistance_query = "UPDATE `edges` SET `is_deleted` = 1 WHERE `from_id` = `to_id`;";

    // Executes the query
    $zeroDistance_result = mysql_query($zeroDistance_query);

    // Find non-deleted edges linking to at least one deleted vertex
    $deletedInconsistencies_query = "UPDATE `edges` SET `is_deleted` = 1 WHERE `from_id` IN
                  (SELECT `id` FROM `vertices` WHERE `is_deleted` = 1) OR `to_id` IN
                  (SELECT `id` FROM `vertices` WHERE `is_deleted` = 1)";

    // Executes the query
    $deletedInconsistencies_result = mysql_query($deletedInconsistencies_query);

    // Updates distances and grades
    $updateDistances_query = "SELECT consolidate() AS nb;";
    
    // Executes the query
    $updateDistances_result = mysql_query($updateDistances_query);

    if ($updateDistances_result !== false) {
      $row = mysql_fetch_array($updateDistances_result, MYSQL_ASSOC);
      $updateDistances_result_nb = $row['nb'];
    } else $updateDistances_result_nb = false;

    return array(
      '1_find_orphans' => $findOrphans_result,
      '2_zero_distance' => $zeroDistance_result,
      '3_delete_inconsistencies' => $deletedInconsistencies_result,
      '4_update_distances_and_grades' => $updateDistances_result_nb
    );
  }
  
}
