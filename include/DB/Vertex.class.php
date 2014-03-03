<?php

class Vertex {
  
  private $id;
  private $latitude;
  private $longitude;
  private $altitude;

  public function __construct($id, $latitude, $longitude, $altitude) {

    $this->id = $id;
    $this->latitude = (float) $latitude;
    $this->longitude = (float) $longitude;
    $this->altitude = (int) $altitude;

  }

  public function getId() {
    return $this->id;
  }

  public function getLatitude() {
    return $this->latitude;
  }
  public function lat() {
    return $this->latitude;
  }

  public function getLongitude() {
    return $this->longitude;
  }
  public function lng() {
    return $this->longitude;
  }

  public function getAltitude() {
    return $this->altitude;
  }
  public function alt() {
    return $this->altitude;
  }

  public function toArray() {
    return array(
      'id' => $this->id, 
      'point' => array(
        'lat' => $this->latitude,
        'lng' => $this->longitude,
        'alt' => $this->altitude
      )
    );
  }

}
