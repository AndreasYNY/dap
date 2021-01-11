<?php
// Database config
define('DATABASE_HOST', 'localhost');	// MySQL host. usually localhost
define('DATABASE_USER', 'root');		// MySQL username
define('DATABASE_PASS', 'meme');		// MySQL password
define('DATABASE_NAME', 'allora');		// Database name
define('DATABASE_WHAT', 'host');		// "host" or unix socket path

// Server urls, no slash
$URL = [
	'avatar' => 'https://a.ripple.moe',
	'server' => 'https://ripple.moe',
	'bancho' => 'http://c.ripple.moe',
	'scores' => 'http://127.0.0.1:5002'
];

// Scores/PP config
$ScoresConfig = [
	"enablePP" => true,
	"useNewBeatmapsTable" => true,		// 0: get beatmaps names from beatmaps_names (old php scores server)
										// 1: get beatmaps names from beatmaps (LETS)
	"api_key" => "",
	"rankRequestsQueueSize" => 20,
	"rankRequestsPerUser" => 2
];

// ip env (ip fix with caddy)
$ipEnv = 'REMOTE_ADDR';	// HTTP_X_FORWARDED_FOR
// DiscordLog
$DiscordHook = [
	"ranked-map" => "",
	"admin-log" => ""
]
// Google recaptcha config
$reCaptchaConfig = [
	"site_key" => "",
	"secret_key" => "",
	"ip" => false
];

$redisConfig = [
	"scheme" => "tcp",
	"host" => "redis",
	"port" => 6379
];
