# AGENTS.md

Modulární webová aplikace postavená na Nette frameworku s Nextras ORM, Vite a Tailwind CSS. Běží v prostředí DDEV. Aplikace obsahuje veřejnou část (`Front`), legacy admin část (`Admin`) a nový admin modul (`AdminCore`).

## Stack

- **PHP 8.4+**, Nette (application, DI, forms, database, security, …), Latte šablony
- **Nextras ORM + DBAL** – přístup k databázi přes entity
- **Nextras Migrations** – správa schématu DB pomocí SQL souborů
- **Vite + Tailwind CSS + Nette Assets** – sestavení frontendových assetů
- **Tracy** – ladění a logování chyb
- **PHPStan** – statická analýza (level 4)
- **Nette Tester** – testy
- **Admin CLI nástroje** – správa admin uživatelů a údržba activity logu

## Adresářová struktura

```
app/
  Bootstrap.php                       # inicializace Nette DI kontejneru
  Core/
    RouterFactory.php                 # definice routeru (Front + Admin + AdminCore)
  Model/
    Orm/                              # Nextras ORM entity, repozitáře, mappery
  Presentation/                       # Front/Admin/Error presentery a Latte šablony
  AdminCore/
    Command/                          # aplikační commandy (např. admin user management)
    Presentation/                     # nový admin modul (App\AdminCore\Presentation\...)
    Model/
    Security/
    assets/
    bin/admin.php                     # interní admin CLI entrypoint
assets/                               # hlavní frontend soubory (Vite entry: main.js)
bin/
  migrations.php                      # DB migrace entrypoint
  admin.php                           # wrapper na app/AdminCore/bin/admin.php
config/
  common.neon                         # sdílená konfigurace
  services.neon                       # registrace služeb
  admin.neon                          # konfigurace admin modulu
  local.neon                          # lokální přístupy k DB (není ve VCS)
  local.example.neon                  # šablona lokální konfigurace
migrations/
  structures/                         # DDL migrace
  basic-data/                         # základní data
  dummy-data/                         # testovací / seed data
tests/
  bootstrap.php
  Core/                               # Nette Tester (.phpt)
www/                                  # document root (index.php)
```

## Důležité příkazy

```bash
# Spuštění prostředí
ddev start
ddev describe

# Instalace závislostí
ddev composer install
ddev npm install

# Sestavení assetů
ddev npm run build          # produkce
ddev npm run dev            # dev server (HMR na https://bestapp.ddev.site:5173)

# Migrace
ddev php bin/migrations.php structures basic-data          # produkce
ddev php bin/migrations.php structures basic-data dummy-data          # vývoj
ddev php bin/migrations.php structures basic-data dummy-data --reset  # reset DB

# Statická analýza
ddev php vendor/bin/phpstan

# Testy
ddev php vendor/bin/tester tests -s
ddev php vendor/bin/tester tests/Core/RouterFactory.phpt -s
ddev composer tester

```

## Konvence

- Namespace: `App\` → `app/` (PSR-4)
- Presentery (Front/Admin): `App\Presentation\<Modul>\<Jméno>Presenter`
- Presentery (AdminCore): `App\AdminCore\Presentation\<Modul>\<Jméno>Presenter`
- Konfigurační soubor `config/local.neon` není verzován – vzor viz `config/local.example.neon`
- Latte šablony mají `strictParsing: yes`
- Nette Assets: Vite mapping s dev serverem, tag `{asset}`
- Vite entrypointy: `main.js` a `../app/AdminCore/assets/admin.js`
- Composer příkazy vždy spouštět přes DDEV (`ddev composer ...`), ne přímo `composer ...`
- PHP skripty vždy spouštět přes DDEV (`ddev php ...`), ne přímo `php ...`
- Modul `Admin` používá Bootstrap 5 (včetně UI komponent) a nepoužívá Tailwind CSS
- Pro modul `Admin` používat samostatný Vite entrypoint `../app/AdminCore/assets/admin.js` a v admin layoutu includovat `{asset '../app/AdminCore/assets/admin.js'}` kvůli HMR/full reload
- Router používá moduly `AdminCore` (`/admin/core/...`), `Admin` (`/admin/...`) a `Front`