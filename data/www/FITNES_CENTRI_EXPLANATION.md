# Razlaga: Avtomatično iskanje fitnes centrov

## Kako deluje

### 1. Geolokacija uporabnika
- Ko uporabnik odpre stran `/fitnes-centri.php`, brskalnik zahteva dovoljenje za dostop do lokacije
- Uporablja se **Geolocation API** (vgrajen v brskalnik)
- Če uporabnik dovoli, dobimo njegove koordinate (latitude, longitude)

### 2. Iskanje fitnes centrov
- JavaScript pošlje AJAX zahtevo na `/api/get-nearby-centers.php` s koordinatami uporabnika
- PHP skripta uporablja **Overpass API** (OpenStreetMap) za iskanje fitnes centrov
- Overpass API poišče vse objekte z oznakami:
  - `leisure=fitness_centre`
  - `amenity=gym`
  - `sport=fitness`
- Iskanje poteka v radiju 10 km okoli uporabnikove lokacije

### 3. Izračun razdalje
- Za vsak najdeni fitnes center se izračuna razdalja z **Haversine formulo**
- Haversine formula izračuna najkrajšo razdaljo med dvema točkama na Zemlji
- Rezultat je v kilometrih

### 4. Sortiranje in prikaz
- Fitnes centri se sortirajo po razdalji (najbližji prvi)
- Prikažejo se samo **5 najbližjih** fitnes centrov
- Če Overpass API ne vrne rezultatov, se uporabi fallback seznam znanih fitnes centrov v Sloveniji

### 5. Prikaz na karti
- Uporablja se **Leaflet.js** (odprtokodna knjižnica za karte)
- Karta prikazuje:
  - Modro piko: Lokacija uporabnika
  - Zeleno piko: Fitnes centri
- Klik na marker prikaže ime, naslov in razdaljo

## Tehnologije

1. **Geolocation API**: Vgrajen v brskalnik, ne potrebuje API ključa
2. **Overpass API**: Brezplačen API od OpenStreetMap, ne potrebuje API ključa
3. **Leaflet.js**: Brezplačna knjižnica za karte, ne potrebuje API ključa

## Prednosti te rešitve

✅ **Brezplačno**: Vse tehnologije so brezplačne  
✅ **Brez API ključev**: Ni potrebna registracija ali API ključi  
✅ **Real-time**: Podatki so vedno posodobljeni (OpenStreetMap se posodablja stalno)  
✅ **Zasebnost**: Lokacija se ne pošilja tretjim osebam (razen OpenStreetMap)  

## Fallback

Če Overpass API ne deluje ali ne vrne rezultatov, se uporabi seznam znanih fitnes centrov:
- Fitnes Center Ljubljana
- Gym Maribor
- CrossFit Celje
- Fitnes Center Kranj
- Yoga Studio Koper

## Kako testirati

1. Odprite `/fitnes-centri.php`
2. Dovolite dostop do lokacije
3. Počakajte, da se naložijo fitnes centri
4. Kliknite na marker za podrobnosti
5. Kliknite "Pokaži na karti" za fokus na izbrani center

