# Specyfikacja: moduł AsapChat dla PrestaShop, wersja 1

Data: 2026-11-11. Status: zatwierdzona do realizacji. Kontrakt API: `docs/CONNECT-API.md`. Mockup: `docs/mockups/configure.html`.

Gdy ten plik i kontrakt mówią co innego, wygrywa kontrakt. Różnicę zgłoś w issue i oznacz `prz10`.

Zadania nazywamy prefiksem z tytułu issue (`01` do `14`, lista w punkcie 19). Numer GitHub (`#N`) to co innego i nie wyznacza kolejności.

## 1. Cel

Sprzedawca wgrywa moduł, klika jeden przycisk i ma na sklepie czat AsapChat oraz wyszukiwarkę AsapSearch. Nie wkleja kodu do motywu. Z ekranu modułu ustawia wygląd czatu, wybiera, gdzie widgety się pokazują, i widzi, czy wszystko działa.

Moduł jest darmowy. Rozdajemy go jako plik zip z GitHub Releases. Link do pliku jest w panelu AsapChat. Moduł sam pokazuje, że wyszła nowa wersja.

## 2. Słownik

- **Back office (BO):** panel administracyjny PrestaShop.
- **Hook:** miejsce w PrestaShop, w które moduł wpina własny kod (np. przed `</body>`).
- **Token połączenia:** tajny ciąg `ack_` i 48 znaków `[A-Za-z0-9]` (razem 52) wydany przez AsapChat. Dowodzi, że ten sklep jest połączony z kontem. To nie jest publiczny `token` z atrybutów znacznika `<script>`.
- **Klucz instalacyjny:** ten sam token `ack_...`, ale skopiowany ręcznie z panelu AsapChat (ścieżka zapasowa).
- **Config:** odpowiedź JSON z `GET /api/plugin/v1/config`. Zawiera adresy skryptów, ich atrybuty, wygląd, statusy i numer najnowszej wersji modułu.
- **PKCE:** zabezpieczenie wymiany kodu. Moduł losuje `code_verifier`, wysyła do panelu tylko jego skrót (`code_challenge`), a przy wymianie kodu pokazuje oryginał.
- **`state`:** losowy ciąg, który moduł wysyła i musi dostać z powrotem bez zmian. Chroni przed podstawieniem cudzego połączenia.
- **Multistore:** tryb PrestaShop, w którym jedna instalacja obsługuje kilka sklepów.
- **Webservice:** wbudowane API PrestaShop. AsapChat czyta przez nie produkty i zamówienia. Moduł go nie używa, tylko pokazuje instrukcję.

## 3. Historie użytkownika

1. Jako sprzedawca chcę połączyć sklep z kontem AsapChat jednym przyciskiem.
2. Jako sprzedawca bez konta chcę je założyć po kliknięciu „Połącz” i sam wrócić do modułu.
3. Jako sprzedawca chcę osobno włączyć czat, wyszukiwarkę i dodawanie do koszyka z wyszukiwarki.
4. Jako sprzedawca chcę zmienić nazwę asystenta, kolor, pozycję i powitania bez wychodzenia z PrestaShop.
5. Jako sprzedawca chcę ukryć widgety na wybranych stronach i opóźnić pojawienie się czatu.
6. Jako sprzedawca chcę widzieć, czy widgety działają i czy AsapChat ma moje produkty i zamówienia.
7. Jako sprzedawca chcę wiedzieć, jak wygenerować klucz Webservice.
8. Jako sprzedawca chcę wiedzieć, że wyszła nowa wersja modułu.
9. Jako sprzedawca chcę rozłączyć sklep i mieć pewność, że skrypty zniknęły.
10. Jako sprzedawca, u którego przekierowanie nie działa, chcę wkleić klucz instalacyjny.

## 4. Co jest zapisane gdzie

To najważniejsza tabela w tym dokumencie. Zanim dodasz pole, sprawdź, po której stronie żyje.

### 4.1 W PrestaShop (`Configuration`, osobno dla każdego sklepu)

| Klucz | Zawartość | Kiedy znika |
|---|---|---|
| `ASAPCHAT_TOKEN` | token połączenia `ack_...`, zapisany jawnie (tylko po stronie serwera, szyfrowania w v1 nie ma) | rozłączenie, błąd 401/409/410, odinstalowanie |
| `ASAPCHAT_CONFIG` | cała ostatnia odpowiedź config (JSON zakodowany `base64_encode`) | jak wyżej |
| `ASAPCHAT_CONFIG_AT` | czas ostatniego udanego pobrania (timestamp) | jak wyżej |
| `ASAPCHAT_RETRY_AT` | czas, przed którym nie ponawiamy odświeżania w tle po błędzie sieci | po udanym pobraniu, odinstalowanie |
| `ASAPCHAT_ERROR` | kod, który wymusił ponowne połączenie: `401`, `409` albo `410` | po udanym połączeniu, odinstalowanie |
| `ASAPCHAT_STATE` | `state` z bieżącej próby połączenia | po powrocie z panelu, po 2 godzinach |
| `ASAPCHAT_VERIFIER` | `code_verifier` z bieżącej próby | jak wyżej |
| `ASAPCHAT_STATE_AT` | czas utworzenia `state` (timestamp) | jak wyżej |
| `ASAPCHAT_CHAT_ON` | przełącznik „Czat”, domyślnie `1` | odinstalowanie |
| `ASAPCHAT_SEARCH_ON` | przełącznik „Wyszukiwarka AsapSearch”, domyślnie `1` | odinstalowanie |
| `ASAPCHAT_CART_ON` | przełącznik „Dodawanie do koszyka z wyszukiwarki”, domyślnie `1` | odinstalowanie |
| `ASAPCHAT_DELAY` | opóźnienie czatu w sekundach, `0` do `60`, domyślnie `0` | odinstalowanie |
| `ASAPCHAT_EXCLUDE` | wykluczenia (JSON zakodowany `base64_encode`): `{"cms":[1,4],"categories":[12],"products":[12,48],"url_contains":["/koszyk"]}` | odinstalowanie |

Dlaczego `base64_encode`: `Configuration::updateValue` wycina znaczniki HTML z wartości. Powitanie ze znakiem `<` zepsułoby JSON. Zakodowany ciąg przechodzi bez zmian.

Ustawienia lokalne (pięć ostatnich wierszy) zostają po rozłączeniu i po błędzie 401. Sprzedawca łączy sklep ponownie i ma je z powrotem.

### 4.2 W AsapChat (moduł tylko czyta albo wysyła)

| Dane | Skąd w config | Czy moduł może zmienić |
|---|---|---|
| Nazwa sklepu, plan, limit rozmów | `shop` | nie |
| Adresy i atrybuty skryptów | `chat`, `search` | nie |
| Wygląd: nazwa asystenta, kolor, pozycja, powitania | `settings` | tak, przez `PATCH /api/plugin/v1/settings` |
| Podłączone źródła zamówień i produktów | `sources` | nie, link `sources.connect_url` prowadzi do panelu |
| Czy widget był widziany na stronie | `status` | nie |
| Najnowsza wersja modułu, adres zipa, adres listy zmian | `plugin` | nie |

Wyglądu nie zapisujesz w osobnych kluczach `Configuration`. Jedyna lokalna kopia to `settings` wewnątrz `ASAPCHAT_CONFIG`.

Moduł nie ma własnych tabel w bazie.

## 5. Ekran konfiguracji

Jeden ekran: Moduły > Menedżer modułów > AsapChat > Konfiguruj (`getContent()`). Trzy stany. Wygląd pokazuje mockup, otwórz go w przeglądarce.

**Spec a mockup.** Zachowanie i teksty bierzesz z tego dokumentu, układ i wygląd z mockupu. Gdy tekst w mockupie różni się od tekstu tutaj, obowiązuje ten stąd. Mockup pokazuje trzy stany główne. Warianty opisane tylko tutaj (punkt 5.6) budujesz z tych samych elementów. Klasy w kodzie mają prefiks `asapchat-`. Nazwy `mk-ps-*` i font z Google Fonts są tylko w mockupie.

### 5.1 Wygląd wspólny dla wszystkich stanów

Ekran wygląda jak panel AsapChat osadzony w back office. Nie używamy `HelperForm` ani klas Bootstrapa z BO.

- Cały ekran siedzi w jednym elemencie `<div class="asapchat-app">`. Każda reguła CSS zaczyna się od `.asapchat-app`. Każda klasa ma prefiks `asapchat-`. Nic nie może zmienić wyglądu reszty BO.
- Na górze czarny pasek (`#161616`) z białym logo AsapChat (`asapchat/views/img/asapchat-logo-white.svg`, plik jest w repo) i fioletową linią 4 px pod spodem (`#6543f0`).
- Tło `#fafafa`. Karty białe, obrys `#e9eaeb`, promień 16 px, cień `0 6px 20px rgba(15,26,23,.05)`.
- Tekst `#161616`, etykiety `#424448`, opisy `#56595f`.
- Akcent i przycisk główny `#6543f0` z białym tekstem. Tła pomocnicze: `#f6f4fe` i `#ece8fd`.
- Pola: wysokość 44 px, promień 10 px, obrys `#d5d7da`. Fokus: obrys `#6543f0` i pierścień 3 px `rgba(101,67,240,.2)`.
- Przycisk główny ma 48 px wysokości. Na ekranie jest jeden fioletowy przycisk. Pozostałe są białe z obrysem.
- Font Open Sans (400, 600, 700, 800) ładowany z paczki modułu (`views/fonts/`, pliki woff2 z pakietu `@fontsource/open-sans`, zestawy `latin` i `latin-ext`, licencja OFL obok plików). Nie ładuj fontu z zewnętrznego adresu. Tytuł 30 px/800, tytuł karty 18 px/800, treść 14 do 15 px, opisy 13 px.
- Przełączniki zamiast checkboxów. Statusy jako pill z kropką: zielona `#05c732` albo szara.
- Bez gradientów, bez emoji, bez znaku pauzy w tekstach.
- Ekran działa na szerokości 390 px i 1280 px. Na wąskim ekranie pola układają się w jedną kolumnę.

CSS i font dołączasz tylko na ekranie konfiguracji modułu (hook `displayBackOfficeHeader`, warunek: kontroler `AdminModules` i `configure=asapchat`).

### 5.2 Stan „Niepołączony”

Warunek: brak `ASAPCHAT_TOKEN` i brak `ASAPCHAT_ERROR`.

Kolejność elementów:

1. Czarny pasek z logo.
2. Tytuł „Połącz sklep z AsapChat”.
3. Jedno zdanie: „Moduł doda do Twojego sklepu czat AI i wyszukiwarkę produktów AsapSearch. Nie musisz niczego wklejać do motywu.”
4. Przycisk główny „Połącz z AsapChat”.
5. Tekst pod przyciskiem: „Nie masz konta? Założysz je w następnym kroku, za darmo.”
6. Tekst o danych: „Po kliknięciu przejdziesz do panel.asapchat.io. Przekażemy tam adres Twojego sklepu: {adres}.”
7. Zwinięty blok „Mam klucz instalacyjny”: pole i przycisk „Połącz kluczem”.

W tym stanie moduł nic nie wstawia na stronie sklepu i nie wysyła żadnych żądań do AsapChat.

Gdy przycisk „Połącz” nie może zadziałać (patrz 6.3), pokazujesz zamiast niego zdanie z wyjaśnieniem, a blok klucza jest od razu rozwinięty.

### 5.3 Stan „Połączony”

Warunek: jest `ASAPCHAT_TOKEN` i `ASAPCHAT_CONFIG`.

Wszystkie pola ekranu są w jednym formularzu z jednym przyciskiem „Zapisz zmiany” na dole. Sekcje w tej kolejności:

**1. Nagłówek**

- W czarnym pasku obok logo: link „Otwórz panel AsapChat” (`links.panel`, nowa karta).
- Pod paskiem: nazwa sklepu (`shop.name`) jako tytuł, pill „Połączono”, pill z planem, adres sklepu.
- Tekst planu: gdy `shop.ai_conversations_limit` jest liczbą, pokaż „{Plan}: {limit} rozmów AI miesięcznie” (dla Free: „Free: 50 rozmów AI miesięcznie”). Gdy jest `null`, pokaż samą nazwę planu z wielkiej litery.
- Przycisk „Rozłącz” (biały, czerwony tekst). Przed wysłaniem pyta: „Rozłączyć sklep? Czat i wyszukiwarka znikną ze strony.”

**2. Status**

| Wiersz | Wartość z config | Co pokazujesz |
|---|---|---|
| Czat | `status.widget_seen_at` to data | zielony pill „Czat działa”, pod spodem „Ostatnio widziany na stronie: {data}” |
| Czat | `status.widget_seen_at` to `null` | szary pill „Jeszcze niewidziany na stronie”, pod spodem „Otwórz sklep w nowej karcie, żeby sprawdzić.” |
| Wyszukiwarka | `status.search_seen_at` to data | zielony pill „Wyszukiwarka działa” i data |
| Wyszukiwarka | `status.search_seen_at` to `null` | szary pill „Jeszcze niewidziana na stronie”, pod spodem „Otwórz sklep w nowej karcie, żeby sprawdzić.” |

Daty przychodzą w ISO 8601 ze strefą (np. `2026-11-11T10:42:00+01:00`). Zamieniasz je na znacznik czasu (`strtotime`) i pokazujesz w strefie sklepu, w formacie daty i godziny PrestaShop (`Tools::displayDate` z pełną datą; sygnatura tej metody różni się między 1.7.8 a 8, sprawdź obie). Tak samo data w stopce. Mockup pokazuje „dzisiaj o 10:42” tylko poglądowo.

Wiersz wyszukiwarki pokazujesz tylko, gdy `search.enabled` jest `true`. Inaczej wystarcza komunikat z sekcji „Widgety”.

Pod wierszami: przycisk „Odśwież” (POST z tokenem BO, pobiera config jeszcze raz) i link „Otwórz sklep w nowej karcie”.

Gdy ostatnie odświeżenie się nie udało (timeout, 429, 5xx), nad sekcją pokazujesz notkę: „Nie udało się pobrać ustawień z AsapChat. Używamy ostatnich zapisanych.” Stan ekranu się nie zmienia.

**3. Widgety** (ustawienia lokalne)

| Przełącznik | Klucz | Zachowanie |
|---|---|---|
| Czat | `ASAPCHAT_CHAT_ON` | wyłączony = skrypt czatu nie trafia na stronę |
| Wyszukiwarka AsapSearch | `ASAPCHAT_SEARCH_ON` | wyłączony = skrypt wyszukiwarki nie trafia na stronę |
| Dodawanie do koszyka z wyszukiwarki | `ASAPCHAT_CART_ON` | wyłączony = pomijasz atrybut `allow_add-to-cart` w znaczniku wyszukiwarki |

Gdy `search.enabled` jest `false`: przełącznik „Wyszukiwarka AsapSearch” jest nieaktywny (`disabled`), a pod nim tekst „Wyszukiwarka nie jest jeszcze gotowa, bo AsapChat nie ma Twoich produktów.” z linkiem „Podłącz produkty w AsapChat” (`sources.connect_url`, nowa karta). Zapisanej wartości `ASAPCHAT_SEARCH_ON` wtedy nie zmieniasz.

Przełącznik „Dodawanie do koszyka” jest nieaktywny, gdy wyszukiwarka jest wyłączona albo niedostępna.

**4. Wygląd** (zapis w AsapChat)

| Pole | Pole API | Kontrolka | Reguła |
|---|---|---|---|
| Nazwa asystenta | `bot_name` | pole tekstowe | wymagane, 1 do 40 znaków (puste = błąd pola) |
| Kolor główny | `main_color` | `<input type="color">` i pole tekstowe z kodem | `#rrggbb` |
| Pozycja czatu | `position` | dwa kafelki: „Prawy dolny róg” (`right-bottom`), „Lewy dolny róg” (`left-bottom`) | jedna z dwóch wartości |
| Powitanie 1 | `greeting_1` | pole tekstowe | do 120 znaków, puste = bez dymka |
| Powitanie 2 | `greeting_2` | pole tekstowe | do 120 znaków, puste = bez dymka |

Pola wypełniasz wartościami z `settings` w zapisanym config. Opis sekcji: „Te same ustawienia co w panelu AsapChat. Zmiana tutaj jest widoczna tam i odwrotnie.” Zapis opisuje punkt 8.

**5. Gdzie pokazywać** (ustawienia lokalne)

| Pole | Kontrolka | Zapis w `ASAPCHAT_EXCLUDE` albo `ASAPCHAT_DELAY` |
|---|---|---|
| Opóźnienie pojawienia się czatu | liczba 0 do 60, sekundy | `ASAPCHAT_DELAY` |
| Wykluczone strony CMS | lista wyboru stron CMS sklepu (`CMS::listCms`), można wybrać wiele | `cms`: lista `id_cms` |
| Wykluczone kategorie | lista wyboru kategorii sklepu, można wybrać wiele | `categories`: lista `id_category` |
| Wykluczone produkty (ID) | pole tekstowe, ID po przecinku | `products`: lista `id_product` |
| Adres zawiera | pole wielowierszowe, jeden fragment w wierszu | `url_contains`: lista tekstów |

Zasady:

- Na wykluczonej stronie nie wstawiasz żadnego skryptu, ani czatu, ani wyszukiwarki.
- Wykluczona kategoria oznacza stronę listy tej kategorii. Produkty z tej kategorii nie są przez to wykluczone.
- Produkty wpisuje się po ID, bo PrestaShop 1.7.8 do 9 nie ma jednego wspólnego selektora produktów dla ekranu modułu. Podpowiedź pod polem: „ID produktów po przecinku. ID znajdziesz na liście: Katalog, potem Produkty.”
- „Adres zawiera” porównujesz z `$_SERVER['REQUEST_URI']` funkcją `strpos`. Wielkość liter ma znaczenie. Puste wiersze pomijasz. Maksymalnie 50 wierszy, każdy do 200 znaków.
- Przy zapisie rzutujesz wszystkie ID na `int` i wyrzucasz zera oraz powtórzenia.

**6. Źródło zamówień i produktów**

Zdanie pod tytułem: „Dzięki temu asystent zna Twoje produkty i odpowiada na pytania o status zamówienia.”

| Wiersz | Wartość z config | Pill |
|---|---|---|
| Zamówienia | `sources.orders_connected` | zielony „Podłączone” albo szary „Niepodłączone” |
| Produkty | `sources.products_connected` | zielony „Podłączone” albo szary „Niepodłączone” |

Przycisk „Podłącz w AsapChat” (`sources.connect_url`, nowa karta). Pola `sources.systems` w tej wersji nie pokazujesz (kontrakt na to pozwala).

Pod przyciskiem zwinięty blok „Jak wygenerować klucz API w PrestaShop” z tym tekstem:

1. W menu sklepu wejdź w Zaawansowane, potem API (Webservice).
2. Włącz opcję „Włącz usługę sieciową PrestaShop” i zapisz.
3. Kliknij „Dodaj nowy klucz”, potem „Generuj”.
4. W tabeli uprawnień zaznacz tylko kolumnę GET dla: `products`, `categories`, `combinations`, `stock_availables`, `images`, `orders`, `order_states`, `customers`, `addresses`, `carriers`.
5. Ustaw status klucza na „Tak” i zapisz.
6. Skopiuj klucz, kliknij „Podłącz w AsapChat” i wklej go tam razem z adresem sklepu.

Moduł sam nie tworzy klucza Webservice i nie wysyła go do AsapChat. To robi sprzedawca w panelu AsapChat.

**7. Aktualizacja**

Pasek pokazujesz tylko wtedy, gdy jest nowsza wersja. Dokładne zasady: punkt 10.

**Stopka ekranu**

Tekst „Ustawienia pobrane z AsapChat: {data}. Wersja modułu: {wersja}.” i przycisk główny „Zapisz zmiany”.

### 5.4 Stan „Błąd, połącz ponownie”

Warunek: jest `ASAPCHAT_ERROR`.

| `ASAPCHAT_ERROR` | Tekst |
|---|---|
| `401` | „Połączenie zostało odwołane w panelu AsapChat albo klucz instalacyjny wygasł.” |
| `409` | „To połączenie jest przypisane do innej domeny. Połącz ten sklep jeszcze raz.” |
| `410` | „Ten sklep został usunięty w AsapChat.” |

Do każdego tekstu dopisujesz: „Czat i wyszukiwarka są teraz wyłączone na stronie sklepu.”

Tytuł stanu: „Połącz sklep ponownie”, obok szary pill „Rozłączono”.

Pod spodem: notka „Twoje ustawienia w tym module (przełączniki, opóźnienie, wykluczenia) zostały zachowane. Wrócą po połączeniu.”, przycisk główny „Połącz ponownie” (działa jak „Połącz z AsapChat”) i zwinięty blok klucza instalacyjnego.

Skrypty nie są wstawiane.

### 5.5 Multistore na ekranie konfiguracji

Gdy w BO wybrany jest kontekst „Wszystkie sklepy” albo grupa sklepów, ekran pokazuje tylko czarny pasek i zdanie: „Wybierz jeden sklep z listy u góry, żeby go połączyć z AsapChat.” Żadnych formularzy.

### 5.6 Warianty, których nie ma w mockupie

Budujesz je z elementów mockupu. Teksty stąd.

| Wariant | Gdzie | Co pokazujesz |
|---|---|---|
| Przycisk nie może zadziałać (6.3) | stan „Niepołączony” i „Błąd” | zamiast przycisku zdanie z 6.3 w szarym polu informacyjnym, blok klucza rozwinięty |
| Kontekst „Wszystkie sklepy” (5.5) | cały ekran | czarny pasek i jedno zdanie w białej karcie |
| Nieudane odświeżenie | stan „Połączony”, nad sekcją „Status” | żółte pole: „Nie udało się pobrać ustawień z AsapChat. Używamy ostatnich zapisanych.” |
| Błąd pola | przy polu | czerwony obrys pola i czerwony tekst 13 px pod polem. Teksty niżej |
| Sukces | nad pierwszą kartą | zielone pole z tekstem: „Połączono sklep z AsapChat.”, „Zapisano.”, „Rozłączono.” |
| Informacja | nad pierwszą kartą | szare pole: „Anulowano łączenie.”, „Spróbuj połączyć ponownie.” |
| Błąd `409` i `410` | stan „Błąd” | ten sam układ co `401`, inny tekst z tabeli w 5.4 |
| Config w nowszej wersji (`v` inne niż `1`) | stan „Połączony”, nad sekcją „Status” | żółte pole: „Ta wersja modułu jest za stara dla AsapChat. Zaktualizuj moduł.” Zapisanej kopii nie nadpisujesz |

Teksty błędów pól (własne teksty modułu, odpowiedź `422` wskazuje tylko pole):

| Pole | Tekst |
|---|---|
| Nazwa asystenta | „Podaj nazwę, najwyżej 40 znaków.” |
| Kolor główny | „Podaj kolor w formacie #rrggbb.” |
| Pozycja czatu | „Wybierz jedną z dwóch pozycji.” |
| Powitanie 1, Powitanie 2 | „Powitanie może mieć najwyżej 120 znaków.” |
| Opóźnienie | „Podaj liczbę od 0 do 60.” |
| Wykluczone produkty (ID) | „Wpisz same numery ID, po przecinku.” |
| Adres zawiera | „Najwyżej 50 wierszy, każdy do 200 znaków.” |
| Klucz instalacyjny | teksty z punktu 7 |

## 6. Połączenie przyciskiem

### 6.1 Kroki

1. Sprzedawca klika „Połącz z AsapChat”. To jest POST formularza z tokenem BO.
2. Moduł losuje:
   - `state`: `bin2hex(random_bytes(16))`, czyli 32 znaki,
   - `code_verifier`: `rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=')`, czyli 64 znaki.
3. Zapisuje `ASAPCHAT_STATE`, `ASAPCHAT_VERIFIER` i `ASAPCHAT_STATE_AT` (bieżący czas) dla bieżącego sklepu. Ważność: **2 godziny**. Nowe kliknięcie nadpisuje poprzednią próbę.
4. Liczy `code_challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')`. Wynik ma 43 znaki.
5. `return_url` = `$this->context->link->getAdminLink('AdminAsapchatConnect')`. Adres zawiera token admina PrestaShop.
6. `site_url` = `$this->context->shop->getBaseURL(true)`.
7. Przekierowuje przeglądarkę na `{ASAPCHAT_BASE}/connect?platform=prestashop&site_url=...&return_url=...&state=...&code_challenge=...`. Każdą wartość kodujesz `rawurlencode`.
8. Panel odsyła na `return_url` z `code` i `state` albo z `error=access_denied` i `state`.
9. Kontroler `AdminAsapchatConnectController`:
   - PrestaShop sam sprawdza, że pracownik jest zalogowany, ma ważny token admina i dostęp do zakładki.
   - Kontroler sprawdza, że `ASAPCHAT_STATE` istnieje, ma mniej niż 2 godziny i jest równy parametrowi `state` (`hash_equals`).
   - Kasuje `ASAPCHAT_STATE` i `ASAPCHAT_STATE_AT` od razu po porównaniu, także gdy wynik jest negatywny.
   - Gdy `state` się nie zgadza albo wygasł: komunikat „Spróbuj połączyć ponownie.” Bez wymiany kodu.
   - Gdy przyszło `error=access_denied`: komunikat „Anulowano łączenie.” Bez wymiany kodu.
   - Inaczej woła `POST /api/plugin/v1/token` z `code` i `code_verifier`.
   - `200`: zapisuje `ASAPCHAT_TOKEN`, `ASAPCHAT_CONFIG` (pole `config` z odpowiedzi), `ASAPCHAT_CONFIG_AT`. Kasuje `ASAPCHAT_VERIFIER` i `ASAPCHAT_ERROR`. Przekierowuje na ekran konfiguracji z komunikatem „Połączono sklep z AsapChat.”
   - `400 invalid_grant`, `429`, `5xx` albo błąd sieci: kasuje `ASAPCHAT_VERIFIER`, komunikat „Nie udało się połączyć. Spróbuj ponownie albo użyj klucza instalacyjnego.” Kod jest jednorazowy, więc ponowna próba to nowe kliknięcie „Połącz”.

Wywołanie `/token` idzie bez `Authorization` i bez `X-Site-Url`.

Kontroler rejestrujesz w `install()` jako ukrytą zakładkę (`Tab` z `id_parent = -1`).

Stała `ASAPCHAT_BASE` ma wartość `https://panel.asapchat.io`. Definiujesz ją w `asapchat.php` tylko wtedy, gdy nie istnieje (`if (!defined('ASAPCHAT_BASE'))`). Nadpisanie (w `config/defines_custom.inc.php`) służy tylko do wskazania atrapy serwera. Osobnego środowiska testowego nie ma: pracujesz na produkcji z własnym kontem (kontrakt, punkt 7).

### 6.2 Ścieżka nowego użytkownika

Sprzedawca bez konta AsapChat przechodzi tę drogę:

1. Klika „Połącz z AsapChat” w module.
2. W panelu wybiera „Załóż darmowe konto”.
3. Przechodzi kreator AsapChat: rejestracja, kod z maila, kolejne kroki.
4. Po kreatorze panel sam pokazuje ekran zgody. Sprzedawca klika „Połącz”.
5. Panel odsyła go na `return_url`. Moduł wymienia kod na token. Sprzedawca widzi ekran „Połączony”.

Moduł nie robi tu nic dodatkowego. Musi tylko trzymać `state` przez 2 godziny, bo kreator trwa. Nie skracaj tego czasu.

Dwa przypadki brzegowe:

- Sesja pracownika w BO wygasła w trakcie kreatora. PrestaShop pokaże logowanie. Po zalogowaniu kod może być już za stary (żyje 120 sekund). Wtedy sprzedawca widzi „Nie udało się połączyć. Spróbuj ponownie...” i klika „Połącz” jeszcze raz. Tym razem ma już konto, więc trwa to chwilę.
- Sprzedawca wrócił po ponad 2 godzinach. Widzi „Spróbuj połączyć ponownie.”

### 6.3 Kiedy przycisk nie może zadziałać

Backend wymaga, żeby `return_url` był po `https` oraz na tym samym hoście i porcie co `site_url` (`www.` nie ma znaczenia). Domena z polskimi znakami musi iść jako punycode (`idn_to_ascii`, gdy funkcja jest dostępna; gdy nie ma, a host ma znaki spoza ASCII, traktuj to jak niespełniony warunek). Wyjątek od `https`: `http://localhost` i `http://127.0.0.1`.

Przed pokazaniem przycisku sprawdzasz dwa warunki:

1. Host i port z adresu BO (`Tools::getHttpHost()`) są takie same jak host i port sklepu (po usunięciu `www.`).
2. Adres BO jest po `https` albo host to `localhost` lub `127.0.0.1`.

Gdy któryś nie jest spełniony, nie pokazujesz przycisku „Połącz”. Pokazujesz zdanie „W tym sklepie połączenie przyciskiem nie zadziała, bo panel sklepu działa pod innym adresem niż sklep. Użyj klucza instalacyjnego.” i rozwinięty blok klucza. Tak bywa w multistore, gdy każdy sklep ma własną domenę.

## 7. Połączenie kluczem instalacyjnym

1. Sprzedawca generuje klucz w panelu AsapChat: edycja sklepu, link „Wtyczka”, potem „Klucz instalacyjny”. Wybiera tam platformę „PrestaShop”: od niej zależy sekcja `plugin` w config, czyli pasek aktualizacji.
2. Wkleja go w pole i klika „Połącz kluczem” (POST z tokenem BO).
3. Moduł obcina spacje i sprawdza format: `^ack_[A-Za-z0-9]{48}$`. Zły format: „Klucz ma zły format. Skopiuj go jeszcze raz z panelu AsapChat.” Bez żądania.
4. Woła `GET /api/plugin/v1/config` z nagłówkami `Authorization: Bearer <klucz>`, `Accept: application/json`, `X-Site-Url: <adres sklepu>`.

| Odpowiedź | Co robi moduł |
|---|---|
| `200` | zapisuje `ASAPCHAT_TOKEN`, `ASAPCHAT_CONFIG`, `ASAPCHAT_CONFIG_AT`, kasuje `ASAPCHAT_ERROR`, pokazuje „Połączono sklep z AsapChat.” |
| `401` | „Klucz jest nieprawidłowy, odwołany albo wygasł. Klucz trzeba użyć w ciągu 24 godzin od wygenerowania.” Nic nie zapisuje |
| `409` | „Ten klucz jest przypisany do innej domeny.” Nic nie zapisuje |
| `410` | „Ten sklep został usunięty w AsapChat.” Nic nie zapisuje |
| inne, brak sieci | „Nie udało się połączyć z AsapChat. Spróbuj za chwilę.” Nic nie zapisuje |

Pole klucza zawsze renderujesz puste. Zapisanego tokenu nie pokazujesz nigdzie, nawet częściowo.

## 8. Zapis formularza („Zapisz zmiany”)

Jeden POST z tokenem BO. Kolejność:

1. Walidujesz pola lokalne (opóźnienie 0 do 60, listy ID, wiersze „Adres zawiera”). Błąd: komunikat przy polu, nic nie zapisujesz.
2. Zapisujesz ustawienia lokalne do `Configuration`.
3. Porównujesz pola sekcji „Wygląd” z `settings` w zapisanym config. Bierzesz tylko te, które się różnią.
4. Gdy nic się nie różni: koniec, komunikat „Zapisano.”
5. Gdy coś się różni: `PATCH /api/plugin/v1/settings` tylko ze zmienionymi polami, jako JSON (`Content-Type: application/json`). Kolor wraca małymi literami, porównuj bez rozróżniania wielkości liter.

| Odpowiedź PATCH | Co robi moduł |
|---|---|
| `200` | odpowiedź to pełny config. Zapisuje go od razu do `ASAPCHAT_CONFIG` i `ASAPCHAT_CONFIG_AT`. Komunikat „Zapisano.” |
| `422` | kształt odpowiedzi: kontrakt, punkt 4 (`errors` to mapa pole, lista komunikatów). Przy każdym polu z `errors` pokazujesz własny tekst modułu z punktu 5.6. Pola zostają wypełnione tym, co wpisał sprzedawca. Ustawienia lokalne są już zapisane |
| `401`, `409`, `410` | jak w punkcie 11 |
| `429`, `5xx`, brak sieci | komunikat „Ustawienia modułu zapisane. Wyglądu nie udało się zapisać w AsapChat, spróbuj za chwilę.” Pola wyglądu zostają wypełnione |

## 9. Wstawianie skryptów na stronie sklepu

Dane wyłącznie z zapisanego `ASAPCHAT_CONFIG`. Strona sklepu nigdy nie wysyła żądania do AsapChat i nigdy na nie nie czeka.

### 9.1 Hooki

| Hook | Do czego |
|---|---|
| `displayHeader` | skrypt wyszukiwarki |
| `displayBeforeBodyClosingTag` | skrypt czatu |
| `displayFooter` | zapas dla czatu, gdy motyw nie woła poprzedniego hooka |
| `displayBackOfficeHeader` | CSS ekranu konfiguracji i odświeżanie config w tle (punkt 10) |

Statyczna flaga w klasie modułu pilnuje, żeby skrypt czatu wyszedł w jednym żądaniu dokładnie raz. `displayFooter` wykonuje się przed `displayBeforeBodyClosingTag`, więc czat wstawiasz w tym, który przyjdzie pierwszy, a drugi nie robi nic.

### 9.2 Kiedy wstawiasz

Najpierw sprawdzasz wykluczenia. Gdy bieżąca strona jest wykluczona, nie wstawiasz nic.

| Warunek wykluczenia | Jak sprawdzić |
|---|---|
| Strona CMS | kontroler `cms` i `id_cms` z żądania jest na liście `cms` |
| Kategoria | kontroler `category` i `id_category` z żądania jest na liście `categories` |
| Produkt | kontroler `product` i `id_product` z żądania jest na liście `products` |
| Adres zawiera | `strpos($_SERVER['REQUEST_URI'], $fragment) !== false` dla któregoś wiersza |

Nazwę kontrolera bierzesz z `$this->context->controller->php_self`.

Potem:

- Czat: gdy jest token, `ASAPCHAT_CHAT_ON` jest `1` i `chat.enabled` jest `true`.
- Wyszukiwarka: gdy jest token, `ASAPCHAT_SEARCH_ON` jest `1` i `search.enabled` jest `true`.

### 9.3 Jak budujesz znacznik

Szablon `views/templates/hook/scripts.tpl`:

- `src` z `chat.src` albo `search.src`. Akceptujesz tylko adres zaczynający się od `https://asapchat.io/` (kontrakt, punkt 4). Bez wyjątków, także przy nadpisanym `ASAPCHAT_BASE`. Inny adres: nie wstawiasz znacznika.
- Każda para z `attributes` staje się atrybutem HTML. Nie masz listy dozwolonych nazw, bo nazwy mogą się zmieniać.
- Wartość `true` renderuje sam atrybut (np. `defer`). Wartości `false` i `null` pomijasz.
- Każda wartość przechodzi przez `escape:'htmlall':'UTF-8'`.
- Nazwę atrybutu wstawiasz tylko wtedy, gdy pasuje do `^[a-zA-Z_][a-zA-Z0-9_\-]*$`. To ochrona składni HTML, nie lista nazw.
- Wyjątek: gdy `ASAPCHAT_CART_ON` jest `0`, pomijasz atrybut `allow_add-to-cart` w znaczniku wyszukiwarki.

Nie używasz `registerJavascript` dla tych skryptów. Widget czyta własne atrybuty ze znacznika, a łączenie plików JS (CCC) by je zgubiło.

### 9.4 Opóźnienie czatu

- `ASAPCHAT_DELAY` = `0`: zwykły znacznik `<script>` czatu w HTML.
- `ASAPCHAT_DELAY` większe od `0`: zamiast znacznika wstawiasz krótki skrypt inline. Skrypt po `setTimeout(..., delay * 1000)` tworzy element `script`, ustawia mu `src` i te same atrybuty przez `setAttribute`, a potem dokłada go do `document.body`. Mapę atrybutów przekazujesz jako JSON zakodowany `json_encode` z flagami `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`.

Opóźnienie dotyczy tylko czatu. Wyszukiwarka ładuje się zawsze od razu.

## 10. Odświeżanie config i pasek aktualizacji

### 10.1 Kiedy moduł pobiera config

Są dokładnie cztery momenty:

1. **Otwarcie ekranu konfiguracji modułu.** Zawsze, przy każdym wejściu, gdy jest token.
2. **Kliknięcie „Odśwież”** na ekranie.
3. **Odświeżanie w tle.** Hook `displayBackOfficeHeader`, czyli wejście dowolnego pracownika na dowolną stronę BO. Warunki: jest token, od `ASAPCHAT_CONFIG_AT` minęło więcej sekund niż `refresh_after` z config (domyślnie 86400), minął czas `ASAPCHAT_RETRY_AT`, żądanie nie jest AJAX-em. W multistore w jednym żądaniu BO odświeżasz najwyżej jeden sklep, ten z najstarszym `ASAPCHAT_CONFIG_AT`.
4. **Odpowiedź na zapis wyglądu.** `PATCH /settings` zwraca pełny config, zapisujesz go.

Timeout każdego żądania: 5 sekund. Po błędzie sieci, 429 albo 5xx w tle ustawiasz `ASAPCHAT_RETRY_AT` na „teraz + 15 minut”, żeby nie spowalniać BO przy każdym kliknięciu.

Moduł nie ma crona. Nigdy nie pobiera config w żądaniu klienta sklepu.

### 10.2 Kiedy moduł sprawdza nową wersję

Moduł nie ma osobnego żądania o wersję. Numer najnowszej wersji przychodzi w config, w polu `plugin.latest_version`. Sprawdzenie dzieje się więc w tych samych momentach co pobranie config (10.1), czyli najczęściej:

- przy otwarciu ekranu konfiguracji modułu,
- raz na dobę przy odświeżaniu w tle.

Samo porównanie robisz przy każdym renderowaniu ekranu konfiguracji, na zapisanym config:

```php
$latest = isset($config['plugin']['latest_version']) ? $config['plugin']['latest_version'] : null;
$showBar = is_string($latest) && $latest !== '' && version_compare($latest, $this->version, '>');
```

`$this->version` to wersja z konstruktora modułu.

### 10.3 Co pokazuje pasek

Pasek jest na ekranie konfiguracji w stanie „Połączony”, jako ostatnia sekcja przed przyciskiem „Zapisz zmiany”.

| Element | Treść |
|---|---|
| Tytuł | „Dostępna wersja {plugin.latest_version}” |
| Opis | „Masz wersję {wersja modułu}. Pobierz plik i wgraj go w Moduły, Menedżer modułów, „Załaduj moduł”. Połączenie i ustawienia zostają.” |
| Przycisk (biały) | „Pobierz asapchat.zip”, prowadzi na `plugin.download_url`, nowa karta |
| Link | „Co nowego”, prowadzi na `plugin.changelog_url`, nowa karta |

Zasady:

- `plugin.latest_version` jest `null`, pusty, równy albo niższy od wersji modułu: paska nie ma.
- Przycisk i link pokazujesz tylko wtedy, gdy ich adres zaczyna się od `https://`. Gdy `download_url` jest pusty, pokazujesz sam tytuł i opis.
- W stanach „Niepołączony” i „Błąd” paska nie ma, bo moduł nie ma config.

### 10.4 Jak wygląda sama aktualizacja

Moduł nie pobiera i nie instaluje nowej wersji sam. Sprzedawca robi to ręcznie:

1. Klika „Pobierz asapchat.zip”.
2. Wchodzi w Moduły > Menedżer modułów > „Załaduj moduł” i wskazuje plik.
3. PrestaShop podmienia pliki i uruchamia skrypty z katalogu `upgrade/`, jeśli są.

Po wgraniu `$this->version` jest już nowa, więc pasek znika przy następnym otwarciu ekranu. Token, config i ustawienia lokalne zostają, bo żyją w `Configuration`.

Każda nowa wersja, która zmienia klucze `Configuration` albo hooki, ma plik `upgrade/upgrade-X.Y.Z.php`.

## 11. Zachowanie przy błędach API

Dotyczy każdego wywołania z tokenem (`GET /config`, `PATCH /settings`).

| Odpowiedź | Co robi moduł |
|---|---|
| `200` | zapisuje nowy config i `ASAPCHAT_CONFIG_AT`, kasuje `ASAPCHAT_RETRY_AT` |
| `401`, `409`, `410` | kasuje `ASAPCHAT_TOKEN`, `ASAPCHAT_CONFIG`, `ASAPCHAT_CONFIG_AT`. Zapisuje kod w `ASAPCHAT_ERROR`. Przestaje wstawiać skrypty. Ekran przechodzi w stan „Błąd, połącz ponownie”. Ustawienia lokalne zostają |
| `422` | tylko przy PATCH: błędy pól (punkt 8) |
| `429`, `5xx`, timeout, brak sieci | nic nie kasuje. Skrypty działają dalej na zapisanej kopii. Na ekranie notka o nieudanym odświeżeniu |

Odpowiedź `200`, której nie da się zdekodować jako JSON albo która nie ma pola `chat`, traktujesz jak błąd sieci. Odpowiedź z `v` innym niż `1`: nie nadpisujesz zapisanej kopii i pokazujesz notkę z punktu 5.6.

Treści błędów (`{"error":"unauthorized"}` i pozostałe) opisuje kontrakt, punkt 4. Moduł decyduje po kodzie HTTP, nie po treści. `400` (brak `X-Site-Url`) to błąd w kodzie modułu: traktuj jak błąd sieci i popraw kod.

## 12. Rozłączenie i odinstalowanie

**„Rozłącz”** (POST z tokenem BO, po potwierdzeniu):

1. `DELETE /api/plugin/v1/connection` z tokenem.
2. Niezależnie od wyniku kasujesz `ASAPCHAT_TOKEN`, `ASAPCHAT_CONFIG`, `ASAPCHAT_CONFIG_AT`, `ASAPCHAT_RETRY_AT`, `ASAPCHAT_ERROR`, `ASAPCHAT_STATE`, `ASAPCHAT_VERIFIER`, `ASAPCHAT_STATE_AT`.
3. Ustawienia lokalne zostają.
4. Ekran wraca do stanu „Niepołączony” z komunikatem „Rozłączono.”

**`uninstall()`:**

1. Dla każdego sklepu, który ma token: `DELETE /api/plugin/v1/connection`. Błąd sieci nie blokuje odinstalowania.
2. Kasujesz wszystkie klucze `ASAPCHAT_*` we wszystkich sklepach i grupach.
3. Usuwasz ukrytą zakładkę i odpinasz hooki.

Po odinstalowaniu w tabeli `configuration` nie zostaje ani jeden wiersz `ASAPCHAT_%`.

## 13. Wersje PrestaShop, PHP i multistore

- PrestaShop 1.7.8, 8.x i 9.x. Jeden kod dla wszystkich. W konstruktorze: `ps_versions_compliancy` od `1.7.8.0` do `9.99.99`.
- PHP 7.2 do 8.4. Krańce zakresu (7.2 i 8.4) sprawdza CI samą składnią (`php -l`), pełne testy idą na 7.4, 8.1 i 8.3. Kod bez składni z PHP 8 (bez `match`, bez nazwanych argumentów, bez `?->`, bez promocji właściwości w konstruktorze).
- Motywy: Classic na każdej wersji, Hummingbird tylko na PrestaShop 9.

Multistore:

- Każdy sklep ma własne połączenie, własny token, własny config i własne ustawienia lokalne.
- Dwa sklepy PrestaShop na jednym hoście (różne podkatalogi) łączysz z dwoma różnymi sklepami AsapChat: ich tokeny są niezależne. Ponowne połączenie tego samego sklepu AsapChat z tego samego hosta unieważnia poprzedni token, więc dwa sklepy PrestaShop na jednym hoście nie mogą dzielić jednego sklepu AsapChat.
- Plan Free to jeden sklep na konto. Do testu multistore potrzebujesz drugiego konta AsapChat na inny adres e-mail.
- Każdy odczyt i zapis `Configuration` podaje wprost `id_shop_group` i `id_shop` bieżącego sklepu.
- Ekran konfiguracji działa tylko w kontekście jednego sklepu (punkt 5.5).
- Strona sklepu czyta ustawienia sklepu, który obsługuje bieżące żądanie.
- Sklepy z osobnymi domenami łączysz kluczem instalacyjnym, gdy przycisk nie może zadziałać (punkt 6.3).
- Odświeżanie w tle obsługuje jeden sklep na żądanie BO (punkt 10.1).

## 14. Tłumaczenia

Teksty źródłowe w kodzie po angielsku (`$this->l('...')` i `{l s='...' mod='asapchat'}`). Polski w `translations/pl.php`. Komplet tekstów po angielsku i po polsku jest w punkcie 20: forma „Ty”, krótko, bez znaku pauzy. Mockup pokazuje układ, nie jest źródłem tekstów.

## 15. Lista bezpieczeństwa

- [ ] Brak żądań do AsapChat przed kliknięciem „Połącz” albo „Połącz kluczem”.
- [ ] `state` jednorazowy, ważny 2 godziny, porównanie `hash_equals`, kasowany po pierwszym użyciu.
- [ ] `code_verifier` nigdy nie trafia do przeglądarki ani do adresu.
- [ ] Token `ack_...` nie pojawia się w HTML, JS, logach ani komunikatach. Pole klucza jest zawsze puste.
- [ ] Kontroler powrotu dostępny tylko dla zalogowanego pracownika z ważnym tokenem admina.
- [ ] Każdy POST w `getContent()` sprawdza `Tools::isSubmit` i token BO.
- [ ] Wartości atrybutów skryptów escapowane, nazwy atrybutów sprawdzane wyrażeniem regularnym.
- [ ] `src` skryptu wstawiany tylko, gdy zaczyna się od `https://asapchat.io/`. `download_url`, `changelog_url` i linki z config tylko, gdy zaczynają się od `https://`.
- [ ] Mapa atrybutów w skrypcie opóźnienia kodowana `json_encode` z flagami `JSON_HEX_*`.
- [ ] Dane z formularza rzutowane i walidowane przed zapisem (ID jako `int`, opóźnienie 0 do 60, limity długości).
- [ ] Brak endpointów frontowych modułu. Brak trybów diagnostycznych.
- [ ] Żadnego żądania HTTP do AsapChat w żądaniu klienta sklepu.
- [ ] CSS ekranu zakresowany pod `.asapchat-app`, font z paczki modułu.

## 16. Macierz testów

| Oś | Wartości |
|---|---|
| PrestaShop | 1.7.8.x, 8.1, 9.0 (obrazy Dockera `prestashop/prestashop`, tagi `1.7.8`, `8.1`, `9.0`, domyślne PHP obrazu) |
| PHP | pełne testy: 7.4 (dla 1.7.8), 8.1, 8.3. Sama składnia w CI: 7.2 i 8.4 |
| Motyw | Classic na każdej wersji, Hummingbird tylko na 9.0 |
| Tryb debug | `_PS_MODE_DEV_ = true`, zero notice i warningów |
| Multistore | wyłączony; włączony z 2 sklepami na jednym hoście, połączonymi z dwoma kontami AsapChat |
| Cache | Smarty cache włączony, CCC: łączenie i minifikacja JS włączone |
| Szerokość ekranu konfiguracji | 390 px i 1280 px |

Ścieżki do przeklikania na każdej wersji PrestaShop:

| Nr | Ścieżka | Oczekiwany wynik |
|---|---|---|
| 1 | Połączenie przyciskiem, konto istnieje | stan „Połączony”, skrypty w źródle strony |
| 2 | Połączenie przyciskiem, nowe konto przez kreator | po kreatorze powrót do modułu, stan „Połączony” |
| 3 | „Anuluj” w panelu | komunikat „Anulowano łączenie.”, stan „Niepołączony” |
| 4 | Powrót ze zmienionym `state` | „Spróbuj połączyć ponownie.”, brak żądania `/token` |
| 5 | Powrót po ponad 2 godzinach (cofnij `ASAPCHAT_STATE_AT` w bazie) | jak w 4 |
| 6 | Klucz instalacyjny: dobry, zły format, odwołany, z innej domeny | połączenie albo właściwy komunikat. Wygaśnięcie po 24 godzinach sprawdza tylko test jednostkowy (odpowiedź `401`) |
| 7 | Przełączniki: czat, wyszukiwarka, koszyk | skrypt znika albo wraca; atrybut `allow_add-to-cart` znika. Część o wyszukiwarce wymaga tunelu (niżej) |
| 8 | `search.enabled = false` | przełącznik nieaktywny, link do `connect_url`, brak skryptu wyszukiwarki |
| 9 | Wygląd: zmiana koloru i nazwy | widoczna w panelu AsapChat i w atrybutach skryptu po zapisie |
| 10 | Wygląd: nazwa ponad 40 znaków wysłana z pominięciem `maxlength` | błąd przy polu z odpowiedzi 422 |
| 11 | Opóźnienie 5 sekund | czat pojawia się po 5 sekundach, brak znacznika czatu w źródle HTML |
| 12 | Wykluczenia: strona CMS, kategoria, produkt, fragment adresu | na wykluczonej stronie zero skryptów AsapChat, na pozostałych są |
| 13 | Źródła: oba statusy, przycisk, instrukcja | pill zgodny z config, instrukcja zgodna z menu tej wersji PrestaShop. Stan „Podłączone” wymaga tunelu (niżej) |
| 14 | Pasek aktualizacji: `latest_version` wyższa, równa, `null` | pasek tylko przy wyższej |
| 15 | Aktualizacja: wgranie nowszego zipa | pasek znika, połączenie i ustawienia zostają |
| 16 | Odwołanie połączenia w panelu (401) | stan „Błąd”, zero skryptów, ustawienia lokalne zachowane |
| 17 | Brak sieci do AsapChat | skrypty dalej się wstawiają, notka na ekranie, BO nie zwalnia przy każdym kliknięciu |
| 18 | „Rozłącz” | zero skryptów, stan „Niepołączony” |
| 19 | Odinstalowanie | zero wierszy `ASAPCHAT_%` w tabeli `configuration`, brak zakładki |
| 20 | Multistore: sklep A połączony, sklep B nie | skrypty tylko na A; kontekst „Wszystkie sklepy” pokazuje prośbę o wybór sklepu |
| 21 | Multistore: A i B połączone z dwoma kontami AsapChat | każdy sklep ma własne atrybuty skryptu, rozłączenie A nie rusza B |

Dla każdej testowanej pary wersji i motywu: skrypt czatu jest w źródle strony głównej, strony produktu i koszyka, dokładnie raz. To samo dla skryptu wyszukiwarki w przebiegu z tunelem.

**Wyszukiwarka i źródła.** Na `localhost` `search.enabled` zawsze jest `false`, a źródła „Niepodłączone”, bo AsapChat nie sięgnie do lokalnego sklepu. Dlatego:

- testy jednostkowe używają przykładu z kontraktu, punkt 7a (`search.enabled = true`, źródła podłączone),
- ścieżki 7 i 13 w pełnym zakresie przechodzisz raz, na jednej wersji PrestaShop, ze sklepem wystawionym przez tunel `https` i kluczem Webservice podłączonym w panelu AsapChat (`sources.connect_url`). Na pozostałych wersjach wystarcza stan `false`.

API działa na produkcji. Testy automatyczne używają stałych odpowiedzi z kontraktu (punkt 4 i 7a), podstawionych w kliencie HTTP.

## 17. Definicja ukończenia

1. Wszystkie ścieżki macierzy przechodzą na trzech wersjach PrestaShop. Wyniki są w issue zadania 14.
2. Ekran w trzech stanach zgadza się z mockupem na 390 px i 1280 px. Zrzuty są w PR.
3. Lista bezpieczeństwa odhaczona w PR.
4. Bramka walidacji z `AGENTS.md` (w tym `vendor/bin/phpunit`) przechodzi. Zero notice w trybie debug.
5. Opcjonalnie: validator.prestashop.com nie zgłasza błędów. Wymaga konta PrestaShop, więc to nie jest warunek.
6. Zip ma poprawną strukturę: `asapchat.zip` > `asapchat/` > `asapchat.php`. Bez `__MACOSX` i `.DS_Store`.
7. Wydanie `v1.0.0` jest w GitHub Releases z plikiem `asapchat.zip` i notatką według szablonu z `docs/marketplace-listing.md`.
8. Wersja, adres zipa i adres listy zmian są przekazane Kamilowi (procedura: `docs/accounts.md`).

## 18. Ryzyko

Aktualizacja jest ręczna, więc część sklepów zostanie na starej wersji. Dlatego każda zmiana w module musi działać ze starszym zapisanym config i nie może wymagać nowej wersji do samego działania czatu.

## 19. Issues

Kolejność wykonania. Numer pozycji to prefiks w tytule issue (`01` do `14`). W nawiasie numer GitHub tego issue. Etykiety pochodzą z taksonomii w `SDLC.md` (kategoria, priorytet, ryzyko, QA).

### 01. Szkielet modułu `asapchat` i CI (#2)

Etykiety: `feature`, `priority-high`, `risk-low`, `skip-qa`

- Katalog `asapchat/` ma strukturę z `AGENTS.md`. Moduł instaluje się i odinstalowuje na PrestaShop 1.7.8, 8 i 9 bez notice.
- Stała `ASAPCHAT_BASE` jest zdefiniowana i da się ją nadpisać.
- W repo są `composer.json` (tylko `require-dev`: `prestashop/php-dev-tools`, `friendsofphp/php-cs-fixer`, `prestashop/header-stamp`, `phpstan/phpstan`, `phpunit/phpunit:^9.6`), `.php-cs-fixer.dist.php`, `phpstan.neon`, `phpunit.xml.dist`, `docker-compose.yml` (przepis: `ONBOARDING-DEV.md`, „Sklep lokalny”) i katalog `tests/` z pierwszym testem. Szczegóły: `AGENTS.md`, sekcje Walidacja i Testy.
- phpstan ma kod PrestaShop w `.ps-src` (poza gitem): w CI płytki klon repo PrestaShop z gałęzi 8.1, lokalnie kopia `/var/www/html` z kontenera 8.1. Lista pakietów, przepis compose i sposób pobrania kodu nie były jeszcze uruchamiane: to zadanie je sprawdza i poprawia dokumenty, jeśli coś się nie zgadza.
- CI uruchamia cztery komendy walidacji z `AGENTS.md` (składnia na PHP 7.2 i 8.4, testy na 7.4, 8.1, 8.3) i buduje `asapchat.zip` o poprawnej strukturze jako artefakt PR.
- Pliki starego prototypu są usunięte z `main`: `prestashopasapchat.php`, `config.xml`, `config_pl.xml`, `index.php`, `controllers/` (w tym `controllers/front/asapchatApi.php`), `sql/`, `translations/`, `upgrade/` (w tym `upgrade/upgrade-1.1.0.php`), `views/` (w tym `views/img/logo.jpeg`), wszystkie `.DS_Store`. Na `main` zostaje katalog `asapchat/`, dokumenty projektu i pliki narzędzi.
- `asapchat/logo.png` (57x57): białe logo z `asapchat/views/img/asapchat-logo-white.svg` wyśrodkowane na kwadracie `#161616`. Fonty Open Sans są w `asapchat/views/fonts/`.

### 02. Ekran konfiguracji w stylu AsapChat: trzy stany (#3)

Etykiety: `feature`, `priority-high`, `risk-low`, `needs-qa`

- Ekran renderuje stany „Niepołączony”, „Połączony” i „Błąd” na danych przykładowych: układ z mockupu, teksty ze specyfikacji (w kodzie po angielsku, punkt 20).
- Stany przełącza skrypt deweloperski `tests/fixtures/seed-state.php <stan>`, uruchamiany z wiersza poleceń w kontenerze sklepu. Skrypt ładuje `config/config.inc.php` i ustawia klucze `ASAPCHAT_*` przez `Configuration::updateValue` na dane przykładowe z kontraktu (punkty 4 i 7a). Leży poza katalogiem `asapchat/`, więc nie trafia do paczki. Komendy są opisane w PR. Żadnych parametrów w adresie ani trybu diagnostycznego.
- Zrzuty w PR (390 px i 1280 px) obejmują: „Niepołączony”, „Niepołączony” bez przycisku (6.3), „Połączony” z `search.enabled=false`, „Połączony” z wyszukiwarką włączoną i jeszcze niewidzianą (przykład 7a), „Połączony” z notką o nieudanym odświeżeniu, „Połączony” z notką o `v` innym niż 1, „Połączony” z błędami pól, „Błąd” dla `401`, `409` i `410`, kontekst „Wszystkie sklepy” oraz pola sukcesu i informacji. Notki i błędy pól skrypt ustawia tym samym sposobem (klucz konfiguracji albo wartości formularza wpisane ręcznie).
- Czarny pasek z logo, tokeny kolorów, przełączniki i pill są zgodne z punktem 5.1.
- Każda reguła CSS zaczyna się od `.asapchat-app`, font Open Sans jest w paczce modułu.
- Ekran działa na 390 px i 1280 px. Zrzuty obu szerokości są w PR.
- Kontekst „Wszystkie sklepy” pokazuje prośbę o wybór sklepu.

### 03. Połączenie przyciskiem (#4)

Etykiety: `feature`, `security`, `priority-high`, `risk-high`, `needs-qa`

- Kliknięcie „Połącz” zapisuje `state` i `code_verifier` i przekierowuje na `/connect` z parametrami z punktu 6.1.
- Kontroler powrotu wymienia kod na token i zapisuje token oraz config.
- `state` żyje 2 godziny, jest jednorazowy i porównywany przez `hash_equals`. Są testy jednostkowe (`tests/`): zły `state`, wygasły `state`, `error=access_denied`, `invalid_grant`.
- Ścieżka nowego konta (kreator i automatyczny powrót) przeklikana i opisana w PR.
- Gdy warunki z punktu 6.3 (host, port, `https`) nie są spełnione, ekran pokazuje tylko ścieżkę klucza.
- QA robi drugi deweloper, nie autor.

### 04. Połączenie kluczem instalacyjnym (#5)

Etykiety: `feature`, `security`, `priority-high`, `risk-high`, `needs-qa`

- Klucz o złym formacie jest odrzucany bez żądania do AsapChat.
- Odpowiedzi 200, 401, 409, 410 i błąd sieci dają zachowanie z tabeli w punkcie 7.
- Token nie pojawia się w HTML ani w logach. Pole klucza jest zawsze puste.
- Są testy jednostkowe: zły format bez żądania, reakcja na 200, 401, 409, 410 i błąd sieci.
- QA robi drugi deweloper, nie autor.

### 05. Config: zapis, odświeżanie, błędy, status, rozłączenie (#8)

Etykiety: `feature`, `priority-high`, `risk-medium`, `needs-qa`

- Config odświeża się przy otwarciu ekranu, po kliknięciu „Odśwież” i w tle według punktu 10.1.
- Błąd sieci, 429 i 5xx nie kasują niczego i ustawiają `ASAPCHAT_RETRY_AT`. 401, 409 i 410 dają stan „Błąd” (punkt 11).
- Sekcje „Nagłówek” i „Status” pokazują prawdziwe dane z config.
- „Rozłącz” woła `DELETE /connection`, kasuje dane połączenia i zostawia ustawienia lokalne.

### 06. Czat na stronie sklepu (#6)

Etykiety: `feature`, `priority-high`, `risk-medium`, `needs-qa`

- Znacznik czatu ma wszystkie atrybuty z `chat.attributes` i wychodzi dokładnie raz na motywie Classic (każda wersja) i Hummingbird (PrestaShop 9).
- Przełącznik „Czat” wyłączony, brak tokenu albo `chat.enabled = false`: zero znaczników.
- Działa przy włączonym CCC i cache Smarty.
- W żądaniu strony sklepu nie ma żadnego żądania HTTP do AsapChat.

### 07. Wyszukiwarka i dodawanie do koszyka (#7)

Etykiety: `feature`, `priority-high`, `risk-medium`, `needs-qa`

- Znacznik wyszukiwarki ma wszystkie atrybuty z `search.attributes` i wychodzi dokładnie raz.
- `search.enabled = false`: przełącznik nieaktywny z linkiem do `sources.connect_url`, brak znacznika.
- Przełącznik „Dodawanie do koszyka” wyłączony: znacznik nie ma atrybutu `allow_add-to-cart`.
- Testy jednostkowe budowy znacznika używają przykładu z kontraktu, punkt 7a. QA ręczne z `search.enabled = true`: sklep przez tunel `https` z podłączonym kluczem Webservice (punkt 16).

### 08. Gdzie pokazywać: opóźnienie i wykluczenia (#14)

Etykiety: `feature`, `priority-medium`, `risk-medium`, `needs-qa`

- Sekcja zapisuje opóźnienie oraz wykluczenia stron CMS, kategorii, produktów i fragmentów adresu.
- Na wykluczonej stronie nie ma żadnego skryptu AsapChat. Na pozostałych są.
- Opóźnienie większe od 0 dokłada czat przez `setTimeout`, a w źródle HTML nie ma znacznika czatu.
- Złe dane (opóźnienie 61, litery w ID) dają komunikat przy polu i nie są zapisywane.

### 09. Wygląd: zapis przez PATCH /settings (#15)

Etykiety: `feature`, `priority-medium`, `risk-medium`, `needs-qa`

- Pola są wypełnione wartościami z `settings`.
- Zapis wysyła tylko zmienione pola i od razu zapisuje config z odpowiedzi.
- Nazwa asystenta jest wymagana (1 do 40 znaków): pusta daje błąd pola przed wysłaniem.
- Odpowiedź 422 (kształt: kontrakt, punkt 4) pokazuje przy właściwych polach teksty z punktu 5.6. Błąd sieci nie gubi wpisanych wartości.
- Zmiana jest widoczna w panelu AsapChat i w atrybutach skryptu na stronie sklepu.

### 10. Źródło zamówień i produktów z instrukcją Webservice (#16)

Etykiety: `feature`, `priority-medium`, `risk-low`, `needs-qa`

- Dwa statusy zgodne z `sources.orders_connected` i `sources.products_connected`. Stan „Podłączone” sprawdzasz testem na przykładzie z kontraktu, punkt 7a, i raz ręcznie przez tunel (punkt 16).
- Przycisk „Podłącz w AsapChat” otwiera `sources.connect_url` w nowej karcie.
- Instrukcja ma kroki z punktu 5.3 i zgadza się z menu PrestaShop 1.7.8, 8 i 9 (sprawdzone ręcznie, różnice nazw opisane w PR).

### 11. Pasek aktualizacji (#17)

Etykiety: `feature`, `priority-medium`, `risk-low`, `needs-qa`

- Pasek pokazuje się tylko, gdy `plugin.latest_version` jest wyższa od wersji modułu (`version_compare`).
- Pasek ma numer wersji, przycisk do `download_url` i link do `changelog_url`. Adresy inne niż `https://` nie są wstawiane.
- Jest test jednostkowy dla wersji wyższej, równej, niższej i `null`. Ręcznie pasek zobaczysz dopiero po pierwszym wydaniu (do tego czasu `plugin.latest_version` jest `null`), więc w QA wystarczy zrzut z config podmienionym w bazie.
- Po wgraniu nowszego zipa pasek znika, a połączenie i ustawienia zostają.

### 12. Odinstalowanie i multistore (#9)

Etykiety: `feature`, `priority-medium`, `risk-medium`, `needs-qa`

- `uninstall()` woła `DELETE /connection` dla każdego połączonego sklepu i nie zostawia żadnego wiersza `ASAPCHAT_%`.
- Dwa sklepy w multistore na jednym hoście, połączone z dwoma kontami AsapChat (Free to jeden sklep na konto), mają osobne połączenia i osobne ustawienia. Skrypty jednego nie trafiają na drugi.
- Odświeżanie w tle obsługuje jeden sklep na żądanie BO.

### 13. Tłumaczenia i teksty strony pobierania (#10)

Etykiety: `documentation`, `priority-medium`, `risk-low`, `skip-qa`

- Wszystkie teksty modułu przechodzą przez mechanizm tłumaczeń. `translations/pl.php` jest kompletny i zgodny z tabelą w punkcie 20 (źródłem tekstów jest specyfikacja, nie mockup).
- W tekstach nie ma znaku pauzy ani emoji.
- Teksty w `docs/marketplace-listing.md` zgadzają się z tym, co moduł naprawdę robi i wysyła.

### 14. Macierz zgodności i wydanie zip w GitHub Releases (#12)

Etykiety: `feature`, `priority-medium`, `risk-low`, `needs-qa`

- Wszystkie ścieżki z punktu 16 są przeklikane na PrestaShop 1.7.8, 8 i 9. Tabela wyników jest w issue.
- Workflow wydania jest w repo: tag `vX.Y.Z` buduje `asapchat.zip` i tworzy wydanie w GitHub Releases. Tag `v1.0.0` tworzy wydanie z notatką według szablonu.
- Zip pobrany z wydania instaluje się na czystym sklepie.
- Wersja, `download_url` i `changelog_url` są przekazane Kamilowi według `docs/accounts.md`. To issue jest issue wydania 1.0.0.

## 20. Teksty UI: angielski (źródło) i polski (tłumaczenie)

W kodzie wpisujesz tekst angielski (`$this->l('...')`, `{l s='...' mod='asapchat'}`). Polski trafia do `translations/pl.php`. `%s` i `%d` to miejsca na wartości (`sprintf`). Nazwy „AsapChat”, „AsapSearch”, „PrestaShop” i „Webservice” nie są tłumaczone. Teksty wspólne z wtyczką WordPress mają to samo brzmienie angielskie co tam. Gdy dodajesz tekst, którego tu nie ma, dopisz go do tej tabeli w tym samym PR.

### Stan „Niepołączony” i multistore

| EN (w kodzie) | PL |
|---|---|
| Connect your store to AsapChat | Połącz sklep z AsapChat |
| The module will add an AI chat and the AsapSearch product search to your store. You do not need to paste anything into the theme. | Moduł doda do Twojego sklepu czat AI i wyszukiwarkę produktów AsapSearch. Nie musisz niczego wklejać do motywu. |
| Connect to AsapChat | Połącz z AsapChat |
| No account yet? You will create one in the next step, for free. | Nie masz konta? Założysz je w następnym kroku, za darmo. |
| After you click, you will go to panel.asapchat.io. We will pass your store address there: %s. | Po kliknięciu przejdziesz do panel.asapchat.io. Przekażemy tam adres Twojego sklepu: %s. |
| I have an install key | Mam klucz instalacyjny |
| Install key | Klucz instalacyjny |
| Connect with key | Połącz kluczem |
| Connecting with the button will not work in this store, because the store panel runs under a different address than the store. Use an install key. | W tym sklepie połączenie przyciskiem nie zadziała, bo panel sklepu działa pod innym adresem niż sklep. Użyj klucza instalacyjnego. |
| Select one store from the list at the top to connect it to AsapChat. | Wybierz jeden sklep z listy u góry, żeby go połączyć z AsapChat. |

### Stan „Połączony”: nagłówek, status, widgety

| EN (w kodzie) | PL |
|---|---|
| Open AsapChat panel | Otwórz panel AsapChat |
| Connected (pill w nagłówku) | Połączono |
| %1$s: %2$d AI conversations per month | %1$s: %2$d rozmów AI miesięcznie |
| Disconnect | Rozłącz |
| Disconnect the store? The chat and the search will disappear from your site. | Rozłączyć sklep? Czat i wyszukiwarka znikną ze strony. |
| Status | Status |
| Chat | Czat |
| Chat is working | Czat działa |
| Last seen on the site: %s | Ostatnio widziany na stronie: %s |
| Not seen on the site yet | Jeszcze niewidziany na stronie |
| Open your store in a new tab to check. | Otwórz sklep w nowej karcie, żeby sprawdzić. |
| Search | Wyszukiwarka |
| Search is working | Wyszukiwarka działa |
| Search last seen on the site: %s | Ostatnio widziana na stronie: %s |
| Search not seen on the site yet | Jeszcze niewidziana na stronie |
| Refresh | Odśwież |
| Open the store in a new tab | Otwórz sklep w nowej karcie |
| Could not fetch the settings from AsapChat. We are using the last saved ones. | Nie udało się pobrać ustawień z AsapChat. Używamy ostatnich zapisanych. |
| This version of the module is too old for AsapChat. Update the module. | Ta wersja modułu jest za stara dla AsapChat. Zaktualizuj moduł. |
| Widgets | Widgety |
| AsapSearch product search | Wyszukiwarka AsapSearch |
| Add to cart from search | Dodawanie do koszyka z wyszukiwarki |
| The search is not ready yet, because AsapChat does not have your products. | Wyszukiwarka nie jest jeszcze gotowa, bo AsapChat nie ma Twoich produktów. |
| Connect products in AsapChat | Podłącz produkty w AsapChat |

Mechanizm tłumaczeń modułów PrestaShop nie ma kontekstu, a po polsku tekst dla wyszukiwarki ma inny rodzaj niż dla czatu. Dlatego wersje dla wyszukiwarki mają własny tekst źródłowy („Search not seen...”, „Search last seen...”).

### Stan „Połączony”: wygląd, gdzie pokazywać, źródła, aktualizacja, stopka

| EN (w kodzie) | PL |
|---|---|
| Look | Wygląd |
| These are the same settings as in the AsapChat panel. A change here is visible there and the other way round. | Te same ustawienia co w panelu AsapChat. Zmiana tutaj jest widoczna tam i odwrotnie. |
| Assistant name | Nazwa asystenta |
| Main color | Kolor główny |
| Chat position | Pozycja czatu |
| Bottom right corner | Prawy dolny róg |
| Bottom left corner | Lewy dolny róg |
| Greeting 1 | Powitanie 1 |
| Greeting 2 | Powitanie 2 |
| Where to show | Gdzie pokazywać |
| Chat delay | Opóźnienie pojawienia się czatu |
| seconds | sekund |
| Excluded CMS pages | Wykluczone strony CMS |
| Excluded categories | Wykluczone kategorie |
| Excluded products (ID) | Wykluczone produkty (ID) |
| Product IDs separated by commas. You will find the ID on the list: Catalog, then Products. | ID produktów po przecinku. ID znajdziesz na liście: Katalog, potem Produkty. |
| Address contains | Adres zawiera |
| Orders and products source | Źródło zamówień i produktów |
| Thanks to this the assistant knows your products and answers questions about order status. | Dzięki temu asystent zna Twoje produkty i odpowiada na pytania o status zamówienia. |
| Orders | Zamówienia |
| Products | Produkty |
| Connected | Podłączone |
| Not connected | Niepodłączone |
| Connect in AsapChat | Podłącz w AsapChat |
| How to generate an API key in PrestaShop | Jak wygenerować klucz API w PrestaShop |
| In the store menu go to Advanced Parameters, then Webservice. | W menu sklepu wejdź w Zaawansowane, potem API (Webservice). |
| Turn on "Enable PrestaShop's webservice" and save. | Włącz opcję „Włącz usługę sieciową PrestaShop” i zapisz. |
| Click "Add new webservice key", then "Generate". | Kliknij „Dodaj nowy klucz”, potem „Generuj”. |
| In the permissions table tick only the GET column for: %s. | W tabeli uprawnień zaznacz tylko kolumnę GET dla: %s. |
| Set the key status to "Yes" and save. | Ustaw status klucza na „Tak” i zapisz. |
| Copy the key, click "Connect in AsapChat" and paste it there together with the store address. | Skopiuj klucz, kliknij „Podłącz w AsapChat” i wklej go tam razem z adresem sklepu. |
| Version %s is available | Dostępna wersja %s |
| You have version %s. Download the file and upload it in Modules, Module Manager, "Upload a module". The connection and the settings stay. | Masz wersję %s. Pobierz plik i wgraj go w Moduły, Menedżer modułów, „Załaduj moduł”. Połączenie i ustawienia zostają. |
| Download asapchat.zip | Pobierz asapchat.zip |
| What's new | Co nowego |
| Settings fetched from AsapChat: %1$s. Module version: %2$s. | Ustawienia pobrane z AsapChat: %1$s. Wersja modułu: %2$s. |
| Save changes | Zapisz zmiany |

### Stan „Błąd” i komunikaty

| EN (w kodzie) | PL |
|---|---|
| Connect the store again | Połącz sklep ponownie |
| Disconnected (pill) | Rozłączono |
| The connection was revoked in the AsapChat panel or the install key expired. | Połączenie zostało odwołane w panelu AsapChat albo klucz instalacyjny wygasł. |
| This connection is assigned to another domain. Connect this store again. | To połączenie jest przypisane do innej domeny. Połącz ten sklep jeszcze raz. |
| This shop was deleted in AsapChat. | Ten sklep został usunięty w AsapChat. |
| The chat and the search are now turned off on the store site. | Czat i wyszukiwarka są teraz wyłączone na stronie sklepu. |
| Your settings in this module (switches, delay, exclusions) have been kept. They will come back after you connect. | Twoje ustawienia w tym module (przełączniki, opóźnienie, wykluczenia) zostały zachowane. Wrócą po połączeniu. |
| Connect again | Połącz ponownie |
| Store connected to AsapChat. | Połączono sklep z AsapChat. |
| Saved. | Zapisano. |
| Disconnected. | Rozłączono. |
| Connection cancelled. | Anulowano łączenie. |
| Try connecting again. | Spróbuj połączyć ponownie. |
| Could not connect. Try again or use an install key. | Nie udało się połączyć. Spróbuj ponownie albo użyj klucza instalacyjnego. |
| The key has a wrong format. Copy it again from the AsapChat panel. | Klucz ma zły format. Skopiuj go jeszcze raz z panelu AsapChat. |
| The key is invalid, revoked or expired. The key must be used within 24 hours of being generated. | Klucz jest nieprawidłowy, odwołany albo wygasł. Klucz trzeba użyć w ciągu 24 godzin od wygenerowania. |
| This key is assigned to another domain. | Ten klucz jest przypisany do innej domeny. |
| Could not connect to AsapChat. Try again in a moment. | Nie udało się połączyć z AsapChat. Spróbuj za chwilę. |
| Module settings saved. The look could not be saved in AsapChat, try again in a moment. | Ustawienia modułu zapisane. Wyglądu nie udało się zapisać w AsapChat, spróbuj za chwilę. |

### Błędy pól

| EN (w kodzie) | PL |
|---|---|
| Enter a name, 40 characters at most. | Podaj nazwę, najwyżej 40 znaków. |
| Enter the color as #rrggbb. | Podaj kolor w formacie #rrggbb. |
| Choose one of the two positions. | Wybierz jedną z dwóch pozycji. |
| A greeting can have 120 characters at most. | Powitanie może mieć najwyżej 120 znaków. |
| Enter a number from 0 to 60. | Podaj liczbę od 0 do 60. |
| Enter ID numbers only, separated by commas. | Wpisz same numery ID, po przecinku. |
| 50 lines at most, each up to 200 characters. | Najwyżej 50 wierszy, każdy do 200 znaków. |

Dopiski w nawiasach w kolumnie EN („pill”, „pill w nagłówku”) to objaśnienia, nie część tekstu. Nazwy pozycji menu PrestaShop w instrukcji Webservice sprawdź w zadaniu 10 na angielskim i polskim panelu wersji 8 i popraw tabelę, jeśli różnią się od wpisanych.
