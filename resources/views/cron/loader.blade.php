<!DOCTYPE html>
<html>
<head>
    <title>Cron Sync</title>

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 30px;
            width: 400px;
            text-align: center;
        }

        .title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #111827;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .spinner {
            border: 4px solid #e5e7eb;
            border-top: 4px solid #6366f1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        progress {
            width: 100%;
            height: 8px;
            border-radius: 6px;
            overflow: hidden;
        }

        progress::-webkit-progress-bar {
            background-color: #e5e7eb;
        }

        progress::-webkit-progress-value {
            background-color: #6366f1;
        }

        .stats {
            margin-top: 20px;
            text-align: left;
            font-size: 14px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .stat-label {
            color: #6b7280;
        }

        .stat-value {
            font-weight: 600;
            color: #111827;
        }

        .success {
            color: #16a34a;
            font-weight: 600;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 16px;
            background: #6366f1;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .button:hover {
            background: #4f46e5;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="card">

        <div id="loader">
            <div class="spinner"></div>
            <div class="title">Sync in progress</div>
            <div class="subtitle" id="statusText">Starting...</div>

            <progress id="progressBar" value="0" max="100"></progress>
            <div class="subtitle"><span id="progressPercent">0</span>%</div>

            <div class="stats">
                <div class="stat-row">
                    <span class="stat-label">Total</span>
                    <span class="stat-value" id="total">0</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Processed</span>
                    <span class="stat-value" id="processed">0</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Running</span>
                    <span class="stat-value" id="running">0</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Failed</span>
                    <span class="stat-value" id="failed">0</span>
                </div>
            </div>
        </div>

        <div id="result" style="display:none;">
            <div class="title success">Sync completed ✅</div>

            <div class="stats">
                <div class="stat-row">
                    <span class="stat-label">Total</span>
                    <span class="stat-value" id="final_total"></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Processed</span>
                    <span class="stat-value" id="final_processed"></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Failed</span>
                    <span class="stat-value" id="final_failed"></span>
                </div>
            </div>

            <a href="/admin" class="button">Back to Admin</a>
        </div>

    </div>
</div>

<script>
let interval = null;

function checkStatus() {
    fetch('/cron/status')
        .then(res => res.json())
        .then(data => {

            const total = data.total || 0;
            const processed = data.processed || 0;
            const failed = data.failed || 0;
            const running = data.running || 0;

            let progress = total > 0 
                ? Math.round(((processed + failed) / total) * 100) 
                : 0;

            document.getElementById('total').innerText = total;
            document.getElementById('processed').innerText = processed;
            document.getElementById('failed').innerText = failed;
            document.getElementById('running').innerText = running;

            document.getElementById('progressBar').value = progress;
            document.getElementById('progressPercent').innerText = progress;

            if (data.status === 'running') {
                document.getElementById('statusText').innerText = "Processing jobs...";
            }

            if (data.finished) {
                clearInterval(interval);

                document.getElementById('loader').style.display = 'none';
                document.getElementById('result').style.display = 'block';

                document.getElementById('final_total').innerText = total;
                document.getElementById('final_processed').innerText = processed;
                document.getElementById('final_failed').innerText = failed;
            }

        });
}

interval = setInterval(checkStatus, 2000);
checkStatus();
</script>

</body>
</html>