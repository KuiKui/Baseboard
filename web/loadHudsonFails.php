<?php
require_once dirname(__FILE__).'/../lib/includes.php';

$failedProjectIds = Baseboard::computeHudsonFails(sfYaml::load(dirname(__FILE__).'/../config/config.yml'));
echo json_encode($failedProjectIds);
?>
