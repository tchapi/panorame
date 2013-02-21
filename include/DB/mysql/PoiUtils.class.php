<?php

class PoiUtils {
  
  /*
   * GET The POI categories and providers
   */
  public static function getPOIProviders(){

    global $DBConnection;

    $getPOIs_query = "SELECT p.`id`, p.`label`, p.`icon`, GROUP_CONCAT( CONCAT( '\"', i.`id`, '\": \"', i.`label`, '\"' ) ) AS items 
                        FROM  `poi_categories` p
                        LEFT JOIN  `poi_providers` i ON p.`id` = i.`category_id`
                        GROUP BY p.id";

    $getPOIs_result = $DBConnection->link->query($getPOIs_query);

    // Returns true if the query was well executed
    if (!$getPOIs_result || $getPOIs_result == false ) {
      return false;
    } else {
      // Fetch the types
      $pois = array();
        while ($row = $getPOIs_result->fetch_assoc()) {
          
          $items = array();

          if ($row["items"] != null) {
            $items = json_decode('{'.$row["items"].'}', true);
            array_push($pois, array('id' => $row['id'], 'label' => $row['label'], 'icon' => $row['icon'], 'items' => $items));
          }

        }
      }

      return $pois;

  }

  /*
   * GET Results from a POI provider
   */

  public static function getPOIResultsIn($provider, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $term = null){

    return false;

  }
  
}
