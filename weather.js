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

  // === TEIL 2: POPUP ===// === Popup-Logik für alle Bojen ===
const bojen = document.querySelectorAll('.boje-icon');

bojen.forEach(boje => {  
  const popupId = boje.dataset.popup; // data-popup auslesen
  const popup = document.getElementById(popupId);

  if (!popup) return;

  // Öffnen
  boje.addEventListener('click', () => {
    popup.style.display = 'flex'; // Popup sichtbar machen
  });

  // Schließen via Button
  const closeBtn = popup.querySelector('.close-btn');
  closeBtn.addEventListener('click', () => {
    popup.style.display = 'none';
  });

  // Schließen durch Klick außerhalb
  popup.addEventListener('click', (e) => {
    if (e.target === popup) popup.style.display = 'none';
  });
});

// === TEIL 3: KARUSSELL IN POPUPS ===
document.querySelectorAll('.popup').forEach(popup => {
  const slides = popup.querySelectorAll('.chart-slide');
  let currentIndex = 0;

  const updateSlides = () => {
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === currentIndex);
    });
  };

  const leftArrow = popup.querySelector('.arrow.left');
  const rightArrow = popup.querySelector('.arrow.right');

  if (leftArrow && rightArrow) {
    leftArrow.addEventListener('click', (e) => {
      e.stopPropagation();
      currentIndex = (currentIndex - 1 + slides.length) % slides.length;
      updateSlides();
    });

    rightArrow.addEventListener('click', (e) => {
      e.stopPropagation();
      currentIndex = (currentIndex + 1) % slides.length;
      updateSlides();
    });
  }

  updateSlides();
});


  // === TEIL 3: TEMPERATUR-CHART ===
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
            maintainAspectRatio: false,
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
  document.getElementById('startDate').addEventListener('change', () => {
    const selectedDate = document.getElementById('startDate').value
    if (!selectedDate) {
      alert('Bitte wähle ein Datum aus!')
      return
    }
    loadChart(selectedDate)
  })
// === TEIL 4: DIAGRAMME FÜR ALLE INSELN (48h, 6-Stunden-Intervalle) ===
function loadIslandCharts() {
  const apiUrl = "https://alohameter.melinagast.ch/unload.php";

  const now = new Date();
  const fromDateObj = new Date(now.getTime() - 48 * 60 * 60 * 1000); // 48h zurück

  const fromDate = `${fromDateObj.toISOString().split("T")[0]} ${String(fromDateObj.getHours()).padStart(2, '0')}:00:00`;
  const toDate = `${now.toISOString().split("T")[0]} ${String(now.getHours()).padStart(2, '0')}:59:59`;

  fetch(`${apiUrl}?from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`)
    .then(res => res.json())
    .then(data => {
      const islands = ["Kauai", "Oahu", "Maui", "Big Island"];

      // === Farbdefinitionen ===
      const chartFrameColor = "#138987"; // Türkisgrün – Rahmen, Achsen, Gridlines
      const chartLineColor = "#E17F69";  // Rotrosa – Datenkurven

      const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            ticks: { color: chartFrameColor },
            grid: { color: chartFrameColor },
            border: { color: chartFrameColor }
          },
          x: {
            ticks: { color: chartFrameColor },
            grid: { color: chartFrameColor },
            border: { color: chartFrameColor }
          }
        }
      };

      islands.forEach(island => {
        const islandData = data.filter(d => d.namen === island);

        const intervals = {};
        islandData.forEach(d => {
          const dateObj = new Date(d.created_at);
          const hour = dateObj.getHours();
          const roundedHour = Math.floor(hour / 6) * 6;
          const key = `${dateObj.toISOString().split("T")[0]} ${String(roundedHour).padStart(2, '0')}:00`;

          if (!intervals[key]) intervals[key] = { wellenhoehe: [], wellenabstand: [], wind: [] };
          intervals[key].wellenhoehe.push(parseFloat(d.wellenhoehe));
          intervals[key].wellenabstand.push(parseFloat(d.wellenabstand));
          intervals[key].wind.push(parseFloat(d.wind));
        });

        const sortedKeys = Object.keys(intervals).sort();
        const avg = arr => arr.reduce((a, b) => a + b, 0) / arr.length;

        const labels = sortedKeys.map(k => {
          const dateObj = new Date(k);
          return `${String(dateObj.getHours()).padStart(2, '0')}:00`;
        });

        const wellenhoeheData = sortedKeys.map(k => parseFloat(avg(intervals[k].wellenhoehe).toFixed(2)));
        const wellenabstandData = sortedKeys.map(k => parseFloat(avg(intervals[k].wellenabstand).toFixed(2)));
        const windData = sortedKeys.map(k => parseFloat(avg(intervals[k].wind).toFixed(2)));

        // ID sauber machen (Leerzeichen entfernen)
        const islandId = island.toLowerCase().replace(/\s/g, '');

        // === Wellenhöhe ===
        const ctxH = document.getElementById(`${islandId}-wellen`).getContext("2d");
        ctxH.canvas.style.backgroundColor = "transparent";
        if (window[`${islandId}WellenhoeheChart`]) window[`${islandId}WellenhoeheChart`].destroy();
        window[`${islandId}WellenhoeheChart`] = new Chart(ctxH, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Wellenhöhe (m)',
              data: wellenhoeheData,
              borderColor: chartLineColor,
              fill: false,
              tension: 0.5,
              pointStyle: 'rect',
              pointRadius: 6,
              pointBackgroundColor: chartLineColor,
              pointBorderColor: chartLineColor
            }]
          },
          options: {
            ...chartOptions,
            scales: {
              ...chartOptions.scales,
              y: {
                ...chartOptions.scales.y,
                min: 0,
                max: 20,
                title: { display: true, text: 'Wellenhöhe (m)', color: chartFrameColor }
              }
            }
          }
        });

        // === Wellenabstand ===
        const ctxA = document.getElementById(`${islandId}-wellenabstand`).getContext("2d");
        ctxA.canvas.style.backgroundColor = "transparent";
        if (window[`${islandId}WellenabstandChart`]) window[`${islandId}WellenabstandChart`].destroy();
        window[`${islandId}WellenabstandChart`] = new Chart(ctxA, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Wellenabstand (m)',
              data: wellenabstandData,
              borderColor: chartLineColor,
              fill: false,
              tension: 0.5,
              pointStyle: 'rect',
              pointRadius: 6,
              pointBackgroundColor: chartLineColor,
              pointBorderColor: chartLineColor
            }]
          },
          options: {
            ...chartOptions,
            scales: {
              ...chartOptions.scales,
              y: {
                ...chartOptions.scales.y,
                min: 0,
                max: 20,
                title: { display: true, text: 'Wellenabstand (m)', color: chartFrameColor }
              }
            }
          }
        });

        // === Wind ===
        const ctxW = document.getElementById(`${islandId}-wind`).getContext("2d");
        ctxW.canvas.style.backgroundColor = "transparent";
        if (window[`${islandId}WindChart`]) window[`${islandId}WindChart`].destroy();
        window[`${islandId}WindChart`] = new Chart(ctxW, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Wind (kn)',
              data: windData,
              borderColor: chartLineColor,
              fill: false,
              tension: 0.5,
              pointStyle: 'rect',
              pointRadius: 6,
              pointBackgroundColor: chartLineColor,
              pointBorderColor: chartLineColor
            }]
          },
          options: {
            ...chartOptions,
            scales: {
              ...chartOptions.scales,
              y: {
                ...chartOptions.scales.y,
                min: 0,
                max: 35,
                title: { display: true, text: 'Wind (kn)', color: chartFrameColor }
              }
            }
          }
        });
      });
    })
    .catch(err => console.error("Fehler beim Laden der Insel-Daten:", err));
}

// Direkt beim Laden ausführen
loadIslandCharts(); 
document.querySelectorAll('.popup').forEach(popup => {
  const slides = popup.querySelectorAll('.chart-slide');
  let currentIndex = 0;

  // === Titel-Element finden ===
  const titleElement = popup.querySelector('.chart-title');

  // === Titel für jede Slide (Reihenfolge muss zu deinen .chart-slide-Elementen passen) ===
  const titles = ["Wellenhöhe (m)", "Wellenabstand (m)", "Wind (kn)"];

  // Erste Slide & Titel initial anzeigen
  slides.forEach((slide, i) => {
    slide.style.display = i === 0 ? 'flex' : 'none';
  });
  if (titleElement) titleElement.textContent = titles[0];

  const showSlide = (index) => {
    slides.forEach((slide, i) => {
      slide.style.display = i === index ? 'flex' : 'none';
    });
    if (titleElement) titleElement.textContent = titles[index];
    currentIndex = index;
  };

  const rightArrow = popup.querySelector('.arrow.right');
  const leftArrow = popup.querySelector('.arrow.left');

  if (rightArrow) {
    rightArrow.addEventListener('click', () => {
      const nextIndex = (currentIndex + 1) % slides.length;
      showSlide(nextIndex);
    });
  }

  if (leftArrow) {
    leftArrow.addEventListener('click', () => {
      const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
      showSlide(prevIndex);
    });
  }
});


});
