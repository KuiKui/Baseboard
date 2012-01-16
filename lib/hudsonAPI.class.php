<?php

class hudsonAPI
{
  protected $restConnection;
  
  public function __construct(RESTConnection $restConnection)
  {
    $this->restConnection = $restConnection;
  }

  public function get($request)
  {
    if($this->restConnection->request($request))
      return json_decode($this->restConnection->getResponseBody(), true);
    return null;
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
