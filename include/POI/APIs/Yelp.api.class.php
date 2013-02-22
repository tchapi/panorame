<?php

  /* YELP API
   *
   */

class Yelp extends PoiAPI {

  public function __construct($configuration){

    $this->parameters  = $configuration;
    $this->serviceName = "Yelp";

  }

}
