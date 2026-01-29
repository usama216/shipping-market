<h2 class="card-title">Create Admin Account</h2>
<p class="card-subtitle">Set up your super admin account to manage the platform.</p>

<form method="POST" action="?step=admin">
    <div class="form-group">
        <label class="form-label" for="admin_name">Full Name</label>
        <input type="text" id="admin_name" name="admin_name" class="form-input"
            value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label class="form-label" for="admin_email">Email Address</label>
        <input type="email" id="admin_email" name="admin_email" class="form-input"
            value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label" for="admin_password">Password</label>
            <input type="password" id="admin_password" name="admin_password" class="form-input" minlength="8" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="admin_password_confirm">Confirm Password</label>
            <input type="password" id="admin_password_confirm" name="admin_password_confirm" class="form-input"
                minlength="8" required>
        </div>
    </div>

    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        Password must be at least 8 characters long.
    </p>

    <button type="submit" class="btn btn-primary btn-block">
        Create Admin & Complete Installation →
    </button>
</form>