<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'email',
        'phone',
        'company',
        'city',
        'status',
        'notes',
        'assigned_to'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'id' => 'permit_empty|is_natural_no_zero',
        'name' => 'required|min_length[3]|max_length[255]',
        'email' => 'required|valid_email|max_length[255]|is_unique[customers.email,id,{id}]',
        'phone' => 'permit_empty|min_length[10]|max_length[50]',
        'company' => 'permit_empty|max_length[255]',
        'city' => 'permit_empty|max_length[100]',
        'status' => 'permit_empty|in_list[active,inactive,pending]',
        'assigned_to' => 'permit_empty|is_natural_no_zero',
        'notes' => 'permit_empty|max_length[5000]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Name is required.',
            'min_length' => 'Name must be at least 3 characters long.'
        ],
        'email' => [
            'required' => 'Email is required.',
            'valid_email' => 'Please enter a valid email address.',
            'is_unique' => 'This email is already registered to another customer.'
        ],
        'phone' => [
            'min_length' => 'Phone number must be at least 10 characters long.'
        ],
        'status' => [
            'in_list' => 'Status must be active, inactive or pending.'
        ]
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
}
