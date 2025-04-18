<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
error_reporting(E_ALL);

// Get query parameter
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// Get API key
$apiKey = getenv('OPENWEATHER_API_KEY');
if (!$apiKey) {
    error_log('OpenWeatherMap API key not configured');
    echo json_encode(['error' => 'OpenWeatherMap API key not configured']);
    exit;
}

// Function to get city temperature
function getCityTemperature($lat, $lon, $apiKey) {
    try {
        $url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=metric&appid=$apiKey";
        error_log("Fetching temperature: $url");
        $response = @file_get_contents($url);
        if ($response === false) {
            error_log("Failed to fetch temperature for lat: $lat, lon: $lon");
            return null;
        }
        $data = json_decode($response, true);
        if (!$data || !isset($data['main']['temp'])) {
            error_log("Invalid temperature data for lat: $lat, lon: $lon");
            return null;
        }
        return $data['main']['temp'];
    } catch (Exception $e) {
        error_log("Error in getCityTemperature: " . $e->getMessage());
        return null;
    }
}

// Fetch city data
try {
    $url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($query) . "&limit=5&appid=$apiKey";
    error_log("Fetching cities: $url");
    $response = @file_get_contents($url);
    if ($response === false) {
        error_log("Failed to fetch cities for query: $query");
        echo json_encode(['error' => 'Failed to fetch cities']);
        exit;
    }

    $cities = json_decode($response, true);
    if (!$cities) {
        echo json_encode([]);
        exit;
    }

    // Add temperature to each city
    $result = array_map(function($city) use ($apiKey) {
        $temp = getCityTemperature($city['lat'], $city['lon'], $apiKey);
        return [
            'name' => $city['name'],
            'country' => $city['country'],
            'lat' => $city['lat'],
            'lon' => $city['lon'],
            'temp' => $temp !== null ? round($temp, 1) : null
        ];
    }, $cities);

    echo json_encode($result);
} catch (Exception $e) {
    error_log("Fatal error in cities.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}
?>