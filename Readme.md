# AsapChat dla PrestaShop

Oficjalny, darmowy moduł PrestaShop. Łączy sklep z kontem AsapChat i dodaje na stronie sklepu czat AI (AsapChat) oraz wyszukiwarkę produktów (AsapSearch).

- Nazwa techniczna modułu: `asapchat` (folder `asapchat/`, plik główny `asapchat.php`).
- Wspierane wersje PrestaShop: 1.7.8, 8.x, 9.x.
- Wydawca: AsapCommerce.
- Pobieranie: plik `asapchat.zip` z [GitHub Releases](https://github.com/asapdevs-it/prestashopasapchat/releases). Ten sam link jest w panelu AsapChat (Sklep, potem Wtyczka).

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

Moduł nie jest w katalogu PrestaShop Addons. Każda wersja to wydanie w GitHub Releases z plikiem `asapchat.zip`. Moduł porównuje własną wersję z numerem najnowszej wersji z AsapChat i pokazuje pasek, gdy jest nowsza. Jak wydać wersję: `docs/accounts.md`.

## Gałęzie

- `main`: bieżąca praca.
- `legacy`: archiwum. Nie kopiuj z niej kodu.

## Zacznij tutaj (kolejność czytania)

1. `ONBOARDING-DEV.md`: jak pracujemy, jak brać zadania.
2. `docs/CONNECT-API.md`: kontrakt z backendem AsapChat. Punkt 7 mówi, jak z nim pracować.
3. `.ai/specs/2026-11-11-connector-v1.md`: pełna specyfikacja modułu i lista issues.
4. `docs/mockups/configure.html`: jak ma wyglądać ekran konfiguracji (otwórz w przeglądarce).
5. `AGENTS.md`: zasady kodu dla Ciebie i dla agenta AI.
6. `docs/accounts.md`: jak wydać nową wersję i komu przekazać numer wersji.
7. `docs/marketplace-listing.md`: teksty na stronę pobierania i szablon notatki wydania.
8. Issues w GitHub: zadania w kolejności numerów.

## Kolejność pracy

Issues robisz w kolejności z punktu 19 specyfikacji: szkielet, ekran, połączenie, config, skrypty na stronie sklepu, sekcje ustawień, pasek aktualizacji, multistore, tłumaczenia, wydanie.
