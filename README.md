# Grendel homepage

PHP-drevet forside for `https://shiny.grendel.no/`.

## Innhold

- `index.php` - den offentlige forsiden
- `content.json` - innhold som kan oppdateres uten å røre layouten
- `grendel-g.png` - den offisielle logoen
- `favicon.svg` - ikon for nettleseren
- `og.svg` - delingsbilde for sosiale medier
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

Workflowen kopierer `index.php`, `content.json`, `favicon.svg`, `og.svg` og `grendel-g.png` til `/srv/shiny-server/`, og fjerner den gamle `index.html` først.

Hvis du vil at forsiden også skal oppdatere GA-tall automatisk, legg inn:

- `GA_PROPERTY_ID` som repository variable
- `GA_SERVICE_ACCOUNT_JSON` som GitHub secret
- eventuelt `GA_START_DATE` og `GA_END_DATE` hvis du vil styre rapportperioden

Da kjører workflowen `scripts/refresh_ga.php` før publisering og oppdaterer bare tallene i `content.json`.
