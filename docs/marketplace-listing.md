# Teksty: strona pobierania i notatka wydania

Moduł nie jest w katalogu PrestaShop Addons. Sprzedawca pobiera plik `asapchat.zip` z GitHub Releases. Link do pliku jest w panelu AsapChat (Sklep, potem Wtyczka).

Ten plik zawiera gotowe teksty. Nazwa pliku została ze względu na inne repozytoria wtyczek, które mają dokument o tej samej nazwie.

Zasady tekstów: polski, forma „Ty”, krótkie zdania, bez znaku pauzy, bez emoji. Każdy tekst musi zgadzać się z tym, co moduł naprawdę robi.

## 1. Strona pobierania (polski)

### Nazwa

`AsapChat dla PrestaShop: czat AI i wyszukiwarka produktów`

### Krótki opis

`Dodaj do sklepu czat AI, który odpowiada klientom całą dobę, i szybką wyszukiwarkę produktów. Połączysz sklep jednym kliknięciem, bez edycji motywu. Moduł jest darmowy.`

### Przycisk

`Pobierz moduł (asapchat.zip)`

Pod przyciskiem: `Wersja {plugin.latest_version}. PrestaShop 1.7.8, 8 i 9.` oraz link `Co nowego` (adres `changelog_url`).

### Co dostajesz

1. Połączenie sklepu z kontem AsapChat jednym przyciskiem. Nie masz konta? Założysz je po drodze, za darmo.
2. Czat AI na stronie sklepu, w kolorach Twojej marki.
3. Wyszukiwarkę AsapSearch z podpowiedziami i tolerancją literówek.
4. Dodawanie do koszyka prosto z wyników wyszukiwania.
5. Ustawienia wyglądu w panelu sklepu: nazwa asystenta, kolor, pozycja, powitania.
6. Wybór, gdzie czat się pokazuje: opóźnienie i wykluczone strony, kategorie, produkty.
7. Podgląd statusu: czy czat działa i czy AsapChat ma Twoje produkty i zamówienia.
8. Informację o nowej wersji modułu.
9. Obsługę multistore: osobne połączenie dla każdego sklepu.

### Instalacja

1. Pobierz plik `asapchat.zip`. Nie rozpakowuj go.
2. W panelu sklepu wejdź w Moduły, potem Menedżer modułów, i kliknij „Załaduj moduł”. Wskaż plik.
3. Kliknij „Konfiguruj”, potem „Połącz z AsapChat”.
4. Zaloguj się albo załóż darmowe konto. Wybierz sklep i kliknij „Połącz”. Wrócisz do modułu automatycznie.
5. Otwórz stronę sklepu i sprawdź, czy widzisz czat.

### Żeby asystent znał Twoje produkty i zamówienia

AsapChat czyta produkty i zamówienia przez wbudowane API PrestaShop (Webservice). Potrzebujesz klucza:

1. W panelu sklepu wejdź w Zaawansowane, potem API (Webservice).
2. Włącz usługę sieciową i zapisz.
3. Kliknij „Dodaj nowy klucz”, potem „Generuj”.
4. Zaznacz tylko kolumnę GET dla: products, categories, combinations, stock_availables, images, orders, order_states, customers, addresses, carriers.
5. Zapisz i skopiuj klucz.
6. W module kliknij „Podłącz w AsapChat” i wklej klucz w panelu AsapChat.

Tę samą instrukcję masz w module, w sekcji „Źródło zamówień i produktów”.

### Aktualizacja

Moduł sam pokaże pasek „Dostępna wersja X” na ekranie konfiguracji. Wtedy:

1. Kliknij „Pobierz asapchat.zip”.
2. Wejdź w Moduły, potem Menedżer modułów, kliknij „Załaduj moduł” i wskaż nowy plik.

Połączenie i ustawienia zostają. Nie odinstalowuj starej wersji przed wgraniem nowej.

### Jakie dane wysyła moduł

- Moduł nie wysyła nic do AsapChat, dopóki nie klikniesz „Połącz z AsapChat” albo nie wkleisz klucza instalacyjnego.
- Po połączeniu moduł wysyła do AsapChat adres Twojego sklepu i ustawienia wyglądu, które zmienisz na ekranie modułu.
- Na stronie sklepu ładowane są skrypty czatu i wyszukiwarki z serwerów AsapChat.
- Moduł nie wysyła produktów, zamówień ani danych klientów. Te dane AsapChat pobiera sam przez klucz Webservice, jeśli go podłączysz.
- Po kliknięciu „Rozłącz” albo odinstalowaniu modułu skrypty znikają ze strony, a połączenie jest odwoływane.

### Najczęstsze problemy

| Problem | Co zrobić |
|---|---|
| Nie widzę czatu na stronie | Sprawdź przełącznik „Czat” i sekcję „Gdzie pokazywać”. Wyczyść cache w Zaawansowane, potem Wydajność. |
| Czat pojawia się z opóźnieniem | Sprawdź pole „Opóźnienie pojawienia się czatu”. |
| Przełącznik wyszukiwarki jest nieaktywny | AsapChat nie ma jeszcze Twoich produktów. Podłącz klucz Webservice. |
| Przycisk „Połącz” nie działa albo go nie ma | Użyj klucza instalacyjnego z panelu AsapChat (Sklep, potem Wtyczka). |
| Ekran pokazuje „Połącz sklep ponownie” | Połączenie zostało odwołane w panelu AsapChat. Kliknij „Połącz ponownie”. Ustawienia modułu zostały. |
| Mam kilka sklepów (multistore) | Wybierz sklep z listy u góry panelu i połącz każdy osobno. |

## 2. Krótki opis po angielsku

Moduł ma teksty po angielsku i po polsku, więc na stronie wydania w GitHub dajemy też krótki opis angielski:

`AsapChat for PrestaShop: a free module that connects your store to AsapChat and adds an AI chat and the AsapSearch product search to your storefront. Works with PrestaShop 1.7.8, 8 and 9. Install: upload asapchat.zip in Module Manager, click Configure, then Connect with AsapChat.`

## 3. Szablon notatki wydania (GitHub Releases)

Tytuł wydania: `AsapChat dla PrestaShop X.Y.Z`

Treść (skopiuj i uzupełnij, puste sekcje usuń):

```markdown
## Co nowego

- Jedno zdanie na zmianę, z punktu widzenia sprzedawcy. Zacznij od czasownika: „Dodaliśmy…”, „Poprawiliśmy…”.

## Poprawki

- Jedno zdanie na poprawkę.

## Jak zaktualizować

1. Pobierz `asapchat.zip` z tej strony (sekcja Assets).
2. W panelu sklepu: Moduły, Menedżer modułów, „Załaduj moduł”. Wskaż plik.

Połączenie z AsapChat i ustawienia zostają.

## Zgodność

PrestaShop 1.7.8, 8.x, 9.x. PHP 7.2 do 8.4.

---

English: one or two sentences summarising the release.
```

Zasady notatki:

- Piszesz dla sprzedawcy, nie dla programisty. Bez nazw klas, hooków i numerów PR.
- Gdy wersja wymaga czegoś od sprzedawcy (np. ponownego połączenia), napisz to w pierwszym zdaniu.
- Pierwsze wydanie: w „Co nowego” wpisz `Pierwsza wersja modułu.`

## 4. Zrzuty ekranu na stronę pobierania

| Nr | Co pokazuje | Podpis |
|---|---|---|
| 1 | Ekran „Niepołączony” z przyciskiem | `Połącz sklep jednym kliknięciem.` |
| 2 | Ekran „Połączony”: status i przełączniki | `Włącz czat i wyszukiwarkę osobno.` |
| 3 | Sekcja „Wygląd” | `Ustaw kolor, nazwę i powitanie.` |
| 4 | Strona sklepu z otwartym czatem | `Czat AI odpowiada klientom całą dobę.` |
| 5 | Strona sklepu z otwartą wyszukiwarką | `Wyszukiwarka z podpowiedziami.` |

Zrzuty robisz na sklepie testowym z przykładowymi danymi. Bez danych prawdziwych klientów.
