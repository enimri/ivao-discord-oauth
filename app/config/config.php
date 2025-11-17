<?php
/**
 * Application configuration - root level structure
 */

// Load environment variables from .env if it exists
$envFile = defined('ROOT_PATH') ? ROOT_PATH . '/.env' : __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Database configuration
$db_host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db.divisions.ivao.aero';
$db_database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'xmivao_discord';
$db_user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'xmivao_discord_dbuser';
$db_password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
$dsn = "mysql:host={$db_host};dbname={$db_database};charset=UTF8";

// IVAO OAuth configuration
if (!defined('COOKIE_NAME')) {
    define('COOKIE_NAME', 'ivao_tokens');
}
$openid_url = 'https://api.ivao.aero/.well-known/openid-configuration';
$client_id_ivao = $_ENV['OAUTH_CLIENT_ID'] ?? getenv('OAUTH_CLIENT_ID') ?: '';
$client_secret_ivao = $_ENV['OAUTH_CLIENT_SECRET'] ?? getenv('OAUTH_CLIENT_SECRET') ?: '';
$state_string_ivao = $_ENV['OAUTH_STATE'] ?? getenv('OAUTH_STATE') ?: '10';

// Discord OAuth configuration
$discord_client_id = $_ENV['DISCORD_CLIENT_ID'] ?? getenv('DISCORD_CLIENT_ID') ?: '';
$discord_secret_id = $_ENV['DISCORD_SECRET_ID'] ?? getenv('DISCORD_SECRET_ID') ?: '';
$discord_scopes = 'identify+guilds.join';
$discord_bot_token = $_ENV['DISCORD_BOT_TOKEN'] ?? getenv('DISCORD_BOT_TOKEN') ?: '';
$discord_guild_id = $_ENV['DISCORD_GUILD_ID'] ?? getenv('DISCORD_GUILD_ID') ?: '';
$discord_base_url = 'https://discord.com';

// Global configuration
$redirect_uri = $_ENV['REDIRECT_URI'] ?? getenv('REDIRECT_URI') ?: 'https://' . $_SERVER['HTTP_HOST'] . '/';
$GLOBALS['redirect_uri'] = $redirect_uri;
$GLOBALS['base_url'] = $discord_base_url;

// Division configuration
$division_name = $_ENV['DIV'] ?? getenv('DIV') ?: 'XM';
$division_country = $_ENV['COUNTRY'] ?? getenv('COUNTRY') ?: 'Middle East';
$division_url = $_ENV['DIVISION_URL'] ?? getenv('DIVISION_URL') ?: 'https://xm.ivao.aero';

// Make variables available globally
$GLOBALS['dsn'] = $dsn;
$GLOBALS['database_user'] = $db_user;
$GLOBALS['database_password'] = $db_password;
$GLOBALS['openid_url'] = $openid_url;
$GLOBALS['client_id_ivao'] = $client_id_ivao;
$GLOBALS['client_secret_ivao'] = $client_secret_ivao;
$GLOBALS['state_string_ivao'] = $state_string_ivao;
$GLOBALS['client_id'] = $discord_client_id;
$GLOBALS['secret_id'] = $discord_secret_id;
$GLOBALS['scopes'] = $discord_scopes;
$GLOBALS['botToken'] = $discord_bot_token;
$GLOBALS['guild_id'] = $discord_guild_id;

