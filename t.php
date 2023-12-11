<!DOCTYPE html>
<html>
<head>
	<title>Mapa z EXIF</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/georaster"></script>
    <script src="https://unpkg.com/proj4"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==" crossorigin=""/>
    <script src="https://unpkg.com/georaster-layer-for-leaflet"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-measure/dist/leaflet-measure.css">
    <script src="https://unpkg.com/leaflet-measure/dist/leaflet-measure.js"></script>
    <script src="https://unpkg.com/leaflet-measure/dist/leaflet-measure.pl.js"></script>
    <script src="js/L.Control.Sidebar.js"></script>
    <link rel="stylesheet" href="js/L.Control.Sidebar.scss">
    <link rel="stylesheet" href="js/L.Control.Sidebar.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/leaflet-color-markers.js"></script>
    <script src="js/Control.FullScreen.js"></script>
    <link rel="stylesheet" href="js/Control.FullScreen.css">
    <style>
      html, body, #map {
        height: 100%;
        margin: 0;
      }
      
      #map {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 8;
    }
    #loading-indicator{
        position: absolute;
        top: 95%;
        left: 1%;
        z-index: 2001;
    }
    #filter-form {
        position: absolute;
        top: 20px;
        left: 50px;
        z-index: 1000;
        background-color: #fff;
        padding: 10px;
        border-radius: 5px;
    }
    label[for="folder-select"] {
      position: absolute;
      z-index: 999;
    }
    #folder-select {
      padding-top: 20px;
      width: 100%;
      overflow: hidden;
    }
    #logout-button
    {
        position: absolute;
        top: 85vh;
        right: 1.2%;
        z-index: 2000;
        background-color: #fff;
        padding: 10px;
        border-radius: 5px;
    }
    #opacity-slider-container {
      position: absolute;
      top: 86vh;
      left: 43vw;
      z-index: 2000;
      margin-top: 10px;
      padding: 8px;
      border-radius: 4px;
      background-color: #fff;
    }
    #opacity-slider-container label {
      display: block;
      text-align: left;
    }
    .select-checkbox option::before {
        content: "\2610";
        width: 1.3em;
        text-align: center;
        display: inline-block;
    }   
    .select-checkbox option:checked::before {
        content: "\2611";
    }
    </style>
</head>
<body>
	<div id="map"></div>
    <div id="opacity-slider-container">
    <label for="opacity-slider">Przezroczystość:</label>
    <input type="range" min="0" max="1" step="0.01" value="0.7" id="opacity-slider">
    </div>
    <img id="loading-indicator" src="./1484.gif" width="64px" height="64px"></img>
	<form id="filter-form">
		<label for="folder-select">Wybierz ortofotomapę:</label>
		<select class="select-checkbox" id="folder-select" name="folder-select" multiple size="3">
			<?php

                $dir = 'situational-map';

				// Pobierz listę plików .png w folderze situational-map
				$files = array_merge(glob($dir . '/*.tif'), glob($dir . '/*.tiff'));

				// Dla każdego pliku .png w folderze situational-map
				foreach ($files as $file) {
					$file_name = basename($file);
          echo "<option value=\"$file_name\">$file_name</option>";
				}
			?>
		</select>
        <button type="button" onclick="showOlder(this)">Starsze...</button>
        <!--<button type="button" onclick="showStatus()">Status</button>
        <button type="button" onclick="map.eachLayer( function(layer) {console.log(layer)} );">Layers</button>-->
	</form>
  <script>

    var older_shown = false;
    var select = document.getElementById("folder-select");
    var loadingIndicator = document.getElementById("loading-indicator");
    loadingIndicator.hidden = true // ukryć ikonkę ładowania

    // dla przycisku "Stare"

        function showOlder(button)
        {
            select.size = 0;
            button.innerHTML = "Zwiń"
            if (older_shown)
            {
                select.size = 3;
                button.innerHTML = "Starsze..."
            }
            older_shown = !older_shown;
        }

        /*function showStatus(button)
        {
          var selectedOptions = []
          for (const option of select.options) {
            if (option.selected) {
              selectedOptions.push(option.value)
            }
          }
          console.log("Selected: " + selectedOptions)
          console.log("Loading: " + loading)
          console.log("Shown: " + shown)
        }*/

        var loading = []; // ładujące się tify
        var loaded = []; // załadowane tify
        var loadedLayers = []; // załadowane warstwy z tiffami
        var shown = []; // tify na ekranie
        var selectedOptions = []; // wybrane opcje na liście

        function loadGeoTIFF(file) {
            var selectedFilePath = 'situational-map/' + file;
            loading.push(file)
            loadingIndicator.hidden = false

            fetch(selectedFilePath)
                .then(response => response.arrayBuffer())
                .then(arrayBuffer => {
                    parseGeoraster(arrayBuffer).then(georaster => {

                      loading = loading.filter((f) => f !== file)
                      if (loading.length == 0) loadingIndicator.hidden = true
                      loaded.push(file)

                        var layer = new GeoRasterLayer({
                            name: file,
                            georaster: georaster,
                            opacity: 0.7,
                            resolution: 206,
                            maxZoom: 30,           // Ustaw maksymalny poziom przybliżenia na 30
                        });
                        if (!loadedLayers.includes(layer)) loadedLayers.push(layer);
                        // jeśli tif jest wybrany, dodać z nim warstwę do mapy
                        if (selectedOptions.includes(file))
                        {
                          shown.push(file)
                          layer.addTo(map);
                          map.invalidateSize(); // odświeżyć mapę
                        }
                        

                        document.getElementById("opacity-slider").addEventListener("input", function(event) {
                            var opacity = parseFloat(event.target.value);
                            layer.setOpacity(opacity);
                        });
                    });
                })
                .catch(error => console.error("Error loading GeoTIFF:", error));
        }

      var map = L.map('map',{
        fullscreenControl:  true,
        fullscreenControlOptions: {
        position: 'topleft'
        }
        }
        ).setView([52.408056, 16.933889], 17); // Poznań, Stary Rynek

        var measureControl = L.control.measure({primaryLengthUnit: 'meters',secondaryLengthUnit: 'kilometers',primaryAreaUnit:'sqmeters',localization:'pl'});
        measureControl.addTo(map);

    // załaduj samą mapę

    var layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
			maxZoom: 30,
            maxNativeZoom: 19
		});
    layer.addTo(map);

		// Definiujemy ikony z fioletowym i niebieskim kolorem tła
		var purpleIcon = new L.Icon({
		    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
		    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
		    iconSize: [25, 41],
		    iconAnchor: [12, 41],
		    popupAnchor: [1, -34],
		    shadowSize: [41, 41]
		});

		var blueIcon = new L.Icon({
		    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
		    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
		    iconSize: [25, 41],
		    iconAnchor: [12, 41],
		    popupAnchor: [1, -34],
		    shadowSize: [41, 41]
		});

		// Tworzymy tablicę z markerami, które będziemy dodawać do mapy
		var markers = [];

    // odznacz zaznaczone opcje
    for (const option of select.options) {
        option.selected = false
    }

    var selectedBefore = []
    select.addEventListener("click", function(event) {
      // select działa normalnie jak form z checkboxami
      selectedOptions = []
      if (!selectedBefore.includes(select.value)) selectedBefore.push(select.value)
      else selectedBefore = selectedBefore.filter((s) => s !== select.value)
      for (const option of select.options) {
        if (selectedBefore.includes(option.value)) option.selected = true
        else option.selected = false
        if (option.selected) {
          selectedOptions.push(option.value)
        }
      }
      // warstwy typu GeoRasterLayer z mapami, które nie są wybrane, są usuwane
      map.eachLayer( function(layer) {
        if(layer instanceof GeoRasterLayer && !selectedOptions.includes(layer.options.name)) {
          //console.log(layer.options.name)
          map.removeLayer(layer)
          shown = shown.filter((f) => f !== layer.options.name)
          loading = loading.filter((f) => f !== layer.options.name)
        }
      });
      for (const option of select.options) {
          if (loading.includes(option.value) && !option.selected)
          {
              console.log("who invited my man " + option.value + " blud 😭😭😭 Bro thinks he's on the team 😭😭😭")
          }
          
          // dodać warstwy już załadowane do mapy. Jeśli nie są załadowane, załadować je
          if (option.selected && !loading.includes(option.value) && !shown.includes(option.value)) {
            if (loaded.includes(option.value))
            {
              let layer = loadedLayers.filter((l) => l.options.name == option.value) // wybrać warstwę z listy
              map.addLayer(layer[0])
              map.invalidateSize(); // odświeżyć mapę
              continue // przejść do następnej opcji, jeśli tif został już załadowany
            }
            loadGeoTIFF(option.value)
          }
      }
      
    });
  </script>
</body>
</html>