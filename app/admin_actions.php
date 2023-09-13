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
if(get_magic_quotes_gpc()) {
  	$id = mysqli_real_escape_string($link,stripslashes($_REQUEST["id"]));
	$dir = mysqli_real_escape_string($link,stripslashes($_REQUEST["dir"]));
	$file = mysqli_real_escape_string($link,stripslashes($_REQUEST["file"]));

} else {
  	$id = mysqli_real_escape_string($link,$_REQUEST["id"]);
	$dir = mysqli_real_escape_string($link,stripslashes($_REQUEST["dir"]));
	$file = mysqli_real_escape_string($link,stripslashes($_REQUEST["file"]));
}

$edit = false;
function updateProfile($id) {
	global $table_prefix, $edit, $link, $common_get, $lang;
	if(get_magic_quotes_gpc()) {
    	$email = mysqli_real_escape_string($link,stripslashes($_POST["email"]));
		$new_password = mysqli_real_escape_string($link,stripslashes($_POST["new_password"]));

     } else {
       	$email = mysqli_real_escape_string($link,$_POST["email"]);
		$new_password = mysqli_real_escape_string($link,$_POST["new_password"]);
     }

	if ($_POST["new_password"]) {
		$password = md5($_POST["new_password"]);
		$query = mysqli_query($link,"UPDATE ".$table_prefix."users set password = '".$password."'WHERE user_id ='".$id."'");
		if (!$query) $msg = $lang["database_error_password_not_updated"];

	}
	if (!$edit) {
		$query = mysqli_query($link,"UPDATE ".$table_prefix."users set email = '".$_POST["email"]."' WHERE user_id =".$id);
		if (!$query) {
			$msg .= $lang["database_error_password_not_updated"];
		} else {
			$msg .= $lang["information_updated"];
		}
	} else {

		$view = $_POST["view"] ? 1 : 0;
		$post = $_POST["post"] ? 1 : 0;
		$add_users = $_POST["add_users"] ? 1 : 0;
		$add_groups = $_POST["add_users"] ? 1 : 0;
		$add_categories = $_POST["add_categories"] ? 1 : 0;
		$query = mysqli_query($link,"UPDATE ".$table_prefix."users set email = '".$_POST["email"]."', view = ".$view.", post = ".$post.", add_users = ".$add_users.", add_groups = ".$add_groups.", add_categories = ".$add_categories." WHERE user_id =".$id);
		if (!$query) $msg .= $lang["database_error_user_not_updated"];

		// clean out current user to categories table of user
		$query = mysqli_query($link,"DELETE from ".$table_prefix."users_to_categories where user_id = ".$id);

		//now add back the ones the user has access to
		if ($_POST["category"]) {
			while (list($key) = each($_POST["category"])) {
				$mod = $_POST["cpost"][$key] == 2 ? 2 : 1;
				if ($_POST["cmoderate"][$key] == 3) $mod = 3;

				$query = mysqli_query($link,"INSERT INTO ".$table_prefix."users_to_categories (user_id, category_id, moderate) values (".$id.", ".$key.", ".$mod.")");
			}
		}

		// clean out current user to groups table of user
		$query = mysqli_query($link,"DELETE from ".$table_prefix."users_to_groups where user_id = ".$id);

		//now add back the ones the user has access to
		if ($_POST["group"]) {
			while (list($key) = each($_POST["group"])) {
				$mod = $_POST["gpost"][$key] == 2 ? 2 : 1;
				if ($_POST["gmoderate"][$key] == 3) $mod = 3;
				//$subscribe = $_POST["gsubscribe"][$key] == 1 ? 1 : 0;
				$subscribe = 0;
				$query = mysqli_query($link,"INSERT INTO ".$table_prefix."users_to_groups (user_id, group_id, moderate, subscribe) values (".$id.", ".$key.", ".$mod.", ".$subscribe.")");

			}
		}
		$msg .= $lang["user_updated"];


	}

	mysqli_close($link);
	header("Location: user_profile.php?msg=".$msg."&id=".$id."&".$common_get);
}

function addProfile() {
	global $table_prefix, $edit, $link, $common_get, $lang,$link;
	if(get_magic_quotes_gpc()) {
    	$email = mysqli_real_escape_string($link,stripslashes($_POST["email"]));
		$new_password = mysqli_real_escape_string($link,stripslashes($_POST["new_password"]));

     } else {
       	$email = mysqli_real_escape_string($link,$_POST["email"]);
		$new_password = mysqli_real_escape_string($link,$_POST["new_password"]);
     }

	if (!$edit) {
		$msg = $lang["not_authorized_add_users"];
	} else {
		if ((!$new_password) || (!$email)) {
				$msg = $lang["username_password_required"];
		} else {
			$query = mysqli_query($link,"SELECT email from ".$table_prefix."users where email = '".$email."'");
			if (mysqli_num_rows($query) > 0) {
				$msg = $lang["email_exists"];
			} else {
				$password = md5($new_password);
				$query = mysqli_query($link,"INSERT INTO ".$table_prefix."users (email, password) values ('".$email."', '".$password."')");
				if (!$query) $msg .= $lang["database_error_user_not_updated"];
				$id = mysqli_insert_id();
				$view = $_POST["view"] ? 1 : 0;
				$post = $_POST["post"] ? 1 : 0;
				$add_users = $_POST["add_users"] ? 1 : 0;
				$add_categories = $_POST["add_categories"] ? 1 : 0;
				$add_groups = $_POST["add_groups"] ? 1 : 0;
				$query = mysqli_query($link,"UPDATE ".$table_prefix."users set add_users = ".$add_users.", add_categories = ".$add_categories.", view = ".$view.", post = ".$post.", add_groups = ".$add_groups." WHERE user_id =".$id);
				if (!$query) $msg .= $lang["database_error_user_not_updated"];

				//now add back the ones the user has access to
				if ($_POST["category"]) {
					while (list($key) = each($_POST["category"])) {
						$mod = 0;
						if ($_POST["cmoderate"][$key]) $mod = 3;
						elseif ($_POST["cpost"][$key]) $mod = 2;
						else $mod = 1;
						$query = mysqli_query($link,"INSERT INTO ".$table_prefix."users_to_categories (user_id, category_id, moderate) values (".$id.", ".$key.", ".$mod.")");
					}
				}
				if ($_POST["group"]) {
					while (list($key) = each($_POST["group"])) {
						$sub = 0;
						$mod = 0;
						if ($_POST["gmoderate"][$key]) $mod = 3;
						elseif ($_POST["gpost"][$key]) $mod = 2;
						else $mod = 1;
						$sub = $_POST["gsubscribe"][$key] == 1 ? 1 : 0;
						$query = mysqli_query($link,"INSERT INTO ".$table_prefix."users_to_groups (user_id, group_id, moderate, subscribe) values (".$id.", ".$key.", ".$mod.", ".$sub.")");
					}
				}
			}
		}
	}
	if (!$msg) $msg = $lang["user_updated"];
	mysqli_close($link);
	header("Location: edit_users.php?msg=".$msg."&".$common_get);
}

function addCategory() {
	global $table_prefix, $link, $edit_categories, $common_get,$link;
	if (!$edit_categories) {
		$msg = $lang["not_authorized_edit_categories"];
	} else {
		if(get_magic_quotes_gpc()) {
            $name = mysqli_real_escape_string($link,stripslashes($_POST["name"]));
			$sub_of = mysqli_real_escape_string($link,stripslashes($_POST["sub_of"]));
			$sequence = mysqli_real_escape_string($link,stripslashes($_POST["sequence"]));
			$description = mysqli_real_escape_string($link,stripslashes($_POST["description"]));
			$color = mysqli_real_escape_string($link,stripslashes($_POST["color"]));
			$background = mysqli_real_escape_string($link,stripslashes($_POST["background"]));
        } else {
          	$name = mysqli_real_escape_string($link,$_POST["name"]);
			$sub_of = mysqli_real_escape_string($link,$_POST["sub_of"]);
			$sequence = mysqli_real_escape_string($link,$_POST["sequence"]);
			$description = mysqli_real_escape_string($link,$_POST["description"]);
			$color = mysqli_real_escape_string($link,$_POST["color"]);
			$background = mysqli_real_escape_string($link,$_POST["background"]);
        }
		$q = "INSERT INTO ".$table_prefix."categories (name, sub_of, sequence, description, color, background) values ('".$name."', '".$sub_of."', '".$sequence."', '".$description."', '".$color."', '".$background."')";
		$query = mysqli_query($link,$q);
		if (!$query) $msg = $lang["database_error_category_not_updated"];
		else $msg = $lang["cateogory_added"];
	}
	mysqli_close($link);
	header("Location: edit_categories.php?msg=".$msg."&".$common_get);
}
function editCategory($id) {
	global $table_prefix, $link, $edit_categories, $common_get, $lang,$link;
	if (!$edit_categories) {
		$msg = $lang["not_authorized_edit_categories"];
	} else {
		if(get_magic_quotes_gpc()) {
            $name = mysqli_real_escape_string($link,stripslashes($_POST["name"]));
			$sub_of = mysqli_real_escape_string($link,stripslashes($_POST["sub_of"]));
			$sequence = mysqli_real_escape_string($link,stripslashes($_POST["sequence"]));
			$description = mysqli_real_escape_string($link,stripslashes($_POST["description"]));
			$color = mysqli_real_escape_string($link,stripslashes($_POST["color"]));
			$background = mysqli_real_escape_string($link,stripslashes($_POST["background"]));
        } else {
          	$name = mysqli_real_escape_string($link,$_POST["name"]);
			$sub_of = mysqli_real_escape_string($link,$_POST["sub_of"]);
			$sequence = mysqli_real_escape_string($link,$_POST["sequence"]);
			$description = mysqli_real_escape_string($link,$_POST["description"]);
			$color = mysqli_real_escape_string($link,$_POST["color"]);
			$background = mysqli_real_escape_string($link,$_POST["background"]);
        }
		$query = mysqli_query($link,"UPDATE ".$table_prefix."categories set name = '".$name."', sub_of = '".$sub_of."', sequence = '".$sequence."', description = '".$description."', color = '".$color."', background = '".$background."' where category_id =".$id);
		if (!$query) $msg = $lang["database_error_category_not_updated"];
		else $msg = $lang["category_updated"];
	}
	mysqli_close($link);
	header("Location: edit_categories.php?msg=".$msg."&".$common_get);
}

function deleteCategory($id) {
	global $table_prefix, $link, $edit_categories, $common_get, $lang;
	if (!$edit_categories) {
		$msg = $lang["not_authorized_edit_categories"];
	} else {
		if(get_magic_quotes_gpc()) {
            $sub_of = mysqli_real_escape_string($link,stripslashes($_POST["sub_of"]));
        } else {
          	$sub_of = mysqli_real_escape_string($link,$_POST["sub_of"]);
        }
		$query = mysqli_query($link,"UPDATE ".$table_prefix."categories set sub_of = '".$sub_of."' where sub_of =".$id);
		if (!$query) $msg = $lang["database_error_dependant_categories_not_updated"];
		$query = mysqli_query($link,"UPDATE ".$table_prefix."events set category_id = '".$sub_of."' where category_id =".$id);
		if (!$query) $msg .= $lang["database_error_dependant_events_not_updated"] ;
		$query = mysqli_query($link,"DELETE from ".$table_prefix."users_to_categories where category_id = ".$id);
		if (!$query) $msg .= $lang["database_error_user_table_not_updated"];
		$query = mysqli_query($link,"DELETE from ".$table_prefix."categories where category_id = ".$id);
		if (!$query) $msg .= $lang["database_error_category_not_deleted"];
		if (!$msg) $msg = $lang["category_deleted"];
	}
	mysqli_close($link);
	header("Location: edit_categories.php?msg=".$msg."&".$common_get);
}

function addGroup() {
	global $table_prefix, $link, $edit_groups, $common_get;
	if (!$edit_groups) {
		$msg = $lang["not_authorized_edit_groups"];
	} else {
		if(get_magic_quotes_gpc()) {
            $name = mysqli_real_escape_string($link,stripslashes($_POST["name"]));
			$sub_of = mysqli_real_escape_string($link,stripslashes($_POST["sub_of"]));
			$sequence = mysqli_real_escape_string($link,stripslashes($_POST["sequence"]));
        } else {
          	$name = mysqli_real_escape_string($link,$_POST["name"]);
			$sub_of = mysqli_real_escape_string($link,$_POST["sub_of"]);
			$sequence = mysqli_real_escape_string($link,$_POST["sequence"]);
        }
		$q = "INSERT INTO ".$table_prefix."groups (name, sub_of, sequence) values ('".$name."', '".$sub_of."', '".$sequence."')";
		$query = mysqli_query($link,$q);
		if (!$query) $msg = $lang["database_error_group_not_updated"];
		else $msg = $lang["group_added"];
	}
	mysqli_close($link);
	header("Location: edit_groups.php?msg=".$msg."&".$common_get);
}
function editGroup($id) {
	global $table_prefix, $link, $edit_groups, $common_get, $lang;
	if (!$edit_groups) {
		$msg = $lang["not_authorized_edit_groups"];
	} else {
		if(get_magic_quotes_gpc()) {
            $name = mysqli_real_escape_string($link,stripslashes($_POST["name"]));
			$sub_of = mysqli_real_escape_string($link,stripslashes($_POST["sub_of"]));
			$sequence = mysqli_real_escape_string($link,stripslashes($_POST["sequence"]));
        } else {
          	$name = mysqli_real_escape_string($link,$_POST["name"]);
			$sub_of = mysqli_real_escape_string($link,$_POST["sub_of"]);
			$sequence = mysqli_real_escape_string($link,$_POST["sequence"]);
        }
		$query = mysqli_query($link,"UPDATE ".$table_prefix."groups set name = '".$name."', sub_of = '".$sub_of."', sequence = '".$sequence."' where group_id =".$id);
		if (!$query) $msg = $lang["database_error_category_not_updated"];
		else $msg = $lang["group_updated"];
	}
	mysqli_close($link);
	header("Location: edit_groups.php?msg=".$msg."&".$common_get);
}

function deleteGroup($id) {
	global $table_prefix, $link, $edit_groups, $common_get, $lang;
	if (!$edit_groups) {
		$msg = $lang["not_authorized_edit_groups"];
	} else {
		if(get_magic_quotes_gpc()) {
    		$sub_of = mysqli_real_escape_string($link,stripslashes($_POST["sub_of"]));
		} else {
       		$sub_of = mysqli_real_escape_string($link,$_POST["sub_of"]);
		}
		$query = mysqli_query($link,"UPDATE ".$table_prefix."groups set sub_of = '".$sub_of."' where sub_of =".$id);
		if (!$query) $msg = $lang["database_error_dependant_groups_not_updated"];
		$query = mysqli_query($link,"UPDATE ".$table_prefix."events set group_id = '".$sub_of."' where group_id =".$id);
		if (!$query) $msg .= $lang["database_error_dependant_events_not_updated"] ;
		$query = mysqli_query($link,"DELETE from ".$table_prefix."users_to_groups where group_id = ".$id);
		if (!$query) $msg .= $lang["database_error_user_table_not_updated"];
		$query = mysqli_query($link,"DELETE from ".$table_prefix."groups where group_id = ".$id);
		if (!$query) $msg .= $lang["database_error_group_not_deleted"];
		if (!$msg) $msg = $lang["group_deleted"];
	}
	mysqli_close($link);
	header("Location: edit_groups.php?msg=".$msg."&".$common_get);
}

function addLink() {
	global $table_prefix, $link, $common_get, $lang, $edit_categories;
	if (!$edit_categories) {
		$msg = $lang["not_authorized_edit_links"];
	} else {
		if(get_magic_quotes_gpc()) {
            $company = mysqli_real_escape_string($link,stripslashes($_POST["company"]));
			$address1 = mysqli_real_escape_string($link,stripslashes($_POST["address1"]));
			$address2 = mysqli_real_escape_string($link,stripslashes($_POST["address2"]));
			$city = mysqli_real_escape_string($link,stripslashes($_POST["city"]));
			$state = mysqli_real_escape_string($link,stripslashes($_POST["state"]));
			$zip = mysqli_real_escape_string($link,stripslashes($_POST["zip"]));
			$phone = mysqli_real_escape_string($link,stripslashes($_POST["phone"]));
			$fax = mysqli_real_escape_string($link,stripslashes($_POST["fax"]));
			$contact = mysqli_real_escape_string($link,stripslashes($_POST["contact"]));
			$email = mysqli_real_escape_string($link,stripslashes($_POST["email"]));
			$url = mysqli_real_escape_string($link,stripslashes($_POST["url"]));
			$description = mysqli_real_escape_string($link,stripslashes($_POST["description"]));
        } else {
          	$company = mysqli_real_escape_string($link,$_POST["company"]);
			$address1 = mysqli_real_escape_string($link,$_POST["address1"]);
			$address2 = mysqli_real_escape_string($link,$_POST["address2"]);
			$city = mysqli_real_escape_string($link,$_POST["city"]);
			$state = mysqli_real_escape_string($link,$_POST["state"]);
			$zip = mysqli_real_escape_string($link,$_POST["zip"]);
			$phone = mysqli_real_escape_string($link,$_POST["phone"]);
			$fax = mysqli_real_escape_string($link,$_POST["fax"]);
			$contact = mysqli_real_escape_string($link,$_POST["contact"]);
			$email = mysqli_real_escape_string($link,$_POST["email"]);
			$url = mysqli_real_escape_string($link,$_POST["url"]);
			$description = mysqli_real_escape_string($link,$_POST["description"]);
        }


		$query = mysqli_query($link,"INSERT INTO ".$table_prefix."links (company, address1, address2, city, state, zip, phone, fax, contact, email, url, description) values ('".$company."', '".$address1."', '".$address2."', '".$city."', '".$state."', '".$zip."', '".$phone."', '".$fax."', '".$contact."', '".$email."', '".$url."', '".$description."')");
		if (!$query) $msg = $lang["database_error_link_not_added"];
		else $msg = $lang["link_added"];
		mysqli_close($link);
	}
	header("Location: edit_links.php?msg=".$msg."&".$common_get);
}
function editLink($id) {
	global $table_prefix, $link, $common_get, $lang, $edit_categories;
	if (!$edit_categories) {
		$msg = $lang["not_authorized_edit_links"];
	} else {
		if(get_magic_quotes_gpc()) {
            $company = mysqli_real_escape_string($link,stripslashes($_POST["company"]));
			$address1 = mysqli_real_escape_string($link,stripslashes($_POST["address1"]));
			$address2 = mysqli_real_escape_string($link,stripslashes($_POST["address2"]));
			$city = mysqli_real_escape_string($link,stripslashes($_POST["city"]));
			$state = mysqli_real_escape_string($link,stripslashes($_POST["state"]));
			$zip = mysqli_real_escape_string($link,stripslashes($_POST["zip"]));
			$phone = mysqli_real_escape_string($link,stripslashes($_POST["phone"]));
			$fax = mysqli_real_escape_string($link,stripslashes($_POST["fax"]));
			$contact = mysqli_real_escape_string($link,stripslashes($_POST["contact"]));
			$email = mysqli_real_escape_string($link,stripslashes($_POST["email"]));
			$url = mysqli_real_escape_string($link,stripslashes($_POST["url"]));
			$description = mysqli_real_escape_string($link,stripslashes($_POST["description"]));
        } else {
          	$company = mysqli_real_escape_string($link,$_POST["company"]);
			$address1 = mysqli_real_escape_string($link,$_POST["address1"]);
			$address2 = mysqli_real_escape_string($link,$_POST["address2"]);
			$city = mysqli_real_escape_string($link,$_POST["city"]);
			$state = mysqli_real_escape_string($link,$_POST["state"]);
			$zip = mysqli_real_escape_string($link,$_POST["zip"]);
			$phone = mysqli_real_escape_string($link,$_POST["phone"]);
			$fax = mysqli_real_escape_string($link,$_POST["fax"]);
			$contact = mysqli_real_escape_string($link,$_POST["contact"]);
			$email = mysqli_real_escape_string($link,$_POST["email"]);
			$url = mysqli_real_escape_string($link,$_POST["url"]);
			$description = mysqli_real_escape_string($link,$_POST["description"]);
        }
		$q = "UPDATE ".$table_prefix."links set company = '".$company."', address1 = '".$address1."', address2 = '".$address2."', city = '".$city."', state = '".$state."', zip = '".$zip."', phone = '".$phone."', fax = '".$fax."', contact = '".$contact."', email = '".$email."', url ='".$url."', description ='".$description."' where link_id =".$id;
		$query = mysqli_query($link,$q);
		if (!$query) $msg = $lang["database_error_link_not_updated"];
		else $msg = $lang["link_updated"];
		mysqli_close($link);
	}
	header("Location: edit_links.php?msg=".$msg."&".$common_get);
}

function deleteLink($id) {
	global $table_prefix, $link, $common_get, $lang, $edit_categories;
	if (!$edit_categories) {
		$msg = $lang["not_authorized_edit_links"];
	} else {
		$query = mysqli_query($link,"UPDATE ".$table_prefix."events set venue_id = 0 where venue_id =".$id);
		if (!$query) $msg .= $lang["database_error_dependant_events_not_updated"];
		$query = mysqli_query($link,"UPDATE ".$table_prefix."events set contact_id = 0 where contact_id =".$id);
		$query = mysqli_query($link,"DELETE from ".$table_prefix."links where link_id = ".$id);
		if (!$query) $msg .= $lang["database_error_link_not_deleted"];
		if (!$msg) $msg = $lang["link_deleted"];
		mysqli_close($link);
	}
	header("Location: edit_links.php?msg=".$msg."&".$common_get);
}

function deleteUser($id) {
	global $table_prefix, $link, $common_get, $lang, $edit;
	if (!$edit) {
		$msg = $lang["not_authorized_add_users"];
	} else {
		$sub_of = addslashes($_POST["sub_of"]);
		$query = mysqli_query($link,"DELETE from ".$table_prefix."users_to_categories where user_id = ".$id);
		if (!$query) $msg .= $lang["database_error_user_table_not_updated"];
		$query = mysqli_query($link,"DELETE from ".$table_prefix."users where user_id = ".$id);
		if (!$query) $msg .= $lang["database_error_user_not_deleted"];
		if (!$msg) $msg = $lang["user_deleted"];
	}
	mysqli_close($link);
	header("Location: edit_users.php?msg=".$msg."&".$common_get);
}

function deleteEvent($id) {
	global $table_prefix, $edit_categories, $link, $common_get, $lang, $c;
	if ($edit_categories) {
		$edit = true;
	} elseif ($row["user_id"] == $_SESSION["user_id"]) {
		$edit = true;
	} else {
		$q = "select moderate from ".$table_prefix."users_to_categories where category_id = ".$c." and user_id = ".$_SESSION["user_id"];
		$mod = mysql_result(mysqli_query($link,$q),0,0);
		if ($mod >= 2) {
			$edit = true;
		}
	}
	if ($edit == true) {
		$query = mysqli_query($link,"DELETE from ".$table_prefix."dates where event_id = ".$id);
		if (!$query) $msg .= $lang["database_error_dates_not_updated"];
		$query = mysqli_query($link,"DELETE from ".$table_prefix."events where event_id = ".$id);
		if (!$query) $msg .= $lang["database_error_event_not_deleted"];
		if (!$msg) $msg = $lang["event_deleted"];
	} else {
		$msg = $lang["not_authorized_events_category"];
	}
	mysqli_close($link);
	header("Location: index.php?msg=".$msg."&".$common_get);
}

function updateModules() {
	global $table_prefix, $link, $edit, $common_get, $lang;
	if (!$edit) {
		$msg = $lang["not_authorized_add_users"];
	} else {

		foreach($_POST["link_name"] as $key => $val) {
			if(get_magic_quotes_gpc()) {
            	$key = mysqli_real_escape_string($link,stripslashes($key));
				$val = mysqli_real_escape_string($link,stripslashes($val));

        	} else {
          		$key = mysqli_real_escape_string($link,$key);
				$val = mysqli_real_escape_string($link,$val);

       		}

			if ($_POST["delete"][$key]) {
				$del = mysqli_query($link,"delete from ".$table_prefix."modules where module_id = ".$key);
			} else {

				if(get_magic_quotes_gpc()) {
	            	$active = mysqli_real_escape_string($link,stripslashes($_POST["active"][$key]));
					$sequence = mysqli_real_escape_string($link,stripslashes($_POST["sequence"][$key]));
					$year = mysqli_real_escape_string($link,stripslashes($_POST["year"][$key]));
					$month = mysqli_real_escape_string($link,stripslashes($_POST["month"][$key]));
					$week = mysqli_real_escape_string($link,stripslashes($_POST["week"][$key]));
					$day = mysqli_real_escape_string($link,stripslashes($_POST["day"][$key]));

	        	} else {
	          		$active = mysqli_real_escape_string($link,$_POST["active"][$key]);
					$sequence = mysqli_real_escape_string($link,$_POST["sequence"][$key]);
					$year = mysqli_real_escape_string($link,$_POST["year"][$key]);
					$month = mysqli_real_escape_string($link,$_POST["month"][$key]);
					$week = mysqli_real_escape_string($link,$_POST["week"][$key]);
					$day = mysqli_real_escape_string($link,$_POST["day"][$key]);

	       		}

				$update = mysqli_query($link,"update ".$table_prefix."modules set link_name = '".$val."',  name = '".$_POST["name"][$key]."', active = '".$active."', sequence = '".$sequence."', year = '".$year."', month = '".$month."', week = '".$week."', day = '".$day."' where module_id =".$key);
			}
			$msg = $lang["modules_updated"];
		}
	}
	mysqli_close($link);
	header("Location: modules.php?msg=".$msg."&".$common_get);
}

function installModule($dir,$file) {
	global $table_prefix, $link, $edit, $common_get, $lang;
	if (!$edit) {
		$msg = $lang["not_authorized_add_users"];
	} else {
		if ($file) {
			$script = file_get_contents($dir."/".$file);
			$pa = xml_parser_create();
			xml_parse_into_struct($pa, $script, $vals, $index);
			xml_parser_free($pa);
			foreach($index as $k => $v) {
				$mod[$k] = $vals[$index[$k][0]]["value"];
			}
			$query = mysqli_query($link,"INSERT INTO ".$table_prefix."modules (link_name, name, script, year, month, week, day) values('".$mod["LINK_NAME"]."','".$mod["NAME"]."','".$file."',1,2,3,4)");

		} else {
			$msg = $lang["modules_updated"];
		}

	}
	mysqli_close($link);
	header("Location: modules.php?msg=".$msg."&".$common_get);
}


function approve($event_id) {
	global $table_prefix, $lang, $edit_groups, $link;
	if (!$edit_groups) {
		$q = "select moderate from ".$table_prefix."groups_to_events where event_id = ".$event_id." and user_id = ".$_SESSION["user_id"]."";
		$query = mysqli_query($link,$q);
		if (mysqli_num_rows($query) > 0) {
			$mod = mysql_result($query,0,0);
			if ($mod > 2) $moderate = true;
		} else {
			$moderate = false;
		}
	} else {
		$moderate = true;
	}
	if ($moderate) {
		$sq = "update ".$table_prefix."events set status_id = 4, quick_approve = NULL where event_id = '".$event_id."'";
		$squery = mysqli_query($link,$sq);
		if ($squery) {
			$msg = $lang["event_updated"];
			include "includes/notify.php";
			notify_group($event_id);
		}
		else $msg = "Database Error: $sq";
	} else {
		$msg = $lang["not_authorized_approve"];
	}
	header("Location: index.php?msg=".$msg."&".$common_get);

}



if (!$_SESSION["user_id"]) {
	mysqli_close($link);
	header("Location: login.php");
} else {
	$query = mysqli_query($link,"SELECT add_users, add_categories, add_groups, post from ".$table_prefix."users where user_id = ".$_SESSION["user_id"]." limit 1");
	$row = mysqli_fetch_row($query);
	if ($row[2] == 1) {
		$edit_groups = true;
	}
	if ($row[1] == 1) {
		$edit_categories = true;
	}
	if ($row[3] == 1) {
		$post = true;
	}
	if ($row[0] == 1) {
		$edit = true;
	}

	if (($id != $_SESSION["user_id"])&& !$post && !$edit && !$edit_categories && !$edit_groups){

		mysqli_close($link);
		$msg =  $lang["not_authorized"];
		header("Location: index.php?msg=".$msg."&id=".$id."&".$common_get);
	}

	switch ($_REQUEST["mode"]) {
	case $lang["update_profile"];

		updateProfile($id);
		break;

	case $lang["add_category"];
		addCategory();
		break;

	case $lang["edit_category"];
		editCategory($id);
		break;

	case $lang["delete_category"];
		deleteCategory($id);
		break;

	case $lang["add_group"];
		addGroup();
		break;

	case $lang["edit_group"];
		editGroup($id);
		break;

	case $lang["delete_group"];
		deleteGroup($id);
		break;

	case $lang["add_link"];
		addLink();
		break;

	case $lang["edit_link"];
		editLink($id);
		break;

	case $lang["delete_link"];
		deleteLink($id);
		break;

	case $lang["delete_user"];
		deleteUser($id);
		break;

	case $lang["delete_event"];
		deleteEvent($id);
		break;


	case $lang["add_profile"];
		addProfile();
		break;

	case $lang["update modules"];
		updateModules();
		break;

	case "approve";
		approve($id);
		break;

	case "install_module";
		installModule($dir,$file);
		break;


	default;
		header("Location: index.php");
		break;
	}
	mysqli_close($link);
}
?>
