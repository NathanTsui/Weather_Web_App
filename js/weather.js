class WeatherService {
    constructor() {
        this.apiKey = 'your_openweather_api_key'; // Replace with actual API key
    }

    async getWeatherData(city) {
        try {
            const response = await fetch(`api/weather.php?city=${encodeURIComponent(city)}`);
            if (!response.ok) throw new Error('Weather data fetch failed');
            return await response.json();
        } catch (error) {
            console.error('Weather fetch error:', error);
            throw error;
        }
    }

    async getCitySuggestions(query) {
        if (query.length < 3) return [];
        try {
            const response = await fetch(`http://api.openweathermap.org/geo/1.0/direct?q=${query}&limit=5&appid=${this.apiKey}`);
            if (!response.ok) throw new Error('City suggestions fetch failed');
            return await response.json();
        } catch (error) {
            console.error('Suggestions fetch error:', error);
            return [];
        }
    }
} 