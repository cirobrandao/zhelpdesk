# ZHelpdesk

Helpdesk web (PHP 8.3 + Slim 4) com painel admin e portal do usuario.

## Requisitos

- Docker + Docker Compose (dev)
- PHP 8.3 + Composer (prod)
- MySQL/MariaDB

## Setup local (Docker)

1) Copie o arquivo de ambiente:

```
cp .env.example .env
```

2) Suba os containers:

```
docker compose up -d --build
```

3) Instale dependencias:

```
docker compose exec php composer install
```

4) Rode as migracoes:

```
docker compose exec php php bin/migrate
```

5) Acesse:

- App: http://localhost:8080
- Mailhog: http://localhost:8025

## Setup producao (Nginx + PHP-FPM)

1) Copie o projeto para o servidor.
2) Configure o vhost usando [config/nginx/site.conf](config/nginx/site.conf).
3) Crie o arquivo .env com os valores corretos.
4) Execute:

```
composer install --no-dev --optimize-autoloader
php bin/migrate
```

## Migracoes

- Rodar: `php bin/migrate`
- Arquivos SQL em [sql/migrations](sql/migrations)

## CLI

Criar usuario admin:

```
php bin/console create-user "Admin" admin@example.com "senha" admin
```

Gerar token da API:

```
php bin/console generate-token admin@example.com
```

## Idiomas (i18n)

- Arquivos em [app/i18n](app/i18n)
- Prioridade de locale: preferencia do usuario > query param `lang` > `Accept-Language` > `pt_BR`
- Para adicionar um idioma, crie `app/i18n/<locale>.php`.

## Updater (releases assinadas)

1) Gere um pacote (zip ou tar.gz) e calcule SHA256.
2) Assine o JSON do manifesto com sua chave privada.
3) Publique manifesto e pacote em um endpoint HTTPS.
4) Defina `UPDATE_ENDPOINT` e `APP_PUBLIC_KEY` no .env.
5) No painel admin, use "Check updates" e "Download & Verify".
6) Aplique o update via CLI:

```
php bin/update --apply
```

Manifesto esperado:

```
{
  "version": "1.2.3",
  "url": "https://example.com/releases/zhelpdesk-1.2.3.zip",
  "sha256": "<sha256>",
  "signature": "<base64>"
}
```

A assinatura deve ser gerada sobre a string:

```
version|url|sha256
```

## API (exemplos curl)

Listar tickets:

```
curl -H "Authorization: Bearer <token>" http://localhost:8080/api/tickets
```

Ver ticket:

```
curl -H "Authorization: Bearer <token>" http://localhost:8080/api/tickets/1
```

Responder ticket:

```
curl -X POST -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"message":"Resposta via API"}' \
  http://localhost:8080/api/tickets/1/reply
```

Healthcheck:

```
curl http://localhost:8080/api/health
```
