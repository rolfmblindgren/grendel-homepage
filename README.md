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

GitHub Actions-workflowen forventer disse secrets:

- `SHINY_SERVER_HOST`
- `SHINY_SERVER_USER`
- `SHINY_SERVER_KEY`
- `SHINY_SERVER_PORT` - valgfri, standard 22

Workflowen kopierer filene til `/srv/shiny-server/`.

