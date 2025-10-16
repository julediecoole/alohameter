document.addEventListener("DOMContentLoaded", function () {
  // === TEIL 1: AKTUELLES WETTER ===
  const weatherUrl =
    "https://api.open-meteo.com/v1/forecast?latitude=19.5481&longitude=-155.665&current_weather=true&temperature_unit=celsius&windspeed_unit=kmh&timezone=Pacific/Honolulu";

  fetch(weatherUrl)
    .then((response) => response.json())
    .then((data) => {
      document.getElementById("temperature").textContent =
        `${data.current_weather.temperature} °C`;
      document.getElementById("wind_speed").textContent =
        `${data.current_weather.windspeed} km/h`;
    })
    .catch((error) => {
      console.error("Fehler beim Laden der Wetterdaten:", error);
    });

  // === TEIL 2: WASSERTEMPERATUR-CHART ===
  const apiUrl = "https://alohameter.melinagast.ch/unload.php";

  // Funktion zum Laden des Charts mit optionalem Datum
  function loadChart(selectedDate = null) {
    // === Datumslogik: letzten 5 Tage inkl. ausgewähltem oder heutigem Tag ===
    const endDate = selectedDate ? new Date(selectedDate) : new Date(); // Ende = gewählt oder heute
    const startDate = new Date(endDate);
    startDate.setDate(endDate.getDate() - 6); // 6 Tage zurück

    // Format für PHP (YYYY-MM-DD HH:MM:SS)
    const fromDate = `${startDate.toISOString().split("T")[0]} 00:00:00`;
    const toDate = `${endDate.toISOString().split("T")[0]} 23:59:59`;

    // URL für unload.php
    const fullUrl = `${apiUrl}?from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`;

    console.log("Lade Daten von:", fullUrl);

    // === API-Daten laden ===
    fetch(fullUrl)
      .then(response => response.json())
      .then(data => {
        console.log("Geladene Temperaturdaten:", data);

        // Gruppieren nach Insel + Tag
        const groupedByDay = {};
        data.forEach(item => {
          const island = item.namen;
          const date = item.created_at.split(" ")[0]; // YYYY-MM-DD
          if (!groupedByDay[island]) groupedByDay[island] = {};
          if (!groupedByDay[island][date]) groupedByDay[island][date] = [];
          groupedByDay[island][date].push(parseFloat(item.temperatur));
        });

        // Labels (Datumsliste für die letzten 7 Tage)
        const labels = [];
        for (let i = 6; i >= 0; i--) {
          const d = new Date(endDate);
          d.setDate(endDate.getDate() - i);
          labels.push(d.toISOString().split("T")[0]);
        }

        // Durchschnitt pro Tag & Insel (ignoriere null oder ungültige Werte)
        const islands = Object.keys(groupedByDay);
        const averagedData = {};
        islands.forEach(island => {
          averagedData[island] = labels.map(date => {
            const temps = groupedByDay[island][date];
            if (!temps || temps.length === 0) return null; // kein Eintrag
             // Nur gültige Zahlen (nicht null, NaN etc.)
            const validTemps = temps.filter(t => typeof t === "number" && !isNaN(t));
            if (validTemps.length === 0) return null; // alle Werte ungültig → leer anzeigen
            const avg = validTemps.reduce((a, b) => a + b, 0) / validTemps.length;
            return parseFloat(avg.toFixed(2));
          });
        });

        // Chart vorbereiten
        const ctx = document.getElementById("wassertemperatur").getContext("2d");
        ctx.canvas.style.backgroundColor = "transparent";

        const islandColors = {
          Kauai: "#F9B9AA",
          Maui: "#138987",
          Oahu: "#E17F69",
          "Big Island": "#BCEEFF"
        };

       const datasets = islands.map(island => ({
        label: island,
        data: averagedData[island],
        borderColor: islandColors[island] || "#FFFFFF",
        fill: false,
        tension: 0.5,
        spanGaps: true,
        backgroundColor: islandColors[island] || "#FFFFFF", // Füllt das Legendensymbol
        pointStyle: 'rect', // Quadrat, für Kreis 'circle'
        pointRadius: 6,      // Größe der Punkte
        pointBorderColor: islandColors[island] || "#FFFFFF",
        pointBackgroundColor: islandColors[island] || "#FFFFFF"
    }));

        const formattedLabels = labels.map(d =>
          new Date(d).toLocaleDateString("de-DE", { day: "2-digit", month: "2-digit" })
        );

        // Falls bereits ein Chart existiert → löschen, um doppelten Aufbau zu verhindern
        if (window.tempChart) window.tempChart.destroy();

        Chart.defaults.font.family = "Khmer MN";
        Chart.defaults.font.size = 16;
        Chart.defaults.color = "#ffffff";

        // Chart erstellen und speichern
        window.tempChart = new Chart(ctx, {
          type: "line",
          data: {
            labels: formattedLabels,
            datasets: datasets
          },
          options: {
            responsive: true,
            plugins: {
              legend: { position: "top" }
            },
            scales: {
              y: {
                min: 20,
                max: 30,
                title: { display: true, text: "Temperatur (°C)" },
                ticks: { color: "#ffffff" },
                grid: { color: "rgba(255,255,255,0.26)" },
                border: { color: "#ffffff" }
              },
              x: {
                title: { display: true, text: "Datum", color: "#ffffff" },
                ticks: { color: "#ffffff" },
                grid: { color: "rgba(255,255,255,0.26)" },
                border: { color: "#ffffff" }
              }
            }
          }
        });
      })
      .catch(error => console.error("Fehler beim Laden der Temperaturdaten:", error));
  }

  // === Initiales Laden (heute + 6 Tage zurück)
  loadChart();

  // === Eventlistener für den Datepicker-Button
  document.getElementById("loadChartBtn").addEventListener("click", () => {
    const selectedDate = document.getElementById("startDate").value;
    if (!selectedDate) {
      alert("Bitte wähle ein Datum aus!");
      return;
    }
    loadChart(selectedDate);
  });
}); 
