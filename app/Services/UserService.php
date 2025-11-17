<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    private $pdo;
    
    public function __construct()
    {
        global $dsn, $database_user, $database_password;
        
        if (empty($dsn) || empty($database_user)) {
            throw new \Exception('Database configuration missing. Check your .env file.');
        }
        
        try {
            $this->pdo = new \PDO($dsn, $database_user, $database_password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function isMaintenanceMode(): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT value FROM options WHERE name = 'maintenance'");
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return isset($result['value']) && $result['value'] == '1';
        } catch (\PDOException $e) {
            // If table doesn't exist or query fails, assume not in maintenance mode
            error_log('Maintenance mode check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function createFromIVAOResponse(array $user_res_data): ?User
    {
        if (isset($user_res_data['description']) && $user_res_data['description'] === 'Couldn\'t decode auth token') {
            return null;
        }
        
        if (!isset($user_res_data['id'])) {
            return null;
        }
        
        $user = new User();
        $user->vid = $user_res_data["id"];
        $user->firstname = $user_res_data["firstName"] ?? '';
        $user->lastname = $user_res_data["lastName"] ?? '';
        
        return $user;
    }
    
    public function getByVID(string $vid): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_data WHERE vid = :vid");
        $stmt->execute(['vid' => $vid]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $user = new User();
        $user->fromArray($data);
        return $user;
    }
    
    public function existsInDatabase(string $vid): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_data WHERE vid = :vid");
        $stmt->execute(['vid' => $vid]);
        return $stmt->rowCount() !== 0;
    }
    
    public function save(User $user): void
    {
        $tokens = json_decode($_COOKIE[COOKIE_NAME] ?? '{}', true);
        $refresh_token = $tokens['refresh_token'] ?? '';
        
        $query = "INSERT INTO user_data 
            (ivao_auth_date, vid, firstname, lastname, refresh_token, refresh_token_date, verified, is_banned)
            VALUES
            (NOW(), :vid, :firstname, :lastname, :refresh_token, NOW(), :verified, :is_banned)";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':vid', $user->vid);
        $stmt->bindParam(':firstname', $user->firstname);
        $stmt->bindParam(':lastname', $user->lastname);
        $stmt->bindParam(':refresh_token', $refresh_token);
        $stmt->bindParam(':verified', $user->verified);
        $stmt->bindParam(':is_banned', $user->is_banned);
        
        if ($stmt->execute()) {
            $user->setNecessaryCookies();
        } else {
            throw new \Exception("Something went wrong with the user save. Please contact staff!");
        }
    }
    
    public function update(User $user): void
    {
        $tokens = json_decode($_COOKIE[COOKIE_NAME] ?? '{}', true);
        $refresh_token = $tokens['refresh_token'] ?? '';
        
        $query = "UPDATE user_data SET
            refresh_token = :refresh_token,
            refresh_token_date = NOW()
            WHERE vid = :vid";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':vid', $user->vid);
        $stmt->bindParam(':refresh_token', $refresh_token);
        
        if ($stmt->execute()) {
            $user->setNecessaryCookies();
        } else {
            throw new \Exception("User update failed");
        }
    }
    
    public function updateDiscordUserID(User $user): void
    {
        $stmt = $this->pdo->prepare("UPDATE user_data SET discord_user_id = :discord_user_id WHERE vid = :vid");
        $stmt->execute([
            'discord_user_id' => $user->discord_user_id,
            'vid' => $user->vid
        ]);
    }
}

