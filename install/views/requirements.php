<h2 class="card-title">System Requirements</h2>
<p class="card-subtitle">Checking your server configuration...</p>

<ul class="requirements-list">
    <?php foreach ($viewData['requirements'] as $req): ?>
        <li class="requirement-item">
            <span class="requirement-name">
                <?= htmlspecialchars($req['name']) ?>
            </span>
            <span class="requirement-status <?= $req['passed'] ? 'status-pass' : 'status-fail' ?>">
                <?php if ($req['passed']): ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                <?php else: ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                <?php endif; ?>
                <?= htmlspecialchars($req['current']) ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($viewData['allPassed']): ?>
    <a href="?step=database" class="btn btn-primary btn-block">
        Continue to Database Setup →
    </a>
<?php else: ?>
    <div class="alert alert-error">
        Please fix the requirements marked in red before continuing.
    </div>
    <a href="?step=requirements" class="btn btn-primary btn-block">
        Re-check Requirements
    </a>
<?php endif; ?>