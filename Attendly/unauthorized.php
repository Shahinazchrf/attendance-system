<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Attendly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 20px;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 50px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            max-width: 500px;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #e74c3c;
        }
        h1 { margin-bottom: 20px; font-size: 2rem; }
        p { margin-bottom: 30px; font-size: 1.1rem; opacity: 0.9; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-ban"></i>
        </div>
        <h1>Access Denied</h1>
        <p>You don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
        <a href="javascript:history.back()" class="btn">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>
        <a href="index.php" class="btn" style="margin-left: 10px;">
            <i class="fas fa-home"></i> Home Page
        </a>
    </div>
</body>
</html>