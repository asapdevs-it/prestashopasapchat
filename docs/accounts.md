# Wydawanie modułu: GitHub Releases

Moduł nie jest w katalogu PrestaShop Addons. Nie potrzebujesz żadnego konta sprzedawcy ani opłat. Jedyne konto to Twoje konto GitHub z dostępem do repo `asapdevs-it/prestashopasapchat`.

## Kto co robi

| Czynność | Kto |
|---|---|
| Podbicie wersji, tag, sprawdzenie zipa | deweloper, który domyka wydanie |
| Zgoda na wydanie (review PR z podbiciem wersji) | Kamil (`prz10`) |
| Publikacja wydania w GitHub Releases | CI po wypchnięciu tagu. Gdy CI nie działa: deweloper ręcznie |
| Ustawienie wersji w backendzie AsapChat | tylko Kamil |

Uprawnienia: do tworzenia tagów i wydań potrzebujesz roli „Write” w repo. Nadaje ją Kamil.

## Numer wersji

Format `X.Y.Z` (np. `1.0.0`, `1.0.1`, `1.1.0`). Bez litery `v` w kodzie, z literą `v` w tagu.

- `Z` rośnie przy poprawkach.
- `Y` rośnie, gdy dochodzi funkcja.
- `X` rośnie, gdy zmiana wymaga ponownego połączenia sklepu.

Wersja modułu jest w jednym miejscu w kodzie: `$this->version` w konstruktorze `asapchat/asapchat.php`. Moduł porównuje ją z `plugin.latest_version` z AsapChat. Jeśli zapomnisz ją podbić, sklepy po aktualizacji dalej będą widzieć pasek „Dostępna wersja”.

## Wydanie krok po kroku

1. Sprawdź, że wszystkie issues tej wersji są zamknięte, a `main` jest zielony.
2. Otwórz PR „Wydanie X.Y.Z”:
   - zmień `$this->version` w `asapchat/asapchat.php`,
   - dodaj `asapchat/upgrade/upgrade-X.Y.Z.php`, jeśli wersja zmienia klucze `Configuration` albo hooki.
3. Po merge utwórz tag na `main`:

```bash
git checkout main && git pull
git tag vX.Y.Z
git push origin vX.Y.Z
```

4. CI buduje `asapchat.zip` i tworzy wydanie `vX.Y.Z` w GitHub Releases z tym plikiem.
5. Uzupełnij notatkę wydania według szablonu z `docs/marketplace-listing.md`, punkt 3.
6. Sprawdź zip:
   - pobierz go z wydania (nie z własnego dysku),
   - w środku jest jeden folder `asapchat/`, a w nim `asapchat.php`,
   - nie ma `__MACOSX`, `.DS_Store`, `.git`, `vendor/`, testów,
   - wgraj go na sklep z poprzednią wersją modułu: połączenie i ustawienia zostają, ekran pokazuje nową wersję w stopce.
7. Przekaż dane Kamilowi (następny punkt).

Zasady paczki: plik zawsze nazywa się `asapchat.zip`, bez numeru wersji w nazwie. Zip buduje CI na Linuksie, nie Finder.

## Przekazanie wersji Kamilowi

Sklepy dowiadują się o nowej wersji z backendu AsapChat. Backend ma trzy wartości, które ustawia Kamil. Ty mu je podajesz.

Dodaj komentarz w issue „Wydanie X.Y.Z” i oznacz `prz10`. Wklej dokładnie to, z prawdziwymi wartościami:

```
Wydanie PrestaShop gotowe do ustawienia w backendzie:

plugin.latest_version: 1.0.1
download_url:  https://github.com/asapdevs-it/prestashopasapchat/releases/download/v1.0.1/asapchat.zip
changelog_url: https://github.com/asapdevs-it/prestashopasapchat/releases/tag/v1.0.1

Zip pobrany z wydania i wgrany na sklep testowy z wersją 1.0.0: działa.
```

Skąd wziąć adresy:

| Wartość | Skąd |
|---|---|
| `plugin.latest_version` | numer z tagu bez litery `v` |
| `download_url` | na stronie wydania kliknij prawym przyciskiem plik `asapchat.zip` i skopiuj adres linku |
| `changelog_url` | adres strony wydania w przeglądarce |

Kolejność jest ważna:

1. Najpierw wydanie jest publiczne i zip jest sprawdzony.
2. Dopiero potem Kamil ustawia wartości w backendzie.

Gdyby było odwrotnie, sklepy zobaczyłyby pasek z linkiem, który nie działa.

Po ustawieniu Kamil odpisuje w issue. Wtedy sprawdź na sklepie testowym ze starszą wersją: otwórz ekran konfiguracji modułu, pasek „Dostępna wersja X.Y.Z” ma się pokazać, a oba linki mają działać. Potem zamknij issue.

## Wycofanie wersji

Gdy wydana wersja ma poważny błąd:

1. Napisz w issue wydania i oznacz `prz10`. Podaj poprzedni dobry numer wersji i jego adresy.
2. Kamil cofa wartości w backendzie. Pasek przestaje proponować złą wersję.
3. Oznacz złe wydanie w GitHub jako „pre-release” i dopisz na górze notatki, żeby go nie instalować.
4. Poprawkę wydaj jako kolejny numer. Nie podmieniaj pliku w istniejącym wydaniu.

## Sekrety

W tym repo nie ma i nie będzie haseł, tokenów ani kluczy. Wydanie buduje się z wbudowanym tokenem GitHub Actions. Nie dodawaj własnych tokenów do ustawień repo bez zgody Kamila.
