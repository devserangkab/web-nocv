<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Klien data panel aaPanel. URL selalu dibangun pada origin (ip:port) —
 * path/entrance login web pada kolom address dibuang. Autentikasi
 * mengikuti koleksi aaPanelAPI.json: query request_time (epoch ms) +
 * request_token = md5(request_time + md5(secret_key)), token
 * ditambahkan di belakang seperti addQueryParams Postman.
 */
class AaPanelClient
{
    private const REQUEST_TIMEOUT = 15;

    private const PAGE_SIZE = 50;

    private const MAX_PAGES = 100;

    private const ENDPOINT_V2 = '/v2/data';

    private const ENDPOINT_LEGACY = '/data';

    /**
     * @param  string  $address  Address panel (boleh memuat path/entrance — dibuang).
     * @param  string  $secretKey  API Key panel (plaintext, dari panels.api_key).
     */
    public function __construct(private string $address, private string $secretKey) {}

    /**
     * Ambil seluruh situs web terdaftar di panel, pagination otomatis.
     * Panel baru menjawab {status:0, message:{...}} lewat /v2/data; panel
     * BT/aaPanel lama menjawab {where, page, data} langsung lewat /data —
     * fallback ke endpoint lama hanya saat endpoint baru tidak mengenali.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchSites(): Collection
    {
        try {
            return $this->fetchSitesVia(self::ENDPOINT_V2);
        } catch (RuntimeException $v2Error) {
            // Coba endpoint gaya lama sebelum menyimpan pesan error berakhir.
        }

        try {
            return $this->fetchSitesVia(self::ENDPOINT_LEGACY);
        } catch (RuntimeException $legacyError) {
            throw new RuntimeException('Kedua endpoint aaPanel gagal. '.self::ENDPOINT_V2.' -> '.Str::limit($v2Error->getMessage(), 300).' | '.self::ENDPOINT_LEGACY.' -> '.Str::limit($legacyError->getMessage(), 300));
        }
    }

    /**
     * Crawl pagination satu endpoint data spesifik.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchSitesVia(string $endpoint): Collection
    {
        $sites = collect();

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = Http::timeout(self::REQUEST_TIMEOUT)
                // Panel aaPanel internal dominan memakai sertifikat self-signed
                // (cURL error 60); API Key tetap aman karena dikirim sebagai hash.
                ->withOptions(['verify' => false])
                ->get($this->origin().$endpoint, array_merge($this->dataQuery($page), $this->tokenQuery()));

            if ($response->failed()) {
                throw new RuntimeException($endpoint.' HTTP '.$response->status().': '.$this->snippet($response->body()));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException($endpoint.' respons bukan JSON valid: '.$this->snippet($response->body()));
            }

            $parsed = $this->parseRows($endpoint, $payload);

            if ($parsed['rows'] === []) {
                break;
            }

            $sites = $sites->concat($parsed['rows']);

            // Label pagination aaPanel menyembunyikan total ("Total N").
            $totalKnown = $this->extractTotal($parsed['pageLabel']);

            if ($totalKnown !== null && $sites->count() >= $totalKnown) {
                break;
            }
        }

        return $sites;
    }

    /**
     * Konversi payload aaPanel jadi baris situs: menerima bentuk panel baru
     * (status 0 + message.data) maupun bentuk lama (data & page langsung).
     *
     * @param  array<string, mixed>  $payload
     * @return array{rows: array<int, array<string, mixed>>, pageLabel: mixed}
     */
    private function parseRows(string $endpoint, array $payload): array
    {
        $status = $payload['status'] ?? null;

        if ($status === 0 || $status === true) {
            $container = is_array($payload['message'] ?? null) ? $payload['message'] : $payload;

            return $this->rows($endpoint, $payload, container: $container);
        }

        // Bentuk lama: tanpa wrapper status, data & page pada root.
        if (is_array($payload['data'] ?? null)) {
            return [
                'rows' => array_values($payload['data']),
                'pageLabel' => $payload['page'] ?? null,
            ];
        }

        // Panel menolak: pesan error bersifat keterbacaan (msg/message scalar).
        $reason = $payload['msg'] ?? $payload['message'] ?? null;

        if (is_scalar($reason) && filled($reason)) {
            throw new RuntimeException($endpoint.' menolak request: '.(string) $reason);
        }

        throw new RuntimeException($endpoint.' bentuk respons tidak dikenal: '.$this->snippet(json_encode($payload)));
    }

    /**
     * Ambil data rows dari container; exception informatif bila bentuknya bukan list.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $container
     * @return array{rows: array<int, array<string, mixed>>, pageLabel: mixed}
     */
    private function rows(string $endpoint, array $payload, array $container): array
    {
        $rows = $container['data'] ?? null;

        if (! is_array($rows)) {
            throw new RuntimeException($endpoint.' container data tidak dikenal: '.$this->snippet(json_encode($payload)));
        }

        return [
            'rows' => array_values($rows),
            'pageLabel' => $container['page'] ?? null,
        ];
    }

    /**
     * Parse jumlah terdapat total hasil dari label pagination ("Total N").
     */
    private function extractTotal(mixed $pageLabel): ?int
    {
        if (! is_string($pageLabel)) {
            return null;
        }

        return preg_match('/Total\s*(\d+)/i', $pageLabel, $m) === 1 ? (int) $m[1] : null;
    }

    /**
     * Bangun base URL normal округ, scheme ditoleransi kasus + lowercase, host+port
     * tetap dikendalikan; path/entrance login web dibuang karena API rute
     * selalu diperlakukan pada origin.
     */
    public static function normalizeAddress(string $address): string
    {
        $address = trim($address);

        if (preg_match('/^https?:\/\//i', $address) !== 1) {
            $address = 'http://'.$address;
        }

        // Lowercase scheme, host tetap apa adanya.
        $address = preg_replace_callback('/^https?:\/\//i', fn ($m) => strtolower($m[0]), $address);

        if (preg_match('#^(https?://[^/]+)#i', $address, $m) !== 1 || blank($m[1])) {
            throw new RuntimeException('Address panel tidak dikenal: '.$address);
        }

        return $m[1];
    }

    /**
     * Origin dari address (scheme + host + port).
     */
    private function origin(): string
    {
        return self::normalizeAddress($this->address);
    }

    /**
     * Parameter data standar aaPanel.
     *
     * @return array<string, mixed>
     */
    private function dataQuery(int $page): array
    {
        return [
            'action' => 'getData',
            'p' => $page,
            'limit' => self::PAGE_SIZE,
            'table' => 'sites',
            'search' => '',
            'order' => '',
            'type' => '-1',
        ];
    }

    /**
     * Parameter auth aaPanel untuk tiap request.
     *
     * @return array<string, string>
     */
    private function tokenQuery(): array
    {
        $requestTime = (string) round(microtime(true) * 1000);

        return [
            'request_time' => $requestTime,
            'request_token' => md5($requestTime.md5($this->secretKey)),
        ];
    }

    /**
     * Potongan isi respons untuk pesan error (mengganti secret bila ada).
     */
    private function snippet(?string $body): string
    {
        return Str::limit(str_replace($this->secretKey, '***', (string) $body), 200);
    }
}
