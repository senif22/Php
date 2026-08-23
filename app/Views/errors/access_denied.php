<?= $this->include('layout/header') ?>

<div class="card border-danger">
    <div class="card-body text-center py-5">
        <i class="bi bi-shield-lock text-danger" style="font-size: 3rem;"></i>
        <h3 class="mt-3 text-danger">Access Denied</h3>
        <p class="text-muted mb-1">You do not have permission to perform this action.</p>
        <p class="text-muted">
            Your role: <strong><?= esc($role ?? 'unknown') ?></strong>
            <?php if (! empty($required)): ?>
                &mdash; required: <strong><?= esc(implode(', ', (array) $required)) ?></strong>
            <?php endif; ?>
        </p>
        <a href="<?= base_url('customers') ?>" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left"></i> Back to Customers
        </a>
    </div>
</div>

<?= $this->include('layout/footer') ?>
