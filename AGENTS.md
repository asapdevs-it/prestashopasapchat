# AGENTS.md: zasady dla agentów AI i deweloperów

Proces pracy: `SDLC.md`. Specyfikacja: `.ai/specs/2026-11-11-connector-v1.md`. Kontrakt API: `docs/CONNECT-API.md`. Czytaj je przed pierwszą zmianą.

## Stack

- PHP 7.2 do 8.4 (kod musi działać na PHP z PrestaShop 1.7.8, czyli bez składni PHP 8).
- PrestaShop 1.7.8, 8.x, 9.x. Jeden kod dla wszystkich.
- Smarty (szablony `.tpl`), bez Composera w paczce, bez JS frameworków.

## Układ katalogów (do utworzenia)

```
asapchat/
  asapchat.php                      klasa modułu: install, uninstall, hooki, getContent
  config.xml                        generowany przez PrestaShop
  logo.png                          57x57
  index.php                         w KAŻDYM katalogu
  .htaccess                         blokada bezpośredniego uruchamiania PHP
  header_csp.txt                    lista zewnętrznych domen (asapchat.io, panel.asapchat.io)
  classes/AsapchatClient.php        wywołania API (token, config, disconnect)
  controllers/admin/AdminAsapchatConnectController.php   powrót z panelu
  views/templates/admin/configure.tpl
  views/templates/hook/scripts.tpl
  translations/pl.php
  upgrade/
  docs/readme_pl.pdf, readme_en.pdf
```

Kod modułu leży w podkatalogu `asapchat/` repozytorium. Dokumenty projektu (`docs/`, `.ai/`) zostają w katalogu głównym i nie trafiają do zipa.

## Zasady kodu

1. Każdy plik PHP zaczyna się od nagłówka licencji AFL 3.0 i `if (!defined('_PS_VERSION_')) { exit; }`.
2. `index.php` w każdym katalogu modułu (poza `vendor/`).
3. Klucze konfiguracji z prefiksem `ASAPCHAT_`: `ASAPCHAT_TOKEN`, `ASAPCHAT_CONFIG`, `ASAPCHAT_CONFIG_AT`, `ASAPCHAT_CHAT_ON`, `ASAPCHAT_SEARCH_ON`, `ASAPCHAT_STATE`, `ASAPCHAT_VERIFIER`.
4. Formularz ustawień przez `HelperForm` w `getContent()`. Każdy zapis sprawdza `Tools::isSubmit` i token formularza.
5. Hooki: `actionFrontControllerSetMedia` lub `displayHeader` dla skryptu wyszukiwarki, `displayBeforeBodyClosingTag` dla czatu, `displayFooter` jako zapas gdy motyw nie woła poprzedniego. Flaga w pamięci pilnuje, żeby skrypt nie wyszedł dwa razy.
6. HTML tylko w szablonach Smarty. Każda zmienna z `|escape:'htmlall':'UTF-8'`. Bez `nofilter`.
7. Kontroler powrotu z panelu jest kontrolerem admina i sprawdza token admina oraz `state`.
8. Multistore: używaj `Configuration::get($key, null, $idShopGroup, $idShop)` i zapisuj w kontekście bieżącego sklepu. Każdy sklep ma własne połączenie.
9. SQL: w v1 moduł nie ma własnych tabel. Jeśli zapytanie jest konieczne: `pSQL()`, `(int)`, `bqSQL()`.
10. HTTP tylko po stronie panelu admina i crona, nigdy w żądaniu klienta sklepu. Timeout 5 s.
11. Porównania sekretów przez `hash_equals`.
12. Zero notice i warningów przy `_PS_MODE_DEV_ = true`.
13. Teksty domyślne po angielsku w kodzie (`$this->l('...')`), polski w `translations/pl.php`.
14. Bez override'ów klas, bez zmian tabel rdzenia, bez `var_dump`, `die`, `error_reporting`, zakomentowanego kodu.

## Walidacja (uruchom przed każdym PR)

```
find asapchat -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse asapchat
```

Ręcznie przed wydaniem: zip na https://validator.prestashop.com, zero błędów.

## Czego agent nie robi nigdy

- Nie przenosi niczego z gałęzi `legacy`: żadnego pull API, routera po `REQUEST_URI`, endpointu `?debug`.
- Nie dodaje synchronizacji zamówień ani produktów.
- Nie wpisuje `shop_id`, tokenu ani domeny na sztywno. Wszystko pochodzi z `GET /api/plugin/v1/config`.
- Nie prosi o hasło AsapChat i go nie zapisuje.
- Nie loguje tokenu `ack_...` ani kodu jednorazowego.
- Nie woła API AsapChat przed kliknięciem „Połącz” przez sprzedawcę.
- Nie dodaje funkcji spoza specyfikacji v1. Pomysł zapisz jako issue.
- Nie commituje haseł, kluczy ani `module_key` z prywatnych kont w miejscach innych niż konstruktor modułu.
