import { useState, useEffect } from 'react';

const API = 'http://localhost:8080/api';

export default function WeatherWidget() {
    const [weather, setWeather] = useState(null);
    const [forecast, setForecast] = useState(null);
    const [city, setCity] = useState('Manila');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        loadWeather();
    }, [city]);

    const loadWeather = async () => {
        setLoading(true);
        setError('');

        try {
            const [currentRes, forecastRes] = await Promise.all([
                fetch(`${API}/weather/current?city=${city}`),
                fetch(`${API}/weather/combat-forecast?city=${city}`)
            ]);

            const currentData = await currentRes.json();
            const forecastData = await forecastRes.json();

            if (currentData.success) {
                setWeather(currentData.data);
            } else {
                setError(currentData.message);
            }

            if (forecastData.success) {
                setForecast(forecastData.data.forecast);
            }
        } catch (err) {
            setError('Cannot load weather');
        } finally {
            setLoading(false);
        }
    };

    const getWeatherIcon = (condition) => {
        const icons = {
            'Clear': '☀️',
            'Clouds': '☁️',
            'Rain': '🌧️',
            'Drizzle': '🌦️',
            'Thunderstorm': '⛈️',
            'Snow': '❄️',
            'Fog': '🌫️',
            'Mist': '🌫️',
            'Haze': '🌫️'
        };
        return icons[condition] || '🌤️';
    };

    if (loading) {
        return (
            <div style={styles.widget}>
                <div style={styles.loading}>Loading weather...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div style={styles.widget}>
                <div style={styles.error}>❌ {error}</div>
            </div>
        );
    }

    return (
        <div style={styles.widget}>
            <div style={styles.header}>
                <h3 style={styles.title}>🌍 WEATHER & COMBAT</h3>
                <input
                    type="text"
                    value={city}
                    onChange={(e) => setCity(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && loadWeather()}
                    style={styles.cityInput}
                    placeholder="Enter city..."
                />
            </div>

            {weather && (
                <>
                    {/* Current Weather */}
                    <div style={styles.currentWeather}>
                        <div style={styles.weatherMain}>
                            <div style={styles.weatherIcon}>
                                {getWeatherIcon(weather.condition)}
                            </div>
                            <div>
                                <div style={styles.temperature}>{weather.temperature}°C</div>
                                <div style={styles.condition}>{weather.condition}</div>
                                <div style={styles.cityName}>
                                    {weather.city}
                                    {weather.demo_mode && ' (DEMO)'}
                                </div>
                            </div>
                        </div>

                        <div style={styles.weatherDetails}>
                            <div style={styles.detail}>
                                <span>💧 Humidity</span>
                                <span>{weather.humidity}%</span>
                            </div>
                            <div style={styles.detail}>
                                <span>💨 Wind</span>
                                <span>{weather.wind_speed} m/s</span>
                            </div>
                            <div style={styles.detail}>
                                <span>🌡️ Feels</span>
                                <span>{weather.feels_like}°C</span>
                            </div>
                        </div>
                    </div>

                    {/* Combat Impact */}
                    {weather.combat_rating && (
                        <div style={styles.combatImpact}>
                            <h4 style={styles.impactTitle}>⚔️ COMBAT IMPACT</h4>
                            <div style={styles.impactBadge}>
                                <span style={styles.ratingBadge}>{weather.combat_rating.label}</span>
                            </div>
                            <div style={styles.impactGrid}>
                                <div style={styles.impactItem}>
                                    <div style={styles.impactValue}>
                                        {weather.bio_drain_modifier >= 0 ? '+' : ''}{weather.bio_drain_modifier}
                                    </div>
                                    <div style={styles.impactLabel}>BIO DRAIN</div>
                                </div>
                                <div style={styles.impactItem}>
                                    <div style={styles.impactValue}>{weather.visibility}%</div>
                                    <div style={styles.impactLabel}>VISIBILITY</div>
                                </div>
                                <div style={styles.impactItem}>
                                    <div style={styles.impactValue}>{weather.mobility}%</div>
                                    <div style={styles.impactLabel}>MOBILITY</div>
                                </div>
                            </div>
                            <div style={styles.impactDesc}>{weather.description}</div>
                        </div>
                    )}
                </>
            )}

            {/* Forecast */}
            {forecast && forecast.length > 0 && (
                <div style={styles.forecast}>
                    <h4 style={styles.forecastTitle}>📅 5-DAY FORECAST</h4>
                    <div style={styles.forecastList}>
                        {forecast.map((day, idx) => (
                            <div key={idx} style={styles.forecastItem}>
                                <div style={styles.forecastDate}>
                                    {new Date(day.date).toLocaleDateString('en', { weekday: 'short' })}
                                </div>
                                <div style={styles.forecastIcon}>
                                    {getWeatherIcon(day.condition)}
                                </div>
                                <div style={styles.forecastTemp}>{day.temp}°C</div>
                                {day.combat_impact && (
                                    <div
                                        style={{
                                            ...styles.forecastRating,
                                            color: day.combat_impact.combat_rating.color
                                        }}
                                    >
                                        {day.combat_impact.combat_rating.rating}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

const styles = {
    widget: {
        background: 'rgba(10, 10, 30, 0.85)',
        border: '1px solid rgba(0, 240, 255, 0.2)',
        borderRadius: '12px',
        padding: '20px',
        marginBottom: '20px',
        fontFamily: 'monospace',
        color: '#e0e0ff'
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '15px',
        flexWrap: 'wrap',
        gap: '10px'
    },
    title: {
        color: '#00f0ff',
        fontSize: '0.85rem',
        margin: 0,
        letterSpacing: '2px'
    },
    cityInput: {
        padding: '6px 12px',
        background: 'rgba(0,0,0,0.4)',
        border: '1px solid rgba(0, 240, 255, 0.2)',
        borderRadius: '6px',
        color: '#e0e0ff',
        fontSize: '0.75rem',
        fontFamily: 'monospace',
        width: '150px'
    },
    loading: {
        textAlign: 'center',
        color: '#8888cc',
        fontSize: '0.8rem',
        padding: '20px'
    },
    error: {
        textAlign: 'center',
        color: '#ff0044',
        fontSize: '0.8rem',
        padding: '20px'
    },
    currentWeather: {
        background: 'rgba(0, 240, 255, 0.05)',
        borderRadius: '10px',
        padding: '15px',
        marginBottom: '15px'
    },
    weatherMain: {
        display: 'flex',
        alignItems: 'center',
        gap: '15px',
        marginBottom: '15px'
    },
    weatherIcon: {
        fontSize: '3rem'
    },
    temperature: {
        fontSize: '1.8rem',
        color: '#00f0ff',
        fontWeight: 'bold',
        lineHeight: 1
    },
    condition: {
        color: '#e0e0ff',
        fontSize: '0.85rem',
        marginTop: '4px'
    },
    cityName: {
        color: '#8888cc',
        fontSize: '0.7rem',
        marginTop: '2px'
    },
    weatherDetails: {
        display: 'grid',
        gridTemplateColumns: 'repeat(3, 1fr)',
        gap: '10px'
    },
    detail: {
        display: 'flex',
        flexDirection: 'column',
        gap: '4px',
        fontSize: '0.7rem',
        color: '#8888cc',
        padding: '8px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '6px',
        textAlign: 'center'
    },
    combatImpact: {
        background: 'rgba(255, 0, 68, 0.05)',
        border: '1px solid rgba(255, 0, 68, 0.2)',
        borderRadius: '10px',
        padding: '15px',
        marginBottom: '15px'
    },
    impactTitle: {
        color: '#ff0044',
        fontSize: '0.75rem',
        margin: '0 0 10px 0',
        letterSpacing: '2px'
    },
    impactBadge: {
        textAlign: 'center',
        marginBottom: '12px'
    },
    ratingBadge: {
        display: 'inline-block',
        padding: '6px 16px',
        background: 'rgba(0,0,0,0.4)',
        borderRadius: '20px',
        fontSize: '0.85rem',
        fontWeight: 'bold'
    },
    impactGrid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(3, 1fr)',
        gap: '10px',
        marginBottom: '10px'
    },
    impactItem: {
        textAlign: 'center',
        padding: '8px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '6px'
    },
    impactValue: {
        fontSize: '1.2rem',
        color: '#00f0ff',
        fontWeight: 'bold'
    },
    impactLabel: {
        fontSize: '0.6rem',
        color: '#8888cc',
        marginTop: '4px'
    },
    impactDesc: {
        textAlign: 'center',
        fontSize: '0.7rem',
        color: '#8888cc',
        fontStyle: 'italic'
    },
    forecast: {
        background: 'rgba(255, 170, 0, 0.05)',
        border: '1px solid rgba(255, 170, 0, 0.2)',
        borderRadius: '10px',
        padding: '15px'
    },
    forecastTitle: {
        color: '#ffaa00',
        fontSize: '0.75rem',
        margin: '0 0 10px 0',
        letterSpacing: '2px'
    },
    forecastList: {
        display: 'grid',
        gridTemplateColumns: 'repeat(5, 1fr)',
        gap: '8px'
    },
    forecastItem: {
        textAlign: 'center',
        padding: '8px 4px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '6px'
    },
    forecastDate: {
        fontSize: '0.65rem',
        color: '#8888cc',
        marginBottom: '4px'
    },
    forecastIcon: {
        fontSize: '1.5rem',
        marginBottom: '4px'
    },
    forecastTemp: {
        fontSize: '0.75rem',
        color: '#00f0ff',
        fontWeight: 'bold'
    },
    forecastRating: {
        fontSize: '0.8rem',
        fontWeight: 'bold',
        marginTop: '4px'
    }
};