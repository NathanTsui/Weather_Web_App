<?php
/**
 * get environment variables from .env file
 */

// define the path to the .env file
$env_path = __DIR__ . '/../.env';

// check if the .env file exists
if (!file_exists($env_path)) {
    die('The .env file does not exist. Please create a .env file and set the necessary environment variables.');
}

// load the .env file
$env_content = file_get_contents($env_path);
$lines = explode("\n", $env_content);
 
//Parsing .env files
foreach ($lines as $line) {
    // skip emtpy lines and comments
    if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
        continue;
    }
    
    // read key-value pairs
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // delete surrounding quotes
        if (preg_match('/^(["\']).*\1$/', $value)) {
            $value = substr($value, 1, -1);
        }
        
        // set environment variables
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

/**
 * Get environment variable
 * 
 * @param string $key environment variable key
 * @param mixed $default default value
 * @return mixed environment variable value or default value
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    return $value;
} 