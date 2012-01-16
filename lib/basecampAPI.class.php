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
      return $this->xml2array(simplexml_load_string($this->restConnection->getResponseBody()));
    }
    return null;
  }

  protected function xml2array($xml)
  {
    $tmp = json_decode(json_encode($xml), true);

    // If xml is a list of entities (type=array)
    if(array_key_exists('@attributes', $tmp))
    {
      $tmp =  array_values(array_slice($tmp, 1, 1));

      if(count($tmp)>0)
      {
        return $tmp[0];
      }
    }

    return $tmp;
  }
  /*
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
  */

}
