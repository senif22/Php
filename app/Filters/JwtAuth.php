<?php

namespace App\Filters;

use App\Libraries\JwtService;
use App\Libraries\Permission;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class JwtAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = new JwtService();
        $token = $jwt->tokenFromRequest($request);

        if ($token === null) {
            return $this->unauthorized('Authorization header with a Bearer token is required');
        }

        $payload = $jwt->decode($token);

        if ($payload === null) {
            return $this->unauthorized('Token is invalid or has expired');
        }

        Permission::actAs([
            'id' => $payload['sub'] ?? null,
            'role' => $payload['role'] ?? null,
        ]);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        Permission::actAs(null);

        return null;
    }

    protected function unauthorized(string $message)
    {
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'status' => 401,
                'error' => 'Unauthorized',
                'message' => $message,
            ]);
    }
}
