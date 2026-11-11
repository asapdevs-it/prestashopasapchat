# AsapChat dla PrestaShop

Oficjalny moduł PrestaShop, który podłącza sklep do konta AsapChat i dodaje na stronie sklepu dwa skrypty: czat AI (AsapChat) oraz wyszukiwarkę produktów (AsapSearch).

- Nazwa techniczna modułu: `asapchat` (folder `asapchat/`, plik główny `asapchat.php`).
- Wspierane wersje PrestaShop: 1.7.8, 8.x, 9.x.
- Wydawca: AsapCommerce.

## Co robi moduł w wersji 1

Dokładnie trzy rzeczy:

1. **Połącz.** Sprzedawca klika „Połącz z AsapChat”, loguje się w `panel.asapchat.io`, wybiera sklep i wraca do modułu. Moduł dostaje tajny token połączenia. Zapasowo: pole na klucz instalacyjny skopiowany z panelu.
2. **Wstaw skrypty.** Moduł pobiera konfigurację z backendu AsapChat i wstawia na stronie sklepu znacznik `<script>` czatu i znacznik `<script>` wyszukiwarki. Każdy ma osobny przełącznik.
3. **Pokaż status.** Nazwa połączonego sklepu, plan, informacja czy widget był już widziany na stronie, link do panelu, przycisk „Rozłącz”.

## Czego moduł nie robi

- Nie synchronizuje zamówień ani produktów. Backend AsapChat pobiera je sam przez Webservice PrestaShop.
- Nie wystawia żadnego własnego API (stare „pull API” z gałęzi `legacy` nie wraca).
- Nie pokazuje statystyk.
- Nie prosi o hasło do AsapChat i nigdzie go nie zapisuje.

## Status i bramki

| Rzecz | Stan |
|---|---|
| Fala | 2 (po WordPressie) |
| Budowa modułu | Można zaczynać, gdy backend ma gotowy kontrakt z `docs/CONNECT-API.md` |
| Kanał 1: zip w GitHub Releases | Bez bramki. Wydajemy od razu po przejściu definicji ukończenia |
| Kanał 2: PrestaShop Addons | **Zablokowane** do pisemnej odpowiedzi PrestaShop partner support: czy zwykły sprzedawca może wystawić darmowy moduł i czy obowiązuje opłata 99 EUR rocznie. Do tego czasu nie płacimy i nie przechodzimy KYC |

Gałąź `legacy` zawiera prototyp z 2024 roku. Służy tylko jako archiwum. Nie kopiuj z niego kodu.

## Zacznij tutaj (kolejność czytania)

1. `ONBOARDING-DEV.md`: jak pracujemy, jak brać zadania.
2. `AGENTS.md`: zasady kodu dla Ciebie i dla agenta AI.
3. `.ai/specs/2026-11-11-connector-v1.md`: pełna specyfikacja modułu.
4. `docs/CONNECT-API.md`: kontrakt z backendem AsapChat.
5. `docs/mockups/configure.html`: jak ma wyglądać ekran konfiguracji (otwórz w przeglądarce).
6. `docs/accounts.md`: jakie konta założyć i co wymaga właściciela firmy.
7. `docs/marketplace-listing.md`: teksty i wymagania do katalogu Addons oraz kanał zip.
8. Issues w GitHub: zadania w kolejności numerów.
