# TrainMe

Spletna platforma za naročanje na fitnes programe z integracijo plačil in spremljanjem napredka.

## 🚀 Hitra namestitev

### Zahteve
- Docker & Docker Compose
- Git

### Namestitev

```bash
# 1. Kloniraj repozitorij
git clone <repository-url>
cd trainme

# 2. Zaženi Docker kontejnerje
docker-compose up -d

# 3. Počakaj ~30 sekund in odpri brskalnik
# Aplikacija: http://localhost:8000
# phpMyAdmin: http://localhost:8001
```

Aplikacija je pripravljena z vzorčnimi podatki.

## ⚙️ Konfiguracija

Ustvari `data/www/.env` datoteko za občutljive podatke:

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Stripe
STRIPE_PUBLIC_KEY=pk_test_your_key
STRIPE_SECRET_KEY=sk_test_your_key

# Email (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM_EMAIL=noreply@trainme.com
SMTP_FROM_NAME=TrainMe Platform
```

> **Opomba:** `.env` datoteka je že v `.gitignore` in se ne bo commit-ala v Git.

### Google OAuth
1. Ustvari aplikacijo na [Google Cloud Console](https://console.cloud.google.com/)
2. Dodaj redirect URI: `http://localhost:8000/google/google-callback.php`
3. Dodaj credentials v `.env`

### Stripe Plačila
1. Ustvari račun na [Stripe Dashboard](https://dashboard.stripe.com/)
2. Kopiraj Test API ključe
3. Dodaj ključe v `.env`

## 📁 Struktura projekta

```
trainme/
├── data/
│   ├── mysql/          # MySQL podatki
│   └── www/            # PHP aplikacija
│       ├── includes/    # Helper funkcije
│       ├── assets/     # CSS, JS, slike
│       ├── api/        # API endpoints
│       ├── google/     # Google OAuth
│       └── trainer/    # Trener dashboard
├── docker-compose.yml
└── README.md
```

## 🛠️ Tehnologije

- **Backend:** PHP 8.3, MySQL 8.0
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Chart.js
- **Container:** Docker & Docker Compose
- **Integracije:** Stripe, Google OAuth, PHPMailer

## ✨ Funkcionalnosti

### 🔐 Avtentikacija in uporabniki
- **Registracija in prijava** - Tradicionalna registracija z emailom in geslom
- **Google OAuth** - Hitra prijava z Google računom
- **Upravljanje profila** - Posodabljanje osebnih podatkov in profilne slike
- **Vloge uporabnikov** - Ločevanje med navadnimi uporabniki in trenerji

### 💪 Programi in naročila
- **Pregled programov** - Iskanje in filtriranje programov po specializaciji
- **Podrobnosti programa** - Prikaz vaj, videov, opisa in informacij o trenerju
- **Naročanje programov** - Enostavno naročanje z izbiro trajanja
- **Stripe plačila** - Varno plačilo s kreditno kartico preko Stripe Elements
- **Dostop do programov** - Osebna stran z vsemi naročenimi programi

### 📊 Spremljanje napredka
- **Vodenje napredka** - Shranjevanje teže in meritev
- **Interaktivni grafi** - Vizualizacija napredka z Chart.js
- **Označevanje opravljenih vaj** - Sledenje napredku skozi program
- **Izvoz podatkov** - Možnost izvoza napredka v PDF

### ⭐ Ocenjevanje in komentarji
- **Ocenjevanje trenerjev** - 1-5 zvezdicna ocena
- **Komentarji** - Pisanje mnenj o trenerjih in programih
- **Pregled ocen** - Prikaz povprečnih ocen za vsakega trenerja

### 🏋️ Trener dashboard
- **Upravljanje programov** - Dodajanje, urejanje in brisanje programov
- **Upravljanje vaj** - Dodajanje vaj z videi, slikami in opisi
- **Pregled ocen** - Pregled vseh ocen in komentarjev
- **Statistike** - Pregled naročil in aktivnosti

### 🗺️ Fitnes centri
- **Geolokacija** - Avtomatično iskanje najbližjih fitnes centrov
- **Interaktivna karta** - Prikaz centrov na Leaflet karti
- **Razdalje** - Izračun razdalje do vsakega centra

### 📱 Dodatne funkcionalnosti
- **QR kode** - Generiranje QR kod za dostop do programov
- **Email obvestila** - Avtomatična obvestila o naročilih in spremembah
- **Responsive dizajn** - Optimizirano za vse naprave
- **AJAX/Fetch API** - Dinamično nalaganje podatkov brez osveževanja strani
- **LocalStorage** - Shranjevanje napredka lokalno v brskalniku

## 📝 Uporaba

### Za uporabnike
1. **Registracija/Prijava** - Ustvari račun ali se prijavi z Google
2. **Iskanje programov** - Preglej in filtriraj programe na `/programi.php`
3. **Naročilo** - Izberi program in opravi plačilo
4. **Vadba** - Dostopaj do vaj in sledi napredku na `/moj-program.php`
5. **Napredek** - Vnesi meritve in si oglej grafe na `/napredek.php`
6. **Ocenjevanje** - Oceni trenerje in napiši komentarje

### Za trenerje
1. **Prijava** - Prijavi se z računom trenerja
2. **Dashboard** - Pojdi na `/trainer/dashboard.php`
3. **Ustvari program** - Dodaj nov program z osnovnimi informacijami
4. **Dodaj vaje** - Ustvari vaje z videi, slikami in opisi
5. **Pregled** - Spremljaj ocene in komentarje uporabnikov

## 🐛 Debugging

```bash
# PHP napake
docker-compose logs spletni-streznik

# MySQL napake
docker-compose logs mysql
```

## 📄 Licenca

Ta projekt je izdelan za izobraževalne namene.
