<?php

use Curl\Curl;

// We aren't calling the class Do because otherwise it would conflict with do { } while ();
class D {
	/*
	 * SaveSystemSettings
	 * Save system settings function (ADMIN CP)
	*/
	public static function SaveSystemSettings() {
		try {
			// Get values
      $s_mt_web     = isset($_POST['wm']) ? $_POST['wm'] : 0;
      $s_mt_score   = isset($_POST['gm']) ? $_POST['gm'] : 0;
      $s_reg_ok     = isset($_POST['r'] ) ? $_POST['r']  : 0;
			$s_reg_lock   = isset($_POST['rg']) ? $_POST['rg'] : 0;
			$s_alert_all  = (!empty($_POST['ga'])) ? $_POST['ga'] : '';
			$s_alert_home = (!empty($_POST['ha'])) ? $_POST['ha'] : '';
			$s_feat_video = (!empty($_POST['fv'])) ? $_POST['fv'] : '';
			// Save new values
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'website_maintenance' LIMIT 1", [$s_mt_web]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'game_maintenance' LIMIT 1", [$s_mt_score]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'registrations_enabled' LIMIT 1", [$s_reg_ok]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'regblock' LIMIT 1", [$s_reg_lock]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_global_alert' LIMIT 1", [$s_alert_all]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_home_alert' LIMIT 1", [$s_alert_home]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'featuredvideo' LIMIT 1", [$s_feat_video]);
			foreach (["std" , "taiko", "ctb", "mania"] as $key) {
				if (!isset($_POST["aql_$key"]) || !is_numeric($_POST["aql_$key"])) {
					continue;
				}
				$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'aql_threshold_" . $key . "' LIMIT 1", [$_POST["aql_$key"]]);
			}
			redisConnect();
			$GLOBALS["redis"]->publish("lets:reload_aql", "reload");
			
			// RAP log
			rapLog("has updated system settings");
			// Done, redirect to success page
			redirect('index.php?p=101&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=101&e='.$e->getMessage());
		}
	}

	public static function SaveEditWhitelistIP() {
		try {
			// save value
			$GLOBALS['db']->execute("UPDATE simpen_ip SET kode_negara = ? WHERE id = ? LIMIT 1", [$_POST["kn"], $_POST["idip"]]);
			// RAP log
			rapLog(sprintf("has whitelisted IP %s", $_POST["ipaddress"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}
	/*
	 * SaveBanchoSettings
	 * Save bancho settings function (ADMIN CP)
	*/
	public static function SaveBanchoSettings() {
		try {
			// Get values
			if (isset($_POST['bm'])) {
				$bm = $_POST['bm'];
			} else {
				$bm = 0;
			}
			if (!empty($_POST['mnicon'])) {
				$mnicon = $_POST['mnicon'];
			} else {
				$mnicon = '';
			}
			if (!empty($_POST['lokasiicon'])) {
				$lokasiicon = $_POST['lokasiicon'];
			} else {
				$lokasiicon = '';
			}
			if (!empty($_POST['urlikon'])) {
				$urlikon = $_POST['urlikon'];
			} else {
				$urlikon = '';
			}
			if (!empty($_POST['ln'])) {
				$ln = $_POST['ln'];
			} else {
				$ln = '';
			}
			
			// Save new values
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'bancho_maintenance' LIMIT 1", [$bm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_notification' LIMIT 1", [$ln]);
			$GLOBALS['db']->execute("UPDATE main_menu_icons SET file_id = ? WHERE id = '1'", [$mnicon]);
			$GLOBALS['db']->execute("UPDATE main_menu_icons SET lokasi_file = ? WHERE id = '1'", [$lokasiicon]);
			$GLOBALS['db']->execute("UPDATE main_menu_icons SET url = ? WHERE id = '1'", [$urlikon]);
			// Pubsub
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:reload_settings", "reload");
			// Rap log
			rapLog("has updated bancho settings");
			// Done, redirect to success page
			redirect('index.php?p=111&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=111&e='.$e->getMessage());
		}
	}
    public static function GoToPageWhitelist() {
		try {
			if (!isset($_POST['id']) || empty($_POST['id'])) {
				throw new Exception('Error');
			}
			$checkID = $GLOBALS["db"]->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$_POST['id']]);
			//CHECK USER
			if (!$checkID) {
				throw new Exception("That user doesn\'t exist");
			} else {
				redirect('index.php?p=146&id='.$_POST['id']);
			}
		}
		catch(Exception $e) {
            redirect('index.php?p=148&e='.$e->getMessage());
        }
	}
	/*
	 * SaveEditUser
	 * Save edit user function (ADMIN CP)
	*/
	public static function SaveEditUser() {
		try {
			// Check if everything is set (username color, username style, rank, allowed and notes can be empty)
			if (!isset($_POST['id']) || !isset($_POST['u']) || !isset($_POST['e']) || !isset($_POST['up']) || !isset($_POST['aka']) || empty($_POST['id']) || empty($_POST['u']) || empty($_POST['e'])) {
				throw new Exception('Nice troll');
			}
			// Check if this user exists and get old data
			$oldData = $GLOBALS["db"]->fetch("SELECT * FROM users LEFT JOIN user_config ON users.id = user_config.id WHERE users.id = ? LIMIT 1", [$_POST["id"]]);
			if (!$oldData) {
				throw new Exception("That user doesn\'t exist");
			}
			// Check if we can edit this user
			if ( (($oldData["privileges"] & Privileges::AdminManageUsers) > 0) && $_POST['u'] != $_SESSION['username']) {
				throw new Exception("You don't have enough permissions to edit this user");
			}
			// Check if email is valid
			if (!filter_var($_POST['e'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("The email isn't valid");
			}


			// Check if silence end has changed. if so, we have to kick the client
			// in order to silence him
			//$oldse = current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE username = ?", array($_POST["u"])));

			// Save new data (email, and cm notes)
			$GLOBALS['db']->execute('UPDATE users SET email = ?, notes = ? WHERE id = ? LIMIT 1', [$_POST['e'], $_POST['ncm'], $_POST['id'] ]);
			// Edit silence time if we can silence users
			if (hasPrivilege(Privileges::AdminSilenceUsers)) {
				$GLOBALS['db']->execute('UPDATE users SET silence_end = ?, silence_reason = ? WHERE id = ? LIMIT 1', [$_POST['se'], $_POST['sr'], $_POST['id'] ]);
			}
			// Save new userpage
			$GLOBALS['db']->execute('UPDATE user_config SET userpage_content = ? WHERE id = ? LIMIT 1', [$_POST['up'], $_POST['id']]);
			/* Save new data if set (rank, allowed, UP and silence)
			if (isset($_POST['r']) && !empty($_POST['r']) && $oldData["rank"] != $_POST["r"]) {
				$GLOBALS['db']->execute('UPDATE users SET rank = ? WHERE id = ?', [$_POST['r'], $_POST['id']]);
				rapLog(sprintf("has changed %s's rank to %s", $_POST["u"], readableRank($_POST['r'])));
			}
			if (isset($_POST['a'])) {
				$banDateTime = $_POST['a'] == 0 ? time() : 0;
				$newPrivileges = $oldData["privileges"] ^ Privileges::UserBasic;
				$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ?', [$newPrivileges, $banDateTime, $_POST['id']]);
			}*/
			// Get username style/color
			if (isset($_POST['c']) && !empty($_POST['c'])) {
				$c = $_POST['c'];
			} else {
				$c = 'black';
			}
			if (isset($_POST['bg']) && !empty($_POST['bg'])) {
				$bg = $_POST['bg'];
			} else {
				$bg = '';
			}
			// Update country flag if set
			if (isset($_POST['country']) && countryCodeToReadable($_POST['country']) != 'unknown country' && $oldData["country"] != $_POST['country']) {
				$GLOBALS['db']->execute('UPDATE user_config SET country = ? WHERE id = ? LIMIT 1', [$_POST['country'], $_POST['id']]);
				rapLog(sprintf("has changed %s's flag to %s", $_POST["u"], $_POST['country']));
			}
			// Set username style/color/aka
			$GLOBALS['db']->execute('UPDATE user_config SET user_color = ?, user_style = ?, username_aka = ? WHERE id = ? LIMIT 1', [$c, $bg, $_POST['aka'], $_POST['id']]);
			// RAP log
			rapLog(sprintf("has edited user %s", $_POST["u"]));
			// Done, redirect to success page
			redirect('index.php?p=103&id='.$_POST["id"].'&s=User edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=103&id='.$_POST["id"].'&e='.$e->getMessage());
		}
	}

	
  public static function saveUserPrivileges() {
    try {
        if (!isset($_POST['id']) || !isset($_POST['privup']) || !isset($_POST['uname']) || empty($_POST['uname']) || empty($_POST['id']) || empty($_POST['privup'])) {
            throw new Exception("User not found");
        }
		//UPDATE PRIVILEGES
		$GLOBALS['db']->execute('UPDATE users SET privileges = ? WHERE id = ? LIMIT 1', [$_POST['privup'], $_POST['id']]);
		// RAP log
		rapLog(sprintf("has updated privileges for user %s", $_POST["uname"]));
		// Done, redirect to success page
		redirect('index.php?p=149&id='.$_POST["id"].'&s=User edited!');
	}
	catch(Exception $e) {
		// Redirect to Exception page
		redirect('index.php?p=149&id='.$_POST["id"].'&e='.$e->getMessage());
	}
  }
  public static function saveEditUserWhitelist() {
    try {
      // Check if everything is set (username color, username style, rank, allowed and notes can be empty)
      if (!isset($_POST['id']) || empty($_POST['id'])) {
		 throw new Exception("eh");
	  }
	  // Get user's username
	  $userData = $GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ? LIMIT 1', $_POST['id']);
	  if (!$userData) {
		  throw new Exception("User doesn't exist");
	  }
      foreach([0,1,2,3] as $gm) {
        foreach([0,1,2] as $sm) {
          if ($sm == 2) continue;
          $flag = sprintf("flag%02d%02d", $sm, $gm);
          if(isset($_POST[$flag]) && strlen($_POST[$flag])>0) {
            $GLOBALS['db']->execute('update master_stats set unrestricted_play = ? where user_id = ? and special_mode = ? and game_mode = ? and unrestricted_play <> ?',[
              $_POST[$flag], $_POST['id'], $sm, $gm, $_POST[$flag]
            ]);
          }
        }
      }
      rapLog(sprintf("has edited username %s (ID: %s) PP whitelist", $userData["username"], $_POST["id"]));
      redirect('index.php?p=146&id='.$_POST["id"].'&s=User edited!');
    }
    catch(Exception $e) {
      // Redirect to users
      redirect('index.php?p=146&id='.$_POST["id"].'&e='.$e->getMessage());
    }
  }

	/*
	 * BanUnbanUser
	 * Ban/Unban user function (ADMIN CP)
	*/
	public static function BanUnbanUser() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to ban this user");
			}
			// Get new allowed value
			if ( ($userData["privileges"] & Privileges::UserNormal) > 0) {
				// Ban, reset UserNormal and UserPublic bits
				$banDateTime = time();
				$newPrivileges = $userData["privileges"] & ~Privileges::UserNormal;
				$newPrivileges &= ~Privileges::UserPublic;
				removeFromLeaderboard($_GET['id']);
			} else {
				// Unban, set UserNormal and UserPublic bits
				$banDateTime = 0;
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges |= Privileges::UserPublic;
			}
			//$newPrivileges = $userData["privileges"] ^ Privileges::UserBasic;
			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_GET['id']]);
			updateBanBancho($_GET["id"]);
			// Rap log
			rapLog(sprintf("has %s user %s", ($newPrivileges & Privileges::UserNormal) > 0 ? "unbanned" : "banned", $userData["username"]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User banned/unbanned/activated!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * QuickEditUser
	 * Redirects to the edit user page for the user with $_POST["u"] username
	*/
	public static function QuickEditUser($email = false) {
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch(sprintf('SELECT id FROM users WHERE %s = ? LIMIT 1', $email ? 'email' : 'username'), [$_POST['u']]));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=103&id='.$id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function QuickEditWhitelistIP() {
		try {
			// Check if everything is set
			if (empty($_POST['ipnya'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM simpen_ip WHERE alamat_ip = ? LIMIT 1', $_POST['ipnya']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That ip doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=139&id='.$id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * QuickEditUserBadges
	 * Redirects to the edit user badges page for the user with $_POST["u"] username
	*/
	public static function QuickEditUserBadges() {
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Done, redirect to edit page
			redirect('index.php?p=110&id='.$id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=110&id='.$id.'&e='.$e->getMessage());
		}
	}

	/*
	 * ChangeIdentity
	 * Change identity function (ADMIN CP)
	*/
	public static function ChangeIdentity() {
		global $DiscordHook;
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['oldu']) || !isset($_POST['newu']) || empty($_POST['id']) || empty($_POST['oldu']) || empty($_POST['newu'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we can edit this user
			$privileges = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$privileges) {
				throw new Exception("User doesn't exist");
			}
			$privileges = current($privileges);
			if ( (($privileges & Privileges::AdminManageUsers) > 0) && $_POST['oldu'] != $_SESSION['username']) {
				throw new Exception("You don't have enough permissions to edit this user");
			}
			// No username with mixed spaces
			if (strpos($_POST["newu"], " ") !== false && strpos($_POST["newu"], "_") !== false) {
				throw new Exception('Usernames with both spaces and underscores are not supported.');
			}
			// Check if username is already in db
			$safe = safeUsername($_POST["newu"]);
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE username_safe = ? AND id != ? LIMIT 1', [$safe, $_POST["id"]])) {
				throw new Exception('Username already used by another user. No changes have been made.');
			}
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:change_username", json_encode([
				"userID" => intval($_POST["id"]),
				"newUsername" => $_POST["newu"]
			]));
			//DiscordLog
			$webhookurl = $DiscordHook["name-log"];
			$lama = $_POST["oldu"];
			$baru = $_POST["newu"];
			$json_data = json_encode(
			[
				"username" => "Log Name",
				"embeds" =>
				[
					[

						"description" => "User : $lama has changed their name to $baru !",
						"color" => hexdec( "eb34c6" )

					]
				]

			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


			$ch = curl_init( $webhookurl );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
			curl_setopt( $ch, CURLOPT_POST, 1);
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt( $ch, CURLOPT_HEADER, 0);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

			$response = curl_exec( $ch );
			curl_close( $ch );
			// rap log
			rapLog(sprintf("has changed %s's username to %s", $_POST["oldu"], $_POST["newu"]));
			// Done, redirect to success page
			redirect('index.php?p=103&id='.$_POST["id"].'&s=User identity changed! It might take a while to change the username if the user is online on Bancho.');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=103&id='.$_POST["id"].'&e='.$e->getMessage());
		}
	}

	/*
	 * SaveBadge
	 * Save badge function (ADMIN CP)
	*/
	public static function SaveBadge() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['n']) || !isset($_POST['i']) || empty($_POST['n']) || empty($_POST['i'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we are creating or editing a doc page
			if ($_POST['id'] == 0) {
				$GLOBALS['db']->execute('INSERT INTO badges (id, name, icon) VALUES (NULL, ?, ?)', [$_POST['n'], $_POST['i']]);
			} else {
				$GLOBALS['db']->execute('UPDATE badges SET name = ?, icon = ? WHERE id = ? LIMIT 1', [$_POST['n'], $_POST['i'], $_POST['id']]);
			}
			// RAP log
			rapLog(sprintf("has %s badge %s", $_POST['id'] == 0 ? "created" : "edited", $_POST["n"]));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * SaveUserBadges
	 * Save user badges function (ADMIN CP)
	*/
	public static function SaveUserBadges() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['b01']) || !isset($_POST['b02']) || !isset($_POST['b03']) || !isset($_POST['b04']) || !isset($_POST['b05']) || !isset($_POST['b06']) || empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			$user = $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']);
			// Make sure that this user exists
			if (!$user) {
				throw new Exception("That user doesn't exist.");
			}
			// delete current badges
			$GLOBALS["db"]->execute("DELETE FROM user_badges WHERE user = ?", [$user["id"]]);
			// add badges
			for ($i = 0; $i <= 6; $i++) {
				$x = $_POST["b0" . $i];
				if ($x == 0) continue;
				$GLOBALS["db"]->execute("INSERT INTO user_badges(user, badge) VALUES (?, ?);", [$user["id"], $x]);
			}
			// RAP log
			rapLog(sprintf("has edited %s's badges", $_POST["u"]));
			// Done, redirect to success page
			redirect('index.php?p=110&id='.$user["id"].'&s=Badge edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=110&id='.$user["id"].'&e='.$e->getMessage());
		}
	}

	/*
	 * RemoveBadge
	 * Remove badge function (ADMIN CP)
	*/
	public static function RemoveBadge() {
		try {
			// Make sure that this is not the "None badge"
			if (empty($_GET['id'])) {
				throw new Exception("You can't delete this badge.");
			}
			// Make sure that this badge exists
			$name = $GLOBALS['db']->fetch('SELECT name FROM badges WHERE id = ? LIMIT 1', $_GET['id']);
			// Badge doesn't exists wtf
			if (!$name) {
				throw new Exception("This badge doesn't exists");
			}
			// Delete badge
			$GLOBALS['db']->execute('DELETE FROM badges WHERE id = ? LIMIT 1', $_GET['id']);
			// delete badge from relationships table
			$GLOBALS['db']->execute('DELETE FROM user_badges WHERE badge = ?', $_GET['id']);
			// RAP log
			rapLog(sprintf("has deleted badge %s", current($name)));
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge deleted!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e='.$e->getMessage());
		}
	}

	/*
	 * SilenceUser
	 * Silence someone (ADMIN CP)
	*/
	public static function silenceUser() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['c']) || !isset($_POST['un']) || !isset($_POST['r']) || !isset($_POST["r"]) || empty($_POST['u']) || empty($_POST['un']) || empty($_POST["r"])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = getUserID($_POST["u"]);
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Calculate silence period length
			$sl = $_POST['c'] * $_POST['un'];
			// Make sure silence time is less than 7 days
			if ($sl > 604800) {
				throw new Exception('Invalid silence length. Maximum silence length is 7 days.');
			}
			// Silence and reconnect that user
			$GLOBALS["db"]->execute("UPDATE users SET silence_end = ?, silence_reason = ? WHERE id = ? LIMIT 1", [time() + $sl, $_POST["r"], $id]);
			updateSilenceBancho($id);
			// RAP log and redirect
			if ($sl > 0) {
				rapLog(sprintf("has silenced user %s for %s for the following reason: \"%s\"", $_POST['u'], timeDifference(time() + $sl, time(), false), $_POST["r"]));
				$msg = 'index.php?p=102&s=User silenced!';
			} else {
				rapLog(sprintf("has removed %s's silence", $_POST['u']));
				$msg = 'index.php?p=102&s=User silence removed!';
			}
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s='.$msg);
			} else {
				redirect('index.php?p=102&s='.$msg);
			}
		}
		catch(Exception $e) {
			// Redirect to Exception page
			if (isset($_POST["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e='.$e->getMessage());
			} else {
				redirect('index.php?p=102&e='.$e->getMessage());
			}
		}
	}

	/*
	 * KickUser
	 * Kick someone from bancho (ADMIN CP)
	*/
	public static function KickUser() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || empty($_POST['u']) || !isset($_POST["r"]) || empty($_POST["r"])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ? LIMIT 1', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exist");
			}
			// Kick that user
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:disconnect", json_encode([
				"userID" => intval($id),
				"reason" => $_POST["r"]
			]));
			// Done, redirect to success page
			redirect('index.php?p=102&s=User kicked!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * ResetAvatar
	 * Reset soneone's avatar (ADMIN CP)
	*/
	public static function ResetAvatar() {
		try {
			// Check if everything is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$avatar = dirname(dirname(dirname(__FILE__))).'/avatars/'.$_GET['id'].'.png';
			if (!file_exists($avatar)) {
				throw new Exception("That user doesn't have an avatar");
			}
			// Delete user avatar
			unlink($avatar);
			// Rap log
			rapLog(sprintf("has reset %s's avatar", getUserUsername($_GET['id'])));
			// Done, redirect to success page
			redirect('index.php?p=102&s=Avatar reset!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	/*
	 * Logout
	 * Logout and return to home
	*/
	public static function Logout() {
		// Logging out without being logged in doesn't make much sense
		if (checkLoggedIn()) {
			startSessionIfNotStarted();
			if (isset($_COOKIE['sli'])) {
				$rch = new RememberCookieHandler();
				$rch->Destroy();
			}
			$_SESSION = [];
			session_unset();
			session_destroy();
		} else {
			// Uhm, some kind of error/h4xx0r. Let's return to login page just because yes.
			redirect('index.php?p=2');
		}
	}

	/*
	 * ForgetEveryCookie
	 * Allows the user to delete every field in the remember database table with their username, so that it is logged out of every computer they were logged in.
	*/
	public static function ForgetEveryCookie() {
		startSessionIfNotStarted();
		$rch = new RememberCookieHandler();
		$rch->DestroyAll($_SESSION['userid']);
		redirect('index.php?p=1&s=forgetDone');
	}

	/*
	 * saveUserSettings
	 * Save user settings functions
	*/
	public static function saveUserSettings() {
		global $PlayStyleEnum;
		try {
			function valid($value, $min=0, $max=1) {
				return ($value >= $min && $value <= $max);
			}

			// Check if we are logged in
			sessionCheck();
			// Restricted check
			if (isRestricted()) {
				throw new Exception(1);
			}
			// Check everything is set
			if (!isset($_POST['c']) || !isset($_POST['aka']) || !isset($_POST['st']) || !isset($_POST['mode'])) {
				throw new Exception(0);
			}
			// Make sure values are valid
			if (!valid($_POST['mode'], 0, 3) || !valid($_POST['st']) || (isset($_POST["showCustomBadge"]) && !valid($_POST["showCustomBadge"]))) {
				throw new Exception(0);
			}
			// Check if username color is not empty and if so, set to black (default)
			if (empty($_POST['c']) || !preg_match('/^#[a-f0-9]{6}$/i', $_POST['c'])) {
				$c = 'black';
			} else {
				$c = $_POST['c'];
			}
			// Playmode stuff
			$pm = 0;
			foreach ($_POST as $key => $value) {
				$i = str_replace('_', ' ', substr($key, 3));
				if ($value == 1 && substr($key, 0, 3) == 'ps_' && isset($PlayStyleEnum[$i])) {
					$pm += $PlayStyleEnum[$i];
				}
			}
			// Save custom badge
			$canCustomBadge = current($GLOBALS["db"]->fetch("SELECT can_custom_badge FROM user_config WHERE id = ? LIMIT 1", [$_SESSION["userid"]])) == 1;
			if (hasPrivilege(Privileges::UserDonor) && $canCustomBadge && isset($_POST["showCustomBadge"]) && isset($_POST["badgeName"]) && isset($_POST["badgeIcon"])) {
				// Script kiddie check 1
				$forbiddenNames = ["BAT", "Developer", "Community Manager"];
				if (in_array($_POST["badgeName"], $forbiddenNames)) {
					throw new Fava(0);
				}

				$oldCustomBadge = $GLOBALS["db"]->fetch("SELECT custom_badge_name AS name, custom_badge_icon AS icon FROM user_config WHERE id = ? LIMIT 1", [$_SESSION["userid"]]);

				// Script kiddie check 2
				// (is this even needed...?)
				$forbiddenClasses = ["fa-lg", "fa-2x", "fa-3x", "fa-4x", "fa-5x", "fa-ul", "fa-li", "fa-border", "fa-pull-right", "fa-pull-left", "fa-stack", "fa-stack-2x", "fa-stack-1x"];
				$icon = explode(" ", $_POST["badgeIcon"]);
				for ($i=0; $i < count($icon); $i++) {
					if (substr($icon[$i], 0, 3) != "fa-" || in_array($icon[$i], $forbiddenClasses)) {
						$icon[$i] = "";
					}
				}
				$icon = implode(" ", $icon);
				$GLOBALS["db"]->execute("UPDATE user_config SET show_custom_badge = ?, custom_badge_name = ?, custom_badge_icon = ? WHERE id = ? LIMIT 1", [$_POST["showCustomBadge"], $_POST["badgeName"], $icon, $_SESSION["userid"]]);
			}
			// Save data in db
			$GLOBALS['db']->execute('UPDATE user_config SET user_color = ?, username_aka = ?, safe_title = ?, play_style = ?, favorite_mode = ? WHERE id = ? LIMIT 1', [$c, $_POST['aka'], $_POST['st'], $pm, $_POST['mode'], $_SESSION['userid']]);
			// Update safe title cookie
			updateSafeTitle();
			// Done, redirect to success page
			redirect('index.php?p=6&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=6&e='.$e->getMessage());
		}
	}

	/*
	 * WipeAccount
	 * Wipes an account
	*/
	public static function WipeAccount() {
		try {
			if (!isset($_POST['id']) || empty($_POST['id'])) {
				throw new Exception('Invalid request');
			}
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$userData) {
				throw new Exception('User doesn\'t exist.');
			}
			$username = $userData["username"];
			// Check if we can wipe this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to wipe this account");
			}

			if ($_POST["gm"] == -1) {
				// All modes
				$modes = ['std', 'taiko', 'ctb', 'mania'];
			} else {
				// 1 mode
				if ($_POST["gm"] == 0) {
					$modes = ['std'];
				} else if ($_POST["gm"] == 1) {
					$modes = ['taiko'];
				} else if ($_POST["gm"] == 2) {
					$modes = ['ctb'];
				} else if ($_POST["gm"] == 3) {
					$modes = ['mania'];
				}
			}
			
			// Delete scores
			// Reset mode stats
			$GLOBALS['db']->execute('call scores_master_wipe_select(?, ?, ?)', [$_POST['id'], $_POST['ppmode']-1, $_POST['gm']]);

			// RAP log
			rapLog(sprintf("has wiped %s's account", $username));

			// Done
			redirect('index.php?p=102&s=User scores and stats have been wiped!');
		}
		catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}
	
	/*
	 * ProcessRankRequest
	 * Rank/unrank a beatmap
	*/
	public static function ProcessRankRequest() {
		global $URL;
		global $ScoresConfig;
		try {
			if (!isset($_GET["id"]) || !isset($_GET["r"]) || empty($_GET["id"]))
				throw new Exception("no");

			// Get beatmapset id
			$requestData = $GLOBALS["db"]->fetch("SELECT * FROM rank_requests WHERE id = ? AND hidden = 0 LIMIT 1", [$_GET["id"]]);
			if (!$requestData)
				throw new Exception("Rank request not found");

			if ($requestData["type"] == "s") {
				// We already have the beatmapset id
				$bsid = $requestData["bid"];
			} else {
				// We have the beatmap but we don't have the beatmap set id.
				$result = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$requestData["bid"]]);
				if (!$result)
					throw new Exception("Beatmap set id not found. Load the beatmap ingame and try again.");
				$bsid = current($result);
			}

			// TODO: Save all beatmaps from a set in db with a given beatmap set id

			if ($_GET["r"] == 0) {
				// Unrank the map set and force osu!api update by setting latest update to 01/01/1970 top stampa piede
				$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 0, latest_update = 0 WHERE beatmapset_id = ?", [$bsid]);
			} else {
				// Rank the map set and freeze status rank
				$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 2, ranked_status_freezed = 1 WHERE beatmapset_id = ?", [$bsid]);

				// send a message to #announce
				$bm = $GLOBALS["db"]->fetch("SELECT beatmapset_id, song_name FROM beatmaps WHERE beatmapset_id = ? LIMIT 1", [$bsid]);

				$msg = "[https://osu.ppy.sh/s/" . $bsid . " " . $bm["song_name"] . "] is now ranked!";
				$to = "%23ranked-now";
				$requesturl = $URL["bancho"] . "/api/v1/fokabotMessage?k=" . $ScoresConfig["api_key"] . "&to=" . urlencode($to) . "&msg=" . urlencode($msg);
				$resp = getJsonCurl($requesturl);
	
				if ($resp["message"] != "ok") {
					rapLog("failed to send YohaneBot message :( err: " . print_r($resp["message"], true));
				}
			}

			// RAP log
			rapLog(sprintf("has %s beatmap set %s", $_GET["r"] == 0 ? "unranked" : "ranked", $bsid), $_SESSION["userid"]);

			// Done
			redirect("index.php?p=117&s=野生のちんちんが現れる"); // kontol gue bisa baca ini
		}
		catch(Exception $e) {
			redirect("index.php?p=117&e=".$e->getMessage());
		}
	}


	/*
	 * BlacklistRankRequest
	 * Toggle blacklist for a rank request
	*/
	public static function BlacklistRankRequest() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("no");
			$GLOBALS["db"]->execute("UPDATE rank_requests SET blacklisted = IF(blacklisted=1, 0, 1) WHERE id = ? LIMIT 1", [$_GET["id"]]);
			$reqData = $GLOBALS["db"]->fetch("SELECT type, bid FROM rank_requests WHERE id = ? AND hidden = 0 LIMIT 1", [$_GET["id"]]);
			rapLog(sprintf("has toggled blacklist flag on beatmap %s %s", $reqData["type"] == "s" ? "set" : "", $reqData["bid"]), $_SESSION["userid"]);
			redirect("index.php?p=117&s=Blacklisted flag changed");
		}
		catch(Exception $e) {
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}

	public static function BMNotes() {
		global $DiscordHook;
		try {
			if (isset($_POST['bmid'])) {
        $bmid = $_POST['bmid'];
      } else {
        $bmid = 0;
      }
			$infoBM = $GLOBALS["db"]->fetch("SELECT beatmapset_id, artist, title, difficulty_name FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$_POST["bmid"]]);
			$Alasan = $_POST["bmreason"];
			if (!(bool)($Alasan))
				throw new Exception("Empty reason");
			//KIRIM KE DISCORD
      $NotesWebhook = $DiscordHook["map-log"];
      $datamap_json = json_encode(
      [
			// "username" => "Ranked Bot",
          "embeds" => [
          	[
              	"title" => cleanupBeatmapName(sprintf("%s - %s [%s]", $infoBM['artist'], $infoBM['title'], $infoBM['difficulty_name'])),
              	"url" => "https://osu.ppy.sh/s/" . $infoBM["beatmapset_id"] . "",
              	"description" => "$Alasan",
              	"color" => hexdec( "3366ff" ),
              	"footer" => [
              		"text" => "Reason give by " . $_SESSION["username"] . "",
              		"icon_url" => "https://a.datenshi.pw/" . $_SESSION["userid"] . ""
              	],
              	"thumbnail" => [
              		"url" => "https://b.ppy.sh/thumb/" . $infoBM["beatmapset_id"] . ".jpg"
              	]
          	]
          ]
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
      $cr = curl_init( $NotesWebhook );
      curl_setopt( $cr, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
      curl_setopt( $cr, CURLOPT_POST, 1);
      curl_setopt( $cr, CURLOPT_POSTFIELDS, $datamap_json);
      curl_setopt( $cr, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt( $cr, CURLOPT_HEADER, 0);
      curl_setopt( $cr, CURLOPT_RETURNTRANSFER, 1);

      $respon = curl_exec( $cr );
      curl_close( $cr );
      // END
			rapLog("telah memberikan alasan pada sebuah beatmap");
      redirect("index.php?p=117&s=Success Memberikan Alasan!");
		}
		catch(Exception $e) {
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}


	public static function MarkDone() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("bruh");
			$GLOBALS["db"]->execute("UPDATE rank_requests SET hidden = 1 WHERE id = ?", [$_GET["id"]]);
			rapLog("has marked done some beatmaps");
			redirect("index.php?p=117&s=The beatmap was marked as done and deleted on database!");
		}
		catch(Exception $e) {
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}
	
	public static function savePrivilegeGroup() {
		try {
			// Args check
			if (!isset($_POST["id"]) || !isset($_POST["n"]) || !isset($_POST["priv"]) || !isset($_POST["c"]))
				throw new Exception("DON'T YOU TRYYYY!!");

			if ($_POST["id"] == 0) {
				// New group
				// Make sure name is unique
				$other = $GLOBALS["db"]->fetch("SELECT id FROM privileges_groups WHERE name = ?", [$_POST["n"]]);
				if ($other) {
					throw new Exception("There's another group with the same name");
				}

				// Insert new group
				$GLOBALS["db"]->execute("INSERT INTO privileges_groups (id, name, privileges, color) VALUES (NULL, ?, ?, ?)", [$_POST["n"], $_POST["priv"], $_POST["c"]]);
			} else {
				// Get old privileges and make sure group exists
				$oldPriv = $GLOBALS["db"]->fetch("SELECT privileges FROM privileges_groups WHERE id = ? LIMIT 1", [$_POST["id"]]);
				if (!$oldPriv) {
					throw new Exception("That privilege group doesn't exist");
				}
				$oldPriv = current($oldPriv);
				// Update existing group
				$GLOBALS["db"]->execute("UPDATE privileges_groups SET name = ?, privileges = ?, color = ? WHERE id = ? LIMIT 1", [$_POST["n"], $_POST["priv"], $_POST["c"], $_POST["id"]]);
				// Get users in this group
				// I genuinely want to kill myself right now.
				$users = $GLOBALS["db"]->fetchAll("SELECT id FROM users WHERE privileges = ".$oldPriv." OR privileges = ".$oldPriv." | ".Privileges::UserDonor);
				foreach ($users as $user) {
					// Remove privileges from previous group
					$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~".$oldPriv." WHERE id = ? LIMIT 1", [$user["id"]]);
					// Add privileges from new group
					$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | ".$_POST["priv"]." WHERE id = ? LIMIT 1", [$user["id"]]);
				}
			}

			// Fin.
			redirect("index.php?p=118&s=Saved!");
		} catch (Exception $e) {
			// There's a memino divertentino
			redirect("index.php?p=118&e=".$e->getMessage());
		}
	}


	/*
	 * RestrictUnrestrictUser
	 * restricte/unrestrict user function (ADMIN CP)
	*/
	public static function RestrictUnrestrictUser() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get user's username
			$userData = $GLOBALS['db']->fetch('SELECT username, privileges FROM users WHERE id = ? LIMIT 1', $_GET['id']);
			if (!$userData) {
				throw new Exception("User doesn't exist");
			}
			// Check if we can ban this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to ban this user");
			}
			// Get new allowed value
			if (!isRestricted($_GET["id"])) {
				// Restrict, set UserNormal and reset UserPublic
				$banDateTime = time();
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges &= ~Privileges::UserPublic;
				removeFromLeaderboard($_GET['id']);
			} else {
				// Remove restrictions, set both UserPublic and UserNormal
				$banDateTime = 0;
				$newPrivileges = $userData["privileges"] | Privileges::UserNormal;
				$newPrivileges |= Privileges::UserPublic;
			}
			// Change privileges
			$GLOBALS['db']->execute('UPDATE users SET privileges = ?, ban_datetime = ? WHERE id = ? LIMIT 1', [$newPrivileges, $banDateTime, $_GET['id']]);
			updateBanBancho($_GET["id"]);
			// Rap log
			rapLog(sprintf("has %s user %s", ($newPrivileges & Privileges::UserPublic) > 0 ? "removed restrictions on" : "restricted", $userData["username"]));
			// Done, redirect to success page
			if (isset($_GET["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&s=User restricted/unrestricted!');
			} else {
				redirect('index.php?p=102&s=User restricted/unrestricted!');
			}
		}
		catch(Exception $e) {
			// Redirect to Exception page
			if (isset($_GET["resend"])) {
				redirect(stripSuccessError($_SERVER["HTTP_REFERER"]) . '&e='.$e->getMessage());
			} else {
				redirect('index.php?p=102&e='.$e->getMessage());
			}
		}
	}

	public static function GiveDonor() {
		global $DiscordHook;
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["m"]) || empty($_POST["m"]))
				throw new Exception("Invalid user");
			$uname = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_POST["id"]]);
			if (!$uname) {
				throw new Exception("Invalid user");
			}
			$uname = current($uname);
			$months = giveDonor($_POST["id"], $_POST["m"], $_POST["type"] == 0);
			$DCid = $GLOBALS["db"]->fetch("SELECT discord_tokens.user_id, discord_tokens.token, users.username, discord_tokens.discord_id, discord_tokens.role_id FROM discord_tokens INNER JOIN users ON discord_tokens.user_id=users.id WHERE discord_tokens.user_id = ?
			", [$_POST["id"]]);
			if ($DCid['discord_id'] == NULL) {
				$namaDonat = sprintf("%s", $uname);
			} else {
				$namaDonat = sprintf("<@%s>", $DCid['discord_id']);
			}
			$bulannya = $_POST["m"];
			//KIRIM KE DISCORD
			$kirimdonat = $DiscordHook["donations-log"];
			$donat_data = json_encode(
			[
				// "username" => "Ranked Bot",
				"content" => "Thank you for $bulannya months donations! $namaDonat"
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$crotdonat = curl_init( $kirimdonat );
			curl_setopt( $crotdonat, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
			curl_setopt( $crotdonat, CURLOPT_POST, 1);
			curl_setopt( $crotdonat, CURLOPT_POSTFIELDS, $donat_data);
			curl_setopt( $crotdonat, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt( $crotdonat, CURLOPT_HEADER, 0);
			curl_setopt( $crotdonat, CURLOPT_RETURNTRANSFER, 1);

			$resp = curl_exec( $crotdonat );
			curl_close( $crotdonat );
			// END
			rapLog(sprintf("has given donor for %s months to user %s", $_POST["m"], $uname), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Donor status changed. Donor for that user now expires in ".$months." months!");
		}
		catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function RemoveDonor() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$username = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$_GET["id"]]);
			if (!$username) {
				throw new Exception("That user doesn't exist");
			}
			$username = current($username);
			$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges & ~".Privileges::UserDonor.", donor_expire = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);

			// Remove donor badge
			// 14 = donor badge id
			$GLOBALS["db"]->execute("DELETE FROM user_badges WHERE user = ? AND badge = ?", [$_GET["id"], 1002]);
			// Set custom badge
			$GLOBALS["db"]->execute("UPDATE user_config SET can_custom_badge = 0 WHERE id = ?", [$_GET["id"]]);

			rapLog(sprintf("has removed donor from user %s", $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=Donor status changed!");
		}
		catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function Rollback() {
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT username, privileges FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			$username = $userData["username"];
			// Check if we can rollback this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to rollback this account");
			}
			switch ($_POST["period"]) {
				case "d": $periodSeconds = 86400; $periodName = "Day"; break;
				case "w": $periodSeconds = 86400*7; $periodName = "Week"; break;
				case "m": $periodSeconds = 86400*30; $periodName = "Month"; break;
				case "y": $periodSeconds = 86400*365; $periodName = "Year"; break;
			}

			//$removeAfterOsuTime = UNIXTimestampToOsuDate(time()-($_POST["length"]*$periodSeconds));
			$removeAfter = time()-($_POST["length"]*$periodSeconds);
			$rollbackString = $_POST["length"]." ".$periodName;
			if ($_POST["length"] > 1) {
				$rollbackString .= "s";
			}

			$GLOBALS["db"]->execute("INSERT INTO scores_removed SELECT * FROM scores WHERE user_id = ? AND time >= ?", [$_POST["id"], $removeAfter]);
			$GLOBALS["db"]->execute("DELETE FROM scores WHERE user_id = ? AND time >= ?", [$_POST["id"], $removeAfter]);

			rapLog(sprintf("has rolled back %s %s's account", $rollbackString, $username), $_SESSION["userid"]);
			redirect("index.php?p=102&s=User account has been rolled back!");
		} catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function lockUnlockUser() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"]))
				throw new Exception("Invalid user");
			$userData = $GLOBALS["db"]->fetch("SELECT id, privileges, username FROM users WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$userData) {
				throw new Exception("That user doesn't exist");
			}
			// Check if we can edit this user
			if ( ($userData["privileges"] & Privileges::AdminManageUsers) > 0) {
				throw new Exception("You don't have enough permissions to lock this account");
			}
			// Make sure the user is not banned/restricted
			if (!hasPrivilege(Privileges::UserPublic, $_GET["id"])) {
				throw new Exception("The user is banned or restricted. You can't lock an account if it's banned or restricted. Only normal accounts can be locked.");
			}

			// Grant/revoke custom badge privilege
			$lockUnlock = (hasPrivilege(Privileges::UserNormal, $_GET["id"])) ? "locked" : "unlocked";
			$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges ^ 2 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			rapLog(sprintf("has %s %s's account", $grantRevoke, $userData["username"]), $_SESSION["userid"]);
			redirect("index.php?p=102&s=User locked/unlocked!");
		} catch(Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function RankBeatmapNew() {
		global $URL;
		global $ScoresConfig;
		global $DiscordHook;
		try {
			if (!isset($_POST["beatmaps"])) {
				throw new Exception("Invalid form data");
			}

			$bsid = -1;
			$result = "";
			$updateCache = false;

			// Do stuff for each beatmap
			foreach ($_POST["beatmaps"] as $beatmapID => $status) {
				$logToRap = true;

				// Get beatmap set id if not set yet
				if ($bsid == -1) {
					$bsid = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);
					if (!$bsid) {
						throw new Exception("Beatmap set not found! Please load one diff from this set ingame and try again.");
					}
					$bsid = current($bsid);
				}

				switch ($status) {
					// Rank beatmap
					case "rank":
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 2, ranked_status_freezed = 1 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);

						// Restore old scores
						$GLOBALS["db"]->execute("UPDATE scores s JOIN (SELECT userid, MAX(score) maxscore FROM scores JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5 WHERE beatmaps.beatmap_md5 = (SELECT beatmap_md5 FROM beatmaps WHERE beatmap_id = ? LIMIT 1) GROUP BY userid) s2 ON s.score = s2.maxscore AND s.user_id = s2.user_id SET completed = 3", [$beatmapID]);
						$result = "$beatmapID has been ranked and its scores have been restored. | ";
						$rap = "ranked";
					break;
					// Love beatmap (INCASE THE BEATMAP IS TOO MUCH PP)
					case "love":
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 5, ranked_status_freezed = 1 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);

						// Restore old scores
						$GLOBALS["db"]->execute("UPDATE scores s JOIN (SELECT userid, MAX(score) maxscore FROM scores JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5 WHERE beatmaps.beatmap_md5 = (SELECT beatmap_md5 FROM beatmaps WHERE beatmap_id = ? LIMIT 1) GROUP BY userid) s2 ON s.score = s2.maxscore AND s.user_id = s2.user_id SET completed = 3", [$beatmapID]);
						$result = "$beatmapID has been loved and its scores have been restored. | ";
						$rap = "loved";
					break;
					// Unrank beatmap (If there are any unfair ranking like TEAR FUCKING RAIN BY JONATHAN LOSER FUCKING JONATHAN)
					case "unrank":
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 1 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);

						// Restore old scores
						$GLOBALS["db"]->execute("UPDATE scores s JOIN (SELECT userid, MAX(score) maxscore FROM scores JOIN beatmaps ON scores.beatmap_md5 = beatmaps.beatmap_md5 WHERE beatmaps.beatmap_md5 = (SELECT beatmap_md5 FROM beatmaps WHERE beatmap_id = ? LIMIT 1) GROUP BY userid) s2 ON s.score = s2.maxscore AND s.user_id = s2.user_id SET completed = 2", [$beatmapID]);
						$result = "$beatmapID has been ranked and its scores have been mark as old scores. | ";
						$rap = "unranked";
					break;
					// Force osu!api update (unfreeze)
					case "update":
						$updateCache = true;
						$GLOBALS["db"]->execute("UPDATE beatmaps SET ranked = 0, ranked_status_freezed = 0 WHERE beatmap_id = ? LIMIT 1", [$beatmapID]);
						$result = "$beatmapID's ranked status is the same from official osu!. | ";
						$rap = "updated status from bancho for";
					break;

					// No changes
					case "no":
						$result = "$beatmapID's ranked status has not been edited!. | ";
						$rap = "ignored";
					break;

					// osuHOW
					default:
						throw new Exception("Unknown ranked status value.");
					break;
				}
				// DAP Log
				if ($logToRap)
					rapLog(sprintf("has %s beatmap id %s", $rap, $bsid), $_SESSION["userid"]);
			}

			global $URL;
			if ($updateCache) {
				post_content_http($URL["scores"]."/api/v1/cacheBeatmap", [
					"sid" => $bsid,
					"refresh" => 1
				], 30);
			}


			// Send a message to #announce
			// TODO BENERIN INI
			// SEMENTARA GA MASOK KE BANCHO MESSAGENYA
			// NANTI DITAMBAH KALAU MOOD -trok
			$bm = $GLOBALS["db"]->fetch("SELECT b.beatmapset_id, b.artist, b.title, b.difficulty_name, b.bpm, bs.length_drain, bs.length_total FROM beatmaps b LEFT JOIN beatmaps_statistics bs ON b.beatmap_id = bs.beatmap_id AND b.mode = bs.game_mode and bs.mods = 0 WHERE b.beatmap_id = ? LIMIT 1", [$beatmapID]);
			if ($status == "rank") {
				$postStatus = "ranked";
			} else if ($status == "love") {
				$postStatus = "loved";
			} else if ($status == "unrank") {
				$postStatus = "unranked";
			} else if ($status == "update") {
				$postStatus = "reset";
			} else if ($status == "no") {
				$postStatus = "ignored";
			}
			//MANUKE
			$waktuMap = sprintf("%s (%s)", gmdate("i:s", $bm["length_total"]),gmdate("i:s", $bm["length_drain"]));
			$beatPM = $bm["bpm"];
			$bmSETid = $bm["beatmapset_id"];
			//KIRIM KE DISCORD
			$rankwebhook = $DiscordHook["ranked-map"];
			$json_data = json_encode(
			[
				// "username" => "Ranked Bot",
				"embeds" => [
					[
						"title" => cleanupBeatmapName(sprintf("%s - %s [%s]", $bm['artist'], $bm['title'], $bm['difficulty_name'])),
						"url" => "https://osu.ppy.sh/s/$bmSETid",
						"description" => "Status : $postStatus\nTime : $waktuMap\nBPM : $beatPM\n[Download](https://osu.datenshi.pw/d/$bmSETid)",
						"color" => hexdec( "3366ff" ),
						"footer" => [
							"text" => "This map was $postStatus by " . $_SESSION["username"] . "",
							"icon_url" => "https://a.datenshi.pw/" . $_SESSION["userid"] . ""
						],
						"thumbnail" => [
							"url" => "https://b.ppy.sh/thumb/$bmSETid.jpg"
						]
					]
				]
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$crot = curl_init( $rankwebhook );
			curl_setopt( $crot, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
			curl_setopt( $crot, CURLOPT_POST, 1);
			curl_setopt( $crot, CURLOPT_POSTFIELDS, $json_data);
			curl_setopt( $crot, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt( $crot, CURLOPT_HEADER, 0);
			curl_setopt( $crot, CURLOPT_RETURNTRANSFER, 1);

			$resp = curl_exec( $crot );
			curl_close( $crot );
			// END
			redirect("index.php?p=117&s=".$result);
		} catch (Exception $e) {
			redirect('index.php?p=117&e='.$e->getMessage());
		}
	}

	public static function RedirectRankBeatmap() {
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"]) || !isset($_POST["type"]) || empty($_POST["type"])) {
				throw new Exception("Invalid beatmap id or type");
			}
			if ($_POST["type"] == "bsid") {
				$bsid = htmlspecialchars($_POST["id"]);
			} else {
				$bsid = $GLOBALS["db"]->fetch("SELECT beatmapset_id FROM beatmaps WHERE beatmap_id = ? LIMIT 1", [$_POST["id"]]);
				if (!$bsid) {
					throw new Exception("Beatmap set not found in Datenshi's database. Please use beatmap set id or load at least one difficulty in game before trying to rank a beatmap by its id.");
				}
				$bsid = current($bsid);
			}
			redirect("index.php?p=124&bsid=".$bsid);
		} catch (Exception $e) {
			redirect('index.php?p=125&e='.$e->getMessage());
		}
	}

	public static function ClearHWIDMatches() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Invalid user ID");
			}
			$GLOBALS["db"]->execute("DELETE FROM hw_user WHERE user_id = ?", [$_GET["id"]]);
			rapLog(sprintf("has cleared %s's HWID matches.", getUserUsername($_GET["id"])));
			redirect('index.php?p=102&s=HWID matches cleared! Make sure to clear multiaccounts\' HWID too, or the user might get restricted for multiaccounting!');
		} catch (Exception $e) {
			redirect('index.php?p=102&e='.$e->getMessage());
		}
	}

	public static function TakeReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0) {
				throw new Exception("This report is closed");
			} else if ($status["assigned"] == $_SESSION["userid"]) {
				// Unassign
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Assign to current user
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = ? WHERE id = ? LIMIT 1", [$_SESSION["userid"], $_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Assignee changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function SolveUnsolveReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0 && $status["assigned"] != -1) {
				throw new Exception("This report is closed or it's marked as useless");
			}
			if ($status["assigned"] == -1) {
				// Unsolve
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Solve
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = -1 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Solved status changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function UselessUsefulReport() {
		try {
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Missing report id");
			}
			$status = $GLOBALS["db"]->fetch("SELECT assigned FROM reports WHERE id = ? LIMIT 1", [$_GET["id"]]);
			if (!$status) {
				throw new Exception("Invalid report id");
			}
			if ($status["assigned"] < 0 && $status["assigned"] != -2) {
				throw new Exception("This report is closed");
			}
			if ($status["assigned"] == -2) {
				// Useful (open)
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = 0 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			} else {
				// Useless
				$GLOBALS["db"]->execute("UPDATE reports SET assigned = -2 WHERE id = ? LIMIT 1", [$_GET["id"]]);
			}
			redirect("index.php?p=127&id=" . $_GET["id"] . "&s=Useful status changed!");
		} catch (Exception $e) {
			redirect("index.php?p=127&id=" . $_GET["id"] . "&e=" . $e->getMessage());
		}
	}

	public static function RestoreScoresSearchUser() {
		try {
			if (!isset($_POST["username"]) || empty($_POST["username"])) {
				throw new Exception("Missing username");
			}
			$userID = getUserID($_POST["username"]);
			if (!$userID) {
				throw new Exception("No such user");
			}
			redirect("index.php?p=134&id=" . $userID);
		} catch (Exception $e) {
			redirect("index.php?p=134&e=" . $e->getMessage());
		}
	}

	public static function RestoreScores() {
		try {
			if (!isset($_POST["userid"]) || empty($_POST["userid"]) || !isset($_POST["gm"]) || empty($_POST["gm"])) {
				throw new Exception("Missing required parameters");
			}

			$q = "SELECT * FROM scores_removed WHERE user_id = ?";
			$qp = [$_POST["userid"]];
			if ($_POST["gm"] > -1 && $_POST["gm"] <= 3) {
				$q .= " AND play_mode = ?";
				array_push($qp, $_POST["gm"]);
			}
			if (isset($_POST["startdate"]) && !empty($_POST["startdate"])) {
				$h = isset($_POST["starttime"]) && !empty($_POST["starttime"]) ? $_POST["starttime"] : "00:00";
				$startts = getTimestampFromStr("$_POST[startdate] $h");
				$q .= " AND time >= ?";
				array_push($qp, $startts);
			}
			if (isset($_POST["enddate"])  && !empty($_POST["enddate"])) {
				$h = isset($_POST["endtime"]) && !empty($_POST["endtime"]) ? $_POST["endtime"] : "00:00";
				$endts = getTimestampFromStr("$_POST[enddate] $h");
				$q .= " AND time <= ?";
				array_push($qp, $endts);
			}

			$scoresToRecover = $GLOBALS["db"]->fetchAll($q, $qp);
			foreach ($scoresToRecover as $lostScore) {
				$restore = false;
				if ($lostScore["completed"] == 3) {
					// Restore completed 3 scores only if they havent been replaced by better scores
					$betterScore = $GLOBALS["db"]->fetch("SELECT id FROM scores WHERE user_id = ? AND play_mode = ? AND beatmap_md5 = ? AND completed = 3 AND pp > ? LIMIT 1", [
						$lostScore["userid"],
						$lostScore["play_mode"],
						$lostScore["beatmap_md5"],
						$lostScore["pp"]
					]);
					$restore = !$betterScore;
				} else {
					// Restore all completed < 3 scores
					$restore = true;
				}
				if (!$restore) {
					echo "$lostScore[id] has a better score, not restoring<br>";
					continue;
				}
				$GLOBALS["db"]->execute("INSERT INTO scores SELECT * FROM scores_removed WHERE id = ? LIMIT 1", [$lostScore["id"]]);
				$GLOBALS["db"]->execute("DELETE FROM scores_removed WHERE id = ? LIMIT 1", [$lostScore["id"]]);
				echo "Restored $lostScore[id]<br>";
			}

			// redirect(index.php?p=134&id=" . $userID);
		} catch (Exception $e) {
			redirect("index.php?p=134&e=" . $e->getMessage());
		}
	}

	public static function BulkBan() {
		try {
			if (!isset($_POST["uid"]) || empty($_POST["uid"])) {
				throw new Exception("No user ids provided.");
			}
			$result = "";
			$errors = "";
			foreach ($_POST["uid"] as $uid) {
				$uid = (int)$uid;
				$user = $GLOBALS["db"]->fetch("SELECT privileges, username FROM users WHERE id = ? LIMIT 1", [$uid]);
				if (!$user) {
					$errors .= "$uid doesn't exist | ";
					continue;
				}
				if (($user["privileges"] & Privileges::AdminManageUsers) > 0) {
					$errors .= "No privileges to ban $uid. | ";
					continue;
				}
				$GLOBALS["db"]->execute("UPDATE users SET privileges = (privileges & ~3) WHERE id = ? LIMIT 1", [$uid]);
				if (isset($_POST["notes"]) && !empty($_POST["notes"])) {
					appendNotes($uid, $_POST["notes"]);
				}
				$result .= "$uid OK! | ";
				$result = trim($result, " | ");
				$errors = trim($errors, " | ");
				updateBanBancho($uid);
				rapLog(sprintf("has banned user %s", $user["username"]));
			}
			redirect("index.php?p=102&e=" . $errors . "&s=" . $result);
		} catch (Exception $e) {
			redirect("index.php?p=102&e=" . $e->getMessage());
		}
	}

	public static function DeleteUser() {
		try {
			if (!isset($_POST["id"]) || empty($_POST["id"])) {
				throw new Exception("No user ids provided.");
			}
			$user = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ? LIMIT 1", [$_POST["id"]]);
			if (!$user) {
				throw new Exception("No user with that id.");
			}

			// Kick from bancho
			echo '<pre>';
			echo "Kicking from bancho...\n";
			redisConnect();
			$GLOBALS["redis"]->publish("peppy:disconnect", json_encode([
				"userID" => intval($_POST["id"]),
				"reason" => "Your account has been deleted. Thank you for playing on Datenshi!"
			]));

			// Delete stuff...
			$uid = $_POST["id"];
			echo "Deleting avatar...   ";
			try {
				$avatar = dirname(dirname(dirname(__FILE__))).'/root/datenshi/avatarserver/avatars/'. $uid .'.png';
				if (file_exists($avatar)) {
					unlink($avatar);
					echo 'OK';
			} else {
					echo 'the user has no avatar';
			}
			} catch (Exception $e) {
				echo '<span style="color: orange;">WARNING: Could not delete avatar: ' . $e->getMessage() . '.</span>';
			}
			echo "\n";

			nuke("2fa", "user_id", $uid);
			nuke("2fa_confirmationa", "user_id", $uid);
			if ($GLOBALS["db"]->fetch("SELECT 1 FROM 2fa_telegram LIMIT 1")) {
				nuke("2fa_telegram", "user_id", $uid);
			}
			nuke("2fa_totp", "user_id", $uid);
			nukeExt(
				"anticheat_reports",
				"DELETE FROM anticheat_reports JOIN scores ON anticheat_reports.score_id = scores.id WHERE scores.user_id = ?",
				[$uid]
			);
			nuke("beatmaps_rating", "user_id", $uid);
			nuke("comments", "user_id", $uid);
			nuke("ip_user", "user_id", $uid);
			nukeExt(
				"osin_expires",
				"DELETE FROM osin_expires WHERE token IN (SELECT access_token FROM osin_access WHERE extra = ?)",
				[$uid]
			);
			//nuke("osin_access", "extra", $uid);
			//nuke("osin_authorize", "extra", $uid);
			//nukeExt(
			//	"osin_client",
			//	"DELETE FROM osin_client WHERE id IN (SELECT client_id FROM osin_client_user WHERE user = ?)",
			//	[$row["client_id"]]
			//);
			
			//nuke("osin_client_user", "user", $uid);

			// WHAT THE FUCK
			nuke("password_recovery", "u", $user["username"]);

			nuke("user_clans", "user", $uid);
			nuke("profile_backgrounds", "uid", $uid);
			nuke("rank_requests", "user_id", $uid);
			nuke("session_remember", "user_id", $uid);
			nukeExt(
				"reports",
				"DELETE FROM reports WHERE from_uid = ? OR to_uid = ?",
				[$uid, $uid]
			);
			//nuke("scores_removed", "userid", $uid);
			nuke("tokens", "user", $uid);
			nuke("identity_tokens", "user_id", $uid);
			nuke("users_achievements", "user_id", $uid);
			nuke("users_beatmap_playcount", "user_id", $uid);
			nukeExt(
				"users_relationships",
				"DELETE FROM users_relationships WHERE user1 = ? OR user2 = ?",
				[$uid, $uid]
			);
			nuke("user_badges", "user", $uid);
			nuke("hw_user", "user_id", $uid);
			nuke("scores_master", "user_id", $uid);
			nuke("user_config", "id", $uid);
			nuke("user_settings", "user_id", $uid);
			nuke("users_logs", "user", $uid);
			nuke("users", "id", $uid);
			// INI APAAN ANJING? ga jelas banget ripple stress
			// Lock account and reset email, password, stats, etc
			//echo "Generating random password\n";
			//$newPassword = password_hash(md5(randomString(64)), PASSWORD_DEFAULT);
			//$randomIdentifier = '';
			//$randomUsername = '';
			//echo "Generating account random identifier\n";
			//do {
			//	$randomIdentifier = randomString(20);
			//	$randomUsername = "DELETED$randomIdentifier";
			//} while ($GLOBALS["db"]->fetch("SELECT 1 FROM users WHERE username = ? LIMIT 1", [$randomUsername]));
			//echo "Account identifier set to $randomIdentifier\n";

			//echo "Locking user\n";
			//$GLOBALS["db"]->execute(
			//	"UPDATE users SET username = ?, username_safe = ?, privileges = 0, password_md5 = ?, salt = '', password_version = 2, email = 'deleted+$randomIdentifier@ripple.moe', register_datetime = 0, donor_expire = 0, ban_datetime = 0, aqn = 0, latest_activity = 0, silence_end = 0, silence_reason = '', flags = 0, notes = ? WHERE id = ? LIMIT 1",
			//	[
			//		$randomUsername,
			//		safeUsername($randomUsername),
			//		$newPassword,
			//		"-- This account and all its related data (but HWIDs) have been permanently deleted (automatic message by RAP).",
			//		$uid
			//	]
			//);

			//echo "Resetting stats\n";
			//$stats = ["ranked_score", "playcount", "total_score", "replays_watched", "playcount", "total_hits", "level", "avg_accuracy", "pp", "playtime"];
			//$modes = ["std", "taiko", "ctb", "mania"];
			//$nukeStats = "";
			//foreach ($modes as $mode) {
			//	if ($nukeStats) {
			//		$nukeStats .= ",";
			//	}
			//	$where = [];
			//	foreach ($stats as $stat) {
			//		$col = $stat . "_" . $mode;
			//		array_push($where, $col . " = DEFAULT(" . $col . ")");
			//	}
			//	$nukeStats .= join(", ", $where);
			//}
			//$q = $q = "UPDATE user_config SET username_aka = '', username = ?, user_color = 'black', user_style = '', country = 'XX', show_country = 1, safe_title = 0, userpage_content = '', play_style = 0, favourite_mode = 0, custom_badge_icon = '', custom_badge_name = '', show_custom_badge = 0, can_custom_badge = 1, $nukeStats WHERE id = ? LIMIT 1";
			//$GLOBALS["db"]->execute($q, [$randomUsername, $uid]);

			echo "Inserting rap log\n";
			rapLog(sprintf("has deleted user %s", $user["username"]));
			echo '<span style="color: green;">Account deleted successfully. Komm Süsser Tod.</span><hr><a href="index.php?p=102">Back to RAP</a>';
		} catch (Exception $e) {
			echo '<span style="color: red;">' . $e->getMessage() . '</span>';
		}
	}
	
	public static function AdminRegisterUser() {
		try {
			if (!every(['username','password','email'], function($k){return isset($_POST[$k]);}))
				throw new Exception("Incomplete body.");
			$isBot = (isset($_POST['botFlag']) && $_POST['botFlag']);
			// do isBot checkers
			if ($isBot) {
				if (!isset($_POST['botOwnerID']))
					throw new Exception('Bot Owner is not set.');
				if (!ctype_digit($_POST['botOwnerID']))
					throw new Exception('Bot Owner ID is invalid.');
				if (getUserPrivileges($_POST['botOwnerID']) & 3 != 3)
					throw new Exception('Bot Owner is not in good standing.');
			}
			
			$_POST['username'] = trim($_POST['username']);
			if (!((bool)preg_match('/^[A-Za-z0-9 _\[\]-]{2,15}$/', $_POST['username'])))
				throw new Exception("Bad username. (Bad format)");
			if (checkUsernameBlacklist(strtolower($_POST['username'])))
				throw new Exception("Bad username. (NO)");
			if (str_contains($_POST['username'], ' ')&&str_contains($_POST['username'], '_'))
				throw new Exception("Bad username. (Mixed space)");
			$safeUsername = str_replace(' ','_',trim(strtolower($_POST['username'])));
			if ($GLOBALS['db']->fetch('select 1 from users where username_safe = ?', [$safeUsername]))
				throw new Exception("Bad username. (Taken)");
			if ($isBot)
				if ($GLOBALS['db']->fetch('select 1 from users where email = ? and id != ?', [$_POST['email'], $_POST['botOwnerID']]))
					throw new Exception("Bad e-mail. (You can use the bot owner.)");
			else
				if ($GLOBALS['db']->fetch('select 1 from users where email = ?', [$_POST['email']]))
					throw new Exception("Bad e-mail.");
			$safePassword = password_hash(md5($_POST['password']), PASSWORD_BCRYPT);
			$GLOBALS['db']->execute('insert into users (username, username_safe, password_md5, salt, email, register_datetime, privileges, password_version) values (?, ?, ?, "", ?, ?, ?, 2)', [
				$_POST['username'], $safeUsername, $safePassword, $_POST['email'], time(),
				$isBot ? (Privileges::UserPublic | Privileges::UserBotFlag) : Privileges::UserPendingVerification
			]);
			redisConnect();
			$GLOBALS['redis']->incr('ripple:registered_users');
			$userID = $GLOBALS['db']->lastInsertId();
			$mstStatValues = [];
			for($i=0;$i<3;$i++){
				for($j=0;$j<4;$j++){
					$mstStatID = ($userID - 1) * 12 + $i * 4 + $j + 1;
					$mstStatValues[$i*4 + $j] = sprintf("(%d,%d,%d,%d)", $mstStatID, $userID, $i, $j);
					$mstStatRanks = [];
					for($k=0;$k<5;$k++)
						array_push($mstStatRanks, sprintf("(%d,%d,0)", $mstStatID, 8 - $k));
					$GLOBALS['db']->execute(sprintf('insert into `master_stat_ranks` (mst_stat_id, grade_level, grade_count) values %s', implode(',', $mstStatRanks)));
				}
			}
			$GLOBALS['db']->execute(sprintf('insert into `master_stats` (id, user_id, special_mode, game_mode) values %s', implode(',', $mstStatValues)));
			$result = sprintf("Registered %s successfully!", $_POST['username']);
			redirect(sprintf("index.php?p=102&s=%s", $result));
		} catch (Exception $e) {
			redirect(sprintf("index.php?p=147&e=%s", $e->getMessage()));
		}
	}
}
