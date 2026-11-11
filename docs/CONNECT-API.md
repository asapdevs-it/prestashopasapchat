# Kontrakt wtyczek AsapChat (v1)

Wspólny kontrakt wszystkich wtyczek AsapChat (WordPress, PrestaShop, Shopify, Shoper, GTM). Ten plik jest kopią: zmiany zgłaszaj przez issue z etykietą `needs-backend`, nie edytuj go w repo wtyczki.

Adres bazowy: `https://panel.asapchat.io`. We wtyczce trzymaj go w jednej stałej, którą da się nadpisać
(np. `define('ASAPCHAT_BASE', ...)` w `wp-config.php`), bo testujesz też na innym adresie.

## 1. Co robi wtyczka

1. Łączy sklep z kontem AsapChat (przycisk „Połącz z AsapChat”, zapasowo klucz instalacyjny).
2. Wstawia na stronę sklepu skrypt czatu i wyszukiwarki z atrybutami z `GET /config`.
3. Pokazuje status, ustawienia wyglądu, źródło zamówień i produktów, informację o nowej wersji.

Wtyczka nigdy nie prosi o hasło AsapChat i nie wysyła nic do AsapChat, zanim użytkownik nie kliknie „Połącz” albo nie wklei klucza.

## 2. Połączenie przyciskiem

```
wtyczka                              panel.asapchat.io                     użytkownik
  | 1. losuje state i code_verifier        |                                   |
  | 2. przekierowanie na /connect -------->| 3. logowanie / rejestracja        |
  |                                        |    wybór sklepu, „Połącz”  <------|
  | 5. sprawdza state  <-- 4. powrót na return_url?code=...&state=...          |
  | 6. POST /api/plugin/v1/token --------->|                                   |
  | 7. zapisuje token ack_...  <-----------|                                   |
```

### Krok 1 i 2: przekierowanie

`GET https://panel.asapchat.io/connect` z parametrami:

| Parametr | Wartość |
|---|---|
| `platform` | `wordpress`, `prestashop`, `shopify`, `shoper`, `gtm` |
| `site_url` | adres sklepu, np. `https://sklep.pl` (tylko `http`/`https`) |
| `return_url` | adres w panelu sklepu, na który wracamy. Musi być `https` (wyjątek: `http://localhost` i `http://127.0.0.1`), na tym samym hoście i porcie co `site_url` (`www.` pomijamy), bez `user:pass@`, bez `#fragmentu`, bez spacji i backslasha. Domeny z polskimi znakami podaj jako punycode (`xn--...`). Może mieć własne parametry (`?action=...`). |
| `state` | losowe 32 znaki z `[A-Za-z0-9._~-]` (min. 16). Zapisz po stronie sklepu z czasem życia 2 godziny. |
| `code_challenge` | `base64url(sha256(code_verifier))` bez `=`, dokładnie 43 znaki |

`code_verifier`: losowe 43 do 128 znaków, zapisane obok `state`. Nigdy nie trafia do adresu.

```php
$verifier  = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
```

Dlaczego 2 godziny: użytkownik bez konta przechodzi po drodze cały kreator AsapChat (rejestracja, kod z maila, kroki) i dopiero
wtedy wraca na ekran zgody. Nie skracaj tego czasu.

### Krok 3: co widzi użytkownik

- Zalogowany ze sklepem: ekran zgody z wyborem sklepu.
- Niezalogowany: „Mam konto, loguję się” albo „Załóż darmowe konto”. Po logowaniu (hasłem albo Google) albo po kreatorze wraca sam na ekran zgody.
- Połączyć może tylko właściciel sklepu. Operator z zespołu zobaczy informację, że nie ma sklepu do połączenia.
- Zły `return_url` albo brak parametrów: ekran błędu (HTTP 422), bez przekierowania.

### Krok 4 i 5: powrót

Powrót na `return_url` z dopisanym `code` i `state`, albo `error=access_denied` i `state` (kliknięto „Anuluj”).

Wtyczka: porównuje `state` z zapisanym (`hash_equals`), sprawdza uprawnienia administratora, kasuje `state`. Niezgodny `state` = komunikat „Spróbuj połączyć ponownie”, bez wymiany kodu.

### Krok 6 i 7: wymiana kodu

```bash
curl -X POST https://panel.asapchat.io/api/plugin/v1/token \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"code":"<code>","code_verifier":"<verifier>"}'
```

`200`: `{"token":"ack_...","shop":{"id":17,"name":"Kwiatowy"},"config":{...}}`. `config` ma ten sam kształt co `GET /config`.

`400 {"error":"invalid_grant"}`: kod zły, użyty drugi raz, starszy niż 120 sekund albo zły `code_verifier`. Kod przepada po pierwszej próbie. Limit: 10 prób na minutę z jednego IP.

Token `ack_...` (52 znaki) to sekret. Zapisz go w ustawieniach sklepu po stronie serwera. Nie pokazuj w HTML, nie loguj, nie wstawiaj do JS. Ponowne połączenie tej samej strony unieważnia poprzedni token.

## 3. Połączenie kluczem instalacyjnym (zapas)

Użytkownik generuje klucz w panelu: Sklep → Wtyczka → „Klucz instalacyjny”, i wkleja go w polu wtyczki. Klucz to od razu token `ack_...`.
Wtyczka woła `GET /config` z nagłówkiem `X-Site-Url`. Pierwsze wywołanie wiąże klucz z tym hostem. Na innym hoście klucz zwraca `409`.
Klucz trzeba użyć w ciągu 24 godzin od wygenerowania, potem wygasa (`401`).
Klucz to jedyna droga, gdy przycisk nie może zadziałać: sklep po `http`, panel administracyjny na innej domenie niż sklep (multistore, aplikacje hostowane). `X-Site-Url` może być po `http`, liczy się sam host.

## 4. API z tokenem

Każde wywołanie: `Authorization: Bearer ack_...`, `Accept: application/json`, `X-Site-Url: https://sklep.pl` (obowiązkowy, brak = `400`). Limit: 60 na minutę z jednego adresu IP.

Token należy do sklepu, nie do osoby: działa dalej, gdy osoba, która łączyła, straci dostęp do sklepu. Odwołuje go właściciel w panelu (Sklep → Wtyczka) albo „Rozłącz” we wtyczce.

### GET /api/plugin/v1/config

```json
{
  "v": 1,
  "shop": {"id": 17, "name": "Kwiatowy", "plan": "free", "ai_conversations_limit": 50},
  "chat": {
    "enabled": true,
    "src": "https://.../embedded-widget.js",
    "attributes": {"main_color": "#112233", "name": "Flora", "shop_id": "17", "token": "..."}
  },
  "search": {"enabled": false, "src": null, "attributes": null},
  "settings": {"bot_name": "Flora", "main_color": "#112233", "position": "right-bottom", "greeting_1": "...", "greeting_2": "..."},
  "sources": {"orders_connected": false, "products_connected": false, "systems": [], "connect_url": "https://panel.asapchat.io/admin/shops/17/connect"},
  "status": {"widget_seen_at": null, "search_seen_at": null},
  "plugin": {"latest_version": "1.0.2", "download_url": "https://...", "changelog_url": "https://..."},
  "loader": {"url": "https://panel.asapchat.io/api/w/17.js?t=..."},
  "links": {"panel": "https://panel.asapchat.io/admin", "settings": "...", "plugin": "..."},
  "refresh_after": 86400
}
```

Zasady dla wtyczki:

- **Skrypty.** `src` zawsze zaczyna się od `https://asapchat.io/`. Inny adres: nie wstawiaj skryptu. Wstaw `<script src="{chat.src}" ...>` i dopisz KAŻDĄ parę z `chat.attributes` jako atrybut, bez listy dozwolonych nazw. Wartość `true` = atrybut bez wartości (np. `defer`). To samo dla `search`, gdy `search.enabled`. Nazwy atrybutów mogą się zmieniać, nie wpisuj ich na sztywno.
- **Cache.** Trzymaj całą odpowiedź lokalnie i odświeżaj co `refresh_after` sekund oraz przy wejściu na stronę ustawień wtyczki. Strona sklepu nigdy nie czeka na AsapChat: skrypty wstawiasz z zapisanej kopii.
- **Wyszukiwarka wyłączona** (`search.enabled=false`): sklep nie ma jeszcze zaimportowanych produktów. Pokaż przełącznik jako nieaktywny z linkiem `sources.connect_url`.
- **`shop.plan`**: `free` albo `pro`. Pokaż nazwę planu, a gdy `ai_conversations_limit` jest liczbą, także „N rozmów AI miesięcznie”.
- **`sources.systems`**: lista nazw podłączonych systemów (np. `woo`, `presta`, `baselinker`), tylko do wyświetlenia jako tekst. Może być pusta.
- **`sources`**: sekcja „Źródło zamówień i produktów”. `orders_connected` / `products_connected` jako dwa statusy, przycisk „Podłącz w AsapChat” prowadzi na `connect_url` (WooCommerce łączy się tam jednym kliknięciem, pozostałe platformy przez klucze API). Pod spodem krótka instrukcja, gdzie w tej platformie wygenerować klucze (tekst w specu repo).
- **Po 401 i po „Rozłącz”** ustawienia lokalne wtyczki (przełączniki, wykluczenia, opóźnienie) zostają. Kasujesz tylko token i zapisany `config`.
- **`status.search_seen_at`**: jak niżej, dla wyszukiwarki („Wyszukiwarka działa” / „Jeszcze niewidziana na stronie”).
- **`status.widget_seen_at`**: `null` = czat nie był jeszcze widziany na stronie („Otwórz sklep w nowej karcie, żeby sprawdzić”), data = „Czat działa”.
- **`plugin.latest_version`**: gdy nie jest `null` i jest wyższa niż wersja wtyczki (`version_compare`), pokaż pasek „Dostępna wersja X” z `download_url` i `changelog_url`. `null` = katalog platformy aktualizuje wtyczkę sam, nic nie pokazuj.

Kody błędów (dla wszystkich wywołań z tokenem):

| Kod | Znaczenie | Co robi wtyczka |
|---|---|---|
| `400` | brak nagłówka `X-Site-Url` | błąd w kodzie wtyczki, popraw |
| `401` | token nieznany, odwołany w panelu albo klucz instalacyjny nieużyty przez 24 h | kasuje token i cache, przestaje wstawiać skrypty, pokazuje „Połącz ponownie” |
| `409` | token związany z inną domeną | jak wyżej, z komunikatem o innej domenie |
| `410` | sklep usunięty w AsapChat | jak przy 401 |
| `422` | złe dane w `PATCH /settings` | pokazuje błędy pól z `errors` |
| `429`, `5xx`, brak sieci | chwilowy problem | NIC nie kasuje, działa dalej na zapisanej kopii, próbuje przy następnym odświeżeniu |

### PATCH /api/plugin/v1/settings

Wygląd widgetu, wspólny z panelem AsapChat. Wysyłasz tylko zmienione pola, odpowiedź to pełny `config` (zapisz go od razu).

| Pole | Reguła |
|---|---|
| `bot_name` | tekst, do 40 znaków |
| `main_color` | `#rrggbb` |
| `position` | `right-bottom` albo `left-bottom` |
| `greeting_1`, `greeting_2` | tekst do 120 znaków, pusty = bez dymka powitania |

### DELETE /api/plugin/v1/connection

„Rozłącz”. `204`. Potem wtyczka kasuje token i cache. Wołaj też przy odinstalowaniu (błąd sieci nie blokuje odinstalowania).

## 5. Ustawienia tylko po stronie wtyczki

Tych opcji backend nie zna. Wtyczka trzyma je u siebie i sama decyduje, czy wstawić skrypt:

| Opcja | Działanie |
|---|---|
| Czat włączony | wyłączony = nie wstawiaj skryptu czatu |
| Wyszukiwarka włączona | wyłączona = nie wstawiaj skryptu wyszukiwarki |
| Dodawanie do koszyka z wyszukiwarki | wyłączone = pomiń atrybut `allow_add-to-cart`, nawet gdy przyszedł w `search.attributes` (wyjątek od zasady „dopisz każdy atrybut”) |
| Wykluczenia | lista stron, kategorii lub produktów (ID albo fragment adresu), na których nie wstawiamy żadnego skryptu |
| Opóźnienie | sekundy 0 do 60. Większe od 0 = skrypt czatu dokładany przez `setTimeout` zamiast znacznikiem w HTML |

## 6. Loader (GTM, Shoper, miejsca bez dostępu do atrybutów)

Jeden adres JS, który sam dokłada czat i wyszukiwarkę z aktualnymi atrybutami:

```
https://panel.asapchat.io/api/w/{shop_id}.js?t={token_widgetu}
```

- `t` to publiczny token widgetu (ten sam, który jest w zwykłym kodzie do wklejenia), nie `ack_...`.
- `&chat=0` albo `&search=0` wyłącza jeden widget, `&cart=0` wyłącza dodawanie do koszyka z wyszukiwarki.
- Wtyczka z tokenem bierze gotowy adres z `loader.url` w `GET /config` i dokleja tylko te flagi.
- Loader działa na publicznym tokenie widgetu, więc „Rozłącz” go nie wyłącza. Tam, gdzie kod wkleja człowiek (GTM, Shoper), po rozłączeniu trzeba kod usunąć ręcznie. Powiedz to w UI.
- Czat pokaże się tylko na domenie sklepu z panelu AsapChat albo na domenie połączonej wtyczką.
- Odpowiedź ma `Cache-Control: public, max-age=300`. Serwer nie trzyma kopii: zmiana wyglądu w panelu jest widoczna po maks. 5 minutach (cache przeglądarki).
- Zły `shop_id` albo `t`: `404` z komentarzem JS.

Szablon GTM: pola „ID sklepu” i „Token”, potem `injectScript(url)`. W uprawnieniach szablonu `inject_script` dla `https://panel.asapchat.io/api/w/*`.
Adres z gotowymi wartościami użytkownik kopiuje z panelu: Sklep → Wtyczka.

## 7. Jak pracować z backendem (deweloper wtyczki)

Do pracy nad wtyczką nie potrzebujesz kodu backendu.

1. Załóż własne darmowe konto na `https://panel.asapchat.io/onboarding/1` (plan Free wystarcza). To Twój sklep testowy.
2. Lokalny sklep stawiaj na `http://localhost:PORT` albo `http://127.0.0.1:PORT`. Tylko te dwa adresy przechodzą bez `https`. Domeny typu `sklep.test` wymagają `https` (np. mkcert albo tunel).
3. Po połączeniu host Twojej strony jest automatycznie dopuszczony do widgetu, czat ładuje się też na `localhost`.
4. Klucz instalacyjny i podgląd połączeń: panel, Sklep → Wtyczka. Tam też odwołasz połączenie, żeby sprawdzić obsługę `401`.
5. Przypadki do przeklikania przed każdym PR: połączenie przyciskiem, „Anuluj”, powrót ze złym `state`, klucz instalacyjny, odwołanie w panelu (401), brak sieci (skrypty dalej się wstawiają), rozłączenie, odinstalowanie.
6. Nowe konto: kliknij „Połącz” w przeglądarce bez sesji AsapChat i wybierz „Załóż darmowe konto”. Po kreatorze musisz wrócić na ekran zgody, a potem do wtyczki.
7. Potrzebujesz zmiany w kontrakcie (nowe pole, nowa platforma): issue w repo wtyczki z etykietą `needs-backend`. Nie obchodź braków po swojej stronie.

Do dnia wdrożenia API na produkcji pracuj na przykładowej odpowiedzi z punktu 4 jako stałej odpowiedzi klienta HTTP w testach.

## 8. Czego wtyczce nie wolno

- Żadnych endpointów po stronie sklepu, które coś zwracają bez autoryzacji (diagnostyka, podgląd konfiguracji, klucze).
- Żadnych wywołań do AsapChat przed zgodą użytkownika, żadnej telemetrii.
- Token `ack_...` nie opuszcza serwera sklepu.
- Skrypty tylko z adresów podanych w `config`, bez własnych kopii plików JS.
