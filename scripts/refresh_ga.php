<?php
declare(strict_types=1);

function normalize_path(string $path): string {
  $path = trim($path);

  if ($path === '') {
    return '/';
  }

  $path = preg_replace('/[?#].*$/', '', $path);
  $path = '/' . ltrim((string) $path, '/');
  $path = rtrim($path, '/');

  return $path === '' ? '/' : $path;
}

function base64url_encode(string $value): string {
  return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function http_json(string $url, array $headers = [], ?array $payload = null): array {
  $ch = curl_init($url);

  if ($ch === false) {
    throw new RuntimeException('Could not initialize cURL.');
  }

  $requestHeaders = $headers;

  if ($payload !== null) {
    $requestHeaders[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
  }

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $requestHeaders,
    CURLOPT_TIMEOUT => 30,
  ]);

  $body = curl_exec($ch);

  if ($body === false) {
    $error = curl_error($ch);
    curl_close($ch);
    throw new RuntimeException($error !== '' ? $error : 'Unknown cURL error.');
  }

  $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  $decoded = json_decode($body, true);

  if ($status >= 400) {
    $message = is_array($decoded) && isset($decoded['error']['message']) ? (string) $decoded['error']['message'] : $body;
    throw new RuntimeException('HTTP ' . $status . ': ' . $message);
  }

  if (!is_array($decoded)) {
    throw new RuntimeException('Unexpected JSON response.');
  }

  return $decoded;
}

function fetch_access_token(array $credentials): string {
  $privateKey = (string) ($credentials['private_key'] ?? '');
  $clientEmail = (string) ($credentials['client_email'] ?? '');

  if (trim($privateKey) === '' || trim($clientEmail) === '') {
    throw new RuntimeException('GA_SERVICE_ACCOUNT_JSON must contain client_email and private_key.');
  }

  $now = time();
  $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
  $payload = base64url_encode(json_encode([
    'iss' => $clientEmail,
    'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
    'aud' => 'https://oauth2.googleapis.com/token',
    'iat' => $now,
    'exp' => $now + 3600,
  ], JSON_THROW_ON_ERROR));

  $unsigned = $header . '.' . $payload;
  $signature = '';
  $key = openssl_pkey_get_private($privateKey);

  if ($key === false) {
    throw new RuntimeException('Could not load service account private key.');
  }

  if (openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256) !== true) {
    openssl_pkey_free($key);
    throw new RuntimeException('Could not sign JWT.');
  }

  openssl_pkey_free($key);

  $token = http_json('https://oauth2.googleapis.com/token', [], [
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $unsigned . '.' . base64url_encode($signature),
  ]);

  $accessToken = (string) ($token['access_token'] ?? '');

  if (trim($accessToken) === '') {
    throw new RuntimeException('Google token response did not contain an access token.');
  }

  return $accessToken;
}

function update_sessions(array &$items, array $counts): void {
  foreach ($items as &$item) {
    if (!is_array($item) || !isset($item['path'])) {
      continue;
    }

    $path = normalize_path((string) $item['path']);

    if (array_key_exists($path, $counts)) {
      $item['sessions'] = $counts[$path];
    }
  }
}

$propertyId = trim((string) getenv('GA_PROPERTY_ID'));
$credentialsJson = trim((string) getenv('GA_SERVICE_ACCOUNT_JSON'));

if ($propertyId === '' || $credentialsJson === '') {
  fwrite(STDERR, "[ga] GA_PROPERTY_ID or GA_SERVICE_ACCOUNT_JSON missing. Skipping refresh.\n");
  exit(0);
}

$startDate = trim((string) getenv('GA_START_DATE'));
$endDate = trim((string) getenv('GA_END_DATE'));

if ($startDate === '') {
  $startDate = '180daysAgo';
}

if ($endDate === '') {
  $endDate = 'today';
}

$contentPath = dirname(__DIR__) . '/content.json';
$content = json_decode((string) file_get_contents($contentPath), true, 512, JSON_THROW_ON_ERROR);
$credentials = json_decode($credentialsJson, true, 512, JSON_THROW_ON_ERROR);
$accessToken = fetch_access_token($credentials);

$report = http_json('https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($propertyId) . ':runReport', [
  'Authorization: Bearer ' . $accessToken,
], [
  'dateRanges' => [
    [
      'startDate' => $startDate,
      'endDate' => $endDate,
    ],
  ],
  'dimensions' => [
    [
      'name' => 'landingPagePlusQueryString',
    ],
  ],
  'metrics' => [
    [
      'name' => 'sessions',
    ],
  ],
  'orderBys' => [
    [
      'metric' => [
        'metricName' => 'sessions',
      ],
      'desc' => true,
    ],
  ],
  'limit' => '1000',
]);

$counts = [];

foreach ($report['rows'] ?? [] as $row) {
  if (!is_array($row)) {
    continue;
  }

  $pathValue = (string) ($row['dimensionValues'][0]['value'] ?? '/');
  $sessionsValue = (int) ($row['metricValues'][0]['value'] ?? 0);
  $counts[normalize_path($pathValue)] = $sessionsValue;
}

update_sessions($content['snapshot'] ?? [], $counts);
update_sessions($content['main_cards'] ?? [], $counts);
update_sessions($content['secondary_cards'] ?? [], $counts);
update_sessions($content['notes_cards'] ?? [], $counts);

file_put_contents(
  $contentPath,
  json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

fwrite(STDOUT, sprintf("[ga] Refreshed %d landing pages from %s to %s.\n", count($counts), $startDate, $endDate));

