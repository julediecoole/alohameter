document.addEventListener("DOMContentLoaded", function() {
  // === 🌤️ TEIL 1: AKTUELLES WETTER ===
  const weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude=19.5481&longitude=-155.665&current_weather=true&temperature_unit=celsius&windspeed_unit=kmh&timezone=Pacific/Honolulu";

  fetch(weatherUrl)
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


  // === TEIL 2: WASSERTEMPERATUR-CHART ===
  const apiUrl = "https://im3.im-abc.ch/etl-boilerplate/solution/550_unload.php"; 
  // Deine Datenquelle für Wassertemperaturen (4 Inseln)

  fetch(apiUrl)
    .then((response) => response.json())
    .then((data) => {
      const ctx = document.getElementById("wassertemperatur").getContext("2d");

      // Farben definieren
      const islandColors = {
        Kauai: "#F9B9AA",
        Maui: "#138987",
        Oahu: "#E17F69",
        "Big Island": "#BCEEFF"
      };

      // Datensätze für jede Insel
      const datasets = Object.keys(data).map((island) => ({
        label: island,
        data: data[island].map((item) => item.temperature_celsius),
        borderColor: islandColors[island] || "#888",
        backgroundColor: (islandColors[island] || "#888") + "55",
        fill: true,
        tension: 0.3
      }));

      // Labels = Datum (z. B. 13.10., 14.10., …)
      const firstIsland = Object.keys(data)[0];
      const labels = data[firstIsland].map((item) =>
        new Date(item.created_at).toLocaleDateString("de-DE", { day: "2-digit", month: "2-digit" })
      );

      // Chart erstellen
      new Chart(ctx, {
        type: "line",
        data: { labels, datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "top" },
            title: { 
              display: true, 
              text: "Wassertemperaturen der Woche (°C)" 
            }
          },
          scales: {
            y: {
              min: 20,
              max: 30,
              title: { display: true, text: "Temperatur (°C)" }
            },
            x: {
              title: { display: true, text: "Datum" }
            }
          }
        }
      });
    })
    .catch((error) => console.error("Fehler beim Laden der Temperaturdaten:", error));
});
