<?php 

class basecampAPI
{
  protected $curlConnexion;
  
  public function __construct(curlConnexion $curlConnexion)
  {
    $this->curlConnexion = $curlConnexion;
  }

  public function get($request, $param = null, $param2 = null)
  {
    $xml = $this->curlConnexion->get($request, $param, $param2);
    return $this->clearBasecampXml(json_decode(json_encode(simplexml_load_string($xml)), true));
  }
  
  protected function clearBasecampXml($xml)
  {
    if(!array_key_exists('@attributes', $xml))
    {
      return $xml;
    }
  
    $tmp = array_values(array_slice($xml, 1, 1));
    return array_shift($tmp);
  }
}
