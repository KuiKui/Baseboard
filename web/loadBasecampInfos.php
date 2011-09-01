<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$projects = Baseboard::compute(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
?>
<?php foreach($projects as $project):?>
  <div id="<?php echo $project['id']; ?>" class="project">
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
