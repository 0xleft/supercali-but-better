<?php
/*
Supercali Event Calendar

Copyright 2006 Dana C. Hutchins

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

For further information visit:
http://supercali.inforest.com/
*/

include "includes/start.php";
function check_login() {
	global $table_prefix, $link, $common_get,$link;
	if(get_magic_quotes_gpc()) {
    	$email = mysqli_real_escape_string($link,stripslashes($_POST["email"]));
		$password = mysqli_real_escape_string($link,stripslashes($_POST["password"]));

     } else {
       	$email = mysqli_real_escape_string($link,$_POST["email"]);
		$password = mysqli_real_escape_string($link,$_POST["password"]);
     }


	$md5_pass = md5($password);

	$query_raw = "Select user_id, email, temp_password from ".$table_prefix."users where email='".$email."' and password='$md5_pass' OR email='".$email."' and temp_password='$md5_pass'";
	$query = mysqli_query($link, $query_raw);

	$total_row = mysqli_num_rows($query);
	if($total_row>0){
		$row = mysqli_fetch_array($query);
		$_SESSION['user_id'] = $row['user_id'];
		$_SESSION['email'] = $row['email'];
		if ($row['temp_password'] == $md5_pass) {
			mysqli_query($link,"UPDATE ".$table_prefix."users set password = '".$row['temp_password']."', temp_password = NULL WHERE user_id ='".$row['user_id']."'");
			mysqli_close($link);
			header("Location: user_profile.php?return_to=".$_POST["return_to"]."&".$common_get);
		} else {
			mysqli_close($link);
			if ($_POST["return_to"]) {
				header("Location: ".$_POST["return_to"]);
			} else {
				header("Location: index.php?".$common_get);
			}
		}
	} else {
		mysqli_close($link);
		header("Location: ".$path."bad_password.php?email=".$email."&return_to=".$_POST["return_to"]."&".$common_get);
	}
}

function send_new_password() {
	global $table_prefix, $calendar_title, $calendar_email, $common_get,$link,$lang,$link;
	if(get_magic_quotes_gpc()) {
    	$email = mysqli_real_escape_string($link,stripslashes($_POST["email"]));

     } else {
       	$email = mysqli_real_escape_string($link,$_POST["email"]);
     }
	$query = mysqli_query($link,"Select * from ".$table_prefix."users where email='$email'");
	$total = mysqli_num_rows($query);
	if($total>0){
		$row = mysqli_fetch_array($query);
		$newpass=substr(md5($email.microtime()), 0, 8);
        $crypt_pass=md5($newpass);
		mysqli_query($link,"UPDATE ".$table_prefix."users set temp_password = '".$crypt_pass."' WHERE email ='".$email."'");
		$message = $lang["password_msg"].$calendar_title.":\n\n$newpass\n\n";
		mail($email, $lang["password_subject_start"].$calendar_title.$lang["password_subject_end"], "$message", "From: \"".$calendar_title."\" <".$calendar_email.">");

		$msg=$lang["password_sent"];


	} else {
		$msg=$lang["password_no_email"];
	}
	mysqli_close($link);
	header("Location: login.php?return_to=".$_POST["return_to"]."&msg=".$msg."&".$common_get);
}

function log_out () {
	global $common_get;
	session_start();
	session_unset();
	session_regenerate_id();
	header("Location: login.php?".$common_get);
}

function approve($code) {
	global $table_prefix, $lang, $link;
	if(get_magic_quotes_gpc()) {
    	$code = mysqli_real_escape_string($link,stripslashes($code));
     } else {
       	$code = mysqli_real_escape_string($link,$code);

     }
	$q = "select event_id from ".$table_prefix."events where quick_approve = '".$code."'";
	$query = mysqli_query($link,$q);
	if (mysqli_num_rows($query) > 0) {
		$sq = "update ".$table_prefix."events set status_id = 4, quick_approve = NULL where quick_approve = '".$code."'";
		$squery = mysqli_query($link,$sq);
		if ($squery) {
			$msg = $lang["event_updated"];
			$event_id = mysql_result($query,0,0);
			include "includes/notify.php";
			notify_group($event_id);
		}
	} else {
		$msg = $lang["event_not_found"];
	}
	header("Location: index.php?msg=".$msg);

}


switch ($_REQUEST["mode"]) {
case $lang["send_new_password"];

	send_new_password();
	break;

case "q";
	approve($_REQUEST["qa"]);
	break;
case "logout";
	log_out();
	break;

case "Log In";
	check_login();
	break;

default;
	header("Location: index.php");
	break;
}

mysql_close($link);
?>
