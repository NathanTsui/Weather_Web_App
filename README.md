# Weather API Application

A simple web application that demonstrates the use of the OpenWeather API to fetch weather data for any city.

## Features

- Get current weather data for any city
- Support for different units (metric, imperial, standard)
- Multiple language support
- Option to exclude specific data types to reduce payload size
- Historical weather data support via date parameter
- AI-powered weather summaries and clothing recommendations

## Setup

1. Make sure you have a web server with PHP support (like MAMP, XAMPP, etc.)
2. Place the files in your web server's directory
3. Open the application in your browser

## API Usage

The application uses the OpenWeather API 3.0 One Call API. Here are the available parameters:

### Required Parameters
- `city`: The name of the city to get weather data for

### Optional Parameters
- `units`: Units of measurement
  - `metric` (default): Celsius, meters/sec
  - `imperial`: Fahrenheit, miles/hour
  - `standard`: Kelvin, meters/sec
- `lang`: Language for the output
  - `en` (default): English
  - `zh_tw`: Traditional Chinese
  - `es`: Spanish
  - `fr`: French
  - `de`: German
  - And many more supported by OpenWeather
- `exclude`: Data to exclude from the response
  - `current`: Current weather
  - `minutely`: Minutely forecast
  - `hourly`: Hourly forecast
  - `daily`: Daily forecast
  - `alerts`: Weather alerts
- `date`: Historical weather data (Unix timestamp or date string)

## Example API Calls

```
# Basic call
api/weather.php?city=London

# With units and language
api/weather.php?city=Tokyo&units=imperial&lang=ja

# Excluding data types
api/weather.php?city=Paris&exclude=minutely,hourly,daily,alerts

# Historical data
api/weather.php?city=New York&date=2023-01-01
```

## Response Format

The API returns a JSON response with the following structure:

```json
{
  "temp": 20.5,
  "humidity": 65,
  "wind_speed": 3.6,
  "uvi": 2.5,
  "description": "scattered clouds",
  "icon": "03d",
  "pop": 0.2,
  "ai_summary": "20°C with 65% humidity - Clear conditions ☀️",
  "clothing_advice": "Light jacket or sweater recommended"
}
```

## Error Handling

The API returns appropriate HTTP status codes and error messages:

- 400: Bad Request (e.g., missing city parameter)
- 404: City not found
- 500: Server error or failed to fetch weather data

## License

This project is open source and available under the MIT License. 