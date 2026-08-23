<?php

namespace App\Libraries;

use Config\Jwt as JwtConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JwtService
{
    protected JwtConfig $config;

    public function __construct(?JwtConfig $config = null)
    {
        $this->config = $config ?? config(JwtConfig::class);

        if ($this->config->secret === '') {
            throw new \RuntimeException('jwt.secret is not set in .env');
        }
    }

    public function issue(array $user): array
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->config->ttl;

        $payload = [
            'iss' => $this->config->issuer,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'manager_id' => $user['manager_id'] === null ? null : (int) $user['manager_id'],
        ];

        return [
            'token' => JWT::encode($payload, $this->config->secret, $this->config->algorithm),
            'token_type' => 'Bearer',
            'expires_in' => $this->config->ttl,
            'expires_at' => date('c', $expiresAt),
        ];
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->secret, $this->config->algorithm));
        } catch (Throwable $e) {
            return null;
        }

        return (array) $decoded;
    }

    public function tokenFromRequest($request): ?string
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token === '' ? null : $token;
    }
}
