<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private string $storagePath;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(string $storagePath, int $maxAttempts, int $windowSeconds)
    {
        $this->storagePath = $storagePath;
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = sha1($ip . $request->getUri()->getPath());
        $file = rtrim($this->storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.json';

        $data = ['count' => 0, 'reset' => time() + $this->windowSeconds];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $decoded = json_decode($content ?: '', true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (time() > (int) $data['reset']) {
            $data = ['count' => 0, 'reset' => time() + $this->windowSeconds];
        }

        $data['count']++;
        file_put_contents($file, json_encode($data));

        if ($data['count'] > $this->maxAttempts) {
            $response = new Response(429);
            $response->getBody()->write('Too many attempts');
            return $response;
        }

        return $handler->handle($request);
    }
}
