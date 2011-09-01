<?php

class twitterAPI
{
  protected $curlConnexion;
  
  public function __construct(curlConnexion $curlConnexion)
  {
    $this->curlConnexion = $curlConnexion;
  }

  public function get($request)
  {
    $json = $this->curlConnexion->get($request);
    return json_decode($json, true);
  }
}
