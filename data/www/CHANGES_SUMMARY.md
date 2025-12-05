# Povzetek sprememb

## 1. Popravljeni encoding problemi za slovenske znake

### Problem
Opisi vaj so se prikazovali z napačnimi znaki (npr. `Ã` namesto `č`, `š`, `ž`).

### Rešitev
- **db.php**: Dodano eksplicitno nastavljanje UTF-8 kodiranja v PDO povezavi
  - `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"`
  - Dodani `SET CHARACTER SET utf8mb4` in `SET NAMES utf8mb4` ukazi

- **Vsi prikazi opisov**: Posodobljeni z eksplicitnim UTF-8 kodiranjem
  - `htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')`
  - Posodobljeno v: `program.php`, `moj-program.php`, `moji-programi.php`, `trener.php`, `programi.php`, `index.php`, `checkout.php`

### Rezultat
Slovenski znaki (č, š, ž, Č, Š, Ž) se sedaj prikazujejo pravilno.

---

## 2. Izboljšan prikaz vaj

### Problem
Vaje brez video URL-ja so prikazovale samo placeholder z linkom na YouTube.

### Rešitev
- **includes/functions.php**: Dodana funkcija `getExerciseImageUrl()`
  - Uporablja Unsplash Source API za pridobivanje relevantnih slik vaj
  - Deterministični seed (crc32) za konsistentne slike iste vaje

- **program.php** in **moj-program.php**: 
  - Če obstaja video URL: prikaže YouTube iframe
  - Če ne obstaja video URL: prikaže lepo sliko iz Unsplash

- **style.css**: Dodani stil za `.exercise-image-embed`
  - Zaobljeni robovi, senca, hover efekt

### Rezultat
Vse vaje imajo sedaj konsistenten in lep prikaz - bodisi video iframe ali slika.

---

## 3. Avtomatično iskanje najbližjih fitnes centrov

### Problem
Fitnes centri so bili hardkodirani in niso bili povezani z uporabnikovo lokacijo.

### Rešitev
- **includes/functions.php**: 
  - Dodana funkcija `getNearbyFitnessCenters()` - uporablja Overpass API
  - Dodana funkcija `calculateDistance()` - Haversine formula za izračun razdalje

- **api/get-nearby-centers.php**: Nov API endpoint
  - Prejme koordinate uporabnika
  - Vrne 5 najbližjih fitnes centrov z razdaljami

- **fitnes-centri.php**: 
  - Avtomatično pridobi lokacijo uporabnika (Geolocation API)
  - Dinamično naloži fitnes centre preko AJAX
  - Prikaže jih na karti z markerji
  - Prikaže seznam z razdaljami

### Kako deluje
1. Brskalnik zahteva dovoljenje za lokacijo
2. JavaScript pošlje koordinate na PHP API
3. PHP uporabi Overpass API za iskanje fitnes centrov v radiju 10 km
4. Izračuna razdalje z Haversine formulo
5. Sortira in vrne 5 najbližjih
6. Prikaže na Leaflet karti

### Rezultat
Uporabnik vidi 5 najbližjih fitnes centrov glede na svojo lokacijo, z razdaljami in prikazom na karti.

---

## 4. Google Login implementacija

### Implementirano
- **google/google-login.php**: Začne OAuth 2.0 flow
  - Generira state token za CSRF zaščito
  - Preusmeri na Google OAuth

- **google/google-callback.php**: Obdela Google callback
  - Preveri state token
  - Zamenja authorization code za access token
  - Pridobi podatke uporabnika iz Google API
  - Če uporabnik obstaja: prijavi ga
  - Če uporabnik ne obstaja: ustvari nov račun in prijavi ga
  - Prenese profilno sliko iz Google

- **login.php**: Dodan gumb "Prijavi se z Google"
  - Lepe ikone in stilizacija

- **migrations/add_google_id.sql**: SQL migracija
  - Dodaja `google_id` stolpec v `uporabniki` tabelo

- **GOOGLE_LOGIN_SETUP.md**: Navodila za nastavitev
  - Korak za korakom navodila
  - Kako dobiti Google OAuth credentials
  - Kako konfigurirati aplikacijo

### Kaj je potrebno
1. Google Cloud Console projekt
2. OAuth 2.0 Client ID in Secret
3. Nastavitev authorized redirect URIs
4. Posodobitev `config.php` z credentials
5. Zažene SQL migracijo za `google_id` stolpec

### Rezultat
Uporabniki se lahko prijavijo z Google računom - hitro, varno, brez potrebe po geslu.

---

## Datoteke, ki so bile spremenjene

### PHP datoteke
- `db.php` - UTF-8 kodiranje
- `includes/functions.php` - Nove funkcije za fitnes centre in slike vaj
- `program.php` - Popravljen encoding, izboljšan prikaz vaj
- `moj-program.php` - Popravljen encoding, izboljšan prikaz vaj
- `fitnes-centri.php` - Avtomatično iskanje fitnes centrov
- `login.php` - Popravljena kompatibilnost gesel
- `moji-programi.php`, `trener.php`, `programi.php`, `index.php`, `checkout.php` - Popravljen encoding

### Nove datoteke
- `api/get-nearby-centers.php` - API endpoint za fitnes centre
- `google/google-login.php` - Google OAuth initiator
- `google/google-callback.php` - Google OAuth handler
- `migrations/add_google_id.sql` - Database migracija
- `GOOGLE_LOGIN_SETUP.md` - Navodila za Google Login
- `FITNES_CENTRI_EXPLANATION.md` - Razlaga fitnes centrov
- `CHANGES_SUMMARY.md` - Ta datoteka

### CSS
- `assets/css/style.css` - Dodani stil za `.exercise-image-embed` in `.gym-marker`

---

## Testiranje

### Encoding
1. Odprite `/program.php?id=8`
2. Preverite, da se slovenski znaki prikazujejo pravilno

### Prikaz vaj
1. Odprite `/program.php?id=1`
2. Preverite, da se vaje z video prikazujejo kot iframe
3. Preverite, da se vaje brez video prikazujejo kot slike

### Fitnes centri
1. Odprite `/fitnes-centri.php`
2. Dovolite dostop do lokacije
3. Preverite, da se prikažejo najbližji fitnes centri
4. Preverite, da se prikažejo na karti

### Google Login
1. Nastavite Google OAuth credentials v `config.php`
2. Zaženite SQL migracijo
3. Odprite `/login.php`
4. Kliknite "Prijavi se z Google"
5. Preverite, da se prijava izvede uspešno

---

## Opombe

- Vse spremembe so kompatibilne z obstoječo kodo
- Fallback mehanizmi so implementirani za vse nove funkcionalnosti
- Vse tehnologije so brezplačne in ne potrebujejo API ključev (razen Google Login)
- Encoding spremembe so povratno kompatibilne

