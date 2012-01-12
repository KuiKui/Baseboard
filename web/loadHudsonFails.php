<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$failedProjectIds = Baseboard::loadHudsonFails(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
echo json_encode($failedProjectIds);
?>
