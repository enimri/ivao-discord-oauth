<?php

namespace App\Services;

class IVAOService
{
    private $openid_data;
    private $openid_url;
    private $openid_data_timestamp;
    private const CACHE_TTL = 3600; // Cache for 1 hour
    
    public function __construct()
    {
        global $openid_url;
        $this->openid_url = $openid_url;
    }
    
    /**
     * Clear the cached OpenID data
     */
    public function clearCache(): void
    {
        $this->openid_data = null;
        $this->openid_data_timestamp = null;
    }
    
    private function getOpenIdData(): array
    {
        // Check if cache is still valid
        $now = time();
        if ($this->openid_data !== null && $this->openid_data_timestamp !== null) {
            $age = $now - $this->openid_data_timestamp;
            if ($age < self::CACHE_TTL) {
                return $this->openid_data;
            }
            // Cache expired, clear it
            $this->clearCache();
        }
        
        if (empty($this->openid_url)) {
            throw new \Exception('OpenID URL not configured. Check your .env file.');
        }
        
        // Use stream context with timeout and cache-busting
        $cache_bust = '?t=' . $now;
        $url = $this->openid_url . (strpos($this->openid_url, '?') === false ? $cache_bust : '&' . substr($cache_bust, 1));
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
                'cache_control' => 'no-cache'
            ]
        ]);
        
        // Clear any opcache for this URL if opcache is enabled
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($this->openid_url, true);
        }
        
        $openid_result = @file_get_contents($url, false, $context);
        
        // If that fails, try without cache busting
        if ($openid_result === FALSE) {
            $openid_result = @file_get_contents($this->openid_url, false, $context);
        }
        
        if ($openid_result === FALSE) {
            $error = error_get_last();
            $errorMsg = $error['message'] ?? 'Unknown error';
            // Clear cache on error to force fresh fetch on next attempt
            $this->clearCache();
            error_log("OpenID fetch failed: $errorMsg");
            throw new \Exception('Error while getting openid data: ' . $errorMsg);
        }
        
        $decoded = json_decode($openid_result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Clear cache on parse error
            $this->clearCache();
            throw new \Exception('Failed to parse OpenID configuration: ' . json_last_error_msg());
        }
        
        if (!isset($decoded['authorization_endpoint']) || !isset($decoded['token_endpoint'])) {
            // Clear cache on invalid data
            $this->clearCache();
            throw new \Exception('Invalid OpenID configuration: missing required endpoints');
        }
        
        // Cache the valid data
        $this->openid_data = $decoded;
        $this->openid_data_timestamp = $now;
        
        return $this->openid_data;
    }
    
    public function getTokens(): void
    {
        $code = $_GET['code'];
        global $client_id_ivao, $client_secret_ivao;
        
        $token_req_data = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $client_id_ivao,
            'client_secret' => $client_secret_ivao,
            'redirect_uri' => $GLOBALS['redirect_uri'],
        );
        
        $token_options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($token_req_data)
            )
        );
        $openid_data = $this->getOpenIdData();
        $token_context = stream_context_create($token_options);
        $token_result = @file_get_contents($openid_data['token_endpoint'], false, $token_context);
        
        if ($token_result === FALSE) {
            throw new \Exception('Error while getting token');
        }
        
        $token_res_data = json_decode($token_result, true);
        
        $access_token = $token_res_data['access_token'];
        $refresh_token = $token_res_data['refresh_token'];
        
        setcookie(COOKIE_NAME, json_encode(array(
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        )), time() + 60 * 60 * 24 * 30, '/', '', true, true);
    }
    
    public function getProfile(): array
    {
        if (!isset($_COOKIE[COOKIE_NAME])) {
            return ['description' => 'No auth token found in request'];
        }
        
        $tokens = json_decode($_COOKIE[COOKIE_NAME], true);
        if (!$tokens || !isset($tokens['access_token'])) {
            return ['description' => 'No auth token found in request'];
        }
        
        $access_token = $tokens['access_token'];
        
        $user_options = array(
            'http' => array(
                'header'  => "Authorization: Bearer $access_token\r\n",
                'method'  => 'GET',
                'ignore_errors' => true,
            )
        );
        
        $openid_data = $this->getOpenIdData();
        $user_context = stream_context_create($user_options);
        $user_result = @file_get_contents($openid_data['userinfo_endpoint'], false, $user_context);
        
        if ($user_result === FALSE) {
            return ['description' => 'Failed to get user profile'];
        }
        
        return json_decode($user_result, true);
    }
    
    public function refreshToken(): void
    {
        if (!isset($_COOKIE[COOKIE_NAME])) {
            throw new \Exception('No refresh token available');
        }
        
        $tokens = json_decode($_COOKIE[COOKIE_NAME], true);
        if (!$tokens || !isset($tokens['refresh_token'])) {
            throw new \Exception('No refresh token available');
        }
        
        $refresh_token = $tokens['refresh_token'];
        global $client_id_ivao, $client_secret_ivao;
        
        $token_req_data = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $client_id_ivao,
            'client_secret' => $client_secret_ivao
        );
        
        $token_options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($token_req_data),
                'ignore_errors' => true,
            )
        );
        $openid_data = $this->getOpenIdData();
        $token_context = stream_context_create($token_options);
        $token_result = @file_get_contents($openid_data['token_endpoint'], false, $token_context);
        
        if ($token_result === FALSE) {
            throw new \Exception('Error while refreshing token');
        }
        
        $token_res_data = json_decode($token_result, true);
        
        if (isset($token_res_data['error'])) {
            throw new \Exception('Token refresh failed: ' . $token_res_data['error_description']);
        }
        
        $access_token = $token_res_data['access_token'];
        $refresh_token = $token_res_data['refresh_token'];
        
        setcookie(COOKIE_NAME, json_encode(array(
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        )), time() + 60 * 60 * 24 * 30, '/', '', true, true);
    }
    
    public function getSSOUrl(): string
    {
        global $client_id_ivao, $state_string_ivao;
        $redirect_uri = $GLOBALS['redirect_uri'];
        $openid_data = $this->getOpenIdData();
        $base_url = $openid_data['authorization_endpoint'];
        $response_type = 'code';
        $scopes = 'profile configuration email';
        
        return "$base_url?response_type=$response_type&client_id=$client_id_ivao&scope=$scopes&redirect_uri=" . urlencode($redirect_uri) . "&state=$state_string_ivao";
    }
}

