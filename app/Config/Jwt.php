<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    public string $secret = '';

    public string $algorithm = 'HS256';

    public int $ttl = 3600;

    public string $issuer = 'legacy-crm';

    public function __construct()
    {
        parent::__construct();

        $this->secret = (string) (env('jwt.secret') ?: '');
    }
}
