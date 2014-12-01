<?php 
// To get started with Kaltura, you need an acccount.
// Get a free trial at: http://corp.kaltura.com
// In your Kaltura account, get the partner Id and API Admin Secret from:
// http://www.kaltura.com/index.php/kmc/kmc4#account|integration
define("PARTNER_ID", '1357241');
define("ADMIN_SECRET",'1179e62370a3731977bf9a4c6dba3590');
define("USER_SECRET", '7501bdbfadc96b24828ce542b5c66b7a');
define("PLAYER_UICONF_ID", 27216752);
define("BUY_BUTTON_PLAYER_UICONF_ID", null);
define("PAYPAL_METADATA_PROFILE_ID", 4376762);
define("PAYPAL_CATEGORY_METADATA_PROFILE_ID", 4376772);
define("PAYPAL_USER_METADATA_PROFILE_ID", 4376782);
//Generates a USER ID based on the machine name and IP address.
function getRealIpAddr() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
	{
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	}
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
	{
		$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else
	{
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}
if(isset($_COOKIE['kaypaluserid']) && $_COOKIE['kaypaluserid'] != "") {
	$USER_ID = $_COOKIE['kaypaluserid'];
}
else {
	$expire=time()+60*60*24*365;
	$user = implode('_', explode(':','demo_user_'.mt_rand(1, 9999999).getRealIpAddr()));
	setcookie('kaypaluserid', $user, $expire);
	$USER_ID = $user;
}
