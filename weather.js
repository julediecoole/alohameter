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
    const groupedByDay = {};
    data.forEach(item => {
      const island = item.namen;
      const date = item.created_at.split(" ")[0]; // nur "YYYY-MM-DD"
      if (!groupedByDay[island]) groupedByDay[island] = {};
      if (!groupedByDay[island][date]) groupedByDay[island][date] = [];
      groupedByDay[island][date].push(parseFloat(item.temperatur));
    });

    // 2 Labels für die letzten 5 Tage erstellen (inkl. heute)
    const labels = [];
    const today = new Date();
    for (let i = 4; i >= 0; i--) {
      const d = new Date(today);
      d.setDate(today.getDate() - i);
      labels.push(d.toISOString().split("T")[0]); // "YYYY-MM-DD"
    }

    // 3 Tagesdurchschnitt pro Insel berechnen
    const islands = Object.keys(groupedByDay);
    const averagedData = {};
    islands.forEach(island => {
      averagedData[island] = labels.map(date => {
        const temps = groupedByDay[island][date];
        if (!temps || temps.length === 0) return null;
        const avg = temps.reduce((a, b) => a + b, 0) / temps.length;
        return parseFloat(avg.toFixed(2));
      });
    });

    // 4 Chart vorbereiten
    const ctx = document.getElementById("wassertemperatur").getContext("2d");
    ctx.canvas.style.backgroundColor = "transparent"; // Hintergrund durchsichtig

    const islandColors = {
      Kauai: "#F9B9AA",
      Maui: "#138987",
      Oahu: "#E17F69",
      "Big Island": "#BCEEFF"
    };

  const datasets = islands.map(island => ({
  label: island,
  data: averagedData[island],
  borderColor: islandColors[island] || "#FFFFFF", // nimmt die Insel-Farbe
  
  fill: false,                                    // nur Linien, keine Fläche
  tension: 0.5,
  spanGaps: true,
  pointBackgroundColor: islandColors[island] || "#FFFFFF", // Tooltip Punktfarbe
  pointBorderColor: islandColors[island] || "#FFFFFF"
}));


    const formattedLabels = labels.map(d =>
      new Date(d).toLocaleDateString("de-DE", { day: "2-digit", month: "2-digit" })
    );

    // 5 Chart erstellen
    Chart.defaults.font.family = "Khmer MN";
    Chart.defaults.font.size = 16; 
    Chart.defaults.color = "#ffffff"; 

    new Chart(ctx, {
      type: "line",
      data: {
        labels: formattedLabels,
        datasets: datasets
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "top",
          },
  
        },
        scales: {
  y: {
    min: 20,
    max: 30,
    title: {
      display: true,
      text: "Temperatur (°C)",
    },
    ticks: {
      color: "#ffffff",
    },
    grid: {
      color: "rgba(255, 255, 255, 0.26)"

    },
    border: { color: "#ffffff" }
  },
  x: {
    title: {
      display: true,
      text: "Datum",
      color: "#ffffff",
    },
    ticks: {
      color: "#ffffff",
  
    },
    grid: {
      color: "rgba(255, 255, 255, 0.26)"
    },
    border: { color: "#ffffff" }
  }
}

      }
    });
  })
  .catch(error => console.error("Fehler beim Laden der Temperaturdaten:", error));
});