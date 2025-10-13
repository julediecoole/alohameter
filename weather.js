document.addEventListener("DOMContentLoaded", function() {
    const url = "https://api.open-meteo.com/v1/forecast?latitude=19.5481&longitude=-155.665&current_weather=true&temperature_unit=celsius&windspeed_unit=kmh&timezone=Pacific/Honolulu";

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const temperature = data.current_weather.temperature;
            const windSpeed = data.current_weather.windspeed;

            document.getElementById("temperature").textContent = `${temperature} °C`;
            document.getElementById("wind_speed").textContent = `${windSpeed} km/h`;
        })
        .catch(error => {
            console.error("Fehler beim Laden der Wetterdaten:", error);
            document.getElementById("temperature").textContent = "Daten konnten nicht geladen werden";
            document.getElementById("wind_speed").textContent = "Daten konnten nicht geladen werden";
        });
});
