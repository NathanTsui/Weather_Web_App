<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
error_reporting(E_ALL);

// Get query parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$openweather_api_key = getenv('OPENWEATHER_API_KEY');

if ($action === 'get_api_key') {
    if (!$openweather_api_key) {
        error_log("OpenWeatherMap API key not configured");
        http_response_code(500);
        echo json_encode(['error' => 'OpenWeatherMap API key not configured']);
        exit;
    }
    echo json_encode(['apiKey' => $openweather_api_key]);
    exit;
}

// Existing weather.php code continues here...
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$units = isset($_GET['units']) ? $_GET['units'] : 'metric';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$exclude = isset($_GET['exclude']) ? $_GET['exclude'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : null;
$ai_summary = isset($_GET['ai_summary']) && $_GET['ai_summary'] === 'true';
$day_data = isset($_GET['day_data']) ? json_decode(urldecode($_GET['day_data']), true) : null;


// Get query parameters
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$units = isset($_GET['units']) ? $_GET['units'] : 'metric';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$exclude = isset($_GET['exclude']) ? $_GET['exclude'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : null;
$ai_summary = isset($_GET['ai_summary']) && $_GET['ai_summary'] === 'true';
$day_data = isset($_GET['day_data']) ? json_decode(urldecode($_GET['day_data']), true) : null;

// Validate city for non-AI summary requests
if (empty($city) && !$ai_summary) {
    error_log("Missing city parameter");
    http_response_code(400);
    echo json_encode(['error' => 'City is required']);
    exit;
}

// Get API keys
$openweather_api_key = getenv('OPENWEATHER_API_KEY');
$openrouter_api_key = getenv('OPENROUTER_API_KEY');
if (!$openweather_api_key) {
    error_log("OpenWeatherMap API key not configured");
    http_response_code(500);
    echo json_encode(['error' => 'OpenWeatherMap API key not configured']);
    exit;
}

// Function to make HTTP requests with error handling
function makeHttpRequest($url, $context = null) {
    error_log("HTTP Request: $url");
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $error = error_get_last();
        error_log("HTTP Request failed: " . ($error['message'] ?? 'Unknown error'));
        return false;
    }
    $data = json_decode($response, true);
    if (!$data) {
        error_log("Failed to parse JSON response from: $url");
        return false;
    }
    return $data;
}

// Function to fetch weather data
function getWeatherData($city, $units, $lang, $exclude, $date, $openweather_api_key) {
    try {
        // Step 1: Get coordinates
        $geocoding_url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($city) . "&limit=1&appid=" . $openweather_api_key;
        $geo_data = makeHttpRequest($geocoding_url);
        if (!$geo_data || empty($geo_data)) {
            error_log("City not found or geocoding failed: $city");
            return ['error' => 'City not found'];
        }

        $lat = $geo_data[0]['lat'];
        $lon = $geo_data[0]['lon'];
        error_log("Coordinates for $city: lat=$lat, lon=$lon");

        // Step 2: Fetch weather data
        $weather_url = "https://api.openweathermap.org/data/3.0/onecall?lat=$lat&lon=$lon&appid=" . $openweather_api_key . "&units=$units&lang=$lang";
        if ($exclude) {
            $exclude_array = explode(',', $exclude);
            $exclude_array = array_diff($exclude_array, ['minutely', 'hourly']);
            $exclude = implode(',', $exclude_array);
            if ($exclude) {
                $weather_url .= "&exclude=$exclude";
            }
        }
        if ($date && ($timestamp = is_numeric($date) ? $date : strtotime($date)) !== false) {
            $weather_url .= "&dt=$timestamp";
        }

        $weather_data = makeHttpRequest($weather_url);
        if (!$weather_data) {
            error_log("Failed to fetch weather data for city: $city");
            return ['error' => 'Failed to fetch weather data'];
        }

        // Step 3: Fetch current AQI
        $aqi_url = "http://api.openweathermap.org/data/2.5/air_pollution?lat=$lat&lon=$lon&appid=" . $openweather_api_key;
        $aqi_value = 50; // Default
        $aqi_data = makeHttpRequest($aqi_url);
        if ($aqi_data && isset($aqi_data['list'][0]['main']['aqi'])) {
            $aqi_value = $aqi_data['list'][0]['main']['aqi'] * 50; // Scale to 0-500
            error_log("Current AQI: $aqi_value");
        } else {
            error_log("Using default AQI: $aqi_value");
        }

        // Step 4: Construct response
        $current = $weather_data['current'] ?? [];
        $data = [
            'temp' => $current['temp'] ?? 0,
            'humidity' => $current['humidity'] ?? 0,
            'wind_speed' => $current['wind_speed'] ?? 0,
            'uvi' => $current['uvi'] ?? 0,
            'description' => $current['weather'][0]['description'] ?? 'unknown',
            'icon' => $current['weather'][0]['icon'] ?? '01d',
            'pop' => isset($weather_data['hourly']) && !empty($weather_data['hourly']) ? $weather_data['hourly'][0]['pop'] : 0,
            'sunrise' => $current['sunrise'] ?? 0,
            'sunset' => $current['sunset'] ?? 0,
            'pressure' => $current['pressure'] ?? 1013,
            'feels_like' => $current['feels_like'] ?? 0,
            'visibility' => $current['visibility'] ?? 10000,
            'timezone_offset' => $weather_data['timezone_offset'] ?? 0,
            'timezone' => $weather_data['timezone'] ?? 'UTC',
            'aqi' => $aqi_value,
            'ai_summary' => '',
            'ai_summary_en' => '',
            'ai_summary_zh' => ''
        ];

        // Step 5: Include forecasts
        if (isset($weather_data['minutely']) && !empty($weather_data['minutely'])) {
            $data['minutely'] = array_slice($weather_data['minutely'], 0, 60);
        }
        if (isset($weather_data['hourly']) && !empty($weather_data['hourly'])) {
            $data['hourly'] = array_slice($weather_data['hourly'], 0, 48);
        }
        if (isset($weather_data['daily']) && !empty($weather_data['daily'])) {
            $data['daily'] = array_slice($weather_data['daily'], 0, 7);
            $aqi_forecast_url = "http://api.openweathermap.org/data/2.5/air_pollution/forecast?lat=$lat&lon=$lon&appid=" . $openweather_api_key;
            $aqi_forecast_data = makeHttpRequest($aqi_forecast_url);
            foreach ($data['daily'] as &$day) {
                $day['aqi'] = 50; // Default
                if ($aqi_forecast_data && isset($aqi_forecast_data['list'])) {
                    $day_timestamp = $day['dt'];
                    $closest_aqi = null;
                    $min_time_diff = PHP_INT_MAX;
                    foreach ($aqi_forecast_data['list'] as $aqi_item) {
                        $time_diff = abs($aqi_item['dt'] - $day_timestamp);
                        if ($time_diff < $min_time_diff) {
                            $min_time_diff = $time_diff;
                            $closest_aqi = $aqi_item;
                        }
                    }
                    if ($closest_aqi && isset($closest_aqi['main']['aqi'])) {
                        $day['aqi'] = $closest_aqi['main']['aqi'] * 50; // Scale to 0-500
                        error_log("AQI for day " . date('Y-m-d', $day_timestamp) . ": " . $day['aqi']);
                    }
                }
            }
            unset($day); // Clean up reference
        }
        if (isset($weather_data['alerts'])) {
            $data['alerts'] = $weather_data['alerts'];
        }

        return $data;
    } catch (Exception $e) {
        error_log("Error in getWeatherData: " . $e->getMessage());
        return ['error' => 'Internal error fetching weather data'];
    }
}

// Function to get AI analysis
function getAIAnalysis($data, $lang, $openrouter_api_key) {
    try {
        if (!$openrouter_api_key) {
            error_log("OpenRouter API key not configured");
            $error_message = $lang === 'zh_tw' ? 'AI 總結無法使用' : 'AI summary unavailable';
            return [
                'ai_summary_en' => $lang === 'en' ? $error_message : '',
                'ai_summary_zh' => $lang === 'zh_tw' ? $error_message : ''
            ];
        }

        $url = "https://openrouter.ai/api/v1/chat/completions";
        $weather_info = [
            'temperature' => $data['temp'] ?? 0,
            'humidity' => $data['humidity'] ?? 0,
            'wind_speed' => $data['wind_speed'] ?? 0,
            'precipitation' => $data['pop'] ?? 0,
            'uv_index' => $data['uvi'] ?? 0,
            'description' => preg_replace('/[^\w\s.,-]/u', '', $data['description'] ?? 'unknown') // Sanitize
        ];
        if (isset($data['date'])) {
            $weather_info['date'] = $data['date'];
        }

        $system_prompt = $lang === 'zh_tw'
            ? "你是一個專業的天氣分析師，請根據提供的天氣數據進行分析，並提供繁體中文的30字內的總結。請使用表情符號來增加可讀性，但不要提及日落和日出時間。請直接返回分析結果。"
            : "You are a professional weather analyst. Analyze the provided weather data and provide a 30-word summary in English. Use emojis to enhance readability, exclude sunrise/sunset times. Return analysis directly.";

        $user_prompt = $lang === 'zh_tw'
            ? "請分析以下天氣數據，提供繁體中文30字總結：\n\n" . json_encode($weather_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n使用表情符號，勿提日落日出，直接返回結果。"
            : "Analyze the following weather data, provide a 30-word summary in English:\n\n" . json_encode($weather_info, JSON_PRETTY_PRINT) . "\n\nUse emojis, exclude sunrise/sunset, return analysis directly.";

        $postData = [
            "model" => "openai/gpt-4o-mini",
            "messages" => [
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ],
            "temperature" => 0.7
        ];

        $headers = [
            "Authorization: Bearer " . $openrouter_api_key,
            "Content-Type: application/json",
            "HTTP-Referer: https://weather-app.com",
            "X-Title: Weather Analysis App"
        ];

        error_log("OpenRouter API Request: " . json_encode($postData));
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($postData),
                'timeout' => 8 // Avoid Vercel timeout
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = makeHttpRequest($url, $context);
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $ai_content = trim($response['choices'][0]['message']['content']);
            error_log("OpenRouter API Success: $ai_content");
            return [
                'ai_summary_en' => $lang === 'en' ? $ai_content : '',
                'ai_summary_zh' => $lang === 'zh_tw' ? $ai_content : ''
            ];
        }

        error_log("OpenRouter API failed or no content returned");
        $error_message = $lang === 'zh_tw' ? "由於 API 錯誤，天氣分析無法使用" : "Weather analysis unavailable due to API error";
        return [
            'ai_summary_en' => $lang === 'en' ? $error_message : '',
            'ai_summary_zh' => $lang === 'zh_tw' ? $error_message : ''
        ];
    } catch (Exception $e) {
        error_log("Error in getAIAnalysis: " . $e->getMessage());
        $error_message = $lang === 'zh_tw' ? "由於 API 錯誤，天氣分析無法使用" : "Weather analysis unavailable due to API error";
        return [
            'ai_summary_en' => $lang === 'en' ? $error_message : '',
            'ai_summary_zh' => $lang === 'zh_tw' ? $error_message : ''
        ];
    }
}

// Function to get AI summary only
function getAISummaryInLanguage($city, $units, $lang, $exclude, $date, $day_data, $openweather_api_key, $openrouter_api_key) {
    try {
        if ($day_data) {
            $ai_response = getAIAnalysis($day_data, $lang, $openrouter_api_key);
            return ['ai_summary' => $lang === 'zh_tw' ? $ai_response['ai_summary_zh'] : $ai_response['ai_summary_en']];
        }

        $weather_data = getWeatherData($city, $units, $lang, $exclude, $date, $openweather_api_key);
        if (isset($weather_data['error'])) {
            return $weather_data;
        }

        $ai_response = getAIAnalysis($weather_data, $lang, $openrouter_api_key);
        return ['ai_summary' => $lang === 'zh_tw' ? $ai_response['ai_summary_zh'] : $ai_response['ai_summary_en']];
    } catch (Exception $e) {
        error_log("Error in getAISummaryInLanguage: " . $e->getMessage());
        $error_message = $lang === 'zh_tw' ? "由於 API 錯誤，天氣分析無法使用" : "Weather analysis unavailable due to API error";
        return ['ai_summary' => $error_message];
    }
}

// Handle request
try {
    if ($ai_summary) {
        $result = getAISummaryInLanguage($city, $units, $lang, $exclude, $date, $day_data, $openweather_api_key, $openrouter_api_key);
    } else {
        $weather_data = getWeatherData($city, $units, $lang, $exclude, $date, $openweather_api_key);
        if (!isset($weather_data['error'])) {
            $ai_response = getAIAnalysis($weather_data, $lang, $openrouter_api_key);
            $weather_data = array_merge($weather_data, $ai_response);
        }
        $result = $weather_data;
    }
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Fatal error in weather.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}
?>