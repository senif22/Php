<?php

namespace App\Controllers\Api;

use App\Libraries\JwtService;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    protected $format = 'json';

    public function login()
    {
        $input = $this->request->getJSON(true);

        if (! is_array($input)) {
            $input = $this->request->getPost();
        }

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return $this->respond([
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'Both email and password are required',
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $user = (new UserModel())->findByEmail($email);

        if ($user === null || ! password_verify($password, $user['password'])) {
            return $this->respond([
                'status' => 401,
                'error' => 'Unauthorized',
                'message' => 'Invalid email or password',
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $issued = (new JwtService())->issue($user);

        return $this->respond([
            'status' => 200,
            'message' => 'Login successful',
            'data' => [
                'token' => $issued['token'],
                'token_type' => $issued['token_type'],
                'expires_in' => $issued['expires_in'],
                'expires_at' => $issued['expires_at'],
                'user' => [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ],
            ],
        ], ResponseInterface::HTTP_OK);
    }

    public function me()
    {
        $user = (new UserModel())->find(\App\Libraries\Permission::userId());

        if ($user === null) {
            return $this->respond([
                'status' => 404,
                'error' => 'Not Found',
                'message' => 'User not found',
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        unset($user['password']);

        return $this->respond([
            'status' => 200,
            'data' => $user,
        ], ResponseInterface::HTTP_OK);
    }
}
