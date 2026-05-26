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

## B) Copy **all** local data (cars, sales, customers, users, …) — recommended

**Do not put data in migration files** — migrations are for schema only. Use the seed file instead.

### 1. On XAMPP (with `.env.local` pointing to `127.0.0.1`)

```powershell
cd c:\xampp\htdocs\ramlewheels\ramlewheels
php bin/console app:build-railway-seed
```

Creates `data/railway_seed.json` with **every table** (users, cars, sales, services, documents, bookings, etc.).

### 2. Commit and push

```powershell
git add data/railway_seed.json
git commit -m "Full Railway seed from local DB"
git push
```

### 3. Railway variables

```
DATABASE_URL=${{MySQL.MYSQL_URL}}
APP_SECRET=...
DEFAULT_URI=https://YOUR-APP.up.railway.app
IMPORT_RAILWAY_SEED=force
```

Use **`force`** if you already deployed once (replaces Railway data with your local copy).

Redeploy once. Logs should show `Imported N rows` per table.

### 4. After it works

Remove `IMPORT_RAILWAY_SEED` from Railway variables.

Log in with your **same local username/password**. All inventory/sales should match local.

---

## Car images (no Railway Volume required)

Railway’s **premium volume is not required** for inventory photos if you commit them to git.

1. Car photos live in `public/uploads/cars/` (already in this repo).
2. Each **deploy** bakes those files into the Docker image (free).
3. After deploy, test: `https://YOUR-APP.up.railway.app/uploads/cars/vios1-6a0f48a02bb7b.jpg`  
   If that returns **404**, push your latest git commit and redeploy.

**New uploads** (added in the admin UI) are saved on the container disk. They work until the next **redeploy**, then only git-committed images remain. To keep new photos long-term without a volume: commit new files under `public/uploads/cars/` and redeploy, or add Cloudinary later.

**Multiple image upload (413 error):** fixed via `client_max_body_size` and PHP `post_max_size` in the Dockerfile — redeploy to apply.

## Cost tips

- Use **only** App + MySQL (no Redis, no extra services).
- Remove `IMPORT_RAILWAY_SEED` after import.
- **No paid Railway volume** needed for seeded car images in git.
