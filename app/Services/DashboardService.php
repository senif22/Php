<?php

namespace App\Services;

use App\Libraries\Permission;
use CodeIgniter\Database\BaseBuilder;
use Config\Database;

class DashboardService
{
    public const CACHE_TTL = 3600;

    protected const GROWTH_MONTHS = 6;

    protected const TOP_CITIES = 5;

    protected $db;

    protected ?array $scope;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->scope = Permission::visibleUserIds();
    }

    public function cacheKey(): string
    {
        $suffix = $this->scope === null ? 'all' : implode('-', $this->scope);

        return 'dashboard_' . $suffix;
    }

    public function clearCache(): void
    {
        cache()->delete($this->cacheKey());
    }

    public function data(): array
    {
        $cached = cache($this->cacheKey());

        if (is_array($cached)) {
            $cached['from_cache'] = true;

            return $cached;
        }

        $data = [
            'summary' => $this->summary(),
            'growth' => $this->growth(),
            'statusDistribution' => $this->statusDistribution(),
            'topCities' => $this->topCities(),
            'recentActivities' => $this->recentActivities(),
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        cache()->save($this->cacheKey(), $data, self::CACHE_TTL);

        $data['from_cache'] = false;

        return $data;
    }

    protected function customers(): BaseBuilder
    {
        $builder = $this->db->table('customers');

        if ($this->scope !== null) {
            $builder->whereIn('assigned_to', $this->scope);
        }

        return $builder;
    }

    protected function summary(): array
    {
        $row = $this->customers()
            ->select("COUNT(*) AS total", false)
            ->select("SUM(status = 'active') AS active", false)
            ->select("SUM(status = 'inactive') AS inactive", false)
            ->select("SUM(status = 'pending') AS pending", false)
            ->select("SUM(created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS new_this_month", false)
            ->get()
            ->getRowArray();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'new_this_month' => (int) ($row['new_this_month'] ?? 0),
        ];
    }

    protected function growth(): array
    {
        $rows = $this->customers()
            ->select("DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total", false)
            ->where('created_at >=', date('Y-m-01', strtotime('-' . (self::GROWTH_MONTHS - 1) . ' months')))
            ->groupBy('ym')
            ->orderBy('ym', 'ASC')
            ->get()
            ->getResultArray();

        $counts = array_column($rows, 'total', 'ym');

        $labels = [];
        $values = [];

        for ($i = self::GROWTH_MONTHS - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime('-' . $i . ' months'));
            $labels[] = date('M Y', strtotime($month . '-01'));
            $values[] = (int) ($counts[$month] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function statusDistribution(): array
    {
        $rows = $this->customers()
            ->select('status, COUNT(*) AS total', false)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $counts = array_column($rows, 'total', 'status');
        $total = array_sum($counts);

        $out = [];

        foreach (['active', 'inactive', 'pending'] as $status) {
            $count = (int) ($counts[$status] ?? 0);

            $out[] = [
                'status' => $status,
                'count' => $count,
                'percent' => $total > 0 ? round($count * 100 / $total, 1) : 0.0,
            ];
        }

        return $out;
    }

    protected function topCities(): array
    {
        $rows = $this->customers()
            ->select('city, COUNT(*) AS total', false)
            ->where('city IS NOT NULL', null, false)
            ->where("city !=", '')
            ->groupBy('city')
            ->orderBy('total', 'DESC')
            ->orderBy('city', 'ASC')
            ->limit(self::TOP_CITIES)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $r): array => [
            'city' => $r['city'],
            'count' => (int) $r['total'],
        ], $rows);
    }

    protected function recentActivities(): array
    {
        $builder = $this->db->table('customer_activities a')
            ->select('a.action, a.description, a.created_at, c.id AS customer_id, c.name AS customer_name', false)
            ->join('customers c', 'c.id = a.customer_id')
            ->orderBy('a.created_at', 'DESC')
            ->orderBy('a.id', 'DESC')
            ->limit(10);

        if ($this->scope !== null) {
            $builder->whereIn('c.assigned_to', $this->scope);
        }

        return $builder->get()->getResultArray();
    }
}
