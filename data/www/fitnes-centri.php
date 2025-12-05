<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

include 'header.php';
?>

<section class="fitness-centers-section">
    <div class="container">
        <h1 class="page-title">Bližnji fitnes centri</h1>
        <p class="section-description">Najdite fitnes centre v vaši bližini</p>
        
        <div class="fitness-centers-container">
            <div class="map-container">
                <div id="map" style="width: 100%; height: 500px; border-radius: 12px;"></div>
            </div>
            
            <div class="centers-list" id="centersList">
                <h3>Fitnes centri</h3>
                <div class="loading">Nalaganje...</div>
            </div>
        </div>
        
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <!-- Leaflet JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    </div>
</section>

<script>
let map;
let userLocation = null;
let centers = [];
let markers = [];

// Get user location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(position) {
            userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            initMap();
        },
        function(error) {
            console.error('Geolocation error:', error);
            // Default to Ljubljana
            userLocation = { lat: 46.0569, lng: 14.5058 };
            initMap();
        }
    );
} else {
    // Default to Ljubljana
    userLocation = { lat: 46.0569, lng: 14.5058 };
    initMap();
}

function initMap() {
    // Initialize Leaflet map
    map = L.map('map').setView([userLocation.lat, userLocation.lng], 13);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Add user location marker
    const userIcon = L.divIcon({
        className: 'user-location-marker',
        html: '<div style="background-color: #2d6cdf; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });
    
    L.marker([userLocation.lat, userLocation.lng], { icon: userIcon })
        .addTo(map)
        .bindPopup('<strong>Vaša lokacija</strong>')
        .openPopup();

    // Fetch nearby centers from server
    fetchNearbyCenters();
    
    // Store location in localStorage
    localStorage.setItem('userLocation', JSON.stringify(userLocation));
}

function fetchNearbyCenters() {
    const listContainer = document.getElementById('centersList');
    listContainer.innerHTML = '<h3>Fitnes centri</h3><div class="loading">Iskanje najbližjih fitnes centrov...</div>';
    
    // Call PHP API to get nearby centers
    fetch(`/api/get-nearby-centers.php?lat=${userLocation.lat}&lng=${userLocation.lng}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.centers && data.centers.length > 0) {
                centers = data.centers;
                displayCentersOnMap();
                displayCentersList();
            } else {
                // This shouldn't happen now since we always return fallback data
                listContainer.innerHTML = '<h3>Fitnes centri</h3><p style="color: #666; font-size: 0.9rem; padding: 1rem;">Nalaganje fitnes centrov...</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching centers:', error);
            listContainer.innerHTML = '<h3>Fitnes centri</h3><p style="color: #dc3545; padding: 1rem;">Napaka pri iskanju fitnes centrov. Prosimo, poskusite znova.</p>';
        });
}

function displayCentersOnMap() {
    // Clear existing markers (except user location)
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    // Add markers for centers
    centers.forEach((center, index) => {
        const gymIcon = L.divIcon({
            className: 'gym-marker',
            html: '<div style="background-color: #28a745; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });
        
        const marker = L.marker([center.lat, center.lng], { icon: gymIcon })
            .addTo(map)
            .bindPopup(`
                <div style="min-width: 200px;">
                    <h4 style="margin: 0 0 5px 0; color: #2d6cdf;">${escapeHtml(center.name)}</h4>
                    <p style="margin: 0; color: #666; font-size: 12px;">${escapeHtml(center.address)}</p>
                    <p style="margin: 5px 0 0 0; color: #28a745; font-weight: 600; font-size: 12px;">${center.distance} km oddaljeno</p>
                </div>
            `);
        
        markers.push(marker);
        
        marker.on('click', function() {
            map.setView([center.lat, center.lng], 15);
        });
    });
    
    // Fit map to show all markers
    if (centers.length > 0) {
        const group = new L.featureGroup(markers);
        group.addLayer(L.marker([userLocation.lat, userLocation.lng]));
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

function displayCentersList() {
    const listContainer = document.getElementById('centersList');
    listContainer.innerHTML = '<h3>Najbližji fitnes centri</h3>';
    
    if (centers.length === 0) {
        listContainer.innerHTML += '<p>Ni najdenih fitnes centrov v vaši bližini.</p>';
        return;
    }
    
    // Sort by distance
    centers.sort((a, b) => a.distance - b.distance);
    
    centers.forEach((center, index) => {
        const centerItem = document.createElement('div');
        centerItem.className = 'center-item';
        centerItem.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <div>
                    <h4 style="margin: 0 0 5px 0;">${escapeHtml(center.name)}</h4>
                    <p style="color: #666; margin: 0; font-size: 14px;">${escapeHtml(center.address)}</p>
                    <p style="color: #28a745; margin: 5px 0 0 0; font-weight: 600; font-size: 13px;">📍 ${center.distance} km</p>
                </div>
            </div>
            <button class="btn btn-sm btn-primary" onclick="showOnMap(${center.lat}, ${center.lng})">
                Pokaži na karti
            </button>
        `;
        listContainer.appendChild(centerItem);
    });
}

function showOnMap(lat, lng) {
    map.setView([lat, lng], 15);
    // Open popup for the marker at this location
    markers.forEach(marker => {
        const markerLat = marker.getLatLng().lat;
        const markerLng = marker.getLatLng().lng;
        if (Math.abs(markerLat - lat) < 0.001 && Math.abs(markerLng - lng) < 0.001) {
            marker.openPopup();
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'footer.php'; ?>

