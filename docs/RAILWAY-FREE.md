# Railway free tier — quick setup (no paid tools)

Use **one** Railway app + MySQL (included in trial/hobby credits). No SQL import, no sync URL, no extra services.

## A) Fastest: new admin only (30 seconds)

Railway → **app** → **Variables**:

```
APP_SECRET=any-long-random-string
DEFAULT_URI=https://YOUR-APP.up.railway.app
DATABASE_URL=${{MySQL.MYSQL_URL}}
ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=YourPassword123
ADMIN_USERNAME=Elmar
```

Redeploy → log in on Railway with **ADMIN_USERNAME** + **ADMIN_PASSWORD**.

---

## B) Copy your local logins (recommended, still free)

### 1. On XAMPP (once)

```powershell
cd c:\xampp\htdocs\ramlewheels\ramlewheels
php bin/console app:build-railway-seed
```

Creates `data/railway_seed.json` (users + customers, passwords stay hashed).

### 2. Commit and push

```powershell
git add data/railway_seed.json
git commit -m "Add Railway seed data"
git push
```

### 3. Railway variables

```
DATABASE_URL=${{MySQL.MYSQL_URL}}
APP_SECRET=...
DEFAULT_URI=https://YOUR-APP.up.railway.app
IMPORT_RAILWAY_SEED=1
```

Redeploy once. Check logs for: `Imported X users`.

### 4. After it works

Remove `IMPORT_RAILWAY_SEED` (so redeploys do not try again).

Log in with your **same local username/password**.

---

## Cost tips

- Use **only** App + MySQL (no Redis, no extra services).
- Remove `IMPORT_RAILWAY_SEED` after first import.
- Skip `/dev/sync-railway` unless you need a full DB clone (dev only).

Cars/inventory are not in the seed file; add them again in the UI or extend the seed later.
