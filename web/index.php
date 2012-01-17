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
    <script type="text/javascript" src="js/webAdjust.js"></script>
    <script type="text/javascript" src="js/wauto.js"></script>
    <!--<script type="text/javascript" src="js/lib/index.js"></script>-->
  </head>
<body>
  <script type="text/javascript">
    $(document).ready(function() {
      var hudsonObject = new Hudson();
      <?php if(isset($config['general']['hudsonRefreshInterval']) && is_numeric($config['general']['hudsonRefreshInterval'])): ?>
      hudsonObject.OPTIONS.timeToTick=1000 * <?php echo $config['general']['hudsonRefreshInterval']; ?>;
      <?php endif; ?>
      hudsonObject.init();

      var basecampObject = new Basecamp(hudsonObject);
      <?php if(isset($config['general']['basecampRefreshInterval']) && is_numeric($config['general']['basecampRefreshInterval'])): ?>
      basecampObject.OPTIONS.timeToTick=1000 * <?php echo $config['general']['basecampRefreshInterval']; ?>;
      <?php endif; ?>
      basecampObject.init();
    });
  </script>

  <div id="wrapper">
    <ul id="projects"></ul>
  </div>
</body>
</html>