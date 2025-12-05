# Git Ukazi za Commit in Push

## Korak 1: Preveri status
```bash
git status
```

## Korak 2: Dodaj vse spremembe
```bash
git add .
```

## Korak 3: Commit sprememb
```bash
git commit -m "Stripe integracija - pravo plačilo z vnosom kartice

- Implementirana prava Stripe integracija z Elements
- Dodan payment-success.php z feedback
- Popravljene varnostne napake
- Izbrisane nepotrebne dokumentacijske datoteke
- Posodobljen README.md in CHANGES_SUMMARY.md"
```

## Korak 4: Push na GitHub
```bash
git push origin main
```

---

## ALI - Vse naenkrat (kopiraj in prilepi):

```bash
git add .
git commit -m "Stripe integracija - pravo plačilo z vnosom kartice

- Implementirana prava Stripe integracija z Elements
- Dodan payment-success.php z feedback
- Popravljene varnostne napake
- Izbrisane nepotrebne dokumentacijske datoteke
- Posodobljen README.md in CHANGES_SUMMARY.md"
git push origin main
```

---

## Preverjanje pred push:

### Preveri, da ni .env datoteke:
```bash
git status | grep .env
```
(Ne sme prikazati ničesar)

### Preveri, da ni ključev v datotekah:
```bash
git diff --cached | grep -i "pk_test\|sk_test\|client_secret\|GOCSPX"
```
(Ne sme prikazati ničesar razen placeholder vrednosti)

---

## Če imaš problem:

### Če je branch drugačen:
```bash
git branch
# Če si na drugačnem branchu:
git checkout main
```

### Če imaš konflikte:
```bash
git pull origin main
# Reši konflikte, nato:
git add .
git commit -m "Resolved conflicts"
git push origin main
```

