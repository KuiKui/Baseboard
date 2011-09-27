<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$infos = Baseboard::compute(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
$projects = $infos['projects'];
$availableTeammates = $infos['availableTeammates'];
?>
<?php foreach($projects as $project):?>
  <li id="<?php echo $project['id'] ?>" class="project">
    <h1>
      <?php echo $project['name'] ?>
      <?php if($project['openedBugsCount'] > 0): ?>
      <span class="bug"><?php echo $project['openedBugsCount']; ?></span>
      <?php endif; ?>
    </h1>
    <ul class="stories">
      <?php foreach($project['milestones'] as $milestone):?>
        <li>
          <span class="title <?php echo $milestone['outdated'] ? 'outdated' : '' ?>"><?php echo $milestone['name'] ?></span>
          <span class="quote">
            <?php echo sprintf('%s / %s', $milestone['completedCotation'], $milestone['totalCotation']); ?>
          </span>
          <div class="bar <?php echo $milestone['lateCssClass'] ?>">
            <div class="pourcent" style="width:<?php echo $milestone['percentCotation'] ?>%"></div>
          </div>
          <?php if($milestone['percentCotation'] == 100): ?>
            <img class="success" src="images/success.png" />
          <?php else :?>
            <span class="user">
              <?php foreach($milestone['teammates'] as $teammate):?>
                <span><?php echo $teammate['name'] ?></span>
              <?php endforeach; ?>
            </span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </li>
<?php endforeach; ?>
  <li class="availableTeam">
    <label>Personnes non affectées :</label>
    <?php foreach($availableTeammates as $availableTeammate):?>
      <span><?php echo $availableTeammate['name'] ?></span>
    <?php endforeach; ?>
  </li>
