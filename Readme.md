# AsapChat dla PrestaShop

Oficjalny, darmowy moduł PrestaShop. Łączy sklep z kontem AsapChat i dodaje na stronie sklepu czat AI (AsapChat) oraz wyszukiwarkę produktów (AsapSearch).

- Nazwa techniczna modułu: `asapchat` (folder `asapchat/`, plik główny `asapchat.php`).
- Wspierane wersje PrestaShop: 1.7.8, 8.x, 9.x.
- Wydawca: AsapCommerce.
- Pobieranie: plik `asapchat.zip` z [GitHub Releases](https://github.com/asapdevs-it/prestashopasapchat/releases). Pierwsze wydanie pojawi się po zadaniu 14, do tego czasu lista wydań jest pusta. Ten sam link będzie w panelu AsapChat (edycja sklepu, link „Wtyczka”).

## Co robi moduł w wersji 1

1. **Łączy sklep z kontem.** Sprzedawca klika „Połącz z AsapChat”, loguje się albo zakłada darmowe konto w `panel.asapchat.io`, wybiera sklep i sam wraca do modułu. Zapasowo: pole na klucz instalacyjny skopiowany z panelu.
2. **Wstawia skrypty.** Moduł pobiera konfigurację z AsapChat i wstawia na stronie sklepu znacznik `<script>` czatu i znacznik `<script>` wyszukiwarki.
3. **Daje ekran ustawień w stylu AsapChat.** Sekcje:
   - nagłówek: logo, nazwa sklepu, plan, link do panelu, „Rozłącz”,
   - status: czy czat i wyszukiwarka były widziane na stronie,
   - widgety: przełączniki czatu, wyszukiwarki i dodawania do koszyka z wyszukiwarki,
   - wygląd: nazwa asystenta, kolor, pozycja, powitania (wspólne z panelem AsapChat),
   - gdzie pokazywać: opóźnienie czatu i wykluczenia stron CMS, kategorii, produktów,
   - źródło zamówień i produktów: statusy i instrukcja klucza Webservice,
   - aktualizacja: pasek „Dostępna wersja X”, gdy wyszła nowa wersja modułu.

## Czego moduł nie robi

- Nie synchronizuje zamówień ani produktów. AsapChat pobiera je sam przez Webservice PrestaShop, po podłączeniu klucza w panelu AsapChat.
- Nie wystawia żadnego własnego API.
- Nie pokazuje statystyk.
- Nie prosi o hasło do AsapChat i nigdzie go nie zapisuje.
- Nie aktualizuje się sam. Nową wersję wgrywasz jako zip w Menedżerze modułów.

## Dystrybucja

Moduł rozdajemy sami, poza katalogiem modułów PrestaShop. Każda wersja to wydanie w GitHub Releases z plikiem `asapchat.zip`. Moduł porównuje własną wersję z numerem najnowszej wersji z AsapChat i pokazuje pasek, gdy jest nowsza. Jak wydać wersję: `docs/accounts.md`.

## Gałęzie

- `main`: bieżąca praca. Do zamknięcia zadania 01 w katalogu głównym leżą jeszcze pliki starego prototypu. Zadanie 01 je usuwa (lista w zadaniu).
- `legacy`: archiwum starego prototypu. Nie kopiuj z niej kodu.
- `master`: niezwiązana pozostałość po innym module. Nie używaj jej.

## Zacznij tutaj (kolejność czytania)

1. `ONBOARDING-DEV.md`: jak pracujemy, jak brać zadania, środowisko lokalne.
2. `docs/CONNECT-API.md`: kontrakt z backendem AsapChat. Punkt 7 mówi, jak z nim pracować.
3. `.ai/specs/2026-11-11-connector-v1.md`: pełna specyfikacja modułu i lista zadań.
4. `docs/mockups/configure.html`: jak ma wyglądać ekran konfiguracji (otwórz w przeglądarce).
5. `docs/marketplace-listing.md` i `docs/accounts.md`: teksty strony pobierania, jak wydać wersję.
6. `AGENTS.md`, `SDLC.md`, `CODE_REVIEW.md`: zasady kodu i pracy.
7. Issues w GitHub: zadania w kolejności prefiksu `01` do `14` w tytule, nie numeru GitHub.

## Kolejność pracy

Zadania robisz w kolejności prefiksu w tytule (`01` do `14`), tej samej co w punkcie 19 specyfikacji. Numer GitHub (`#N`) nie wyznacza kolejności. Kolejno: szkielet, ekran, połączenie, config, skrypty na stronie sklepu, sekcje ustawień, pasek aktualizacji, multistore, tłumaczenia, wydanie.
