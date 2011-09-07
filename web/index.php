<?php
require_once dirname(__FILE__).'/../lib/vendor/Yaml/lib/sfYaml.php';

$config = sfYaml::load(dirname(__FILE__).'/../config/config.yml');
?>
<!doctype html>
<html>
  <head>
    <title>Baseboard</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet/less" type="text/css" href="css/main.less">
    <script src="js/less.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/lib/vendor/jquery-1.6.2.min.js"></script>
    <script type="text/javascript" src="js/lib/vendor/jquery.animate-colors-min.js"></script>
    <script type="text/javascript" src="js/lib/hudson.class.js"></script>
    <script type="text/javascript" src="js/lib/basecamp.class.js"></script>
    <script type="text/javascript" src="js/lib/twitter.class.js"></script>
    <script type="text/javascript" src="js/index.js"></script>
</head>
<body>
  <div id="wrapped">
    <ul id="projects"></ul>
    <ul id="tweets"></ul>
  </div>
</body>
</html>
