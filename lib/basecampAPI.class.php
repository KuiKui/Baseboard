<?php 

class basecampAPI
{
  protected $url;
  protected $token;
  
  public function __construct($url, $token)
  {
    $this->url = $url;
    $this->token = $token;
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
    
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $this->url.$request);
    curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($session, CURLOPT_USERPWD, $this->token.':X');
    curl_setopt($session, CURLOPT_TIMEOUT, 5);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_HEADER, 'Accept: application/xml');
    curl_setopt($session, CURLOPT_HEADER, 'Content-Type: application/xml');
    $response = curl_exec($session);
    $status   = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if (!$response) {
        throw new RuntimeException(sprintf("%s (%s)", curl_error($session), $request));
    }
    curl_close($session);
    if($status != 200)
    {
      throw new RuntimeException(sprintf("Erreur %d, %s (%s)", $status, $response, $request));
    }
  
    return $this->xmlToArray($response);
  }
  
  protected function xmlToArray($xmlString)
  {
    $xml = simplexml_load_string($xmlString);
    $json = json_encode($xml);
    $array = json_decode($json, true);
    return $this->clearXmlStyle($array);
  }
  
  protected function clearXmlStyle($element)
  {
    if(!array_key_exists('@attributes', $element))
    {
      return $element;
    }
  
    $tmp = array_values(array_slice($element, 1, 1));
    return array_shift($tmp);
  }
}
