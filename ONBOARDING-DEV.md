# Start dla dewelopera

Czytasz to raz, pierwszego dnia. Potem pracujesz z issues.

## 1. Co budujemy

Oficjalne darmowe wtyczki AsapChat dla platform sklepowych. Każda: łączy sklep z kontem AsapChat, wstawia skrypt czatu i wyszukiwarki, daje ustawienia (wygląd, wykluczenia, opóźnienie), pokazuje status i źródło zamówień oraz produktów.

Repozytoria (organizacja `asapdevs-it`), w kolejności prac:

| Nr | Repo | Gdzie trafia |
|---|---|---|
| 1 | `woocommerce-asapchat` | katalog WordPress.org (slug `asapchat`) |
| 2 | `prestashopasapchat` | zip do pobrania (GitHub Releases, link w panelu AsapChat) |
| 3 | `asapchat-gtm-template` | galeria szablonów Google Tag Manager |
| 4 | `asapchat-shoper` | Shoper App Store, aplikacja darmowa |
| później | `asapchat-shopify` | odłożone, nie zaczynamy |
| później | `asapchat-magento` | odłożone, nie zaczynamy |

Backend (`asapchat`) prowadzi Kamil. Kontrakt jest gotowy i opisany w `docs/CONNECT-API.md`. Nie masz dostępu do repo backendu i go nie potrzebujesz: punkt 7 kontraktu mówi, jak testować.

Nie wysyłamy pytań do supportów platform. To repo nie zgłasza niczego do żadnego katalogu: moduł wychodzi jako zip w GitHub Releases.

## 2. Kolejność czytania w repo

1. `Readme.md`
2. `docs/CONNECT-API.md`: jak wtyczka rozmawia z AsapChat
3. `.ai/specs/2026-11-11-connector-v1.md`: co dokładnie ma powstać
4. `docs/mockups/*.html`: otwórz w przeglądarce, tak mają wyglądać ekrany
5. `docs/marketplace-listing.md` i `docs/accounts.md`: teksty strony pobierania i jak wydać wersję
6. `AGENTS.md`, `SDLC.md`, `CODE_REVIEW.md`: zasady pracy

`SDLC.md` jest wspólny dla wszystkich repo firmy i opisuje cały proces po angielsku. Wymienia skille (`om-...`), których nie musisz znać na start: wystarczą te z punktu 4. Wzmianki o `BACKWARD_COMPATIBILITY.md` i `.uxproof/` nie dotyczą repo wtyczek.

## 3. Narzędzia

1. Zainstaluj `gh` (2.82.1 lub nowszy) i `jq`. Zaloguj się: `gh auth login`.
2. Zainstaluj Dockera, PHP (7.4 albo nowsze) i Composera. Po zadaniu 01 `composer install` w katalogu repo daje `php-cs-fixer`, `phpstan` i `phpunit`.
3. Agent AI (Claude Code albo Codex CLI) jest opcjonalny. Jeśli go używasz, to na własnej subskrypcji. Bez agenta robisz te same kroki ręcznie.
4. Jeśli używasz agenta, zainstaluj skille SDLC (raz na komputer):

```bash
npx skills add https://github.com/open-mercato/skills.git --skill '*'
```

5. Sklonuj repo i otwórz w nim agenta. Agent sam czyta `AGENTS.md` i `.ai/agentic.config.json`.

### Sklep lokalny

Używaj oficjalnych obrazów Dockera `prestashop/prestashop` z domyślnym PHP obrazu: tagi `1.7.8`, `8.1` i `9.0`. Każdy obraz potrzebuje kontenera MySQL (opis w dokumentacji obrazu na Docker Hub). Sklep wystaw na `http://localhost:PORT`, bo tylko `localhost` i `127.0.0.1` łączą się bez `https` (kontrakt, punkt 7). Katalog `asapchat/` z repo zamontuj jako `modules/asapchat`.

- Tryb debug: `PS_DEV_MODE=1` w zmiennych kontenera.
- Multistore: w panelu sklepu Preferencje (Konfiguruj), Ogólne, „Włącz multistore”, potem Zaawansowane, Multistore, dodaj drugi sklep z adresem w podkatalogu.
- Motywy: Classic jest w każdej wersji. Hummingbird testujesz tylko na PrestaShop 9.
- Wyszukiwarki i statusów źródeł nie przetestujesz na `localhost`. Kontrakt, punkt 7 i 7a, mówi jak: przykład do testów automatycznych, tunel `https` do testu ręcznego.
- Plan Free to jeden sklep na konto. Do testu multistore załóż drugie konto AsapChat na inny adres e-mail.

## 4. Jak wygląda jedno zadanie

1. Wybierz zadanie o najniższym prefiksie w tytule (`01`, `02`, ...), które nie ma etykiety `in-progress` i którego zadania z sekcji „Zależności” są zamknięte. Kolejność wyznacza prefiks w tytule, nie numer GitHub: zadanie `05` ma numer `#8`.
2. Powiedz agentowi: `/om-fix <numer GitHub>` (małe zadanie) albo `/om-auto-create-pr <numer GitHub>` (większe). W komendach i w `Closes` podajesz numer GitHub (`#N`), w rozmowie i w dokumentach prefiks z tytułu. Agent przypisze issue do Ciebie, doda `in-progress`, zrobi gałąź, kod, testy i PR.
3. Przeczytaj cały diff, zanim poprosisz o review. Agent myli się pewnym tonem. Ty odpowiadasz za kod.
4. Bramka walidacji (komendy z `AGENTS.md`, te same są w `.ai/agentic.config.json`) musi przejść lokalnie.
5. PR do `main`. Każdy PR zatwierdza Kamil (`prz10`): wymusza to `.github/CODEOWNERS` i ochrona gałęzi. Zanim poprosisz o zatwierdzenie, przegląd robi drugi deweloper albo `/om-auto-review-pr <numer PR>`.
6. PR z `needs-qa` czeka na `qa-approved`: zmianę przeklikuje w prawdziwym panelu sklepu drugi deweloper (sprawdzacie nawzajem swoje PR) i dołącza zrzuty ekranu. Przy `risk-high` nigdy nie sprawdzasz własnego PR.
7. Merge tylko przez squash: `main` wymaga podpisanych commitów, a commit ze squasha podpisuje GitHub. W opisie PR wpisz `Closes #N` (numer GitHub). GitHub zamyka issue tylko po angielskim słowie.

## 5. Zasady, których nie łamiemy

- Zakres v1 to to, co jest w specu. Pomysł na coś więcej: nowe issue, nie dodatkowy kod w PR.
- Żadnych sekretów w repo: tokenów, haseł, kluczy API, danych klientów.
- Żadnych endpointów diagnostycznych, które coś zwracają bez autoryzacji.
- Gałąź `legacy` to archiwum starego prototypu. Nikt go nie używa, nie kopiujemy z niego niczego.
- Ekrany wtyczki mają logo AsapChat i wygląd panelu AsapChat (tokeny w specu i mockach), nie natywny formularz platformy. Wyjątek: Shopify (Polaris).
- Teksty dla użytkownika: po polsku forma „Ty”, bez pauz (znaku —), krótko. Wersja angielska obowiązkowa tam, gdzie wymaga jej katalog.
- To repo nie potrzebuje żadnego konta wydawcy. Wydanie opisuje `docs/accounts.md`.

## 6. Kiedy pytać

Gdy spec i mockup mówią co innego, nie pytaj: spec rozstrzyga zachowanie i teksty, mockup układ (spec, punkt 5). Pytaj od razu, gdy: platforma wymaga czegoś, czego nie ma w specu, potrzebujesz zmiany w kontrakcie API, coś kosztuje. Pytanie zadaj jako komentarz w issue i oznacz `prz10`.

## 7. Od Kamila potrzebujesz

| Co | Kiedy |
|---|---|
| Rola „Write” w repo (gałęzie, etykiety, tagi) | przed pierwszym zadaniem |
| Zatwierdzenie każdego PR | przy każdym PR |
| Ustawienie numeru wersji i adresów w backendzie AsapChat | po każdym wydaniu (`docs/accounts.md`) |

Nic więcej: konto AsapChat do testów zakładasz sam (kontrakt, punkt 7).
