<?php
require_once dirname(__FILE__).'/../lib/vendor/Yaml/lib/sfYaml.php';
require_once dirname(__FILE__).'/../lib/curlConnexion.class.php';
require_once dirname(__FILE__).'/../lib/baseboard.class.php';
require_once dirname(__FILE__).'/../lib/basecampAPI.class.php';
require_once dirname(__FILE__).'/../lib/hudsonAPI.class.php';

$projects = Baseboard::compute(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <title>Baseboard</title>
    <link rel="stylesheet" type="text/css" href="./css/main.css" media="all" />
  </head>
  <body>
  <div id="fixed">
    <?php foreach($projects as $project):?>
      <div class="project <?php if($project['hasFailedJobs']) echo 'hasFailedJobs'; ?>">
        <h1><?php echo $project['name']; ?></h1>
        <?php foreach($project['milestones'] as $milestone):?>
          <div class="milestone <?php if($milestone['outdated']) echo 'outdated'; ?>">
            <h2><strong><?php echo $milestone['name']; ?></strong><span class="quote <?php echo $milestone['lateCssClass']; ?>"><?php echo sprintf("%s / %s", $milestone['completedCotation'], $milestone['totalCotation']); ?></span></h2>
            <table class="info">
              <tr>
                <th>Progression</th>
                <td><div class="progress"><span class="done <?php echo $milestone['lateCssClass']; ?>" style="width: <?php echo $milestone['percentCotation']; ?>%;"><?php echo $milestone['percentCotation']; ?> %</span></div></td>
              </tr>
              <?php if($milestone['openedBug'] > 0): ?>
                <tr>
                  <th>Bugs ouverts</th>
                  <td><div class="bug"><?php echo $milestone['openedBug'] ?></div></td>
                </tr>
              <?php endif; ?>
            </table>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</body>
