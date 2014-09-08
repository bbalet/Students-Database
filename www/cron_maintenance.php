<?php 
global $in_cron_maintenance;
$in_cron_maintenance = true;
global $cron_maintenance_tasks;
$cron_maintenance_tasks = array();

$f = fopen("maintenance_in_progress","w");
fclose($f);
$f = fopen("maintenance/password","w");
fclose($f);
$f = fopen("maintenance/origin","w");
fwrite($f, "Automatic Maintenance");
fclose($f);
@unlink("maintenance_time");
@unlink("maintenance/ask_cancel");

include("install_config.inc");
require_once("component/PNApplication.inc");
require_once("SQLQuery.inc");
PNApplication::$instance = new PNApplication();
PNApplication::$instance->init();
PNApplication::$instance->cron->executeMaintenanceTasks();

@unlink("maintenance/password");
@unlink("maintenance/origin");
@unlink("maintenance/ask_cancel");
@unlink("maintenance_in_progress");
@unlink("maintenance_time");
?>