<?php

class BOUNDS {
  
  private $NW_lat;
  private $NW_lng;
  private $SE_lat;
  private $SE_lng;

  public function __construct($boundsAsArray) {

    $this->NW_lat = (float) $boundsAsArray['NW_lat'];
    $this->NW_lng = (float) $boundsAsArray['NW_lng'];
    $this->SE_lat = (float) $boundsAsArray['SE_lat'];
    $this->SE_lng = (float) $boundsAsArray['SE_lng'];

  }

  public function NW_lat() {
    return $this->NW_lat;
  }

  public function NW_lng() {
    return $this->NW_lng;
  }

  public function SE_lat() {
    return $this->SE_lat;
  }

  public function SE_lng() {
    return $this->SE_lng;
  }

  public function toArray() {
    return array(
      'NW_lat' => $this->NW_lat, 
      'NW_lng' => $this->NW_lng, 
      'SE_lat' => $this->SE_lat, 
      'SE_lng' => $this->SE_lng, 
    );
  }

}
