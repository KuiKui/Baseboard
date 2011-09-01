<?php
require_once dirname(__FILE__).'/../lib/vendor/Yaml/lib/sfYaml.php';

$config = sfYaml::load(dirname(__FILE__).'/../config/config.yml');
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <title>Baseboard</title>
    <link rel="stylesheet" type="text/css" href="./css/main.css" media="all" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
    <script type="text/javascript" src="./js/main.js"></script>
  </head>
  <body>
  <div id="fixed">
    <div id="basecampContent"></div>
    <div id="twitterContent"></div>
  </div>
</body>
