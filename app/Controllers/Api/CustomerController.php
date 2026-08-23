<?php

namespace App\Controllers\Api;

use App\Libraries\Permission;
use App\Models\ActivityModel;
use App\Models\CustomerModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class CustomerController extends ResourceController
{
    protected $format = 'json';

    protected const SORTABLE = ['id', 'name', 'email', 'company', 'city', 'status', 'created_at', 'updated_at'];

    protected const PER_PAGE_MAX = 100;

    public function index()
    {
        $model = new CustomerModel();

        $visibleIds = Permission::visibleUserIds();

        if ($visibleIds !== null) {
            $model->whereIn('assigned_to', $visibleIds);
        }

        foreach (['status', 'city', 'company'] as $field) {
            $value = $this->request->getGet($field);

            if ($value !== null && $value !== '') {
                $model->where($field, $value);
            }
        }

        $search = $this->request->getGet('search');

        if ($search !== null && $search !== '') {
            $model->groupStart()->like('name', $search)->orLike('email', $search)->groupEnd();
        }

        $sort = $this->request->getGet('sort');
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'id';

        $order = strtolower((string) $this->request->getGet('order')) === 'asc' ? 'asc' : 'desc';

        $perPage = (int) ($this->request->getGet('per_page') ?? 20);
        $perPage = max(1, min($perPage, self::PER_PAGE_MAX));

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        $customers = $model->orderBy($sort, $order)->paginate($perPage, 'default', $page);
        $pager = $model->pager;

        return $this->respond([
            'status' => 200,
            'data' => $customers,
            'meta' => [
                'page' => $pager->getCurrentPage(),
                'per_page' => $perPage,
                'total' => $pager->getTotal(),
                'total_pages' => $pager->getPageCount(),
                'sort' => $sort,
                'order' => $order,
            ],
        ], ResponseInterface::HTTP_OK);
    }

    public function show($id = null)
    {
        $customer = (new CustomerModel())->find($id);

        if ($customer === null) {
            return $this->notFound();
        }

        if (! Permission::canView($customer)) {
            return $this->forbidden();
        }

        return $this->respond([
            'status' => 200,
            'data' => $customer,
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        if (! Permission::canCreate()) {
            return $this->forbidden();
        }

        $input = $this->input();
        $model = new CustomerModel();

        $data = $this->fields($input);

        if (! isset($data['name'], $data['email'])) {
            return $this->validationFailed([
                'name' => 'Name is required.',
                'email' => 'Email is required.',
            ]);
        }

        $data['assigned_to'] = Permission::isAdmin()
            ? ($input['assigned_to'] ?? null)
            : Permission::userId();

        if ($model->insert($data) === false) {
            return $this->validationFailed($model->errors());
        }

        $id = $model->getInsertID();

        (new ActivityModel())->insert([
            'customer_id' => $id,
            'action' => 'created',
            'description' => 'Customer created via API',
            'user_id' => Permission::userId(),
        ]);

        return $this->respond([
            'status' => 201,
            'message' => 'Customer created',
            'data' => $model->find($id),
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        $model = new CustomerModel();
        $customer = $model->find($id);

        if ($customer === null) {
            return $this->notFound();
        }

        if (! Permission::canEdit($customer)) {
            return $this->forbidden();
        }

        $input = $this->input();
        $data = $this->fields($input);

        if ($data === []) {
            return $this->respond([
                'status' => 400,
                'error' => 'Bad Request',
                'message' => 'No updatable fields were provided',
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $data['id'] = (int) $id;

        if (Permission::isAdmin() && array_key_exists('assigned_to', $input)) {
            $data['assigned_to'] = $input['assigned_to'];
        }

        if ($model->update($id, $data) === false) {
            return $this->validationFailed($model->errors());
        }

        (new ActivityModel())->insert([
            'customer_id' => $id,
            'action' => 'updated',
            'description' => 'Customer updated via API',
            'user_id' => Permission::userId(),
        ]);

        return $this->respond([
            'status' => 200,
            'message' => 'Customer updated',
            'data' => $model->find($id),
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        $model = new CustomerModel();
        $customer = $model->find($id);

        if ($customer === null) {
            return $this->notFound();
        }

        if (! Permission::canDelete($customer)) {
            return $this->forbidden();
        }

        $model->delete($id);

        return $this->respond([
            'status' => 200,
            'message' => 'Customer deleted',
            'data' => ['id' => (int) $id],
        ], ResponseInterface::HTTP_OK);
    }

    protected function input(): array
    {
        $input = $this->request->getJSON(true);

        if (! is_array($input)) {
            $input = $this->request->getRawInput();
        }

        return is_array($input) ? $input : [];
    }

    protected function fields(array $input): array
    {
        $allowed = ['name', 'email', 'phone', 'company', 'city', 'status', 'notes'];
        $data = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $input) && $input[$field] !== null) {
                $data[$field] = $input[$field];
            }
        }

        return $data;
    }

    protected function notFound()
    {
        return $this->respond([
            'status' => 404,
            'error' => 'Not Found',
            'message' => 'Customer not found',
        ], ResponseInterface::HTTP_NOT_FOUND);
    }

    protected function forbidden()
    {
        return $this->respond([
            'status' => 403,
            'error' => 'Forbidden',
            'message' => 'You do not have permission to access this customer',
        ], ResponseInterface::HTTP_FORBIDDEN);
    }

    protected function validationFailed(array $errors)
    {
        return $this->respond([
            'status' => 400,
            'error' => 'Bad Request',
            'message' => 'Validation failed',
            'errors' => $errors,
        ], ResponseInterface::HTTP_BAD_REQUEST);
    }
}
