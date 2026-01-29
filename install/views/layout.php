<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Marketsz</title>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #475569;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .installer {
            width: 100%;
            max-width: 600px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .steps {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .step-indicator {
            width: 40px;
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            transition: background 0.3s;
        }

        .step-indicator.active {
            background: var(--primary);
        }

        .step-indicator.completed {
            background: var(--success);
        }

        .card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid var(--border);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .requirements-list {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .requirement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .requirement-item:last-child {
            border-bottom: none;
        }

        .requirement-name {
            font-weight: 500;
        }

        .requirement-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .status-pass {
            color: var(--success);
        }

        .status-fail {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-block {
            width: 100%;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            color: var(--success);
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="installer">
        <div class="logo">
            <h1>📦 Marketsz</h1>
        </div>

        <div class="steps">
            <?php
            $steps = ['welcome', 'requirements', 'database', 'dependencies', 'admin', 'complete'];
            $currentIndex = array_search($step, $steps);
            foreach ($steps as $i => $s):
                $class = '';
                if ($i < $currentIndex)
                    $class = 'completed';
                elseif ($i === $currentIndex)
                    $class = 'active';
                ?>
                <div class="step-indicator <?= $class ?>"></div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php include __DIR__ . "/{$step}.php"; ?>
        </div>
    </div>
</body>

</html>