<?php
/*
 * Form submission php file
*/
require_once './inc/functions.php';
try {
	startSessionIfNotStarted();

	// Find what the user wants to do (compatible with both GET/POST forms)
	if (isset($_POST['action']) && !empty($_POST['action'])) {
		$action = $_POST['action'];
	} elseif (isset($_GET['action']) && !empty($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		throw new Exception("Couldn't find action parameter");
	}
	foreach ($pages as $page) {
		if ($action == $page::URL) {
			if (defined(get_class($page).'::LoggedIn')) {
				if ($page::LoggedIn) {
					clir();
				} else {
					clir(true, 'index.php?p=1&e=1');
				}
			}
			checkMustHave($page);
			$page->D();

			return;
		}
	}
	if (!csrfCheck()) {
		throw new Exception("csrf token check not passed");	// I'M. HOW. TO. BASIC!!
	}

	// What shall we do?
	switch ($action) {
		case 'logout':
			D::Logout();
			redirect('index.php');
		break;
		break;
			// Admin functions, need sessionCheckAdmin() because can be performed only by admins

		case 'saveSystemSettings':
			sessionCheckAdmin(Privileges::AdminManageSettings);
			D::SaveSystemSettings();
		break;
		case 'saveBanchoSettings':
			sessionCheckAdmin(Privileges::AdminManageSettings);
			D::SaveBanchoSettings();
		break;
		case 'saveEditUser':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::SaveEditUser();
		break;
    		case 'saveEditUserWhitelist':
			sessionCheckAdmin(Privileges::AdminManageUsers | Privileges::AdminSupportWhitelist);
			D::SaveEditUserWhitelist();
		break;
		case 'banUnbanUser': // TODO
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::BanUnbanUser();
		break;
		case 'restrictUnrestrictUser':	// TODO
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::RestrictUnrestrictUser();
		break;
		case 'quickEditUser':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::QuickEditUser();
		break;
		case 'quickWhitelistIP':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::QuickEditWhitelistIP();
		break;
		case 'saveWhitelistIP':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::SaveEditWhitelistIP();
		break;
		case 'changeIdentity':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::ChangeIdentity();
		break;
		case 'ReqMarkedDone':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::MarkDone();
		break;
		case 'removeBadge':	// TODO
			sessionCheckAdmin(Privileges::AdminManageBadges);
			D::RemoveBadge();
		break;
		case 'saveBadge':
			sessionCheckAdmin(Privileges::AdminManageBadges);
			D::SaveBadge();
		break;
		case 'quickEditUserBadges':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::QuickEditUserBadges();
		break;
		case 'saveUserBadges':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::SaveUserBadges();
		break;
		case 'silenceUser':
			sessionCheckAdmin(Privileges::AdminSilenceUsers);
			D::SilenceUser();
		break;
		case 'kickUser':
			sessionCheckAdmin(Privileges::AdminSilenceUsers);
			D::KickUser();
		break;
		case 'resetAvatar':	// TODO
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::ResetAvatar();
		break;
		case 'wipeAccount':
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			D::WipeAccount();
		break;
		case 'processBMnotes':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::BMNotes();
		break;
		case 'processWhitelistUser':
			sessionCheckAdmin(Privileges::AdminSupportWhitelist);
			D::GoToPageWhitelist();
		break;
		case 'blacklistRankRequest':	// TODO
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::BlacklistRankRequest();
		break;
		case 'savePrivilegeGroup':
			sessionCheckAdmin(Privileges::AdminManagePrivileges);
			D::savePrivilegeGroup();
		break;
		case 'giveDonor':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::GiveDonor();
		break;
		case 'removeDonor':	// TODO
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::RemoveDonor();
		break;
		case 'rollback':
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			D::Rollback();
		break;
		case 'toggleCustomBadge':	// TODO
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::ToggleCustomBadge();
		break;
		case 'lockUnlockUser':	// TODO
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::LockUnlockUser();
		break;
		case 'rankBeatmapNew':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::RankBeatmapNew();
		break;
		case 'redirectRankBeatmap':
			sessionCheckAdmin(Privileges::AdminManageBeatmaps);
			D::RedirectRankBeatmap();
		break;
		case 'updateUserPrivilege':
			sessionCheckAdmin(Privileges::AdminManagePrivileges);
			D::saveUserPrivileges();
		break;
		case 'clearHWID':	// TODO
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::ClearHWIDMatches();
		break;
		case 'takeReport':	// TODO?
			sessionCheckAdmin(Privileges::AdminManageReports);
			D::TakeReport();
		break;
		case 'solveUnsolveReport':	// TODO?
			sessionCheckAdmin(Privileges::AdminManageReports);
			D::SolveUnsolveReport();
		break;
		case 'uselessUsefulReport':	// TODO?
			sessionCheckAdmin(Privileges::AdminManageReports);
			D::UselessUsefulReport();
		break;
		case 'restoreScoresSearchUser':
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			D::RestoreScoresSearchUser();
		break;
		case 'restoreScores':
			sessionCheckAdmin(Privileges::AdminWipeUsers);
			D::RestoreScores();
		break;
		case 'bulkBan':
			sessionCheckAdmin(Privileges::AdminBanUsers);
			D::BulkBan();
		break;
		case 'deleteUser':
			sessionCheckAdmin(Privileges::AdminManageUsers);
			D::DeleteUser();
		break;
    		case 'challengeEdit':
      			sessionCheckAdmin(Privileges::AdminManageSettings);
      			D::AdminChallengeEdit();
    		break;
		case 'adminRegisterUser':
			sessionCheckAdmin(Privileges::AdminManageBetaKeys);
			D::AdminRegisterUser();
		default:
			throw new Exception('Invalid action value');
	}
}
catch(Exception $e) {
	// Redirect to Exception page
	redirect('index.php?p=99&e='.$e->getMessage());
}
