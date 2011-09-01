<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$companyTwitts = Baseboard::computeCompanyTwitts(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
$teamTwitts = Baseboard::computeTeamTwitts(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
?>
<?php if(count($companyTwitts) > 0): ?>
  <div class="companyTwitts">
    <?php foreach($companyTwitts as $twitt):?>
      <div class="twitt"><img src="<?php echo $twitt['image']; ?>" /><?php echo $twitt['user'].' : '.$twitt['text']; ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php if(count($teamTwitts) > 0): ?>
  <div class="teamTwitts">
    <?php foreach($teamTwitts as $twitt):?>
      <div class="twitt"><img src="<?php echo $twitt['image']; ?>" /><?php echo $twitt['user'].' : '.$twitt['text']; ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
