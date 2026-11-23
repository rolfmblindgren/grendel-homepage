<?php
declare(strict_types=1);

// Validate the actual static files that will be deployed, in both languages.
$root = dirname(__DIR__);
$buildDir = $argv[1] ?? $root;
$content = json_decode(file_get_contents($root . '/content.json'), true, 512, JSON_THROW_ON_ERROR);

function check(bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

foreach (['nb' => '/index.html', 'en' => '/en/index.html'] as $language => $path) {
  $html = file_get_contents($buildDir . $path);
  check($html !== false, "Missing rendered page: $path");
  $localized = $language === 'en'
    ? array_replace_recursive($content, $content['translations']['en']) : $content;
  $canonical = rtrim($content['site']['canonical'], '/') . ($language === 'en' ? '/en/' : '/');
  check(str_contains($html, '<html lang="' . $language . '">'), "Wrong language: $path");
  check(str_contains($html, '<link rel="canonical" href="' . $canonical . '">'), "Wrong canonical: $path");
  check(substr_count($html, '<h1>') === 1, "Expected one H1: $path");
  check(!str_contains($html, 'primaryLanguage'), "Do not redirect first-time visitors by browser language: $path");
  foreach (['nb', 'en', 'x-default'] as $alternate) {
    check(str_contains($html, 'hreflang="' . $alternate . '"'), "Missing language alternate: $path");
  }
  preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
  $schema = json_decode($match[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
  $pages = array_values(array_filter($schema['@graph'], fn($node) => isset($node['mainEntity'])));
  check(count($pages) === 1, "Expected one app collection: $path");
  $list = $pages[0]['mainEntity'];
  check($list['numberOfItems'] === count($localized['main_cards']), "Wrong app count: $path");
  preg_match('~<section class="section" id="mest-brukt">(.*?)</section>~s', $html, $section);
  foreach ($localized['main_cards'] as $i => $card) {
    $item = $list['itemListElement'][$i];
    check($item['position'] === $i + 1 && $item['item']['url'] === $card['href']
      && $item['item']['name'] === $card['title'], "Schema app mismatch: $path, item $i");
    check(str_contains(html_entity_decode($section[1] ?? '', ENT_QUOTES, 'UTF-8'), $card['title']),
      "Schema app is missing from the visible collection: $path, item $i");
  }
  $imageUrl = $localized['site']['og_image'];
  check(str_contains($html, '<meta property="og:image" content="' . $imageUrl . '">'), "Wrong sharing image: $path");
  $image = getimagesize($root . '/' . basename(parse_url($imageUrl, PHP_URL_PATH)));
  check($image !== false && $image['mime'] === 'image/png'
    && $image[0] === 1600 && $image[1] === 900, "Invalid sharing image: $path");
}

$sitemap = file_get_contents($buildDir . '/sitemap.xml');
preg_match_all('~<loc>(.*?)</loc>~s', $sitemap, $matches);
check(count($matches[1]) >= 2, 'Sitemap must include both home pages');
foreach ($matches[1] as $url) {
  check(str_starts_with($url, rtrim($content['site']['canonical'], '/') . '/'),
    'External URL in sitemap: ' . $url);
}
check(in_array(rtrim($content['site']['canonical'], '/') . '/en/', $matches[1], true), 'Missing English sitemap entry');
echo "SEO checks passed: both languages, canonical URLs, visible app/schema consistency, sharing images and sitemap.\n";
