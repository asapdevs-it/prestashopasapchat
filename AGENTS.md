# AGENTS.md: zasady dla agentów AI i deweloperów

Proces pracy: `SDLC.md`. Specyfikacja: `.ai/specs/2026-11-11-connector-v1.md`. Kontrakt API: `docs/CONNECT-API.md`. Czytaj je przed pierwszą zmianą.

## Stack

- PHP 7.2 do 8.4 (kod musi działać na PHP z PrestaShop 1.7.8, czyli bez składni PHP 8).
- PrestaShop 1.7.8, 8.x, 9.x. Jeden kod dla wszystkich.
- Smarty (szablony `.tpl`), bez Composera w paczce, bez JS frameworków.
- Dystrybucja: zip w GitHub Releases. Moduł nie jest w katalogu PrestaShop Addons.

## Układ katalogów (do utworzenia)

```
asapchat/
  asapchat.php                      klasa modułu: install, uninstall, hooki, getContent
  config.xml                        generowany przez PrestaShop
  logo.png                          57x57
  index.php                         w KAŻDYM katalogu
  .htaccess                         blokada bezpośredniego uruchamiania PHP
  classes/AsapchatClient.php        wywołania API (token, config, settings, disconnect)
  controllers/admin/AdminAsapchatConnectController.php   powrót z panelu
  views/templates/admin/configure.tpl
  views/templates/hook/scripts.tpl
  views/css/admin.css               style ekranu, każda reguła pod .asapchat-app
  views/fonts/                      Open Sans woff2 (400, 600, 700, 800)
  views/img/asapchat-logo-white.svg
  translations/pl.php
  upgrade/                          upgrade-X.Y.Z.php dla wersji, które zmieniają klucze lub hooki
```

Kod modułu leży w podkatalogu `asapchat/` repozytorium. Dokumenty projektu (`docs/`, `.ai/`) zostają w katalogu głównym i nie trafiają do zipa.

## Zasady kodu

1. Każdy plik PHP zaczyna się od nagłówka licencji AFL 3.0 i `if (!defined('_PS_VERSION_')) { exit; }`.
2. `index.php` w każdym katalogu modułu (poza `vendor/`).
3. Klucze konfiguracji z prefiksem `ASAPCHAT_`. Pełna lista i znaczenie: punkt 4 specyfikacji. Nie dodawaj klucza, którego tam nie ma.
4. Ekran ustawień to własny szablon Smarty w stylu AsapChat (bez `HelperForm`), renderowany w `getContent()`. Każdy zapis sprawdza `Tools::isSubmit` i token BO.
5. Hooki: `displayHeader` dla skryptu wyszukiwarki, `displayBeforeBodyClosingTag` dla czatu, `displayFooter` jako zapas, gdy motyw nie woła poprzedniego, `displayBackOfficeHeader` dla CSS ekranu i odświeżania config. Flaga w pamięci pilnuje, żeby skrypt nie wyszedł dwa razy.
6. HTML tylko w szablonach Smarty. Każda zmienna z `|escape:'htmlall':'UTF-8'`. Bez `nofilter`.
7. Kontroler powrotu z panelu jest kontrolerem admina i sprawdza token admina oraz `state`.
8. Multistore: używaj `Configuration::get($key, null, $idShopGroup, $idShop)` i zapisuj w kontekście bieżącego sklepu. Każdy sklep ma własne połączenie.
9. SQL: w v1 moduł nie ma własnych tabel. Jeśli zapytanie jest konieczne: `pSQL()`, `(int)`, `bqSQL()`.
10. HTTP tylko w back office, nigdy w żądaniu klienta sklepu. Timeout 5 s. Moduł nie ma crona.
11. Porównania sekretów przez `hash_equals`.
12. Zero notice i warningów przy `_PS_MODE_DEV_ = true`.
13. Teksty domyślne po angielsku w kodzie (`$this->l('...')`), polski w `translations/pl.php`.
14. Bez override'ów klas, bez zmian tabel rdzenia, bez `var_dump`, `die`, `error_reporting`, zakomentowanego kodu.
15. CSS ekranu: każda klasa z prefiksem `asapchat-`, każda reguła zaczyna się od `.asapchat-app`. Font Open Sans z paczki modułu, bez zewnętrznych adresów. Tokeny wyglądu: punkt 5.1 specyfikacji.
16. Adres AsapChat tylko ze stałej `ASAPCHAT_BASE`. Nigdy pole w ustawieniach.
17. Wersja modułu jest w jednym miejscu: `$this->version` w konstruktorze. Z nią porównujesz `plugin.latest_version`.

## Walidacja (uruchom przed każdym PR)

```
find asapchat -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse asapchat
```

Ręcznie przed wydaniem: wgraj zip na https://validator.prestashop.com i popraw błędy. To dobra praktyka, nie warunek zewnętrzny.

## Czego agent nie robi nigdy

- Nie kopiuje kodu z gałęzi `legacy`. To archiwum.
- Nie dodaje endpointów po stronie sklepu ani trybów diagnostycznych.
- Nie dodaje synchronizacji zamówień ani produktów. Nie tworzy klucza Webservice za sprzedawcę.
- Nie pobiera i nie instaluje aktualizacji modułu. Pokazuje tylko pasek z linkiem do zipa.
- Nie zmienia `docs/CONNECT-API.md`. Potrzebna zmiana kontraktu: issue z etykietą `needs-backend` i oznaczeniem `prz10`.
- Nie wpisuje `shop_id`, tokenu ani domeny na sztywno. Wszystko pochodzi z `GET /api/plugin/v1/config`.
- Nie prosi o hasło AsapChat i go nie zapisuje.
- Nie loguje tokenu `ack_...` ani kodu jednorazowego.
- Nie woła API AsapChat przed kliknięciem „Połącz” przez sprzedawcę.
- Nie dodaje funkcji spoza specyfikacji v1. Pomysł zapisz jako issue.
- Nie commituje haseł, tokenów ani kluczy.
