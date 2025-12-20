<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .certificate-container {
            width: 900px;
            margin: 40px auto;
            background: #fff;
            box-shadow: 0 0 16px rgba(0,0,0,0.08);
            padding: 48px 64px;
            border-radius: 16px;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="certificate-container">
        @yield('content')
    </div>
</body>
</html> 