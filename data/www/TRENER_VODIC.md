# Vodič za trenerje - TrainMe

## Kako se prijaviti kot trener?

### 1. Registracija kot trener

1. Pojdite na stran **Registracija** (`/register.php`)
2. Izpolnite obrazec z vašimi podatki:
   - Ime
   - Priimek
   - Email
   - Geslo (najmanj 6 znakov)
3. **Pomembno**: Izberite **"Trener"** kot vašo vlogo
4. Kliknite "Registriraj se"

### 2. Prijava

1. Pojdite na stran **Prijava** (`/login.php`)
2. Vnesite vaš email in geslo
3. Kliknite "Prijavi se"

**Opomba**: Če ste se registrirali z Google, uporabite gumb "Prijavi se z Google".

---

## Kako ustvariti program?

### 1. Odprite Dashboard trenerja

Po prijavi kot trener:
- V navigaciji kliknite **"Dashboard"**
- Ali pa pojdite direktno na `/trainer/dashboard.php`

### 2. Dodajte nov program

1. Na dashboardu kliknite gumb **"+ Dodaj nov program"**
2. Izpolnite obrazec:
   - **Naziv programa**: npr. "Pilates za začetnike"
   - **Opis**: Podroben opis programa (kaj vključuje, za koga je namenjen, itd.)
   - **Cena (€)**: Mesečna cena programa
   - **Trajanje (dni)**: Koliko dni traja program

### 3. Dodajte vaje

V istem obrazcu lahko dodate vaje za program:

1. Za vsako vajo izpolnite:
   - **Naziv vaje**: npr. "Plank"
   - **Opis vaje**: Navodila za izvedbo (korak za korakom)
   - **Video URL** (opcijsko): YouTube povezava do videoposnetka vaje

2. Za dodajanje več vaj kliknite **"+ Dodaj vajo"**

3. Za odstranitev vaje kliknite **"Odstrani"** (program mora imeti vsaj eno vajo)

### 4. Shranite program

1. Kliknite **"Shrani program"**
2. Program bo shranjen in bo viden uporabnikom na strani `/programi.php`

---

## Kako urediti obstoječi program?

1. Na dashboardu v razdelku **"Moji programi"** kliknite **"Uredi"** ob programu
2. Spremenite podatke programa ali vaje
3. Kliknite **"Shrani program"**

**Pomembno**: Ko shranite program, se vse obstoječe vaje izbrišejo in se dodajo nove iz obrazca. Če želite ohraniti vaje, jih ne odstranjujte iz obrazca.

---

## Kaj lahko vidite na dashboardu?

### Statistike

- **Aktivne naročnine**: Koliko uporabnikov je trenutno naročenih na vaše programe
- **Unikatni uporabniki**: Koliko različnih uporabnikov ima naročnino
- **Povprečna ocena**: Povprečna ocena vaših programov
- **Ocen**: Skupno število ocen

### Moji programi

Tabela vseh vaših programov z:
- Nazivom programa
- Ceno
- Trajanjem
- Številom naročnin
- Povprečno oceno
- Gumbom za urejanje

---

## Kako videti ocene uporabnikov?

1. Na dashboardu kliknite **"Poglej ocene"**
2. Videli boste vse ocene in komentarje uporabnikov o vaših programih

---

## Nasveti za dobre programe

1. **Podroben opis**: Napišite jasen opis programa, za koga je namenjen in kaj uporabniki pridobijo
2. **Kvalitetne vaje**: Dodajte jasne navodila za vsako vajo
3. **Video vaje**: Če je mogoče, dodajte YouTube videoposnetke za boljšo razlago
4. **Primerna cena**: Nastavite pošteno ceno glede na vsebino programa
5. **Trajanje**: Nastavite realistično trajanje programa

---

## Pomoč

Če imate težave:
- Preverite, ali ste prijavljeni kot trener (v navigaciji mora biti vidna povezava "Dashboard")
- Če nimate dostopa do dashboarda, preverite v phpMyAdmin, ali imate `tk_vloga = 2` v tabeli `uporabniki`
- Za spremembo vloge v bazi: `UPDATE uporabniki SET tk_vloga = 2 WHERE email = 'vas@email.com';`

