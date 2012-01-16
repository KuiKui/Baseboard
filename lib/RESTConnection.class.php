<?php


// PHP class used to make requests to REST APIs
class RESTConnection
{
  const GET     = 0; // load/retrieve
  const POST    = 1; // create
  const PUT     = 2; // update (replacing old entity, removing undefined fields)
  const DELETE  = 3; // delete
  const PATCH   = 4; // update (modifying fields from old entity)

  static $acceptedVerbs = array (self::GET, self::POST, self::PUT, self::DELETE, self::PATCH);

  protected $serviceUrl;
  protected $serviceUser;
  protected $servicePassword;
  protected $serviceTimeout = 900;
  protected $serviceRequestUserAgent;
  protected $serviceRequestHeader;

  protected $compatibilityMode = false;

  protected $lastResponseHeader;
  protected $lastResponseBody;
  protected $lastResponseInfo;
  protected $lastResponseError;


  public function __construct($serviceUrl, $serviceRequestHeader = array(), $serviceUser = null, $servicePassword = null)
  {
    $this->serviceUrl           = $serviceUrl;
    $this->serviceRequestHeader = $serviceRequestHeader;
    $this->serviceUser          = $serviceUser;
    $this->servicePassword      = $servicePassword;
  }

  public function setServiceRequestHeader($requestHeader)
  {
    $this->serviceRequestHeader = $requestHeader;
  }
  public function getServiceRequestHeader()
  {
    return $this->serviceRequestHeader;
  }

  public function setServiceRequestUserAgent($requestUserAgent)
  {
    $this->serviceRequestUserAgent = $requestUserAgent;
  }
  public function getServiceRequestUserAgent()
  {
    return $this->serviceRequestUserAgent;
  }

  public function setServiceTimeout($timeout)
  {
    $this->serviceTimeout = $timeout;
  }
  public function getServiceTimeout()
  {
    return $this->serviceTimeout;
  }

  public function setServiceUrl($url)
  {
    $this->serviceUrl = $url;
  }
  public function getServiceUrl()
  {
    return $this->serviceUrl;
  }

  public function setServiceCredentials($userName, $password)
  {
    $this->serviceUser = $userName;
    $this->servicePassword = $password;
  }

  public function setCompatibilityMode($mode)
  {
    $this->compatibilityMode = $mode;
  }
  public function getCompatibilityMode()
  {
    return $this->compatibilityMode;
  }

  protected function flushLastResponse()
  {
    $this->lastResponseHeader = null;
    $this->lastResponseBody = null;
    $this->lastResponseInfo = null;
    $this->lastResponseError= null;
  }

  public function getResponseHeader()
  {
    return $this->lastResponseHeader;
  }

  public function getResponseBody()
  {
    return $this->lastResponseBody;
  }

  public function getResponseInfo()
  {
    return $this->lastResponseInfo;
  }

  public function getLastError()
  {
    return $this->lastResponseError;
  }

  public function getLastStatusCode()
  {
    if(isset($this->lastResponseInfo))
      return $this->lastResponseInfo['http_code'];

    return null;
  }


  public function request($ressourceUrl, $params = null, $verb = self::GET, $overridingVerb = null)
  {
    // flush last response
    $this->flushLastResponse();

    // Compatibility mode if server only supports GET/POST or firewall blocks some verbs
    if($this->compatibilityMode && $verb!=self::GET && $verb!=self::POST)
    {
      $overridingVerb = $verb;
    }

    // Override specified verb if needed (might be needed to force GET method as a POST if parameters are too long)
    if(!is_null($overridingVerb))
    {
      if( !in_array($overridingVerb, self::$acceptedVerbs))
        throw new InvalidArgumentException(sprintf("Unsupported overriding HTTP Verb: %s", $overridingVerb));

      // the overriding verb is valid, forcing current $verb to POST if user forgot to do so
      $verb = self::POST;
    }

    $ch = curl_init();

    $this->setAuth($ch);
    $this->setCurlOpts($ch, $ressourceUrl, $overridingVerb);

    try
    {
      switch ($verb)
      {
        case self::GET:
          $this->executeGet($ch);
          break;
        case self::POST:
          $this->executePost($ch, $params);
          break;
        case self::PUT:
          $this->executePut($ch, $params);
          break;
        case self::DELETE:
          $this->executeDelete($ch, $params);
          break;
        case self::PATCH:
          $this->executePatch($ch, $params);
          break;
        default:
          throw new InvalidArgumentException(sprintf("Unsupported HTTP Verb: %s", $verb));
          break;
      }
    }
    catch (InvalidArgumentException $e)
    {
      curl_close($ch);
      throw $e;
    }
    catch (Exception $e)
    {
      curl_close($ch);
      throw $e;
    }

    return !is_null($this->lastResponseBody) && $this->getLastStatusCode()>=200 && $this->getLastStatusCode()<300;
  }


  protected function formatData ($data)
  {
    if (is_null($data))
      return null;

    // if passed data is an array, urlencode it (val1=foo&val2=bar...)
    if (is_array($data))
    {
      return http_build_query($data, '', '&');
    }

    return $data;

  }

  protected function executeGet ($ch)
  {
    curl_setopt($ch, CURLOPT_HTTPGET, true);  // reset http get just in case
    $this->doExecute($ch);
  }

  protected function executePost ($ch, $data)
  {
    $req = $this->formatData($data);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_POST, true);

    $this->doExecute($ch);
  }

  protected function executePut ($ch, $data)
  {
    curl_setopt($ch, CURLOPT_PUT, true);

    $this->executeStreamData($ch, $data);
  }

  protected function executeDelete ($ch, $data)
  {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

    if(!is_null($data))
      $this->executeStreamData($ch, $data);
    else
      $this->doExecute($ch);
  }

  protected function executePatch ($ch, $data)
  {
    $req = $this->formatData($data);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

    $this->doExecute($ch);
  }

  protected function executeStreamData($ch, $data)
  {
    $req = $this->formatData($data);

    $requestLength = strlen($req);

    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $req);
    rewind($fh);

    curl_setopt($ch, CURLOPT_INFILE, $fh);
    curl_setopt($ch, CURLOPT_INFILESIZE, $requestLength);

    $this->doExecute($ch);

    fclose($fh);
  }

  protected function doExecute (&$curlHandle)
  {
    $response = curl_exec($curlHandle);

    if (!$response)
    {
      $this->lastResponseError = (sprintf("%s (%s)", curl_error($curlHandle), ""));
    }
    else
    {
      $this->lastResponseError = "No errors";
      $this->lastResponseInfo   = curl_getinfo($curlHandle);
      $header_size = $this->lastResponseInfo['header_size'];
      $this->lastResponseHeader = substr($response, 0, $header_size);
      $this->lastResponseBody = substr( $response, $header_size );
    }

    $status = $this->getLastStatusCode();
    if($status < 200 || $status >= 300)
    {
      $this->lastResponseError = (sprintf("Error %d (%s) for %s", $status, self::getStatusCodeMessage($status), $this->lastResponseInfo['url']));
    }

    curl_close($curlHandle);
  }

  protected function setCurlOpts (&$curlHandle, $ressourceUrl, $overridingVerb)
  {
    curl_setopt($curlHandle, CURLOPT_TIMEOUT, $this->serviceTimeout);
    curl_setopt($curlHandle, CURLOPT_URL, $this->serviceUrl.$ressourceUrl);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlHandle, CURLOPT_HEADER, true);

    $header = $this->serviceRequestHeader;
    if(!is_null($overridingVerb))
    {
      $header[] = "X-HTTP-Method-Override: ".$overridingVerb;
    }
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $this->serviceRequestHeader);

    // add useragent if requested
    if(!is_null($this->serviceRequestUserAgent))
    {
      curl_setopt($curlHandle, CURLOPT_USERAGENT, $this->serviceRequestUserAgent);
    }

    // debug mode
    //$mydebug = fopen('debug.txt','w');
    //curl_setopt($curl, CURLOPT_STDERR, $mydebug);
    //curl_setopt($curl, CURLOPT_VERBOSE, 1);
  }

  protected function setAuth (&$curlHandle)
  {
    if(!is_null($this->serviceUser) && !is_null($this->servicePassword))
    {
      curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curlHandle, CURLOPT_USERPWD, $this->serviceUser.':'.$this->servicePassword);
    }
  }


  public static function getStatusCodeMessage($status)
  {
    $codes = Array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      306 => '(Unused)',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported'
    );

    return (isset($codes[$status])) ? $codes[$status] : '';
  }



}
