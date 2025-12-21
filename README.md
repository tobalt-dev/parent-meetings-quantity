# Parent Meetings Plugin - Naudojimo Instrukcija

**Versija:** 2.2.3
**Autorius:** Tobalt — https://tobalt.lt
**WordPress versija:** 5.0 ar naujesnė
**PHP versija:** 7.4 ar naujesnė

## Apie Įskiepį

Parent Meetings yra išsamus tėvų susitikimų rezervavimo sistema, sukurta mokykloms. Sistema leidžia:
- Valdyti kelis projektus (tėvų dienos, projektų pristatymai, kabinetų rezervacijos)
- Kiekvienam projektui priskirti mokytojus ir klases
- Konfigūruoti rezervacijos formos laukus pagal poreikius
- Automatiškai siųsti el. laiškus su patvirtinimais ir priminimais
- Valdyti rezervacijas (trinti, atšaukti, peržiūrėti)

## Naujausios Funkcijos (v2.2.3)

### 🔧 Pasirenkamas Klasių Priskyrimas (v2.2.3)
- Mokytojai gali būti sukurti BE klasių priskyrimo
- Mokytojai be klasių matomi visose klasėse
- Lankstus darbo procesas - pasirinkite ar mokytojas dirba su konkrečiomis klasėmis ar visomis
- Admin sąsajoje aiškiai pažymėta, kad klasės yra pasirenkamos (pasirinktinai)

### 📊 Analitikos Sistema (v2.2.0)
- Išsami analitikos valdymo skydas
- Interaktyvūs KPI rodikliai ir grafikai
- CSV eksportas duomenų analizei
- Konfigūruojamas duomenų saugojimo laikas

### 🎯 Daugkartinių Projektų Palaikymas
- Sukurkite neribotą skaičių projektų
- Kiekvienas projektas turi savo mokytojus, klases ir nustatymus
- Projektai yra visiškai izoliuoti - nėra duomenų maišymosi

### 💡 Tooltip Pagalbos Sistema
- Visose admin puslapiuose yra baltų apskritimų su "?" ženklu
- Užvedus pelę ant "?" atsiranda patarimai lietuvių kalba
- Aiškina kiekvieno lauko paskirtį ir naudojimą

### 📝 Konfigūruojami Formos Laukai
- Įjunkite/išjunkite laukus pagal poreikius
- Nustatykite kurie laukai yra privalomi
- Pakeiskite laukų pavadinimus
- Laukai: Tėvo vardas, Mokinio vardas, El. paštas, Telefonas, Pastabos

### 🗑️ Rezervacijų Valdymas
- Ištrinkite rezervacijas admin puslapyje
- Atšaukite rezervacijas (išsaugo įrašą, pakeičia statusą)
- Filtruokite rezervacijas pagal projektą

### 🎨 Stilių Izoliacija (v2.2.1)
- Rezervacijos forma atrodo identiškai VISOSE WordPress temose
- Visiškai izoliuoti stiliai nuo temos CSS konfliktų
- 100% suderinamumas su bet kuria tema

## Greitas Startas

### 1. Įdiegimas

1. Įkelkite `parent-meetings-message` katalogą į `/wp-content/plugins/`
2. Aktyvuokite įskiepį WordPress admin panelėje
3. Eikite į **Parent Meetings** meniu

### 2. Pradinis Nustatymas

#### a) Nustatymai (Settings)
Eikite į **Parent Meetings → Nustatymai** ir sukonfigūruokite:

**Bendri Nustatymai:**
- Susitikimo trukmė (pvz., 15 min)
- Pertraukos laikas (pvz., 5 min)
- Minimalus rezervacijos laikas (pvz., 1 val.)
- Laiko juosta (rekomenduojama: Europe/Vilnius)

**El. Pašto Nustatymai:**
- Siuntėjo vardas
- Siuntėjo el. paštas
- Automatiniai priminimai (24 val. prieš)
- iCal priedai

**reCAPTCHA (Rekomenduojama):**
- Gaukite raktus iš https://www.google.com/recaptcha/admin
- Įveskite Site Key ir Secret Key
- Įjungkite reCAPTCHA

#### b) Pridėkite Mokytojus
Eikite į **Parent Meetings → Mokytojai**:

1. Spauskite "Pridėti Mokytoją"
2. Įveskite vardą, pavardę, el. paštą
3. Pasirinkite klases (pasirinktinai - palikite tuščią, jei dirba su visomis klasėmis)
4. Pasirinkite susitikimų tipus (Vietoje, Nuotoliniu būdu)
5. Išsaugokite

**SVARBU:** Nuo v2.2.3 klasių priskyrimas yra pasirenkamas. Jei nepasirinksite klasių, mokytojas bus matomas visose klasėse.

#### c) Pridėkite Klases
Eikite į **Parent Meetings → Klasės**:

1. Spauskite "Pridėti Klasę"
2. Įveskite klasės pavadinimą (pvz., "1A", "2B", "10 klasė")
3. Išsaugokite

#### d) Sukurkite Projektą
Eikite į **Parent Meetings → Projektai**:

1. Įveskite projekto pavadinimą (pvz., "Tėvų dienos 2025.12")
2. Pridėkite aprašymą (neprivaloma)
3. Pasirinkite mokytojus, kurie dalyvaus šiame projekte
4. Pasirinkite klases
5. **Klasių Pasirinkimas:**
   - ✅ Pažymėkite - tėvų susitikimams
   - ❌ Atžymėkite - kabinetų/įrangos rezervacijoms
6. **Sukonfigūruokite formos laukus:**
   - Įjungta: Ar rodyti šį lauką?
   - Privaloma: Ar privalomas užpildyti?
   - Pavadinimas: Kaip bus vadinamas formoje?
7. Išsaugokite ir nukopijuokite shortcode (pvz., `[parent_meetings id="1"]`)

### 3. Mokytojų Grafikų Kūrimas

#### a) Sugeneruokite Magic Link
1. Eikite į **Parent Meetings → Mokytojai**
2. Spauskite "Generuoti Magic Link" šalia mokytojo vardo
3. Nukopijuokite nuorodą ir išsiųskite mokytojui

#### b) Mokytojas Sukuria Grafiką
1. Mokytojas atidaro gautą nuorodą
2. Pasirenka projektą (SVARBU!)
3. Pasirenka datą ir laiką
4. Įveda trukmę ir pertrauką
5. Sistema automatiškai sugeneruoja laiko tarpus
6. Mokytojas gali:
   - Peržiūrėti rezervacijas
   - Atspausdinti sąrašą
   - Pažymėti lankymą

### 4. Įdėkite Rezervacijos Formą

Įdėkite shortcode į bet kurį puslapį:

```
[parent_meetings id="1"]
```

Kur `id="1"` yra jūsų projekto ID (rasite Projektų puslapyje).

### 5. Tėvų Rezervacijos Procesas

1. Tėvai atidaro puslapį su forma
2. Pasirenka klasę (jei įjungta)
3. Pasirenka mokytoją
4. Pasirenka datą ir laiką
5. Užpildo kontaktinius duomenis
6. Patvirtina reCAPTCHA
7. Gauna patvirtinimo laišką su:
   - Susitikimo informacija
   - Atšaukimo nuoroda
   - iCal failu (jei įjungta)

### 6. Rezervacijų Valdymas

#### Admin Vaizdas
Eikite į **Parent Meetings → Rezervacijos**:

- **Filtruokite:** Pasirinkite projektą iš sąrašo
- **Peržiūrėkite:** Matykite visas rezervacijas
- **Atšaukite:** Pakeičia statusą į "Atšaukta"
- **Ištrinkite:** Visiškai ištrina rezervaciją ir atlaisvina laiką

#### Mokytojo Vaizdas
Mokytojas savo Magic Link puslapyje gali:
- Peržiūrėti būsimus susitikimus
- Pažymėti lankymą (Atvyko/Neatvyko/Laukiama)
- Atspausdinti sąrašą

## Naudojimo Scenarijai

### Pavyzdys 1: Tėvų Susitikimai su Mokytojais

**Nustatymai:**
- Projekto pavadinimas: "Tėvų dienos 2025.12"
- Klasių pasirinkimas: ✅ Įjungta
- Mokytojai: Visi klasių auklėtojai
- Klasės: Visos mokyklos klasės
- Formos laukai: Visi įjungti ir privalomi

**Shortcode:**
```
[parent_meetings id="1"]
```

### Pavyzdys 2: Projektų Pristatymai

**Nustatymai:**
- Projekto pavadinimas: "Projektų pristatymai 2025"
- Klasių pasirinkimas: ✅ Įjungta
- Mokytojai: Projektinius darbus vertinantys mokytojai
- Klasės: 9-12 klasės
- Formos laukai: Telefonas - neprivalomas

**Shortcode:**
```
[parent_meetings id="2"]
```

### Pavyzdys 3: Kompiuterių Kabineto Rezervacija

**Nustatymai:**
- Projekto pavadinimas: "Kompiuterių kabinetas"
- Klasių pasirinkimas: ❌ Išjungta
- "Mokytojai": Kabineto administratorius
- Klasės: Nereikalingos
- Formos laukai: Mokinio vardas išjungtas, Pastabos įjungtos

**Shortcode:**
```
[parent_meetings id="3"]
```

### Pavyzdys 4: Vežimėlių Rezervacija

**Nustatymai:**
- Projekto pavadinimas: "Laptopų vežimėliai (6 vnt.)"
- Klasių pasirinkimas: ❌ Išjungta
- "Mokytojai": Sukurkite 6 "mokytojus" - Vežimėlis #1, Vežimėlis #2, ...
- Formos laukai: Tik mokytojas ir laikas

**Shortcode:**
```
[parent_meetings id="4"]
```

## Tooltip Pagalbos Sistema

Visose admin puslapiuose rasite baltus apskritimus su "?" ženklu. Tai tooltip pagalbos sistema:

- **Užveskite pelę** ant "?" ženklo
- **Atsiras juodas langas** su lietuvišku paaišk
inimu
- **Skaito apie:**
  - Kas yra šis laukas
  - Kaip jį naudoti
  - Rekomenduojamos vertės
  - Pavyzdžiai

**Kur rasite tooltips:**
- Nustatymai - prie kiekvieno nustatymo
- Projektai - prie visų formos laukų
- Formos konfigūracija - prie lentelės stulpelių

## Dažniausiai Užduodami Klausimai

### Kaip mokytojas sukuria grafiką?

1. Admin sugeneruoja Magic Link (**Mokytojai → Generuoti Magic Link**)
2. Išsiunčia nuorodą mokytojui
3. Mokytojas atidaro nuorodą
4. Pasirenka projektą (BŪTINA!)
5. Sukuria savo prieinamumą

### Kaip tėvai atšaukia rezervaciją?

Tėvai gauna el. laiške "Atšaukti rezervaciją" nuorodą. Paspaudus rezervacija bus atšaukta.

### Kaip pridėti kelis projektus?

Eikite į **Projektai → Pridėti Projektą**. Galite sukurti neribotą skaičių projektų. Kiekvienas turi savo shortcode.

### Ar galiu naudoti tą patį mokytoją keliems projektams?

Taip! Priskiriant mokytoją projektui, pažymėkite visus projektus, kuriuose jis dalyvauja. Mokytojas kuriant grafiką turės pasirinkti projektą.

### Kaip išjungti klasių pasirinkimą?

Redaguojant projektą, atžymėkite "Rodyti klasių pasirinkimą rezervacijos formoje". Naudinga kabinetų/įrangos rezervacijoms.

### Kaip pakeisti formos laukus?

**Projektai → Redaguoti Projektą → Formos Laukai** lentelėje galite:
- Įjungti/išjungti laukus
- Nustatyti privalomumą
- Pakeisti pavadinimus

### Ar veikia su Gutenberg?

Taip! Tiesiog įdėkite shortcode bloką ir įrašykite `[parent_meetings id="X"]`.

### Kaip eksportuoti rezervacijas?

Mokytojas gali spausdinti sąrašą per Magic Link puslapį. Admin gali peržiūrėti visas rezervacijas **Rezervacijos** puslapyje.

## Techninė Informacija

### Sisteminiai Reikalavimai

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ arba MariaDB 10.0+

### Duomenų Bazės Lentelės

Įskiepis sukuria šias lenteles:
- `wp_pm_teachers` - Mokytojai
- `wp_pm_classes` - Klasės
- `wp_pm_projects` - Projektai
- `wp_pm_teacher_projects` - Mokytojų ir projektų ryšiai
- `wp_pm_class_projects` - Klasių ir projektų ryšiai
- `wp_pm_availability` - Mokytojų prieinamumas
- `wp_pm_slots` - Laiko tarpsniai
- `wp_pm_bookings` - Rezervacijos
- `wp_pm_waiting_list` - Laukimo sąrašas

### Shortcode Parametrai

```
[parent_meetings id="1"]
```

- `id` - Projekto ID (privalomas nuo v2.1.0)

### AJAX Endpoints

Forma naudoja šiuos AJAX veiksmus:
- `pm_get_classes` - Gauti klases
- `pm_get_teachers` - Gauti mokytojus
- `pm_get_time_slots` - Gauti laiko tarpus
- `pm_book_meeting` - Rezervuoti susitikimą
- `pm_cancel_booking` - Atšaukti rezervaciją

### Saugumas

- ✅ Nonce patikrinimas visose formose
- ✅ Capability checks admin funkcijoms
- ✅ Prepared statements SQL užklausoms
- ✅ Input sanitization ir validation
- ✅ Output escaping
- ✅ reCAPTCHA apsauga
- ✅ Rate limiting rezervacijoms
- ✅ Token-based autentifikacija (Magic Links)

## Palaikymas

- **Autorius:** Tobalt
- **Website:** https://tobalt.lt
- **El. paštas:** support@tobalt.lt
- **Versija:** 2.2.3

## Changelog

### 2.2.3 (2025-11-19)
- ✅ Pasirenkamas klasių priskyrimas - mokytojai gali dirbti be klasių priskyrimo
- ✅ Atnaujintas AJAX užklausų logika - mokytojai be klasių matomi visose klasėse
- ✅ Patobulinta admin sąsaja - aiškiai pažymėta, kad klasės yra pasirenkamos
- ✅ Atgalinė suderinamumas - esami klasių priskyrimai veikia kaip anksčiau

### 2.2.2 (2025-11-19)
- Pašalinta klasių paieškos funkcija (supaprastinta sąsaja)
- Klasės dabar rodomos tiesiogiai be filtravimo

### 2.2.1 (2025-11-19)
- Visiškai izoliuoti rezervacijos formos stiliai
- 100% suderinamumas su bet kuria WordPress tema
- Išspręsti CSS konfliktai tarp temos ir įskiepio stilių

### 2.2.0 (2025-11-19)
- Pridėta išsami analitikos sistema
- Interaktyvūs grafikai ir KPI rodikliai
- CSV duomenų eksportas
- Konfigūruojamas duomenų saugojimo laikas

### 2.1.2 (2025-11-14)
- Pridėta tooltip pagalbos sistema su lietuviškais paaiškinimais
- Patobulinti tooltips su baltais apskritimais ir "?" ženklu
- Užtikrintas tooltips veikimas be dashicons priklausomybės

### 2.1.1 (2025-11-14)
- Pridėta rezervacijų trynimo funkcija admin puslapyje
- Pridėta rezervacijų atšaukimo funkcija admin puslapyje
- Pridėtas projektų filtras rezervacijų puslapyje
- Išversta admin sąsaja į lietuvių kalbą

### 2.1.0 (2025-11-14)
- Pridėtas daugkartinių projektų palaikymas
- Sukurta projektų valdymo sistema
- Konfigūruojami formos laukai pagal projektą
- Shortcode parametras `id` projektų atskyrimui
- Automatinė esamų duomenų migracija į numatytąjį projektą
- Projektų izoliacija su atskirais mokytojais ir klasėmis

### 2.0.0
- Pradinė versija su rezervacijų sistema
- Magic Links mokytojams
- Email pranešimai ir priminimai
- iCal eksportas
- Laukimo sąrašas

## Licencija

Šis įskiepis yra sukurtas Tobalt (https://tobalt.lt) ir yra skirtas naudoti pagal sutartį.

---

**Dėkojame, kad naudojate Parent Meetings!** 🎓

Jei turite klausimų ar pasiūlymų, susisiekite per https://tobalt.lt
