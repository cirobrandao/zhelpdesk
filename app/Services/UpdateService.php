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
            return ['error' => 'updater.endpoint_missing'];
        }

        $client = new Client(['timeout' => 10]);
        $response = $client->get($endpoint);
        $manifest = json_decode((string) $response->getBody(), true);

        if (!is_array($manifest)) {
            return ['error' => 'updater.invalid_manifest'];
        }

        file_put_contents($this->manifestPath(), json_encode($manifest, JSON_PRETTY_PRINT));
        return ['ok' => true, 'manifest' => $manifest];
    }

    public function downloadAndVerify(): array
    {
        $manifestPath = $this->manifestPath();
        if (!file_exists($manifestPath)) {
            return ['error' => 'updater.manifest_not_found'];
        }

        $manifest = json_decode(file_get_contents($manifestPath) ?: '', true);
        if (!is_array($manifest)) {
            return ['error' => 'updater.invalid_manifest'];
        }

        $verify = $this->verifyManifest($manifest);
        if ($verify !== true) {
            return $verify;
        }

        $client = new Client(['timeout' => 60]);
        $packagePath = $this->packagePath((string) $manifest['version']);
        $client->get((string) $manifest['url'], ['sink' => $packagePath]);

        $sha256 = hash_file('sha256', $packagePath);
        if (!hash_equals((string) $manifest['sha256'], $sha256)) {
            return ['error' => 'updater.sha_mismatch'];
        }

        return ['ok' => true, 'package' => $packagePath];
    }

    private function verifyManifest(array $manifest): array|bool
    {
        foreach (['version', 'url', 'sha256', 'signature'] as $field) {
            if (empty($manifest[$field])) {
                return ['error' => 'updater.missing_field', 'field' => $field];
            }
        }

        $publicKey = $this->config['public_key'] ?? '';
        if ($publicKey === '') {
            return ['error' => 'updater.public_key_missing'];
        }

        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            return ['error' => 'updater.sodium_missing'];
        }

        $data = $manifest['version'] . '|' . $manifest['url'] . '|' . $manifest['sha256'];
        $signature = base64_decode((string) $manifest['signature']);
        $key = base64_decode($publicKey);

        if ($signature === false || $key === false) {
            return ['error' => 'updater.invalid_base64'];
        }

        $valid = sodium_crypto_sign_verify_detached($signature, $data, $key);
        return $valid ? true : ['error' => 'updater.invalid_signature'];
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
