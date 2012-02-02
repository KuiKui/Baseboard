<?php
require_once dirname(__FILE__).'/../lib/includes.php';


$infos = Baseboard::loadProjects(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
$projects = $infos['projects'];
$availableTeammates = $infos['availableTeammates'];
$fitScreen = $infos['fitScreen'];
?>
<?php foreach($projects as $project):?>
  <?php if(!$project->shouldDisplay()) continue; ?>
  <li id="<?php echo $project->getBasecampId() ?>" class="project">
    <?php if($project->getOpenBugsCount() > 0): ?>
    <div class="bug">
      <span class="bugUsers">
        <?php foreach($project->getBugsResolvingTeammates() as $teammate):?>
          <span class="box"><?php echo $teammate['name'] ?></span>
        <?php endforeach; ?>
      </span>
      <img class="success" src="images/bug.png" /> <span class="nb"><?php echo $project->getOpenBugsCount(); ?></span>
      <ul class="tooltip">
        <?php foreach($project->getOpenBugsList() as $bug):?>
        <li><?php echo $bug['name'] ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    <h1>
      <a href="<?php echo $project->getFullUrl() ?>"><?php echo $project ?></a>
    </h1>
    <ul class="stories">
      <?php foreach($project->getMilestones() as $milestone):?>
        <?php if(!$milestone->isPending()) continue;?>
        <li class="milestone">
          <div class="box title <?php echo $milestone->getOutdated() ? 'outdated' : '' ?>"><a href="<?php echo $milestone->getFullUrl() ?>" class="ellipsis"><?php echo $milestone ?></a></div>
          <div class="box quote">
            <label><?php echo sprintf('%s / %s', $milestone->getCompletedQuotation(), $milestone->getTotalQuotation()); ?></label>
            <ul class="tooltip">
              <?php foreach($milestone->getTodoLists() as $todoList):?>
              <li>
                <a href="<?php echo $todoList->getFullUrl() ?>"><?php echo $todoList ?></a>
                <ul>
                  <?php foreach($todoList->getRemainingItems() as $item):?>
                  <li><?php echo $item['name'] ?></li>
                  <?php endforeach; ?>
                </ul>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="box bar <?php echo $milestone->getProgressState() ?>">
            <div class="pourcent" style="width:<?php echo $milestone->getPercentQuotation() ?>%"></div>
          </div>
          <?php if($milestone->getPercentQuotation() == 100): ?>
            <img class="success" src="images/success.png" />
          <?php else :?>
            <div class="users">
              <?php foreach($milestone->getWorkingTeammates() as $teammate):?>
                <span class="box"><?php echo $teammate['name'] ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </li>
<?php endforeach; ?>
<?php if(!empty($availableTeammates)): ?>
  <li class="availableTeam">
    <label>Available teammates :</label>
    <?php foreach($availableTeammates as $availableTeammate):?>
      <span class="box"><?php echo $availableTeammate['name'] ?></span>
    <?php endforeach; ?>
  </li>
<?php endif; ?>
<?php if($fitScreen): ?>
  <script type="text/javascript">
    $(window).webAdjust({wrapper: $('#projects'), maxFontSize: 32});
  </script>
<?php endif; ?>
