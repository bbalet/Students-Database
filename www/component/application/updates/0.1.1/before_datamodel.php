<?php 
global $previous_version_path, $new_version_path;
if (!file_exists($previous_version_path."/conf/instance.uid")) {
	// generate an installation UID, so we can differentiate several installations on the same server
	$uid = "".time()."-".rand(0, 10000)."-".$_SERVER["SERVER_NAME"]."-".rand(0,10000);
	$f = fopen($previous_version_path."/conf/instance.uid","w");
	fwrite($f,$uid);
	fclose($f);
}
// fix step_install
copy($new_version_path."/maintenance/step_install.inc",$previous_version_path."/maintenance/step_install.inc");
?>