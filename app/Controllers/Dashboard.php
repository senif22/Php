<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use App\Libraries\Permission;

class Dashboard extends BaseController
{
    public function index()
    {
        $visibleIds = Permission::visibleUserIds();

        $data = [
            'total_customers' => $this->scoped($visibleIds)->countAllResults(),
            'active_customers' => $this->scoped($visibleIds)->where('status', 'active')->countAllResults(),
            'recent_customers' => $this->scoped($visibleIds)->orderBy('created_at', 'DESC')->limit(5)->find()
        ];

        return view('dashboard/index', $data);
    }

    protected function scoped(?array $visibleIds): CustomerModel
    {
        $model = new CustomerModel();

        if ($visibleIds !== null) {
            $model->whereIn('assigned_to', $visibleIds);
        }

        return $model;
    }
}
