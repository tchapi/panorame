<?php

class GeoUtils {
  
  /*
   * HAVERSINE function for distance between two points on Earth
   */
  public static function haversine($lat_1,$lng_1,$lat_2,$lng_2) {

    $sin_lat   = sin(deg2rad($lat_2  - $lat_1)  / 2.0);
    $sin2_lat  = $sin_lat * $sin_lat;

    $sin_lng  = sin(deg2rad($lng_2 - $lng_1) / 2.0);
    $sin2_lng = $sin_lng * $sin_lng;

    $cos_lat_1 = cos($lat_1);
    $cos_lat_2 = cos($lat_2);
     
    $sqrt      = sqrt($sin2_lat + ($cos_lat_1 * $cos_lat_2 * $sin2_lng));
    
    $distance  = 2.0 * _earth_radius * asin($sqrt);
     
    return $distance;

  }
    
  /*
   * Extends the given bounding box by $extent, to allow for more smooth panning in the view
   * Allows to have more routes in the view as well, by capilarity
   */
  public static function extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng, $extent){

    if ($extent == null) $extent = _extendBoundsPointRadius;

    $ratio = $extent/_earth_radius;
    
    // Calculating new NW point
    $lat_rad = $NW_lat*pi()/180;
    $lng_rad = $NW_lng*pi()/180;
    $cos_lat_rad = cos($lat_rad);
    $sin_lat_rad = sin($lat_rad);

    $brng = (315/180)*pi(); // bearing is -45°
    $newNW_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
    $newNW_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($newNW_lat*pi()/180)))* 180 / pi();

    // Calculating new SE point
    $lat_rad = $SE_lat*pi()/180;
    $lng_rad = $SE_lng*pi()/180;
    $cos_lat_rad = cos($lat_rad);
    $sin_lat_rad = sin($lat_rad);

    $brng = 135/180*pi(); // Bearing is 135°
    $newSE_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
    $newSE_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($newSE_lat*pi()/180)))* 180 / pi();

    return array('NW_lat' => $newNW_lat, 'NW_lng' => $newNW_lng, 'SE_lat' => $newSE_lat, 'SE_lng' => $newSE_lng);

  }

  /*
   * Given a Lat/Lng array, calculate the bounding box in a circular model
   */
  public static function calculateBBox($latsArray, $lngsArray){

    // Sort the points south to north (lat)
    $southest = min($latsArray);
    $northest = max($latsArray); 

    // Sort the points west to east (long)
    sort($lngsArray);
    $count = count($lngsArray);

    $max = 0;
    for($i=0; $i < $count; $i++){

      if ($i == $count - 1)
        $j = 0;
      else
        $j = $i + 1;

      $current = abs($lngsArray[$j] - $lngsArray[$i]);

      if ($current > $max) { $westest = $j; $eastest = $i; $max = $current; }

    }

    return array('NW_lat' => $northest, 'NW_lng' => $lngsArray[$westest], 'SE_lat' => $southest, 'SE_lng' => $lngsArray[$eastest]);

  }

}