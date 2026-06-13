# Grendel homepage

Statisk forside for `https://shiny.grendel.no/`, bygget fra en PHP-mal i deploy.

## Innhold

- `index.php` - kilde-malen som render den offentlige `index.html`
- `content.json` - innhold som kan oppdateres uten å røre layouten, inkludert GA4, schema.org og verifiseringskoder
- `grendel-g.png` - den offisielle logoen
- `favicon.svg` - ikon for nettleseren
- `og.svg` - delingsbilde for sosiale medier
- `scripts/render_sitemap.php` - bygger `sitemap.xml` fra samme innhold som forsiden
- `robots.txt` - peker søkemotorer til sitemap
- `.github/workflows/deploy.yml` - deploy til live server

## Lokalt

Kjør en enkel PHP-server:

```sh
php -S 127.0.0.1:8080
```

Deretter kan du åpne `http://127.0.0.1:8080/`.

## Deploy

GitHub Actions-workflowen forventer:

- `SHINY_DEPLOY_KEY` som GitHub secret
- `DEPLOY_HOST` som repository variable, eller standardverdien `dnsgrendel.grendel.no`
- `DEPLOY_USER` som repository variable, eller standardverdien `deployshiny`

Workflowen rendrer `index.php` til `index.html`, kopierer `index.html`, `content.json`, `favicon.svg`, `og.svg` og `grendel-g.png` til `/srv/shiny-server/`, og fjerner den gamle `index.html` og `index.php` først.
Den tar også med `grendel.png` når hero-bildet brukes i forsiden.
Den rendrer også `scripts/render_sitemap.php` til `sitemap.xml` og legger ut en `robots.txt` som peker på sitemap.
Den kjører både ved push, manuelt og daglig, så landing-page-tallene holder seg oppdatert uten PHP i produksjon.

Hvis du vil at forsiden også skal oppdatere GA-tall automatisk, legg inn:

- `GA_PROPERTY_ID` som repository variable
- `GA_SERVICE_ACCOUNT_JSON` som GitHub secret
- eventuelt `GA_START_DATE` og `GA_END_DATE` hvis du vil styre rapportperioden

Da kjører workflowen `scripts/refresh_ga.php` før publisering og oppdaterer bare tallene i `content.json`.

Hvis du trenger søkemotorverifisering, kan du legge inn felter i `content.json` som `bing_site_verification`, `google_site_verification` og tilsvarende støttefelter. Da blir de skrevet ut som vanlige meta-tagger i den renderte `index.html`.

Som en liten huskelapp: denne forsiden er bare en enkel inngang til Grendel sine Shiny-sider. Kildekode og deploy-oppsett er samlet her i repoet, med arbeidsflyten definert i `.github/workflows/deploy.yml`.
