# 🔒 Varnostna poprava - Google OAuth Credentials

## ⚠️ KRITIČNO: Google OAuth credentials so bili izpostavljeni na GitHub!

### Kaj moraš narediti TAKOJ:

## 1. Rotiraj Google OAuth Credentials (OBVEZNO!)

### Korak 1: Odstrani stari Client Secret
1. Pojdi na [Google Cloud Console](https://console.cloud.google.com/)
2. APIs & Services → Credentials
3. Klikni na tvoj OAuth 2.0 Client ID
4. Klikni "Reset Secret" ali "Delete" za stari secret
5. Ustvari nov Client Secret
6. **Kopiraj nov Client Secret** (ne shranjuj ga v Git!)

### Korak 2: Ustvari .env datoteko
V korenu projekta (`C:\Users\Kristina\Desktop\trainme\.env`) ustvari datoteko:

```env
GOOGLE_CLIENT_ID=67319301234-hgrtprv068b0ebp6nv3071ts3547mfm8.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TVOJ_NOV_CLIENT_SECRET_TUKAJ
```

**POMEMBNO:** `.env` datoteka je že v `.gitignore` in se NE bo commit-ala v Git!

## 2. Očisti Git zgodovino (odstrani credentials iz zgodovine)

### Možnost A: BFG Repo-Cleaner (priporočeno)
```bash
# Prenesi BFG: https://rtyley.github.io/bfg-repo-cleaner/
java -jar bfg.jar --replace-text passwords.txt
git reflog expire --expire=now --all
git gc --prune=now --aggressive
git push --force
```

### Možnost B: git-filter-repo (alternativa)
```bash
pip install git-filter-repo
git filter-repo --replace-text <(echo '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==>REMOVED')
git push --force
```

### Možnost C: Ročno (če nič drugega ne deluje)
```bash
# Ustvari nov branch brez zgodovine
git checkout --orphan new-main
git add .
git commit -m "Initial commit - cleaned history"
git branch -D main
git branch -m main
git push -f origin main
```

## 3. Preveri, ali so credentials še v Git

```bash
git log --all --full-history --source -- "**/config.php"
git log -p --all -- "**/config.php" | grep -i "GOCSPX"
```

## 4. Posodobi config.php

`config.php` je sedaj posodobljen, da bere iz `.env` datoteke. Če `.env` ne obstaja, uporabi placeholder vrednosti.

## 5. Testiraj aplikacijo

1. Ustvari `.env` datoteko z novimi credentials
2. Preveri, ali Google prijava deluje
3. Preveri, ali so stari credentials odstranjeni iz kode

## 6. Prepreči prihodnje izpostavitve

- ✅ `.env` je v `.gitignore`
- ✅ `config.php` bere iz `.env`
- ✅ Nikoli ne commit-aj datotek z občutljivimi podatki
- ✅ Preveri `git status` pred `git add .`
- ✅ Uporabi `git diff` preden commit-aš

## Pomembno

- Stari Client Secret je kompromitiran - **MORAŠ** ga rotirati
- Če ne rotiraš credentials, lahko kdorkoli uporablja tvoj Google OAuth
- Git zgodovina vsebuje stari secret - moraš jo očistiti

## Hitra pomoč

Če ne veš, kako narediti korake zgoraj:
1. **Takoj rotiraj Client Secret** v Google Cloud Console
2. Ustvari `.env` datoteko z novimi credentials
3. Za čiščenje Git zgodovine poišči pomoč ali uporabi GitHub's "Secret scanning" feature

