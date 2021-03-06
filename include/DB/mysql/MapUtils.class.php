<?php

class MapUtils implements MapUtilsInterface {

  /*
   * GET The means availables and their respective speeds
   */
  public static function getMeansAndSpeeds(){

    $db = DBConnection::db();

    $getMeansAndSpeeds_query = "SELECT m.`id` AS id, m.`description` AS description, m.`slug` AS slug, GROUP_CONCAT(CONCAT('{\"type_id\":', s.`type_id`, ', \"speeds\": [', s.`flat_speed`, ',', s.`grade_speed`, ']}')) AS explorables
                       FROM `means` m
                       LEFT JOIN `speeds` s ON (m.`id`= s.`mean_id`)
                       GROUP BY mean_id ";

    $statement = $db->prepare($getMeansAndSpeeds_query);
    $exe = $statement->execute();

    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the info
      $means = array();

      while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {

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

      return $means;
    }

  }

  /*
   * Restricts a query for a vertex (of table v) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictForVertex($bounds, $POI){

    if ($POI !== null) {

      // Poi should be included
      $where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()),
            floatval($bounds->SE_lng()),
            floatval($bounds->SE_lat()),
            floatval($POI->lng()),
            floatval($POI->lat()),
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()));

    } else {

      // Just a polyline without the POI
      $where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()),
            floatval($bounds->SE_lng()),
            floatval($bounds->SE_lat()),
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()));

    }

    return $where_clause;
  }

  /*
   * Restricts a query for an edge (of table v and v_dest) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictForEdgeBBox($bounds, $POI){

    if ($POI !== null) {

      // Poi should be included
      $where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()),
            floatval($bounds->SE_lng()),
            floatval($bounds->SE_lat()),
            floatval($POI->lng()),
            floatval($POI->lat()),
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()));
    
    } else {

      // Just a polygon without the POI
      $where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()),
            floatval($bounds->SE_lng()),
            floatval($bounds->SE_lat()),
            floatval($bounds->NW_lng()),
            floatval($bounds->NW_lat()));
    }

    return $where_clause;
  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getVerticesIn($bounds, $POI){

    $db = DBConnection::db();

    $getVerticesIn_query = "SELECT `id`, Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` v";

    $getVerticesIn_query .= self::restrictForVertex($bounds, $POI);
    $getVerticesIn_query .= " AND v.`is_deleted` = 0";

    $statement = $db->prepare($getVerticesIn_query);
    $exe = $statement->execute();

    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the vertices
      $verticesArray = array();
      while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $verticesArray[] = new Vertex($row["id"], $row["lat"], $row["lng"], $row["alt"]); 
      }
      return $verticesArray;
    }

  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   * AND their 1th children
   */
  public static function getVerticesAndChildrenIn($bounds, $POI){

    $db = DBConnection::db();

    // Extends the bounds
    $bounds = GeoUtils::extendBBox($bounds, null);

    $getVerticesAndChildrenIn_query = "SELECT v.`id` AS id, Y(v.`point`) AS lat, X(v.`point`) AS lng, v.`elevation` AS alt, 
                    group_concat(CONCAT('{\"id\":',e.`to_id`, ', \"path_id\":', e.`id`, ', \"distance\":', e.`distance`, ', \"grade\":', e.`grade`, ', \"type\":', e.`type`,', \"secable\":', t.`secable`, '}')) AS children FROM `vertices` v
                    LEFT JOIN `edges` e ON (e.`from_id` = v.`id` AND e.`is_deleted` = 0)
                    JOIN `types` t ON (e.`type` = t.`id`)";

    $getVerticesAndChildrenIn_query .= self::restrictForVertex($bounds, $POI);
    $getVerticesAndChildrenIn_query .= " GROUP BY v.`id`";

    $statement = $db->prepare($getVerticesAndChildrenIn_query);
    $exe = $statement->execute();

    // Returns true if the query was well executed
    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the vertices
      $vertices = array();
      while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {

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
  public static function getEdgesIn($bounds, $POI, $restrictToType){

    $db = DBConnection::db();
    
    $getEdgesIn_query = "SELECT e.`id` AS id, Y(v.`point`) AS lat_start, X(v.`point`) AS lng_start, v.`elevation` AS alt_start, v.`id` AS id_start,
                  Y(v_dest.`point`) AS lat_dest, X(v_dest.`point`) AS lng_dest, v_dest.`elevation` AS alt_dest, v_dest.`id` AS id_dest,
                  e.`distance` AS distance, e.`grade` AS grade, e.`type` AS type
                  FROM `edges` e
                  INNER JOIN `vertices` v ON v.`id` = e.`from_id`
                  INNER JOIN `vertices` v_dest ON v_dest.`id` = e.`to_id`";

    $getEdgesIn_query .= self::restrictForEdgeBBox($bounds, $POI);
    $getEdgesIn_query .= " AND e.`is_deleted` = 0";

    if ($restrictToType != null && $restrictToType != 0) $getEdgesIn_query .= " AND e.`type` = ".intval($restrictToType);
    
    $statement = $db->prepare($getEdgesIn_query);
    $exe = $statement->execute();

    // Returns true if the query was well executed
    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the edges
      $edges = array();
      while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $edges[] = array(
                  'id' => intval($row["id"]), 
                  'start' => new Vertex($row["id_start"], $row["lat_start"], $row['lng_start'], $row["alt_start"]),
                  'dest' => new Vertex($row["id_dest"], $row["lat_dest"], $row['lng_dest'], $row["alt_dest"]),
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
  public static function getClosestVertex ($lat, $lng, $radius_in_m){

    $db = DBConnection::db();
    
    // Extends the Bounding Box
    $bounds = GeoUtils::extendBBox(new Bounds(array("NW_lat" => $lat, "NW_lng" => $lng, "SE_lat" => $lat, "SE_lng" => $lng)), $radius_in_m, null);

    $getClosest_query = "CALL getClosest(:lat, :lng, :radius, :NW_lat, :NW_lng, :SE_lat, :SE_lng);";

    $statement = $db->prepare($getClosest_query);
      $statement->bindParam(':lat', $lat, PDO::PARAM_STR);
      $statement->bindParam(':lng', $lng, PDO::PARAM_STR);
      $statement->bindParam(':radius', $radius_in_m, PDO::PARAM_STR);
      $statement->bindParam(':NW_lng', $bounds->NW_lng(), PDO::PARAM_STR);
      $statement->bindParam(':NW_lat', $bounds->NW_lat(), PDO::PARAM_STR);
      $statement->bindParam(':SE_lng', $bounds->SE_lng(), PDO::PARAM_STR);
      $statement->bindParam(':SE_lat', $bounds->SE_lat(), PDO::PARAM_STR);

    // Executes the query
    $exe = $statement->execute();

    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the info
      $closest = $statement->fetch(PDO::FETCH_ASSOC);
      if (!$closest) return null;
      
      return array(
            'point' => new Vertex($closest["id"], $closest["lat"], $closest['lng'], $closest["alt"]),
            'distance' => floatval($closest["distance"]),
          );
    }

  }

}
