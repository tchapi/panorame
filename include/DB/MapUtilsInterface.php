<?php

interface MapUtilsInterface {

  public static function getMeansAndSpeeds();
  public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
  public static function getVerticesAndChildrenIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
  public static function getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $restrictToType, $POI_lat, $POI_lng);

  /*
   * Returns the closest vertex as an array
   */
  public static function getClosestVertex($lat, $lng, $radius_in_m);

}
