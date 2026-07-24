Tato aplikace je skeleton modulární webové aplikace využívající kombinaci webových technologií - DDEV, Nette, Vite, Tailwind, PHPstan.

Pro spouštění PHP skriptů v projektu používej `ddev php ...`.


# DDEV
`ddev start`  
`ddev describe`  

Adminer: https://bestapp.ddev.site:9101/

PHPstan: `ddev php vendor/bin/phpstan`


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

Migrace (SQL) jsou v adresáři `migrations/`:
- `structures/` - schéma databáze
- `basic-data/` - základní data potřebná i v produkci
- `dummy-data/` - volitelná testovací data (např. demo `article` seed)

Spuštění migrací (produkce / minimum):
```bash
ddev php bin/migrations.php structures basic-data`
```

Spuštění migrací (vývoj s demo daty):
```bash
ddev php bin/migrations.php structures basic-data dummy-data`
```

Reset DB a kompletní znovunahrání:
```bash
ddev php bin/migrations.php structures basic-data dummy-data --reset`
```



# Admin CLI

```bash
ddev php bin/admin.php admin:user:create
ddev php bin/admin.php admin:user:set-password --username=admin --password="new-strong-password"
ddev php bin/admin.php admin:activity-log:cleanup --older-than="6 months"

```