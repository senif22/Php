<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $customerModel = new CustomerModel();

        $data = [
            'total_customers' => $customerModel->countAllResults(),
            'active_customers' => $customerModel->where('status', 'active')->countAllResults(),
            'recent_customers' => $customerModel->orderBy('created_at', 'DESC')->limit(5)->find()
        ];

        return view('dashboard/index', $data);
    }
}
