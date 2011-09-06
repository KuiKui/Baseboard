<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$infos = Baseboard::compute(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
$projects = $infos['projects'];
$availableTeammates = $infos['availableTeammates'];
?>
<?php foreach($projects as $project):?>
  <div id="<?php echo $project['id']; ?>" class="project">
    <h1><?php echo $project['name']; ?></h1>
    <div>Bugs ouverts : <?php echo $project['openedBugsCount']; ?></div>
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
          <?php if(count($milestone['teammates']) > 0): ?>
            <tr>
              <th>Equipe</th>
              <td>
                <?php foreach($milestone['teammates'] as $teammate):?>
                  <span class="teammate"><img src="<?php echo $teammate['avatar'] ?>" /><?php echo $teammate['name'] ?></span>
                <?php endforeach; ?>
              </td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<div>
  <?php foreach($availableTeammates as $availableTeammate):?>
    <span class="teammate"><img src="<?php echo $availableTeammate['avatar'] ?>" /><?php echo $availableTeammate['name'] ?></span>
  <?php endforeach; ?>
</div>
