# Google Login Setup Guide

## Kaj je potrebno za Google Login

### 1. Google Cloud Console Setup

1. Pojdite na [Google Cloud Console](https://console.cloud.google.com/)
2. Ustvarite nov projekt ali izberite obstoječega
3. Omogočite **Google+ API** (ali **Google Identity API**)
4. Pojdite na **Credentials** → **Create Credentials** → **OAuth 2.0 Client ID**
5. Izberite **Web application**
6. Nastavite:
   - **Name**: TrainMe (ali poljubno ime)
   - **Authorized JavaScript origins**: 
     - `http://localhost:8000` (za lokalni razvoj)
     - `https://yourdomain.com` (za produkcijo)
   - **Authorized redirect URIs**:
     - `http://localhost:8000/google/google-callback.php` (za lokalni razvoj)
     - `https://yourdomain.com/google/google-callback.php` (za produkcijo)

7. Kopirajte **Client ID** in **Client Secret**

### 2. Konfiguracija aplikacije

Odprite `data/www/includes/config.php` in posodobite:

```php
define('GOOGLE_CLIENT_ID', 'VAŠ_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'VAŠ_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/google/google-callback.php');
```

### 3. Database Migration

Zaženite SQL migracijo za dodajanje `google_id` stolpca:

```bash
docker-compose exec mysql mysql -uroot -psuperVarnoGeslo trainme_db < data/www/migrations/add_google_id.sql
```

Ali ročno v phpMyAdmin:
```sql
ALTER TABLE uporabniki 
ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email;
```

### 4. Testiranje

1. Odprite `http://localhost:8000/login.php`
2. Kliknite na "Prijavi se z Google"
3. Izberite Google račun
4. Dovolite dostop do podatkov
5. Preusmeritev nazaj na aplikacijo

## Kako deluje

1. **google-login.php**: Začne OAuth flow, preusmeri uporabnika na Google
2. **google-callback.php**: Prejme avtorizacijsko kodo, zamenja za access token, pridobi podatke uporabnika
3. Če uporabnik obstaja: prijavi ga
4. Če uporabnik ne obstaja: ustvari nov račun in ga prijavi

## Varnost

- **State parameter**: Zaščita pred CSRF napadi
- **HTTPS**: V produkciji uporabite HTTPS
- **Token validation**: Preverjanje veljavnosti access tokena
- **Email verification**: Google že verifikira e-poštne naslove

## Opombe

- Uporabniki, ki se prijavijo z Google, imajo naključno geslo (ne potrebujejo ga)
- Profilna slika se avtomatično prenese iz Google računa
- `google_id` se shrani za povezavo računov

