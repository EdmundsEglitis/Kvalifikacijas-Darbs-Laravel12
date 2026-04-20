<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Updating Database</title>

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            padding: 36px 32px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-top: 14px;
            line-height: 1.5;
        }

        .spinner {
            width: 42px;
            height: 42px;
            margin: 18px auto 0;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">Updating database...</div>
        <div class="spinner"></div>
        <div class="subtitle">You can close this tab, the update is in progress.</div>
    </div>
</body>
</html>