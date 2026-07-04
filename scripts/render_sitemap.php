<?php
declare(strict_types=1);

$contentPath = dirname(__DIR__) . '/content.json';

if (!is_file($contentPath)) {
  fwrite(STDERR, "Missing content.json\n");
  exit(1);
}

try {
  $content = json_decode(file_get_contents($contentPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
  fwrite(STDERR, "Could not read content.json\n");
  exit(1);
}

function sitemapEscape(string $value): string {
  return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalizeSitemapUrl(string $url, string $baseUrl): string {
  $url = trim($url);

  if ($url === '') {
    return '';
  }

  if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
    return $url;
  }

  if ($url[0] !== '/') {
    $url = '/' . $url;
  }

  return rtrim($baseUrl, '/') . $url;
}

function addSitemapUrl(array &$urls, string $url, string $changefreq, string $priority): void {
  $url = trim($url);

  if ($url === '' || isset($urls[$url])) {
    return;
  }

  $urls[$url] = [
    'loc' => $url,
    'changefreq' => $changefreq,
    'priority' => $priority,
  ];
}

$site = $content['site'] ?? [];
$baseUrl = trim((string) ($site['canonical'] ?? 'https://shiny.grendel.no/'));

if ($baseUrl === '') {
  $baseUrl = 'https://shiny.grendel.no/';
}

$urls = [];
addSitemapUrl($urls, rtrim($baseUrl, '/') . '/', 'daily', '1.0');
addSitemapUrl($urls, rtrim($baseUrl, '/') . '/en/', 'daily', '0.8');

$mainCards = $content['main_cards'] ?? [];
$secondaryCards = $content['secondary_cards'] ?? [];
$notesCards = $content['notes_cards'] ?? [];

foreach ($mainCards as $card) {
  if (!is_array($card)) {
    continue;
  }

  $href = trim((string) ($card['href'] ?? ''));
  $path = trim((string) ($card['path'] ?? ''));
  $url = normalizeSitemapUrl($href !== '' ? $href : $path, $baseUrl);

  if ($url === '') {
    continue;
  }

  addSitemapUrl($urls, $url, 'monthly', !empty($card['featured']) ? '0.9' : '0.8');
}

foreach ($secondaryCards as $card) {
  if (!is_array($card)) {
    continue;
  }

  $href = trim((string) ($card['href'] ?? ''));
  $path = trim((string) ($card['path'] ?? ''));
  $url = normalizeSitemapUrl($href !== '' ? $href : $path, $baseUrl);

  if ($url === '') {
    continue;
  }

  addSitemapUrl($urls, $url, 'monthly', '0.6');
}

foreach ($notesCards as $card) {
  if (!is_array($card)) {
    continue;
  }

  $href = trim((string) ($card['href'] ?? ''));
  $path = trim((string) ($card['path'] ?? ''));
  $url = normalizeSitemapUrl($href !== '' ? $href : $path, $baseUrl);

  if ($url === '') {
    continue;
  }

  addSitemapUrl($urls, $url, 'monthly', '0.5');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $entry) {
  echo "  <url>\n";
  echo '    <loc>' . sitemapEscape($entry['loc']) . "</loc>\n";
  echo '    <changefreq>' . sitemapEscape($entry['changefreq']) . "</changefreq>\n";
  echo '    <priority>' . sitemapEscape($entry['priority']) . "</priority>\n";
  echo "  </url>\n";
}

echo "</urlset>\n";
