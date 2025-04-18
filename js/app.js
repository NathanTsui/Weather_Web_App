class WeatherApp {
    constructor() {
        this.weatherService = new WeatherService();
        this.setupEventListeners();
        this.setupDebounce();
    }

    setupEventListeners() {
        const searchInput = document.getElementById('citySearch');
        const suggestionsContainer = document.getElementById('suggestions');

        searchInput.addEventListener('input', () => {
            this.debounceSearch(searchInput.value);
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.classList.add('hidden');
            }
        });
    }

    setupDebounce() {
        this.debounceSearch = this.debounce((query) => {
            this.handleSearch(query);
        }, 300);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async handleSearch(query) {
        if (query.length < 3) {
            document.getElementById('suggestions').classList.add('hidden');
            return;
        }

        const suggestions = await this.weatherService.getCitySuggestions(query);
        this.displaySuggestions(suggestions);
    }

    displaySuggestions(suggestions) {
        const container = document.getElementById('suggestions');
        container.innerHTML = '';

        if (suggestions.length === 0) {
            container.classList.add('hidden');
            return;
        }

        suggestions.forEach(city => {
            const div = document.createElement('div');
            div.className = 'p-3 hover:bg-gray-100 dark:hover:bg-github-border cursor-pointer';
            div.textContent = `${city.name}, ${city.country}`;
            div.addEventListener('click', () => this.selectCity(city));
            container.appendChild(div);
        });

        container.classList.remove('hidden');
    }

    async selectCity(city) {
        document.getElementById('suggestions').classList.add('hidden');
        document.getElementById('citySearch').value = `${city.name}, ${city.country}`;

        try {
            const weatherData = await this.weatherService.getWeatherData(city.name);
            this.updateUI(weatherData);
        } catch (error) {
            console.error('Error fetching weather data:', error);

        }
    }

    updateUI(data) {
        document.getElementById('temperature').textContent = `${Math.round(data.temp)}°C`;
        document.getElementById('humidity').textContent = `${data.humidity}%`;
        document.getElementById('windSpeed').textContent = `${data.wind_speed} m/s`;
        document.getElementById('precipitation').textContent = `${Math.round(data.pop * 100)}%`;
        document.getElementById('uvIndex').textContent = data.uvi;


        console.log('Raw data:', {
            sunrise: data.sunrise,
            sunset: data.sunset,
            timezone_offset: data.timezone_offset,
            timezone: data.timezone
        });
        

        const formatTime = (timestamp) => {

            const date = new Date(timestamp * 1000);
            const utcHours = date.getUTCHours();
            const utcMinutes = date.getUTCMinutes();
            
            console.log('UTC time (from timestamp):', `${utcHours}:${utcMinutes}`);
            

            const cityOffsetHours = Math.floor(data.timezone_offset / 3600);
            const cityOffsetMinutes = Math.floor((data.timezone_offset % 3600) / 60);
            
            console.log('City timezone offset:', `${cityOffsetHours}:${cityOffsetMinutes}`);
            

            let cityHours = utcHours + cityOffsetHours;
            let cityMinutes = utcMinutes + cityOffsetMinutes;
            

            if (cityMinutes >= 60) {
                cityHours += Math.floor(cityMinutes / 60);
                cityMinutes = cityMinutes % 60;
            }
            

            if (cityHours >= 24) {
                cityHours = cityHours % 24;
            }
            

            if (cityHours < 0) {
                cityHours += 24;
            }
            
            console.log('City local time (calculated):', `${cityHours}:${cityMinutes}`);
            

            let period = 'AM';
            if (cityHours >= 12) {
                period = 'PM';
                if (cityHours > 12) {
                    cityHours -= 12;
                }
            }
            if (cityHours === 0) {
                cityHours = 12;
            }
            

            const formattedHours = cityHours.toString().padStart(2, '0');
            const formattedMinutes = cityMinutes.toString().padStart(2, '0');
            
            const formattedTime = `${formattedHours}:${formattedMinutes} ${period}`;
            console.log('Final formatted time:', formattedTime);
            
            return formattedTime;
        };


        document.getElementById('sunrise').textContent = formatTime(data.sunrise);
        document.getElementById('sunset').textContent = formatTime(data.sunset);


        const summaryElement = document.getElementById('aiSummary');
        summaryElement.querySelector('p').textContent = data.ai_summary;
        summaryElement.classList.remove('hidden');


        document.getElementById('clothingAdvice').textContent = data.clothing_advice;
    }
}

// Initialize the app
const app = new WeatherApp(); 