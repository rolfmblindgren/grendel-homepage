# Grendel homepage

Statisk forside for `https://shiny.grendel.no/`.

## Innhold

- `index.html` - den offentlige forsiden
- `favicon.svg` - ikon for nettleseren
- `og.svg` - delingsbilde for sosiale medier
- `.github/workflows/deploy.yml` - deploy til live server

## Lokalt

Åpne `index.html` direkte i nettleseren, eller kjør en enkel server:

```sh
python3 -m http.server 8080
```

## Deploy

GitHub Actions-workflowen forventer:

- `SHINY_DEPLOY_KEY` som GitHub secret
- `DEPLOY_HOST` som repository variable, eller standardverdien `dnsgrendel.grendel.no`
- `DEPLOY_USER` som repository variable, eller standardverdien `deployshiny`
- `DEPLOY_GROUP` som repository variable, eller standardverdien `shinyapps`

Workflowen kopierer `index.html`, `favicon.svg` og `og.svg` til `/srv/shiny-server/`.
