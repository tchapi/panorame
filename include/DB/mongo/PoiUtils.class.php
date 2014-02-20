<?php

class PoiUtils implements PoiUtilsInterface {

  /*
   * GET The POI categories and providers
   */
  public static function getPOIProviders(){

    global $DBConnection;
    $db = $DBConnection->getDB();

    $poi_cats = iterator_to_array($db->poi_categories->find()->sort(array("_id" => 1)));
    $pois = array();

    // Fetch the means
    foreach($poi_cats as $cat) {
      
      $itemsAsPHPArray = iterator_to_array($db->poi_providers->find(array('category_id' => $cat['_id'])));

      $items = array();

      foreach ($itemsAsPHPArray as $item){
        $items[$item['_id']] = $item['label'];
      }

      array_push($pois, array('id' => $cat['_id'], 'label' => $cat['label'], 'icon' => $cat['icon'], 'items' => $items));
      
    } 

    return $pois;

  }

}
