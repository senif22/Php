<?php

namespace App\Filters;

use App\Libraries\Permission;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleCheck implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = Permission::role();

        if ($role === null) {
            return redirect()->to('/login')->with('error', 'Please login to access this page');
        }

        if ($arguments === null || $arguments === []) {
            return null;
        }

        if (! in_array($role, $arguments, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/access_denied', [
                    'required' => $arguments,
                    'role' => $role,
                ]));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
