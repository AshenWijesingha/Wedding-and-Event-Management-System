<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to secure checkout…</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 12px; padding: 40px 48px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; max-width: 420px; }
        .spinner { width: 36px; height: 36px; border: 4px solid #e5e7eb; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 18px; margin: 0 0 8px; }
        p { font-size: 14px; color: #6b7280; margin: 0 0 20px; }
        button { background: #4f46e5; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <h1>Redirecting to secure checkout</h1>
        <p>Please wait while we take you to PayHere. Do not close this window.</p>

        <form id="payhere-form" method="post" action="{{ $action }}">
            @foreach($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <noscript><button type="submit">Continue to payment</button></noscript>
        </form>
    </div>

    <script>document.getElementById('payhere-form').submit();</script>
</body>
</html>
