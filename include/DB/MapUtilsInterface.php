<?php

interface MapUtilsInterface {

  public static function getMeansAndSpeeds();
  public static function getVerticesIn($bounds, $POI);
  public static function getVerticesAndChildrenIn($bounds, $POI);
  public static function getEdgesIn($bounds, $POI, $restrictToType);

  /*
   * Returns the closest vertex as an array
   */
  public static function getClosestVertex($lat, $lng, $radius_in_m);

}
