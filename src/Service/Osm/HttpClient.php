<?php

declare(strict_types=1);

namespace App\Service\Osm;

/**
 * Minimal HTTP client for the OSM APIs. Uses cURL when available, otherwise PHP streams.
 * Not final so tests can replace the transport.
 */
class HttpClient
{
    public function __construct(
        private string $userAgent,
        private int $timeout = 15,
    ) {
    }

    /**
     * @param array<string, string|int|float> $query
     * @param array<string, string> $headers extra request headers, e.g. an API key
     */
    public function get(string $url, array $query = [], array $headers = []): string
    {
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }
        return $this->request('GET', $url, null, $headers);
    }

    /** @param array<string, string> $form */
    public function post(string $url, array $form): string
    {
        return $this->request('POST', $url, http_build_query($form));
    }

    /** @param array<string, string> $extraHeaders */
    private function request(string $method, string $url, ?string $body = null, array $extraHeaders = []): string
    {
        // No "Expect: 100-continue" – some outbound proxies stall on it.
        $headers = ['Accept: application/json', 'Expect:'];
        foreach ($extraHeaders as $name => $value) {
            $headers[] = "$name: $value";
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $ip = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $body);
            }
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $ip = (string) curl_getinfo($ch, CURLINFO_PRIMARY_IP);
            $error = curl_error($ch);
            if (!is_string($response)) {
                throw new OsmException('Verbindung fehlgeschlagen: ' . $error . ($ip !== '' ? " [IP $ip]" : ''));
            }
        } else {
            $context = stream_context_create(['http' => [
                'method' => $method,
                'header' => implode("\r\n", [...$headers, 'User-Agent: ' . $this->userAgent]),
                'content' => $body ?? '',
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ]]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                throw new OsmException('Verbindung fehlgeschlagen.');
            }
            $status = 0;
            // PHP 8.4+ provides a function; the magic variable is deprecated as of 8.5.
            $headerLines = function_exists('http_get_last_response_headers')
                ? (http_get_last_response_headers() ?? [])
                : $http_response_header;
            foreach ($headerLines as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                    $status = (int) $m[1];
                }
            }
        }

        if ($status === 429) {
            throw new OsmException('Der Dienst ist gerade überlastet (429). Bitte gleich nochmal versuchen.');
        }
        if ($status < 200 || $status >= 300) {
            // IP and the start of the error page tell whether the error came from the
            // service itself or from a proxy in between (e.g. at the hosting provider).
            $snippet = self::snippet($response);
            throw new OsmException("Der Dienst antwortet mit HTTP $status."
                . ($ip !== '' ? " [IP $ip]" : '')
                . ($snippet !== '' ? " „{$snippet}“" : ''));
        }
        return $response;
    }

    private static function snippet(string $body): string
    {
        $text = (string) preg_replace(['#<(script|style)\b.*?</\1>#is', '/<[^>]*>/'], ' ', $body);
        $text = trim((string) preg_replace('/\s+/', ' ', html_entity_decode($text)));
        return mb_strlen($text) > 120 ? mb_substr($text, 0, 120) . '…' : $text;
    }
}
