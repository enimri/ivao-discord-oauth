<?php

namespace App\Controllers;

use App\Services\IVAOService;
use App\Services\DiscordService;
use App\Services\UserService;
use App\Core\View;

class AuthController
{
    private $ivaoService;
    private $discordService;
    private $userService;
    
    public function __construct()
    {
        try {
            $this->ivaoService = new IVAOService();
            $this->discordService = new DiscordService();
            $this->userService = new UserService();
        } catch (\Exception $e) {
            throw new \Exception('Failed to initialize services: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function index(): void
    {
        try {
            // Check maintenance mode
            if ($this->userService->isMaintenanceMode()) {
                $this->maintenance();
                return;
            }
            
            // Handle IVAO callback
            if (isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] == $GLOBALS['state_string_ivao']) {
                $this->ivaoService->getTokens();
                header('Location: ' . $GLOBALS['redirect_uri']);
                exit;
            }
            
            // Check if user has IVAO auth
            if (isset($_COOKIE[COOKIE_NAME])) {
                $profile = $this->ivaoService->getProfile();
                
                if ($this->isTokenError($profile)) {
                    $this->ivaoService->refreshToken();
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                }
                
                // Save/update user in database
                $user = $this->userService->createFromIVAOResponse($profile);
                if ($user) {
                    if ($this->userService->existsInDatabase($user->vid)) {
                        $this->userService->update($user);
                    } else {
                        $this->userService->save($user);
                    }
                }
                
                // User has IVAO auth, show Discord join button
                View::render('discord-join', [
                    'discordUrl' => $this->discordService->getSSOUrl(),
                    'division_name' => $_ENV['DIV'] ?? getenv('DIV') ?: 'XM',
                    'division_country' => $_ENV['COUNTRY'] ?? getenv('COUNTRY') ?: 'Middle East',
                    'division_url' => $_ENV['DIVISION_URL'] ?? getenv('DIVISION_URL') ?: 'https://xm.ivao.aero'
                ]);
                return;
            }
            
            // Show IVAO login
            try {
                $ivaoUrl = $this->ivaoService->getSSOUrl();
            } catch (\Exception $e) {
                // Clear cache on error to force fresh fetch on next attempt
                try {
                    $this->ivaoService->clearCache();
                } catch (\Exception $clearError) {
                    // Ignore cache clear errors
                }
                
                // If OpenID fetch fails, show error page
                http_response_code(500);
                echo "<h1>Service Unavailable</h1>";
                echo "<p>Unable to connect to IVAO authentication service. Please try again later.</p>";
                if (ini_get('display_errors')) {
                    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                return;
            }
            
            View::render('index', [
                'ivaoUrl' => $ivaoUrl,
                'division_name' => $_ENV['DIV'] ?? getenv('DIV') ?: 'XM',
                'division_country' => $_ENV['COUNTRY'] ?? getenv('COUNTRY') ?: 'Middle East',
                'division_url' => $_ENV['DIVISION_URL'] ?? getenv('DIVISION_URL') ?: 'https://xm.ivao.aero'
            ]);
        } catch (\Throwable $e) {
            if (ini_get('display_errors')) {
                http_response_code(500);
                echo "<h1>Error in index()</h1>";
                echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            } else {
                http_response_code(500);
                echo "Internal server error";
            }
        }
    }
    
    public function ivaoCallback(): void
    {
        // Handled in index()
        header('Location: /');
        exit;
    }
    
    public function discordCallback(): void
    {
        if (isset($_GET['code']) && isset($_GET['state']) && isset($_COOKIE['vid'])) {
            $discordUser = $this->discordService->getProfile();
            
            // Update user in database
            $user = $this->userService->getByVID($_COOKIE['vid']);
            if ($user) {
                $user->discord_user_id = $discordUser['id'];
                $this->userService->updateDiscordUserID($user);
                
                // Join user to Discord server
                $this->discordService->joinUserToGuild($user);
                
                header('Location: /success');
                exit;
            }
        }
        
        header('Location: /');
        exit;
    }
    
    public function success(): void
    {
        $user = $this->userService->getByVID($_COOKIE['vid'] ?? '');
        View::render('success', [
            'user' => $user,
            'division_name' => $_ENV['DIV'] ?? getenv('DIV') ?: 'XM',
            'division_country' => $_ENV['COUNTRY'] ?? getenv('COUNTRY') ?: 'Middle East',
            'division_url' => $_ENV['DIVISION_URL'] ?? getenv('DIVISION_URL') ?: 'https://xm.ivao.aero'
        ]);
    }
    
    public function maintenance(): void
    {
        View::render('maintenance', [
            'division_name' => $_ENV['DIV'] ?? getenv('DIV') ?: 'XM',
            'division_country' => $_ENV['COUNTRY'] ?? getenv('COUNTRY') ?: 'Middle East'
        ]);
    }
    
    private function isTokenError(array $profile): bool
    {
        if (!isset($profile['description'])) {
            return false;
        }
        
        $errorMessages = [
            'This auth token has been revoked or expired',
            'Couldn\'t decode auth token',
            'No auth token found in request'
        ];
        
        return in_array($profile['description'], $errorMessages);
    }
}

