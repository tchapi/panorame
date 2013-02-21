<?php

class MapUtils {

  /*
   * GET The means availables and their respective speeds
   */
  public static function getMeansAndSpeeds(){

    global $DBConnection;

    $getMeansAndSpeeds_query = "SELECT m.`id` AS id, m.`description` AS description, m.`slug` AS slug, GROUP_CONCAT(CONCAT('{\"type_id\":', s.`type_id`, ', \"speeds\": [', s.`flat_speed`, ',', s.`grade_speed`, ']}')) AS explorables
                       FROM `means` m
                       LEFT JOIN `speeds` s ON (m.`id`= s.`mean_id`)
                       GROUP BY mean_id ";

    $getMeansAndSpeeds_result = $DBConnection->link->query($getMeansAndSpeeds_query);

    // Returns true if the query was well executed
    if (!$getMeansAndSpeeds_result || $getMeansAndSpeeds_result == false ) {
      return false;
    } else {
      // Fetch the types
      $means = array();
        while ($row = $getMeansAndSpeeds_result->fetch_assoc()) {
          
          $explorables = array();

          if ($row["explorables"] == null){
            $explorables = null;
          } else {
            $explorablesAsPHPArray = json_decode('['.$row["explorables"].']');
            foreach ($explorablesAsPHPArray as $explorable){
              $explorables[$explorable->type_id] = $explorable->speeds;
            }
          }

          array_push($means, array('id' => $row['id'], 'slug' => $row['slug'], 'description' => $row['description'], 'explorables' => $explorables));
        
        }
      }

      return $means;

  }

  /*
   * Restricts a query for a vertex (of table v) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictForVertex($query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    global $DBConnection;
    
    if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

      // Poi should be included
      $where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat),
            $DBConnection->link->escape_string($SE_lng),
            $DBConnection->link->escape_string($SE_lat),
            $DBConnection->link->escape_string($POI_lng),
            $DBConnection->link->escape_string($POI_lat),
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat));

    } else {

      // Just a polyline without the POI
      $where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat),
            $DBConnection->link->escape_string($SE_lng),
            $DBConnection->link->escape_string($SE_lat),
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat));
      
    }

    return $query.$where_clause;
  }

  /*
   * Restricts a query for an edge (of table v and v_dest) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictForEdgeBBox($query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    global $DBConnection;
    
    if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

      // Poi should be included
      $where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat),
            $DBConnection->link->escape_string($SE_lng),
            $DBConnection->link->escape_string($SE_lat),
            $DBConnection->link->escape_string($POI_lng),
            $DBConnection->link->escape_string($POI_lat),
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat));

    } else {

      // Just a polygon without the POI
      $where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat),
            $DBConnection->link->escape_string($SE_lng),
            $DBConnection->link->escape_string($SE_lat),
            $DBConnection->link->escape_string($NW_lng),
            $DBConnection->link->escape_string($NW_lat));   

    }

    return $query.$where_clause;
  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    global $DBConnection;

    $getVerticesIn_query = sprintf("SELECT `id`, Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` v");

    $getVerticesIn_query = self::restrictForVertex($getVerticesIn_query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    $getVerticesIn_query .= " AND v.`is_deleted` = 0";

    $queryResult = $DBConnection->link->query($getVerticesIn_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the vertices
      $verticesArray = array();
      while ($row = $queryResult->fetch_assoc()) {
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

    global $DBConnection;

    // Extends the bounds
    $b = GeoUtils::extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng, null);

    $getVerticesAndChildrenIn_query = sprintf("SELECT v.`id` AS id, Y(v.`point`) AS lat, X(v.`point`) AS lng, v.`elevation` AS alt, 
                    group_concat(CONCAT('{\"id\":',e.`to_id`, ', \"path_id\":', e.`id`, ', \"distance\":', e.`distance`, ', \"grade\":', e.`grade`, ', \"type\":', e.`type`,', \"secable\":', t.`secable`, '}')) AS children FROM `vertices` v
                    LEFT JOIN `edges` e ON (e.`from_id` = v.`id` AND e.`is_deleted` =0)
                    JOIN `types` t ON (e.`type` = t.`id`)");

    $getVerticesAndChildrenIn_query  = self::restrictForVertex($getVerticesAndChildrenIn_query, $b['NW_lat'], $b['NW_lng'], $b['SE_lat'], $b['SE_lng'], $POI_lat, $POI_lng);
    $getVerticesAndChildrenIn_query .= " GROUP BY v.`id`";

    $queryResult = $DBConnection->link->query($getVerticesAndChildrenIn_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the vertices
      $vertices = array();
      while ($row = $queryResult->fetch_assoc()) {
        
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
  public static function getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $restrictToType, $POI_lat, $POI_lng){

    global $DBConnection;
    
    $getEdgesIn_query = "SELECT e.`id` AS id, Y(v.`point`) AS lat_start, X(v.`point`) AS lng_start, v.`elevation` AS alt_start, v.`id` AS id_start,
                  Y(v_dest.`point`) AS lat_dest, X(v_dest.`point`) AS lng_dest, v_dest.`elevation` AS alt_dest, v_dest.`id` AS id_dest,
                  e.`distance` AS distance, e.`grade` AS grade, e.`type` AS type
                  FROM `edges` e
                  INNER JOIN `vertices` v ON v.`id` = e.`from_id`
                  INNER JOIN `vertices` v_dest ON v_dest.`id` = e.`to_id`";

    $getEdgesIn_query  = self::restrictForEdgeBBox($getEdgesIn_query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    $getEdgesIn_query .= " AND e.`is_deleted` = 0";

    if ($restrictToType != null && $restrictToType != 0) $getEdgesIn_query .= " AND e.`type` = ".intval($restrictToType);
    
    $queryResult = $DBConnection->link->query($getEdgesIn_query);

    // Returns true if the query was well executed
    if (!$queryResult || $queryResult == false ) {
      return false;
    } else {
      // Fetch the edges
      $edges = array();
      while ($row = $queryResult->fetch_assoc()) {
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
  public static function getClosestVertex($lat, $lng, $radius_in_m){

    global $DBConnection;
    
    $bbox = GeoUtils::extendBBox($lat, $lng, $lat, $lng, $radius_in_m, null);

    $getClosest_query = sprintf("CALL getClosest(%F, %F, %d, %F, %F, %F, %F);",
      $DBConnection->link->escape_string($lat),
      $DBConnection->link->escape_string($lng),
      $DBConnection->link->escape_string($radius_in_m),
      $DBConnection->link->escape_string($bbox['NW_lat']),
      $DBConnection->link->escape_string($bbox['NW_lng']),
      $DBConnection->link->escape_string($bbox['SE_lat']),
      $DBConnection->link->escape_string($bbox['SE_lng']));

    $res = $DBConnection->link->multi_query($getClosest_query);

    $closest = $DBConnection->link->use_result();
    $closest = $closest->fetch_assoc();

    // Flush ....
    while ($DBConnection->link->more_results() && $DBConnection->link->next_result());
   
    if ($closest == null || $closest == false) return null;

    return array(
            'id' => intval($closest["id"]), 
            'point' => array(
              'lat' => floatval($closest["lat"]),
              'lng' => floatval($closest["lng"]),
              'alt' => intval($closest["alt"])
            ),
            'distance' => floatval($closest["distance"]),
          );

  }

}
