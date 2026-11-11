# Wydanie modułu: PrestaShop Addons i kanał zip

Dwa kanały. Zip w GitHub Releases robimy od razu. Addons dopiero po odpowiedzi PrestaShop (patrz `docs/accounts.md`, punkt 1).

Oznaczenie „do potwierdzenia” = fakt nie został sprawdzony w oficjalnym źródle. Sprawdź go w formularzu sprzedawcy, zanim na nim polegasz.

## 1. Kanał zip (bez bramki)

1. Tag `v1.0.0` w repo. CI buduje `asapchat.zip` i dołącza do GitHub Release.
2. Instrukcja dla sprzedawcy (do wklejenia na stronę pomocy AsapChat):

> **Instalacja modułu AsapChat w PrestaShop**
> 1. Pobierz plik `asapchat.zip`.
> 2. W panelu sklepu wejdź w Moduły > Menedżer modułów > Załaduj moduł i wskaż plik.
> 3. Kliknij Konfiguruj, potem „Połącz z AsapChat”.
> 4. Zaloguj się lub załóż konto, wybierz sklep i potwierdź.
> 5. Gotowe. Otwórz stronę sklepu i sprawdź, czy widzisz czat.

## 2. Zasady paczki zip

- Plik `asapchat.zip`, w środku jeden folder `asapchat/`, w nim `asapchat.php`.
- Nazwa: małe litery, cyfry, podkreślenia. Bez numeru wersji, bez słów „prestashop” i „module”.
- Jeden moduł w archiwum.
- Bez `__MACOSX`, `.DS_Store`, `.git`, `node_modules`, testów. Zip buduje CI na Linuksie, nie Finder.
- `index.php` w każdym katalogu, `.htaccess` w katalogu głównym modułu.
- `module_key` z konta sprzedawcy wpisany w konstruktorze (tylko dla paczki na Addons).
- `header_csp.txt` w katalogu głównym modułu, treść:

```
https://asapchat.io
https://panel.asapchat.io
```

Uzasadnienie dla recenzenta: skrypty widgetu to sama usługa, a nie zasób pomocniczy. Sposób traktowania zdalnego skryptu przez recenzentów: do potwierdzenia.

## 3. Walidator

1. Wejdź na https://validator.prestashop.com i zaloguj się kontem Marketplace.
2. Wgraj `asapchat.zip`.
3. Popraw wszystko z zakładek Errors, Security, Standards, Licenses.
4. Powtarzaj do zera błędów. Zrzut wyniku dołącz do issue „Wydanie”.

## 4. Karta produktu (gotowe teksty)

### Nazwa

- PL: `AsapChat: czat AI i wyszukiwarka produktów`
- EN: `AsapChat: AI chat and product search`

### Krótki opis

- PL: `Dodaj do sklepu czat AI, który odpowiada klientom całą dobę, i szybką wyszukiwarkę produktów. Połączenie jednym kliknięciem, bez edycji motywu.`
- EN: `Add an AI chat that answers customers around the clock and a fast product search. Connect in one click, no theme editing.`

Limity znaków pól: do potwierdzenia w formularzu sprzedawcy.

### Funkcje

PL:
1. Połączenie sklepu z kontem AsapChat jednym przyciskiem.
2. Czat AI na każdej stronie sklepu, w kolorach Twojej marki.
3. Wyszukiwarka AsapSearch z podpowiedziami i tolerancją literówek.
4. Osobne przełączniki czatu i wyszukiwarki.
5. Podgląd statusu: czy widget działa na stronie.
6. Obsługa multistore: osobne połączenie dla każdego sklepu.
7. Zgodność z PrestaShop 1.7.8, 8 i 9.

EN:
1. Connect your store to AsapChat with one button.
2. AI chat on every page, in your brand colours.
3. AsapSearch product search with suggestions and typo tolerance.
4. Separate switches for chat and search.
5. Status view: see whether the widget is live.
6. Multistore support: one connection per shop.
7. Compatible with PrestaShop 1.7.8, 8 and 9.

### Korzyści dla sprzedawcy

- PL: `Mniej powtarzalnych pytań do obsługi. AI odpowiada na pytania o produkty, dostawę i status zamówienia, a trudne sprawy przekazuje Tobie.`
- EN: `Fewer repetitive support questions. The AI answers questions about products, delivery and order status and hands the hard cases over to you.`

### Korzyści dla klienta sklepu

- PL: `Odpowiedź od razu, także w nocy i w weekend. Produkt znaleziony w kilka sekund, nawet z literówką.`
- EN: `An answer right away, also at night and at weekends. The right product found in seconds, even with a typo.`

### Słowa kluczowe (minimum 5 na język)

- PL: `czat, czat AI, chatbot, wyszukiwarka produktów, obsługa klienta, live chat, asystent AI`
- EN: `chat, AI chat, chatbot, product search, customer service, live chat, AI assistant`

### Usługa zewnętrzna (akapit obowiązkowy w opisie i w dokumentacji)

- PL: `Moduł łączy sklep z usługą AsapChat. Po połączeniu na stronie sklepu ładowane są skrypty z domeny asapchat.io. Moduł wysyła do AsapChat adres sklepu. Konto AsapChat ma plan bezpłatny (50 rozmów AI miesięcznie).`
- EN: `The module connects your store to the AsapChat service. Once connected, scripts from asapchat.io are loaded on the storefront. The module sends your store URL to AsapChat. AsapChat has a free plan (50 AI conversations per month).`

Uwaga: regulamin Addons zabrania linków zewnętrznych, adresów e-mail i adresów stron w opisie i dokumentacji. Jak wolno wskazać stronę rejestracji: do potwierdzenia (pytanie w `docs/accounts.md`).

## 5. Grafiki

| Zasób | Wymaganie |
|---|---|
| Ikona | 57x57 px, PNG (także jako `asapchat/logo.png`) |
| Zrzuty ekranu | minimum 3, najlepiej kwadratowe, osobno dla każdego języka |

Zrzuty i podpisy:

1. Ekran „Niepołączony” z przyciskiem. PL: `Połącz sklep jednym kliknięciem.` EN: `Connect your store in one click.`
2. Ekran „Połączony” z przełącznikami. PL: `Włącz czat i wyszukiwarkę osobno.` EN: `Switch chat and search on separately.`
3. Strona sklepu z otwartym czatem. PL: `Czat AI odpowiada klientom całą dobę.` EN: `The AI chat answers customers around the clock.`
4. Strona sklepu z otwartą wyszukiwarką. PL: `Wyszukiwarka z podpowiedziami.` EN: `Search with suggestions.`

## 6. Dokumentacja PDF (`asapchat/docs/readme_pl.pdf`, `readme_en.pdf`)

Spis treści:

1. Do czego służy moduł.
2. Wymagania (wersje PrestaShop, PHP, konto AsapChat).
3. Instalacja.
4. Połączenie sklepu przyciskiem.
5. Połączenie kluczem instalacyjnym.
6. Przełączniki czatu i wyszukiwarki.
7. Multistore.
8. Rozłączenie i odinstalowanie.
9. Usługa zewnętrzna i przesyłane dane.
10. Najczęstsze problemy (widget niewidoczny, cache, motyw bez hooka stopki).

## 7. Pozostałe pola karty

- Języki: angielski obowiązkowy, polski jako drugi.
- Kraje: wszystkie.
- Zgodność: 1.7.8 do 9.x.
- Changelog: `1.0.0: first release`.
- Sklep demo: opcjonalnie.

## 8. Recenzja

| Etap | Czas |
|---|---|
| 1. Automatyczny | do 24 godzin |
| 2. Techniczny (ekspert) | 5 do 7 dni roboczych |
| 3. Marketingowy | 7 do 10 dni roboczych |

Razem około 12 do 17 dni roboczych.

## 9. Typowe powody odrzucenia

1. Błędy walidatora lub notice w trybie debug.
2. Brak `index.php` albo strażnika `_PS_VERSION_`.
3. Brak zgodności z PrestaShop 9.
4. Zła struktura lub nazwa zipa.
5. Nieescapowane zmienne Smarty, niebezpieczny SQL.
6. Linki zewnętrzne lub dane kontaktowe w opisie albo dokumentacji.
7. Cudze nazwy marek, skopiowane teksty, zrzuty nieprzetłumaczone.
8. Brak dokumentacji.

## 10. Opłaty

- 99 EUR za moduł rocznie (od maja 2024). Czy dotyczy modułu darmowego: do potwierdzenia.
- Prowizja od sprzedaży: nie dotyczy modułu za 0 EUR.
- Czy zwykły sprzedawca może wystawić moduł darmowy: do potwierdzenia. To jest bramka całego kanału Addons.
