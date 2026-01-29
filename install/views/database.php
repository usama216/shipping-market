<h2 class="card-title">Database Configuration</h2>
<p class="card-subtitle">Enter your MySQL database credentials.</p>

<form method="POST" action="?step=database">
    <div class="form-group">
        <label class="form-label" for="app_name">Application Name</label>
        <input type="text" id="app_name" name="app_name" class="form-input"
            value="<?= htmlspecialchars($_POST['app_name'] ?? 'Marketsz') ?>" required>
    </div>

    <div class="form-group">
        <label class="form-label" for="app_url">Application URL</label>
        <input type="url" id="app_url" name="app_url" class="form-input" placeholder="https://yourdomain.com"
            value="<?= htmlspecialchars($_POST['app_url'] ?? '') ?>" required>
    </div>

    <hr style="border: none; border-top: 1px solid var(--border); margin: 1.5rem 0;">

    <div class="form-row">
        <div class="form-group">
            <label class="form-label" for="db_host">Database Host</label>
            <input type="text" id="db_host" name="db_host" class="form-input"
                value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="db_port">Port</label>
            <input type="text" id="db_port" name="db_port" class="form-input"
                value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="db_database">Database Name</label>
        <input type="text" id="db_database" name="db_database" class="form-input"
            value="<?= htmlspecialchars($_POST['db_database'] ?? '') ?>" required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label" for="db_username">Username</label>
            <input type="text" id="db_username" name="db_username" class="form-input"
                value="<?= htmlspecialchars($_POST['db_username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="db_password">Password</label>
            <input type="password" id="db_password" name="db_password" class="form-input">
        </div>
    </div>

    <div class="btn-group">
        <a href="?step=requirements" class="btn" style="background: var(--bg-input);">← Back</a>
        <button type="submit" class="btn btn-primary" style="flex: 1;">
            Configure Database →
        </button>
    </div>
</form>