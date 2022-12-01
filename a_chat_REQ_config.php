<?php

if(strstr($_SERVER['PHP_SELF'], "a_chat_REQ")) return false;
#error_reporting(2039);


#############################################
#
#	  A D/s Chatsystem
#
#	  PHP Chat with avatars
#		and advanced admin interface
#
#   Written and copyrighted(2000-2008) by Peter Thulin
#		Petersg2002@yahoo.se
#   Creation started: Summer 00, DEC 00 (Perl, version 1.0)
#   PHP creation started: Jan 2004-2008 (PHP, version 3, 4, 5)
#
$lastmodified = "Oct 14th 2008";
$version = "5.0-Beta";
$versionbuild = "- build 1565";
#
$copyright = "Copyright © 2000-2008 All Rights Reserved by Peter Thulin"; # &169; 
$my_email = "webmaster@techgodswe.us";
#
#############################################
#
#   Config Script
#
#############################################

$OFFLINE = "";
if ($_SERVER['SERVER_NAME']=='localhost') $OFFLINE = 1; # comment this line when going live


$NEW_CHAT_ONLINE = "NEW_CHAT";
$CHARSET = "ISO-8859-1";

# Betatesting VARS
#$test = "_test";
$test = "";
$timeit = 1;	# 1 el. ""
$Betatest = 1; # 1 el. ""
$use_auto = 1; #use auto listen in advanced browsers aswell?
$about_msg = "<a href=\"mailto:$my_email?Subject=A D/s chat\">A D/s Chatware</A> - $copyright\nVersion $version, last modified on $lastmodified\nLicensed to Ties-That-Bind.com, ISP Technologies."; # msg printed on /info
if ($test || $Betatest) $testing_new_version = "<font color=\"#FF5F55\" size=1> Chat V.$version Test - PLEASE <a href=\"http://techgodswe.ties-that-bind.com/betatest/Default.php\" target=\"blank\">REPORT BUGS</a> </font>";


# Use secure parsing - - NOT TESTED YET!
$Secure_parsing = "";

if ($timeit) {
	if ($OFFLINE) include_once('D:/newsite/siteGen/timer.inc.php');
	else include_once('/home/ttb2002/public_html/siteGen/timer.inc.php');
	$timer = new Timer;
	$timer->start_time();
}


# INIT GLOBAL VARS
#global $qs, $query, $Admcfg, $mem_niv, $mem_status, $Staff, $Setmsg_total, $Setsystemmsg_total;

$Setmsg_total = array();
$Setsystemmsg_total = array();
$qs = array();
$query = array();
$Admcfg = array();
$mem_niv=""; $mem_status=""; $Staff="";


# GET $Admcfg[] VARS
if ($OFFLINE) include_once("D:/newsite/testing/adm_cfg_vars.php.pl");
else include_once("/home/ttb2002/public_html/chat/Dep_cfg_files_XX_inC2_eeeE/adm_cfg_vars.php.pl");


if (stristr($version, "BETA")) {
	$Admcfg['chatbot_name'] = "roboslave";
	$Admcfg['chatbot_interval'] = 30;
	$Admcfg['chatbot_limit'] = 10; # -1 to turn off chatbot
	$Admcfg['chatbot_response'] = ""; # 1 to respond to all post regardless to whom it is for
	$Admcfg['chatbot_room'] = ""; # room that chatbot is in empty for all
	$Admcfg['chatbot_jokes'] = 10; # percent chance of chatbot telling a joke
	
	$Admcfg['ignore_type'] = 'nick'; # 'name' - ignore all nicks from a membernumber else just ignore separate nicks
	$Admcfg['max_allowed_ignores'] = 100; # max 100 ignore entries per member
	$Admcfg['ignore_expire'] = 72; # 72 hours, 3 days
	
	$Admcfg['Allowed_chat_chars'] = "A-Za-z0-9_-~¤^*{}+";
}

$Admcfg['Allowed_chat_chars'] = preg_quote($Admcfg['Allowed_chat_chars']);


# Server Vars
$IP = $_SERVER['REMOTE_ADDR'];
$USER_AGENT = $_SERVER['HTTP_USER_AGENT'];


# CONFIG VARS FOR A_CHAT

$timestamp = time();

# SESSION Chat timeout in minutes, usually 15
$session_timeout = 15;

$cryptkey = "xalfa";
$cookie_fetch_name = "pwd";
$lock = "lock"; # file ending on lock files
$closedown = 0;

$lgkak_value = "login";
$chat_sess_cookie_name = "TTBID";
if ($Betatest) $addclife = ($session_timeout * 60);
else $addclife = ($session_timeout * 60); # cookielife in seconds

$Chatbot_DB = "ttb2002_alicebot";


# Config parameters
if ($OFFLINE) {

	$base_path = "D:/newsite/";	
	$cgi_base_path = "";
	$html_base_path = "";
	$pip_path = "pip";
	$html_base_url = "http://localhost/newsite/";
	$base_url = "http://localhost/newsite";
	$cookielifetime = $timestamp + 1800;
	$ingetlosen="";
	$log_out_time = 900;
	#$cookiepath = "/";
	#$return_signup = "http://localhost/EasySite/index.php";

	$cgi_true_url = "http://localhost/chat/";	
	
	define("_site_gen_", "D:/newsite/siteGen/", true);
	define("_chat_path_", "D:/ttb_php/", true);
	define("_chat_url_", "http://localhost/chat", true);
	
	$frames_url = "http://localhost/chat/frames";
	
	$Dep_cfg_path = "D:/newsite/testing/";

	#if(preg_match("/NT 5/", $USER_AGENT)) {
		$html_base_url_newsite = "http://localhost/newsite/";
		#$base_url = "http://localhost/newsite";
		#$return_signup = "http://localhost/TTB2/EasySite/index.php";
	#}
	
	define("_html_newsite_url_", "http://localhost/newsite/", true);
	
	$chatbot_path = _chat_path_ . "chatbot/";

} else {

	$flocking = 1;
	$use_reopen = 0; # SET TO 1?
	#$reopen_test = 1;
	$cgi_true_url = "http://www.ties-that-bind.com/chat/";

	$base_path = "/home/ttb2002/public_html/";
	
	$cgi_base_path = $base_path . "chat/";
	$html_base_path = $base_path . "chat/";
	
	$pip_path = $base_path."pip";

	$base_url = "http://www.ties-that-bind.com";
	$html_base_url = "http://www.ties-that-bind.com/chat/";
	#$base_url = "../..";
	#$html_base_url = "../../chat/";
	
	#$html_base_url_newsite = $html_base_url;
	$html_base_url_newsite = $base_url . "/";

	define("_site_gen_", $base_path . "siteGen/", true);
	define("_chat_path_", $base_path . "chat/", true);
	define("_chat_url_", "http://www.ties-that-bind.com/chat", true);
	
	$frames_url = $html_base_url . "frames";
	
	$Dep_cfg_path = $base_path . "chat/Dep_cfg_files_XX_inC2_eeeE/";
	
	define("_html_newsite_url_", $base_url . "/", true);

	$cookielifetime = $timestamp + $addclife;
	$ingetlosen="";
	$log_out_time = 900;
	#$cookiepath = "/";
	$mail_prog = '/usr/lib/sendmail';
	$use_local_mail = 1;
	$use_local_reg = 1;
	$mail_on_new_registration = 1; # set to 1 if you want to be mailed when a new user registers
	$return_signup = "http://www.ties-that-bind.com/index.php";
	
	$chatbot_path = $html_base_path . "chatbot/";

}


# ConfigParameter Vars FILES AND DIRS
$chat_URL = "<a href=\"http://www.ties-that-bind.com\">http://www.ties-that-bind.com</a>";
$email_check_file = $cgi_base_path."email/mail_check_ff.dat";
$icon_Gen_script_url = $base_url . "/index.php?page=icon_Gen.php";
$web_mail_script = $base_url . "/webmail.php";

$script_name = $cgi_true_url . "a_chat.php"; # filename of the chat script
$script2_name = $cgi_true_url . "a_chat.php"; # filename of the config script

#$script2_name = $cgi_true_url . "a_chat_config.php"; # filename of the config script
$script3_name = $cgi_true_url . "chat.php"; # filename of the third signup script


$icon_upload_script = "iconupload.cgi";

$logout_from_chat_link = $cgi_true_url . "logout.php";
$logout_url = $html_base_url_newsite . "index.php?logout=true"; // returning from logout.php
$exit_url = $html_base_url_newsite . "index.php?page=chatlinks.html"; // returning from logout.php with exit room

$chatroomcount_link = "javascript:launchWindow('" . $html_base_url_newsite . "siteGen/chatroomcount.php','TTBroomcount');";

$data_dir = _chat_path_ . "LLdaBBta"; # data directory like "data"
$DB_DIRECTORY = "$cgi_base_path$data_dir" ;
$error_logger = "chat_v3_errorlog.pl";
$log_dir = _chat_path_ . "LLolga"; # directory for log files
$icon_del_log = "priv8icon_del.log";
$reg_log = "registration_V3_log.pl";
$already_mail_file = "already_emailed.dat";
$mail_dir = "email";
$offline_file = "offline.dat";
$usericon_file = "usericons"; # without user
$avatar_dir = "avatars";
$images_url = "${html_base_url}images/smilys"; # smilys should not be in cgi-bin
$memberlist = "$data_dir/mail/email_memberlist.dat";
$Flash_dl_url = "http://www.macromedia.com/download";
$view_news = 1;

$sysopstatus = " [<a href=\"". $Admcfg['sysop_html'] . "\" target=\"sys\">";
$modstatus = " [<a href=\"". $Admcfg['dso_html'] . "\" target=\"sys\">";
$hoststatus = " [<a href=\"". $Admcfg['guide_html'] . "\" target=\"sys\">";
$idastatus = " [<a href=\"". $Admcfg['ida_html'] . "\" target=\"sys\">";
$volstatus = " [<a href=\"". $Admcfg['vol_html'] . "\" target=\"sys\">Volunteer</a>]";
$spac = "<BR><BR>";
$Uhelpmod = "Uhelp_mod2.html";

$Keep_password = 86400; #in secs, 24 hours
$Keep_room = 86400; #in secs


# session list, session vars to use
$session_vars_list = array('room_name', 'col', 'defaultnick', 'oldlogin', 'updfreq', 'logintime', 'noframes', 
													 'msgtoshow', 'trans', 'background', 'ignoreimg', 'ignoreswf', 'ignorewebcamicons', 
													 'nick', 'style', 'turnoffbbcode', 'bigwin', 'turnoffhide', 'no_welcome', 'room');


# session vars that GET or POST can overwrite
$session_vars_GP_ow_list = array('room_name', 'updfreq', 'msgtoshow', 'flavers', 'nick', 'room', 
																 'loc', 'loc2', 'att', 'att2', 'att3', 'noframes', 'icon', 'pauseload', 'msg_action');

# for handling checkboxes/radiobuttons
$session_vars_GP_ow_list_CB = array('sendlock', 'ignoreimg', 'ignoreswf', 'onlynew', 'disp');


# These gets added in add_session_vars()
# session vars that GET or POST can overwrite but for 'frames'
$session_vars_frame = array('to', 'Postfield', 'level', 'action', 'frames', 'submit', 
														'sel_room', 'newroom', 'go_room', 'go_room_sel', 
														'setdelmsg', 'setignore', 'setPMignore', 'totaldel', 'totalignore');

# for handling checkboxes/radiobuttons
$session_vars_frame_CB = array('addbuddy');



# MESSAGES Part
###############

$chat_msg[0]  = "Y/your level is inadequate to use this command";
$chat_msg[1]  = "unknown command";
$chat_msg[2]  = "no number of days specified";

# msg printed on /help (DON'T REMOVE "[END]") (changed)
$chat_msg[3]  = <<<_END_

	<I><B>commands usage:</B></I>
	<font color="white">/help</font> - this helptext.
	<font color="white">/info</font> - info about this chat.
	<font color="white">/userinfo <KBD>nick</KBD></font> - userinfo about (offline) U/user.
	<font color="white">/ignore <KBD>nick</KBD></font> - ignore Private Whispers from 'nick' U/user.
	<font color="white">/unignore <KBD>nick</KBD></font> - unignore ALL types messages from 'nick' U/user.
	<font color="white">/away <KBD>message</KBD></font> - set or unset away status and post message. (Paying member level needed)
	<font color="white">/lurk <KBD>message</KBD></font> - set or unset lurk status and post message. (Paying member level needed)
	<font color="white">/memo <KBD>nick message</KBD></font> - leave message for offline user. (Paying member level needed)
	<font color="white">/seen <KBD>nick</KBD></font> - when was U/user last in the chatroom? (Paying member level needed)
	<font color="white">/msg <KBD>nickname</KBD> <KBD>message</KBD></font> - send private message to nickname. (Volunteer member level needed)\n\n
_END_;

$chat_msg[4]  = "has slipped out from the room";
$chat_msg[5]  = "U/users";
$chat_msg[6]  = "U/user";
$chat_msg[7]  = "in the room";
$chat_msg[8]  = "MemberNR(level)(Nicknames)";
$chat_msg[9]  = "Created on";
$chat_msg[10] = "last visited"; # last time logging in
$chat_msg[11] = "current/last ip";
$chat_msg[12] = "status"; # status
$chat_msg[13] = "#memos"; # #memos
$chat_msg[14] = "normal"; # normal
$chat_msg[15] = "Gagged"; # kicked
$chat_msg[16] = "Banned"; # banned
$chat_msg[17] = "removed users";
$chat_msg[18] = "total";
$chat_msg[19] = "removed";
$chat_msg[20] = "doesn't exist";
$chat_msg[21] = "EveryOone in room";
$chat_msg[22] = "unbanned";
$chat_msg[23] = "Memo for";
$chat_msg[24] = "was posted successfully";
$chat_msg[25] = "all U/users";
$chat_msg[26] = "Auto-listen";
$chat_msg[27] = "(timeout)";
$chat_msg[28] = "Y/you are now away";
$chat_msg[29] = "Y/you are back";
$chat_msg[30] = "[is away]"; # [away] (New) - may be replaced with an image?
$chat_msg[31] = "is away:";
$chat_msg[32] = "is back again:";
$chat_msg[33] = "Total number of U/users:";
$chat_msg[34] = "<font size=1><a href=\"#TOP\">TOP OF PAGE</a></font>";
$chat_msg[35] = "<font color=\"#FF5F55\" size=2>new </font>";
# chat_is_full_html
$chat_msg[36] = "Chat is full";
$chat_msg[37] = "<p>Sorry, the chat room is full, please try another room or come back another time.</p>";
$chat_msg[38] = "Y/you are now lurking";
$chat_msg[39] = "Y/you are back from lurking";
$chat_msg[40] = "[lurks]"; # [lurking] (New) - may be replaced with an image?
$chat_msg[41] = "is lurking:";


$config_msg[0]  = "unknown nickname";
# nonick_html
$config_msg[1]  = "<p>U/user doesn't exist";
#$config_msg[2]  = "<A HREF=\"$script2_name?action=register\">register</A><br>Or close window and try with another.<p>";

# wrongpass_html
$config_msg[3]  = "Wrong Password!";
$config_msg[4] = "You have enter the wrong membernumber/password?";
#$config_msg[4]  = "<A HREF=\"$script2_name?action=send_pwd&name=";
#$config_msg[19] = "\">lost password</A><BR><br>Or close window and try again.<p>";

# kicked_html
$config_msg[5]  = "<p><h2>Y/you have been bad and were gagged.<br>(i.e removed from chatroom temporarily)</h2>";
$config_msg[6]  = "<p>";

# banned_html
$config_msg[7]  = "<p><h2>Y/you are banned from this chat<br>until the Chat Security Team decides otherwise.</h2>";
$config_msg[8]  = "<p>";
# user [user] not found
$config_msg[9]  = "U/user";
$config_msg[10] = "not found";
$config_msg[11] = "realname";
$config_msg[12] = "eMail";
$config_msg[13] = "age";
$config_msg[14] = "city";
$config_msg[15] = "country";
$config_msg[16] = "homepage";
$config_msg[17] = "anything else";
$config_msg[18] = "photo";
#$config_msg[19] = ",<br>the membernumber or password you entered were incorrect.";
$config_msg[20] = "Chat Session expired";
$config_msg[21] = "<h4>W/we are sorry, but Y/your current Chat session has expired.<br>Yyou need to log-in again.</h4>";
$config_msg[22] = "There was an error retrieving a cookie.";
$config_msg[23] = "<p>If this problem persists, use the less secure login: Membernumber+@<br>like this: 1009@ , in the Member textbox. ";
$config_msg[24] = "<p>If this problem persists please contact support";
$config_msg[25] = "A faulty or no cookie was sent from Y/your browser.";

#: END CONFIG

return 1;

?>