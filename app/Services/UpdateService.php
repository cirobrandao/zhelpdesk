<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UpdateRepository;
use GuzzleHttp\Client;

class UpdateService
{
    private UpdateRepository $repo;
    private array $config;

    public function __construct(UpdateRepository $repo, array $config)
    {
        $this->repo = $repo;
        $this->config = $config;
    }

    public function currentStatus(): array
    {
        return [
            'last_applied' => $this->repo->lastApplied(),
            'downloaded_manifest' => $this->manifestPath(),
        ];
    }

    public function checkForUpdates(): array
    {
        $endpoint = $this->config['endpoint'] ?? '';
        if ($endpoint === '') {
            return ['error' => 'Update endpoint not configured'];
        }

        $client = new Client(['timeout' => 10]);
        $response = $client->get($endpoint);
        $manifest = json_decode((string) $response->getBody(), true);

        if (!is_array($manifest)) {
            return ['error' => 'Invalid manifest'];
        }

        file_put_contents($this->manifestPath(), json_encode($manifest, JSON_PRETTY_PRINT));
        return ['ok' => true, 'manifest' => $manifest];
    }

    public function downloadAndVerify(): array
    {
        $manifestPath = $this->manifestPath();
        if (!file_exists($manifestPath)) {
            return ['error' => 'Manifest not found'];
        }

        $manifest = json_decode(file_get_contents($manifestPath) ?: '', true);
        if (!is_array($manifest)) {
            return ['error' => 'Invalid manifest'];
        }

        $verify = $this->verifyManifest($manifest);
        if ($verify !== true) {
            return ['error' => $verify];
        }

        $client = new Client(['timeout' => 60]);
        $packagePath = $this->packagePath((string) $manifest['version']);
        $client->get((string) $manifest['url'], ['sink' => $packagePath]);

        $sha256 = hash_file('sha256', $packagePath);
        if (!hash_equals((string) $manifest['sha256'], $sha256)) {
            return ['error' => 'SHA256 mismatch'];
        }

        return ['ok' => true, 'package' => $packagePath];
    }

    private function verifyManifest(array $manifest)
    {
        foreach (['version', 'url', 'sha256', 'signature'] as $field) {
            if (empty($manifest[$field])) {
                return 'Missing field: ' . $field;
            }
        }

        $publicKey = $this->config['public_key'] ?? '';
        if ($publicKey === '') {
            return 'Public key missing';
        }

        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            return 'Sodium extension not available';
        }

        $data = $manifest['version'] . '|' . $manifest['url'] . '|' . $manifest['sha256'];
        $signature = base64_decode((string) $manifest['signature']);
        $key = base64_decode($publicKey);

        if ($signature === false || $key === false) {
            return 'Invalid base64 in manifest or key';
        }

        $valid = sodium_crypto_sign_verify_detached($signature, $data, $key);
        return $valid ? true : 'Invalid signature';
    }

    private function manifestPath(): string
    {
        $dir = storage_path('updates');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'manifest.json';
    }

    private function packagePath(string $version): string
    {
        $dir = storage_path('updates');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'release-' . $version . '.zip';
    }
}
