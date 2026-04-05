Tato aplikace je skeleton a jako testovací prostředí pro kombinaci webových technologií - DDEV, Nette, Vite, Tailwind, PHPstan


# DDEV
`ddev start`  
`ddev describe`  

Adminer: https://bestapp.ddev.site:9101/

PHPstan: `ddev composer phpstan app --level 4`


# Vite - assets build
`ddev npm install`  
`ddev npm run build`  


# Nextras ORM + migrace
Instalované balíčky:
- `nextras/orm`
- `nextras/dbal`
- `nextras/migrations`

Šablona lokální konfigurace je v `config/local.example.neon`.
Lokální DB přístupy patří do `config/local.neon` (soubor je v `.gitignore`, tedy neverzovaný).

Migrace (SQL) jsou v adresáři `migrations/`:
- `structures/` - schéma databáze
- `basic-data/` - základní data potřebná i v produkci
- `dummy-data/` - volitelná testovací data (např. demo `article` seed)

Spuštění migrací (produkce / minimum):
`ddev exec php bin/migrations.php structures basic-data`

Spuštění migrací (vývoj s demo daty):
`ddev exec php bin/migrations.php structures basic-data dummy-data`

Reset DB a kompletní znovunahrání:
`ddev exec php bin/migrations.php structures basic-data dummy-data --reset`

