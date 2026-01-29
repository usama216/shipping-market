<h2 class="card-title">Installing Dependencies</h2>
<p class="card-subtitle">Setting up PHP packages and database tables...</p>

<div id="install-progress" style="margin-bottom: 1.5rem;">
    <div
        style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg-input); border-radius: 8px; margin-bottom: 1rem;">
        <div class="spinner"
            style="width: 24px; height: 24px; border: 3px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;">
        </div>
        <div>
            <p style="font-weight: 500; margin: 0;">Preparing installation...</p>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">This may take 1-3 minutes depending on
                your server.</p>
        </div>
    </div>

    <div id="progress-steps" style="font-size: 0.875rem; color: var(--text-muted);">
        <div class="progress-step" data-step="composer"
            style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
            <span class="step-icon">⏳</span>
            <span>Downloading & installing Composer packages</span>
        </div>
        <div class="progress-step" data-step="migrations"
            style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
            <span class="step-icon">⏳</span>
            <span>Running database migrations</span>
        </div>
        <div class="progress-step" data-step="seeding"
            style="padding: 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
            <span class="step-icon">⏳</span>
            <span>Seeding default data</span>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<form method="POST" action="?step=dependencies" id="dependencies-form">
    <button type="submit" class="btn btn-primary btn-block" id="install-btn">
        <span id="btn-text">Install Dependencies</span>
        <span id="btn-loading" style="display: none;">
            <span class="spinner"
                style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; vertical-align: middle; margin-right: 0.5rem;"></span>
            Installing...
        </span>
    </button>
</form>

<p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem; text-align: center;">
    ⚠️ Do not close this page during installation.
</p>

<script>
    document.getElementById('dependencies-form').addEventListener('submit', function (e) {
        document.getElementById('btn-text').style.display = 'none';
        document.getElementById('btn-loading').style.display = 'inline';
        document.getElementById('install-btn').disabled = true;

        // Update progress indicators
        var steps = document.querySelectorAll('.progress-step .step-icon');
        steps[0].textContent = '🔄';
    });
</script>