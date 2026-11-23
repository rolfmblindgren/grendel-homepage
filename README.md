# Grendel homepage

Statisk forside for `https://shiny.grendel.no/`, bygget fra en PHP-mal i deploy.

## Innhold

- `index.php` - kilde-malen som render den offentlige `index.html`
- `content.json` - innhold som kan oppdateres uten å røre layouten, inkludert GA4, schema.org og verifiseringskoder
- `grendel-g.png` - den offisielle logoen
- `favicon.svg` - ikon for nettleseren
- `og.svg` / `og-en.svg` - redigerbare originaler til delingsbildene
- `og.png` / `og-en.png` - delingsbilder i 16:9, norsk og engelsk
- `grendel.webp` / `grendel-640.webp` - komprimerte varianter av forsideillustrasjonen
- `scripts/render_sitemap.php` - bygger `sitemap.xml` fra samme innhold som forsiden
- `scripts/check_seo.php` - kontrollerer de ferdig renderte språkversjonene før publisering
- `robots.txt` - peker søkemotorer til sitemap
- `.github/workflows/deploy.yml` - deploy til live server

## Lokalt

Kjør en enkel PHP-server:

```sh
php -S 127.0.0.1:8080
```

Deretter kan du åpne `http://127.0.0.1:8080/`.

For å kontrollere den engelske varianten lokalt:

```sh
php index.php en > /tmp/grendel-homepage-en.html
```

Deploy bygger norsk forside på `/` og engelsk forside på `/en/`. Språkknappen
lenker mellom de to statiske variantene, og engelske tekster ligger under
`translations.en` i `content.json`.

Begge språkversjonene har fast adresse, egen canonical og gjensidige hreflang-lenker.
Nye besøkende på `/` sendes ikke automatisk til en annen språkversjon ut fra
nettleserspråk. Et eksplisitt valg i språkknappen lagres lokalt; et lagret valg
av engelsk sender senere besøk på `/` til `/en/`.

Kontroller samme statiske filer som publiseres:

```sh
php index.php > index.html
mkdir -p en
php index.php en > en/index.html
php scripts/render_sitemap.php > sitemap.xml
php scripts/check_seo.php
```

Genererte HTML- og sitemap-filer skal ikke sjekkes inn. Delingsbildene er
ferdig rendret PNG, så produksjon trenger ingen bildekonvertering. Etter
endringer i SVG-originalene kan de gjenskapes med `rsvg-convert`:

```sh
rsvg-convert -o og.png og.svg
rsvg-convert -o og-en.png og-en.svg
```

## Deploy

GitHub Actions-workflowen forventer:

- `SHINY_DEPLOY_KEY` som GitHub secret
- `DEPLOY_HOST` som repository variable, eller standardverdien `dnsgrendel.grendel.no`
- `DEPLOY_USER` som repository variable, eller standardverdien `deployshiny`

Workflowen rendrer begge språkversjonene og sitemapet, og kontrollerer metadata,
schema-listen og bildene før filer endres på serveren. HTML, innhold, logo,
delingsbilder og illustrasjonene kopieres til `/srv/shiny-server/`.
Forsiden velger WebP i passende størrelse, med `grendel.png` som reserveformat.
Den rendrer også `scripts/render_sitemap.php` til `sitemap.xml` og legger ut en `robots.txt` som peker på sitemap.
Den kjører både ved push, manuelt og daglig, så landing-page-tallene holder seg oppdatert uten PHP i produksjon.

Hvis du vil at forsiden også skal oppdatere GA-tall automatisk, legg inn:

- `GA_PROPERTY_ID` som repository variable
- `GA_SERVICE_ACCOUNT_JSON` som GitHub secret
- eventuelt `GA_START_DATE` og `GA_END_DATE` hvis du vil styre rapportperioden

Da kjører workflowen `scripts/refresh_ga.php` før publisering og oppdaterer bare tallene i `content.json`.

Hvis du trenger søkemotorverifisering, kan du legge inn felter i `content.json` som `bing_site_verification`, `google_site_verification` og tilsvarende støttefelter. Da blir de skrevet ut som vanlige meta-tagger i den renderte `index.html`.

Som en liten huskelapp: denne forsiden er bare en enkel inngang til Grendel sine Shiny-sider. Kildekode og deploy-oppsett er samlet her i repoet, med arbeidsflyten definert i `.github/workflows/deploy.yml`.

## Search Console-sjekkliste

Sjekk dette de neste 2 til 4 ukene etter at endringen er live:

- `Indexing > Pages`: se at `/` og `/en/` blir indeksert uten feil
- `Performance > Search results`: se etter endring i impressions, clicks og CTR for brand- og app-navn
- `URL inspection`: test at Google ser riktig canonical og riktig språkversjon
- `Enhancements`: sjekk om Google rapporterer strukturerte data eller advarsler
- `Sitemaps`: verifiser at `sitemap.xml` er hentet uten feil
- Kontroller canonical og hreflang i begge språkversjoner; ikke baser kontrollen på en egen «International targeting»-rapport
- `Core Web Vitals`: følg med på eventuelle tregheter etter deploy

En enkel praktisk rytme er:

- Dag 1 til 3: bekreft indeksering og at sitemap er lest inn
- Uke 1: se om impressions begynner å flytte seg
- Uke 2 til 4: vurder om CTR eller språkfordeling endrer seg

Hvis du vil være ekstra nøktern, noter bare disse tre tallene over tid:

- impressions
- clicks
- average CTR
