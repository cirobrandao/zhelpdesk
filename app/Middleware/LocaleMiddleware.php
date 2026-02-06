<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = $_SESSION['user_locale'] ?? null;

        if (!$locale) {
            $queryLocale = $request->getQueryParams()['lang'] ?? null;
            $locale = $queryLocale ?: null;
        }

        if (!$locale) {
            $accept = $request->getHeaderLine('Accept-Language');
            $locale = $this->parseAcceptLanguage($accept);
        }

        if (!$locale) {
            $locale = $this->config['default_locale'] ?? 'pt_BR';
        }

        $_SESSION['locale'] = $locale;

        return $handler->handle($request->withAttribute('locale', $locale));
    }

    private function parseAcceptLanguage(string $header): ?string
    {
        if ($header === '') {
            return null;
        }
        $parts = explode(',', $header);
        if (count($parts) === 0) {
            return null;
        }
        $primary = trim($parts[0]);
        return $primary !== '' ? str_replace('-', '_', $primary) : null;
    }
}
