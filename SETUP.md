# Hitri vodič za zagon TrainMe aplikacije

## 1. Zaženite Docker kontejnerje

```bash
docker-compose up -d
```

## 2. Počakajte 30 sekund

Podatkovna baza se inicializira.

## 3. Odprite aplikacijo

- **Aplikacija**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8001

## 4. Testni računi

### Uporabnik:
- Email: `maja@example.com`
- Geslo: `test123` (ali katerokoli - hash je že v bazi)

### Trener:
- Email: `luka@example.com`
- Geslo: `test123`

## 5. Konfiguracija (opcijsko)

### Google OAuth
1. Ustvarite projekt na https://console.cloud.google.com/
2. Omogočite Google+ API
3. Ustvarite OAuth 2.0 Client ID
4. Dodajte redirect URI: `http://localhost:8000/google/google-callback.php`
5. Posodobite `data/www/includes/config.php`:
   ```php
   define('GOOGLE_CLIENT_ID', 'VAŠ_CLIENT_ID');
   define('GOOGLE_CLIENT_SECRET', 'VAŠ_CLIENT_SECRET');
   ```

### Google Maps
1. Omogočite Maps JavaScript API v Google Cloud Console
2. Ustvarite API ključ
3. Posodobite `data/www/fitnes-centri.php`:
   ```javascript
   src="https://maps.googleapis.com/maps/api/js?key=VAŠ_API_KLJUČ&callback=initMap"
   ```

## 6. Ustavi kontejnerje

```bash
docker-compose down
```

## Opombe

- Vsi gesla v testnih podatkih so hash-ani, zato se prijava morda ne bo izvedla. Ustvarite nov račun preko registracije.
- Za produkcijo spremenite gesla v `docker-compose.yml` in `config.php`.
- Upload mapa (`data/www/uploads/`) mora imeti dovolj dovoljenj za pisanje.

