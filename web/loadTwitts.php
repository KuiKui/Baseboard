<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$twitts = Baseboard::computeTwitts(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
?>
<?php foreach($twitts as $twitt):?>
  <div class="twitt"><?php echo $twitt['text']; ?></div>
<?php endforeach; ?>
