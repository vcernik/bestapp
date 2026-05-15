# Administrace – návrh struktury a přihlašování

## Architektura a autentizace

Administrace je rozdělena na dvě části:
- **Veřejná část**: Přihlašovací formulář a obnova hesla dostupné nepřihlášeným uživatelům.
- **Neveřejná část**: Obsah administrace dostupný pouze po úspěšném přihlášení.

### Struktura adresářů a souborů

```
app/Presentation/Admin/
├── @layout.base.latte              – základní HTML struktura (DOCTYPE, head, body)
├── @layout.public.latte            – layout pro veřejné presentery (Sign, ForgotPassword)
├── @layout.private.latte           – layout pro přihlášené uživatele (Home, atd.)
├── Public/
│   ├── Sign/
│   │   ├── SignPresenter.php       – přihlášení a odhlášení
│   │   └── in.latte                – formulář přihlášení
│   ├── ForgotPassword/
│   │   ├── ForgotPasswordPresenter.php – obnovení hesla
│   │   ├── request.latte           – žádost o reset hesla
│   │   └── reset.latte             – formulář pro nové heslo
│   └── Accessory/
│       ├── BasePublicPresenter.php – základní presenter pro veřejné presentery
│       ├── SignFormFactory.php
│       └── ForgotPasswordFormFactory.php
├── Home/
│   ├── HomePresenter.php           – dashboard administrace
│   ├── ProfilePresenter.php        – změna vlastního hesla
│   └── default.latte               – obsah dashboardu
└── Accessory/
    ├── BasePrivatePresenter.php    – základní presenter pro přihlášené presentery
    ├── AdminMenuProvider.php
    └── DatagridFactory.php
```

**Presentery – veřejná část (bez požadavku na přihlášení):**
- `app/Presentation/Admin/Public/Sign/SignPresenter.php` – přihlašování, odhlašování
- `app/Presentation/Admin/Public/ForgotPassword/ForgotPasswordPresenter.php` – obnovení hesla
- Oba dědí z `BasePublicPresenter`, který používá `@layout.public.latte`

**Presentery – privátní část (s požadavkem na přihlášení):**
- `app/Presentation/Admin/Home/HomePresenter.php` – hlavní stránka administrace
- Všechny dědí z `BasePrivatePresenter`, který:
    - Zajišťuje kontrolu přihlášení (v startup metodě)
    - Pokud uživatel není přihlášen, přesměruje na přihlašovací stránku
    - Poskytuje data pro layout (menu, user info)

**Authenticator:**
- app/Core/Security/AdminAuthenticator.php – třída implementující Nette\Security\Authenticator, řeší ověření uživatele, blokace, počítadlo pokusů

**Facades (aplikační logika):**
- app/Core/Security/AdminPasswordResetFacade.php – logika vytvoření, validace a uplatnění reset tokenu
- app/Core/Security/AdminPasswordChangeFacade.php – logika změny hesla přihlášeného uživatele
- app/Core/Security/AdminActivityLogger.php – logování aktivit administrace do DB (kdo, kdy, co, data)

**Model:**
- app/Model/Orm/AdminUser.php – entita admin uživatele
- app/Model/Orm/AdminUserRepository.php – repozitář pro práci s tabulkou admin_user
- app/Model/Orm/AdminPasswordReset.php – entita reset tokenu
- app/Model/Orm/AdminPasswordResetRepository.php – repozitář reset tokenů
- app/Model/Orm/AdminActivityLog.php – entita auditního logu administrace
- app/Model/Orm/AdminActivityLogRepository.php – repozitář logů aktivit

**Šablony:**
- app/Presentation/Admin/@layout.base.latte – základní HTML struktura (použito všemi)
- app/Presentation/Admin/@layout.public.latte – layout veřejné části (formuláře bez sidebaru)
- app/Presentation/Admin/@layout.private.latte – layout privátní části (sidebar, menu, přihlášený uživatel)
- app/Presentation/Admin/Public/Sign/in.latte – přihlašovací formulář
- app/Presentation/Admin/Public/ForgotPassword/request.latte – žádost o reset hesla
- app/Presentation/Admin/Public/ForgotPassword/reset.latte – formulář pro nové heslo
- app/Presentation/Admin/Home/default.latte – obsah dashboardu

**Konfigurace:**
- config/admin.neon – registrace služeb, routování administrace, DI pro presentery

**Poznámka:**
Veškeré bezpečnostní mechanismy (hashování, ověřování hesla) jsou řešeny pomocí Nette komponent (`Nette\Security\Passwords`, `Nette\Security\User`).
Struktura je navržena dle doporučených best practices Nette Frameworku.

## Proces přihlašování

1. Uživatel zadá email a heslo do přihlašovacího formuláře.
2. Po odeslání formuláře:
    - Systém ověří existenci uživatele dle emailu.
    - Pokud je uživatel zablokován (překročen limit neúspěšných pokusů), zobrazí chybovou hlášku.
    - Pokud není zablokován, ověří heslo.
    - Při úspěchu:
        - Vynuluje počítadlo neúspěšných pokusů.
        - Přihlásí uživatele a přesměruje do administrace.
    - Při neúspěchu:
        - Zvýší počítadlo neúspěšných pokusů.
        - Pokud počet dosáhne 5, nastaví blokaci na 10 minut.
        - Zobrazí obecnou chybovou hlášku.
3. Po úspěšném přihlášení je uživatel přesměrován na neveřejnou část administrace.
4. Po odhlášení je uživatel vždy přesměrován zpět na přihlašovací stránku s flash message o úspěšném odhlášení.

## Doplňující implementační pravidla

### Session a automatické odhlášení
- Pro administraci bude nastavena samostatná session sekce s expirací při neaktivitě.
- Při neaktivitě delší než několik hodin (doporučení: 3 hodiny) dojde k automatickému odhlášení.
- Po vypršení session je uživatel přesměrován na přihlašovací stránku a je zobrazena flash message o vypršení relace.

### Validace přihlašovacího formuláře
- Pole pro login a heslo jsou povinná.
- Login se validuje pouze jako neprázdná hodnota (není vyžadována validace formátu email).

### Zobrazování chybových hlášek
- Pro všechny stavy (chyba přihlášení, blokace účtu, úspěšné odhlášení, vypršení session) se použijí flash messages.
- Texty chybových hlášek mají být obecné a bezpečné (bez prozrazení, zda neexistuje účet nebo je špatné heslo).

## Obnovení zapomenutého hesla

### Struktura souborů
- app/Presentation/Admin/Public/ForgotPassword/ForgotPasswordPresenter.php - veřejná část pro žádost o reset hesla a potvrzení resetu
- app/Model/Orm/AdminPasswordReset.php - entita reset tokenu
- app/Model/Orm/AdminPasswordResetRepository.php - repozitář reset tokenů
- app/Model/Orm/AdminPasswordResetMapper.php - mapper tabulky reset tokenů (Nextras ORM)
- app/Core/Security/AdminPasswordResetFacade.php - aplikační logika pro vytvoření tokenu, validaci tokenu a změnu hesla

### Proces obnovení hesla
1. Uživatel na přihlašovací stránce zvolí zapomenuté heslo.
2. Zadá login (email), systém vždy zobrazí obecnou informaci, že instrukce byly odeslány.
3. Pokud účet existuje, vygeneruje se jednorázový token s omezenou platností (doporučení: 30-60 minut).
4. Uživatel přes odkaz s tokenem otevře formulář pro nové heslo.
5. Po úspěšné změně hesla je token zneplatněn a uživatel je přesměrován na přihlašovací stránku s flash message.

### Návrh tabulky pro reset hesla

#### admin_password_reset
| Sloupec      | Typ           | Poznámka                              |
|--------------|--------------|---------------------------------------|
| id           | INT, PK      |                                       |
| user_id      | INT, FK      | Odkaz na admin_user                   |
| token_hash   | VARCHAR(255) | Hash tokenu (nikdy neukládat token v plain textu) |
| expires_at   | DATETIME     | Čas expirace tokenu                   |
| used_at      | DATETIME     | Čas použití tokenu (NULL = nepoužit)  |
| created_at   | DATETIME     |                                       |

## Změna hesla po přihlášení

### Struktura souborů
- app/Presentation/Admin/Home/ProfilePresenter.php - veřejná akce v Home presenteru pro změnu vlastního hesla
- app/Core/Security/AdminPasswordChangeFacade.php - aplikační logika změny hesla přihlášeného uživatele

### Proces změny hesla
1. Přihlášený uživatel otevře stránku změny hesla v administraci.
2. Vyplní aktuální heslo, nové heslo a potvrzení nového hesla.
3. Systém ověří aktuální heslo pomocí Nette Security.
4. Při úspěchu uloží nový hash hesla, vynuluje případné blokace a zobrazí flash message o úspěšné změně.
5. Při chybě zobrazí flash message a heslo nezmění.

## Logger aktivit administrace

### Cíl
- Logovat auditní záznamy akcí v administraci: datum a čas, uživatel, název akce a doplňková data.
- Umožnit dohledání změn a troubleshooting bez ruční analýzy log souborů.

### Struktura souborů
- app/Core/Security/AdminActivityLogger.php - služba pro zápis auditních záznamů
- app/Model/Orm/AdminActivityLog.php - entita logu aktivit
- app/Model/Orm/AdminActivityLogRepository.php - repozitář logů aktivit
- bin/admin.php - CLI vstup pro cleanup příkaz logů aktivit

### Co logovat
- Přihlášení uživatele (úspěšné i neúspěšné)
- Odhlášení
- Žádost o reset hesla
- Úspěšné dokončení resetu hesla
- Změnu hesla po přihlášení
- Důležité změny obsahu v administraci (vytvoření, úprava, smazání)

### Návrh API loggeru

```php
$this->adminActivityLogger->log(
    userId: $this->getUser()->getId(),
    action: 'article.updated',
    data: [
        'articleId' => 123,
        'title' => 'Nový titulek',
    ],
);
```

### Návrh tabulky pro audit log

#### admin_activity_log
| Sloupec      | Typ           | Poznámka |
|--------------|--------------|----------|
| id           | INT, PK      |          |
| user_id      | INT, FK, NULL| NULL pokud akci provedl anonym (např. reset request), FK `ON DELETE SET NULL` |
| action       | VARCHAR(120) | Název akce, např. `auth.login.success`, `article.updated` |
| payload_json | JSON         | Serializovaná doplňková data k akci |
| created_at   | DATETIME(6)  | Čas provedení akce v UTC |

Doporučené indexy:
- `INDEX idx_admin_activity_log_created_at (created_at)`
- `INDEX idx_admin_activity_log_user_id (user_id)`
- `INDEX idx_admin_activity_log_action (action)`

Poznámka k DB kompatibilitě:
- Pokud databáze nepodporuje typ `JSON`, použít `TEXT` a validovat JSON na aplikační vrstvě.

### Retence dat a cleanup
- Data v `admin_activity_log` ponechávat maximálně 6 měsíců.
- Cleanup řešit samostatným CLI příkazem a cronem.

Příklad ručního spuštění cleanupu:

```bash
ddev php bin/admin.php admin:activity-log:cleanup --older-than="6 months"
```

Příklad cronu pro lokální vývoj (1x denně ve 3:15):

```cron
15 3 * * * cd /home/vcernik/dev/bestapp && ddev php bin/admin.php admin:activity-log:cleanup --older-than="6 months" >> log/admin-activity-cleanup.log 2>&1
```

Příklad cronu pro produkci (bez ddev):

```cron
15 3 * * * cd /var/www/bestapp && php bin/admin.php admin:activity-log:cleanup --older-than="6 months" >> var/log/admin-activity-cleanup.log 2>&1
```

Poznámka:
- Cleanup má mazat pouze tabulku `admin_activity_log`, ne bezpečnostní data uživatelů.
- Příkaz má na konci vypsat počet smazaných záznamů.

## Návrh databázových tabulek


### admin_user
| Sloupec         | Typ           | Poznámka                |
|-----------------|--------------|-------------------------|
| id              | INT, PK      |                         |
| email           | VARCHAR(255) | Unikátní, přihlašovací  |
| password_hash   | VARCHAR(255) | Heslo (bcrypt/argon2)   |
| failed_count    | INT          | Počet neúspěšných pokusů|
| blocked_until   | DATETIME     | Blokace do (NULL = není blokován) |
| last_attempt_at | DATETIME     | Datum posledního pokusu |
| created_at      | DATETIME     |                         |
| updated_at      | DATETIME     |                         |

**Poznámky:**
- Při každém pokusu o přihlášení se aktualizují sloupce `failed_count`, `blocked_until` a `last_attempt_at` v tabulce `admin_user`.
- Po úspěšném přihlášení se `failed_count` vynuluje a `blocked_until` nastaví na NULL.
- Blokace je nastavena na 10 minut po dosažení 5 neúspěšných pokusů.
- Aktivita administrace se ukládá do `admin_activity_log` s JSON payloadem pro detail akce.

## Bezpečnostní doporučení
- Při chybě zobrazovat obecnou hlášku (nezdařené přihlášení), neupřesňovat, zda je špatný email nebo heslo.
- Počet pokusů a blokace evidovat per uživatel.
- Všechny administrativní routy chránit middlewarem/ověřením identity.

## Latte šablony
- Veřejná část: jednoduchý přihlašovací formulář.
- Neveřejná část: layout s navigací, obsah pouze pro přihlášené.

---
Tento návrh je podkladem pro implementaci dle best practices Nette Frameworku.

---

## 🔧 Architekturní vylepšení (doporučena refaktorizace)

### 1. Vytvoření samostatné veřejné sekce (`App\Presentation\Admin\Public`)

**Aktuální stav:** `SignPresenter` je přímo v `app/Presentation/Admin/`, což míchá veřejné a privátní presentery.

**Doporučení:**
- Přesunout `SignPresenter.php` → `app/Presentation/Admin/Public/SignPresenter.php`
- Vytvořit `app/Presentation/Admin/Public/ForgotPasswordPresenter.php` pro obnovení hesla
- Obě třídy by měly dědit z `BasePublicPresenter`

**Výhody:**
- Jasné oddělení veřejné a privátní části
- Snadnější správa rout (všechny veřejné routy v jednom místě)
- Snadnější ochranu privátních presenterů (kontrola přihlášení)

### 2. Rozlišení layoutů na `@layout.public.latte` a `@layout.private.latte`

**Cílový stav:**
- `@layout.public.latte` – veřejný layout (centrovaný box bez sidebaru)
- `@layout.private.latte` – privátní layout (se sidebarem a menu)

**Doporučení:**
- Přejmenovat/reorganizovat:
  - `app/Presentation/Admin/@layout.base.latte` – základní HTML (zůstane)
    - `app/Presentation/Admin/@layout.public.latte` – layout pro Sign/ForgotPassword (centrovaný)
    - `app/Presentation/Admin/@layout.private.latte` – layout pro přihlášené (se sidebarem)

**Příklad odkazu v presenteru:**

```php
// app/Presentation/Admin/Public/Sign/SignPresenter.php
final class SignPresenter extends BasePublicPresenter
{
    // Automaticky se použije @layout.public.latte
}

// app/Presentation/Admin/Home/HomePresenter.php
final class HomePresenter extends BasePrivatePresenter
{
    // Automaticky se použije @layout.private.latte
}
```

### 3. Vytvoření Base tříd pro automatické řešení layoutu

**Doporučení:**

```php
// app/Presentation/Admin/Public/Accessory/BasePublicPresenter.php
abstract class BasePublicPresenter extends Presenter
{
    public function startup(): void
    {
        parent::startup();
        // Veřejný presenter nemusí nic ověřovat
    }
}

// app/Presentation/Admin/Accessory/BasePrivatePresenter.php
abstract class BasePrivatePresenter extends Presenter
{
    public function startup(): void
    {
        parent::startup();
        
        // Kontrola přihlášení
        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('Musíte se přihlásit.', 'info');
            $this->redirect('Admin:Public:Sign:in');
        }
    }
}
```

### 4. Formuláře na veřejné části – DRY princip

**Aktuální stav:** Formuláře jsou v šablonách veřejných presenterů.

**Doporučení:**
- Vytvořit `SignFormFactory.php` v `app/Presentation/Admin/Public/Accessory/`
- Vytvořit `ForgotPasswordFormFactory.php` v `app/Presentation/Admin/Public/Accessory/`
- Oba formuláře by měly mít konzistentní styling a správu chyb

### 5. Flash messages a design na veřejné části

**Problém:** Obě stránky (přihlášení, obnova hesla) by měly mít jednotný design, ale momentálně nejde jasné, jak je to organizované.

**Doporučení:**
- Společné CSS třídy pro veřejný layout (např. `admin-public-box`, `admin-public-form`)
- Jednotná správa flash messages (informace, úspěch, chyba)
- Možnost vrácení se z obnovy hesla zpět na přihlášení

### 6. Routování administrace

**Doporučení v `config/admin.neon`:**

```neon
routing:
  routes:
    # Veřejné routy (bez požadavku na přihlášení)
    'admin/sign/<action=in>': 'Admin:Public:Sign:<action>'
    'admin/forgot-password/<action=request>': 'Admin:Public:ForgotPassword:<action>'
    'admin/forgot-password': 'Admin:Public:ForgotPassword:request'
    
        # Privátní routy (s ověřením přihlášení)
        'admin/<presenter>/<action>': 'Admin:<presenter>:<action>'
        'admin': 'Admin:Home:default'
```

### 7. Přítomnost uživatele v layoutu

**Doporučení:**
- `BasePrivatePresenter` by měl automaticky poskytovat data přihlášeného uživatele layoutu
- V `@layout.private.latte` používat `$user->identity` namísto statických dat

```php
// app/Presentation/Admin/Accessory/BasePrivatePresenter.php
public function startup(): void
{
    parent::startup();
    if (!$this->user->isLoggedIn()) {
        $this->flashMessage('Musíte se přihlásit.', 'info');
        $this->redirect('Admin:Public:Sign:in');
    }
}

protected function beforeRender(): void
{
    parent::beforeRender();
    $this->template->currentUser = $this->user->identity;
}
```

### 8. Chybové stránky

**Doporučení:**
- Vytvořit chybový presenter pro veřejné chyby (404, 500)
- Zajistit, aby veřejné chyby používaly `@layout.public.latte`
- Privátní chyby nech používají `@layout.private.latte`

---

## Shrnutí – doporučené kroky implementace

1. [Planned] Vytvořit adresář `app/Presentation/Admin/Public/`.
2. [Planned] Přesunout `SignPresenter.php` do `Public/Sign/` s šablonami.
3. [Planned] Vytvořit `ForgotPasswordPresenter.php` v `Public/ForgotPassword/` s šablonami.
4. [Planned] Vytvořit `BasePublicPresenter.php` v `Public/Accessory/` a `BasePrivatePresenter.php` v `Accessory/`.
5. [Planned] Sjednotit layout soubory (`@layout.public.latte`, `@layout.private.latte`).
6. [Planned] Vytvořit `SignFormFactory.php` a `ForgotPasswordFormFactory.php` v `Public/Accessory/`.
7. [Planned] Aktualizovat routování v `config/admin.neon`.
8. [Planned] Zajistit jednotný styling veřejné a privátní části.

---

## Návrh CLI skriptů pro správu admin uživatelů

### Cíl
- Umožnit bezpečné vytvoření admin uživatele z CLI bez ručního zásahu do databáze.
- Omezit rizika při zakládání prvního účtu v novém prostředí.

### Doporučená struktura
- `bin/admin.php` - vstupní CLI skript pro všechny admin příkazy
- `app/Core/Command/CreateAdminUserCommand.php` - aplikační logika příkazu
- `app/Core/Security/AdminUserManager.php` - doménová logika (validace, hash hesla, uložení)
- `app/Core/Command/CleanupAdminActivityLogCommand.php` - logika mazání starých auditních záznamů

### Návrh použití

```bash
ddev php bin/admin.php admin:user:create --username=admin --password='SilneHeslo123!'
```

Interaktivní režim (doporučeno):

```bash
ddev php bin/admin.php admin:user:create
# prompt: Username:
# prompt: Password:
# prompt: Confirm password:
```

### Návrh argumentů příkazu
- `admin:user:create` - akce pro vytvoření uživatele
- `--username=` - povinný login (unikátní)
- `--password=` - heslo (v neinteraktivním CI režimu)
- `--force` - volitelné přepsání již existujícího účtu (jinak chyba)

### Validace v CLI
- `username` nesmí být prázdný a musí být unikátní
- heslo min. 12 znaků, alespoň 1 číslo, 1 malé písmeno, 1 velké písmeno, 1 speciální znak
- potvrzení hesla musí souhlasit
- při chybě vracet nenulový exit code

### Bezpečnostní pravidla
- Heslo nikdy nelogovat ani nevypisovat do konzole
- V DB ukládat pouze hash (`Nette\Security\Passwords`)
- V CLI výpisu nevracet citlivá data (jen ID, username, timestamp)

### Návrh výstupu

```text
[OK] Admin user created
ID: 1
Username: admin
Created at: 2026-05-15 10:42:00
```

### Doporučené rozšíření
- `admin:user:list` - výpis admin uživatelů (bez citlivých údajů)
- `admin:user:change-password --username=...` - změna hesla z CLI
- `admin:user:deactivate --username=...` - deaktivace účtu
- `admin:activity-log:cleanup --older-than='6 months'` - údržba audit logu administrace

---

## Návrh testů pro administraci (Nette Tester)

### Cíl testování
- Pokrýt veřejnou i neveřejnou část administrace.
- Ověřit autentizaci, autorizaci, obnovu hesla a routování.
- Udržet testy rychlé, deterministické a spustitelné lokálně i v CI.

### Doporučená struktura testů

```text
tests/
├── bootstrap.php
├── _support/
│   ├── TestDatabase.php
│   └── Fixtures.php
├── Core/
│   ├── RouterFactory.phpt
│   └── Security/
│       ├── AdminAuthenticator.phpt
│       ├── AdminActivityLogger.phpt
│       ├── AdminPasswordResetFacade.phpt
│       └── AdminUserManager.phpt
├── Presentation/
│   └── Admin/
│       ├── PublicAccess.phpt
│       ├── PrivateAccess.phpt
│       └── FlashMessages.phpt
└── Cli/
    ├── CreateAdminUserCommand.phpt
    └── CleanupAdminActivityLogCommand.phpt
```

### 1) Unit testy - Core/Security

### Test infrastruktura a izolace
- Každý test běží nad separátní test DB (nikdy ne nad lokální vývojovou DB).
- Před každým testem se načtou fixtures a po testu se DB vrátí do čistého stavu.
- Pro integrační testy používat transakční obálku (BEGIN/ROLLBACK) nebo explicitní truncate.
- Všechny testy času používat v UTC, ideálně s fixovaným časem (clock stub).
- Test bootstrap (`tests/bootstrap.php`) má validovat, že běží `APP_ENV=test`.

`tests/Core/Security/AdminAuthenticator.phpt`
- přihlášení s validními údaji vrátí identitu
- neplatné heslo zvýší `failed_count`
- po dosažení limitu nastaví `blocked_until`
- přihlášení během blokace vyhodí očekávanou výjimku
- po úspěšném přihlášení vynuluje `failed_count`

`tests/Core/Security/AdminPasswordResetFacade.phpt`
- vytvoření tokenu uloží hash, ne plain token
- token je po expiraci neplatný
- token lze použít jen jednou (`used_at`)
- úspěšný reset hesla zneplatní token

`tests/Core/Security/AdminUserManager.phpt`
- vytvoření uživatele ukládá hash hesla
- duplicitní username vrací doménovou chybu
- slabé heslo vrací validační chybu

`tests/Core/Security/AdminActivityLogger.phpt`
- log se uloží s `user_id`, `action`, `payload_json`, `created_at`
- payload se serializuje konzistentně (JSON)
- logger zvládne i akci bez uživatele (`user_id = NULL`)

### 2) Integrační testy - Presentation/Admin

`tests/Presentation/Admin/PublicAccess.phpt`
- nepřihlášený uživatel otevře `Sign:in`
- nepřihlášený uživatel otevře `ForgotPassword:request`
- veřejné presentery používají veřejný layout

`tests/Presentation/Admin/PrivateAccess.phpt`
- nepřihlášený uživatel je přesměrován z `Admin:Home:default` na `Admin:Public:Sign:in`
- přihlášený uživatel otevře `Admin:Home:default`
- privátní presentery používají privátní layout

`tests/Presentation/Admin/FlashMessages.phpt`
- neúspěšné přihlášení vypíše obecnou bezpečnou hlášku
- odhlášení vypíše očekávanou hlášku
- vypršení session vypíše správnou hlášku

### 3) CLI testy

`tests/Cli/CreateAdminUserCommand.phpt`
- vytvoření uživatele vrací exit code 0
- uživatel je skutečně uložen v DB
- duplicitní username vrací nenulový exit code
- nevalidní heslo vrací nenulový exit code

`tests/Cli/CleanupAdminActivityLogCommand.phpt`
- smaže pouze záznamy starší než 6 měsíců
- novější záznamy ponechá
- vrací exit code 0 a počet smazaných řádků

### 4) Smoke test rout

Rozšířit `tests/Core/RouterFactory.phpt` o:
- `admin/sign/in` -> `Admin:Public:Sign:in`
- `admin/forgot-password/request` -> `Admin:Public:ForgotPassword:request`
- `admin/forgot-password/reset` -> `Admin:Public:ForgotPassword:reset`
- `admin` -> `Admin:Home:default`

### Ukázka stylu jednoho .phpt testu

```php
<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

test('password is stored as hash', function (): void {
    // Arrange
    // Act
    // Assert
    Assert::true(true);
});
```

### Spouštění testů

```bash
ddev php vendor/bin/tester tests -s
ddev php vendor/bin/tester tests/Core/Security -s
ddev php vendor/bin/tester tests/Cli -s
```

### Minimální cílové pokrytí
- Core/Security: 90 %
- CLI commandy: 80 %
- Presentation/Admin (integrační): klíčové scénáře přihlášení, odhlášení, resetu hesla

---

## Doporučené pořadí implementace

1. Vytvořit `AdminUserManager` a CLI příkaz `create`.
2. Přidat `AdminActivityLogger` + tabulku `admin_activity_log`.
3. Připravit CLI cleanup příkaz a cron pro mazání dat starších 6 měsíců.
4. Dopsat unit testy pro `AdminUserManager`, `AdminAuthenticator` a `AdminActivityLogger`.
5. Přidat `ForgotPasswordPresenter` + `AdminPasswordResetFacade`.
6. Dopsat integrační testy přístupu a layoutů.
7. Rozšířit smoke test rout a přidat CLI testy.