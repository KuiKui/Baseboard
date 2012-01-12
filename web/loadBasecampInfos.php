<?php
require_once dirname(__FILE__).'/../lib/includes.php';


$infos = Baseboard::loadProjects(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
$projects = $infos['projects'];
$availableTeammates = $infos['availableTeammates'];
?>
<?php foreach($projects as $project):?>
  <li id="<?php echo $project->getBasecampId() ?>" class="project">
    <h1>
      <?php echo $project->getName() ?>
      <?php if($project->getOpenBugsCount() > 0): ?>
      <span class="bug"><?php echo $project->getOpenBugsCount(); ?></span>
      <?php endif; ?>
    </h1>
    <ul class="stories">
      <?php foreach($project->getMilestones() as $milestone):?>
        <?php if(!$milestone->isPending()) continue;?>
        <li>
          <span class="title <?php echo $milestone->getOutdated() ? 'outdated' : '' ?>"><?php echo $milestone->getName() ?></span>
          <span class="quote">
            <?php echo sprintf('%s / %s', $milestone->getCompletedQuotation(), $milestone->getTotalQuotation()); ?>
          </span>
          <div class="bar <?php echo $milestone->getProgressState() ?>">
            <div class="pourcent" style="width:<?php echo $milestone->getPercentQuotation() ?>%"></div>
          </div>
          <?php if($milestone->getPercentQuotation() == 100): ?>
            <img class="success" src="images/success.png" />
          <?php else :?>
            <span class="user">
              <?php foreach($milestone->getWorkingTeammates() as $teammate):?>
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
    <label>Available teammates :</label>
    <?php foreach($availableTeammates as $availableTeammate):?>
      <span><?php echo $availableTeammate['name'] ?></span>
    <?php endforeach; ?>
  </li>
