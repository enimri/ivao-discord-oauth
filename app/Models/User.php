<?php

namespace App\Models;

class User
{
    public $ivao_auth_date;
    public $vid;
    public $firstname;
    public $lastname;
    public $refresh_token;
    public $refresh_token_date;
    public $discord_user_id;
    public $verified = 0;
    public $is_banned = 0;
    public $discord_username;
    
    public function fromArray(array $data): void
    {
        $this->ivao_auth_date = $data['ivao_auth_date'] ?? null;
        $this->vid = $data['vid'] ?? null;
        $this->firstname = $data['firstname'] ?? null;
        $this->lastname = $data['lastname'] ?? null;
        $this->refresh_token = $data['refresh_token'] ?? null;
        $this->refresh_token_date = $data['refresh_token_date'] ?? null;
        $this->discord_user_id = $data['discord_user_id'] ?? null;
        $this->verified = $data['verified'] ?? 0;
        $this->is_banned = $data['is_banned'] ?? 0;
        $this->discord_username = $data['discord_username'] ?? null;
    }
    
    public function setNecessaryCookies(): void
    {
        if (!setcookie("vid", $this->vid, time() + 60 * 60 * 24 * 30, '/', '', true, true)) {
            throw new \Exception("Cookie creation failed after db insertion");
        }
        setcookie("firstname", $this->firstname, time() + 60 * 60 * 24 * 30, '/', '', true, true);
    }
    
    public function getDiscordNickname(): string
    {
        return $this->firstname . " " . $this->lastname . " - " . $this->vid;
    }
    
    public function getFullName(): string
    {
        if ($this->firstname && $this->lastname) {
            return "{$this->firstname} {$this->lastname}";
        }
        return $this->firstname ?? 'User';
    }
}

