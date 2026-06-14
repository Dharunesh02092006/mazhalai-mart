<?php
/**
 * Environment Configuration Loader for Mazhalai Mart
 * Loads configuration from .env file
 */

class EnvLoader {
    private static $loaded = false;
    private static $config = [];
    
    /**
     * Load environment variables from .env file
     */
    public static function load($envFile = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($envFile)) {
            // If .env doesn't exist, use default values
            self::setDefaults();
            self::$loaded = true;
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Set environment variable
                $_ENV[$key] = $value;
                putenv("$key=$value");
                self::$config[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Set default values if .env file doesn't exist
     */
    private static function setDefaults() {
        $defaults = [
            'DB_HOST' => 'dummy_host',
            'DB_NAME' => 'dummy_database',
            'DB_USER' => 'dummy_user',
            'DB_PASS' => 'dummy_password',
            'ADMIN_USERNAME' => 'dummy_admin',
            'ADMIN_PASSWORD' => 'dummy_admin_password',
            'ADMIN_EMAIL' => 'admin@mazhalaimart.com',
            'ADMIN_FULL_NAME' => 'Administrator',
            'APP_NAME' => 'Mazhalai Mart',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'SESSION_LIFETIME' => '3600',
            'ITEMS_PER_PAGE' => '10',
            'ADMIN_ITEMS_PER_PAGE' => '20'
        ];
        
        foreach ($defaults as $key => $value) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
            self::$config[$key] = $value;
        }
    }
    
    /**
     * Get environment variable value
     */
    public static function get($key, $default = null) {
        self::load();
        
        // Check $_ENV first, then getenv(), then our config array
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        return $default;
    }
    
    /**
     * Get boolean value from environment
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }
    
    /**
     * Get integer value from environment
     */
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
}

// Auto-load environment variables
EnvLoader::load();

/**
 * Helper function to get environment variables
 */
function env($key, $default = null) {
    return EnvLoader::get($key, $default);
}

/**
 * Helper function to get boolean environment variables
 */
function env_bool($key, $default = false) {
    return EnvLoader::getBool($key, $default);
}

/**
 * Helper function to get integer environment variables
 */
function env_int($key, $default = 0) {
    return EnvLoader::getInt($key, $default);
}
?>
