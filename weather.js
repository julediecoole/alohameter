document.addEventListener("DOMContentLoaded", function() {

  // === TEIL 1: AKTUELLES WETTER ===
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
  const apiUrl = "https://alohameter.melinagast.ch/unload.php";

  fetch(apiUrl)
    .then(response => response.json())
    .then(data => {
      console.log("Geladene Temperaturdaten:", data);

      // 1️ Gruppieren nach Insel + Tag
      // Wir erstellen ein Objekt: groupedByDay[Insel][Datum] = [alle Messungen an diesem Tag]
      const groupedByDay = {};
      data.forEach(item => {
        const island = item.namen;
        const date = item.created_at.split(" ")[0]; // nur "YYYY-MM-DD"
        if (!groupedByDay[island]) groupedByDay[island] = {};
        if (!groupedByDay[island][date]) groupedByDay[island][date] = [];
        groupedByDay[island][date].push(parseFloat(item.temperatur));
      });
      console.log("Nach Insel + Tag gruppiert:", groupedByDay);

      // 2 Labels für die letzten 5 Tage erstellen (inkl. heute)
      const labels = [];
      const today = new Date();
      for (let i = 4; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        labels.push(d.toISOString().split("T")[0]); // "YYYY-MM-DD"
      }
      console.log("Labels (letzte 5 Tage):", labels);

      // 3 Tagesdurchschnitt pro Insel berechnen
      const islands = Object.keys(groupedByDay);
      const averagedData = {};
      islands.forEach(island => {
        averagedData[island] = labels.map(date => {
          const temps = groupedByDay[island][date];
          if (!temps || temps.length === 0) return null; // leerer Punkt
          const avg = temps.reduce((a, b) => a + b, 0) / temps.length;
          return parseFloat(avg.toFixed(2));
        });
      });
      console.log("Durchschnitt pro Tag:", averagedData);

      // 4 Chart vorbereiten
      const ctx = document.getElementById("wassertemperatur").getContext("2d");

      const islandColors = {
        Kauai: "#F9B9AA",
        Maui: "#138987",
        Oahu: "#E17F69",
        "Big Island": "#BCEEFF"
      };

      const datasets = islands.map(island => ({
        label: island,
        data: averagedData[island],
        borderColor: islandColors[island] || "#ffffffff",
        backgroundColor: (islandColors[island] || "#ffffffff") + "55",
        fill: true,
        tension: 0.3,
        spanGaps: true // verbindet Linien über null hinweg
      }));

      const formattedLabels = labels.map(d =>
        new Date(d).toLocaleDateString("de-DE", { day: "2-digit", month: "2-digit" })
      );

      // 5 Chart erstellen
      new Chart(ctx, {
        type: "line",
        data: {
          labels: formattedLabels,
          datasets: datasets
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: "top" },
            title: { display: true, text: "Wassertemperaturen – Tagesdurchschnitt letzte 5 Tage" }
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
    .catch(error => console.error("Fehler beim Laden der Temperaturdaten:", error));
});
