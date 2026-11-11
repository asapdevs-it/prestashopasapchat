# Specyfikacja: moduł AsapChat dla PrestaShop, wersja 1

Data: 2026-11-11. Status: zatwierdzona do realizacji. Kontrakt API: `docs/CONNECT-API.md`. Mockup: `docs/mockups/configure.html`.

## 1. Cel

Sprzedawca instaluje moduł, klika jeden przycisk i po minucie ma na sklepie czat AsapChat oraz wyszukiwarkę AsapSearch, bez wklejania kodu do motywu.

## 2. Słownik

- **Back office (BO):** panel administracyjny PrestaShop.
- **Hook:** miejsce w PrestaShop, w które moduł może wpiąć własny kod (np. przed `</body>`).
- **Token połączenia:** tajny ciąg `ack_...` wydany przez backend AsapChat. Dowodzi, że ten sklep jest połączony z kontem. Nie mylić z publicznym `token` w znaczniku `<script>`.
- **Klucz instalacyjny:** ten sam token `ack_...`, ale skopiowany ręcznie z panelu AsapChat (ścieżka zapasowa).
- **Config:** odpowiedź JSON z `GET /api/plugin/v1/config`. Zawiera adresy skryptów i ich atrybuty.
- **PKCE:** zabezpieczenie wymiany kodu. Moduł losuje `code_verifier`, wysyła do panelu tylko jego skrót (`code_challenge`), a przy wymianie kodu pokazuje oryginał. Przechwycony kod jest bez niego bezużyteczny.
- **`state`:** losowy ciąg, który moduł wysyła i musi dostać z powrotem bez zmian. Chroni przed podstawieniem cudzego połączenia.
- **Multistore:** tryb PrestaShop, w którym jedna instalacja obsługuje kilka sklepów.

## 3. Historie użytkownika

1. Jako sprzedawca chcę połączyć sklep z kontem AsapChat jednym przyciskiem, żeby nie kopiować kodu.
2. Jako sprzedawca bez konta chcę je założyć po kliknięciu „Połącz” i wrócić do modułu.
3. Jako sprzedawca chcę osobno włączyć lub wyłączyć czat i wyszukiwarkę.
4. Jako sprzedawca chcę widzieć, czy widget faktycznie działa na stronie.
5. Jako sprzedawca chcę rozłączyć sklep i mieć pewność, że skrypty zniknęły.
6. Jako sprzedawca, u którego przekierowanie nie działa (firewall, nietypowy adres BO), chcę wkleić klucz instalacyjny.

## 4. Ekran konfiguracji i stany

Jeden ekran: Moduły > AsapChat > Konfiguruj (`getContent()`). Trzy stany.

### 4.1 Niepołączony

Warunek: brak `ASAPCHAT_TOKEN`.

- Nagłówek i jedno zdanie, co moduł robi.
- Przycisk główny „Połącz z AsapChat”.
- Zdanie o danych: „Po kliknięciu przejdziesz do panel.asapchat.io. Przekażemy tam adres Twojego sklepu.”
- Rozwijane „Mam klucz instalacyjny”: pole tekstowe + „Zapisz klucz”.
- Link „Nie masz konta? Załóż je po kliknięciu Połącz, za darmo.”
- Na storefroncie: moduł nic nie wstawia i nie wykonuje żadnych żądań.

### 4.2 Połączony

Warunek: jest `ASAPCHAT_TOKEN` i ostatni config ma status 200.

- Plakietka „Połączono”, nazwa sklepu AsapChat, plan (np. „Free, 50 rozmów AI miesięcznie”).
- Przełącznik „Czat na stronie sklepu” (domyślnie włączony).
- Przełącznik „Wyszukiwarka AsapSearch” (domyślnie włączony, gdy dostępna). Gdy `config.search.enabled = false`: przełącznik nieaktywny i tekst „Wyszukiwarka nie jest jeszcze gotowa dla tego sklepu. Podłącz produkty w panelu AsapChat.” z linkiem `config.links.settings`.
- Status „Widget widziany na stronie: tak, data” albo „jeszcze nie” z podpowiedzią „Otwórz stronę sklepu w nowej karcie”.
- Przyciski: „Zapisz”, „Otwórz panel AsapChat” (`config.links.panel`, nowa karta), „Odśwież” (pobiera config), „Rozłącz” (z potwierdzeniem).

### 4.3 Błąd, wymagane ponowne połączenie

Warunek: ostatnie wywołanie config zwróciło 401 (połączenie odwołane) lub 410 (sklep usunięty w AsapChat).

- Komunikat ostrzegawczy z przyczyną.
- Przycisk „Połącz ponownie”.
- Skrypty nie są wstawiane.

Błąd przejściowy (timeout, 5xx) NIE zmienia stanu. Ekran zostaje „Połączony” z dopiskiem „Nie udało się odświeżyć ustawień, używamy ostatnich zapisanych”.

## 5. Przepływ „Połącz” w PrestaShop

1. Sprzedawca klika „Połącz z AsapChat” (POST formularza z tokenem BO).
2. Moduł losuje `state` (32 bajty, hex) i `code_verifier` (64 znaki). Zapisuje je w `Configuration` jako `ASAPCHAT_STATE` i `ASAPCHAT_VERIFIER` w kontekście bieżącego sklepu, razem z czasem utworzenia. Ważne 10 minut.
3. Moduł liczy `code_challenge = base64url(sha256(code_verifier))`.
4. `return_url` = link do kontrolera admina: `$this->context->link->getAdminLink('AdminAsapchatConnect')`. Zawiera token admina PrestaShop.
5. `site_url` = adres sklepu z bieżącego kontekstu (`$this->context->shop->getBaseURL(true)`).
6. Przekierowanie przeglądarki na `https://panel.asapchat.io/connect?platform=prestashop&site_url=...&return_url=...&state=...&code_challenge=...&v=1`.
7. Panel odsyła na `return_url` z `code` i `state` albo z `error=access_denied`.
8. `AdminAsapchatConnectController`:
   - PrestaShop sam sprawdza token admina i uprawnienia pracownika do zakładki.
   - Porównuje `state` z zapisanym przez `hash_equals`. Niezgodny lub przeterminowany: komunikat błędu, koniec.
   - Usuwa `ASAPCHAT_STATE` (jednorazowość).
   - Wywołuje `POST /api/plugin/v1/token` z `code` i `code_verifier`.
   - Sukces: zapisuje `ASAPCHAT_TOKEN`, `ASAPCHAT_CONFIG` (JSON), `ASAPCHAT_CONFIG_AT`, usuwa `ASAPCHAT_VERIFIER`, przekierowuje na ekran konfiguracji z komunikatem „Połączono”.
   - Błąd: komunikat „Nie udało się połączyć. Spróbuj ponownie albo użyj klucza instalacyjnego.”

Uwaga o adresie BO: katalog admina w PrestaShop ma losową nazwę (np. `/admin123abc/`), ale leży w tej samej domenie co sklep, więc warunek backendu „host `return_url` = host `site_url`” jest spełniony. W multistore z różnymi domenami `return_url` może mieć inny host niż sklep. Patrz pytania otwarte.

Kontroler rejestrujemy jako ukrytą zakładkę (`Tab` z `id_parent = -1`) w `install()`.

## 6. Klucz instalacyjny (ścieżka zapasowa)

1. Sprzedawca kopiuje klucz z panelu AsapChat (karta „Wtyczka”).
2. Wkleja w pole i zapisuje.
3. Moduł sprawdza format (`ack_` + 48 znaków), potem woła `GET /api/plugin/v1/config` z nagłówkiem `Authorization: Bearer <klucz>` i `X-Site-Url: <adres sklepu>`.
4. 200: zapis jak przy połączeniu. 401: „Klucz jest nieprawidłowy lub został odwołany”. 409: „Ten klucz jest przypisany do innej domeny”.

## 7. Wstawianie skryptów

Dane wyłącznie z zapisanego `ASAPCHAT_CONFIG`. Żadnych żądań HTTP w żądaniu klienta sklepu.

- Czat: gdy `ASAPCHAT_CHAT_ON` i `config.chat.enabled`. Hook `displayBeforeBodyClosingTag`. Zapas: `displayFooter`, jeśli pierwszy hook nie został wywołany w tym żądaniu (statyczna flaga w klasie).
- Wyszukiwarka: gdy `ASAPCHAT_SEARCH_ON` i `config.search.enabled`. Hook `displayHeader`.
- Szablon `views/templates/hook/scripts.tpl` buduje znacznik z mapy atrybutów:
  - `src` z `config.*.src`,
  - każda para z `config.*.attributes` jako atrybut HTML, wartość przez `escape:'htmlall'`,
  - wartość `true` renderuje sam atrybut (np. `defer`), `false` i `null` pomijamy,
  - nazwa atrybutu dopuszczona tylko, gdy pasuje do `^[a-zA-Z_][a-zA-Z0-9_\-]*$`.
- Nie używamy `registerJavascript` dla tych skryptów, bo widget czyta własne atrybuty ze znacznika, a łączenie JS (CCC) by je zgubiło. To jest świadomy wyjątek, opisany w dokumentacji modułu.

## 8. Pamięć configu

- Zapis: `ASAPCHAT_CONFIG` (JSON), `ASAPCHAT_CONFIG_AT` (timestamp).
- Odświeżanie: przy każdym otwarciu ekranu konfiguracji oraz raz na dobę. Dobowe odświeżenie: przy wejściu dowolnego pracownika do BO (hook `displayBackOfficeHeader`), gdy od `ASAPCHAT_CONFIG_AT` minęło ponad `refresh_after` sekund. Bez osobnego crona.
- Nigdy w żądaniu storefrontu.

## 9. Zachowanie przy błędach

| Odpowiedź config | Co robi moduł |
|---|---|
| 200 | Zapisuje nowy config |
| timeout, 5xx, błąd sieci | Zostawia stary config, skrypty działają dalej, dopisek na ekranie |
| 401 | Stan „Błąd”: przestaje wstawiać skrypty, czyści config, zostawia token do diagnozy |
| 410 | Jak 401, komunikat „Sklep został usunięty w AsapChat” |
| 409 (klucz i inna domena) | Komunikat, nic nie zapisuje |

## 10. Rozłączenie i odinstalowanie

- „Rozłącz”: `DELETE /api/plugin/v1/connection`, potem usunięcie wszystkich kluczy `ASAPCHAT_*`. Jeśli DELETE się nie uda, lokalne dane i tak usuwamy.
- `uninstall()`: to samo co „Rozłącz” dla każdego sklepu w multistore, usunięcie ukrytej zakładki, odpięcie hooków. Po odinstalowaniu w tabeli `configuration` nie zostaje ani jeden wiersz `ASAPCHAT_%`.

## 11. Tłumaczenia

Angielski w kodzie, polski w `translations/pl.php`. Wszystkie teksty przez `$this->l()` i `{l s='...' mod='asapchat'}`.

## 12. Lista bezpieczeństwa

- [ ] Brak żądań do AsapChat przed kliknięciem „Połącz” lub zapisaniem klucza.
- [ ] `state` jednorazowy, ważny 10 minut, porównanie `hash_equals`.
- [ ] `code_verifier` nigdy nie trafia do przeglądarki.
- [ ] Token `ack_...` nie pojawia się w HTML, logach ani komunikatach. W polu klucza po zapisaniu pokazujemy tylko 4 ostatnie znaki.
- [ ] Kontroler powrotu dostępny tylko dla zalogowanego pracownika z ważnym tokenem admina.
- [ ] Każdy POST w `getContent()` sprawdza token formularza.
- [ ] Atrybuty skryptów escapowane, nazwy atrybutów filtrowane wyrażeniem regularnym.
- [ ] `src` skryptu akceptowany tylko z hostów `asapchat.io` i `panel.asapchat.io` po `https`.
- [ ] Brak endpointów frontowych modułu. Brak parametru `debug`.

## 13. Macierz testów

| Oś | Wartości |
|---|---|
| PrestaShop | 1.7.8.x, 8.1 lub 8.2, 9.0 |
| PHP | 7.4 (dla 1.7.8), 8.1, 8.3 |
| Motyw | Classic, Hummingbird |
| Tryb debug | włączony, zero notice |
| Multistore | wyłączony; włączony z 2 sklepami i osobnymi połączeniami |
| Cache | Smarty cache włączony, CCC: łączenie i minifikacja JS włączone |
| Ścieżki | połączenie przyciskiem, klucz instalacyjny, anulowanie w panelu, rozłączenie, odinstalowanie |

Dla każdej kombinacji wersji i motywu: skrypt czatu i wyszukiwarki widoczny w źródle strony głównej, strony produktu i koszyka, dokładnie raz.

## 14. Definicja ukończenia

1. Wszystkie pozycje macierzy przechodzą, wyniki w issue „Macierz zgodności”.
2. validator.prestashop.com: zero błędów.
3. Lista bezpieczeństwa odhaczona w PR.
4. Zip ma poprawną strukturę (`asapchat.zip` > `asapchat/` > `asapchat.php`), bez `__MACOSX` i `.DS_Store`.
5. Dokumentacja PDF pl i en w `asapchat/docs/`.
6. Wydanie w GitHub Releases z zipem.

## 15. Fazy i issues

| Faza | Issues |
|---|---|
| A. Fundament | 1 szkielet i CI, 2 ekran konfiguracji w trzech stanach |
| B. Połączenie | 3 połączenie przyciskiem, 4 klucz instalacyjny |
| C. Storefront | 5 czat, 6 wyszukiwarka |
| D. Utrzymanie | 7 status, odświeżanie, rozłączenie, 8 odinstalowanie i multistore |
| E. Wydanie | 9 tłumaczenia i dokumentacja, 10 macierz zgodności, 11 wydanie zip, 12 pytanie do PrestaShop o Addons |

## 16. Migracja ze starego prototypu

Prototyp z 2024 roku ma inną nazwę techniczną: `prestashopasapchat`. PrestaShop traktuje go jako osobny moduł, więc nie ma automatycznej aktualizacji.

1. Sklep z prototypem musi go **odinstalować** i usunąć katalog `modules/prestashopasapchat/`. Powód: prototyp nie spełnia wymagań bezpieczeństwa nowej wersji.
2. Potem instaluje `asapchat` i łączy sklep przyciskiem.
3. Nowy moduł w `install()` sprawdza `Module::isInstalled('prestashopasapchat')` i pokazuje na ekranie konfiguracji ostrzeżenie z tą instrukcją. Nie odinstalowuje go sam.
4. Listę sklepów z prototypem i unieważnienie kluczy prowadzi właściciel poza tym repo.

## 17. Pytania otwarte

1. Multistore z różnymi domenami: BO działa pod jedną domeną, a łączony sklep pod inną. Backend wymaga równości hostów `return_url` i `site_url`. Propozycja: w takim układzie pokazujemy tylko ścieżkę klucza instalacyjnego. Do potwierdzenia z autorem backendu.
2. Czy recenzenci Addons zaakceptują skrypt ładowany z `asapchat.io` (opisany w `header_csp.txt`)? Do potwierdzenia w pytaniu do partner support.
3. Czy `displayBeforeBodyClosingTag` istnieje w 1.7.8 we wszystkich popularnych motywach? Jeśli nie, zapas `displayFooter` wystarcza. Do sprawdzenia w issue 5.
4. Minimalna wersja wymagana przez Addons (tylko 9.0 czy także starsze): do potwierdzenia.
