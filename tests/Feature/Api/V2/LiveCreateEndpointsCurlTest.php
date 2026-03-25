<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;

class LiveCreateEndpointsCurlTest extends TestCase
{
    private static bool $markdownIntroPrinted = false;

    private string $baseUrl;
    private string $token;
    private bool $insecureTls;
    private int $max429Retries;
    private int $baseRetryDelaySeconds;
    private int $interRequestDelayMs;
    private bool $printCurlCommands;

    protected function setUp(): void
    {
        parent::setUp();

        $runLive = filter_var((string) (getenv('API_V2_RUN_LIVE') ?: '0'), FILTER_VALIDATE_BOOL);
        if (!$runLive) {
            $this->markTestSkipped(
                "Live cURL API tests are disabled. Set API_V2_RUN_LIVE=1 to enable.\\n" .
                "Example:\\n" .
                "API_V2_RUN_LIVE=1 API_V2_BASE_URL='https://hiko-test10.localhost' API_V2_BEARER_TOKEN='13|...' ./vendor/bin/phpunit --filter LiveCreateEndpointsCurlTest --testdox"
            );
        }

        if (!function_exists('curl_init')) {
            $this->markTestSkipped('cURL extension is not available.');
        }

        $this->baseUrl = rtrim((string) (getenv('API_V2_BASE_URL') ?: getenv('API_BASE_URL') ?: ''), '/');
        $this->token = (string) (getenv('API_V2_BEARER_TOKEN') ?: getenv('BEARER_TOKEN') ?: '');

        if ($this->baseUrl === '' || $this->token === '') {
            $this->markTestSkipped(
                "Set API_V2_BASE_URL and API_V2_BEARER_TOKEN to run live V2 cURL create tests.\n" .
                "Example:\n" .
                "API_V2_RUN_LIVE=1 API_V2_BASE_URL='https://hiko-test10.localhost' API_V2_BEARER_TOKEN='13|...' ./vendor/bin/phpunit --filter LiveCreateEndpointsCurlTest --testdox"
            );
        }

        $host = (string) parse_url($this->baseUrl, PHP_URL_HOST);
        $defaultInsecure = str_ends_with($host, '.localhost') || $host === 'localhost';
        $this->insecureTls = filter_var(
            getenv('API_V2_CURL_INSECURE') !== false ? getenv('API_V2_CURL_INSECURE') : ($defaultInsecure ? '1' : '0'),
            FILTER_VALIDATE_BOOL
        );

        $this->max429Retries = max(0, (int) (getenv('API_V2_MAX_429_RETRIES') ?: 8));
        $this->baseRetryDelaySeconds = max(1, (int) (getenv('API_V2_RETRY_BASE_DELAY_SECONDS') ?: 5));
        $this->interRequestDelayMs = max(0, (int) (getenv('API_V2_INTER_REQUEST_DELAY_MS') ?: 0));
        $this->printCurlCommands = filter_var((string) (getenv('API_V2_PRINT_CURL_COMMANDS') ?: '1'), FILTER_VALIDATE_BOOL);
    }

    public function test_can_create_all_v2_entities_in_dependency_order_with_live_curl(): void
    {
        $tag = 'api-v2-live-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

        $globalProfessionCategoryId = $this->assertCreatedAndGetId(
            'global-profession-categories',
            $this->curlJson('POST', '/global-profession-categories', [
                'cs' => "GPC {$tag}",
                'en' => "GPC {$tag}",
                'client_meta' => ['external_id' => "gpc-{$tag}"],
            ])
        );

        $globalProfessionId = $this->assertCreatedAndGetId(
            'global-professions',
            $this->curlJson('POST', '/global-professions', [
                'cs' => "Global Profession {$tag}",
                'en' => "Global Profession {$tag}",
                'category_id' => $globalProfessionCategoryId,
                'client_meta' => ['external_id' => "gp-{$tag}"],
            ])
        );

        $professionCategoryId = $this->assertCreatedAndGetId(
            'profession-categories',
            $this->curlJson('POST', '/profession-categories', [
                'cs' => "Mistni profesni kategorie {$tag}",
                'en' => "Local profession category {$tag}",
                'client_meta' => ['external_id' => "pc-{$tag}"],
            ])
        );

        $professionId = $this->assertCreatedAndGetId(
            'professions',
            $this->curlJson('POST', '/professions', [
                'cs' => "Mistni profese {$tag}",
                'en' => "Local profession {$tag}",
                'category_id' => $professionCategoryId,
                'client_meta' => ['external_id' => "p-{$tag}"],
            ])
        );

        $globalKeywordCategoryId = $this->assertCreatedAndGetId(
            'global-keyword-categories',
            $this->curlJson('POST', '/global-keyword-categories', [
                'cs' => "GKC {$tag}",
                'en' => "GKC {$tag}",
                'client_meta' => ['external_id' => "gkc-{$tag}"],
            ])
        );

        $globalKeywordId = $this->assertCreatedAndGetId(
            'global-keywords',
            $this->curlJson('POST', '/global-keywords', [
                'cs' => "Global klicove slovo {$tag}",
                'en' => "Global keyword {$tag}",
                'category_id' => $globalKeywordCategoryId,
                'client_meta' => ['external_id' => "gk-{$tag}"],
            ])
        );

        $keywordCategoryId = $this->assertCreatedAndGetId(
            'keyword-categories',
            $this->curlJson('POST', '/keyword-categories', [
                'cs' => "Mistni kategorie klicovych slov {$tag}",
                'en' => "Local keyword category {$tag}",
                'client_meta' => ['external_id' => "kc-{$tag}"],
            ])
        );

        $keywordId = $this->assertCreatedAndGetId(
            'keywords',
            $this->curlJson('POST', '/keywords', [
                'cs' => "Mistni klicove slovo {$tag}",
                'en' => "Local keyword {$tag}",
                'category_id' => $keywordCategoryId,
                'client_meta' => ['external_id' => "k-{$tag}"],
            ])
        );

        $locationId = $this->assertCreatedAndGetId(
            'locations (repository)',
            $this->curlJson('POST', '/locations', [
                'name' => "Repository {$tag}",
                'type' => 'repository',
                'client_meta' => ['external_id' => "loc-repo-{$tag}"],
            ])
        );

        $collectionLocationId = $this->assertCreatedAndGetId(
            'locations (collection)',
            $this->curlJson('POST', '/locations', [
                'name' => "Collection {$tag}",
                'type' => 'collection',
                'client_meta' => ['external_id' => "loc-collection-{$tag}"],
            ])
        );

        $globalLocationId = $this->assertCreatedAndGetId(
            'global-locations',
            $this->curlJson('POST', '/global-locations', [
                'name' => "Global Archive {$tag}",
                'type' => 'archive',
                'client_meta' => ['external_id' => "gloc-{$tag}"],
            ])
        );

        $globalPlaceId = $this->assertCreatedAndGetId(
            'global-places',
            $this->curlJson('POST', '/global-places', [
                'name' => "Global Place {$tag}",
                'country' => 'Czech Republic',
                'division' => 'Bohemia',
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "gplace-{$tag}"],
            ])
        );

        $localPlaceId = $this->assertCreatedAndGetId(
            'places',
            $this->curlJson('POST', '/places', [
                'name' => "Local Place {$tag}",
                'country' => 'Czech Republic',
                'division' => 'Moravia',
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "place-{$tag}"],
            ])
        );

        $globalInstitutionIdentityId = $this->assertCreatedAndGetId(
            'global-identities (institution)',
            $this->curlJson('POST', '/global-identities', [
                'type' => 'institution',
                'name' => "Global Institution {$tag}",
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "gi-inst-{$tag}"],
                'professions' => [
                    ['id' => $globalProfessionId, 'scope' => 'global'],
                ],
            ])
        );

        $religionIds = $this->fetchActiveReligionIds(2);
        $globalPersonReligions = array_slice($religionIds, 0, min(3, count($religionIds)));
        $authorReligions = $this->pickRandomReligionIds($religionIds, 2);
        $mentionedReligions = $this->pickRandomReligionIds($religionIds, 2);

        $globalPersonIdentityId = $this->assertCreatedAndGetId(
            'global-identities (person)',
            $this->curlJson('POST', '/global-identities', [
                'type' => 'person',
                'surname' => "GlobalPerson{$tag}",
                'forename' => 'Author',
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "gi-person-{$tag}"],
                'professions' => [
                    ['id' => $globalProfessionId, 'scope' => 'global'],
                ],
                'religions' => $globalPersonReligions,
            ])
        );

        $localInstitutionRecipientId = $this->assertCreatedAndGetId(
            'identities (institution recipient)',
            $this->curlJson('POST', '/identities', [
                'type' => 'institution',
                'name' => "Local Institution Recipient {$tag}",
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "i-inst-{$tag}"],
                'global_identity' => [
                    'id' => $globalInstitutionIdentityId,
                    'scope' => 'global',
                ],
                'professions' => [
                    ['id' => $professionId, 'scope' => 'local'],
                    ['id' => $globalProfessionId, 'scope' => 'global'],
                ],
            ])
        );

        $localPersonAuthorId = $this->assertCreatedAndGetId(
            'identities (person author)',
            $this->curlJson('POST', '/identities', [
                'type' => 'person',
                'surname' => "LocalPerson{$tag}",
                'forename' => 'Author',
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "i-person-author-{$tag}"],
                'global_identity' => [
                    'id' => $globalPersonIdentityId,
                    'scope' => 'global',
                ],
                'professions' => [
                    ['id' => $professionId, 'scope' => 'local'],
                    ['id' => $globalProfessionId, 'scope' => 'global'],
                ],
                'religions' => $authorReligions,
            ])
        );

        $localPersonMentionedId = $this->assertCreatedAndGetId(
            'identities (person mentioned)',
            $this->curlJson('POST', '/identities', [
                'type' => 'person',
                'surname' => "LocalPerson{$tag}",
                'forename' => 'Mentioned',
                'note' => 'Live API smoke test',
                'client_meta' => ['external_id' => "i-person-mentioned-{$tag}"],
                'global_identity' => [
                    'id' => $globalPersonIdentityId,
                    'scope' => 'global',
                ],
                'religions' => $mentionedReligions,
            ])
        );

        $this->assertIdentityReligionCountAtLeastViaApi($localPersonAuthorId, 2);
        $this->assertIdentityReligionCountAtLeastViaApi($localPersonMentionedId, 2);

        $letterId = $this->assertCreatedAndGetId(
            'letters',
            $this->curlJson('POST', '/letters', [
                'date_year' => 1933,
                'date_month' => 9,
                'date_day' => 13,
                'date_marked' => '13.09.1933',
                'date_uncertain' => false,
                'date_approximate' => true,
                'date_inferred' => false,
                'date_is_range' => true,
                'range_year' => 1939,
                'range_month' => 3,
                'range_day' => 21,
                'date_note' => 'Live API smoke test',

                'author_uncertain' => false,
                'author_inferred' => false,
                'author_note' => 'Author note',
                'recipient_uncertain' => false,
                'recipient_inferred' => true,
                'recipient_note' => 'Recipient note',
                'destination_uncertain' => true,
                'destination_inferred' => false,
                'destination_note' => 'Destination note',
                'origin_uncertain' => false,
                'origin_inferred' => true,
                'origin_note' => 'Origin note',

                'authors' => [
                    ['id' => $localPersonAuthorId, 'scope' => 'local', 'marked' => "Author {$tag}"],
                ],
                'recipients' => [
                    ['id' => $globalPersonIdentityId, 'scope' => 'global', 'marked' => "Global recipient {$tag}", 'salutation' => 'Dear global recipient'],
                ],
                'mentioned' => [
                    ['id' => $localPersonMentionedId, 'scope' => 'local'],
                    ['id' => $globalPersonIdentityId, 'scope' => 'global'],
                    ['id' => $localInstitutionRecipientId, 'scope' => 'local'],
                ],

                'origins' => [
                    ['id' => $localPlaceId, 'scope' => 'local', 'marked' => 'Local origin'],
                ],
                'destinations' => [
                    ['id' => $globalPlaceId, 'scope' => 'global', 'marked' => 'Global destination'],
                ],

                'keywords' => [
                    ['id' => $keywordId, 'scope' => 'local'],
                    ['id' => $globalKeywordId, 'scope' => 'global'],
                ],

                'languages' => 'Arabic;Azerbaijani',
                'abstract' => ['cs' => "Abstrakt {$tag}", 'en' => "Abstract {$tag}"],
                'incipit' => 'Test incipit',
                'explicit' => 'Test explicit',
                'notes_private' => 'Private note',
                'notes_public' => 'Public note',
                'people_mentioned_note' => 'Mentioned note',
                'copyright' => 'Copyright test value',
                'content' => "<p>Test content {$tag}</p>",
                'status' => 'draft',
                'approval' => 0,
                'client_meta' => [
                    'external_id' => "letter-{$tag}",
                    'sync_source' => 'live-smoke',
                ],

                'related_resources' => [
                    ['title' => 'Source 1', 'link' => 'https://example.org/source-1'],
                ],
                'copies' => [
                    [
                        'repository' => "local-{$locationId}",
                        'archive' => "global-{$globalLocationId}",
                        'collection' => "local-{$collectionLocationId}",
                        'signature' => "SIG-{$tag}",
                        'type' => 'letter',
                        'preservation' => 'original',
                        'copy' => 'handwritten',
                        'l_number' => "L-{$tag}",
                        'manifestation_notes' => 'Manifestation note',
                        'location_note' => 'Location note',
                    ],
                ],
            ])
        );

        $this->assertGreaterThan(0, $globalProfessionCategoryId);
        $this->assertGreaterThan(0, $globalProfessionId);
        $this->assertGreaterThan(0, $professionCategoryId);
        $this->assertGreaterThan(0, $professionId);
        $this->assertGreaterThan(0, $globalKeywordCategoryId);
        $this->assertGreaterThan(0, $globalKeywordId);
        $this->assertGreaterThan(0, $keywordCategoryId);
        $this->assertGreaterThan(0, $keywordId);
        $this->assertGreaterThan(0, $locationId);
        $this->assertGreaterThan(0, $collectionLocationId);
        $this->assertGreaterThan(0, $globalLocationId);
        $this->assertGreaterThan(0, $globalPlaceId);
        $this->assertGreaterThan(0, $localPlaceId);
        $this->assertGreaterThan(0, $globalInstitutionIdentityId);
        $this->assertGreaterThan(0, $globalPersonIdentityId);
        $this->assertGreaterThan(0, $localInstitutionRecipientId);
        $this->assertGreaterThan(0, $localPersonAuthorId);
        $this->assertGreaterThan(0, $localPersonMentionedId);
        $this->assertGreaterThan(0, $letterId);
    }

    /**
     * @return array{status:int, json:array|null, raw:string, url:string, headers:array<string,string>, retry_after:int|null}
     */
    private function curlJson(string $method, string $path, ?array $payload = null): array
    {
        $attempt = 0;
        $delaySeconds = $this->baseRetryDelaySeconds;

        do {
            if ($this->interRequestDelayMs > 0) {
                usleep($this->interRequestDelayMs * 1000);
            }

            $response = $this->curlJsonOnce($method, $path, $payload);

            if ($response['status'] !== 429) {
                return $response;
            }

            if ($attempt >= $this->max429Retries) {
                return $response;
            }

            $sleepSeconds = $response['retry_after'] ?? $delaySeconds;
            sleep(max(1, $sleepSeconds));

            $attempt++;
            $delaySeconds = min(30, $delaySeconds * 2);
        } while (true);
    }

    /**
     * @return array{status:int, json:array|null, raw:string, url:string, headers:array<string,string>, retry_after:int|null}
     */
    private function curlJsonOnce(string $method, string $path, ?array $payload = null): array
    {
        $url = $this->resolveUrl($path);
        if ($this->printCurlCommands) {
            $this->printMarkdownIntro();
            fwrite(STDOUT, '## ' . $this->buildActionLabel($method, $path) . PHP_EOL . PHP_EOL);
            fwrite(STDOUT, "```sh\n" . $this->buildCurlCommand($method, $url, $payload) . "\n```\n\n");
        }

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token,
        ];
        $ch = curl_init($url);
        $options = [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
        ];

        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($ch, $options);

        if ($this->insecureTls) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $fullRaw = curl_exec($ch);

        if ($fullRaw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            $this->fail("cURL error on {$url}: {$err}");
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr((string) $fullRaw, 0, $headerSize);
        $rawBody = substr((string) $fullRaw, $headerSize);
        $headers = $this->parseHeaders($rawHeaders);
        $decoded = json_decode((string) $rawBody, true);
        $retryAfter = isset($headers['retry-after']) && ctype_digit($headers['retry-after'])
            ? (int) $headers['retry-after']
            : null;

        return [
            'status' => $status,
            'json' => is_array($decoded) ? $decoded : null,
            'raw' => (string) $rawBody,
            'url' => $url,
            'headers' => $headers,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * @param array{status:int, json:array|null, raw:string, url:string, headers:array<string,string>, retry_after:int|null} $response
     */
    private function assertCreatedAndGetId(string $label, array $response): int
    {
        $this->assertSame(
            201,
            $response['status'],
            "{$label} expected HTTP 201 from {$response['url']}. Raw response: {$response['raw']}"
        );

        $id = $response['json']['data']['id'] ?? $response['json']['id'] ?? null;

        if (is_string($id) && ctype_digit($id)) {
            $id = (int) $id;
        }

        $this->assertIsInt(
            $id,
            "{$label} response should contain integer id. Raw response: {$response['raw']}"
        );

        if ($this->printCurlCommands) {
            fwrite(STDOUT, "- Result: created `{$label}` with id `{$id}`." . PHP_EOL . PHP_EOL);
        }

        return $id;
    }

    /**
     * @return array<string,string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = preg_split('/\r\n|\r|\n/', $rawHeaders) ?: [];

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($value);
        }

        return $headers;
    }

    private function resolveUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        if (str_contains($this->baseUrl, '/api/v2')) {
            return $this->baseUrl . $path;
        }

        return $this->baseUrl . '/api/v2' . $path;
    }

    private function buildCurlCommand(string $method, string $url, ?array $payload = null): string
    {
        $parts = [
            'curl',
            '-X ' . strtoupper($method),
            escapeshellarg($url),
            '-H ' . escapeshellarg('Authorization: Bearer ' . $this->token),
            '-H ' . escapeshellarg('Accept: application/json'),
        ];

        if ($this->insecureTls) {
            $parts[] = '-k';
        }

        if ($payload !== null) {
            $parts[] = '-H ' . escapeshellarg('Content-Type: application/json');
            $parts[] = '-d ' . escapeshellarg((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return implode(" \\\n  ", $parts);
    }

    private function printMarkdownIntro(): void
    {
        if (self::$markdownIntroPrinted) {
            return;
        }

        self::$markdownIntroPrinted = true;

        $lines = [
            '# Live Create Endpoints cURL Test',
            '',
            'This file is generated from `LiveCreateEndpointsCurlTest` using PHPUnit `--testdox` output redirection.',
            '',
            '## Context',
            '',
            '- Base URL: `' . $this->baseUrl . '`',
            '- TLS verification disabled: `' . ($this->insecureTls ? 'yes' : 'no') . '`',
            '- cURL commands printed: `' . ($this->printCurlCommands ? 'yes' : 'no') . '`',
            '',
            '',
        ];

        fwrite(STDOUT, implode(PHP_EOL, $lines));
    }

    private function buildActionLabel(string $method, string $path): string
    {
        $verb = match (strtoupper($method)) {
            'POST' => 'CREATE',
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            default => 'FETCH',
        };

        $cleanPath = ltrim(strtok($path, '?') ?: $path, '/');
        $parts = array_values(array_filter(explode('/', $cleanPath), fn ($part) => $part !== ''));
        $candidate = $parts[0] ?? 'resource';

        if (isset($parts[1]) && ctype_digit($parts[1])) {
            $candidate = $parts[0];
        }

        $entity = $this->humanizeEntityName($candidate);
        return "{$verb} {$entity}";
    }

    private function humanizeEntityName(string $resource): string
    {
        $normalized = str_replace('-', ' ', strtolower($resource));
        $words = preg_split('/\s+/', trim($normalized)) ?: [];

        $words = array_map(function ($word) {
            if ($word === '') {
                return $word;
            }

            if (str_ends_with($word, 'ies')) {
                return substr($word, 0, -3) . 'y';
            }

            if (str_ends_with($word, 'ses')) {
                return substr($word, 0, -2);
            }

            if (str_ends_with($word, 's') && !str_ends_with($word, 'ss')) {
                return substr($word, 0, -1);
            }

            return $word;
        }, $words);

        return implode(' ', array_map(fn ($word) => ucfirst($word), $words));
    }

    /**
     * @return list<int>
     */
    private function fetchActiveReligionIds(int $min): array
    {
        $response = $this->curlJson('GET', '/religions?active=1&per_page=100&locale=cs');
        $this->assertSame(
            200,
            $response['status'],
            "religions expected HTTP 200 from {$response['url']}. Raw response: {$response['raw']}"
        );

        $rows = $response['json']['data'] ?? [];
        $this->assertIsArray($rows, "religions response should contain data array. Raw response: {$response['raw']}");

        $ids = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['id'])) {
                continue;
            }

            $id = $row['id'];
            if (is_string($id) && ctype_digit($id)) {
                $id = (int) $id;
            }

            if (is_int($id) && $id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        $this->assertGreaterThanOrEqual(
            $min,
            count($ids),
            "religions endpoint returned fewer than {$min} active religions. Raw response: {$response['raw']}"
        );

        return $ids;
    }

    /**
     * @param list<int> $religionIds
     * @return list<int>
     */
    private function pickRandomReligionIds(array $religionIds, int $count): array
    {
        $pool = array_values(array_unique(array_filter($religionIds, fn ($id) => is_int($id) && $id > 0)));
        $this->assertNotEmpty($pool, 'No religion IDs available for random selection.');

        shuffle($pool);
        $selected = array_slice($pool, 0, min($count, count($pool)));

        while (count($selected) < $count) {
            $selected[] = $pool[array_rand($pool)];
        }

        return array_values($selected);
    }

    private function assertIdentityReligionCountAtLeastViaApi(int $identityId, int $min): void
    {
        $response = $this->curlJson('GET', "/identity/{$identityId}");
        $this->assertSame(
            200,
            $response['status'],
            "identity {$identityId} expected HTTP 200 from {$response['url']}. Raw response: {$response['raw']}"
        );

        $religions = $response['json']['data']['religions'] ?? $response['json']['religions'] ?? null;
        $this->assertIsArray(
            $religions,
            "identity {$identityId} response should contain religions array. Raw response: {$response['raw']}"
        );

        $this->assertGreaterThanOrEqual(
            $min,
            count($religions),
            "Expected identity {$identityId} to have at least {$min} religions, got " . count($religions) . ". Raw response: {$response['raw']}"
        );

        if ($this->printCurlCommands) {
            fwrite(
                STDOUT,
                '- Result: identity `' . $identityId . '` currently has `' . count($religions) . '` religion links.' . PHP_EOL . PHP_EOL
            );
        }
    }
}
