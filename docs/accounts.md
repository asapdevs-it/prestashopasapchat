# Konta wydawcy: PrestaShop

Wydawcą jest spółka AsapCommerce. Konto zakłada deweloper, właściciel (Kamil) ma do niego pełny dostęp od pierwszego dnia.

## Zasady wspólne

1. Hasła, kody zapasowe 2FA i `module_key` trzymamy w firmowym menedżerze haseł. Nigdy w repo, issue, PR ani na czacie.
2. Po założeniu konta tego samego dnia: wpis w menedżerze haseł udostępniony Kamilowi.
3. Nie płacisz niczego i nie zaczynasz KYC bez zgody Kamila.

## Krok 1: pytanie do PrestaShop (zrób to PRZED zakładaniem konta sprzedawcy)

Nie wiemy, czy zwykły sprzedawca może wystawić darmowy moduł. Od odpowiedzi zależy, czy w ogóle wchodzimy na Addons.

Gdzie wysłać: formularz kontaktowy partnerów na https://addons.prestashop.com (Contact us > Become a seller / Seller support). Dokładna ścieżka formularza: do potwierdzenia.

Treść (wyślij po angielsku):

> Subject: Free connector module for an external SaaS: listing rules
>
> Hello,
>
> We are AsapCommerce, the company behind AsapChat, a SaaS providing an AI chat widget and a product search widget for online stores. We are preparing a free PrestaShop module (compatible with 1.7.8, 8 and 9) whose only function is to connect the store with the merchant's AsapChat account and load our widget scripts on the storefront.
>
> Before we open a seller account we would like to confirm:
>
> 1. Can a regular seller list a module priced at 0 EUR, or is this reserved for partners?
> 2. Does the 99 EUR annual fee per module apply to a free module?
> 3. The module loads JavaScript from our own domain (asapchat.io), because the widget is the service itself. We will declare the domain in header_csp.txt. Is this acceptable in technical validation?
> 4. The merchant needs an AsapChat account (a free plan exists; paid plans are billed on our side). Is a "Connect" button that redirects to our sign-in page allowed, and how may we refer to our sign-up page in the product sheet and documentation?
> 5. Is a partnership agreement or PrestaShop Account / PrestaShop Billing integration required for this kind of module?
> 6. Which minimum PrestaShop version must a new module support today?
>
> Thank you,
> [imię i nazwisko], AsapCommerce

Odpowiedź wklej do issue „Pytanie do PrestaShop partner support o darmowy moduł” i oznacz Kamila.

## Krok 2: konto PrestaShop Marketplace (po zgodzie Kamila)

1. Wejdź na https://addons.prestashop.com i utwórz konto na swój firmowy adres e-mail.
2. Potwierdź e-mail i numer telefonu, zaakceptuj regulamin.
3. Włącz 2FA, jeśli jest dostępne. Kody zapasowe do menedżera haseł.

## Krok 3: konto sprzedawcy

W ustawieniach konta wybierz otwarcie konta sprzedawcy. Potrzebne dane (weź je od Kamila, nie zgaduj):

| Pole | Skąd |
|---|---|
| Pełna nazwa spółki | Kamil |
| Numer KRS lub inny numer rejestrowy | Kamil |
| NIP i numer VAT UE (PL + NIP) | Kamil |
| Adres siedziby | Kamil |
| Planowane kategorie produktów | „Customer service / Chat” oraz „Search and filters” |

## Krok 4: KYC w Stripe (wymaga Kamila osobiście)

KYC (Know Your Customer) to weryfikacja tożsamości osoby reprezentującej firmę. PrestaShop robi ją przez Stripe Express.

1. Dochodzisz w formularzu do kroku Stripe i **zatrzymujesz się**.
2. Umawiasz z Kamilem 20 minut. Kamil przechodzi weryfikację sam: dokument tożsamości, dane dyrektora spółki.
3. Kamil podaje rachunek bankowy w EUR (wymagany, nawet gdy moduł jest darmowy: do potwierdzenia).
4. Nie wpisujesz danych osobowych Kamila samodzielnie i nie przesyłasz skanów dokumentów.

## Krok 5: opłata i module_key

1. Opłata 99 EUR za moduł rocznie: płaci firma, po zgodzie Kamila.
2. W koncie sprzedawcy: Products > New > Add a Module. Pobierz `module_key`.
3. Wpisz `module_key` w konstruktorze `asapchat.php` w osobnym PR.

## Jak właściciel zachowuje dostęp

- Login i hasło w firmowym menedżerze haseł, udostępnione Kamilowi.
- Adres e-mail konta ma przekierowanie kopii do Kamila (ustaw regułę w poczcie).
- Telefon do weryfikacji: firmowy, jeśli jest. Jeśli Twój prywatny, zapisz to w menedżerze haseł, żeby przy zmianie osoby dało się go podmienić.
- Czy PrestaShop pozwala dodać drugiego użytkownika do konta sprzedawcy: do potwierdzenia. Jeśli tak, dodaj Kamila.
