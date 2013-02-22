<?php 

  /* Base API class
   *
   */

abstract class PoiAPI {

  protected $serviceName;
  protected $parameters;

  abstract protected function call();
  abstract protected function constructUrl();

  // Common printing method
  public function __toString() {
      return "API : " . $this->getServiceName() . "";
  }

  public function setServiceName($serviceName){
    $this->serviceName = $serviceName;
    return $this
  }

  public function getServiceName(){
    return $this->serviceName;
  }

}
