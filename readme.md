Tato aplikace je skeleton modulární webové aplikace využívající kombinaci webových technologií - DDEV, Nette, Vite, Tailwind, PHPstan.

Pro spouštění PHP skriptů v projektu používej `ddev php ...`.


# DDEV
`ddev start`  
`ddev describe`  

Adminer: https://bestapp.ddev.site:9101/

PHPstan: `ddev composer phpstan app --level 4`


# Vite - assets build
`ddev npm install`  
`ddev npm run build`  

# Nette Tester
Testy jsou připravené přes Nette Tester v adresáři `tests/`.

Spuštění všech testů:
`ddev php vendor/bin/tester tests -s`

Spuštění jednoho testu:
`ddev php vendor/bin/tester tests/Core/RouterFactory.phpt -s`

Alternativně lze použít Composer script:
`ddev composer tester`

# Nextras ORM + migrace
Šablona lokální konfigurace je v `config/local.example.neon`.
Lokální DB přístupy patří do `config/local.neon` (soubor je v `.gitignore`, tedy neverzovaný).

Migrace (SQL) jsou v adresáři `migrations/`:
- `structures/` - schéma databáze
- `basic-data/` - základní data potřebná i v produkci
- `dummy-data/` - volitelná testovací data (např. demo `article` seed)

Spuštění migrací (produkce / minimum):
`ddev php bin/migrations.php structures basic-data`

Spuštění migrací (vývoj s demo daty):
`ddev php bin/migrations.php structures basic-data dummy-data`

Reset DB a kompletní znovunahrání:
`ddev php bin/migrations.php structures basic-data dummy-data --reset`

