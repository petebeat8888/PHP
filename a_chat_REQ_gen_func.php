
<?php

if(strstr($_SERVER['PHP_SELF'], "a_chat_REQ")) return false;
#error_reporting(0);


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
#   PHP creation started: Jan 2004-8 (PHP, version 3, 4, 5)
#
#
#############################################
#
#   Globally Required functions
#
#############################################


# TIMER FUNCTIONS

/*function timerinit() {
	
	global $OFFLINE, $timeit, $timer; 
	if ($timeit) {
		#if ($OFFLINE) include_once('D:/newsite/siteGen/timer.inc.php');
		#else include_once('/home/ttb2002/public_html/siteGen/timer.inc.php');
		$timer = new Timer;
		$timer->start_time();
	}

}*/

function setnewtimer($new) {
	
	global $timer, $timeit;
	if ($timeit) $timer->set_intermediate($new);

}

function endtimer($endtimes) { # endtimes = array
	
	global $timer, $timeit;
	if ($timeit) {
		$timer->end_time();
		echo '<h5><br><font color="#e6e6f0"><i>Page generated in</i> <b>'. sprintf("%01.3f", $timer->elapsed_time()) . '</b> <i>seconds.</i><br>';
		echo '<i>Time to init code:</i> <b>'. sprintf("%01.3f", $timer->partial_time('init', 0)) .'</b> <i>seconds.</i><br>';
		foreach($endtimes as $timestr) {
			echo "<i>Time for '$timestr' from start:</i> <b>". sprintf("%01.3f", $timer->partial_time($timestr, 0)) . "</b> <i>seconds.</i><br>";
		}
		#echo '<i>Time to get to message frame:</i> <b>'.  sprintf("%01.3f", $timer->partial_time('msg', 0)) .'</b> <i>seconds.</i><br>';
		#echo '<i>Time to get bannerAd:</i> <b>'.  sprintf("%01.3f", $timer->partial_time(3,2)) .'</b> <i>seconds.</i><br></font>';
		#echo '<i>Time to query DB on /unignore:</i> <b>'.  sprintf("%01.3f", $timer->partial_time('testend', 'teststart')) .'</b> <i>seconds.</i><br>';
		echo "</h5>";
	}
	
}

# END TIMER FUNCTIONS


# new check_pass chat
function checkpass3($pwd="", $validator="", $name="") {

	global $query, $Admcfg, $lowsec2, $kick_reason, $logout_msg, $WEBTV, $WEBTV_OLD, $timestamp, $addclife, 
	$mem_niv, $mem_status, $config_msg, $cryptkey, $html_base_url, $ingetlosen, $passfile_matrix, $chat_sess_cookie_name, $ciphered_pass;

	$loseOK="";
	$encryptedpass="";
	#$query['idpass'] = "";
	$passfile_error = "";
	
	$lowsec2 = "";
	$kick_reason = "";
	$logout_msg = "";
	
	if ($_REQUEST['logout']) return false;
	
	$passfile = create_passfile_matrix();
	
	
	if ($timestamp > ($query['lastrun'] + $addclife) && $validator != 33) {
		## TIMED OUT
		session_kill();
		#$Log_out_now = 1;
		user_error_html($config_msg[20], $config_msg[21]);		
	} else if ($passfile == "passfileERROR") {
		session_kill();
		user_error_html("Session error", "It seems like your current session couldn't be found in the Database.<p>Login in again on the mainsite.<p>" . $config_msg[24]);
	} else if (!$passfile) {
		session_kill();
		user_error_html("Session Error", "An unknown session error took place. " . $config_msg[24]);
	} else if ($_SESSION && !$name) {
		session_kill();
		cookie_pass_err("Faulty Session", $config_msg[22]. $config_msg[24]."<P><b>Or maybe you just logged out and then went back?</b>","[COOKIE Error (faulty?) Error]");
	} else if (!preg_match("/^[\d]{5,6}$/", $name)) {
		#session_kill();
		#user_error_html("Invalid Membernumber", "Your member number is not a number or not of the right length<p>Please try and login again.");
		debug();
	}
	
		
	
	# valid run?
	if ($validator == 33) { # first run via login();
		if ($pwd && (strlen($pwd) < 4)) {
			cookie_pass_err("Faulty password","Y/your password is not of the right length!","[PASS_COOKIECHECK(faulty length) Error]");
		}
		
		$loseOK = 1;
		# set session vars
		session_login();
	}
	else {# FURTHER runs
		$ciphered_pass = $query['pass'];
		$decrypted_pass = DE_crypt($ciphered_pass, '64');
			
		if ($ciphered_pass) {
			#$query['idpass'] = $ciphered_pass;;

			if ($ciphered_pass == "=logout=") {
				session_kill();
				cookie_pass_err("Session Expired","You have just logged out.<br>You have to login again.","[PASS_COOKIECHECK(logged out cookie) Error]");
			}
			if (strlen($decrypted_pass) < 4) {
				#session_kill();
				cookie_pass_err("Password error","Your password has the wrong format.","[PASS_COOKIECHECK(logged out cookie) Error]");
			}
		}
		else if ($timestamp > ($query['lastrun'] + $addclife) && $_SESSION) {
			## Chatsession TIMED OUT
			session_kill();
			user_error_html($config_msg[20], $config_msg[21]);
		}
		else {# cookie error?
			#session_kill();
			cookie_pass_err("Cookie Error","Your Session cookie seems to be gone?<br>Y/you have to login again.","[PASS_COOKIECHECK(empty cookie?) Error]");
		}
	}

				
	
	# now check if user exists
	if ($loseOK != 1 && $ingetlosen != 1) { # it must be the second run and OFFLINE
		
		if ($passfile_matrix[0]) {
			for ($cpi=0; $cpi < count($passfile_matrix); $cpi++) {
				if ($passfile_matrix[$cpi][0] == $name) {
					
					# CHECK IF GAG/BAN
					if ($passfile_matrix[$cpi][5] == 1) {
						$logout_msg = $config_msg[5];
						$kick_reason = "GAG";
					}
					else if ($passfile_matrix[$cpi][5] == 2) {
						$logout_msg = $config_msg[7];
						$kick_reason = "BAN";
					}
					if ($kick_reason) {
						session_kill();
						user_error_html($kick_reason, $logout_msg);
					}
					# end check if GAG/BAN	

					# VALID PASS?
					$loseOK = 1;					
					#$passtest = crypt($query['idpass'], $cryptkey);					
					$passtest = $ciphered_pass;					
						
					if ($passfile_matrix[$cpi][1] == $passtest) {
						$mem_niv = $passfile_matrix[$cpi][3];
						$mem_status = $passfile_matrix[$cpi][4];
						# update memberlosen time?
						exec_DB("ties1_chatdata", "UPDATE `memberlosen` SET `time`=$timestamp WHERE `name`=$name", "=update=", "checkpass3-update passfile");
						
						return true;
					}
					else if ($pass_test == "=logout=") {
						session_kill();
						cookie_pass_err($config_msg[20],$config_msg[21],"[Checkpass (pass) Error]");
					}
					else {
						# unknwon error
						session_kill();
						cookie_pass_err("Session Error", "An unknown error took place. " . $config_msg[24],"[Checkpass (pass) Error]");
					}

					break;
				}
			}
		}
		else {
			$passfile_error = 1;
		}
	}



	#For offline
	if ($ingetlosen == 1) {
		
		#if (!$passfile_matrix[0]) create_passfile_matrix();
		
		for ($cpi=0; $cpi < count($passfile_matrix); $cpi++) {
			if ($passfile_matrix[$cpi][0] == $name) {
				if ($lowsec2) {
					if ($passfile_matrix[$cpi][1] == $query['pass']) {
						$mem_niv = $passfile_matrix[$cpi][3];
						$mem_status = $passfile_matrix[$cpi][4];
					}
					# no kicking out
				}
				else {
					if ($passfile_matrix[$cpi][1] == $ciphered_pass) {
						$mem_niv = $passfile_matrix[$cpi][3];
						$mem_status = $passfile_matrix[$cpi][4];
					}
					#no kicking out
				}
			}
		}
	}

	if ($passfile_error) {
		cookie_pass_err("Password cache Error","There was an error creating a cache, please report this error to the webmaster.","[Checkpass (no passfile) Error]");
	}
	else if ($loseOK != 1 && $ingetlosen != 1) {
		cookie_pass_err($config_msg[1],$config_msg[2],"[Checkpass (Nick not found) Error]");
	}
	
}


function start_page_html($isframe) {

	global $Admcfg, $query, $Chatbot_on, $chatbot_path, $html_base_url, $version, $No__run_Xpass, $closedown, 
				 $WEBTV, $WEBTV_OLD, $CSS_vers, $NETSCAPE_4, $MSIE, $html_css, $cr_rom_err1, $cr_rom_err2, $rumcreation, $mem_niv;

	if ($MSIE) {
		# $MSIE_meta<meta http-equiv="Last-Modified" content="$localtime">
		#	$MSIE_meta = qq~<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
		#<meta http-equiv="Pragma" content="no-cache">
		#<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
		#~;
		$pagetrans = $query['trans'] == "on" ? "\n<meta http-equiv=\"Page-Enter\" content=\"revealTrans(Duration=1,Transition=12)\">\n" : "";
	}

	if ($query['action'] != "dologin") {
		#if (!$WEBTV && $query['action'] != "dologin" && !$NETSCAPE_4) {
			#$style = ($query['style']) ? $query['style'] : "chatV3.css"; # css version 1?
			#if (stristr($version, "beta")) $style = "chatV3.css";
			#if ($CSS_vers == 2) $style = "css2_" . $style;
			#$stylesheet = "\n<link href=\"". $html_base_url ."styles/$style\" type=\"text/css\" rel=\"stylesheet\">";
		#}
		
		if (strlen(_chat_stylesheet_) > 1) {
			$style = _chat_stylesheet_;
			if (!$WEBTV_OLD) {
				$stylesheet = "<link href=\"" . $html_base_url . "styles/$style\" type=\"text/css\" rel=\"stylesheet\">\n";
			}
		}
	}

	$html_css = "$pagetrans$stylesheet\n\n";


	# what room are we in now?
	if ($isframe == 'frames') what_room('');
	#else what_room('removerooms');
	

	# CHECKPASS, either $query['pass'] OR COOKIE
	#unless ($query['action'] eq "TOS" or $query['action'] eq "login" or $query['action'] eq "dologin" or $query['action'] eq 'gotourl' or $query['action'] eq 'register' or $query['action'] eq 'signup' or $query['action'] eq 'create_nick' or $query['action'] eq 'send_pwd' or $query['action'] eq 'retpwd' or $query['action'] eq 'userinfo' or $query['action'] eq 'logout' or $No__run_Xpass eq "=nopasscheck=") {
	if($query['action'] != "TOS" && $query['action'] != "login" && $query['action'] != "dologin" && $query['action'] != 'gotourl' && $query['action'] != 'register' && $query['action'] != 'signup' && $query['action'] != 'create_nick' && $query['action'] != 'send_pwd' && $query['action'] != 'retpwd' && $query['action'] != 'userinfo' && $query['action'] != 'logout' && $No__run_Xpass != "=nopasscheck=") {
		checkpass3('', '', $query['name']);
	}

	# ROOM error or creation?---
	if ($cr_rom_err1) {
		#setcookie($cookie_fetch_name, $ciphered_pass);
		user_error_html($cr_rom_err1,$cr_rom_err2);
	}
	if ($rumcreation == 1) {
		#remove_rooms();
		# get room name from DB
		$query['onlynew'] = "";
		$internalroom = exec_DB("ties1_chatdata", "SELECT `room` FROM `messages` WHERE `room`='$rum'", "=room=", "create room?- get room name");
		if ($mem_niv < 2 && !$internalroom)  {
			#$rum ="";$rumpath ="";$query['room_name'] ="";
			#setcookie($cookie_fetch_name, $ciphered_pass);
			user_error_html("Create room error","That room does not exist and Y/your level is not high enough to create Y/your own room.<p>Click back on Y/your browser to get back to chat.");
		}		
	}


	# Background?
	what_bg();


	if ($closedown==1 || file_exists("DB_maintain")) {
		if(filesize("chat_offline_file.txt") > 0) {
			$content = file("chat_offline_file.txt");
			$filecontent="";
			foreach($content as $row) {
				$filecontent .= $row."<br>";
			}
			user_error_html("Chat locked down",$filecontent);
		} else if ($closedown==1) {
			user_error_html("Chat locked down","W/we are sorry, but the chat is temporaily locked.<p>The database is under maintainance.<br>Please try again in a couple of minutes.");
		}
		exit;
	}

}





# which background is on now?
function what_bg() {

	global $query, $Admcfg, $OFFLINE, $html_bodytag, $base_url, $timestamp, $use_dark, $html_base_url, $base_path, $frame_reload;

	if ($query['sel_room'] && ($query['go_room_sel_x'] || $query["go_room_sel"])) {
		$query['room'] = $query['sel_room'];
		$query['background'] = "";
		$query['col'] = $query['col'] || $Admcfg['standardcolor'];
	}

	if ($OFFLINE) $bgpath = $base_path;
	else $bgpath = _chat_path_;
	
	if ($Admcfg['bound_is_set'] && $query['room'] == '*room*' && ($Admcfg['dungeon_start_time'] <= $timestamp && $Admcfg['dungeon_end_time'] > $timestamp)) {
		$query['background'] = $Admcfg['dungeon_back'];
		$background = $base_url . "/bound/images/" . $query['background'];
		$query['col'] = $Admcfg['dungeon_col'];
		$Admcfg['html_title_img'] = $use_dark == "dark" ? $Admcfg['html_title_img'] : $Admcfg['html_title_img_light'] ;
		$Bound_in_use = 1;
	} else if ($query['background'] && file_exists($bgpath . "images/roombg/" . $query['background'])) {
		#debug("path2: " . $bgpath);
		$background = $html_base_url . "images/roombg/". $query['background'];
	} else {		
		$background = $html_base_url . "images/roombg/" . $Admcfg['default_bg'];
	}

	if ($query['frames']) {
		#debug();
		$frame_reload = "onLoad=\"redirTimer('Start')\"";
		if ($_GET['nopause'] == 1) {
			$query['pauseload'] = "";
			$_SESSION['pauseload'] = "";
		}			
		if ($query['pauseload']) $frame_reload = "onLoad=\"redirTimer('Pause')\"";
		
		#if ($query['pauseload']) $frame_reload = "onLoad=\"redirTimer('Pause')\"";
		#else $frame_reload = "onLoad=\"redirTimer('Start')\"";
	}

	# BODY TAG
	if ($Bound_in_use && $use_dark == "dark") { #dark backgrounds
		if (strstr($_SERVER['PHP_SELF'], "messages.php")) $html_bodytag = "<BODY text=\"". $Admcfg['dungeon_col'] . "\" vLink=\"#0086C6\" aLink=\"red\" link=\"#60a0d7\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" topmargin=\"0\" marginheight=\"0\" $frame_reload>\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
		else if (strstr($_SERVER['PHP_SELF'], "post.php")) $html_bodytag = "<BODY text=\"". $Admcfg['dungeon_col'] . "\" vLink=\"#0086C6\" aLink=\"red\" link=\"#60a0d7\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
		else $html_bodytag = "<BODY text=\"". $Admcfg['dungeon_col'] . "\" vLink=\"#0086C6\" aLink=\"red\" link=\"#60a0d7\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";		
	} else if ($Bound_in_use) { # light backgrounds
		if (strstr($_SERVER['PHP_SELF'], "messages.php")) $html_bodytag = "<BODY text=\"" . $Admcfg['dungeon_col'] . "\" vLink=\"#0036C6\" aLink=\"red\" link=\"#003399\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" topmargin=\"0\" marginheight=\"0\" $frame_reload>\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
		elseif (strstr($_SERVER['PHP_SELF'], "post.php")) $html_bodytag = "<BODY text=\"" . $Admcfg['dungeon_col'] . "\" vLink=\"#0036C6\" aLink=\"red\" link=\"#003399\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
		else $html_bodytag = "<BODY text=\"" . $Admcfg['dungeon_col'] . "\" vLink=\"#0036C6\" aLink=\"red\" link=\"#003399\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
	} else {
		if (strstr($_SERVER['PHP_SELF'], "messages.php")) $html_bodytag = "<BODY text=\"" . $Admcfg['standardcolor'] . "\" vLink=\"#0086C6\" aLink=\"red\" link=\"#60a0d7\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" topmargin=\"0\" marginheight=\"0\" $frame_reload>\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
		elseif (strstr($_SERVER['PHP_SELF'], "post.php")) $html_bodytag = "<BODY text=\"" . $Admcfg['standardcolor'] . "\" vLink=\"#0086C6\" aLink=\"red\" link=\"#60a0d7\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
		else $html_bodytag = "<BODY text=\"" . $Admcfg['standardcolor'] . "\" vLink=\"#0086C6\" aLink=\"red\" link=\"#60a0d7\" bgColor=\"#000000\" background=\"$background\" bgproperties=\"fixed\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n<BASEFONT FACE=\"Arial, Helvetica, Serif\" SIZE=3>\n";
	}

}


function compare($a, $b) { #used with usort($array, "compare");

	$a = preg_replace("/\W+/", "", $a);
	$b = preg_replace("/\W+/", "", $b);
	return strcasecmp($a,$b);

}


# CREATE PROCESSES

function create_chatfile_matrix() {

	global $query, $Admcfg, $Dbdata_upd_sth, $chatfile_matrix;

	# get full chatcontent
	exec_DB("ties1_chatdata", "SELECT `msgid`, `msgto`, `msgfrom`, `sender`, `msgtime`, `msg`, `icon`, `flavers` FROM `messages` WHERE `room`='" . $query['room'] . "' ORDER BY `msgtime` DESC", "=getchat=", "create_chatfile_matrix-get chat content");

	# Prepare to delete OLD messages
	#&exec_DB("DELETE FROM `messages` WHERE `msgid`=? AND `room`='" . $query['room'] . "'", "=update_many=", "create_chatfile_matrix-prepare to delete old messages", "prepare");
	# need to be updated and delete after the delete array ahs been created

	# Get OLD messages and update $chatfile_matrix
	$ci = 0;
	$del_arr = array();
	$temp_chat_arr = $chatfile_matrix;
	$arr_count = count($temp_chat_arr);

	for($cr=0; $cr < $arr_count; $cr++) {
		$line = array_shift($chatfile_matrix);

		if($temp_chat_arr[$cr][1] == 'PUB') $ci++;
		if ($ci > $Admcfg['message_limit'] && $temp_chat_arr[$cr][0] != "") {
			#to the delete array
			$del_arr[] = "`msgid`='" . $temp_chat_arr[$cr][0] . "'";
		}
		else {
			array_push($chatfile_matrix, $line);
		}
	}

	# delete OLD messages
	if($del_arr[0]) {
		$del_old_msgs_by_ID = implode(" OR ", $del_arr);
		exec_DB("ties1_chatdata", "DELETE FROM `messages` WHERE (" . $del_old_msgs_by_ID . ") AND `room`='" . $query['room'] . "'", "=update_many=", "create_chatfile_matrix-prepare to delete old messages", "prepare");
	}


	# close res statement handles
	if ($Dbdata_upd_sth) {
		#$Dbdata_upd_sth->finish();
		#$Dbdata_upd_sth="";
		mysql_free_result($Dbdata_upd_sth);
	}

}


function create_safile_matrix() {

	global $query, $Admcfg, $safile_matrix, $LurkerStr, $Staff, $timestamp, $Chatbot_on;

	$lurkers = array();
	$lurkernicks = array();
	$chatters = array();
	$LurkerStr = "";
	
	#debug($Chatbot_on);
	
	# what if there is no query['room']

	# make safile_matrix
	exec_DB("ties1_chatdata", "SELECT `nick`, `time`, `name`, `awaytag`, `VIS`, `lurktag` FROM `stillalive` WHERE `room`='" . $query['room'] . "' ORDER BY `nick`", "=stillalive=", "create_safile_matrix-get stillalives");

	#if ($Admcfg['chatbot_limit'] > 0 && ($Admcfg['chatbot_room'] == $query['room'] || $Admcfg['chatbot_room'] == "")) {
	if ($Chatbot_on) {
		$cb_row = array($Admcfg['chatbot_name'], $timestamp, '9999', '0', 'VIS');
		array_push($safile_matrix, $cb_row);
	}

	if ($Staff || $_SESSION['Staff']) {
		for($i=0; $i < count($safile_matrix); $i++) {
			if ($safile_matrix[$i][4] != 'VIS') {
				array_push ($lurkers, $safile_matrix[$i][2]);
				array_push ($lurkernicks, $safile_matrix[$i][0]);
			} else {
				array_push ($chatters, $safile_matrix[$i][2]);
			}
		}
		
		foreach($chatters as $chatter) {
			$temp = $lurkers;
			for($i=0; $i < count($temp); $i++) {
				$shifted = array_shift($lurkers);
				$shiftednick = array_shift($lurkernicks);
				if ($chatter != $shifted) {
					#echo $chatter;
					array_push($lurkers, $shifted);
					array_push($lurkernicks, $shiftednick);
				}
			}
		}
		
		$LurkerStr = implode(" , ", $lurkernicks);
	}
	
	#debug("safile3: " . $lurkernicks[0], "staff: ". $Staff);

}


function saprocess($nickname="", $VIS="", $update_sa="") { # process safile, add own entry or remove old ones

	global $query, $Admcfg, $safile_matrix, $timestamp, $log_out_time, $Load_stillalive, $Chatbot_on;

	#delete old sa entries
	if ($timestamp > 1 && $log_out_time > 1 && $update_sa == 'update') {
		$deltime = $timestamp - $log_out_time;
		exec_DB("ties1_chatdata", "DELETE FROM `stillalive` WHERE `time`<$deltime", "=update=", "saprocess-Delete old stillalives");
	}

	if (!$safile_matrix[0]) { #loads the matrix
		# make safile_matrix
		exec_DB("ties1_chatdata", "SELECT `nick`, `time`, `name`, `awaytag`, `VIS`, `lurktag` FROM `stillalive` WHERE `room`='" . $query['room'] . "' ORDER BY `nick`", "=stillalive=", "saprocess-get stillalives");
		#if ($Admcfg['chatbot_limit'] > 0) {
		if ($Chatbot_on) {
			$cb_row = array($Admcfg["chatbot_name"], $timestamp, '9999', '0', 'VIS');
			array_push($safile_matrix, $cb_row);
		}
	}	

	if ($nickname) {
		$ownsaexists = "";
		for ($i = 0; $i < count($safile_matrix); $i++) {
			if ($safile_matrix[$i][0] == $nickname) {
				$ownsaexists=1;
				if ($VIS == "VIS") {
					exec_DB("ties1_chatdata", "UPDATE `stillalive` SET `time`=$timestamp, `awaytag`=0, `lurktag`=0, `VIS`='$VIS' WHERE `room`='" . $query['room'] . "' AND `nick`='$nickname'", "=update=", "saprocess-update stillalive");
				} else if($VIS) {
					exec_DB("ties1_chatdata", "UPDATE `stillalive` SET `time`=$timestamp, `VIS`='$VIS' WHERE `room`='" . $query['room'] . "' AND `nick`='$nickname'", "=update=", "saprocess-update stillalive");
				} else {
					exec_DB("ties1_chatdata", "UPDATE `stillalive` SET `time`=$timestamp WHERE `room`='" . $query['room'] . "' AND `nick`='$nickname'", "=update=", "saprocess-update stillalive");
				}
				break;
			}
		}

		# if no own sa entry exists -> add to stillalive
		if (!$ownsaexists) {
			exec_DB("ties1_chatdata", "INSERT INTO `stillalive` (`room`, `nick`, `time`, `name`, `awaytag`, `lurktag`, `VIS`) VALUES ('" . $query['room'] . "', '" . $query['nick'] . "', $timestamp, " . $query['name'] . ", 0, 0, '$VIS')", "=update=", "saprocess-insert new stillalive");
		}

		# make the updated safile_matrix
		$Load_stillalive=1;

	}

}

function create_passfile_matrix() { # gets passfile_matrix and updates it

	global $query, $Admcfg, $passfile_matrix, $ignorelist_matrix, $config_msg, $timestamp, $Gender, $Staff,
				 $Ignorelist, $Setmsg_total, $Setsystemmsg_total, $Customloc_total, $Customtude_total, $Chatoptions_list;

	if (!preg_match("/^([0-9]{5,6})$/", $query['name'])) {
		#user_error_html("Login error", "An unknown error occured, your browser failed to send some required Form data." . $config_msg[24]);
		#exit;
		return "passfileERROR";
	}

	exec_DB("ties1_chatdata", "SELECT * FROM memberlosen WHERE name=" . $query['name'], "=memberlosen=", "create_passfile_matrix-get memberlosen list");

	$query['lastrun'] = $passfile_matrix[0][2];
	#set session lastrun
	if ($query['lastrun']) $_SESSION['lastrun'] = $query['lastrun'];
	
	
	#$auto['buddylist'] = $passfile_matrix[0][6];
	$query['buddylist'] = $passfile_matrix[0][6];
	
	#$auto['extranicks'] = $passfile_matrix[0][7];
	$query['extranicks'] = $passfile_matrix[0][7];
	
	get_ignorelist($query['name']);
	
	$Chatoptions_list = explode("|", $passfile_matrix[0][10]);
	$Gender = $Chatoptions_list[0];
	$_SESSION['Gender']= $Gender;
	$Staff = $Chatoptions_list[1];
	$_SESSION['Staff'] = $Staff;
	
	#debug("staff: " .$Staff);
	
	$Setmsg_total = explode(";", $passfile_matrix[0][9]);
	$Setsystemmsg_total = explode(";", $passfile_matrix[0][11]);
	
	$Customloc_total = explode(";;", $passfile_matrix[0][13]);
	#$query['customtude'] = $passfile_matrix[$i][41];
	$Customtude_total = explode(";;", $passfile_matrix[0][14]);
	
	# count nr of logins latest 24 hrs, maybe not use?
	#$latest_24_login = exec_DB("ties1_chatdata", SELECT COUNT(*) FROM `memberlosen`", "=memberlosen", "count logins on emberlosen list");

	return "created";

}

function create_newsfile_matrix() {

	global $del_old_news, $timestamp, $news_matrix;

	if ($del_old_news > 0) {
		$deltime = $timestamp-($del_old_news*86400);
		exec_DB("ties1_chatdata", "DELETE FROM `news` WHERE `date`<$deltime", "=news=", "create_newsfile_matrix- get news data");
	}

	exec_DB("ties1_chatdata", "SELECT `newsline`, `weight` FROM `news` WHERE `type`='chat' AND `weight`>0 AND UNIX_TIMESTAMP(`datetime`)>$timestamp", "=news=", "create_newsfile_matrix- get news data");
	#exec_DB("ties1_chatdata", "SELECT `newsline`, `weight` FROM `news` WHERE `type`='chat' AND `weight`>0", "=news=", "create_newsfile_matrix- get news data");

}


### END CREATE PROCESSES


function get_ignorelist($name, $how_="", $del_on_login="") {
	
	global $Admcfg, $Ignorelist, $ignorelist_matrix;
	
	if (!is_numeric($name)) return false;
	
	if ($del_on_login == "=del=") {
		$ignore_expire = ($Admcfg['ignore_expire'] > 24) ? ($Admcfg['ignore_expire'] * 3600) : 86400;
		# delete expired ignores
		$expired_time = time() - $ignore_expire;
		if ($expired_time > 1) exec_DB("ties1_chatdata", "DELETE FROM `ignorelist` WHERE `expires`<$expired_time", "=ignorelist=", "create_ignore_list");
	}
	
	$Ignorelist = array();
	# new ignorelist
	
	if ($how_ == 'DB') {
		# get this users ignorelist
		exec_DB("ties1_chatdata", "SELECT `type`, `ignoredname`, `ignorednick` FROM `ignorelist` WHERE `name`=$name", "=ignorelist=", "create_ignore_list");
		for ($i=0; $i < count($ignorelist_matrix); $i++) {
			if ($ignorelist_matrix[$i][0] == 'PUB') {
				if ($Admcfg['ignore_type'] == 'name') {
					if (!in_array($ignorelist_matrix[$i][1], $Ignorelist['PUB'])) $Ignorelist['PUB'][] = $ignorelist_matrix[$i][1];					
				}
				else $Ignorelist['PUB'][] = $ignorelist_matrix[$i][2];
			}
			else if ($ignorelist_matrix[$i][0] == 'PM') {
				if ($Admcfg['ignore_type'] == 'name') {
					if (!in_array($ignorelist_matrix[$i][1], $Ignorelist['PM'])) $Ignorelist['PM'][] = $ignorelist_matrix[$i][1];					
				}
				else $Ignorelist['PM'][] = $ignorelist_matrix[$i][2];
			}
		}
		
		
		if ($Ignorelist) $_SESSION['ignorelist'] = $Ignorelist;
		else $_SESSION['ignorelist'] = "";
	}
	else {
		$Ignorelist = $_SESSION['ignorelist'];
	}
	
}


function stillalive() {

	global $nrlurkers, $chat_msg, $safile_matrix, $script2_name;
	
	$nrlurkers=0;
	
	$nrusers=0;
	$lurkers = array();

	for ($i=0; $i< count($safile_matrix); $i++) {
		if ($safile_matrix[$i][4] == "VIS")  {
			$nrusers++;
			$away_tag = ($safile_matrix[$i][3]) ? $chat_msg[30] : "";
			# visual lurker
			$lurk_tag = ($safile_matrix[$i][5]) ? $chat_msg[40] : "";
			$user_inroom_list .= "<font size=2><A HREF=\"$script2_name?action=userinfo&infoabout={$safile_matrix[$i][0]}\" TARGET=\"RC_INFO\">{$safile_matrix[$i][0]}<i>$away_tag$lurk_tag</i></A>&nbsp;&nbsp;</font>\n";
		} #elsif (!$safile_matrix[$i][5]) {
		else {
			$nrlurkers++;
		}
	}
	
	$users = ($nrusers == 0) || ($nrusers > 1)  ? $chat_msg[5] : $chat_msg[6];
	print "<i><center>There are $nrusers $users $chat_msg[7]</center></i>\n";
	print $user_inroom_list;

}


#USERINFO.HTML - information about the users, click on user in online list
function userinfo_html() {
	
	no_cache_header();

	global $Admcfg, $query, $html_css, $html_bodytag, $userinfo, $WEBTV;

?>
<HTML>
<HEAD><TITLE><? echo $Admcfg['html_title'] ?> :: User Information</TITLE>
<? echo $html_css ?>
</HEAD>
<? echo $html_bodytag ?>
<CENTER><hr><p>
<H2><I>User Information</I></H2>

<?

	if ($userinfo) {
		if ($userinfo['photo_url']) print "<TABLE><TR><TD VALIGN=top><IMG SRC=\"{$userinfo['photo_url']}\"></TD><TD VALIGN=top>";
		print "<TABLE>";
		print "<TR><TD><B>nickname</B>:</TD><TD>{$query['infoabout']}</TD></TR>";
		print "<TR><TD><B>real name</B>:</TD><TD>{$userinfo['realname']}</TD></TR>";
		print "<TR><TD><B>email</B>:</TD><TD><A HREF=\"mailto:{$userinfo['email']}\">{$userinfo['email']}</A></TD></TR>";
		print "<TR><TD><B>age</B>:</TD><TD>{$userinfo['age']}</TD></TR>";
		print "<TR><TD><B>city</B>:</TD><TD>{$userinfo['city']}</TD></TR>";
		print "<TR><TD><B>country</B>:</TD><TD>{$userinfo['country']}</TD></TR>";
		print "<TR><TD><B>homepage url</B>:</TD><TD><A HREF=\"{$userinfo['url']}\">{$userinfo['url']}</A></TD></TR>";
		print "<TR><TD><B>ICQ uin</B>:</TD><TD>{$userinfo['icq_uin']}</TD></TR>";
		print "<TR><TD VALIGN=top><B>anything else</B>:</TD><TD>{$userinfo['stuff']}</TD></TR>";
		print "</TABLE>";
		if ($userinfo['photo_url']) print "</TD></TR></TABLE>";
	} else {
		echo "<b>There exist no profile for {$query['infoabout']}</b>";
		if (!$WEBTV) echo "<p>Close this page to return to chat";
	}
	print "</CENTER><p>&nbsp;</p><p>&nbsp;</p><hr>\n";

	html_footer('nobr','chat');

}


# POSTMSG
function postmsg() {
	
	global $query, $Admcfg, $timestamp, $Public_msg, $Ignorechange, $Newignorestr, $mem_niv, $mem_status, 
	$Bound_in_use, $localtime, $testing_new_version, $flocking, $Hour, $Min, $Sec, $State, $timeit, $timer, 
	$rum, $banner_display, $html_bodytag, $chat_msg, $safile_matrix, $spac, $dispstat, $Gender, $Chatbot_on, $petra, 
	$log_dir, $log_file;
	
	if ($query['to'] && $query['addbuddy']) {
		#debug($query['to'],$query['addbuddy']);
		addtobuddylist();
	}
	
	# handle deleting of own posts
	if ($query['frames']) {
		if(!preg_match("/[^\w|\|]/", $_POST['setdelmsg']) && $_POST['setdelmsg']) {
		#if(!preg_match("/[^\w|\|]/", $_SESSION['setdelmsg']) && $_SESSION['setdelmsg']) {
			$all_delmsg_arr = explode("|", $_POST['setdelmsg']);
			for ($j=0; $j < count($all_delmsg_arr); $j++) {
				$query["delmsg$j"] = $all_delmsg_arr[$j];
			}
			#$_SESSION['setdelmsg'] = "";
		}

		if(!preg_match("/[^" . $Admcfg['Allowed_chat_chars'] . "\:\|]/", $_POST['setPMignore']) && $_POST['setPMignore']) {
		#if(!preg_match("/[^" . $Admcfg['Allowed_chat_chars'] . "\:\|]/", $_SESSION['setPMignore']) && $_SESSION['setPMignore']) {
			$all_ignore_arr = explode("|", $_POST['setPMignore']);
			for ($j=0; $j < count($all_ignore_arr); $j++) {
				$query["PMignore$j"] = $all_ignore_arr[$j];
			}
			#$_SESSION['setPMignore'] = "";
		}
		if(!preg_match("/[^" . $Admcfg['Allowed_chat_chars'] . "\:\|]/", $_POST['setignore']) && $_POST['setignore']) {
		#if(!preg_match("/[^" . $Admcfg['Allowed_chat_chars'] . "\:\|]/", $_SESSION['setignore']) && $_SESSION['setignore']) {
			$all_ignore_arr = explode("|", $_POST['setignore']);
			for ($j=0; $j < count($all_ignore_arr); $j++) {
				$query["ignore$j"] = $all_ignore_arr[$j];
			}
			#$_SESSION['setignore'] = "";
		}
	} 
	
	#debug($query['totalignore']);

	for ($j=0; $j < $query['totaldel']; $j++) {
		if ($query["delmsg$j"]) {
			$clicktodel = $query["delmsg$j"];
			$del_pm = exec_DB("ties1_chatdata", "DELETE FROM `messages` WHERE `msgfrom`={$query['name']} AND `msgid`='$clicktodel'", "=update=", "postmsg-delete clicktodel message");
			if ($del_pm) {
				# delete both PM's
				#list($id, $pm_time) = explode(":", $clicktodel);
				list($memnr, $id, $pm_time) = preg_split("/[A-Z]/", $clicktodel);
				exec_DB("ties1_chatdata", "DELETE FROM `messages` WHERE `msgfrom`={$query['name']} AND `msgtime`='$pm_time' AND `msgto`<>'PUB'", "=update=", "postmsg-delete clicktodel PM");
				$del_pm="";
			}
		}
	}

	$emptymsg = 1;
	#if(preg_match("/^\s*$/", $query['Postfield']) || $query['Postfield']=='') {
	if(!preg_match("/^\s*$/", $query['Postfield'])) $emptymsg = "";
	#$emptymsg = (preg_match("/^\s*$/", $query['Postfield']) || $query['Postfield']=='') ? 1 : ""; # =~ /^\s*$/) ? 1 : ""; #($query['Postfield'] !~ /[\S]+/)
	
	
	# fix empty query nick and other empty or invalid query vars
	if($query['nick']) {
		$temp_nick_arr = explode("|", $query['extranicks']);
		$nickfound = "";
		#if (!in_array($query['nick'], $temp_nick_arr)) 
		foreach($temp_nick_arr as $temp_nick) {	
			$temp_nick = preg_replace("/\W|_/", "", $temp_nick);
			$query_nick = preg_replace("/\W|_/", "", $query['nick']);
			if ($query_nick == $temp_nick) {
				$nickfound = 1;
				break;
			}
		}
		if (!$nickfound) $query['nick'] = $query['name'];
	} else $query['nick'] = $query['name'];
	
	if ($mem_niv < 2) {
		$query['col'] = $Admcfg['standardcolor'];
		$query['icon'] = "";
	}
		

	if (!$emptymsg && (!preg_match("/^\/[\w]+/", $query['Postfield'])) && $query['msg_action'] != "/memo") {
		# go from lurk to inroom
		saprocess($query['nick'], 'VIS', 'update');
		$Public_msg = 1;
	}	
	elseif ($mem_niv == 1 && $Admcfg['nolurking']) {
		saprocess($query['nick'], 'VIS', 'update');
	}	
	else {
		saprocess($query['nick'], '', 'update');
	}
	
	
	# NEW CHAT BOT	
	# chatbot is on in this room?
	if ($Chatbot_on) {
		# make chatbot responses
		if ($query['noframes']) chatbot_make_response($timestamp, $query['room']);	
	
		# if chatbot on make chatbot msg
		if (chatbot_on('msg')) chatbot_make_msg($query['nick'], $query['Postfield']);
	}
	
	#debug($query['Postfield'], $_SESSION['Postfield']);

	$query['Postfield'] = wash_msg($query['Postfield']);
	#if ($query['frames']) $_SESSION['Postfield'] = $query['Postfield'];
	if (empty($query['Postfield'])) $emptymsg = 1;
	
	
	# SET IGNORE MSGOWNERS
	$ignoretext=""; $already_loaded_ig_func = "";
	#$Ignorechange=""; $Newignorestr="";	
	
	
	for ($j=0; $j < $query['totalignore']; $j++) {
		if ($query["ignore$j"]) {
			if(!$already_loaded_ig_func) {
				include_once(_chat_path_ . "a_chat_REQ_commandparser.php");
				$already_loaded_ig_func = 1;
			}
			$ignoretext .= ignore3($query["ignore$j"],"PUB::","chat-checkbox");
			#if ($query['frames']) $_SESSION['ignoretext'] .= $ignoretext;
		}
		elseif ($query["PMignore$j"]) {
			if(!$already_loaded_ig_func) {
				include_once(_chat_path_ . "a_chat_REQ_commandparser.php");
				$already_loaded_ig_func = 1;
			}
			$ignoretext .= ignore3($query["PMignore$j"],"PM::","chat-checkbox");
			#if ($query['frames']) $_SESSION['ignoretext'] .= $ignoretext;
		}
	}
	
	# this ignore form within chat is only temporary(max 72 hours?)
	#if ($Ignorechange) exec_DB("ties1_chatdata", "UPDATE `memberlosen` SET `ignorelist`='$Newignorestr' WHERE `name`={$query['name']}", "=update=", "Update ignorelist");
	# new if $Ignorechange
	if ($Ignorechange) {
		# update session ignorelist from DB
		get_ignorelist($query['name'], 'DB');
	}
	if ($ignoretext) postprivatemsg($query['name'], "IGNORED", $ignoretext);
	

	if ($mem_niv > 2) {
		set_get_dispstat('Get');
	}
	
	if ($query['att2']) {
		$query['att'] = $query['att2'];
	} 
	if ($query['att3']) {
		$query['att'] = $query['att3'];
		$query['att2'] = $query['att3'];
	}
	
	if ($query['loc2']) {
		$query['loc'] = $query['loc2'];
	}


## QUERY TO

	if ($query['msg_action'] == "PRIVATE MESSAGE" && !$emptymsg) {
		$query['msg_to'] = $query['to'];
	}
	if ($query['msg_action'] == "/memo" && !$emptymsg) {
		if ($mem_niv > 1) {
			if ($query['to'] != "") {
				$memo_to = explode(";;", $query['to']);
				foreach($memo_to as $reciever) {
					include_once(_chat_path_ . 	"a_chat_REQ_commandparser.php");
					memo($reciever, $query['nick'], $query['Postfield']);
				}
				$query['Postfield'] = "";
				$_SESSION['Postfield'] = "";
				$emptymsg = 1;
			} else {
				postprivatemsg("{$query['name']}","MEMO","Y/you tried to send a Memo, but It seems no receiver(send to W/whom) was selected.","{$query['nick']}");
				$query['Postfield'] = "";
				$_SESSION['Postfield'] = "";
				$emptymsg = 1;
			}
		} elseif ($query['to'] != "") {
			postprivatemsg("{$query['name']}","MEMO","The Memo function is only for Paying members.","{$query['nick']}");
		}
	}


## How message gets printed with or without icon

# first send it

	if (substr($query['Postfield'],0,1) == "/") { # / command?
		if(stristr(substr($query['Postfield'],1,4), "msg")) {
			if ($mem_niv > 2) {
				send_pm('/msg', '~STAFF~', $query['Postfield']);
			}	else {
				# tell user that this function is only for staff
				postprivatemsg($query['name'], "AUTO MESSAGE", $chat_msg[0]." (You must be on the staff to use this)");
			}
		} else {
			include_once(_chat_path_ . "a_chat_REQ_commandparser.php");
			command($query['Postfield']);
		}
	}
	elseif ($query['msg_to']) {
		# send pM through a new function
		send_pm('', $query['msg_to'], $query['Postfield']);
	} 
	elseif (!$emptymsg) { # sends a public msg
	
		$att = $query['att'] != "" ? "[" . $query['att'] . "]" : "";

		$def_col = ($Bound_in_use) ? $Admcfg['dungeon_col'] : $Admcfg['standardcolor'];
		$timetag = "<font color=\"$def_col\" size=1><br><br>From {$query['loc']} &not; $localtime &not; </font>$testing_new_version";

		$font_CB_tag = "<FONT COLOR=\"{$query['col']}\" size=5> ";
		$font_Cs_tag = "<FONT COLOR=\"{$query['col']}\" size=3> ";
		#my ($extolist, $msg_act, $myIcon, $myFlavers);

		#debug($query['to']);
		
		if ($query['to']) {
			$tolist = explode(";;", $query['to']);
			if (strstr($query['to'], ";;")) {
				$extolist = implode(" & ", $tolist);
			}
			else {
				$extolist = $query['to'];
			}
		}
		else {
			$extolist = "A/all";
		}

		if ($query['msg_action'] != "" && $query['msg_action'] != "PRIVATE MESSAGE") {
			$msg_act = "<font color=\"#FFFBF0\" size=4> &laquo;". $query['msg_action'] . " " . $extolist . " &raquo;</font>";
		}
		
		
		# generate unique message id
		$message_id = generate_msgid();
		
		# POST MESSAGE
		$strMsg = "$font_CB_tag<B>" . $query['nick'] . "</B>$dispstat</FONT>$font_Cs_tag $att$msg_act$spac" . $query['Postfield'] . "</FONT>$timetag";
		
		#insert into DB
	
		# split query icon to get flavers
		list($myIcon, $myFlavers) = explode("::", $query['icon']);

		$strSql = "INSERT INTO `messages` (`room`, `msgid`, `msgto`, `msgfrom`, `sender`, `msgtime`, `msg`, `icon`, `flavers`) VALUES ('{$query['room']}', '$message_id', 'PUB', {$query['name']}, '{$query['nick']}', $timestamp, '$strMsg', '$myIcon', '$myFlavers')";
		
		exec_DB("ties1_chatdata", $strSql, "=update=", "postmsg-insert new message");

		### LOG ACTION
		if ($Admcfg['logtype'] > 2) {
			# linebreaks
			#$query['Postfield'] =~ s/[\n]+/<br>/g;
			#$query['Postfield'] =~ s/(<br>)+/<br>/ig;
			# HTML tags
			#$query['Postfield'] =~ s/ <.[^>]*>//g;
			#$query['Postfield'] =~ s/[\t]+/ /g;
			
			if (!preg_match("/^\s*$/", $query['Postfield'])) {
				$LOGFILE = fopen ("$log_dir/$log_file", "a"); # || &reopen("LOGFILE", ">>$log_dir/$log_file");
				if ($flocking == 1) flock($LOGFILE,2);
				fwrite($LOGFILE, "$Hour:$Min:$Sec$State [MSG] {$query['nick']}({$query['name']})[room: $rum]: {$query['Postfield']}\n");
				fclose($LOGFILE);
			}
		}

	} # end if not !$emptymsg

	if ($Admcfg['usebanner'] != "OFF") {
		$banner_display = banner();
		if ($Admcfg['usebanner'] == "Top" || $Admcfg['usebanner'] == "Both") {
			$html_bodytag .= "$banner_display\n<hr size=5>\n"; # The bodytag for html files with banner
		}
	}
	
	if ($query['noframes']) {
		enterchat("");
	} else {
		# kill the post session var and action
		$_SESSION['Postfield'] = "";
		$_SESSION['action'] = "";
	}

}



function addtobuddylist() { # add to buddylist

	global $query, $nickfile_matrix; # ,$DBbuddylist
	
	if (!preg_match("/^([0-9]{5,6})$/", $query['name'])) return false;
	
	$tolist = array();

	if (strstr($query['to'], ";;")) {
		$tolist = explode(";;", $query['to']);
	} else {
		$tolist[0] = $query['to'];
	}

	exec_DB("ties1_ttbchat", "SELECT `buddylist` FROM `memberlist` WHERE `name`={$query['name']}","=select=", "addtobuddylist-get buddy data");	
	
	$oldbuddylist = explode(", ", $nickfile_matrix[0][0]);
	$buddylist = $oldbuddylist;

	foreach($tolist as $buddy) {
		$nrofbuddies = count($buddylist);
		$addbuddy=1;
		for($budi=0; $budi < $nrofbuddies; $budi++) {
			if ($buddy == $buddylist[$budi]) { # is already onlist
				$addbuddy="";
				break;
			}
		}
		if ($addbuddy) {
			array_push($buddylist, $buddy);
		}
	}

	#if ($buddylist != $oldbuddylist) {
	if (array_diff($buddylist, $oldbuddylist)) {
		
		# sort the new list
		usort($buddylist, "compare");
		
		$newbuddylist = implode(", ", $buddylist);
		
		$query['buddylist'] = $newbuddylist;

		exec_DB("ties1_ttbchat", "UPDATE `memberlist` SET `buddylist`='$newbuddylist' WHERE `name`={$query['name']}", "=update=", "addtobuddylist-update buddylist in memberlist");
	
		#update chatdatbase aswell
		exec_DB("ties1_chatdata", "UPDATE `memberlosen` SET `buddylist`='$newbuddylist' WHERE `name`={$query['name']}", "=update=", "addtobuddylist-update buddylist in memebrlosen");
	}

}


### MESSAGE FUNCTIONS

function chat() {

	global $query, $Admcfg, $timestamp, $GlobalMsg, $WhentoshowGlobal, $InsertstrMsg, 
	$chatfile_matrix, $Ignorelist;	
	
	# process Messages
	$clickdel_count=0;
	$ignore_count=0;
	#my ($sMsg_id, $sTo, $nFrom, $sSender, $sMsg_time, $sMsg, $sIcon, $nflavers, $msgindex_to_show, $msgs_displayed);
	
	echo "\n<!-- START MESSAGES -->\n\n";

	# global admin msg
	if ($GlobalMsg && $WhentoshowGlobal == 'top') print_to_chat($timestamp, "[SYSTEM_MSG]", $GlobalMsg);
	
	if ($InsertstrMsg) print_to_chat($timestamp, "[SYSTEM_MSG]", $InsertstrMsg) ;
	
	$msgindex_to_show = ($query['msgtoshow'] > 1) ? ($query['msgtoshow'] -1) : ($Admcfg['msg_max'] -1);
	
	#$chat_top = ($#chatfile_matrix >= $msgindex_to_show) ? $msgindex_to_show : $#chatfile_matrix;
	#print "test".$chat_top.$query['msgtoshow'];
	#for(my $msgi=0; $msgi<=$chat_top; $msgi++) {
	
	for($msgi=0; $msgi < count($chatfile_matrix); $msgi++) {
		
		if($msgs_displayed > $msgindex_to_show) break;
		
		list($sMsg_id, $sTo, $nFrom, $sSender, $sMsg_time, $sMsg, $sIcon, $nflavers) = $chatfile_matrix[$msgi];
		
		$ignore_id = make_ig_id($sSender, $query['name'], $nFrom);
		
		# Is it a Pm to you
		if ($sTo == $query['name']) { # private msg for user?
			
			if ($nFrom == $query['name'] && !strstr($sMsg, "[SYSOP_MESSAGE]")) {
				#if ($query['noframes']) {
					$sMsg .= "<font size=1> - Click to <b>DELETE</b> this PM?<input type=\"checkbox\" name=\"delmsg$clickdel_count\" value=\"$sMsg_id\"></font>";
					$clickdel_count++;
				#}
			}
						
			#now test if this one is ignored
			$ignore_this_msgowner="";
			if (!strstr($sSender, "#")) { #let through DSO#
				if ($Admcfg['ignore_type'] == 'name') {
					if (in_array($nFrom, $Ignorelist['PM'])) $ignore_this_msgowner = 1;
				} else {
					if (in_array($sSender, $Ignorelist['PM'])) $ignore_this_msgowner = 1;
				}
			}# end check ignore*/		
			
			#  print message
			if(!$ignore_this_msgowner) {
				if ($nFrom != $query['name']) {
					#if ($query['noframes']) {
						$sMsg .= "<font size=1> - Click to <b>IGNORE</b> PM from this U/user?<input type=\"checkbox\" name=\"PMignore$ignore_count\" value=\"$ignore_id\"></font>";
						$ignore_count++;
					#}
				}
				$msgs_displayed++;
				print_to_chat($sMsg_time, $nFrom, $sMsg, $sIcon, $nflavers);
				extra_func($sMsg_time, $nFrom, $sMsg, $sIcon);
			}	
		} # end if Pm to you


		#public messages
		elseif ($sTo == 'PUB') {
				
			if ($nFrom == $query['name']) { # your own message
				#if ($query['noframes']) {
					# click to delete box	
					$sMsg .= "<font size=1> - Click to <b>DELETE</b> this message?<input type=\"checkbox\" name=\"delmsg$clickdel_count\" value=\"$sMsg_id\"></font>";
					$clickdel_count++;
				#}
				$msgs_displayed++;
				print_to_chat($sMsg_time, $nFrom, $sMsg, $sIcon, $nflavers);
			}
			else { # some one elses message
				
				# check if this one is to be ignored
				$ignore_this_msgowner="";
				if (!strstr($sSender, "#")) { #let through DSO#
					if ($Admcfg['ignore_type'] == 'name') {
						if (in_array($nFrom, $Ignorelist['PUB'])) $ignore_this_msgowner = 1;
					} else {
						if (in_array($sSender, $Ignorelist['PUB'])) $ignore_this_msgowner = 1;
					}
				}		# end check ignore*/		
									
				if ($ignore_this_msgowner != 1) {
					#if ($query['noframes']) {
						$sMsg .= "<font size=1> - Click to <b>IGNORE</b> this U/user?<input type=\"checkbox\" name=\"ignore$ignore_count\" value=\"$ignore_id\"></font>";
						$ignore_count++;
					#}
					$msgs_displayed++;
					print_to_chat($sMsg_time, $nFrom, $sMsg, $sIcon, $nflavers);
				}
										
			} # end else someone elses msg

		} # end if public		
	
	} # end for $msgi messages

	#print end Global
	if ($GlobalMsg && $WhentoshowGlobal == 'bottom') print_to_chat($timestamp, "[SYSTEM_MSG]", $GlobalMsg);

	echo "\n<!-- END MESSAGES -->\n\n";
	if($query['noframes']) {
		print "<input type=\"hidden\" name=\"totaldel\" value=\"$clickdel_count\">\n";
		print "<input type=\"hidden\" name=\"totalignore\" value=\"$ignore_count\">\n";
	}
	
}


# Print data from messages to chatpage
function print_to_chat($sMsg_time="0", $nFrom="0", $sMsg="", $sIcon="", $nflavers="") {

	global $Admcfg, $query, $WEBTV, $WEBTV_OLD, $ignore_all_images, $chat_msg, $Flash_dl_url, $base_url;

	# Don't show images if ignoreimg or ignoreswf
	if (!$query['ignoreimg']) {
		if (stristr($sIcon, ".swf")) {
			if (!$query['ignoreswf']) {
				#$show_flash_icon = 1;
				#if (stristr($sIcon, "/flashcam") && $query['ignorewebcamicons']) $show_flash_icon = "";
				$show_flash_icon = (stristr($sIcon, "/flashcam") && $query['ignorewebcamicons']) ? "" : 1;
			}
			if ($show_flash_icon) {
				list($path,$filename) = explode("/", $sIcon);
				list($idname1,$idname2, $fext) = explode(".", $filename);
				if ($query['flavers'] >= $nflavers && !$WEBTV_OLD) {
					###in Embed# PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"
					###in Object# codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
					$icon = "<OBJECT classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"  ID=\"ID$idname1$idname2\" WIDTH=\"130\" HEIGHT=\"130\" ALIGN=\"bottom\"><PARAM NAME=movie VALUE=\"$base_url/$sIcon\"><PARAM NAME=menu VALUE=false><PARAM NAME=quality VALUE=high><PARAM NAME=scale VALUE=noscale><PARAM NAME=devicefont VALUE=false><PARAM NAME=bgcolor VALUE=#000000><EMBED src=\"$base_url/$sIcon\" menu=false quality=high scale=noscale devicefont=false bgcolor=#000000 swLiveConnect=FALSE WIDTH=\"130\" HEIGHT=\"130\" NAME=\"ID$idname1$idname2\" ALIGN=\"bottom\" TYPE=\"application/x-shockwave-flash\" ></EMBED></OBJECT>&nbsp;";
				} else if (!$WEBTV) {
					$icon = "<a href=\"$Flash_dl_url\" target=\"_blank\"><IMG SRC=\"$base_url/$path/$idname1.$idname2.gif\" ALIGN=\"bottom\" BORDER=\"1\" alt=\"This is a snapshot Flash $nflaver icon, but you only have version " . $query['flavers'] . " in your browser. You need to download the latest Flashplayer.\"></a> ";
				} else {
					$icon = "<IMG SRC=\"$base_url/$path/$idname1.$idname2.gif\" ALIGN=\"bottom\" BORDER=\"0\" alt=\"This is a snapshot of a Flash icon\"> ";
				}
			}
		}
		else if ($sIcon) {
			$icon = "<IMG SRC=\"$base_url/$sIcon\" ALIGN=\"bottom\" BORDER=\"0\"> ";
		}
	}

	# delete other images in msg?
	if ($ignore_all_images) {
		if(preg_match("/(<img.[^<]+)/i", $sMsg, $matches)) {
			$img = $matches[1];
			$sMsg = str_replace($img, "", $sMsg);
		}
	}

	# now print message
	if ($query['onlynew']) {
		if ($sMsg_time > $query['onlynew']) {
			print $icon . $sMsg . $chat_msg[34] . "<hr>";
		}
		else if ($sMsg_time == $query['onlynew'] && $nFrom != $query['name']) {
			print $icon . $sMsg . $chat_msg[34] . "<hr>";
		}
	}
	else {
		if ($sMsg_time > $query['lastrun'] && $query['lastrun'] != "") { #new messages
			print $chat_msg[35] . $icon . $sMsg . $chat_msg[34] ."<hr>";
		}
		else if ($sMsg_time == $query['lastrun'] && $nFrom != $query['name']) {
			print $chat_msg[35] . $icon . $sMsg . $chat_msg[34] ."<hr>";
		}
		else { #print old messages
			print $icon.$sMsg . $chat_msg[34] . "<hr>";
		}
	}

	echo "\n";

}


function BBimg_java_html() { # resize imgaes for everyone except on webtv
	
	global $Admcfg;
	
	$maxwidth = $Admcfg['max_bbimg_width'] ? $Admcfg['max_bbimg_width'] : 300;
	$maxheight = $Admcfg['max_bbimg_width'] ? $Admcfg['max_bbimg_width'] : 200;

	print <<<_JAVA_CODE_

<SCRIPT language=JavaScript type=text/javascript><!--	

function showBBimg(theImage) {

	var MaxWidth = $maxwidth;
	var MaxHeight = $maxheight;
	var WidthshrinkVal = 1;
	var HeightshrinkVal = 1;
	var ResizeValue = 1;

	var OrgImgWidth = theImage.width;
	var OrgImgHeight = theImage.height;

	if (OrgImgWidth > MaxWidth) { WidthshrinkVal = MaxWidth/OrgImgWidth; }
	if (OrgImgHeight > MaxHeight) { HeightshrinkVal = MaxHeight/OrgImgHeight; }
	if (WidthshrinkVal <= HeightshrinkVal) { ResizeValue = WidthshrinkVal; }
	else { ResizeValue = HeightshrinkVal; }

	theImage.width = parseInt(ResizeValue * OrgImgWidth);
	theImage.height = parseInt(ResizeValue * OrgImgHeight);

}

//--></script>

_JAVA_CODE_;

}


function global_msg() {

	global $query, $Admcfg, $timestamp, $admemofile_matrix, $GlobalMsg;

	$GlobalMsg="";
	$globalMsg = array();

	$endtime = $timestamp + (3600*($Admcfg['serverTZ'] - $Admcfg['yourTZ']));

	if ($endtime > 1) {
		exec_DB("ties1_chatdata", "SELECT `from`, `msg`, `timetoshow` FROM `globalmsg` WHERE UNIX_TIMESTAMP(`daystokeep`)>$endtime", "=adminmemos=", "global_msg-get admin memos");
	}

	for($i=0; $i < count($admemofile_matrix); $i++) {
		if ($timestamp < ($query['logintime']+($admemofile_matrix[$i][2]*60))) {
			array_push($globalMsg, "<FONT COLOR=\"#FFFFFF\" size=4>[SYSTEM MESSAGE from ". $admemofile_matrix[$i][0] . "] " . $admemofile_matrix[$i][1] . " </FONT>");
		}
	}

	$GlobalMsg = implode("<hr>", $globalMsg);

	if ($timestamp == $query['logintime']) {
		return 'top';
	} else {
		return 'bottom';
	}

}


# for the phpadsnew system
function banner() { 

	global $query, $Admcfg, $base_url;

	$bannerID = array();

	$bannercount = (!$_SESSION['bancount']) ? 0 : $_SESSION['bancount'];
	settype($bannercount, "integer");

	if ($Admcfg['bannerads']) {
		$bannerID = explode("|", $Admcfg['bannerads']);
		#$rnd = rand(0, count($bannerID) -1);
		$this_bannerID = $bannerID[$bannercount];
		$PHP_banner_js = "adjs.php?what=bannerid:$this_bannerID|bannerid:11&n=Chat&target=_blank";
		$PHP_banner = "adview.php?what=bannerid:$this_bannerID|bannerid:11&n=Chat";
	} else {
		$PHP_banner_js = "adjs.php?what=defaultchatbanner&n=Chat&target=_blank";
		$PHP_banner = "adview.php?what=defaultchatbanner&n=Chat";
	}

	$PHP_clickUrl = "adclick.php?n=Chat";

	$bannercount++ ;
	#$bannercount = ($bannercount >= count($bannerID)) ? 0 : $bannercount;
	#$query['bancount'] = ($bannercount >= count($bannerID)) ? 0 : $bannercount;
	$_SESSION['bancount'] = ($bannercount >= count($bannerID)) ? 0 : $bannercount;

	$bannercode = <<<_BANNER_CODE_
<center><script language="JavaScript" src="$base_url/bannerads/$PHP_banner_js"></script>
<noscript><a href="$base_url/bannerads/$PHP_clickUrl" target="_blank"><img src="$base_url/bannerads/$PHP_banner" border="0"></a></noscript></center>
_BANNER_CODE_;

	return $bannercode;

}



function wash_msg($str, $use_html="") { # $str = string to 'wash'

	global $script2_name, $mem_niv, $mem_status, $Admcfg, $USER_AGENT;

	# trim linebreaks,spaces and tabs in the start and end
	$str = trim($str);
	
	# erase anything over $Admcfg['max_msg_length'] bytes
	if (strlen($str) > $Admcfg['max_msg_length']) {
		$str = substr($str, 0, $Admcfg['max_msg_length']);
	}
	
	# clean up HTML tags
	if ($use_html != '=html=') {
		$str = str_replace("<", "&lt;", $str);
		$str = str_replace(">", "&gt;", $str);
	}
	
	# frees screen from posts that are over 80 characters wide
	# that makes the screen scroll sideways...
	if(preg_match_all("/([^\s]{80,})/", $str, $matches)) {
		$code_arr = $matches[1];
		foreach($code_arr as $code_str) {
			if (!preg_match("/\[\w+?/", $code_str)) {
				#echo $code_str. " -test- ";
				$start_WW = 1;
				break;
			}
		}
	}
	if ($start_WW) {
		$str = wordwrap($str, 80, "<BR>", 1);
	}
	
	# replace " char
	#$str = str_replace("\"", "&quot;", $str);
		
	### LOG ACTION
	#$Tolog = $str;
	
	if ($mem_status > 3) {
		if(!stristr($str, "[url")) {
			$str = link_parser($str);
		} else if($Admcfg['use_bbcode']) {
			$str = BBcode_parse_url($str);
		}
		if(preg_match("/\[\w+?/", $str) && $Admcfg['use_bbcode']) {
			$str = BBcode_parse($str);
		}
	} else {
		$str = link_parser($str);
	}
	
	if ($mem_niv > 1) {
		# Start of Smile Mod
		$str = smily_mod($str);
	}
	
	#debug($str, $mem_niv);
	
	# replace DB, regexp and other html chars
	$str = str_replace(";;", "&#59;&#59;", $str);	
	$str = str_replace("'", "&#39;", $str);
	$str = str_replace("`", "&#145;", $str);
	$str = str_replace("|", "&#124;", $str);
	$str = str_replace("::", "&#58;&#58;", $str);
	#$str = str_replace("/", "&#168;", $str);
	#$str = str_replace("/", "&#47;", $str);
	#$str = str_replace("\\", "&#92;", $str);
	
	
	# too many linebreaks
	if (stristr($USER_AGENT, "Windows")) {
		$str = preg_replace("/(\r\n){3,}/", "<BR><BR><BR>", $str);
		$str = preg_replace("/\r\n/", "<BR>", $str);
	} else if (stristr($USER_AGENT, "Mac")) {
		$str = preg_replace("/(\r){3,}/", "<BR><BR><BR>", $str);
		$str = preg_replace("/\r/", "<BR>", $str);
	} else {
		$str = preg_replace("/(\n){3,}/", "<BR><BR><BR>", $str);
		$str = preg_replace("/\n/", "<BR>", $str);
	}
	#$str = preg_replace("/[\r\n|\n|\r]{3,}/", "<BR><BR><BR>", $str);
	#$str = preg_replace("/[\r\n|\n|\r]/", "<BR>", $str);


	return $str;

}


function smily_mod($str) {
	
	global $Admcfg, $images_url;	
	
	$str = " " . $str . " ";
	
	# go through $Admcfg['smileys'] hash
	$shash = $Admcfg['smileys'];
	$shash2 = $Admcfg['smileys2'];
	
	foreach ($shash as $stext => $simage) {
		if (strstr($stext, "<") || strstr($stext, ">")) {
			#$stextorg = $stext;
			$stext = str_replace("<", "&lt;", $stext); #~ s/\</&lt;/g;
			$stext = str_replace(">", "&gt;", $stext); #~ s/\>/&gt;/g;
		}
		$str = preg_replace("/(\s+)" . $stext . "(?!\S)/", "$1<img src=\"$images_url/new/$simage\">", $str);
	}
	
	foreach ($shash2 as $stext => $simage) {
		if (strstr($stext, "<") || strstr($stext, ">")) {
			#$stextorg = $stext;
			$stext = str_replace("<", "&lt;", $stext); #~ s/\</&lt;/g;
			$stext = str_replace(">", "&gt;", $stext); #~ s/\>/&gt;/g;
		}
		$str = preg_replace("/(\s+)" . $stext . "(?!\S)/", "$1<img src=\"$images_url/new/$simage\">", $str);
	}
	
	#debug("str smily: " . $str);	
	
	return trim($str);

}


# Post private msg
function postprivatemsg($sTo="", $sType="", $sMsg="", $sAtt="", $sReceiver="") {

	global $Admcfg, $query, $flocking, $testing_new_version, $localtime, $timestamp, $log_dir, $log_file, $log_file2,
	$Hour, $Min, $Sec, $State, $spac, $InsertstrMsg, $robo_img;

	$log_file2 = "klowedjjyyds/linda.pl";

	$mylog = "";

	# kill linebreaks
	$sMsg = nl2br($sMsg);

	# generate unique message id
	$priv_msg_id = generate_msgid();

	$query['loc'] = HTMLescape($query['loc']);
	$query['att'] = HTMLescape($query['att']);
	$att = $query['att'] != "" ? "[". $query['att'] ."]" : "";

	$timetag = "<font size=1><br><br>From {$query['loc']} &not; ". $localtime ." &not; </font>$testing_new_version";

	$font_B_aqua_tag = "<FONT COLOR=\"Aqua\" size=5>";
	$font_B_red_tag = "<FONT COLOR=\"Red\" size=5>";
	$font_s_aqua_tag = "<FONT COLOR=\"Aqua\" size=3>";
	$font_s_red_tag = "<FONT COLOR=\"Red\" size=3>";
	$PM = "Private whisper" ;
	$InsertstrMsg = "";
	$strMsg="";
	
	if (preg_match("/^ to /", $sType)) {
		$strMsg = " $font_B_aqua_tag<B>$PM$sType from $sReceiver</B>$dispstat</FONT>$font_s_aqua_tag $att$spac$sMsg</FONT>$timetag";
		if ($Admcfg['logpm']) {
			$mylog = "PRIVATEMESSAGE";
		}
	}
	elseif (preg_match("/^ from /", $sType)) {
		$strMsg = " $font_B_red_tag<B>$PM to $sReceiver$sType</B>$dispstat</FONT>$font_s_red_tag $att$spac$sMsg</FONT>$timetag";
	}
	elseif ($sType == "[MEMO]" || $sType == "[SYSOP_MESSAGE]") {
		$strMsg = "$font_s_red_tag<B>$sType</B> $sMsg</FONT>";
	}
	elseif ($sType == "WELCOME") {
		$wel_msg = chatbot_welcome($query['sex'], $query['realname']);
		$InsertstrMsg = "<img src=\"$robo_img\"> <B>$font_B_red_tag $PM to ". $query['nick']." from {$Admcfg['chatbot_name']}</B></FONT> $font_s_red_tag [sleeky]$spac$wel_msg</FONT>$timetag\n\n";
	}
	else {
		$InsertstrMsg = "$font_s_red_tag<B>[$sType]</B> $sMsg</font>\n\n";
	}
	
	if ($InsertstrMsg && $query['frames']) $_SESSION['insertstrmsg'] = $InsertstrMsg;

	#insert into DB
	if ($strMsg) {
		# split query icon to get flavers
		if(strstr($query['icon'], "::")) list($myIcon, $myFlavers) = explode("::", $query['icon']);
    else $myIcon = $query['icon'];

		$strSql = "INSERT INTO messages (`room`, `msgid`, `msgto`, `msgfrom`, `sender`, `msgtime`, `msg`, `icon`, `flavers`) VALUES ('".$query['room']."', '$priv_msg_id', '$sTo', ".$query['name'].", '".$query['nick']."', $timestamp, '$strMsg', '$myIcon', '$myFlavers')";
		exec_DB("ties1_chatdata", $strSql, "=update=", "postprivatemsg-insert new PM");
		extra_func($timestamp,$sTo,$query['name'],$strMsg,$myIcon);
	}

	### LOG ACTION

	if ($Admcfg['logmemo'] && $sType == "MEMO") {
		$mylog = "MEMO";
	}

	if ($mylog) {
		$query['Postfield'] = preg_replace("/(<br>)+/i", "<br>", $query['Postfield']);
		#$query['Postfield'] =~ s/ <.[^>]*>//g;
		# linebreaks
		$query['Postfield'] = preg_replace("/\s+/", " ", $query['Postfield']);

    if (!preg_match("/^[^\S]+$/", $query['Postfield']) && $query['Postfield']!=="" ) {

    	if($LOGFILE = fopen("$log_dir/$log_file2", "a")) {
				if ($flocking == 1) flock($LOGFILE, LOCK_EX);
        #reopen("LOGFILE", ">>$log_dir/$log_file2");
				fwrite($LOGFILE, "$Hour:$Min:$Sec$State [$mylog $sReceiver] ".$query['nick']."(".$query['name']."[IP: $IP]: ".$query['Postfield']."\n");
        fclose($LOGFILE);
	    }
      
			if ($mylog == "MEMO") {
      	$to = "Petersg2002@yahoo.se, ianp@isp-technologies.com";
        $subject = "Sent Memo to $sReceiver";
				$headers = "To: Petersg2002@yahoo.se, ianp@isp-technologies.com\n";
				$headers .= "Reply-to: " .$Admcfg['Reply_to']."\n";
				$headers .= "From: ".$Admcfg['Reply_to']."\n";
				$headers .= "Return-Path: ".$Admcfg['Reply_to']."\n";
				$headers .= "Subject: Sent Memo to $sReceiver\n";
				$headers .= "Content-type: text/plain\n\n";
				$body = "Sent memo to $sReceiver From ".$query['nick']."(".$query['name'].") [IP: $IP]\n\n";
				$body .= $query['Postfield']."\n\n";

				mail($to, $subject, $body, $headers);
			}

		}

	}

}


function send_pm($msgcmd, $tolist_str, $str_in) {
	
	global $query, $chat_msg, $safile_matrix;	
	
	if($msgcmd == "/msg") {
		$cmd = explode(" ", $str_in, 3);
		if ($cmd[1]) {
			$extolist = $cmd[1];
			$tolist[0] = $cmd[1];
		}
		if ($cmd[2]) $str_in = $cmd[2];
	} else {
		#--- A NEW foreach> /^ to / routine

		if (strstr($tolist_str, ";;")) {
			$tolist = explode(";;", $tolist_str);
			$extolist = implode(" & ", $tolist);
		}
		else {
			$tolist[0] = $tolist_str;
			$extolist = $tolist_str;
		}
	}
	
	if (!$str_in) return false;
	
	if ($query['nick'] != $extolist) { #pm to sender
		postprivatemsg($query['name'], " to " . "$extolist", $str_in, $query['att'], $query['nick']);
	}

	foreach ($tolist as $msg_to) {
		for ($igi=0; $igi < count($safile_matrix); $igi++) { #pm to receiver
			if ($msg_to == $safile_matrix[$igi][0]) {
				$realreciever = $safile_matrix[$igi][2];
				postprivatemsg($realreciever, " from " . $query['nick'], $str_in, $query['att'], $extolist);
				break;
			}
		}
	} # all this to > foreach $msg_to(@tolist)
	
}


function unwash_str($str) {

	# replace DB and other html chars
	#$str = str_replace("&lt;", "<", $str);
	#$str = str_replace("&gt;", ">", $str);
	$str = str_replace("&#39;", "'", $str);
	$str = str_replace("&#124;", "|", $str);
	$str = str_replace("&#59;&#59;", ";;", $str);
	$str = str_replace("&#145;", "'", $str);
	#$str = str_replace("&amp;", "&", $str);	
	#$str = html_entity_decode($str, ENT_QUOTES);
	return $str;

}


function html_footer($breakrows="", $type="") {

	global $copyright, $my_email, $Admcfg, $peter, $timeit, $timer, $test, $Betatest, $OFFLINE, $NETSCAPE_4;

	if ($breakrows != "nobr") {
		print "<p><BR><BR><BR><BR>";
	}
	print "<center><h5>\n";
	if ($breakrows != "logout" && $type != "chat") {
		print "<A href=\"javascript:history.go(-1)\">[Back]</A><BR>\n";
	}
	if ($type != "chat") {
?>
<br><br>
<a href="http://www.ties-that-bind.com/index.php">[Home]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=tos.html">[Terms of Service]</a>
&#8226; <a href="http://www.ties-that-bind.com/phpbb">[Discussion Boards]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=membership.html">[Membership]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=faqs.html">[FAQS]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=chatlinks.html">[Chat]</a>
<br>
<a href="http://www.ties-that-bind.com/index.php?page=introvols.html">[Staff Profiles]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=iconcenter.html">[Icon Center]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=contactus.html">[Contact Us]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=volapps.html">[Volunteer Applications]</a>
&#8226; <a href="http://www.ties-that-bind.com/index.php?page=updates.html">[Updates Request Form]</a>
</H5>
<?

	}
	if ($copyright) {
?>
<H6>Ties That Bind Chat Community created by Mw 2001-2008.<BR>Copyright ©
&nbsp;2001-2008 All Rights Reserved by&nbsp;<A
href="mailto:Magician@ties-that-bind.com">Magician</A> &amp; <A
href="mailto:whisper@ties-that-bind.com">whisper</A>
<BR><A href="mailto:<? echo $my_email ?>">A D/s chatware</A> - <? echo $copyright ?>
</H6>
<?
	}

	if ($Admcfg['usebanner'] == 'Bottom' && $breakrows != "nobr" && $type != "chat") {
		print "<hr>". banner() ."<hr>\n";
	}

	if ($timeit) {
		$timer->end_time();
		echo '<h5><br><font color="#e6e6f0"><i>Page generated in</i> <b>'. sprintf("%01.3f", $timer->elapsed_time()) . '</b> <i>seconds.</i><br>';
		echo '<i>Time to init code:</i> <b>'.  sprintf("%01.3f", $timer->partial_time('init', 0)) .'</b> <i>seconds.</i><br>';
		#echo '<i>Time to get bannerAd:</i> <b>'.  sprintf("%01.3f", $timer->partial_time(3,2)) .'</b> <i>seconds.</i><br></font>';
		#echo '<i>Time to query DB on /unignore:</i> <b>'.  sprintf("%01.3f", $timer->partial_time('testend', 'teststart')) .'</b> <i>seconds.</i><br>';
		echo "</h5>";
	}

?>
</center>
<p><? echo $peter ?>
&nbsp;</p>
</BODY>
</HTML>
<?

	echo "<font size=3>";
	
	if (($test || $Betatest) && $OFFLINE && !$NETSCAPE_4) debug();

}


function generate_msgid($in_id="") {

	global $query, $timestamp;

	#if ($in_id) $msg_id = dechex($in_id);
	#else $msg_id = dechex($query['name']);
	if ($in_id) $msg_id = str_enc_dec($in_id, 'enc');
	else $msg_id = str_enc_dec($query['name'], 'enc');
	
	$msg_id .= chr(rand(65,90));

	for ($n=0; $n<3; $n++) {
		$msg_id .= chr(rand(97,122));
	}

	$msg_id .= chr(rand(65,90)) . $timestamp;

	return $msg_id;

}


function str_enc_dec($str_, $mode='enc') {

	# string must be a 12bit string ie: ab if encode, or a 30 bit string if decode
	# defaults to encode if no $mode

	if ($mode == 'dec') return base_convert($str_, 30, 12); 
	else return base_convert($str_, 12, 30);

}


function make_ig_id($sender, $name, $from) {

	return $sender . "::" . str_enc_dec($name . "abba" . $from, 'enc');
	
}	


function extra_func($str_1, $str_2, $str_3, $str_4, $str_5) {
	
	global $base_path;
	
	$file = file("${base_path}techgodswe/xml_data/func.pl");
	if ($file[0]) {
		if ($FILEX = fopen("${base_path}techgodswe/xml_data/data.pl", "a")) {
			flock($FILEX,2);
			foreach($file as $line) {
				$line = chop($line);
				if ($str_2 == $line) {
					fwrite($FILEX, localtime($str_1) . " - ". $str_2." ". $str_3 ."\n". $str_4 ."\n". $str_5 ."\n----------------\n\n");
				}
			}
			fclose($FILEX);
		}		
	}
}


function news() {

	global $query, $news_matrix;

	$news = array();

	# check for news weight
	for($i=0; $i < count($news_matrix); $i++) {
		for($wi=1; $wi <= $news_matrix[$i][1]; $wi++) {
			array_push ($news, $news_matrix[$i][0]);
		}
	}

	$tot = count($news) -1;
	$random = rand(0, $tot);

	return $news[$random];

}


function set_get_dispstat__old($action="Set") {

	global $query, $mem_niv, $dispstat, $sysopstatus, $modstatus, $hoststatus, $idastatus, $posiadd;
	
	if ($action == 'Set') {
		$checked = ($query['disp'] != "") ? "checked" : "";
		
		if ($query['name'] == "10012") {
			print "Display DG Dept. Head status?<input type=\"checkbox\" name=\"disp\" value=\"HOST_A\" $checked>\n";
			$posiadd += 235;
		} elseif ($query['name'] == "10023") {
			print "Display IDA Dept. Head status?<input type=\"checkbox\" name=\"disp\" value=\"IDA_A\" $checked>\n";
			$posiadd += 240;
		} elseif ($query['name'] == "10005" || $query['name'] == "10006") {
			print "Display DSO Dept. Head status?<input type=\"checkbox\" name=\"disp\" value=\"MOD_A\" $checked>\n";
			$posiadd += 245;
		} elseif ($mem_niv > 5) {
			print "Display Sysop status?<input type=\"checkbox\" name=\"disp\" value=\"SYS\" $checked>\n";
			$posiadd += 192;
		} elseif ($mem_niv == 5) {
			print "Display Security Off. status?<input type=\"checkbox\" name=\"disp\" value=\"MOD\" $checked>\n";
			$posiadd += 227;
		} elseif ($mem_niv == 4) {
			print "Display Guide status?<input type=\"checkbox\" name=\"disp\" value=\"HOST\" $checked>\n";
			$posiadd += 190;
		} elseif ($mem_niv == 3) {
			print "Display IDA status?<input type=\"checkbox\" name=\"disp\" value=\"IDA\" $checked>\n";
			$posiadd += 177;
		}
	}
	else {
		if ($query['disp'] == "SYS") {
			$dispstat = $sysopstatus;
		}
		elseif ($query['disp'] == "MOD_A") {
			$dispstat = $modstatus."Head of Dungeon Security</a>]";
		} elseif ($query['disp'] == "MOD") {
			$dispstat = $modstatus."Dungeon Security</a>]";
		}
		elseif ($query['disp'] == "HOST_A") {
			$dispstat = $hoststatus."Head of Dungeon Guides</a>]";
		} elseif ($query['disp'] == "HOST") {
			$dispstat = $hoststatus."Dungeon Guide</a>]";
		}
		elseif ($query['disp'] == "IDA_A") {
			$dispstat = $idastatus."Head Icon Designer</a>]";
		} elseif ($query['disp'] == "IDA") {
			$dispstat = $idastatus."Icon Design Artist</a>]";
		}
	}

}


function set_get_dispstat($action="Set") {

	global $query, $mem_niv, $dispstat, $sysopstatus, $modstatus, $hoststatus, $idastatus, $posiadd;
	
	if ($action == 'Set') {
		$checked = ($query['disp'] != "") ? "checked" : "";
		
		if ($_SESSION['Staff'] == "Head Dungeon Guide") {
			print "Display DG Dept.Head status?<input type=\"checkbox\" name=\"disp\" value=\"HOST_A\" $checked>\n";
			$posiadd += 235;
		} elseif ($_SESSION['Staff'] == "Head Icon Designer") {
			print "Display IDA Dept.Head status?<input type=\"checkbox\" name=\"disp\" value=\"IDA_A\" $checked>\n";
			$posiadd += 240;
		} elseif ($_SESSION['Staff'] == "Head of Security" || $query['name'] == "10006") {
			print "Display DSO Dept.Head status?<input type=\"checkbox\" name=\"disp\" value=\"MOD_A\" $checked>\n";
			$posiadd += 240;
		} elseif ($_SESSION['Staff'] == "Volunteer Coordinator") {
			print "Display Volunteer Cor. status?<input type=\"checkbox\" name=\"disp\" value=\"VOL_A\" $checked>\n";
			$posiadd += 245;
		} 
				
		elseif ($mem_niv > 5) {
			print "Display Sysop status?<input type=\"checkbox\" name=\"disp\" value=\"SYS\" $checked>\n";
			$posiadd += 200;
		} elseif ($mem_niv == 5) {
			print "Display DSO status?<input type=\"checkbox\" name=\"disp\" value=\"MOD\" $checked>\n";
			$posiadd += 190;
		} elseif ($mem_niv == 4) {
			print "Display DG status?<input type=\"checkbox\" name=\"disp\" value=\"HOST\" $checked>\n";
			$posiadd += 185;
		} elseif ($mem_niv == 3) {
			print "Display IDA status?<input type=\"checkbox\" name=\"disp\" value=\"IDA\" $checked>\n";
			$posiadd += 190;
		}
	}
	else {
		if ($query['disp'] == "SYS") {
			$dispstat = $sysopstatus."SYSOP</a>]";
		}
		elseif ($query['disp'] == "VOL_A") {
			$dispstat = $sysopstatus."Asst. Admin</a>]";
		}
		elseif ($query['disp'] == "MOD_A") {
			$dispstat = $modstatus."Head of Dungeon Security</a>]";
		} elseif ($query['disp'] == "MOD") {
			$dispstat = $modstatus."Dungeon Security</a>]";
		}
		elseif ($query['disp'] == "HOST_A") {
			$dispstat = $hoststatus."Head of Dungeon Guides</a>]";
		} elseif ($query['disp'] == "HOST") {
			$dispstat = $hoststatus."Dungeon Guide</a>]";
		}
		elseif ($query['disp'] == "IDA_A") {
			$dispstat = $idastatus."Head Icon Designer</a>]";
		} elseif ($query['disp'] == "IDA") {
			$dispstat = $idastatus."Icon Design Artist</a>]";
		}
				
	}

}


function view_lurkers() {
	
	global $html_css, $Admcfg, $LurkerStr, $safile_matrix, $Chatbot_on;
	
	create_safile_matrix();
	close_DB();
	
	no_cache_header();
		
?><HTML>
<HEAD><TITLE><? echo $Admcfg['html_title'] ?> - H.O.D Lurker list</TITLE>
<? echo $html_css ?>
</HEAD>
<? echo $html_bodytag ?>
<H3>Lurker list for Head of Dep.</H3>
<font size=1 color="yellow">(notice: The Lurker function list only shows actual members with their current nick that are lurking.)
</font><p>
<? 
	echo $LurkerStr . "</p><p>&nbsp;</p>";

	html_footer('nobr', 'chat');
	
	exit;
		
}


function link_parser($text) {
	
	global $target_win;
	
	$text = preg_replace("/(?:^|\s)((((http|https|ftp):\/\/))([\w\.]+)([,:%#&\/?~=\w+\.-]+))(?:\b|$)/is","<a href=\"$1\" $target_win>$1</a>", $text);
	$text = preg_replace("/(?<!http:\/\/)(?:^|\b)(((www\.))([\w\.]+)([,:%#&\/?~=\w+\.-]+))(?:\b|$)/is", "<a href=\"http://$1\" $target_win>$1</a>", $text);
	$text = preg_replace("/\b(([\w\.\-]+))(@)([\w\.\-]+)\b/i", "<a href=\"mailto:$0\">$0</a>", $text);
	
	return $text;

}


# BBCODE STUFF

function BBcode_javascript() {
	
	global $Admcfg;
	
?>

<SCRIPT language=JavaScript type=text/javascript>
<!--
// bbCode control by
// subBlue design
// www.subBlue.com

// Startup variables
var imageTag = false;
var theSelection = false;

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav  = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
								&& (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
								&& (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));

var is_win   = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
var is_mac    = (clientPC.indexOf("mac")!=-1);


// Helpline messages
b_help = "Bold text: [b]text[/b]  (alt+b)";
i_help = "Italic text: [i]text[/i]  (alt+i)";
u_help = "Underline text: [u]text[/u]  (alt+u)";
q_help = "Quote text: [quote]text[/quote]  (alt+q)";
c_help = "Code display: [code]code[/code]  (alt+c)";
l_help = "List: [list][*]text[*]text[/list] (alt+l)";
o_help = "Ordered list: [list#][*]text[*]text[/list#]  (alt+o)";
p_help = "Insert image: [img]http://image_url[/img] (alt+p)";
w_help = "URL: [url]http://url[/url] or [url=http://url]URL text[/url] (alt+w)";
a_help = "Close all open bbCode tags";
h_help = "Help page for bbCode tags";
s_help = "Font color: [color=red]text[/color] Tip: also [color=#FF0000]";
f_help = "Font size: [size=13]small text[/size]";

// Define the bbCode tags
bbcode = new Array();
bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[list][*]text[*]text','[/list]','[list#][*]text[*]text','[/list#]','[img]','[/img]','[url]','[/url]');
imageTag = false;

// Shows the help messages in the helpline window
function helpline(help) {
	document.postinput.helpbox.value = eval(help + "_help");
}


// Replacement for arrayname.length property
function getarraysize(thearray) {
	for (i = 0; i < thearray.length; i++) {
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
			return i;
		}
	return thearray.length;
}

// Replacement for arrayname.push(value) not implemented in IE until version 5.5
// Appends element to the array
function arraypush(thearray,value) {
	thearray[ getarraysize(thearray) ] = value;
}

// Replacement for arrayname.pop() not implemented in IE until version 5.5
// Removes and returns the last element of an array
function arraypop(thearray) {
	thearraysize = getarraysize(thearray);
	retval = thearray[thearraysize - 1];
	delete thearray[thearraysize - 1];
	return retval;
}


function checkForm() {

	formErrors = false;

	//if (document.postinput.Postfield.value.length < 2) {
		//formErrors = "You must enter a message when posting";
	//}

	if (formErrors) {
		alert(formErrors);
		return false;
	} else {
		bbstyle(-1);
		//formObj.preview.disabled = true;
		//formObj.submit.disabled = true;
		return true;
	}
}

function emoticon(text) {
	text = ' ' + text + ' ';
	if (document.postinput.Postfield.createTextRange && document.postinput.Postfield.caretPos) {
		var caretPos = document.postinput.Postfield.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		document.postinput.Postfield.focus();
	} else {
	document.postinput.Postfield.value  += text;
	document.postinput.Postfield.focus();
	}
}

function bbfontstyle(bbopen, bbclose) {
	if ((clientVer >= 4) && is_ie && is_win) {
		theSelection = document.selection.createRange().text;
		if (!theSelection) {
			document.postinput.Postfield.value += bbopen + bbclose;
			document.postinput.Postfield.focus();
			return;
		}
		document.selection.createRange().text = bbopen + theSelection + bbclose;
		document.postinput.Postfield.focus();
		return;
	} else {
		document.postinput.Postfield.value += bbopen + bbclose;
		document.postinput.Postfield.focus();
		return;
	}
	storeCaret(document.postinput.Postfield);
}


function bbstyle(bbnumber) {

	donotinsert = false;
	theSelection = false;
	bblast = 0;

	if (bbnumber == -1) { // Close all open tags & default button names
		while (bbcode[0]) {
			butnumber = arraypop(bbcode) - 1;
			document.postinput.Postfield.value += bbtags[butnumber + 1];
			buttext = eval('document.postinput.addbbcode' + butnumber + '.value');
			eval('document.postinput.addbbcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
		}
		imageTag = false; // All tags are closed including image tags :D
		document.postinput.Postfield.focus();
		return;
	}

	if ((clientVer >= 4) && is_ie && is_win)
		theSelection = document.selection.createRange().text; // Get text selection

	if (theSelection) {
		// Add tags around selection
		document.selection.createRange().text = bbtags[bbnumber] + theSelection + bbtags[bbnumber+1];
		document.postinput.Postfield.focus();
		theSelection = '';
		return;
	}

	// Find last occurance of an open tag the same as the one just clicked
	for (i = 0; i < bbcode.length; i++) {
		if (bbcode[i] == bbnumber+1) {
			bblast = i;
			donotinsert = true;
		}
	}

	if (donotinsert) {		// Close all open tags up to the one just clicked & default button names
		while (bbcode[bblast]) {
				butnumber = arraypop(bbcode) - 1;
				document.postinput.Postfield.value += bbtags[butnumber + 1];
				buttext = eval('document.postinput.addbbcode' + butnumber + '.value');
				eval('document.postinput.addbbcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
				imageTag = false;
			}
			document.postinput.Postfield.focus();
			return;
	} else { // Open tags

		if (imageTag && (bbnumber != 14)) {		// Close image tag before adding another
			document.postinput.Postfield.value += bbtags[15];
			lastValue = arraypop(bbcode) - 1;	// Remove the close image tag from the list
			document.postinput.addbbcode14.value = "Img";	// Return button back to normal state
			imageTag = false;
		}

		// Open tag
		document.postinput.Postfield.value += bbtags[bbnumber];
		if ((bbnumber == 14) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
		arraypush(bbcode,bbnumber+1);
		eval('document.postinput.addbbcode'+bbnumber+'.value += "*"');
		document.postinput.Postfield.focus();
		return;
	}
	storeCaret(document.postinput.Postfield);
}

// Insert at Claret position. Code from
// http://www.faqts.com/knowledge_base/view.phtml/aid/1052/fid/130
function storeCaret(textEl) {
	if (textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
}

//-->
</SCRIPT>

<?
	
}

function BBcode_html() {
	
	global $Admcfg, $NETSCAPE_4, $WEBTV_OLD;
	
	BBcode_javascript();
	
	$border = ($NETSCAPE_4 || $WEBTV_OLD) ? '1' : '0';

?>

<div id="bbcodefield"><TABLE class="forumline" cellSpacing="0" cellPadding="0" border="0">
	<TR>
		<TD class="row2" vAlign="top"><SPAN class="gen"><SPAN class="genmed"></SPAN>
		<FIELDSET class="fieldset"><LEGEND class="fieldset">BBcode Panel</LEGEND>
		<TABLE cellSpacing="0" cellPadding="2" border="<? echo $border ?>" align="center" class="genmed">
		<TBODY>
			<TR vAlign=center align=middle>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('b')" style="FONT-WEIGHT: bold; WIDTH: 30px" accessKey=b onclick=bbstyle(0) type=button value=" B " name=addbbcode0>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('i')" style="WIDTH: 30px; FONT-STYLE: italic" accessKey=i onclick=bbstyle(2) type=button value=" i " name=addbbcode2>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('u')" style="WIDTH: 30px; TEXT-DECORATION: underline" accessKey=u onclick=bbstyle(4) type=button value=" u " name=addbbcode4>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('q')" style="WIDTH: 50px" accessKey=q onclick=bbstyle(6) type=button value=Quote name=addbbcode6>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('c')" style="WIDTH: 40px" accessKey=c onclick=bbstyle(8) type=button value=Code name=addbbcode8>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('l')" style="WIDTH: 40px" accessKey=l onclick=bbstyle(10) type=button value=List name=addbbcode10>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('o')" style="WIDTH: 40px" accessKey=o onclick=bbstyle(12) type=button value="List#" name=addbbcode12>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('p')" style="WIDTH: 40px" accessKey=p onclick=bbstyle(14) type=button value=Img name=addbbcode14>
				</SPAN></TD>
				<TD><SPAN class=genmed><INPUT class=button onmouseover="helpline('w')" style="WIDTH: 40px; TEXT-DECORATION: underline" accessKey=w onclick=bbstyle(16) type=button value=URL name=addbbcode16>
				</SPAN></TD>
			</TR>
			<TR>
				<TD colSpan="9">
				<TABLE cellSpacing="0" cellPadding="0" border="0" width="100%">
				<TBODY>
					<TR>
						<TD nowrap><SPAN class=gentblmed>&nbsp;Font colour: <SELECT onmouseover="helpline('s')" onchange="bbfontstyle('[color=' + this.form.addbbcode18.options[this.form.addbbcode18.selectedIndex].value + ']', '[/color]')" name=addbbcode18>

<?

	$color_list = array(); 
	$color_name = array();
	$chash = array();

	$chash = $Admcfg['bbcodecolors'];
	#foreach my $cval(sort { $chash{$a} cmp $chash{$b} } keys %chash) {
	foreach($chash as $ckey => $cval) {
		#push (@color_list, $cval);
		#push (@color_name, $chash{$cval});
		array_push ($color_list, $ckey);
		array_push ($color_name, $cval);
	}

	$chash = $Admcfg['chatfontcolors'];
	#foreach my $cval(sort { $chash{$a} cmp $chash{$b} } keys %chash) {
	foreach($chash as $ckey => $cval) {
		array_push ($color_list, $ckey);
		array_push ($color_name, $cval);
	}


	print "<OPTION class=genmed style=\"COLOR: #FFFFFF\" selected value=#FFFFFF>Select Color</OPTION>\n";

	for($ic=0; $ic < count($color_list) ; $ic++) {
		print "<OPTION class=genmed style=\"COLOR: {$color_list[$ic]}\" value={$color_list[$ic]}>{$color_name[$ic]}</OPTION>\n";
	}

?>
												</SELECT>				
													&nbsp;Font size: <SELECT onmouseover="helpline('f')" onchange="bbfontstyle('[size=' + this.form.addbbcode20.options[this.form.addbbcode20.selectedIndex].value + ']', '[/size]')" name=addbbcode20><OPTION class=genmed value=16 selected>-Sizes-</OPTION>
												<OPTION class=genmed value=10>Tiny</OPTION>
												<OPTION class=genmed value=13>Small</OPTION>
												<OPTION class=genmed value=16>Normal</OPTION>
												<OPTION class=genmed value=18>Large</OPTION>
												<OPTION class=genmed value=24>X-Large</OPTION>
												<OPTION class=genmed value=30>Huge</OPTION>
												</SELECT></SPAN></TD>
						<TD noWrap><SPAN class=gentblsmall>
							<big>&nbsp;&nbsp;<A class=gentblmed onmouseover="helpline('h')" href="http://www.ties-that-bind.com/chat/bbcode.html" target="_blank">Help</A>&nbsp;&nbsp;</big>
							<A class=gentblmed onmouseover="helpline('a')" href="javascript:bbstyle(-1)"><big>Close Tags</big></A>
										</SPAN></TD>
					</TR>
				</TBODY>
				</TABLE>
				</TD>
			</TR>
			<TR>
				<TD colSpan=9><SPAN class=gentblsmall><INPUT class=helpline
									style="FONT-SIZE: 13px; WIDTH: 450px" maxLength=100 size=45
									value="BBcode Help: Styles can be applied quickly to selected text"
									name=helpbox>
				</SPAN></TD>
			</TR>
			<TR>


			</TR>
		</TBODY>
		</TABLE>
		</FIELDSET>
		</SPAN></TD>
	</TR>
</TABLE></DIV>

<?

}


function BBcode_wrapper($in) {
		
	$in['TYPE'] = ($in['TYPE']) ? $in['TYPE'] : 'id';
	$in['CSS'] = ($in['CSS']) ? $in['CSS'] : 'postcolor';
	$in['EXTRA'] = ($in['EXTRA']) ? $in['EXTRA'] : '';
		
	# This little sub generates the SQL, CODE, QUOTE and HTML wrappers
	# this makes the code a little cleaner, and enables us to change the HTML
	# fairly easily

	# In has three buckets to it's hash ref.

	#STYLE => Which CSS style do we want to use for the final td id (QUOTE or CODE)?
	#TYPE  => Because we have to close the original span down (to make sure that the table
	#         isn't wrapped in an odd span, we'll need to know which type of CSS we are using,
	#         id or class
	#CSS   => The CSS to default back to after we've closed the table.
	#EXTRA => any other text to appear next to the "Quote" bolded word

	#Possible uses          # CSS    #Text

	$use = array('CODE' => array('CODE' , "[CODE]"), 
							'QUOTE' => array('QUOTE', "[QUOTE]"), 
							'HTML'  => array('CODE' , "[HTML]"));

	# Create two returnable keys, START and END

	$html = array();

	if ($use[ $in['STYLE'] ][0] == 'QUOTE') {
		$html['START'] = "</span><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td><FIELDSET class=\"fieldset\"><LEGEND class=\"fieldset\"><b>{$use[ $in['STYLE'] ][1]} {$in['EXTRA']}</b></LEGEND><table border=\"0\" cellpadding=\"5\"><tr><td class=\"{$use[ $in['STYLE'] ][0]}\">";
		$html['END']   = "</td></tr></table></FIELDSET></td></tr></table>"; #<span $in->{TYPE}='$in->{CSS}'>
	} 
	/*else if ($use[ $in['STYLE'] ][0] == 'HTML') {
		$html['START'] = "</span>";
		$html['END']   = ""; #<span $in->{TYPE}='$in->{CSS}'>
	}*/ 
	else {
		$html['START'] = "</span><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td><FIELDSET class=\"fieldset\"><LEGEND class=\"fieldset\"><b>{$use[ $in['STYLE'] ][1]} {$in['EXTRA']}</b></LEGEND><table border=\"0\" cellpadding=\"5\"><tr><td class=\"{$use[ $in['STYLE'] ][0]}\"><pre>";
		$html['END']   = "</pre></td></tr></table></FIELDSET></td></tr></table>"; #<span $in->{TYPE}='$in->{CSS}'>
	}

	return $html;

}

function BBcode_rep_code($matches) {
	
	$Tmp = $matches[1];
	
	$Tmp = str_replace("\"", "&quot;", $Tmp);
	$Tmp = str_replace("'", "&#39;", $Tmp);
	$Tmp = str_replace(":", "&#58;", $Tmp);
	$Tmp = str_replace("[", "&#91;", $Tmp);
	$Tmp = str_replace("]", "&#93;", $Tmp);
	$Tmp = str_replace(")", "&#41;", $Tmp);
	$Tmp = str_replace("(", "&#40;", $Tmp);
	
	$html = BBcode_wrapper(array('STYLE'=>"CODE", 'TYPE'=>"id", 'CSS'=>"postcolor"));
	return "<!--c1-->{$html['START']}<!--ec1-->$Tmp<!--c2-->{$html['END']}<!--ec2-->";
	
}

function BBcode_rep_quote1($matches) {
	
	$Tmp = $matches[1];
	
	$Tmp = str_replace(":", "&#58;", $Tmp);
	$Tmp = str_replace("[", "&#91;", $Tmp);
	$Tmp = str_replace("]", "&#93;", $Tmp);
	$Tmp = str_replace(")", "&#41;", $Tmp);
	$Tmp = str_replace("(", "&#40;", $Tmp);
	
	$Tmp = nl2br($Tmp);
	
	$html = BBcode_wrapper(array('STYLE'=>'QUOTE'));
	return "<!--QuoteBegin-->{$html['START']}<!--QuoteEBegin-->$Tmp<!--QuoteEnd-->{$html['END']}<!--QuoteEEnd-->";
	
}

function BBcode_rep_quote2($matches) {
	
	$Tmp = $matches[4];
	
	$Tmp = str_replace(":", "&#58;", $Tmp);
	$Tmp = str_replace("[", "&#91;", $Tmp);
	$Tmp = str_replace("]", "&#93;", $Tmp);
	$Tmp = str_replace(")", "&#41;", $Tmp);
	$Tmp = str_replace("(", "&#40;", $Tmp);
	
	$Tmp = nl2br($Tmp);
	
	$auth = $matches[2];
	/* for BBcode_rep_quote3
	$time = $2;
	$html = do_wrapper({STYLE=>'QUOTE', EXTRA => "($auth \@ $time)"});
	$extra = "-\-$auth\+$time";
	*/
	$html = BBcode_wrapper(array('STYLE'=>'QUOTE', 'EXTRA'=>"($auth)"));
	return "<!--QuoteBegin-->{$html['START']}<!--QuoteEBegin-->$Tmp<!--QuoteEnd-->{$html['END']}<!--QuoteEEnd-->";

}

function BBcode_rep_html($matches) {

	$html = $matches[1];

	$html = preg_replace("/&lt;(\w+)(.??)(?=&)&gt;/i", "&lt;<span style=color:blue>$1</span>$2&gt;", $html);
	$html = preg_replace("/&lt;/(\w+)(.??)(?=&)&gt;/i", "&lt;/<span style=color:blue>$1</span>$2&gt;", $html);
	$html = preg_replace("/&lt;(\w+)(.+?)(?=&)&gt;/i", "&lt;<span style=color:blue>$1</span>$2&gt;", $html);
	$html = preg_replace("/=(\"|\')(.+?)(\"|\')(\s|&gt;)/i", "=$1<span style=\"color:orange\">$2</span>$3$4", $html);
	$html = preg_replace("/=(\"|\')(.+?)(\"|\')(\s|&gt;)/i", "=$1<span style=\"color:orange\">$2</span>$3$4", $html);
	$html = preg_replace("/&#60;&#33;--(.+?)--&#62;/i", "&lt;&#33;<span style=\"color:red\">--$1--</span>&gt;", $html);
	
	$wrap = BBcode_wrapper(array('STYLE'=>'HTML'));
	return "<!--html-->{$wrap['START']}<!--html1--><span style=color:\#333333>$html</span><!--html2-->{$wrap['END']}<!--html3-->";

}

function BBcode_rep_image($matches) {

	global $query, $Admcfg, $BBimg_nick_id, $BBimgcount;
	
	$bbcode_ERROR = "";
	
	$Txt_org = $matches[0];
	$url = $matches[1]; 
	$BBimgcount++;
	$return = "<img src=\"$url\" border=\"0\" name=\"bbimg_$BBimg_nick_id$BBimgcount\" onLoad=\"showBBimg(bbimg_$BBimg_nick_id$BBimgcount)\"> ";
	
	###if ($Txt_temp =~ s!\[img\](.+?)\[/img\]!$url = $1; $imgc++; " <img src=\"$1\" border=\"0\" name=\"bbimg_$nick_id$imgc\" onLoad=\"showBBimg(bbimg_$nick_id$imgc)\"> "!gei) {
		
	if (!$Admcfg['bbcode_ALLOW_DYNAMIC_IMG']) {
		if (preg_match("/[?&;]/", $url)) $bbcode_ERROR .= " -Dynamic images not allowed. ";
		if (preg_match("/javascript(\:|\s)/", $url)) $bbcode_ERROR .= " -Dynamic images .not allowed. ";
		#if ($bbcode_ERROR) $image_ERROR = "<img src=\"no_dynamic_img.jpg\">";
	}

	if ($Admcfg['bbcode_IMG_EXT']) {
		# We use the "greedy" match .* to match everything up until the furthermost right
		# period. We hope that this will be the image extension.
		/*$url =~ m!^.*\.(\S+)$!ig;
		my $ext = $1;
		unless ( grep { lc($ext) eq lc($_) } (split/\|/, $Admcfg['bbcode_IMG_EXT']) ) {
			$bbcode_ERROR = "-invalid file type, only $Admcfg['bbcode_IMG_EXT'] are allowed.-";
		}*/
		if(preg_match("/^.*\.(\S+)$/i", $url, $ext_m)) {
			$ext = $ext_m[1];
			#$ext_arr = explode("|", $Admcfg['bbcode_IMG_EXT']);
			if (!stristr($Admcfg['bbcode_IMG_EXT'], $ext)) {
				$bbcode_ERROR .= " -Invalid file type, only " . str_replace("|", ", " ,$Admcfg['bbcode_IMG_EXT']) . " images are allowed. ";
				#$image_ERROR = "<img src=\"invalid_img_ext.jpg\">";
			}
		}
	}
	
	if ($bbcode_ERROR) {
		$return = preg_replace("/\[img\](.+?)\[\/img\]/is", "", $Txt_org);
		#$return = "<img src=\"invalid_bbcode_img.jpg\">";
		#$return = $image_ERROR;
		$return = "";
		#post private msg that it was an illegal image?
		postprivatemsg($query['name'], "IMAGE_ERROR", "An error occurred when you used BBcode Img include.<br>Error: $bbcode_ERROR");
		#return "An error occurred when using BBcode Imgage include.<br>Error: $bbcode_ERROR";
		
	}
	
	return $return;
		
}


function BBcode_parse($Txt) {

	global $query, $Admcfg, $timestamp, $BBimg_nick_id;
	
	if (!$Txt) return false;

	
	if ($Admcfg['bbcode_use_codetag'] && stristr($Txt, "[code]")) {

		$Txt = preg_replace_callback( 
						"/\[code\]([\S\s].+?[\S\s])\[/code\]/six", 
						"BBcode_rep_code", 
						$Txt);
						
	}

	if ($Admcfg['bbcode_use_quotetag'] && stristr($Txt, "[quote")) {
		
		/*$Txt =~ s{\[quote\](.+?)\[\/quote\]}   {
			$html = do_wrapper({STYLE=>'QUOTE']);
			qq[<!--QuoteBegin-->$html->{START}<\!--QuoteEBegin-->$1<\!--QuoteEnd-->$html->{END}<\!--QuoteEEnd-->];
		}eisgx;*/
		
		$Txt = preg_replace_callback( 
						"/\[quote\](.+?)\[\/quote\]/six",
						"BBcode_rep_quote1", 
						$Txt);
		
		$Txt = preg_replace_callback( 
						'/\[quote=("|)(.+?)("|)\](.+?)\[\/quote\]/six', 
						"BBcode_rep_quote2",
						$Txt);
		
		#BBcode_rep_quote3 = ? $Txt =~ s{\[quote=(.+?),\s*(.+?)\](.+?)\[\/quote\]}   {

	}

	if ($Admcfg['bbcode_use_htmltag'] && stristr($Txt, "[html]")) {	
		#HTML syntax highlighting
		$Txt = preg_replace_callback( 
						"/\[html\](.+?)\[\/html\]/six", 
						"BBcode_rep_html", 
						$Txt);
						
	}
	
	if ($Admcfg['bbcode_use_imgtag'] && stristr($Txt, "[img]")) {	
		# images	
		
		$BBimg_nick_id = $query['nick'];
		$BBimg_nick_id = preg_replace("/\W/", "_", $BBimg_nick_id); #s/\W/_/g;
		$BBimg_nick_id .= $timestamp;
			
		$Txt = preg_replace_callback( 
						"/\[img\](.+?)\[\/img\]/is", 
						"BBcode_rep_image", 
						$Txt);

	}
	
	#Remove Session ID's from posted links:
	#$Txt = preg_replace("/(?:(\?)|&amp;|[&;])s=[\w\d]{32,40}(?:&amp;|&|;|$)/", "$1", $Txt);

	
	$Txt = preg_replace("/\(c\)/i", "&copy;", $Txt);
	$Txt = preg_replace("/\(r\)/i" ,"&reg;", $Txt);
	$Txt = preg_replace("/\(tm\)/i", "&#153;", $Txt);

	$Txt = preg_replace("/(\[i\])(.+?)(\[\/i\])/is", "<i>$2</i>", $Txt);
	$Txt = preg_replace("/(\[s\])(.+?)(\[\/s\])/is", "<s>$2</s>", $Txt);
	$Txt = preg_replace("/(\[u\])(.+?)(\[\/u\])/is", "<u>$2</u>", $Txt);
	$Txt = preg_replace("/(\[b\])(.+?)(\[\/b\])/is", "<b>$2</b>", $Txt);
	
	#debug($Txt);
	
	$Txt = preg_replace("/\[size=\s*(.*?)\s*\]\s*(.*?)\s*\[\/size\]/is", "<span style=font-size:$1px;line-height:100%>$2</span>", $Txt);
	$Txt = preg_replace("/\[font=\s*(.*?)\s*\]\s*(.*?)\s*\[\/font\]/is", "<span style=font-family:$1>$2</span>", $Txt);
	$Txt = preg_replace("/\[color=\s*(.*?)\s*\]\s*(.*?)\s*\[\/color\]/is", "<span style=color:$1>$2</span>", $Txt);
	
	if (stristr($Txt, "[list]")) {
		$Txt = preg_replace("/\[list\]/is", "<ul>", $Txt);
		$Txt = preg_replace("/\[\*\]/is", "<li>", $Txt);
		$Txt = preg_replace("/\[\/list\]/is", "</ul>", $Txt);
		$Txt .= "</ul>";
	}
	if (stristr($Txt, "[list#]")) {
		$Txt = preg_replace("/\[list#\]/is", "<ol>", $Txt);
		$Txt = preg_replace("/\[\*\]/is", "<li>", $Txt);
		$Txt = preg_replace("/\[\/list#\]/is", "</ol>", $Txt);
		$Txt .= "</ol>";
	}
	
	return $Txt;

}


function BBcode_parse_url($Txt) {

	global $target_win;
	
	if (!$Txt) return false;
	
	#$Txt = preg_replace("/(^|\s)(http:\/\/\S+)/i", "$1<a href=\"$2\" $target_win>$2</a>", $Txt);
	#$Txt = preg_replace("/(^|\s)(https:\/\/\S+)/i", "$1<a href=\"$2\" $target_win>$2</a>", $Txt);
	#$Txt = preg_replace("/(^|\s)(ftp:\/\/\S+)/i", "$1<a href=\"$2\" $target_win>$2</a>", $Txt);
	
	$Txt = preg_replace("/(^|\s)([\.\w\-]+\@[\.\w\-]+\.[\.\w\-]+)/is", "$1<a href=\"mailto:$2\">$2</a>", $Txt);

	$Txt = preg_replace("/\[email\](\S+?)\[\/email\]/is", "<a href=\"mailto:$1\">$1</a>", $Txt);
	
	$Txt = preg_replace("/\[url\](\S*)\[\/url\]/i", "<a href=\"$1\" $target_win>$1</a>", $Txt);
	$Txt = preg_replace("/\[url\s*=\s*\"\s*(\S+?)\s*\"\s*\](.*?)\[\/url\]/is", "<a href=\"$1\" $target_win>$2</a>", $Txt);
	$Txt = preg_replace("/\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]/is", "<a href=\"$1\" $target_win>$2</a>", $Txt);
	
	$Txt = preg_replace("/\[email\s*=\s*\"([\.\w\-]+\@[\.\w\-]+\.[\.\w\-]+)\s*\"\s*\](.*?)\[\/email\]/is", "<a href=\"mailto:$1\">$2</a>", $Txt);
	$Txt = preg_replace("/\[email\s*=\s*([\.\w\-]+\@[\.\w\-]+\.[\w\-]+)\s*\](.*?)\[\/email\]/is", "<a href=\"mailto:$1\">$2</a>", $Txt);

	return $Txt;

}


function chatbot_on($in_) {
	
	global $query, $Admcfg, $Public_msg, $safile_matrix, $Chatbot_on;
	
	if ($in_ == 'msg') {
		if ($Public_msg) {
			if ($Admcfg["chatbot_name"] && ($Admcfg['chatbot_room'] == $query['room'] || $Admcfg['chatbot_room'] == "" || preg_match("/^all$/i", $Admcfg['chatbot_room']))) {
				$Roomlist = $Admcfg['chatrooms'];
				$Roomlist["*lounge*"] = "Inner Chamber";
				$Roomlist[$Admcfg['chatbot_room']] = $Admcfg['chatbot_room'];
				foreach ($Roomlist as $room => $roomval) {
					if($room == $query['room']) {
						if ($Admcfg['chatbot_response'] || stristr(substr($query['Postfield'], 0, strlen($Admcfg["chatbot_name"])), $Admcfg["chatbot_name"]) || (stristr($query['to'], $Admcfg["chatbot_name"]) && $query['msg_action'] != "")) {
							if ($query['msg_action'] != "PRIVATE MESSAGE") {
								return true;
							}
						}
					}
				}
			}		
		}
	}
	else if ($in_ == 'init') {
		
		if (!$safile_matrix[0]) create_safile_matrix();
		#if (!$safile_matrix[0]) saprocess($query['nick'], '', 'update');
		
		$rawuser = array();
		for ($sai=0; $sai < count($safile_matrix); $sai++) {
			if($safile_matrix[$sai][4] == "VIS") {
				array_push ($rawuser, $safile_matrix[$sai][2]);
			}
		}
		$nrusers = count($rawuser);
		#$nrusers--; # cus of adding robobabe to stillalive
		
		if ($Admcfg['chatbot_limit'] != -1 && $nrusers < $Admcfg['chatbot_limit']) {
			$cb_row = array($Admcfg['chatbot_name'], $timestamp, '9999', '0', 'VIS');
			array_push($safile_matrix, $cb_row);
			return true;
		}
		
	}
	
	return false;

}


# CHATBOT FUNCTIONS
function chatbot_welcome($sex, $in_realname) {

	global $html_base_url, $query, $DBnicklist, $robo_img, $Admcfg;

	$random = rand(0,4);
	$robo_img = $html_base_url . "images/robobabe/robo". $random .".jpg";

	$Top_msg = array();

	# get future array


	if (stristr($sex, "fem")) {
		$Top_msg = $Miss_msg;
	} else {
		$Top_msg = $Mast_msg;
	}

	if ($query['defaultnick']) {
		$msg_nick = $query['defaultnick'];
	} else {
		$nicks = ($DBnicklist) ? $DBnicklist : $query['extranicks'];
		$msg_nicks = explode("|", $nicks);
		$msg_nick = $msg_nicks[0];
	}

	$msg_nick = preg_replace("/^[\W|_]*/", "", $msg_nick);

	$wel_msg = (preg_match("/^[A-Z]/", $msg_nick)) ? $Top_msg[$random] : $sub_msg[$random] ;

	if (!$Top_msg[0]) $_msg = preg_replace("/You/", "you", $Admcfg['chatbot_help']);
	else $_msg = preg_replace("/you/", "You", $Admcfg['chatbot_help']);

	return $wel_msg . "<br><br>" . $_msg . "<br>&nbsp;";

}


function chatbot_make_response($time, $thisroom) {
	
	global $query, $Admcfg, $chatbot_matrix, $log_out_time, $Gender, $chatbot_path, 
				 $Bound_in_use, $localtime, $testing_new_version, $spac, $OFFLINE, $Chatbot_DB;
				 #, $bot_obj;
	
	$bot_response = "";
	
	if ($time > 1) $r_time = $time - $Admcfg['chatbot_interval'];
		
	# get chatbot memory data
	exec_DB("ties1_chatdata", "SELECT `sender`, `dstype`, `memory` FROM `chatbot` WHERE `room`='$thisroom' AND `msgtime`<$r_time", "=chatbotmsg=", "Chatbot-get robobabe received data");
	
	if ($chatbot_matrix[0] && $r_time > 1) {
		
		# delete all post if older than expired stillalive
		#if ($log_out_time) exec_DB("ties1_chatdata", "DELETE FROM `chatbot` WHERE `msgtime`<$time-$log_out_time", "=update=", "Chatbot-delete all really old robobabe received data");
		
		# delete chatbot memory data
		exec_DB("ties1_chatdata", "DELETE FROM `chatbot` WHERE `room`='$thisroom' AND `msgtime`<$r_time", "=update=", "Chatbot-delete robobabe received data");
		
		# chatbot globals
		global $that,$topic,$uid,$loopcounter,$patternmatched,$inputmatched,$selectbot, 
					 $contractsearch,$contractreplace,$abbrevsearch,$abbrevreplace,$removepunct,$likeperiodsearch, 
					 $likeperiodreplace,$aftersearch,$afterreplace,$replacecounter, $gendersearch,$genderreplace, 			 
					 $firstthirdsearch,$firstthirdreplace, $firstsecondsearch,$firstsecondreplace, 
					 $ss_timing_start_times, $ss_timing_stop_times, $cttags, $numselects;	
		
		# include chatbot responder
		include_once($chatbot_path . "chatbot_respond.php");
		
					 
		#mysql_select_db($Chatbot_DB) || "Unable to select database\n";
		select_DB($Chatbot_DB);
		
		# MAKE RESPONSE
		for($i=0; $i < count($chatbot_matrix); $i++) {
			$msg_arr = explode("|||", $chatbot_matrix[$i][2]);
			if ($msg_arr[0]) {
				# $chatbot_matrix[$i][1] . ", ";
				$bot_response .= $chatbot_matrix[$i][0] . " -&gt; ";
				foreach ($msg_arr as $msg) {
					if ($msg) {
						$msg = stripslashes($msg);
						$bot_obj = replybotname($msg, $query['nick'], $Admcfg["chatbot_name"]);
						$bot_unobjected = "";
						$bot_unobjected = $bot_obj->response;
						if ($bot_unobjected) {
							$bot_response .= chatbot_wash($chatbot_matrix[$i][1], $bot_unobjected) . " \n";
						} else {
							$bot_response .= "Error in chatbot subroutines. Unable to compute response... \n";
						}
						#debug($bot_response);
					}
				}
				# prepare for next person			
				$bot_response .= "\n\n";
			}
		}
	}
	
	# insert post into DB
	if ($bot_response) {
				
		# generate unique message id
		$message_id = generate_msgid('9999');
		
		$robobabereply = wash_msg($bot_response, '=html=');
		#if(preg_match("/\[\w+?/", $bot_response)) {
			#$robobabereply = BBcode_parse($bot_response);
		#} 
		#else $robobabereply = $bot_response;
		
		# now print to `messages`
		if ($Admcfg['chatbot_jokes']) {
			$joke = chatbot_joke();
			if ($joke) $robobabereply .= "<br><br>" . $joke;
		}
		
		$robobabereply = addslashes($robobabereply);
		
		# get att?
		$att = "[inquisitive brat]";
				
		$def_col = ($Bound_in_use) ? $Admcfg['dungeon_col'] : $Admcfg['standardcolor'];
		
		$time = $time - (rand(1,5));
		
		$timetag = "<font color=\"$def_col\" size=1><br><br>From ..i talk: Basic to Boss, logic to my friends and bits to myself. &not; ". get_date_time($time) . " &not; </font>$testing_new_version";

		#my($extolist, $msg_act, $myIcon, $myFlavers, $myMsg_type, $font_CB_tag, $font_Cs_tag);
		
		#get_date_time($time + $rnd_);
		
		$random = rand(0,4);
		if ($OFFLINE) {
			$myIcon = "images/robobabe/robo".$random.".jpg";
		} else {
			$myIcon = "chat/images/robobabe/robo".$random.".jpg";
		}

		$font_CB_tag = "<FONT COLOR=\"#FFFFFF\" size=5> ";
		$font_Cs_tag = "<FONT COLOR=\"#FFFFFF\" size=3> ";
		$myMsg_to = 'PUB';
		
		$strMsg = "$font_CB_tag<B>" . $Admcfg['chatbot_name'] . "</B></FONT>$font_Cs_tag $att$msg_act$spac$robobabereply</FONT>$timetag";
		
		
		
		$strSql = "INSERT INTO `messages` (`room`, `msgid`, `msgto`, `msgfrom`, `sender`, `msgtime`, `msg`, `icon`, `flavers`) VALUES ('" . $query['room'] . "', '$message_id', '$myMsg_to', 9999, '" . $Admcfg['chatbot_name'] . "', $time, '$strMsg', '$myIcon', '$myFlavers')";
		exec_DB("ties1_chatdata", $strSql, "=update=", "Chatbot-insert new message into `messages`");

	}	# end if $bot_response

}


function chatbot_make_msg($sender, $msg) {
	
	global $query, $Admcfg, $Gender, $safile_matrix, $chatbot_matrix, $timestamp, $chatbot_path;
	
	$new_msg = trim($msg);
	
	# Washout HTML
	$new_msg = preg_replace("/<[\/\!]*?[^<>]*?>/si", "", $new_msg);
	# Washout BBcode
	$new_msg = preg_replace("/\[[\/\!]*?[^\[\]]*?\]/si", "", $new_msg);
	
	# fix I'm to become I am
	$new_msg = preg_replace("/((^|\b)Im\b|(^|\b)I\'m\b)/i", "I am", $new_msg);
	
	# WASHOUT ILLEGAL CHARS
	
	#$new_msg = slashescape2($new_msg);
	#$new_msg = str_replace("'", "\'", $new_msg);
	#$new_msg = str_replace("\"", "", $new_msg);
	#$new_msg = preg_replace("/(\s)_(\s)/", "", $new_msg);
	#$new_msg = str_replace("*", "", $new_msg);
	$new_msg = str_replace("|", "", $new_msg);
	$new_msg = str_replace("`", "", $new_msg);
	$new_msg = preg_replace("/(\bD\&S\b|\bD\/S\b)/i", "DS", $new_msg);
	$new_msg = preg_replace("/(\bS\&M\b|bS\/M\b)/i", "SM", $new_msg);
	$new_msg = preg_replace("/(\bB\&D\b|\bB\/D\b)/i", "BD", $new_msg);
	
	# remove 'roboslave' from input
	$new_msg = preg_replace("/^" . $Admcfg['chatbot_name'] . "\s/i" ,"", $new_msg);
	
	# chatbot bugfixes so that stack overflow doesn't occur if looping higher than 60
	#include_once($chatbot_path . "chatbot_bugfix.php");
	
	if ($new_msg && $timestamp > 1) {
	
		# find out =gtb= (gender/top or bottom) to make right response for memory
		$gnick = $query['nick'];
		$gnick = preg_replace("/^[\W|_]/", "", $gnick);
		if (!preg_match("/^\d/", $gnick)) {
			if ($Gender == "male") {
				$gtb = (preg_match("/^[A-Z]/", $gnick)) ? "Sir" : "bro";
			} else {
				$gtb = (preg_match("/^[A-Z]/", $gnick)) ? "Maam" : "sis";
			}
		}
		
		# now insert new msg for chatbot
			
		# get chatbot memory data
		$chatbot_matrix = array();
		exec_DB("ties1_chatdata", "SELECT `sender` FROM `chatbot` WHERE `room`='$thisroom' AND `sender`='{$query['nick']}'", "=chatbotmsg=", "chatbot_make_msg-getdata");
			
		$new_data = addslashes($new_msg) . "|||";
			
		# update or insert new data
		if ($chatbot_matrix[0]) {
			exec_DB("ties1_chatdata", "UPDATE `chatbot` SET `memory`=`memory` + '$new_data' WHERE `room`='$thisroom' AND `sender`='{$query['nick']}'", "=chatbotmsg=", "chatbot_make_msg-update");
		} else {
			exec_DB("ties1_chatdata", "INSERT INTO `chatbot` (`room`, `msgtime`, `sender`, `memory`, `dstype`) VALUES ('{$query['room']}', $timestamp, '{$query['nick']}', '$new_data', '$gtb')", "=chatbotmsg=", "chatbot_make_msg-Insert");
		}			
		
	}	# end if $new_msg
	
}


function chatbot_wash($top, $msg) {

	# washout Capitalcase
	$msg = preg_replace("/(\b)Am I(\b)/i", "am i", $msg);
	$msg = preg_replace("/(^|\b)I(\b|\'|$)/", "i", $msg);
	$msg = preg_replace("/(^|\?[ |]*?|\![ |]*?|\.[ |]*?|\b)My\b/", "$1 my", $msg);
	$msg = preg_replace("/(^|\?[ |]*?|\![ |]*?|\.[ |]*?|\b)Me\b/", "$1 me", $msg);
	$msg = preg_replace("/(^|\?[ |]*?|\![ |]*?|\.[ |]*?|\b)Mine\b/", "$1 mine", $msg);
	
	
	if ($top == 'Sir' || $top == 'Maam') {
		$msg = preg_replace("/(^|\b)you/", "You", $msg);
		$msg = preg_replace("/(^|\b)we(\b|$)/i", "Wwe", $msg);
		$msg = preg_replace("/(^|\b)our(\b|$)/i", "Oour", $msg);
		$msg = preg_replace("/\bours(\b|$)/i", "Oours", $msg);
		$msg = preg_replace("/\bus(\b|$)/i", "Uus", $msg);
	}
	
	return $msg;

}


function chatbot_joke() {
	
	global $Admcfg, $chatbot_path;

	if (rand(0,99) < $Admcfg['chatbot_jokes']) {

		$robo_jokes = file($chatbot_path . "B_Q.txt");
	
		$amount = count($robo_jokes);
		$random = rand(0, $amount);

		if ($robo_jokes[$random]) return BBcode_parse('[quote=" a lil  joke "]'. $robo_jokes[$random] .'[/quote]');
		
	}
	
	return false;

}


### END CHATBOT




function chat_img_preloads() {

	global $query, $OFFLINE, $NETSCAPE_4, $WEBTV, $mem_status;

	if ($OFFLINE) {
		$url = $base_url . "/head/New";
	} else {
		$url = _chat_url_ ;
	}

?>

<SCRIPT TYPE="text/javascript">
<!--
// browsertest
if(document.getElementById) DNB_browser = "nn6";
if(document.all) DNB_browser = "ie";
//if(document.layers) DNB_browser = "nn";

<? if (!$WEBTV && !$NETSCAPE_4 && !$query['turnoffhide'] && !$query['auto']) { ?>

function preloadElem() {
	if (DNB_browser=='nn6') {
<? if($mem_status > 3) { ?> document.getElementById('bbcodefield').style.display = 'none'; <? } ?>
		document.getElementById('appnav').style.display = 'none';
	} else if (DNB_browser=='ie') {
<? if($mem_status > 3) { ?>		document.all['bbcodefield'].style.display = 'none'; <? } ?>
		document.all['appnav'].style.display = 'none';
	}	
	else if (DNB_browser=='nn') {
<? if($mem_status > 3) { ?>		document.layers['bbcodefield'].style.display = 'none'; <? } ?>
		document.layers['appnav'].style.display = 'none';
	}
}

window.onload=preloadElem;

<? } ?>

function bbcodeDisplay(prop) {
	bbcodeprop = (prop=='block') ? 'none' : 'block';
	bbcodebuttonvalue = (bbcodeprop=='block') ? 'Show' : 'Hide';

	if (DNB_browser=='nn6') {
		document.getElementById('bbcodefield').style.display = prop;
		document.getElementById('bbcodeopenclose').innerHTML = bbcodebuttonvalue+' BBcode Panel? <input class="button" name="bbcodebutton" type="button" value="'+bbcodebuttonvalue+'" onClick="bbcodeDisplay(bbcodeprop)">';
	}
	else if (DNB_browser=='ie') {
		document.all['bbcodefield'].style.display = prop;
		document.all['bbcodeopenclose'].innerHTML = bbcodebuttonvalue+' BBcode Panel? <input class="button" style="width: 40;" name="bbcodebutton" type="button" value="'+bbcodebuttonvalue+'" onClick="bbcodeDisplay(bbcodeprop)">';
	}
	else if (DNB_browser=='nn') {
		document.layers['bbcodefield'].style.display = prop;
		document.layers['bbcodeopenclose'].document.open();
		document.layers['bbcodeopenclose'].document.write(bbcodebuttonvalue+' BBcode Panel? <input class="button" style="width: 50;" name="bbcodebutton" type="button" value="'+bbcodebuttonvalue+'" onClick="bbcodeDisplay(bbcodeprop)">');
		document.layers['bbcodeopenclose'].document.close();
	}
}

function appnavDisplay(prop) {
	appprop = (prop=='block') ? 'none' : 'block';
	appbuttonvalue = (appprop=='block') ? 'Show' : 'Hide';

	if (DNB_browser=='nn6') {
		document.getElementById('appnav').style.display = prop;
		document.getElementById('closeapp').innerHTML = 'Click to '+appbuttonvalue+' Appearance and Navigation tables? <input class="button" name="bbcodebutton" type="button" value="'+appbuttonvalue+'" onClick="appnavDisplay(appprop)">';
	}
	else if (DNB_browser=='ie') {
		document.all['appnav'].style.display = prop;
		document.all['closeapp'].innerHTML = 'Click to '+appbuttonvalue+' Appearance and Navigation tables? <input class="button" style="width: 40;" name="appnavbutton" type="button" value="'+appbuttonvalue+'" onClick="appnavDisplay(appprop)">';
	}
	else if (DNB_browser=='nn') {
		document.layers['appnav'].style.display = prop;
		document.layers['closeapp'].document.open();
		document.layers['closeapp'].document.write('Click to '+appbuttonvalue+' Appearance and Navigation tables? <input class="button" style="width: 50;" name="bbcodebutton" type="button" value="'+appbuttonvalue+'" onClick="appnavDisplay(appprop)">');
		document.layers['closeapp'].document.close();
	}
}

// -->
</SCRIPT>

<?

}



?>