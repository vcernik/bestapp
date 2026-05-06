# AGENTS.md

Modulární webová aplikace postavená na Nette frameworku s Nextras ORM, Vite a Tailwind CSS. Běží v prostředí DDEV.

## Stack

- **PHP 8.2+**, Nette (application, DI, forms, database, security, …), Latte šablony
- **Nextras ORM + DBAL** – přístup k databázi přes entity
- **Nextras Migrations** – správa schématu DB pomocí SQL souborů
- **Vite + Tailwind CSS** – sestavení frontendových assetů
- **Tracy** – ladění a logování chyb
- **PHPStan** – statická analýza (level 4)
- **Nette Tester** – testy

## Adresářová struktura

```
app/
  Bootstrap.php               # inicializace Nette DI kontejneru
  Core/RouterFactory.php      # definice routeru
  Model/Orm/                  # Nextras ORM entity, repozitáře, mappery
  Presentation/               # presentery a Latte šablony
assets/                       # zdrojové frontend soubory (Vite entry)
config/
  common.neon                 # sdílená konfigurace
  local.neon                  # lokální přístupy k DB (není ve VCS)
migrations/
  structures/                 # DDL migrace
  basic-data/                 # základní data
  dummy-data/                 # testovací data
www/                          # document root (index.php)
```

## Důležité příkazy

```bash
# Spuštění prostředí
ddev start

# Instalace závislostí
ddev composer install
ddev npm install

# Sestavení assetů
ddev npm run build          # produkce
ddev npm run dev            # dev server (HMR na https://bestapp.ddev.site:5173)

# Migrace
ddev exec php bin/migrations.php structures basic-data          # produkce
ddev exec php bin/migrations.php structures basic-data dummy-data          # vývoj
ddev exec php bin/migrations.php structures basic-data dummy-data --reset  # reset DB

# Statická analýza
ddev composer phpstan app --level 4

# Testy
ddev composer tester
```

## Konvence

- Namespace: `App\` → `app/` (PSR-4)
- Presentery: `App\Presentation\<Modul>\<Jméno>Presenter`
- Konfigurační soubor `config/local.neon` není verzován – vzor viz `config/local.example.neon`
- Latte šablony mají `strictParsing: yes`
- Nette Assets: Vite mapping s dev serverem, tag `{asset}`
- Composer příkazy vždy spouštět přes DDEV (`ddev composer ...`), ne přímo `composer ...`
- Modul `Admin` používá Bootstrap 5 (včetně UI komponent) a nepoužívá Tailwind CSS
- Pro modul `Admin` používat samostatný Vite entrypoint `assets/admin.js` a v admin layoutu includovat `{asset 'admin.js'}` kvůli HMR/full reload