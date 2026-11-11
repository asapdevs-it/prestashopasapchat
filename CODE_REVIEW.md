# Zasady code review (wtyczki AsapChat)

Plik czyta automatycznie `om-code-review`. Bramka walidacji: `.ai/agentic.config.json` (`validation.commands`). Zasady platformy: `AGENTS.md`. Kontrakt z backendem: `docs/CONNECT-API.md`.

## Priorytety

1. **Bezpieczeństwo**: token połączenia, obsługa powrotu z panelu, uprawnienia administratora.
2. **Sklep klienta nie może ucierpieć**: wtyczka nie spowalnia strony, nie rzuca błędów, nie psuje koszyka.
3. **Zgodność z regułami katalogu** (WordPress.org, PrestaShop Addons, Shopify, Shoper): odrzucenie kosztuje tygodnie.
4. **Zakres**: v1 to połączenie, wstrzyknięcie skryptów, status. Reszta to osobne issue.
5. Styl i uproszczenia.

## Sprawdzenia

**Token i połączenie**
- Token połączenia (`ack_…`) nigdy w HTML, JS, logach, komunikatach, odpowiedziach endpointów. Zapis w magazynie konfiguracji platformy, w miarę możliwości zaszyfrowany i nieładowany automatycznie.
- `state` i `code_verifier` jednorazowe, porównanie odporne na czas, kasowane po użyciu.
- Obsługa powrotu i każda akcja zapisu: sprawdzenie uprawnień administratora i tokenu anty-CSRF platformy.
- Brak endpointów diagnostycznych i trybów debug.

**Strona sklepu**
- Żadnego żądania HTTP do AsapChat podczas renderowania strony sklepu. Konfiguracja tylko z lokalnego zapisu.
- Skrypty jako prawdziwe znaczniki `<script>` z atrybutami z konfiguracji, każda wartość zakodowana jako atrybut HTML.
- Brak połączenia, wyłączony przełącznik albo `enabled: false`: zero znaczników w HTML.
- Błąd 5xx lub timeout przy odświeżaniu nie usuwa działającej konfiguracji; 401, 409 i 410 zatrzymują wstrzykiwanie i pokazują stan „Połącz ponownie”.

**Zgoda i prywatność**
- Zero wywołań do AsapChat przed kliknięciem „Połącz” lub zapisaniem klucza.
- Opis usług zewnętrznych w dokumentacji wtyczki zgodny z tym, co kod naprawdę wysyła.

**Platforma**
- Dane wejściowe czyszczone, dane wyjściowe kodowane funkcjami platformy.
- Odinstalowanie usuwa wszystkie zapisane dane i odwołuje połączenie.
- Teksty przez mechanizm tłumaczeń platformy, polski i angielski.
- Brak kodu skopiowanego z gałęzi `legacy`.

## Testy

- Zmiana zachowania ma test. Błąd ma najpierw test, który go odtwarza.
- Zmiany `risk-high` (połączenie, token, podpisy, webhooki) wymagają testu ścieżki odmowy (zły `state`, brak uprawnień, zły podpis) i drugiej osoby w review.
- Zmiana ekranu: zrzuty wszystkich stanów (niepołączony, połączony, błąd) na szerokości 390 px i desktop.

## Severity

- **Blocker:** wyciek tokenu, brak sprawdzenia uprawnień lub `state`, żądanie HTTP na stronie sklepu, wywołanie przed zgodą, endpoint debug, sekret w repo, złamana reguła katalogu, kod spoza zakresu v1.
- **Major:** brak testu zmiany zachowania, brak tłumaczenia, niepełne odinstalowanie, brak obsługi 401/409/410.
- **Minor/Nit:** styl, nazewnictwo, drobne uproszczenia (nie blokują).
- Czerwone CI lub konflikt zbierane razem z pozostałymi uwagami, nie zamiast przeglądu.
