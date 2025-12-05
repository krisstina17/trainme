# TrainMe - Platforma za naročanje na fitnes programe

Kompletna spletna aplikacija za naročanje na fitnes programe z modernim dizajnom.

## Funkcionalnosti

### Osnovne funkcionalnosti
- ✅ Registracija in prijava uporabnikov
- ✅ Google OAuth prijava
- ✅ Pregled profilov trenerjev in njihovih fitnes programov
- ✅ Iskanje in filtriranje trenerjev po tipu programa
- ✅ Naročnina na mesečni program trenerja
- ✅ Plačilo mesečne članarine (Stripe/PayPal simulacija)
- ✅ Dostop do vsebin programa
- ✅ Prikaz grafov napredka uporabnika (Chart.js)
- ✅ Ocenjevanje in komentiranje trenerjev
- ✅ Prikaz bližnjih fitnes centrov (Google Maps API)
- ✅ Generiranje QR kod za dostop do programa

### Bonus funkcionalnosti
- ✅ Iskanje in sortiranje podatkov
- ✅ Hash gesel (bcrypt)
- ✅ Interaktivni grafikon (Chart.js)
- ✅ Geolocation API
- ✅ LocalStorage uporaba
- ✅ AJAX/Fetch API
- ✅ Lazy loading slik
- ✅ Različni tipi uporabnikov (uporabnik/trener)
- ✅ Docker kontejnerizacija
- ✅ Responsive dizajn

## Tehnologije

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Chart.js
- **Backend**: PHP 8.3
- **Podatkovna baza**: MySQL 8.0
- **Container**: Docker & Docker Compose

## Namestitev

### Zahteve
- Docker in Docker Compose
- Git

### Koraki

1. Klonirajte repozitorij:
```bash
git clone <repository-url>
cd trainme
```

2. Zaženite Docker kontejnerje:
```bash
docker-compose up -d
```

3. Počakajte, da se kontejnerji zaženejo (približno 30 sekund)

4. Odprite brskalnik in pojdite na:
   - Aplikacija: http://localhost:8000
   - phpMyAdmin: http://localhost:8001

5. Podatkovna baza je že nastavljena z vzorčnimi podatki.

## Konfiguracija

### Google OAuth
1. Ustvarite Google OAuth aplikacijo na [Google Cloud Console](https://console.cloud.google.com/)
2. Dodajte `http://localhost:8000/google/google-callback.php` kot redirect URI
3. Posodobite `data/www/includes/config.php` z vašimi Google Client ID in Secret

### Google Maps API
1. Ustvarite API ključ na [Google Cloud Console](https://console.cloud.google.com/)
2. Omogočite Maps JavaScript API
3. Posodobite `data/www/fitnes-centri.php` z vašim API ključem

### Email konfiguracija
Posodobite email nastavitve v `data/www/includes/config.php` za pošiljanje emailov.

## Struktura projekta

```
trainme/
├── data/
│   ├── mysql/          # MySQL podatki
│   └── www/            # PHP aplikacija
│       ├── includes/    # PHP helper fajli
│       ├── assets/     # CSS, JS, slike
│       ├── google/     # Google OAuth
│       ├── trainer/    # Trener dashboard
│       └── *.php       # Glavne strani
├── docker-compose.yml
└── README.md
```

## Uporaba

### Registracija uporabnika
1. Pojdite na `/register.php`
2. Izpolnite obrazec ali uporabite Google prijavo
3. Po registraciji boste preusmerjeni na seznam programov

### Naročilo programa
1. Preglejte programe na `/programi.php`
2. Kliknite na program za več informacij
3. Kliknite "Naroči se" in izberite način plačila
4. Po uspešnem plačilu imate dostop do programa

### Spremljanje napredka
1. Pojdite na `/napredek.php`
2. Dodajte meritev teže
3. Oglejte si graf napredka

### Trener dashboard
1. Prijavite se z računom trenerja
2. Pojdite na `/trainer/dashboard.php`
3. Dodajte ali uredite programe

## Testni podatki

Aplikacija vsebuje vzorčne podatke:
- Uporabniki: maja@example.com, tina@example.com, itd.
- Trenerji: luka@example.com, jure@example.com, itd.
- Programi: Moč za začetnike, CrossFit Osnove, itd.

## Razvoj

### Dodajanje novih funkcionalnosti
1. Ustvarite novo PHP datoteko v `data/www/`
2. Dodajte potrebne SQL poizvedbe
3. Posodobite navigacijo v `header.php`
4. Dodajte CSS stile v `assets/css/style.css`

### Debugging
- PHP napake: Preverite Docker loge z `docker-compose logs spletni-streznik`
- MySQL napake: Preverite Docker loge z `docker-compose logs mysql`

## Licenca

Ta projekt je izdelan za izobraževalne namene.

## Avtor

TrainMe Development Team

