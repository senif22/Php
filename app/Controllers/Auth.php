<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function authenticate()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        if ($email === null || $password === null) {
            return redirect()->back()->withInput()->with('error', 'Email and password are required');
        }

        $user = (new UserModel())->findByEmail($email);

        if ($user === null || ! password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials');
        }

        session()->regenerate();
        session()->set([
            'user_id' => $user['id'],
            'username' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'manager_id' => $user['manager_id'],
            'logged_in' => true
        ]);

        return redirect()->to('/dashboard')->with('success', 'Welcome back, ' . $user['name'] . '!');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Logged out successfully');
    }
}
