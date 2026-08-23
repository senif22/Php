<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use App\Models\ActivityModel;
use App\Libraries\Permission;
use App\Models\UserModel;

class Customers extends BaseController
{
    protected $customerModel;
    protected $activityModel;

    public function __construct()
    {
        $this->customerModel = new CustomerModel();
        $this->activityModel = new ActivityModel();
    }

    public function index()
    {
        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');
        $city = $this->request->getGet('city');

        $customers = $this->customerModel
            ->select('customers.*, users.name as assigned_name')
            ->join('users', 'users.id = customers.assigned_to', 'left');

        $visibleIds = Permission::visibleUserIds();

        if ($visibleIds !== null) {
            $customers = $customers->whereIn('customers.assigned_to', $visibleIds);
        }

        if ($search !== null && $search !== '') {
            $customers = $customers->groupStart()
                ->like('customers.name', $search)
                ->orLike('customers.email', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $customers = $customers->where('customers.status', $status);
        }

        $customers = $customers->orderBy('customers.id', 'DESC')->paginate(20);

        $data = [
            'customers' => $customers,
            'pager' => $this->customerModel->pager,
            'search' => $search,
            'status' => $status,
            'city' => $city
        ];

        return view('customers/index', $data);
    }

    public function create()
    {
        if (! Permission::canCreate()) {
            return $this->accessDenied();
        }

        return view('customers/create', [
            'users' => Permission::isAdmin() ? (new UserModel())->orderBy('name')->findAll() : []
        ]);
    }

    public function store()
    {
        if (! Permission::canCreate()) {
            return $this->accessDenied();
        }

        $data = [
            'assigned_to' => Permission::isAdmin()
                ? $this->request->getPost('assigned_to')
                : Permission::userId(),
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'company' => $this->request->getPost('company'),
            'city' => $this->request->getPost('city'),
            'status' => $this->request->getPost('status') ?? 'active',
            'notes' => $this->request->getPost('notes')
        ];

        if ($this->customerModel->insert($data)) {
            // Log activity
            $this->activityModel->insert([
                'customer_id' => $this->customerModel->getInsertID(),
                'action' => 'created',
                'description' => 'Customer created',
                'user_id' => session()->get('user_id')
            ]);

            return redirect()->to('/customers')->with('success', 'Customer created successfully');
        }

        return redirect()->back()->withInput()
            ->with('errors', $this->customerModel->errors())
            ->with('error', 'Failed to create customer');
    }

    public function edit($id)
    {
        $customer = $this->customerModel->find($id);

        if (!$customer) {
            return redirect()->to('/customers')->with('error', 'Customer not found');
        }

        if (! Permission::canEdit($customer)) {
            return $this->accessDenied();
        }

        $data = [
            'customer' => $customer
        ];

        return view('customers/edit', $data);
    }

    public function update($id)
    {
        $customer = $this->customerModel->find($id);

        if (!$customer) {
            return redirect()->to('/customers')->with('error', 'Customer not found');
        }

        if (! Permission::canEdit($customer)) {
            return $this->accessDenied();
        }

        $data = [
            'id' => $id,
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'company' => $this->request->getPost('company'),
            'city' => $this->request->getPost('city'),
            'status' => $this->request->getPost('status'),
            'notes' => $this->request->getPost('notes')
        ];

        if ($this->customerModel->update($id, $data)) {
            // Log activity
            $this->activityModel->insert([
                'customer_id' => $id,
                'action' => 'updated',
                'description' => 'Customer information updated',
                'user_id' => session()->get('user_id')
            ]);

            return redirect()->to('/customers')->with('success', 'Customer updated successfully');
        }

        return redirect()->back()->withInput()
            ->with('errors', $this->customerModel->errors())
            ->with('error', 'Failed to update customer');
    }

    public function delete($id)
    {
        $customer = $this->customerModel->find($id);

        if (!$customer) {
            return redirect()->to('/customers')->with('error', 'Customer not found');
        }

        if (! Permission::canDelete($customer)) {
            return $this->accessDenied();
        }

        $this->customerModel->delete($id);

        return redirect()->to('/customers')->with('success', 'Customer deleted successfully');
    }

    public function view($id)
    {
        $customer = $this->customerModel->find($id);

        if (!$customer) {
            return redirect()->to('/customers')->with('error', 'Customer not found');
        }

        if (! Permission::canView($customer)) {
            return $this->accessDenied();
        }

        $activities = $this->activityModel
            ->where('customer_id', $id)
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->find();

        $data = [
            'customer' => $customer,
            'activities' => $activities
        ];

        return view('customers/view', $data);
    }

    public function export()
    {
        $visibleIds = Permission::visibleUserIds();

        if ($visibleIds !== null) {
            $this->customerModel->whereIn('assigned_to', $visibleIds);
        }

        $customers = $this->customerModel->findAll();

        $filename = 'customers_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Company', 'City', 'Status']);

        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                $customer['company'],
                $customer['city'],
                $customer['status']
            ]);
        }

        fclose($output);
        exit;
    }

    protected function accessDenied()
    {
        return service('response')
            ->setStatusCode(403)
            ->setBody(view('errors/access_denied', [
                'role' => Permission::role(),
                'required' => [],
            ]));
    }
}
