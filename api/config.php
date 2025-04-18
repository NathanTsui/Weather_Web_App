<?php
/**
 * get environment variables from .env file
 */
$env_path = __DIR__ . '/../.env';
if (!file_exists($env_path)) {
    die('.env 文件不存在，請創建一個 .env 文件並設置必要的環境變量。');
}
$env_content = file_get_contents($env_path);
$lines = explode("\n", $env_content);
foreach ($lines as $line) {
    if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
        continue;
    }
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (preg_match('/^(["\']).*\1$/', $value)) {
            $value = substr($value, 1, -1);
        }
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}