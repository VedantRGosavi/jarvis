<?php
namespace App\Utils;

class OAuthFactory {
    public static function getProvider($provider) {
        switch (strtolower($provider)) {
            case 'google':
                return new GoogleOAuth();
            case 'github':
                return new GitHubOAuth();
            case 'playstation':
                return new PlayStationOAuth();
            case 'steam':
                return new SteamOAuth();
            default:
                throw new \Exception("Unsupported OAuth provider: $provider");
        }
    }
} 