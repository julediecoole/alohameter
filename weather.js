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


  // === 🌊 TEIL 2: WASSERTEMPERATUR-CHART ===
  const apiUrl = "https://alohameter.melinagast.ch/unload.php";

  fetch(apiUrl)
    .then((response) => response.json())
    .then((data) => {
      console.log("Geladene Temperaturdaten:", data);

      // Daten nach Inselnamen gruppieren
      const groupedData = {};
      data.forEach(item => {
        const island = item.namen;
        if (!groupedData[island]) groupedData[island] = [];
        groupedData[island].push({
          temperature_celsius: parseFloat(item.temperatur),
          created_at: item.created_at
        });
      });

      console.log("Gruppierte Daten:", groupedData);

      const ctx = document.getElementById("wassertemperatur").getContext("2d");

      const islandColors = {
        Kauai: "#F9B9AA",
        Maui: "#138987",
        Oahu: "#E17F69",
        "Big Island": "#BCEEFF"
      };

      // Labels (Datum)
      const firstIsland = Object.keys(groupedData)[0];
      const labels = groupedData[firstIsland].map((item) =>
        new Date(item.created_at).toLocaleDateString("de-DE", {
          day: "2-digit",
          month: "2-digit"
        })
      );

      // Datensätze erstellen
      const datasets = Object.keys(groupedData).map((island) => ({
        label: island,
        data: groupedData[island].map((item) => item.temperature_celsius),
        borderColor: islandColors[island] || "#888",
        backgroundColor: (islandColors[island] || "#888") + "55",
        fill: true,
        tension: 0.3
      }));

      new Chart(ctx, {
        type: "line",
        data: { labels, datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "top" },
            title: { display: true, text: "Wassertemperaturen der Woche (°C)" }
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
