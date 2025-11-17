<?php

namespace App\Services;

use App\Models\User;

class DiscordService
{
    public function getProfile(): array
    {
        global $client_id, $secret_id;
        
        if (!isset($_GET['code'])) {
            throw new \Exception('No authorization code provided');
        }
        
        $code = $_GET['code'];
        $url = $GLOBALS['base_url'] . "/api/oauth2/token";
        $data = array(
            "client_id" => $client_id,
            "client_secret" => $secret_id,
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $GLOBALS['redirect_uri']
        );
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to get Discord tokens');
        }
        
        $results = json_decode($response, true);
        if (isset($results['error'])) {
            throw new \Exception('Discord OAuth error: ' . $results['error_description']);
        }
        
        $_SESSION['access_token'] = $results['access_token'];
        
        $url = $GLOBALS['base_url'] . "/api/users/@me";
        $headers = array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $results['access_token']);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to get Discord profile');
        }
        
        return json_decode($response, true);
    }
    
    public function joinUserToGuild(User $user): void
    {
        global $guild_id, $botToken;
        
        if (!isset($_SESSION['access_token'])) {
            throw new \Exception('No Discord access token available');
        }
        
        $data = json_encode(array(
            "access_token" => $_SESSION['access_token'],
            "nick" => $user->getDiscordNickname()
        ));
        
        $url = $GLOBALS['base_url'] . "/api/guilds/" . $guild_id . "/members/" . $user->discord_user_id;
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bot ' . $botToken
        );
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
        curl_close($curl);
    }
    
    public function getSSOUrl(): string
    {
        global $client_id, $scopes;
        
        if (!isset($_SESSION['state'])) {
            $_SESSION['state'] = bin2hex(openssl_random_pseudo_bytes(12));
        }
        
        return 'https://discordapp.com/oauth2/authorize?response_type=code&client_id=' . $client_id . '&redirect_uri=' . urlencode($GLOBALS['redirect_uri']) . '&scope=' . $scopes . "&state=" . $_SESSION['state'];
    }
}

