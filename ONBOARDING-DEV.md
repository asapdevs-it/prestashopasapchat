# Start dla dewelopera

Czytasz to raz, pierwszego dnia. Potem pracujesz z issues.

## 1. Co budujemy

Oficjalne darmowe wtyczki AsapChat dla platform sklepowych. Każda: łączy sklep z kontem AsapChat, wstawia skrypt czatu i wyszukiwarki, daje ustawienia (wygląd, wykluczenia, opóźnienie), pokazuje status i źródło zamówień oraz produktów. Pełny obraz ze wszystkimi mockami: strona planu. Link dostaniesz od Kamila.

Repozytoria (organizacja `asapdevs-it`), w kolejności prac:

| Nr | Repo | Gdzie trafia |
|---|---|---|
| 1 | `woocommerce-asapchat` | katalog WordPress.org (slug `asapchat`) |
| 2 | `prestashopasapchat` | zip do pobrania (GitHub Releases, link w panelu AsapChat), bez Addons |
| 3 | `asapchat-shopify` | Shopify App Store, aplikacja darmowa |
| 4 | `asapchat-gtm-template` | galeria szablonów Google Tag Manager |
| 5 | `asapchat-shoper` | Shoper App Store, aplikacja darmowa |
| później | `asapchat-magento` | odłożone, nie zaczynamy |

Backend (`asapchat`) prowadzi Kamil. Kontrakt jest gotowy i opisany w `docs/CONNECT-API.md`. Nie masz dostępu do repo backendu i go nie potrzebujesz: punkt 7 kontraktu mówi, jak testować.

Nie wysyłamy pytań do supportów platform. Spełniamy opublikowane wymagania i zgłaszamy darmową wtyczkę.

## 2. Kolejność czytania w repo

1. `README.md`
2. `docs/CONNECT-API.md`: jak wtyczka rozmawia z AsapChat
3. `.ai/specs/2026-11-11-connector-v1.md`: co dokładnie ma powstać
4. `docs/mockups/*.html`: otwórz w przeglądarce, tak mają wyglądać ekrany
5. `docs/marketplace-listing.md` i `docs/accounts.md`: wymagania katalogu i konta
6. `AGENTS.md`, `SDLC.md`, `CODE_REVIEW.md`: zasady pracy

Jeśli `README.md` podaje inną kolejność, obowiązuje ta stąd. `SDLC.md` jest wspólny dla wszystkich repo firmy i opisuje cały proces po angielsku. Wymienia skille (`om-...`), których nie musisz znać na start: wystarczą te z punktu 4. Wzmianki o `BACKWARD_COMPATIBILITY.md` i `.uxproof/` nie dotyczą repo wtyczek.

## 3. Narzędzia

1. Zainstaluj `gh` (2.82.1 lub nowszy) i `jq`. Zaloguj się: `gh auth login`.
2. Zainstaluj Claude Code albo Codex CLI.
3. Zainstaluj skille SDLC (raz na komputer):

```bash
npx skills add https://github.com/open-mercato/skills.git --skill '*'
```

4. Sklonuj repo i otwórz w nim agenta. Agent sam czyta `AGENTS.md` i `.ai/agentic.config.json`.

## 4. Jak wygląda jedno zadanie

1. Wybierz issue o najniższym numerze, które nie ma etykiety `in-progress` ani `blocked`. Issues są ułożone w kolejności wykonania.
2. Powiedz agentowi: `/om-fix <numer issue>` (małe zadanie) albo `/om-auto-create-pr <numer issue>` (większe). Agent przypisze issue do Ciebie, doda `in-progress`, zrobi gałąź, kod, testy i PR.
3. Przeczytaj cały diff, zanim poprosisz o review. Agent myli się pewnym tonem. Ty odpowiadasz za kod.
4. Bramka walidacji (komendy z `.ai/agentic.config.json`) musi przejść lokalnie.
5. PR do `main`. Review robi drugi deweloper albo `/om-auto-review-pr <numer PR>`. Zmiany z etykietą `risk-high` (połączenie, tokeny, podpisy) zawsze ogląda też Kamil (`prz10`).
6. PR z `needs-qa` czeka na `qa-approved`: ktoś musi przeklikać zmianę w prawdziwym panelu sklepu i dołączyć zrzuty ekranu.
7. Merge przez squash. Issue zamyka się słowem `zamyka #N` w opisie PR.

## 5. Zasady, których nie łamiemy

- Zakres v1 to to, co jest w specu. Pomysł na coś więcej: nowe issue, nie dodatkowy kod w PR.
- Żadnych sekretów w repo: tokenów, haseł, kluczy API, danych klientów. Dane dostępowe do kont wydawcy trzymamy w firmowym menedżerze haseł.
- Żadnych endpointów diagnostycznych, które coś zwracają bez autoryzacji.
- Gałąź `legacy` to archiwum starego prototypu. Nikt go nie używa, nie kopiujemy z niego niczego.
- Ekrany wtyczki mają logo AsapChat i wygląd panelu AsapChat (tokeny w specu i mockach), nie natywny formularz platformy. Wyjątek: Shopify (Polaris).
- Teksty dla użytkownika: po polsku forma „Ty”, bez pauz (znaku —), krótko. Wersja angielska obowiązkowa tam, gdzie wymaga jej katalog.
- Zanim zaczniesz konto wydawcy na platformie, przeczytaj `docs/accounts.md`. Każde konto ma dać Kamilowi dostęp tak wcześnie, jak platforma pozwala. Jak dokładnie, mówi `docs/accounts.md`.

## 6. Kiedy pytać

Pytaj od razu, gdy: spec i mock mówią co innego, platforma wymaga czegoś, czego nie ma w specu, potrzebujesz zmiany w kontrakcie API, coś kosztuje (opłata za konto, płatna wtyczka do testów). Pytanie zadaj jako komentarz w issue i oznacz `prz10`.
