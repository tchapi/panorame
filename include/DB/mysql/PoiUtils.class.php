<?php

class PoiUtils implements PoiUtilsInterface {
  
  /*
   * GET The POI categories and providers
   */
  public static function getPOIProviders(){

    $db = DBConnection::db();

    $getPOIs_query = "SELECT p.`id`, p.`label`, p.`icon`, GROUP_CONCAT( CONCAT( '\"', i.`id`, '\": \"', i.`label`, '\"' ) ) AS items 
                        FROM  `poi_categories` p
                        LEFT JOIN  `poi_providers` i ON p.`id` = i.`category_id`
                        GROUP BY p.id";

    $statement = $db->prepare($getPOIs_query);

    // Executes the query
    $exe = $statement->execute();
    
    // Returns true if the query was well executed
    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the info
      $pois = array();

      while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $items = array();

        if ($row["items"] != null) {
          $items = json_decode('{'.$row["items"].'}', true);
          array_push($pois, array('id' => $row['id'], 'label' => $row['label'], 'icon' => $row['icon'], 'items' => $items));
        }

      }

      return $pois;
    }

  }

}
