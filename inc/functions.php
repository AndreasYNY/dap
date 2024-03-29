<?php
/*
 * Ripple functions file
 * include this to include the world
*/
// Include config file and db class
$df = dirname(__FILE__);
require_once $df.'/config.php';
require_once $df.'/db.php';
require_once $df.'/password_compat.php';
require_once $df.'/Do.php';
require_once $df.'/Print.php';
require_once $df.'/RememberCookieHandler.php';
require_once $df.'/PlayStyleEnum.php';
require_once $df.'/resize.php';
require_once $df.'/PrivilegesEnum.php';
// Composer
require_once $df.'/../vendor/autoload.php';
// Helpers
require_once $df.'/helpers/PasswordHelper.php';
require_once $df.'/helpers/UsernameHelper.php';
require_once $df.'/helpers/URL.php';
require_once $df.'/helpers/APITokens.php';
//require_once $df.'/curlcuy.php';
// controller system v2
require_once $df.'/pages/Login.php';
require_once $df.'/pages/Beatmaps.php';
$pages = [
	new Login(),
	new Beatmaps(),
];
//CURL
use Curl\Curl;
// Set timezone to UTC
date_default_timezone_set('Asia/Makassar');
// Connect to MySQL Database
$GLOBALS['db'] = new DBPDO();
if (defined('DATABASE_DEV_NAME') && defined('DATABASE_DEV_PASS') && defined('DATABASE_DEV_NAME')) {
	$GLOBALS['db_dev'] = new DBPDO(true);
} else {
	$GLOBALS['db_dev'] = $GLOBALS['db'];
}
// Birthday
global $isBday;
$isBday = date("dm") == "1104";
/****************************************
 **			GENERAL FUNCTIONS 		   **
 ****************************************/
function redisConnect() {
	if (!isset($_GLOBALS["redis"])) {
		global $redisConfig;
		$GLOBALS["redis"] = new Predis\Client($redisConfig);
	}
}
/*
 * redirect
 * Redirects to a URL.
 *
 * @param (string) ($url) Destination URL.
*/
function redirect($url) {
	header('Location: '.$url);
	session_commit();
	exit();
}

/*
 * compareArrayMulti
 * compares two array's keys with specific formatting
 *
 * @param (array) ($a1) left  hand side
 * @param (array) ($a2) right hand side
 * @param (array) ($keys) array keys to compare on
 * @param (string) ($fmt) i/d/f representing type of respective array element
 * @param (string) ($cmp) </> representing comparison operator to check
 *
 * the value will keep going when there's an equality between two values
 */
function compareArrayMulti($a1, $a2, $keys, $fmt, $cmp) {
  $tc = function(&$v, $k){
    switch($k) {
    case 'i':
    case 'd':
      settype($v, 'int'); break;
    case 'f':
      settype($v, 'float'); break;
    }
  };
  $r = false;
  $c = true;
  $i = 0;
  while($c && ((!$r) && ($i < strlen($fmt)))) {
    $v1 = $a1[ $keys[$i] ];
    $v2 = $a2[ $keys[$i] ];
    $tc($v1, $fmt[$i]);
    $tc($v2, $fmt[$i]);
    $c = $c && ( $v1 == $v2 );
    switch($cmp[$i]){
    case '<':
      $r = $r || ( $v1 < $v2 );
      break;
    case '>':
      $r = $r || ( $v1 > $v2 );
      break;
    }
    $i += 1;
  }
  return $r;
}

function some($a, $fn) {
	foreach($a as $b) {
		if($fn($b)) return true;
	}
	return false;
}

function every($a, $fn) {
	foreach($a as $b) {
		if(!$fn($b)) return false;
	}
	return true;
}

/*
 * outputVariable
 * Output $v variable to $fn file
 * Only for debugging purposes
 *
 * @param (string) ($fn) Output file name
 * @param ($v) Variable to output
*/
function outputVariable($v, $fn = "/tmp/ainu.txt") {
	file_put_contents($fn, var_export($v, true), FILE_APPEND);
}
/*
 * randomString
 * Generate a random string.
 * Used to get screenshot id in osu-screenshot.php
 *
 * @param (int) ($l) Length of the generated string
 * @return (string) Generated string
*/
function randomString($l, $c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
	$res = '';
	srand((float) microtime() * 1000000);
	for ($i = 0; $i < $l; $i++) {
		$res .= $c[rand() % strlen($c)];
	}
	return $res;
}
function getIP() {
	global $ipEnv;
	return getenv($ipEnv); // Add getenv('HTTP_FORWARDED_FOR')?: before getenv if you are using a dumb proxy. Meaning that if you try to get the user's IP with REMOTE_ADDR, it returns 127.0.0.1 or keeps saying the same IP, always.
	// NEVER add getenv('HTTP_FORWARDED_FOR') if you're not behind a proxy.
	// It can easily be spoofed.

}
/****************************************
 **		HTML/PAGES   FUNCTIONS 		   **
 ****************************************/
/*
 * setTitle
 * sets the title of the current $p page.
 *
 * @param (int) ($p) page ID.
*/
function setTitle($p) {
	if (isset($_COOKIE['st']) && $_COOKIE['st'] == 1) {
		// Safe title, so Peppy doesn't know we are browsing Ainu
		return '<title>Google</title>';
	} else {
		$namesAinu = [
			1 =>   'Private osu! server',
			3 =>   'Register',
			4 =>   'User CP',
			5 =>   'Change avatar',
			6 =>   'Edit user settings',
			7 =>   'Change password',
			8 =>   'Edit userpage',
			18 =>  'Recover your password',
			21 =>  'About',
			23 =>  'Rules',
			26 =>  'Friends',
			41 =>  'Elmo! Stop!',
			'u' => 'Userpage',
		];
		$namesAAP = [
			99 =>  "You've been tracked",
			100 => 'Dashboard',
			101 => 'System settings',
			102 => 'Users',
			103 => 'Edit user',
			104 => 'Change identity',
			108 => 'Badges',
			109 => 'Edit Badge',
			110 => 'Edit user badges',
			111 => 'Bancho settings',
			116 => 'Admin Logs',
			117 => 'Rank requests',
			118 => 'Privilege Groups',
			119 => 'Edit privilege group',
			120 => 'View users in privilege group',
			121 => 'Give Donor',
			122 => 'Rollback user',
			123 => 'Wipe user',
			124 => 'Rank beatmap',
			125 => 'Rank beatmap manually',
			126 => 'Reports',
			127 => 'View report',
			128 => 'Cakes',
			129 => 'View cake',
			130 => 'Cake recipes',
			131 => 'View cake recipe',
			132 => 'View anticheat reports',
			133 => 'View anticheat report',
			134 => 'Restore scores',
			135 => 'Search users by IP',
			136 => 'Search users by IP - Results',
			137 => 'Top Scores',
			138 => 'Top Scores Results',
			139 => 'Edit Whitelist IP',
			140 => 'BAT Give Reason',
			141 => 'Auto Rank Listing',
			142 => 'Challenge Listing',
			143 => 'Leaderboard Configuration',
      		144 => 'Challenge Configuration',
     	 	145 => 'Leaderboard View',
      		146 => 'PP Limit Configuration',
			147 => 'Admin Register User',
			148 => 'Admin Input PP Whitelist'
		];
		if (isset($namesAinu[$p])) {
			return __maketitle('Datenshi', $namesAinu[$p]);
		} else if (isset($namesAAP[$p])) {
			return __maketitle('DAP', $namesAAP[$p]);
		} else {
			return __maketitle('Datenshi', '404');
		}
	}
}
function __maketitle($b1, $b2) {
	return "<title>$b1 - $b2</title>";
}
/*
 * printPage
 * Prints the content of a page.
 * For protected pages (logged in only pages), call first sessionCheck() and then print the page.
 * For guest pages (logged out only pages), call first checkLoggedIn() and if false print the page.
 *
 * @param (int) ($p) page ID.
*/
function printPage($p) {
	$exceptions = ['Permission failure.', 'Only administrators are allowed to see that documentation file.', "<div style='font-size: 40pt;'>ATTEMPTED USER ACCOUNT VIOLATION DETECTED</div>
			<p>We detected an attempt to violate an user account. If you didn't do this on purpose, you can ignore this message and login into your account normally. However if you changed your cookies on purpose and you were trying to access another user's account, don't do that.</p>
			<p>By the way, the attacked user is aware that you tried to get access to their account, and we removed all permanent login hashes. We wish you good luck in even finding the new 's' cookie for that user.</p>
			<p>Don't even try.</p>", 9001 => "don't even try"];
	if (!isset($_GET['u']) || empty($_GET['u'])) {
		// Standard page
		switch ($p) {
				// Error page

			case 99:
				if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
					$e = $_GET['e'];
				} elseif (isset($_GET['e']) && strlen($_GET['e']) > 12 && substr($_GET['e'], 0, 12) == 'do_missing__') {
					$s = substr($_GET['e'], 12);
					if (preg_match('/^[a-z0-9-]*$/i', $s) === 1) {
						P::ExceptionMessage('Missing parameter while trying to do action: '.$s);
						$e = -1;
					} else {
						$e = '9001';
					}
				} else {
					$e = '9001';
				}
				if ($e != -1) {
					P::ExceptionMessage($exceptions[$e]);
				}
			break;
				// Home

			case 1:
				P::HomePage();
			break;

				// Admin panel (> 100 pages are admin ones)
			case 100:
				sessionCheckAdmin();
				P::AdminDashboard();
			break;
				// Admin panel - System settings

			case 101:
				sessionCheckAdmin(Privileges::AdminManageSettings);
				P::AdminSystemSettings();
			break;
				// Admin panel - Users

			case 102:
				sessionCheckAdmin(Privileges::AdminSilenceUsers);
				P::AdminUsers();
			break;
				// Admin panel - Edit user

			case 103:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminEditUser();
			break;
				// Admin panel - Change identity

			case 104:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminChangeIdentity();
			break;
				// Admin panel - Badges

			case 108:
				sessionCheckAdmin(Privileges::AdminManageBadges);
				P::AdminBadges();
			break;
				// Admin panel - Edit badge

			case 109:
				sessionCheckAdmin(Privileges::AdminManageBadges);
				P::AdminEditBadge();
			break;
				// Admin panel - Edit uesr badges

			case 110:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminEditUserBadges();
			break;
				// Admin panel - System settings

			case 111:
				sessionCheckAdmin(Privileges::AdminManageSettings);
				P::AdminBanchoSettings();
			break;

			// Admin panel - Admin logs
			case 116:
				sessionCheckAdmin(Privileges::AdminViewRAPLogs);
				P::AdminLog();
			break;

			// Admin panel - Beatmap rank requests
			case 117:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::AdminRankRequests();
			break;

			// Admin panel - Privileges Groups
			case 118:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::AdminPrivilegesGroupsMain();
			break;

			// Admin panel - Privileges Groups
			case 119:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::AdminEditPrivilegesGroups();
			break;

			// Admin panel - Show users in group
			case 120:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::AdminShowUsersInPrivilegeGroup();
			break;

			// Admin panel - Give donor to user
			case 121:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminGiveDonor();
			break;

			// Admin panel - Rollback User
			case 122:
				sessionCheckAdmin(Privileges::AdminWipeUsers);
				P::AdminRollback();
			break;

			// Admin panel - Wipe User
			case 123:
				sessionCheckAdmin(Privileges::AdminWipeUsers);
				P::AdminWipe();
			break;

			// Admin panel - Rank beatmap
			case 124:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::AdminRankBeatmap();
			break;

			// Admin panel - Rank beatmap manually
			case 125:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::AdminRankBeatmapManually();
			break;

			// Admin panel - Reports
			case 126:
				sessionCheckAdmin(Privileges::AdminManageReports);
				P::AdminViewReports();
			break;

			// Admin panel - View report
			case 127:
				sessionCheckAdmin(Privileges::AdminManageReports);
				P::AdminViewReport();
			break;

			// Admin panel - View anticheat reports
			case 132:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminViewAnticheatReports();
			break;

			// Admin panel - View anticheat report
			case 133:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminViewAnticheatReport();
			break;

			// Admin panel - Restore scores
			case 134:
				sessionCheckAdmin(Privileges::AdminWipeUsers);
				P::AdminRestoreScores();
			break;

			// Admin panel - Search users by IP
			case 135:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminSearchUserByIP();
			break;

			// Admin panel - Search users by IP - Results
			case 136:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminSearchUserByIPResults();
			break;

			// Admin panel - Top scores
			case 137:
				sessionCheckAdmin(Privileges::AdminViewTopScores);
				P::AdminTopScores();
			break;

			// Admin panel - Top scores results
			case 138:
				sessionCheckAdmin(Privileges::AdminViewTopScores);
				P::AdminTopScoresResults();
			break;

			// Admin panel - QuickEditIP
			case 139:
				sessionCheckAdmin(Privileges::AdminManageUsers);
				P::AdminWhitelistIP();
			break;

			// BAT Give Reason
			case 140:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::BATGiveReason();
			break;
			
			case 141:
				sessionCheckAdmin(Privileges::AdminManageBeatmaps);
				P::BATViewAutorank();
			break;

      		// ch. listing/leaderboard config/ch. config
			case 142:
				sessionCheckAdmin();
				P::BATViewChallenges();
			break;

			case 143:
				sessionCheckAdmin(Privileges::AdminManageSettings);
				P::AdminBeatmapLeaderboardEdit();
			break;

			case 144:
        	// who can manage check DAP can VIEW
        	// who can manage server can EDIT
			// who can't pull who tao can't TOUCH
				sessionCheckAdmin();
				P::BATEditChallenge();
			break;
      
      		case 145:
        		sessionCheckAdmin();
        		P::BATBeatmapLeaderboardView();
        	break;

      		case 146:
        		sessionCheckAdmin(Privileges::AdminSupportWhitelist);
        		P::AdminEditPPWhitelist();
        	break;

      		case 147:
        		sessionCheckAdmin(Privileges::AdminManageBetaKeys);
        		P::AdminRegisterUser();
        	break;

	  		case 148:
				sessionCheckAdmin(Privileges::AdminSupportWhitelist);
				P::AdminPageInputWhitelist();
			break;

			case 149:
				sessionCheckAdmin(Privileges::AdminManagePrivileges);
				P::ManageUserPrivilegesPage();
			break;
			// 404 page
			default:
				define('NotFound', '<br><h1>404</h1><p>Page not found. Meh.</p>');
				if ($p < 100)
					echo NotFound;
				else {
						echo '
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div id="content">
					' . NotFound . '
                    </div>
                </div>
            </div>
        </div>';
				}
			break;
		}
	} else {
		if (hasPrivilege(Privileges::AdminAccessRAP)) {
			// Userpage
			P::UserPage($_GET["u"], isset($_GET['m']) ? $_GET['m'] : -1);
		} else {
			echo "how did i get here?";
		}
	}
}
/*
 * printNavbar
 * Prints the navbar.
 * To print tabs only for guests (not logged in), do
 *	if (!checkLoggedIn()) echo('stuff');
 *
 * To print tabs only for logged in users, do
 *	if (checkLoggedIn()) echo('stuff');
 *
 * To print tabs for both guests and logged in users, do
 *	echo('stuff');
*/
function printNavbar() {
	global $discordConfig;
	echo '<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
				<div class="container">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>';
						if (isset($_GET['p']) && $_GET['p'] >= 100) {
						echo '<button type="button" class="navbar-toggle with-icon" data-toggle="collapse" data-target="#sidebar-wrapper">
								<span class="glyphicon glyphicon-briefcase">
							</button>';
						}
						global $isBday;
						echo $isBday ? '<a class="navbar-brand" href="index.php"><i class="fa fa-birthday-cake"></i><img src="https://cdn.datenshi.pw/static/logos/text-white.png" style="display: inline; padding-left: 10px;"></a>' : '<a class="navbar-brand" href="index.php"><img src="https://cdn.datenshi.pw/static/logos/text-white.png"></a>';
					echo '</div>
					<div class="navbar-collapse collapse">';
	// Left elements
	// Not logged left elements
	echo '<ul class="nav navbar-nav navbar-left">';
	if (!checkLoggedIn()) {
		echo '<li><a href="index.php?p=2"><i class="fa fa-sign-in-alt"></i>	Login</a></li>';
	}
	// Logged in left elements
	if (checkLoggedIn()) {
		// Just an easter egg that you'll probably never notice, unless you do it on purpose.
		if (hasPrivilege(Privileges::AdminAccessRAP)) {
			echo '<li><a href="index.php?p=100"><i class="fa fa-cog"></i>	<b>Admin Panel</b></a></li>';
			//echo '<li><a href="/phpmyadmin"><i class="fa fa-database"></i>	<b>phpMyAdmin</b></a></li>';
		}
	}
	// Right elements
	echo '</ul><ul class="nav navbar-nav navbar-right">';
	echo '<li><input type="text" class="form-control" name="query" id="query" placeholder="Search users..."></li>';
	// Logged in right elements
	if (checkLoggedIn()) {
		global $URL;
		echo '<li class="dropdown">
					<a data-toggle="dropdown"><img src="'.URL::Avatar().'/'.getUserID($_SESSION['username']).'" height="22" width="22" />	<b>'.$_SESSION['username'].'</b><span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li class="dropdown-submenu"><a href="index.php?u='.getUserID($_SESSION['username']).'"><i class="fa fa-user"></i> My profile</a></li>
						<li class="dropdown-submenu"><a href="submit.php?action=logout&csrf='.csrfToken().'"><i class="fa fa-sign-out-alt"></i>	Logout</a></li>
					</ul>
				</li>';
	}
	// Navbar end
	echo '</ul></div></div></nav>';
}
/*
 * printAdminSidebar
 * Prints the admin left sidebar
*/
function printAdminSidebar() {
	echo '<div id="sidebar-wrapper" class="collapse" aria-expanded="false">
					<ul class="sidebar-nav">
						<li class="sidebar-brand">
							<a href="#"><b>D</b>atenshi <b>A</b>dmin <b>P</b>anel</a>
						</li>
						<li><a href="index.php?p=100"><i class="fa fa-tachometer-alt"></i>	Dashboard</a></li>';

						if (hasPrivilege(Privileges::AdminManageSettings)) {
							echo '<li><a href="index.php?p=101"><i class="fa fa-cog"></i>	System settings</a></li>
							<li><a href="index.php?p=111"><i class="fa fa-server"></i>	Bancho settings</a></li>';
						}

						//if (hasPrivilege(Privileges::AdminCaker)) {
						//	echo '<li><a href="index.php?p=139"><i class="fa fa-boxes"></i>	S3 Replays Buckets</a></li>';
						//}

						if (hasPrivilege(Privileges::AdminSilenceUsers)) {
							echo '<li><a href="index.php?p=102"><i class="fa fa-user"></i>	Users</a></li>';
						}

						if (hasPrivilege(Privileges::AdminManageUsers)) {
							echo '<li><a href="index.php?p=132"><i class="fa fa-fire"></i>	Anticheat reports</a></li>';
						}
						
						if (hasPrivilege(Privileges::AdminSupportWhitelist)) {
							echo '<li><a href="index.php?p=148"><i class="fa fa-user"></i> Whitelist PP</a></li>';
						}
						if (hasPrivilege(Privileges::AdminWipeUsers)) {
							echo '<li><a href="index.php?p=134"><i class="fa fa-undo"></i>	Restore scores</a></li>';
						}

						if (hasPrivilege(Privileges::AdminManageReports))
							echo '<li><a href="index.php?p=126"><i class="fa fa-flag"></i>	Reports</a></li>';

						if (hasPrivilege(Privileges::AdminManagePrivileges))
							echo '<li><a href="index.php?p=118"><i class="fa fa-layer-group"></i>	Privilege Groups</a></li>';

						if (hasPrivilege(Privileges::AdminManageBadges))
							echo '<li><a href="index.php?p=108"><i class="fa fa-certificate"></i>	Badges</a></li>';

						if (hasPrivilege(Privileges::AdminManageBeatmaps)) {
							echo '<li><a href="index.php?p=117"><i class="fa fa-music"></i>	Rank requests</a></li>';
							echo '<li><a href="index.php?p=125"><i class="fa fa-level-up-alt"></i>	Rank beatmap manually</a></li>';
							if (hasPrivilege(Privileges::AdminManageAutoRank)) {
								echo '<li><a href="index.php?p=141"><i class="fa fa-level-up-alt"></i>	Autorank queue</a></li>';
							}
						}

						if (hasPrivilege(Privileges::AdminViewTopScores))
							echo '<li><a href="index.php?p=137"><i class="fa fa-fighter-jet"></i>	Top scores</a></li>';
            				echo '<li><a href="index.php?p=142"><i class="fa fa-fighter-jet"></i>	View Challenges</a></li>';

						if (hasPrivilege(Privileges::AdminViewRAPLogs))
							echo '<li class="animated infinite pulse"><a href="index.php?p=116"><i class="fa fa-calendar"></i>	Admin log&nbsp;&nbsp;&nbsp;<div class="label label-primary">Free botnets</div></a></li>';
							echo "</ul></div>";
}
/*
 * printAdminPanel
 * Prints an admin dashboard panel, used to show
 * statistics (like total plays, beta keys left and stuff)
 *
 * @c (string) panel color, you can use standard bootstrap colors or custom ones (add them in style.css)
 * @i (string) font awesome icon of that panel. Recommended doing fa-5x (Eg: fa fa-gamepad fa-5x)
 * @bt (string) big text, usually the value
 * @st (string) small text, usually the name of that stat
*/
function printAdminPanel($c, $i, $bt, $st, $tt="") {
	echo '<div class="col-lg-3 col-md-6">
			<div class="panel panel-'.$c.'">
			<div class="panel-heading">
			<div class="row">
			<div class="col-xs-3"><i class="'.$i.'"></i></div>
			<div class="col-xs-9 text-right">
				<div title="'.$tt.'" class="huge">'.$bt.'</div>
				<div>'.$st.'</div>
			</div></div></div></div></div>';
}

function htmlTag($tag, $content, $options=[], $echo=true) {
  $opt_str = "";
  $body = "";
  if(is_array($options))
    foreach($options as $k=>$v)
      $opt_str .= sprintf(' %s="%s"', $k, $v);
  if($echo) {
    echo sprintf('<%1$s%2$s>', $tag, $opt_str);
    if(is_string($content))
      $body = $content;
    elseif(is_callable($content))
      $body = $content();
    if(!is_null($body))
      echo $body;
    echo sprintf('</%1$s>', $tag);
  } else {
    $ret = '';
    $ret .= sprintf('<%1$s%2$s>', $tag, $opt_str);
    if(is_string($content))
      $body = $content;
    elseif(is_callable($content))
      $body = $content();
    if((bool)$body)
      $ret .= $body;
    $ret .= sprintf('</%1$s>', $tag);
    return $ret;
  }
};

function reAssoc($array, $keyfunc){
  $keys = array_map($keyfunc, $array);
  return array_combine($keys, $array);
};

/*
 * getUserCountry
 * Does a call to ip.zxq.co to get the user's IP address.
 *
 * @returns (string) A 2-character string containing the user's country.
*/
function getUserCountry() {
	$ip = getIP();
	if (!$ip || $ip == '127.0.0.1') {
		return 'XX'; // Return XX if $ip isn't valid.

	}
	// otherwise, retrieve the contents from ip.zxq.co's API
	$data = get_contents_http("http://ip.zxq.co/$ip/country");
	// And return the country. If it's set, that is.
	return strlen($data) == 2 ? $data : 'XX';
}
// updateUserCountry updates the user's country in the database with the country they
// are currently connecting from.
function updateUserCountry($u, $field = 'username') {
	$c = getUserCountry();
	if ($c == 'XX')
		return;
	$GLOBALS['db']->execute("UPDATE user_config SET country = ? WHERE $field = ?", [$c, $u]);
}
function countryCodeToReadable($cc) {
	require_once dirname(__FILE__).'/countryCodesReadable.php';

	return isset($c[$cc]) ? $c[$cc] : 'unknown country';
}
/*
 * getAllowedUsers()
 * Get an associative array, saying whether a user is banned or not on Ainu.
 *
 * @returns (array) see above.

function getAllowedUsers($by = 'username') {
	// get all the allowed users in Ainu
	$allowedUsersRaw = $GLOBALS['db']->fetchAll('SELECT '.$by.', allowed FROM users');
	// Future array containing all the allowed users.
	$allowedUsers = [];
	// Fill up the $allowedUsers array.
	foreach ($allowedUsersRaw as $u) {
		$allowedUsers[$u[$by]] = ($u['allowed'] == '1' ? true : false);
	}
	// Free up some space in the ram by deleting the data in $allowedUsersRaw.
	unset($allowedUsersRaw);

	return $allowedUsers;
}*/
/****************************************
 **	 LOGIN/LOGOUT/SESSION FUNCTIONS	   **
 ****************************************/
/*
 * startSessionIfNotStarted
 * Starts a session only if not started yet.
*/
function startSessionIfNotStarted() {
	if (session_status() == PHP_SESSION_NONE)
		session_start();
	if (isset($_SESSION['username']) && !isset($_SESSION['userid']))
		$_SESSION['userid'] = getUserID($_SESSION['username']);
}
/*
 * sessionCheck
 * Check if we are logged in, otherwise go to login page.
 * Used for logged-in only pages
*/
function sessionCheck() {
	try {
		// Start session
		startSessionIfNotStarted();
		// Check if we are logged in
		if (!isset($_SESSION["username"])) {
			unset($_SESSION['redirpage']);
			$_SESSION['redirpage'] = $_SERVER['REQUEST_URI'];
			throw new Exception('You are not logged in.');
		}
		// Check if we've changed our password
		if ($_SESSION['passwordChanged']) {
			// Update our session password so we don't get kicked
			$_SESSION['password'] = current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username']));
			// Reset passwordChanged
			$_SESSION['passwordChanged'] = false;
		}
		// Check if our password is still valid
		if (current($GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username = ?', $_SESSION['username'])) != $_SESSION['password']) {
			throw new Exception('Session expired. Please login again.');
		}
		/* Check if we aren't banned (OLD)
		if (current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE username = ?', $_SESSION['username'])) == 0) {
			throw new Exception('You are banned.');
		} */
		// Ban check (NEW)
		if (!hasPrivilege(Privileges::UserNormal)) {
			throw new Exception('You are banned.');
		}
		// Set Y cookie
		setYCookie($_SESSION["userid"]);
		// Everything is ok, go on

	}
	catch(Exception $e) {
		addError($e->getMessage());
		// Destroy session if it still exists
		D::Logout();
		// Return to login page
		redirect('index.php?p=2');
	}
}
/*
 * sessionCheckAdmin
 * Check if we are logged in, and we are admin.
 * Used for admin pages (like admin cp)
 * Call this function instead of sessionCheck();
*/
function sessionCheckAdmin($privilege = -1, $e = 0) {
	sessionCheck();
	try {
		// Make sure the user can access RAP and is not banned/restricted
		if (!hasPrivilege(Privileges::AdminAccessRAP) || !hasPrivilege(Privileges::UserPublic) || !hasPrivilege(Privileges::UserNormal)) {
			throw new Exception;
		}

		if ($privilege > -1 && !hasPrivilege($privilege)) {
			throw new Exception;
		}
		return true;
	} catch (Exception $meme) {
		redirect('index.php?p=99&e='.$e);
		return false;
	}
}
/*
 * updateLatestActivity
 * Updates the latest_activity column for $u user
 *
 * @param ($u) (string) User ID
*/
function updateLatestActivity($u) {
	$GLOBALS['db']->execute('UPDATE users SET latest_activity = ? WHERE id = ?', [time(), $u]);
}
/*
 * updateSafeTitle
 * Updates the st cookie, if 1 title is "Google" instead
 * of Ainu - pagename, so Peppy doesn't know that
 * we are browsing Ainu
*/
function updateSafeTitle() {
	$safeTitle = $GLOBALS['db']->fetch('SELECT safe_title FROM user_config WHERE username = ?', $_SESSION['username']);
	if(!$safeTitle) return;
	setcookie('st', current($safeTitle));
}
/*
 * timeDifference
 * Returns a string with difference from $t1 and $t2
 *
 * @param (int) ($t1) Current time. Usually time()
 * @param (int) ($t2) Event time.
 * @param (bool) ($ago) Output "ago" after time difference
 * @return (string) A string in "x minutes/hours/days (ago)" format
*/
function timeDifference($t1, $t2, $ago = true, $leastText = "Right Now") {
	// Calculate difference in seconds
	// abs and +1 should fix memes
	$d = abs($t1 - $t2 + 1);
	switch ($d) {
		// Right now
		default:
			return $leastText;
		break;

		// 1 year or more
		case $d >= 31556926:
			$n = round($d / 31556926);
			$i = 'year';
		break;

		// 1 month or more
		case $d >= 2629743 && $d < 31556926:
			$n = round($d / 2629743);
			$i = 'month';
		break;

		// 1 day or more
		case $d >= 86400 && $d < 2629743:
			$n = round($d / 86400);
			$i = 'day';
		break;

		// 1 hour or more
		case $d >= 3600 && $d < 86400:
			$n = round($d / 3600);
			$i = 'hour';
		break;

		// 1 minute or more
		case $d >= 60 && $d < 3600:
			$n = round($d / 60);
			$i = 'minute';
		break;
	}

	// Plural, ago and more
	$s = $n > 1 ? 's' : '';
	$a = $ago ? 'ago' : '';

	return $n.' '.$i.$s.' '.$a;
}
$checkLoggedInCache = -100;
/*
 * checkLoggedIn
 * Similar to sessionCheck(), but let the user choose what to do if logged in or not
 *
 * @return (bool) true: logged in / false: not logged in
*/
function checkLoggedIn() {
	global $checkLoggedInCache;
	// Start session
	startSessionIfNotStarted();
	if ($checkLoggedInCache !== -100) {
		return $checkLoggedInCache;
	}
	// Check if we are logged in
	if (!isset($_SESSION['userid'])) {
		$checkLoggedInCache = false;
		return false;
	}
	// Check if our password is still valid
	if ($GLOBALS['db']->fetch('SELECT password FROM users WHERE username = ?', $_SESSION['username']) == $_SESSION['password']) {
		$checkLoggedInCache = false;

		return false;
	}
	// Check if we aren't banned
	//if ($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE username = ?', $_SESSION['username']) == 0) {
	if (!hasPrivilege(Privileges::UserNormal)) {
		$checkLoggedInCache = false;

		return false;
	}
	// Everything is ok, go on
	$checkLoggedInCache = true;

	return true;
}
/*
 * getUserRank
 * Gets the rank of the $u user
 *
 * @return (int) rank

function getUserRank($u) {
	return current($GLOBALS['db']->fetch('SELECT rank FROM users WHERE username = ?', $u));
}*/
function checkWebsiteMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkGameMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkBanchoMaintenance() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'")) == 0) {
		return false;
	} else {
		return true;
	}
}
function checkRegistrationsEnabled() {
	if (current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'")) == 0) {
		return false;
	} else {
		return true;
	}
}
// ******** GET USER ID/USERNAME FUNCTIONS *********
$cachedID = false;
/*
 * getUserID
 * Get the user ID of the $u user
 *
 * @param (string) ($u) Username
 * @return (string) user ID of $u
*/
function getUserID($u) {
	global $cachedID;
	if (isset($cachedID[$u])) {
		return $cachedID[$u];
	}
	$ID = $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $u);
	if ($ID) {
		$cachedID[$u] = current($ID);
	} else {
		// ID not set, maybe invalid player. Return 0.
		$cachedID[$u] = 0;
	}

	return $cachedID[$u];
}
/*
 * getUserUsername
 * Get the username for $uid user
 *
 * @param (int) ($uid) user ID
 * @return (string) username
*/
function getUserUsername($uid) {
	$username = $GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ? LIMIT 1', $uid);
	if ($username) {
		return current($username);
	} else {
		return 'unknown';
	}
}
/*
 * getPlaymodeText
 * Returns a text representation of a playmode integer.
 *
 * @param (int) ($playModeInt) an integer from 0 to 3 (inclusive) stating the play mode.
 * @param (bool) ($readable) set to false for returning values to be inserted into the db. set to true for having something human readable (osu!standard / Taiko...)
*/
function getPlaymodeText($playModeInt, $readable = false) {
	switch ($playModeInt) {
		case 1:
			return $readable ? 'Taiko' : 'taiko';
		break;
		case 2:
			return $readable ? 'Catch the Beat' : 'ctb';
		break;
		case 3:
			return $readable ? 'osu!mania' : 'mania';
		break;
			// Protection against memes from the users

		default:
			return $readable ? 'osu!standard' : 'std';
		break;
	}
}
/*
 * getScoreMods
 * Gets the mods for the $m mod flag
 *
 * @param (int) ($m) Mod flag
 * @returns (string) Eg: "+ HD, HR"
*/
function getScoreMods($m,$f=false) {
	require_once dirname(__FILE__).'/ModsEnum.php';
  $s = [];
	if ($m & ModsEnum::NoFail) array_push($s,'NF');
	if ($m & ModsEnum::Easy) array_push($s,$f ? 'EM' : 'EZ');
	if ($m & ModsEnum::NoVideo) array_push($s,'TD');
	if ($m & ModsEnum::Hidden) array_push($s,'HD');
	if ($m & ModsEnum::HardRock) array_push($s,'HR');
	if ($m & ModsEnum::Perfect) array_push($s,$f ? 'AP' : 'PF');
	elseif ($m & ModsEnum::SuddenDeath) array_push($s,'SD');
	if ($m & ModsEnum::Nightcore) array_push($s,'NC');
	elseif ($m & ModsEnum::DoubleTime) array_push($s,'DT');
	if ($m & ModsEnum::Relax) array_push($s,$f ? 'RL' : 'RX');
	if ($m & ModsEnum::HalfTime) array_push($s,'HT');
	if ($m & ModsEnum::Flashlight) array_push($s,'FL');
	if ($m & ModsEnum::Autoplay) array_push($s,'Auto');
	if ($m & ModsEnum::SpunOut) array_push($s,'SO');
	if ($m & ModsEnum::Relax2) array_push($s,$f ? 'ATP' : 'AP');
	if ($m & ModsEnum::Target) array_push($s,'TRG');
	if ($m & ModsEnum::Key4) array_push($s,'4K');
	if ($m & ModsEnum::Key5) array_push($s,'5K');
	if ($m & ModsEnum::Key6) array_push($s,'6K');
	if ($m & ModsEnum::Key7) array_push($s,'7K');
	if ($m & ModsEnum::Key8) array_push($s,'8K');
	if ($m & ModsEnum::keyMod) {}
	if ($m & ModsEnum::FadeIn) array_push($s,$f ? 'SUD' : 'FI');
	if ($m & ModsEnum::Random) array_push($s,$f ? 'RAN' : 'RD');
	if ($m & ModsEnum::LastMod) array_push($s,'CIN');
	if ($m & ModsEnum::Key9) array_push($s,'9K');
	if ($m & ModsEnum::Key10) array_push($s,'10K');
	if ($m & ModsEnum::Key1) array_push($s,'1K');
	if ($m & ModsEnum::Key3) array_push($s,'3K');
	if ($m & ModsEnum::Key2) array_push($s,'2K');
  if ($m & ModsEnum::Mirror) array_push($s,'MIR');
  if(count($s) <= 0) array_push($s,'NM');
  if ($m & ModsEnum::ScoreV2) array_push($s,'V2');
  return implode(', ', $s);
}
/*
 * calculateAccuracy
 * Calculates the accuracy of a score in a given gamemode.
 *
 * @param int $n300 The number of 300 hits in a song.
 * @param int $n100 The number of 100 hits in a song.
 * @param int $n50 The number of 50 hits in a song.
 * @param int $ngeki The number of geki hits in a song.
 * @param int $nkatu The number of katu hits in a song.
 * @param int $nmiss The number of missed hits in a song.
 * @param int $mode The game mode.
*/
function calculateAccuracy($n300, $n100, $n50, $ngeki, $nkatu, $nmiss, $mode) {
	// For reference, see: http://osu.ppy.sh/wiki/Accuracy
	switch ($mode) {
		case 0:
			$totalPoints = ($n50 * 50 + $n100 * 100 + $n300 * 300);
			$maxHits = ($nmiss + $n50 + $n100 + $n300);
			$accuracy = $totalPoints / ($maxHits * 300);
		break;
		case 1:
			// Please note this is not what is written on the wiki.
			// However, what was written on the wiki didn't make any sense at all.
			$totalPoints = ($n100 * 50 + $n300 * 100);
			$maxHits = ($nmiss + $n100 + $n300);
			$accuracy = $totalPoints / ($maxHits * 100);
		break;
		case 2:
			$fruits = $n300 + $n100 + $n50;
			$totalFruits = $fruits + $nmiss + $nkatu;
			$accuracy = $fruits / $totalFruits;
		break;
		case 3:
			$totalPoints = ($n50 * 50 + $n100 * 100 + $nkatu * 200 + $n300 * 300 + $ngeki * 300);
			$maxHits = ($nmiss + $n50 + $n100 + $n300 + $ngeki + $nkatu);
			$accuracy = $totalPoints / ($maxHits * 300);
		break;
	}

	return $accuracy * 100; // we're doing * 100 because $accuracy is like 0.9823[...]

}
/*
 * getRequiredScoreForLevel
 * Gets the required score for $l level
 *
 * @param (int) ($l) level
 * @return (int) required score
*/
function getRequiredScoreForLevel($l) {
	// Calcolate required score
	if ($l <= 100) {
		if ($l >= 2) {
			return 5000 / 3 * (4 * bcpow($l, 3, 0) - 3 * bcpow($l, 2, 0) - $l) + 1.25 * bcpow(1.8, $l - 60, 0);
		} elseif ($l <= 0 || $l = 1) {
			return 1;
		} // Should be 0, but we get division by 0 below so set to 1

	} elseif ($l >= 101) {
		return 26931190829 + 100000000000 * ($l - 100);
	}
}
/*
 * getLevel
 * Gets the level for $s score
 *
 * @param (int) ($s) ranked score number
*/
function getLevel($s) {
	$level = 1;
	while (true) {
		// if the level is > 8000, it's probably an endless loop. terminate it.
		if ($level > 8000) {
			return $level;
			break;
		}
		// Calculate required score
		$reqScore = getRequiredScoreForLevel($level);
		// Check if this is our level
		if ($s <= $reqScore) {
			// Our level, return it and break
			return $level;
			break;
		} else {
			// Not our level, calculate score for next level
			$level++;
		}
	}
}

/**************************
 **   OTHER   FUNCTIONS  **
 **************************/
function get_contents_http($url) {
	// If curl is not installed, attempt to use file_get_contents
	if (!function_exists('curl_init')) {
		$w = stream_get_wrappers();
		if (in_array('http', $w)) {
			return file_get_contents($url);
		}

		return;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// Download the given URL, and return output
	$output = curl_exec($ch);
	/*
				    if(curl_errno($ch))
				    {
				        echo 'error:' . curl_error($ch);
				    }*/
	// Close the cURL resource, and free system resources
	curl_close($ch);

	return $output;
}
function post_content_http($url, $content, $timeout=10) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// POST data
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// Download the given URL, and return output
	$output = curl_exec($ch);
	// Close the cURL resource, and free system resources
	curl_close($ch);

	return $output;
}
/*
 * printBadgeSelect()
 * Prints a select with every badge available as options
 *
 * @param (string) ($sn) Name of the select, for php form stuff
 * @param (string) ($sid) Name of the selected item (badge ID)
 * @param (array) ($bd) Badge data array (SELECT * FROM badges)
*/
function printBadgeSelect($sn, $sid, $bd) {
	echo '<select name="'.$sn.'" class="selectpicker" data-width="100%">';
	foreach ($bd as $b) {
		if ($sid == $b['id']) {
			$sel = 'selected';
		} else {
			$sel = '';
		}
		echo '<option value="'.$b['id'].'" '.$sel.'>'.$b['name'].'</option>';
	}
	echo '</select>';
}
/**
 * BwToString()
 * Bitwise enum number to string.
 *
 * @param (int) ($num) Number to convert to string
 * @param (array) ($bwenum) Bitwise enum in the form of array, $EnumName => $int
 * @param (string) ($sep) Separator
 */
function BwToString($num, $bwenum, $sep = '<br>') {
	$ret = [];
	foreach ($bwenum as $key => $value) {
		if ($num & $value) {
			$ret[] = $key;
		}
	}

	return implode($sep, $ret);
}
/*
 * checkUserExists
 * Check if given user exists
 *
 * @param (string) ($i) username/id
 * @param (bool) ($id) if true, search by id. Default: false
*/
function checkUserExists($u, $id = false) {
	if ($id) {
		return $GLOBALS['db']->fetch('SELECT id FROM users WHERE id = ?', [$u]);
	} else {
		return $GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', [$u]);
	}
}
/*
 * getFriendship
 * Check friendship between u0 and u1
 *
 * @param (int/string) ($u0) u0 id/username
 * @param (int/string) ($u1) u1 id/username
 * @param (bool) ($id) If true, u0 and u1 are ids, if false they are usernames
 * @return (int) 0: no friendship, 1: u0 friend with u1, 2: mutual
*/
function getFriendship($u0, $u1, $id = false) {
	// Get id if needed
	if (!$id) {
		$u0 = getUserID($u0);
		$u1 = getUserID($u1);
	}
	// Make sure u0 and u1 exist
	if (!checkUserExists($u0, true) || !checkUserExists($u1, true)) {
		return 0;
	}
	// If user1 is friend of user2, check for mutual.
	if ($GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user1 = ? AND user2 = ?', [$u0, $u1]) !== false) {
		if ($u1 == 999 || $GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user2 = ? AND user1 = ?', [$u0, $u1]) !== false) {
			return 2;
		}

		return 1;
	}
	// Otherwise, it's just no friendship.
	return 0;
}
/*
 * addFriend
 * Add $newFriend to $dude's friendlist
 *
 * @param (int/string) ($dude) user who sent the request
 * @param (int/string) ($newFriend) dude's new friend
 * @param (bool) ($id) If true, $dude and $newFriend are ids, if false they are usernames
 * @return (bool) true if added, false if not (already in friendlist, invalid user...)
*/
function addFriend($dude, $newFriend, $id = false) {
	try {
		// Get id if needed
		if (!$id) {
			$dude = getUserID($dude);
			$newFriend = getUserID($newFriend);
		}
		// Make sure we aren't adding us to our friends
		if ($dude == $newFriend) {
			throw new Exception();
		}
		// Make sure users exist
		if (!checkUserExists($dude, true) || !checkUserExists($newFriend, true)) {
			throw new Exception();
		}
		// Check whether frienship already exists
		if ($GLOBALS['db']->fetch('SELECT id FROM users_relationships WHERE user1 = ? AND user2 = ?') !== false) {
			throw new Exception();
		}
		// Add newFriend to friends
		$GLOBALS['db']->execute('INSERT INTO users_relationships (user1, user2) VALUES (?, ?)', [$dude, $newFriend]);

		return true;
	}
	catch(Exception $e) {
		return false;
	}
}
/*
 * removeFriend
 * Remove $oldFriend from $dude's friendlist
 *
 * @param (int/string) ($dude) user who sent the request
 * @param (int/string) ($oldFriend) dude's old friend
 * @param (bool) ($id) If true, $dude and $oldFriend are ids, if false they are usernames
 * @return (bool) true if removed, false if not (not in friendlist, invalid user...)
*/
function removeFriend($dude, $oldFriend, $id = false) {
	try {
		// Get id if needed
		if (!$id) {
			$dude = getUserID($dude);
			$oldFriend = getUserID($oldFriend);
		}
		// Make sure users exist
		if (!checkUserExists($dude, true) || !checkUserExists($oldFriend, true)) {
			throw new Exception();
		}
		// Delete user relationship. We don't need to check if the relationship was there, because who gives a shit,
		// if they were not friends and they don't want to be anymore, be it. ¯\_(ツ)_/¯
		$GLOBALS['db']->execute('DELETE FROM users_relationships WHERE user1 = ? AND user2 = ?', [$dude, $oldFriend]);

		return true;
	}
	catch(Exception $e) {
		return false;
	}
}
// I don't know what this function is for anymore
function clir($must = false, $redirTo = 'index.php?p=2&e=3') {
	if (checkLoggedIn() === $must) {
		if ($redirTo == "index.php?p=2&e=3") {
			addError('You\'re not logged in.');
			$redirTo == "index.php?p=2";
		}
		redirect($redirTo);
	}
}
/*
 * checkMustHave
 * Makes sure a request has the "Must Have"s of a page.
 * (Must Haves = $mh_GET, $mh_POST)
*/
function checkMustHave($page) {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($page->mh_POST) && count($page->mh_POST) > 0) {
			foreach ($page->mh_POST as $el) {
				if (empty($_POST[$el])) {
					redirect('index.php?p=99&e=do_missing__'.$el);
				}
			}
		}
	} else {
		if (isset($page->mh_GET) && count($page->mh_GET) > 0) {
			foreach ($page->mh_GET as $el) {
				if (empty($_GET[$el])) {
					redirect('index.php?p=99&e=do_missing__'.$el);
				}
			}
		}
	}
}
/*
 * accuracy
 * Convert accuracy to string, having 2 decimal digits.
 *
 * @param (float) accuracy
 * @return (string) accuracy, formatted into a string
*/
function accuracy($acc) {
	return number_format(round($acc, 2), 2);
}
function checkServiceStatus($url) {
	// allow very little timeout for each service
	//ini_set('default_socket_timeout', 5);
	// 0: offline, 1: online, -1: restarting
	try {
		// Bancho status
		//$result = @json_decode(@file_get_contents($url), true);
		$result = getJsonCurl($url);
		if (!isset($result)) {
			throw new Exception();
		}
		if (!array_key_exists('status', $result)) {
			throw new Exception();
		}

		if (array_key_exists('result', $result)) {
			return $result['result'];
		} else {
			return $result['status'];
		}
	}
	catch(Exception $e) {
		return 0;
	}
}
function serverStatusBadge($status) {
	switch ($status) {
		case 1:
		case 200:
			return '<span class="label label-success"><i class="fa fa-check"></i>	Online</span>';
		break;
		case -1:
			return '<span class="label label-warning"><i class="fa fa-exclamation"></i>	Restarting</span>';
		break;
		case 0:
			return '<span class="label label-danger"><i class="fa fa-close"></i>	Offline</span>';
		break;
		default:
			return '<span class="label label-default"><i class="fa fa-question"></i>	Unknown</span>';
		break;
	}
}
function addError($e) {
	startSessionIfNotStarted();
	if (!isset($_SESSION['errors']) || !is_array($_SESSION['errors']))
		$_SESSION['errors'] = array();
	$_SESSION['errors'][] = $e;
}
function addSuccess($s) {
	startSessionIfNotStarted();
	if (!isset($_SESSION['successes']) || !is_array($_SESSION['successes']))
		$_SESSION['successes'] = array();
	$_SESSION['successes'][] = $s;
}
// logIP adds the user to ip_user if they're not in it, and increases occurencies if they are
function logIP($uid) {
	// botnet-track IP
	$GLOBALS['db']->execute("INSERT INTO ip_user (user_id, ip, occurencies) VALUES (?, ?, '1')
							ON DUPLICATE KEY UPDATE occurencies = occurencies + 1",
							[$uid, getIP()]);
}

function getJsonCurl($url, $timeout = 1) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$result=curl_exec($ch);
		var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT));
		curl_close($ch);
		return json_decode($result, true);
	} catch (Exception $e) {
		return false;
	}
}

function curlochi($url){
	$curlHandler = curl_init();
	curl_setopt_array($curlHandler, [
	CURLOPT_URL => $url,
	CURLOPT_RETURNTRANSFER => true,

	CURLOPT_VERBOSE => true,
]);
curl_exec($curlHandler);
curl_close($curlHandler);
}


function postJsonCurl($url, $data, $timeout = 1) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result=curl_exec($ch);
		curl_close($ch);
		return json_decode($result, true);
	} catch (Exception $e) {
		return false;
	}
}

/*
 * bloodcatDirectString()
 * Return a osu!direct-like string for a specific song
 * from a bloodcat song array
 *
 * @param (array) ($arr) Bloodcat data array
 * @param (bool) ($np) If true, output chat np beatmap, otherwise output osu direct search beatmap
 * @return (string) osu!direct-like string
*/
function bloodcatDirectString($arr, $np = false) {
	$s = '';
	if ($np) {
		$s = $arr['id'].'.osz|'.$arr['artist'].'|'.$arr['title'].'|'.$arr['creator'].'|'.$arr['status'].'|10.00000|'.$arr['synced'].'|'.$arr['id'].'|'.$arr['id'].'|0|0|0|';
	} else {
		$s = $arr['id'].'|'.$arr['artist'].'|'.$arr['title'].'|'.$arr['creator'].'|'.$arr['status'].'|10.00000|'.$arr['synced'].'|'.$arr['id'].'|'.$arr['beatmaps'][0]['id'].'|0|0|0||';
		foreach ($arr['beatmaps'] as $diff) {
			$s .= $diff['name'].'@'.$diff['mode'].',';
		}
		$s = rtrim($s, ',');
		$s .= '|';
	}
	return $s;
}

function printBubble($userID, $username, $message, $time, $through) {
	echo '
	<img class="circle" src="' . URL::Avatar() . '/' . $userID . '">
	<div class="bubble">
		<b>' . $username . '</b> ' . $message . '<br>
		<span style="font-size: 80%">' . timeDifference($time, time()) .' through <i>' . $through . '</i></span>
	</div>';
}

function rapLog($message, $userID = -1, $through = "Datenshi Admin Panel") {
	global $DiscordHook;
	if ($userID == -1)
		$userID = $_SESSION["userid"];
	$GLOBALS["db"]->execute("INSERT INTO rap_logs (id, user_id, text, datetime, through) VALUES (NULL, ?, ?, ?, ?);", [$userID, $message, time(), $through]);
	$webhookurl = $DiscordHook["admin-log"];

			$json_data = json_encode(
			[
				"username" => "Log Bot",
				"embeds" =>	[
					[
						"description" => "($userID) " . $_SESSION["username"] ." $message",
						"color" => hexdec( "3366ff" ),
						"footer" => [
							"text" => "via $through"
						],
						"thumbnail" => [
							"url" => "https://a.datenshi.pw/$userID"
						]
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
	// If you need to debug, or find out why you can't send message uncomment line below, and execute script.
	// echo $response;
	curl_close( $ch );
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	// Done
}

function readableRank($rank) {
	switch ($rank) {
		case 1: return "normal"; break;
		case 2: return "supporter"; break;
		case 3: return "developer"; break;
		case 4: return "community manager"; break;
		default: return "bad egg"; break;
	}
}

/*
   RIP Documentation and comments from now on.
   Those functions are the last ones that we've added to old-frontend
   Because new frontend is coming soonTM, so I don't want to waste time writing comments and docs.
   You'll also find 20% more memes in these functions.

   ...and fuck php
   -- Nyo

   I'd just like to interject for a moment. You do not just 'fuck' PHP, you 'fuck' PHP with a CACTUS!
   -- Howl
*/

function getUserPrivileges($userID) {
	global $cachedPrivileges;
	if (isset($cachedPrivileges[$userID])) {
		return $cachedPrivileges[$userID];
	}

	$result = $GLOBALS["db"]->fetch("SELECT privileges FROM users WHERE id = ? LIMIT 1", [$userID]);
	if ($result) {
		$cachedPrivileges[$userID] = current($result);
	} else {
		$cachedPrivileges[$userID] = 0;
	}
	return getUserPrivileges($userID);
}

function hasPrivilege($privilege, $userID = -1) {
	if ($userID == -1)
		if (!array_key_exists("userid", $_SESSION))
			return false;
		else
			$userID = $_SESSION["userid"];
	$result = getUserPrivileges($userID) & $privilege;
	return $result > 0 ? true : false;
}

function isRestricted($userID = -1) {
	return (!hasPrivilege(Privileges::UserPublic, $userID) && hasPrivilege(Privileges::UserNormal, $userID));
}

function isBanned($userID = -1) {
	return (!hasPrivilege(Privileges::UserPublic, $userID) && !hasPrivilege(Privileges::UserNormal, $userID));
}

function multiaccCheckIP($ip) {
	$multiUserID = $GLOBALS['db']->fetch("SELECT user_id, users.username FROM ip_user LEFT JOIN users ON users.id = ip_user.user_id WHERE ip = ?", [$ip]);
	if (!$multiUserID)
		return false;
	return $multiUserID;
	/*$multiUsername = $GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", [$multiUserID]);

	$GLOBALS["db"]->execute("UPDATE users SET notes=CONCAT(COALESCE(notes, ''),'\n-- Multiacc attempt (".$_POST["u"].") from IP ".$ip."') WHERE id = ?", [$multiUserID]); */
}

function getUserFromMultiaccToken($token) {
	$multiToken = $GLOBALS["db"]->fetch("SELECT userid, users.username FROM identity_tokens LEFT JOIN users ON users.id = identity_tokens.user_id WHERE token = ? LIMIT 1", [$token]);
	if (!$multiToken)
		return false;
	return $multiToken;
}

function multiaccCheckToken() {
	if (!isset($_COOKIE["y"]))
		return false;

	// y cookie is set, we expect to found a token in db
	$multiToken = getUserFromMultiaccToken($_COOKIE["y"]);
	if ($multiToken === FALSE) {
		// Token not found in db, user has edited cookie manually.
		// Akerino, keep showing multiacc warning
		$multiToken = false;
	}
	return $multiToken;
}

function getIdentityToken($userID, $generate = True) {
	$identityToken = $GLOBALS["db"]->fetch("SELECT token FROM identity_tokens WHERE user_id = ? LIMIT 1", [$userID]);
	if (!$identityToken && $generate) {
		// If not, generate a new one
		do {
			$identityToken = hash("sha256", randomString(32));
			$collision = $GLOBALS["db"]->fetch("SELECT id FROM identity_tokens WHERE token = ? LIMIT 1", $identityToken);
		} while ($collision);

		// And save it in db
		$GLOBALS["db"]->execute("INSERT INTO identity_tokens (id, user_id, token) VALUES (NULL, ?, ?)", [$userID, $identityToken]);
	} else if ($identityToken) {
		$identityToken = current($identityToken);
	} else {
		$identityToken = false;
	}
	return $identityToken;
}

function setYCookie($userID) {
	$identityToken = getIdentityToken($userID);
	setcookie("y", $identityToken, time()+60*60*24*30*6, "/");	// y of yee
}

function UNIXTimestampToOsuDate($unix) {
	return date("ymdHis", $unix);
}

function isOnline($uid) {
	global $URL;
	try {
		$data = getJsonCurl($URL["bancho"]."/api/v1/isOnline?id=".urlencode($uid));
		return $data["result"];
	} catch (Exception $e) {
		return false;
	}
}

function getDonorPrice($months) {
	return number_format(pow($months * 30 * 0.2, 0.70), 2, ".", "");
}

function getDonorMonths($price) {
	return round(pow($price, (1 / 0.70)) / 30 / 0.2);
}

function unsetCookie($name) {
	unset($_COOKIE[$name]);
	setcookie($name, "", time()-3600);
}

function safeUsername($name) {
	return str_replace(" ", "_", strtolower($name));
}

function updateBanBancho($userID) {
	redisConnect();
	$GLOBALS["redis"]->publish("peppy:ban", $userID);
}

function updateSilenceBancho($userID) {
	redisConnect();
	$GLOBALS["redis"]->publish("peppy:silence", $userID);
}

function stripSuccessError($url) {
	$parts = parse_url($url);
	parse_str($parts['query'], $query);
	unset($query["e"]);
	unset($query["s"]);
	return $parts["path"] . "?" .  http_build_query($query);
}

function appendNotes($userID, $notes, $addNl=true, $addTimestamp=true) {
	$wowo = "";
	if ($addNl)
		$wowo .= "\n";
	if ($addTimestamp)
		$wowo .= date("[Y-m-d H:i:s] ");
	$wowo .= $notes;
	$GLOBALS["db"]->execute("UPDATE users SET notes=CONCAT(COALESCE(notes, ''),?) WHERE id = ? LIMIT 1", [$wowo, $userID]);
}

function removeFromLeaderboard($userID) {
	redisConnect();
	$country = strtolower($GLOBALS["db"]->fetch("SELECT country FROM user_config WHERE id = ? LIMIT 1", [$userID])["country"]);
	foreach (["std", "taiko", "ctb", "mania"] as $key => $value) {
		$GLOBALS["redis"]->zrem("ripple:leaderboard:".$value, $userID);
		$GLOBALS["redis"]->zrem("ripple:leaderboard_relax:".$value, $userID);
		if (strlen($country) > 0 && $country != "xx") {
			$GLOBALS["redis"]->zrem("ripple:leaderboard:".$value.":".$country, $userID);
			$GLOBALS["redis"]->zrem("ripple:leaderboard_relax:".$value.":".$country, $userID);
		}
	}
}

function generateCsrfToken() {
	return bin2hex(openssl_random_pseudo_bytes(32));
}

function csrfToken() {
	if (!isset($_SESSION['csrf'])) {
		$_SESSION['csrf'] = generateCsrfToken();
	}
	return $_SESSION['csrf'];
}

function csrfCheck($givenToken=NULL, $regen=true) {
	if (!isset($_SESSION['csrf'])) {
		return false;
	}
	if ($givenToken === NULL) {
		if (isset($_POST['csrf'])) {
			$givenToken = $_POST['csrf'];
		} else if (isset($_GET['csrf'])) {
			$givenToken = $_GET['csrf'];
		} else {
			return false;
		}
	}
	if (empty($givenToken)) {
		return false;
	}
	$rightToken = $_SESSION['csrf'];
	if ($regen) {
		$_SESSION['csrf'] = generateCsrfToken();
	}
	return hash_equals($rightToken, $givenToken);
}

function giveDonor($userID, $months, $add=true) {
	$userData = $GLOBALS["db"]->fetch("SELECT username, email, donor_expire FROM users WHERE id = ? LIMIT 1", [$userID]);
	if (!$userData) {
		throw new Exception("That user doesn't exist");
	}
	$isDonor = hasPrivilege(Privileges::UserDonor, $userID);
	$username = $userData["username"];
	if (!$isDonor || !$add) {
		$start = time();
	} else {
		$start = $userData["donor_expire"];
		if ($start < time()) {
			$start = time();
		}
	}
	$unixExpire = $start+((30*86400)*$months);
	$monthsExpire = round(($unixExpire-time())/(30*86400));
	$GLOBALS["db"]->execute("UPDATE users SET privileges = privileges | ".Privileges::UserDonor.", donor_expire = ? WHERE id = ?", [$unixExpire, $userID]);
	//can custom badge
	$GLOBALS["db"]->execute("UPDATE user_config SET can_custom_badge = 1 WHERE id = ?", [$userID]);
	
	$donorBadge = $GLOBALS["db"]->fetch("SELECT id FROM badges WHERE name = 'Donat' OR name = 'Donor' LIMIT 1");
	if (!$donorBadge) {
		throw new Exception("There's no Donor badge in the database. Please run bdzr to migrate the database to the latest version.");
	}
	$hasAlready = $GLOBALS["db"]->fetch("SELECT id FROM user_badges WHERE user = ? AND badge = ? LIMIT 1", [$userID, $donorBadge["id"]]);
	if (!$hasAlready) {
		$GLOBALS["db"]->execute("INSERT INTO user_badges(user, badge) VALUES (?, ?);", [$userID, $donorBadge["id"]]);
	}

	return $monthsExpire;
}

function isJson($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}

function prettyPrintJsonString($s) {
	return json_encode(json_decode($s), JSON_PRETTY_PRINT);
}

function getTimestampFromStr($str, $fmt="Y-m-d H:i") {
	$dateTime = DateTime::createFromFormat($fmt, $str);
	if ($dateTime === FALSE) {
		throw new Exception("Invalid timestamp string");
	}
	return $dateTime->getTimestamp();
}

function jsonArrayToHtmlTable($arr) {
	$str = "<table class='anticheattable'><tbody>";
	foreach ($arr as $key => $val) {
			$str .= "<tr>";
			$str .= "<td>$key</td>";
			$str .= "<td>";
			if (is_array($val)) {
					if (!empty($val)) {
							$str .= jsonArrayToHtmlTable($val);
					}
			} else {
					$str .= "<strong>".(is_bool($val) ? ($val ? "true" : "false") : $val)."</strong>";
			}
			$str .= "</td></tr>";
	}
	$str .= "</tbody></table>";

	return $str;
}

function jsonObjectToHtmlTable($jsonString="") {
		$arr = json_decode($jsonString, true);
		$html = "";
		if ($arr && is_array($arr)) {
				$html .= jsonArrayToHtmlTable($arr);
		}
		return $html;
}

function randomFileName($path, $suffix) {
	do {
			$randomStr = randomString(32);
			$file = $path . "/" . $randomStr . $suffix;
			$exists = file_exists($file);
	} while($exists);
	echo $file;
	return $randomStr;
}

function updateMainMenuIconBancho() {
	redisConnect();
	$GLOBALS["redis"]->publish("peppy:reload_settings", "reload");
}

function testMainMenuIconBancho($userID, $mainMenuIconID) {
	redisConnect();
	$GLOBALS["redis"]->publish("peppy:set_main_menu_icon", json_encode(["userID" => $userID, "mainMenuIconID" => $mainMenuIconID]));
}

function getDiscordData($userID) {
	return $GLOBALS["db"]->fetch("SELECT discordid, roleid FROM discord_roles WHERE user_id = ? LIMIT 1", [$userID]);
}

function nukeExt($table, $q, $p) {
	echo "Deleting data from " . $table . "\n";
	$GLOBALS["db"]->execute($q, $p);
}

function nuke($table, $column, $userID, $limit = false) {
	$q = "DELETE FROM " . $table . " WHERE " . $column . " = ?";
	if ($limit) {
		$q .= " LIMIT 1";
	}
	nukeExt($table, $q, [$userID]);
}

function getLeaderboardCondition($key, $id){
  return $GLOBALS['db']->fetchAll('select * from scores_condition where type_id = ? and map_id = ? and active = 1', [$key, $id]);
}

function loadLimitedLeaderboard($key, $id) {
  // If you are confused reading this, consult fetch-20210321.py
  function filterScores($conds) {
    $modeN = 4;
    $modeF = array_fill(0, $modeN, true);
    $modeT = array_fill(0, $modeN, false);
    $modF  = array_fill(0, 4, array_fill(0, $modeN, []));
    foreach($conds as $c) {
      // skip ignored mode
      if(!$modeF[$c['mode_id']]) continue;
      if($c['category_mode']==0 && $c['mod_bit']<0)
        $modeF[$c['mode_id']] = false;
      $modeT[$c['mode_id']] = true;
      $modV = null;
      if((int)$c['mod_bit']<0) continue;
      elseif((int)$c['mod_bit']==0) $modV = 0;
      elseif((int)$c['mod_bit']>0) $modV = 1 << ((int)$c['mod_bit'] - 1);
      if(in_array($modV, $modF[ $c['category_mode'] ][ $c['mode_id'] ])) continue;
      array_push($modF[ $c['category_mode'] ][ $c['mode_id'] ], $modV);
    }
    $filterFun = function ($score) use ($modeF, $modeT, $modF) {
			$actualMods = $score['mods'];
			$noModeMods = $actualMods & ~(128 | 268435456);
      if(!$modeF[ $score['play_mode'] ]) return false;
      if(!$modeT[ $score['play_mode'] ]) return false;
      $modNG = array_filter($modF[0][ $score['play_mode'] ], function($mi) use ($score, $actualMods, $noModeMods){
				// if the map is played without any 1-mod add (excl. s-mod mods)
				// and defined to ignore those, ignore it.
				if($mi == 0)
					return $noModeMods == 0;
        return $mi > 0 && ($noModeMods & $mi) > 0;
      });
      if(count($modNG)>0) return false;
			$modOK = array_filter($modF[2][ $score['play_mode'] ], function($mi) use ($score, $actualMods, $noModeMods){
        return $mi > 0 && ($noModeMods & $mi) > 0;
      });
      if(count($modOK)<=0 && count($modF[2][ $score['play_mode'] ])>0) return false;
			$modRQ = array_filter($modF[3][ $score['play_mode'] ], function($mi) use ($score, $actualMods, $noModeMods){
        return $mi > 0 && ($noModeMods & $mi) > 0;
      });
      if(count($modRQ)<count($modF[3][ $score['play_mode'] ])) return false;
      return true;
    };
    return $filterFun;
  }
  $cData  = null;
  $cRawQ  = [];
  switch($key){
  case 'beatmap_id':
    $cData['beatmap_id'] = $id;
    array_push($cRawQ, 'beatmap_md5 in (select beatmap_md5 from beatmaps where beatmap_id = ?)');
    break;
  case 'beatmap_md5':
    $cData['beatmap_md5'] = $id;
    array_push($cRawQ, 'beatmap_md5 = ?');
    break;
  case 'period_id':
    $cPeriod = $GLOBALS['db']->fetch('select * from score_period where entry_id = ?', [$id]);
    if(!$cPeriod) return [];
    $cData['beatmap_id'] = $cPeriod['beatmap_id'];
    $cData['time_start'] = $cPeriod['start_time'];
    $cData['time_stop']  = $cPeriod['end_time'];
    array_push($cRawQ, 'beatmap_md5 in (select beatmap_md5 from beatmaps where beatmap_id = ?)');
    array_push($cRawQ, '`time` between ? and ?');
    break;
  default:
    return [];
  }
  if(count($cRawQ) > 0) {
    $condQuery = implode(' and ', $cRawQ);
  } else {
    $condQuery = "1=1";
  }
  $scores = $GLOBALS['db']->fetchAll("select * from scores_master where $condQuery and completed in (2,3) order by score desc, accuracy desc, pp desc, `time` asc", array_values($cData));
  $conds  = getLeaderboardCondition($key, $id);
  $scores = array_values(array_filter($scores, filterScores($conds)));
  $scoreMap = reAssoc($scores,function($e){return $e['id'];});
  $scoreBO  = [];
  foreach(array_map(function($s){return (int)$s['user_id'];},$scores) as $userID){
    foreach(array_filter($scores,function($s)use($userID){return (int)$s['user_id'] == $userID;}) as $s){
      $scoreBest = false;
      if(array_key_exists($userID,$scoreBO)){
        $s2 = $scoreMap[$scoreBO[$userID]];
        $scoreBest = compareArrayMulti(
          $s, $s2,
          ['score', 'accuracy', 'pp', 'time'],
          'iffi', '>>><'
        );
        /*
        if ((int)$s['score'] > (int)$s2['score']) $scoreBest = true;
        elseif ((int)$s['score'] < (int)$s2['score']) $scoreBest = false;
        elseif ((float)$s['accuracy'] > (float)$s2['accuracy']) $scoreBest = true;
        elseif ((float)$s['accuracy'] < (float)$s2['accuracy']) $scoreBest = false;
        elseif ((float)$s['pp'] > (float)$s2['pp']) $scoreBest = true;
        elseif ((float)$s['pp'] < (float)$s2['pp']) $scoreBest = false;
        else $scoreBest = ((int)$s['time'] < (int)$s2['time']);
        */
      }else{
        $scoreBest = true;
      }
      if($scoreBest) $scoreBO[$userID]=$s['id'];
    }
  }
  $scores = array_values(array_filter($scores, function($s)use($scoreBO){return in_array((int)$s['id'],array_values($scoreBO));}));
  return $scores;
}

function getGitBranch(){
  $content = file_get_contents(".git/HEAD");
  if(!$content) { return "?????"; }
  return str_ireplace([
    "ref: refs/heads/",
    "\n",
  ],"",$content);
}

function getGitCommit(){
  $branch = getGitBranch();
  if($branch == '?????') { return '?????'; }
  $refs = file_get_contents(sprintf(".git/refs/heads/%s", $branch));
  if(!$refs) { return "????????"; }
  return substr($refs, 0, 8);
}

function checkUsernameBlacklist($s) {
	$bad = false;
	$data = $GLOBALS['db']->fetchAll('select name, type from name_blacklist where active');
	foreach($data as $row) {
		switch($row['type']) {
			case 'split':
				$bad |= (bool)preg_match(sprintf("/\b%s\b/", $row['name']), $s);
				break;
			case 'advanced':
				$bad |= (bool)preg_match(sprintf("/%s/", $row['name']), $s);
				break;
			case 'partial':
				$bad |= str_contains($s, $row['name']);
				break;
			default:
				$bad |= ($row['name'] == $s);
		}
	}
	return $bad;
}

// derived from common/datenshi/reigexp.py
class ReiGexp {
	const FLAGS = 'im';
	
	public static function match($t, $q, $s, $f=ReiGexp::FLAGS) {
		if (!is_string($f)) $f = FLAGS;
		
		switch($t) {
		case 'split':
			return preg_match("/\\b$q\\b/$f", $s) === 1;
		case 'advanced':
		case 'regexp':
			return preg_match("/$q/$f", $s) === 1;
		case 'partial':
			return str_contains($s, $q);
		default:
			return $s == $q;
		}
	}
	
	public static function replace($mt, $q, $s, $rt, $r, $f=ReiGexp::FLAGS) {
		if (!is_string($f)) $f = ReiGexp::FLAGS;
		if (!self::match($mt, $q, $s, $f)) return $s;
		if ($rt == 'total') return $r;
		
		switch($mt) {
		case 'split':
			return preg_replace("/\\b$q\\b/$f", $r, $s);
		case 'advanced':
		case 'regexp':
			return preg_replace("/$q/$f", $r, $s);
		case 'partial':
			return str_replace($q, $r, $s);
		default:
			return $r;
		}
	}
}

function cleanupBeatmapName($name) {
	$filters = $GLOBALS['db']->fetchAll('select * from bancho_filter_system where active and find_in_set("announce", `type`)');
	foreach ($filters as $f) {
		$name = ReiGexp::replace($f['match_type'], $f['from_text'], $name, $f['replace_type'], $f['to_text']);
	}
	return $name;
}
