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

## 📝 Uporaba

### Za uporabnike
- Registracija/Prijava (tudi z Google)
- Pregled in naročilo programov
- Spremljanje napredka z grafi
- Ocenjevanje trenerjev

### Za trenerje
- Dashboard za upravljanje programov
- Dodajanje in urejanje vaj
- Pregled ocen in komentarjev

## 🐛 Debugging

```bash
# PHP napake
docker-compose logs spletni-streznik

# MySQL napake
docker-compose logs mysql
```

## 📄 Licenca

Ta projekt je izdelan za izobraževalne namene.
