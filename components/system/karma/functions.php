<?php

include('/pineapple/includes/api/tile_functions.php');

if(isset($_GET['action'])){
	switch ($_GET['action']) {
		case 'start_karma':
		exec("pineapple karma start");
		break;
		case 'stop_karma':
		exec("pineapple karma stop");
		break;
		case 'start_autostart':
		autostart('enable');
		break;
		case 'stop_autostart':
		autostart('disable');
		break;
		case 'get_log':
		echo get_log();
		break;
		case 'clear_log':
		exec("echo '' > /tmp/karma-phy0.log");
		break;
		case 'get_report':
		echo get_detailed_report();
		break;
		case 'change_ssid_mode':
		change_ssid_mode();
		break;
		case 'get_macs':
		echo get_macs();
		break;
		case 'get_ssids':
		echo get_ssids();
		break;
		default:
			# code...
		break;
	}
}

if(isset($_GET['change_ssid'])){
	$_POST['ssid'] = str_replace("'", '\'"\'"\'', $_POST['ssid']);
	echo change_ssid($_POST['ssid'], $_POST['persistent']);
}

if(isset($_GET['client_list'])){
	if($_POST['remove_client'] == 'true'){
		echo del_mac($_POST['mac']);
	}else{
		echo add_mac($_POST['mac']);
	}
}

if(isset($_GET['ssid_list'])){
	$_POST['ssid'] = str_replace("'", '\'"\'"\'', $_POST['ssid']);
	if($_POST['remove_ssid'] == 'true'){
		echo del_ssid($_POST['ssid']);
	}else{
		echo add_ssid($_POST['ssid']);
	}
}



function get_log(){
	exec("cat /tmp/dhcp.leases; echo '\n'; cat /proc/net/arp; echo '\n'; grep KARMA: /tmp/karma-phy0.log |awk '!x[$0]++ || ($3 == \"Successful\") || ($3 == \"Checking\")'| sed -e 's/\(CTRL_IFACE \)\|\(IEEE802_11 \)//' | sed -n '1!G;h;\$p'", $log);
	$html = "<pre>";
	foreach($log as $line){
		$html .= htmlspecialchars($line)."\n";
	}
	$html .= "</pre>";

	return $html;
}

function get_detailed_report(){
	$logs = array();

	array_push($logs, htmlspecialchars(file_get_contents('/tmp/dhcp.leases')));
	array_push($logs, htmlspecialchars(file_get_contents('/proc/net/arp')));
	exec("cat /tmp/karma-phy0.log | grep -E 'Successful|association'", $output);
	$karma = array();
	foreach($output as $line){
		array_push($karma, htmlspecialchars($line));
	}
	array_push($logs, $karma);
	$html = json_encode($logs);

	return $html;
}

function autostart($mode){
	if($mode == "enable"){
		exec("/etc/init.d/karma enable");
	}else{
		exec("/etc/init.d/karma disable");
	}
}

function change_ssid_mode(){
	if(exec('hostapd_cli -p /var/run/hostapd-phy0 karma_get_black_white') == 'BLACK'){
		exec('hostapd_cli -p /var/run/hostapd-phy0 karma_white');
	}else{
		exec('hostapd_cli -p /var/run/hostapd-phy0 karma_black');
	}
}

function change_ssid($ssid, $persistence=false){
	exec("hostapd_cli -p /var/run/hostapd-phy0 karma_change_ssid '".$ssid."'");
	if($persistence){
		exec("uci set wireless.@wifi-iface[0].ssid='".$ssid."'");
		exec("uci commit wireless");
	}
	return "<font color='lime'>SSID changed to '$ssid'.</font>";
}

function add_ssid($ssid){
	exec("pineapple karma add_ssid '".$ssid."'");
	return "<font color='lime'>SSID added to list.</font>";

}

function del_ssid($ssid){
	exec("pineapple karma del_ssid '".$ssid."'");
	return "<font color='lime'>SSID removed from list.</font>";
}

function add_mac($mac){
	exec('pineapple karma add_mac "'.$mac.'"');
	return "<font color='lime'>MAC added to list.</font>";
}

function del_mac($mac){
	exec('pineapple karma del_mac "'.$mac.'"');
	return "<font color='lime'>MAC removed from list.</font>";
}

function get_ssids(){
	exec("pineapple karma list_ssids", $ssid_list);
	echo "<b>List of SSIDs:</b><br />";
	foreach ($ssid_list as $ssid) {
		echo $ssid."<br />";
	}
}

function get_macs(){
	exec("pineapple karma list_macs", $mac_list);
	echo "<b>List of MAC addresses:</b><br />";
	foreach ($mac_list as $mac) {
		echo $mac."<br />";
	}
}

?>
