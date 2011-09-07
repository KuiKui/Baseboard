<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$teamTwitts = Baseboard::computeTeamTwitts(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
$teamTwitts = array_slice($teamTwitts, 0, 6, true);
?>
<?php if(count($teamTwitts) > 0): ?>
  <?php foreach($teamTwitts as $twitt):?>
    <li class="tweet">
      <img src="<?php echo $twitt['avatar']; ?>" /><?php echo $twitt['text']; ?>
    </li>
  <?php endforeach; ?>
<?php endif; ?>