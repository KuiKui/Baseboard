RESTConnection is a PHP class used to make requests to REST APIs easily

The process is as follow :

1- instantiate a RESTConnection object, passing it the general parameters to connect to the REST api of your choice.
   ie : base url to the service, credentials, headers (to specify sent and accepted content types for example)

2- Use the method "request()" to send a request to this api, using parameters : url path, (optional) data to send,
   and method (verb, ie : get, post, put...) to use.

3- Get the resulting http status code, body, header, error message



Altough RESTConnection supports most used http verbs (get, post, put, delete, patch), you might be in a situation
where only get/post are supported (your firewall might block other verbs).
In that case, you can set the compatibilityMode to true. Then, every delete, put, patch verb will be passed as a
POST and a special header X-HTTP-Method-Override will be added.
On another hand, you also might want to force this for one specific request. Think of google translate, that usually
takes the word to translate as a GET. If you want to transalte a whole paragraph you'll have to pass it as a POST and
force the X-HTTP-Method-Override to GET. You can do this easily, by just adding a overriding verb to the request
parameters list.



-------------------------------------------
Examples
-------------------------------------------

1- Get twitter public tweets

// Initialize the header of our future requests, specifying the format we want to use in request and response (json)
$requestHeader = array('Accept: application/json', 'Content-Type: application/json');
// Create the RESTConnection object, for now, no credential needed as we get only public tweets
$testAPI = new RESTConnection('https://api.twitter.com/1/', $requestHeader);

// Issue a GET request on 'https://api.twitter.com/1/statuses/public_timeline.json'
if($testAPI->request('statuses/public_timeline.json'))
{
  // Display the tweets
  var_dump(json_decode($testAPI->getResponseBody(), true));
}
else
{
  // Something went wrong
  var_dump($testAPI->getLastError());
}

-------------------------------------------

2- Post a message to campfire, highlight it and then change the room topic

// Initialize the header of our future requests, specifying the format we want to use in request and response (json)
$requestHeader = array('Accept: application/json', 'Content-Type: application/json');
// Create the RESTConnection object, this time, credential are specified
$testAPI = new RESTConnection('https://your.campfirenow.com/', $requestHeader, 'your_token_here', 'X');

// Issue a POST request on 'https://your.campfirenow.com/room/your_room_id/speak.json'
if($testAPI->request('room/your_room_id/speak.json', json_encode(array('message' => array('body' => "Hello"))), RESTConnection::POST)))
{
  // lastStatusCode should be 201
  var_dump($testAPI->getLastStatusCode());
  // Response body contains the message id
  $result = (json_decode($testAPI->getResponseBody(), true));

  // star the message
  $messageid = $result['message']['id'];
  $testAPI->request(sprintf('messages/%s/star.json', $messageid), array(), RESTConnection::POST);

  // unstar it
  // $testAPI->request(sprintf('messages/%s/star.json', $messageid), array(), RESTConnection::DELETE);
}
else
{
  // Something went wrong
  var_dump($testAPI->getLastError());
}

// Issue a PUT request on 'https://your.campfirenow.com/room/your_room_id.json'
$testAPI->request('room/your_room_id.json', json_encode(array('room' => array('topic' => "this room is not about cats"))), RESTConnection::PUT);


