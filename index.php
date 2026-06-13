<?php
declare(strict_types=1);

$contentPath = __DIR__ . '/content.json';

if (!is_file($contentPath)) {
  http_response_code(500);
  echo 'Missing content.json';
  exit;
}

try {
  $content = json_decode(file_get_contents($contentPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Could not read content.json';
  exit;
}

function e(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function renderMetaList(array $items): string {
  $html = '';

  foreach ($items as $item) {
    $html .= '<li>' . e((string) $item) . '</li>';
  }

  return $html;
}

function renderMainCard(array $card): void {
  $class = 'app-card';
  $badge = !empty($card['featured']) ? 'Fremhevet' : 'Anbefalt';

  if (!empty($card['featured'])) {
    $class .= ' featured';
  }

  echo '<a class="' . e($class) . '" href="' . e((string) $card['href']) . '">';
  echo '<div class="card-top">';
  echo '<span class="badge">' . e($badge) . '</span>';
  echo '<span class="path">' . e((string) $card['path']) . '</span>';
  echo '</div>';
  echo '<h3>' . e((string) $card['title']) . '</h3>';
  echo '<p>' . e((string) $card['description']) . '</p>';
  echo '<ul class="meta-list">' . renderMetaList($card['meta'] ?? []) . '</ul>';
  echo '<span class="card-link">Åpne appen</span>';
  echo '</a>';
}

function renderSpotlightCard(array $card): void {
  $href = trim((string) ($card['href'] ?? ''));
  if ($href === '') {
    return;
  }

  echo '<a class="spotlight-card" href="' . e($href) . '">';
  echo '<span class="spotlight-path">' . e(trim((string) ($card['path'] ?? ''))) . '</span>';
  echo '<strong>' . e((string) $card['title']) . '</strong>';
  echo '<span class="spotlight-desc">' . e((string) $card['description']) . '</span>';
  echo '</a>';
}

function renderMiniCard(array $card, bool $link = true): void {
  $link = (bool) ($card['link'] ?? $link);
  $badge = 'Nyttig';
  $displayPath = trim((string) ($card['path'] ?? ''));
  $targetHref = trim((string) ($card['href'] ?? ($card['path'] ?? '')));
  $tag = $link && $targetHref !== '' ? 'a' : 'div';
  $href = $link && $targetHref !== '' ? ' href="' . e($targetHref) . '"' : '';

  echo '<' . $tag . ' class="mini-card"' . $href . '>';
  echo '<div class="card-top">';
  echo '<span class="badge">' . e($badge) . '</span>';
  if ($displayPath !== '') {
    echo '<span class="path">' . e($displayPath) . '</span>';
  }
  echo '</div>';
  echo '<h3>' . e((string) $card['title']) . '</h3>';
  echo '<p>' . e((string) $card['description']) . '</p>';

  if ($link) {
    echo '<span class="card-link">Åpne siden</span>';
  }

  echo '</' . $tag . '>';
}

$site = $content['site'] ?? [];
$hero = $content['hero'] ?? [];
$heroMeta = $content['hero_meta'] ?? [];
$mainCards = $content['main_cards'] ?? [];
$secondaryCards = $content['secondary_cards'] ?? [];
$notesCards = $content['notes_cards'] ?? [];
$heroPopularCards = array_slice($mainCards, 0, 5);
$heroRecommendedCards = array_slice($secondaryCards, 0, 2);

$siteTitle = trim((string) ($site['title'] ?? 'Grendel sine Shiny-apper'));
$siteDescription = trim((string) ($site['description'] ?? ''));
$siteCanonical = trim((string) ($site['canonical'] ?? 'https://shiny.grendel.no/'));
$siteOgImage = trim((string) ($site['og_image'] ?? 'https://shiny.grendel.no/og.svg'));
$brandLine = trim((string) ($site['brand_line'] ?? 'Grendel programvareverksted'));
$ga4MeasurementId = trim((string) ($site['ga4_measurement_id'] ?? ''));
$organizationName = trim((string) ($site['organization_name'] ?? 'Grendel'));
$organizationUrl = trim((string) ($site['organization_url'] ?? $siteCanonical));
$organizationLogo = trim((string) ($site['organization_logo'] ?? 'https://shiny.grendel.no/grendel-g.png'));

$verificationFields = [
  'bing_site_verification' => 'msvalidate.01',
  'google_site_verification' => 'google-site-verification',
  'yandex_site_verification' => 'yandex-verification',
  'baidu_site_verification' => 'baidu-site-verification',
  'naver_site_verification' => 'naver-site-verification',
  'facebook_domain_verification' => 'facebook-domain-verification',
  'pinterest_domain_verification' => 'p:domain_verify',
];

$schemaGraph = [];

if ($siteCanonical !== '') {
  $schemaGraph[] = [
    '@type' => 'Organization',
    '@id' => $siteCanonical . '#organization',
    'name' => $organizationName,
    'url' => $organizationUrl,
    'logo' => [
      '@type' => 'ImageObject',
      'url' => $organizationLogo,
    ],
  ];

  $schemaGraph[] = [
    '@type' => 'WebSite',
    '@id' => $siteCanonical . '#website',
    'url' => $siteCanonical,
    'name' => $siteTitle,
    'description' => $siteDescription,
    'publisher' => [
      '@id' => $siteCanonical . '#organization',
    ],
  ];
}
?>
<!doctype html>
<html lang="nb">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="dark light">
  <title><?= e($siteTitle) ?></title>
  <meta name="description" content="<?= e($siteDescription) ?>">
  <meta property="og:title" content="<?= e($siteTitle) ?>">
  <meta property="og:description" content="<?= e($siteDescription) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e($siteCanonical) ?>">
  <meta property="og:image" content="<?= e($siteOgImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($siteTitle) ?>">
  <meta name="twitter:description" content="<?= e($siteDescription) ?>">
  <meta name="twitter:image" content="<?= e($siteOgImage) ?>">
  <link rel="canonical" href="<?= e($siteCanonical) ?>">
  <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">
  <link rel="icon" href="favicon.svg">
  <?php foreach ($verificationFields as $field => $metaName) : ?>
    <?php if (trim((string) ($site[$field] ?? '')) !== '') : ?>
      <meta name="<?= e($metaName) ?>" content="<?= e(trim((string) $site[$field])) ?>">
    <?php endif; ?>
  <?php endforeach; ?>
  <?php if ($ga4MeasurementId !== '') : ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4MeasurementId) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag() {
        dataLayer.push(arguments);
      }
      gtag('js', new Date());
      gtag('config', '<?= e($ga4MeasurementId) ?>');
    </script>
  <?php endif; ?>
  <?php if ($schemaGraph !== []) : ?>
    <script type="application/ld+json">
<?= json_encode(['@context' => 'https://schema.org', '@graph' => $schemaGraph], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
  <?php endif; ?>
  <style>
    :root {
      --bg: #f6eee2;
      --bg-2: #e7f9f5;
      --panel: rgba(255, 251, 244, 0.82);
      --panel-strong: rgba(255, 255, 255, 0.94);
      --line: rgba(15, 92, 81, 0.16);
      --text: #0f2421;
      --muted: rgba(15, 36, 33, 0.74);
      --muted-2: rgba(15, 36, 33, 0.62);
      --accent: #6de9dc;
      --accent-2: #0f5c51;
      --accent-3: #2aa18f;
      --cream: #fff7ef;
      --shadow: 0 24px 60px rgba(15, 36, 33, 0.12);
      --radius: 28px;
      --radius-sm: 20px;
      --max: 1240px;
    }

    * {
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
      background:
        radial-gradient(circle at 15% 14%, rgba(109, 233, 220, 0.36), transparent 22%),
        radial-gradient(circle at 85% 18%, rgba(255, 245, 233, 0.92), transparent 28%),
        linear-gradient(180deg, #f5ecdf 0%, #eefaf8 100%);
    }

    body {
      margin: 0;
      min-height: 100vh;
      color: var(--text);
      background:
        radial-gradient(circle at 18% 16%, rgba(109, 233, 220, 0.30), transparent 28%),
        radial-gradient(circle at 82% 18%, rgba(255, 250, 243, 0.72), transparent 25%),
        radial-gradient(circle at 70% 84%, rgba(42, 161, 143, 0.11), transparent 24%),
        linear-gradient(180deg, #f7efe4 0%, #eefaf8 44%, #f8f0e6 100%);
      font-family: "Trebuchet MS", "Avenir Next", "Avenir", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
      line-height: 1.5;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(15, 92, 81, 0.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(15, 92, 81, 0.07) 1px, transparent 1px);
      background-size: 72px 72px;
      mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.12), rgba(0, 0, 0, 0.06) 50%, transparent 100%);
      opacity: 0.4;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .shell {
      width: min(var(--max), calc(100% - 32px));
      margin: 0 auto;
      padding: 22px 0 48px;
      position: relative;
      z-index: 1;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      padding: 8px 0 20px;
    }

    .brand {
      display: inline-flex;
      align-items: center;
      gap: 14px;
      font-weight: 700;
      letter-spacing: 0.01em;
    }

    .brand-mark {
      width: 54px;
      height: 54px;
      flex: none;
      padding: 5px;
      border-radius: 16px;
      background: linear-gradient(180deg, rgba(255, 251, 244, 0.98), rgba(223, 254, 249, 0.96));
      border: 1px solid rgba(15, 92, 81, 0.18);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4), 0 12px 26px rgba(15, 36, 33, 0.12);
      object-fit: contain;
    }

    .brand-name {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .brand-name strong {
      font-size: 1rem;
    }

    .brand-name span {
      color: var(--muted-2);
      font-size: 0.88rem;
    }

    .topnav {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 10px;
    }

    .nav-link {
      padding: 10px 14px;
      border: 1px solid rgba(15, 92, 81, 0.14);
      border-radius: 999px;
      color: var(--muted);
      background: rgba(255, 251, 244, 0.72);
      transition: transform 180ms ease, border-color 180ms ease, background 180ms ease, color 180ms ease;
    }

    .nav-link:hover,
    .nav-link:focus-visible {
      transform: translateY(-1px);
      border-color: rgba(15, 92, 81, 0.42);
      color: var(--text);
      background: rgba(255, 255, 255, 0.92);
      outline: none;
    }

    .hero {
      display: grid;
      grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
      gap: 24px;
      align-items: stretch;
      margin: 8px 0 28px;
    }

    .hero-copy,
    .hero-aside,
    .section {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      backdrop-filter: blur(14px);
    }

    .hero-copy {
      padding: 34px;
      position: relative;
      overflow: hidden;
    }

    .hero-copy::after {
      content: "";
      position: absolute;
      inset: auto -6% -38% auto;
      width: 360px;
      height: 360px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(109, 233, 220, 0.22), transparent 70%);
      filter: blur(4px);
      pointer-events: none;
    }

    .hero-copy-layout {
      display: grid;
      grid-template-columns: 1fr;
      gap: 22px;
      position: relative;
      z-index: 1;
    }

    .hero-art {
      margin: 0;
      width: min(100%, 30rem);
      justify-self: start;
      border-radius: 28px;
    }

    .hero-art img {
      display: block;
      width: 100%;
      height: auto;
      object-fit: contain;
    }

    .hero-copy-body {
      position: relative;
      z-index: 1;
      display: grid;
      align-content: start;
    }

    .kicker {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(255, 247, 239, 0.82);
      border: 1px solid rgba(15, 92, 81, 0.16);
      color: var(--accent-2);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.14em;
    }

    h1,
    h2,
    h3,
    p {
      margin: 0;
    }

    h1 {
      font-size: clamp(2.5rem, 5vw, 5.1rem);
      line-height: 0.95;
      letter-spacing: -0.05em;
      margin-top: 18px;
      max-width: 10ch;
    }

    .lead {
      max-width: 62ch;
      margin-top: 18px;
      font-size: clamp(1.05rem, 1.55vw, 1.18rem);
      color: var(--muted);
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 24px;
    }

    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 48px;
      padding: 0 18px;
      border-radius: 999px;
      border: 1px solid rgba(15, 92, 81, 0.16);
      transition: transform 180ms ease, background 180ms ease, border-color 180ms ease, color 180ms ease;
      font-weight: 700;
    }

    .button.primary {
      background: linear-gradient(135deg, rgba(223, 254, 249, 0.98), rgba(109, 233, 220, 0.92));
      color: #0a1917;
      border-color: transparent;
    }

    .button.secondary {
      background: rgba(255, 251, 244, 0.8);
      color: var(--text);
    }

    .button:hover,
    .button:focus-visible {
      transform: translateY(-2px);
      outline: none;
      border-color: rgba(15, 92, 81, 0.48);
    }

    .hero-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 26px;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 12px;
      border-radius: 999px;
      background: rgba(255, 251, 244, 0.8);
      border: 1px solid rgba(15, 92, 81, 0.12);
      color: var(--muted);
      font-size: 0.94rem;
    }

    .chip strong {
      color: var(--text);
    }

    .hero-aside {
      padding: 24px;
      display: grid;
      gap: 18px;
      align-content: start;
      position: relative;
      overflow: hidden;
      background:
        linear-gradient(180deg, rgba(255, 251, 244, 0.94), rgba(238, 250, 248, 0.92)),
        radial-gradient(circle at 20% 10%, rgba(109, 233, 220, 0.12), transparent 35%);
    }

    .hero-aside::before {
      content: "";
      position: absolute;
      inset: auto -10% -18% auto;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(223, 254, 249, 0.96), transparent 72%);
      pointer-events: none;
    }

    .hero-aside-head {
      display: grid;
      gap: 10px;
      position: relative;
      z-index: 1;
    }

    .hero-aside-head h2 {
      font-size: 1.45rem;
      line-height: 1.05;
      letter-spacing: -0.03em;
      max-width: 12ch;
    }

    .hero-aside-head p {
      color: var(--muted);
      max-width: 34ch;
    }

    .spotlight-group {
      display: grid;
      gap: 10px;
      position: relative;
      z-index: 1;
    }

    .spotlight-group h3 {
      font-size: 1rem;
      letter-spacing: 0.01em;
    }

    .spotlight-group p {
      color: var(--muted);
      font-size: 0.95rem;
    }

    .spotlight-list {
      display: grid;
      gap: 10px;
    }

    .spotlight-card {
      display: grid;
      gap: 4px;
      padding: 13px 14px;
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.64);
      border: 1px solid rgba(15, 92, 81, 0.10);
      color: var(--text);
      transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;
    }

    .spotlight-card:hover,
    .spotlight-card:focus-visible {
      transform: translateY(-2px);
      border-color: rgba(15, 92, 81, 0.28);
      background: rgba(255, 255, 255, 0.92);
      outline: none;
    }

    .spotlight-path {
      color: var(--accent-2);
      font-size: 0.84rem;
      font-family: ui-monospace, "SFMono-Regular", Menlo, Consolas, "Liberation Mono", monospace;
    }

    .spotlight-card strong {
      font-size: 1rem;
      line-height: 1.18;
    }

    .spotlight-desc {
      color: var(--muted);
      font-size: 0.92rem;
    }

    .section {
      margin-top: 18px;
      padding: 24px;
    }

    .section-head {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: end;
      margin-bottom: 18px;
    }

    .section-head h2 {
      font-size: clamp(1.4rem, 2vw, 2rem);
      letter-spacing: -0.03em;
      max-width: 18ch;
    }

    .section-head p {
      max-width: 56ch;
      color: var(--muted);
    }

    .app-grid {
      display: grid;
      grid-template-columns: repeat(12, minmax(0, 1fr));
      gap: 16px;
    }

    .app-card {
      grid-column: span 4;
      display: flex;
      flex-direction: column;
      gap: 12px;
      min-height: 260px;
      padding: 20px;
      border-radius: 24px;
      background:
        linear-gradient(180deg, rgba(255, 251, 244, 0.96), rgba(238, 250, 248, 0.96)),
        radial-gradient(circle at top right, rgba(109, 233, 220, 0.16), transparent 42%);
      border: 1px solid rgba(15, 92, 81, 0.10);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
      transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
    }

    .app-card:hover,
    .app-card:focus-visible {
      transform: translateY(-3px);
      border-color: rgba(15, 92, 81, 0.34);
      box-shadow: 0 20px 42px rgba(15, 36, 33, 0.14);
      outline: none;
    }

    .app-card.featured {
      grid-column: span 4;
      background:
        linear-gradient(180deg, rgba(247, 238, 225, 0.98), rgba(223, 254, 249, 0.96)),
        radial-gradient(circle at top right, rgba(15, 92, 81, 0.18), transparent 42%);
    }

    .card-top {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: center;
      font-size: 0.88rem;
      color: var(--muted-2);
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 11px;
      border-radius: 999px;
      background: rgba(255, 247, 239, 0.92);
      color: var(--accent-2);
      border: 1px solid rgba(15, 92, 81, 0.12);
      white-space: nowrap;
    }

    .path {
      font-family: ui-monospace, "SFMono-Regular", Menlo, Consolas, "Liberation Mono", monospace;
      color: rgba(15, 92, 81, 0.72);
    }

    .app-card h3 {
      font-size: 1.35rem;
      line-height: 1.06;
      letter-spacing: -0.03em;
      max-width: 16ch;
    }

    .app-card p {
      color: var(--muted);
      font-size: 0.98rem;
    }

    .meta-list {
      margin: auto 0 0;
      padding: 0;
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .meta-list li {
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.58);
      border: 1px solid rgba(15, 92, 81, 0.08);
      color: var(--muted-2);
      font-size: 0.86rem;
    }

    .card-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 4px;
      font-weight: 700;
      color: var(--accent-2);
    }

    .card-link::after {
      content: "↗";
      transition: transform 180ms ease;
    }

    .app-card:hover .card-link::after,
    .app-card:focus-visible .card-link::after,
    .mini-card:hover .card-link::after,
    .mini-card:focus-visible .card-link::after {
      transform: translateX(2px) translateY(-1px);
    }

    .smaller-grid {
      margin-top: 16px;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .mini-card {
      padding: 20px;
      border-radius: 22px;
      border: 1px solid rgba(15, 92, 81, 0.10);
      background: rgba(255, 251, 244, 0.88);
      display: grid;
      gap: 10px;
      transition: transform 180ms ease, border-color 180ms ease;
    }

    .mini-card:hover,
    .mini-card:focus-visible {
      transform: translateY(-2px);
      border-color: rgba(15, 92, 81, 0.32);
      outline: none;
    }

    .mini-card h3 {
      font-size: 1.15rem;
      letter-spacing: -0.02em;
    }

    .mini-card p {
      color: var(--muted);
    }

    @media (max-width: 1060px) {
      .hero {
        grid-template-columns: 1fr;
      }

      .app-card,
      .app-card.featured {
        grid-column: span 6;
      }
    }

    @media (max-width: 720px) {
      .shell {
        width: min(100% - 20px, var(--max));
        padding-top: 10px;
      }

      .topbar {
        flex-direction: column;
        align-items: flex-start;
      }

      .topnav {
        justify-content: flex-start;
      }

      .hero-copy,
      .hero-aside,
      .section {
        padding: 20px;
      }

      .hero-copy-layout {
        grid-template-columns: 1fr;
      }

      .hero-art {
        width: 100%;
      }

      .section-head {
        flex-direction: column;
        align-items: flex-start;
      }

      .app-card,
      .app-card.featured {
        grid-column: span 12;
      }

      .smaller-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="shell">
    <header class="topbar" aria-label="Toppnavigasjon">
      <a class="brand" href="#topp">
        <img class="brand-mark" src="grendel-g.png" alt="">
        <span class="brand-name">
          <strong><?= e($organizationName) ?></strong>
          <span><?= e($brandLine) ?></span>
        </span>
      </a>
      <nav class="topnav">
        <a class="nav-link" href="#mest-brukt">Mest brukt</a>
        <a class="nav-link" href="#andre-prosjekter">Andre prosjekter</a>
        <a class="nav-link" href="#fotnoter">Notater</a>
      </nav>
    </header>

    <main id="topp">
      <section class="hero" aria-label="Forside">
        <div class="hero-copy">
          <div class="hero-copy-layout">
            <div class="hero-copy-body">
              <span class="kicker"><?= e((string) ($hero['kicker'] ?? 'Offisiell startside')) ?></span>
              <h1><?= e((string) ($hero['title'] ?? 'Grendel sine Shiny-apper')) ?></h1>
              <p class="lead">
                <?= e((string) ($hero['lead'] ?? '')) ?>
              </p>
              <div class="hero-actions">
                <a class="button primary" href="#mest-brukt">Se utvalget</a>
                <a class="button secondary" href="#andre-prosjekter">Se flere sider</a>
              </div>
              <div class="hero-meta" aria-label="Rask status">
                <?php foreach ($heroMeta as $chip): ?>
                  <span class="chip"><strong><?= e((string) ($chip['strong'] ?? '')) ?></strong> <?= e((string) ($chip['text'] ?? '')) ?></span>
                <?php endforeach; ?>
              </div>
            </div>

            <figure class="hero-art">
              <img src="grendel.png" alt="Illustrasjon av Grendel sitt programvareverksted" loading="eager" decoding="async">
            </figure>
          </div>
        </div>

        <aside class="hero-aside" aria-label="Høyrestilt oversikt">
          <div class="hero-aside-head">
            <span class="kicker">Raskt overblikk</span>
            <h2>Fem som er mest brukt, og to som er gode å ha med</h2>
            <p>Ingen tall her, bare en rolig pekepinn på hvor folk vanligvis går først.</p>
          </div>

          <div class="spotlight-group">
            <div>
              <h3>Mest brukt</h3>
              <p>De fem inngangene vi oftest vil løfte frem.</p>
            </div>
            <div class="spotlight-list">
              <?php foreach ($heroPopularCards as $card) {
                renderSpotlightCard($card);
              } ?>
            </div>
          </div>

          <div class="spotlight-group">
            <div>
              <h3>Anbefalt</h3>
              <p>To ekstra sider som fortjener plass i nærheten.</p>
            </div>
            <div class="spotlight-list">
              <?php foreach ($heroRecommendedCards as $card) {
                renderSpotlightCard($card);
              } ?>
            </div>
          </div>
        </aside>

      </section>

      <section class="section" id="mest-brukt">
        <div class="section-head">
          <div>
            <h2>Anbefalte apper</h2>
            <p>
              Dette er sidene vi vil løfte frem akkurat nå.
              De ligger derfor først på forsiden.
            </p>
          </div>
          <span class="badge">Kuratert utvalg</span>
        </div>

        <div class="app-grid">
          <?php foreach ($mainCards as $card) {
            renderMainCard($card);
          } ?>
        </div>
      </section>

      <section class="section" id="andre-prosjekter">
        <div class="section-head">
          <div>
            <h2>Andre prosjekter som også fortjener plass</h2>
            <p>
              Ikke alle sider trenger å være størst for å være nyttige. Noen er små,
              men treffer veldig konkrete behov.
            </p>
          </div>
          <span class="badge">Kuratert, ikke tilfeldig</span>
        </div>

        <div class="smaller-grid">
          <?php foreach ($secondaryCards as $card) {
            renderMiniCard($card);
          } ?>
        </div>
      </section>

      <section class="section" id="fotnoter">
        <div class="section-head">
          <div>
            <h2>Små notater</h2>
            <p>
              Jeg har også tatt med noen gamle innganger som fortsatt er nyttige å finne.
            </p>
          </div>
        </div>

        <div class="smaller-grid">
          <?php foreach ($notesCards as $card) {
            renderMiniCard($card);
          } ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
