# Kontrakt „Połącz z AsapChat” (wspólny dla wszystkich wtyczek)

Ten plik jest identyczny w każdym repo wtyczki. Źródło prawdy: spec `.ai/specs/2026-11-11-plugin-connect.md` w repo backendu `asapdevs-it/asapchat`. Jeśli coś tu się nie zgadza z backendem, wygrywa backend, a różnicę zgłoś jako issue.

**Stan:** kontrakt zaprojektowany, backend jeszcze go nie implementuje. Dopóki endpointy nie działają, wtyczkę testuj na atrapie (mock) zwracającej odpowiedzi z tego pliku.

## Słownik

- **Panel**: `https://panel.asapchat.io`, aplikacja AsapChat, w której sprzedawca ma konto.
- **Sklep AsapChat**: rekord w panelu (ma `shop_id`). Jedno konto Free ma jeden sklep.
- **Token połączenia**: tajny ciąg `ack_` + 48 znaków. Wtyczka trzyma go po stronie serwera sklepu i wysyła w nagłówku `Authorization: Bearer`. Nigdy nie trafia do HTML strony.
- **Token widgetu** (`token` w atrybutach skryptu): publiczny, jest w kodzie strony. To nie jest token połączenia.
- **Kod jednorazowy** (`code`): ważny 120 sekund, do jednokrotnej wymiany na token połączenia.
- **PKCE**: zabezpieczenie przed przechwyceniem kodu. Wtyczka losuje `code_verifier`, wysyła do panelu tylko jego skrót (`code_challenge`), a przy wymianie kodu pokazuje oryginał.
- **state**: losowy ciąg, który wtyczka zapamiętuje przed przekierowaniem i porównuje po powrocie. Chroni przed podstawieniem cudzego połączenia (CSRF).

## Przepływ A: wtyczka na serwerze sprzedawcy (WordPress, PrestaShop, Magento)

1. Sprzedawca klika „Połącz z AsapChat”. To kliknięcie jest zgodą na kontakt z naszym serwerem. Przed nim wtyczka nie wysyła do nas nic.
2. Wtyczka losuje i zapisuje po stronie serwera (ważne 10 minut, powiązane z zalogowanym administratorem):
   - `state`: 32 losowe bajty, zapis szesnastkowy,
   - `code_verifier`: 32 losowe bajty, base64url bez `=`.
3. Wtyczka przekierowuje przeglądarkę na:

```
GET https://panel.asapchat.io/connect
  ?v=1
  &platform=wordpress            (wordpress | prestashop | magento)
  &site_url=https://sklep.pl     (adres strony głównej sklepu)
  &return_url=https://sklep.pl/wp-admin/admin.php?page=asapchat&asapchat_callback=1
  &state=<state>
  &code_challenge=<base64url(sha256(code_verifier))>
```

4. Panel wymaga logowania (albo rejestracji, potem wraca tutaj), pokazuje ekran zgody z nazwą hosta dużą czcionką i listą sklepów użytkownika. Panel odrzuca żądanie, gdy:
   - `return_url` nie jest `https` (wyjątek: `localhost`),
   - host `return_url` jest inny niż host `site_url` (porównanie małymi literami, bez `www.`).
5. Po „Połącz” panel przekierowuje na `return_url` z dopisanymi `code` i `state`. Po „Anuluj”: `error=access_denied` i `state`.
6. Wtyczka w obsłudze powrotu sprawdza kolejno: uprawnienia administratora, zgodność `state` (porównanie odporne na czas, np. `hash_equals`), obecność `code`. Potem wysyła z serwera sklepu:

```
POST https://panel.asapchat.io/api/plugin/v1/token
Content-Type: application/json

{"code": "<code>", "code_verifier": "<code_verifier>"}
```

Odpowiedź 200:

```json
{
  "token": "ack_…",
  "shop": {"id": 12, "name": "Sklep Testowy"},
  "config": { "…": "jak w GET /config" }
}
```

Każdy błąd to 400 i kod przepada. Limit: 10 żądań na minutę.

7. Wtyczka zapisuje `token` i `config`, kasuje `state` i `code_verifier`, pokazuje stan „Połączony”.

## Przepływ B: klucz instalacyjny (zapas)

Sprzedawca kopiuje klucz z panelu (karta „Wtyczka” w ustawieniach sklepu, pokazywany raz) i wkleja we wtyczce. Klucz ma ten sam format `ack_…`. Wtyczka od razu woła `GET /config` z nagłówkiem `X-Site-Url`. Pierwsze takie wywołanie wiąże klucz z hostem; późniejsze z innego hosta dostaje 409.

## Przepływ C: aplikacje hostowane (Shopify, Shoper)

Klientem jest nasz backend, nie serwer sprzedawcy. Ten sam ekran zgody, ale `return_url` to nasza trasa i połączenie powstaje w backendzie. Host sklepu pochodzi z API platformy, nie z parametru. Trasy:

- Shopify: `GET /shopify/app` (strona osadzona), `POST /api/shopify/session`, `GET /shopify/connect/callback` (zapisuje metafield `$app:asapchat/config`), `POST /api/shopify/webhooks/{topic}` dla `customers/data_request`, `customers/redact`, `shop/redact`, `app/uninstalled`.
- Shoper: `POST /api/shoper-app/lifecycle`, `GET /shoper-app/panel` (iframe, weryfikacja HMAC-SHA512), `GET /shoper-app/connect/callback`.

## Konfiguracja skryptów

```
GET https://panel.asapchat.io/api/plugin/v1/config
Authorization: Bearer ack_…
X-Site-Url: https://sklep.pl
```

Odpowiedź 200:

```json
{
  "v": 1,
  "shop": {"id": 12, "name": "Sklep Testowy", "plan": "free"},
  "chat": {
    "enabled": true,
    "src": "https://asapchat.io/chat/embedded-widget.js",
    "attributes": {
      "id": "embedded-widget",
      "shop_id": "12",
      "token": "…",
      "sticky": "1",
      "position": "right-bottom",
      "main_color": "#24b59b",
      "button_color": "#24b59b",
      "name": "Asystent",
      "bot_name": "Asystent",
      "data_startmessagebeforeopen": "Cześć",
      "data_secondmessagebeforceopen": "Jak mogę Ci pomóc?"
    }
  },
  "search": {
    "enabled": true,
    "src": "https://asapchat.io/search/asap-search-widget.iife.js",
    "attributes": {
      "defer": true,
      "shop_id": "12",
      "token": "…",
      "shop_engine": "Woocommerce",
      "allow_add-to-cart": "true",
      "selectors": "input[type=search]"
    }
  },
  "status": {"widget_seen_at": "2026-11-10T12:00:00Z", "search_seen_at": null},
  "links": {"panel": "https://panel.asapchat.io/admin", "settings": "https://panel.asapchat.io/admin/shops/12/edit"},
  "refresh_after": 86400
}
```

Zasady dla wtyczki:

1. **Renderuj prawdziwy znacznik `<script>`** z `src` i każdym atrybutem z mapy. Widgety czytają atrybuty z własnego znacznika, więc muszą być atrybutami HTML, nie zmiennymi JS. Nazwy atrybutów przepisuj dokładnie (także literówkę `data_secondmessagebeforceopen` i myślnik w `allow_add-to-cart`).
2. Wartość `true` to atrybut bez wartości (`defer`). Każdą inną wartość zakoduj jak atrybut HTML.
3. Nie dopisuj i nie pomijaj atrybutów. Nowy atrybut w odpowiedzi ma zadziałać bez aktualizacji wtyczki.
4. `enabled: false` znaczy „nie wstrzykuj” (np. wyszukiwarka bez podłączonego importu produktów). Pokaż wtedy wskazówkę z linkiem `links.settings`.
5. Sprzedawca ma we wtyczce dwa własne przełączniki (czat, wyszukiwarka). Skrypt trafia na stronę tylko, gdy przełącznik jest włączony i `enabled` jest `true`.

## Kiedy odświeżać konfigurację

- Zapisz ostatnią poprawną odpowiedź lokalnie.
- Odświeżaj: przy otwarciu strony ustawień wtyczki oraz raz na dobę zadaniem w tle.
- **Nigdy przy żądaniu strony sklepu.** Awaria naszego serwera nie może spowolnić ani zepsuć sklepu.

| Odpowiedź | Co robi wtyczka |
|---|---|
| 200 | Zapisuje nową konfigurację. |
| 5xx, timeout, błąd sieci | Zostawia ostatnią konfigurację, skrypty działają dalej. |
| 401 | Połączenie odwołane. Przestaje wstrzykiwać, stan „Połącz ponownie”. |
| 409 | Klucz związany z innym hostem. Przestaje wstrzykiwać, komunikat z linkiem do panelu. |
| 410 | Sklep usunięty w panelu. Przestaje wstrzykiwać, stan „Połącz ponownie”. |
| 429 | Za dużo żądań. Zostawia konfigurację, próbuje przy następnym cyklu. |

Limit: 60 żądań na minutę na token. Timeout po stronie wtyczki: 5 sekund.

## Rozłączenie

```
DELETE https://panel.asapchat.io/api/plugin/v1/connection
Authorization: Bearer ack_…
```

Odpowiedź 204. Wtyczka kasuje token i konfigurację niezależnie od wyniku (także przy błędzie sieci). Odinstalowanie wtyczki robi to samo.

## Bezpieczeństwo, lista kontrolna

- Token połączenia tylko po stronie serwera, nigdy w HTML, JS, logach ani komunikatach błędów.
- `state` i `code_verifier` jednorazowe, kasowane po użyciu lub po 10 minutach.
- Obsługa powrotu dostępna tylko dla administratora sklepu.
- Wszystkie wywołania przez HTTPS z weryfikacją certyfikatu.
- Żadnych endpointów diagnostycznych zwracających konfigurację lub token.
- Żadnego wywołania do AsapChat przed kliknięciem „Połącz” albo zapisaniem klucza.
