<?php

class hudsonAPI
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
  
  public function hasFailedHudsonJobs($jobs = null)
  {
    $infos = $this->get('json');
    
    if(is_array($infos) && key_exists('jobs', $infos))
    {
      foreach($infos['jobs'] as $scanedJobs)
      {
        if(!is_null($jobs) && is_array($jobs) && !in_array($scanedJobs['name'], $jobs))
        {
          continue;
        }
      
        if(strpos($scanedJobs['color'], 'red') !== false || strpos($scanedJobs['color'], 'yellow') !== false)
        {
          return true;
        }
      }
    }
  
    return false;
  }
}
