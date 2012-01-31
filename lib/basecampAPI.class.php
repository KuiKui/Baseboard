<?php 

class basecampAPI
{
  protected $restConnection;
  
  public function __construct(RESTConnection $restConnection)
  {
    $this->restConnection = $restConnection;
  }

  public function get($request)
  {
    if($this->restConnection->request($request))
    {
      $res = $this->xml2array(simplexml_load_string($this->restConnection->getResponseBody()));

      // if there's a result, we don't need the 1st array (container)
      if(is_array($res) && count($res)>0)
      {
        $res = array_values($res);
        return $res[0];
      }
    }
    return null;
  }

  protected function xml2array($xml)
  {
    $arr = array();
    foreach ($xml->children() as $child)
    {
      $name = $child->getName();
      if(count($child->children()) == 0)
        $arr[$name] = strval($child);
      else
        $arr[$name][] = $this->xml2array($child);
    }
    return $arr;
  }


}
