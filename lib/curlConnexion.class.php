<?php

class curlConnexion
{
  protected $serviceUrl;
  protected $serviceContentType;
  protected $serviceUser;
  protected $servicePassword;
  
  public function __construct($serviceUrl, $serviceContentType = 'json', $serviceUser = null, $servicePassword = null)
  {
    $this->serviceUrl         = $serviceUrl;
    $this->serviceContentType = $serviceContentType;
    $this->serviceUser        = $serviceUser;
    $this->servicePassword    = $servicePassword;
  }
  
  public function get($request, $param = null, $param2 = null)
  {
    if(strlen($param) > 0)
    {
      $request = preg_replace( '/#\{[\w-]*\}/', $param, $request, 1);
    }

    if(strlen($param2) > 0)
    {
      $request = preg_replace( '/#\{[\w-]*\}/', $param2, $request, 1);
    }
    
    if(!in_array($this->serviceContentType, array('json', 'xml')))
    {
      throw new RuntimeException(sprintf("Content-Type inconnu : %s (%s%s)", $this->serviceContentType, $this->serviceUrl, $request));
    }
    
    try
    {
      $session = curl_init();
      curl_setopt($session, CURLOPT_URL, $this->serviceUrl.$request);
      curl_setopt($session, CURLOPT_TIMEOUT, 5);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($session, CURLOPT_HEADER, 'Accept: application/'.$this->serviceContentType);
      curl_setopt($session, CURLOPT_HEADER, 'Content-Type: application/'.$this->serviceContentType);
      if(!is_null($this->serviceUser) && !is_null($this->servicePassword))
      {
        curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($session, CURLOPT_USERPWD, $this->serviceUser.':'.$this->servicePassword);
      }
      $response = curl_exec($session);
      $status   = curl_getinfo($session, CURLINFO_HTTP_CODE);
      if (!$response)
      {
        throw new RuntimeException(sprintf("%s (%s%s)", curl_error($session), $this->serviceUrl, $request));
      }
      curl_close($session);
      if($status != 200)
      {
        throw new RuntimeException(sprintf("Erreur %d (%s%s)", $status, $this->serviceUrl, $request));
      }
    }
    catch(Exception $e)
    {
      // Faire quelques choses...
    }

    return $response;
  }
}
