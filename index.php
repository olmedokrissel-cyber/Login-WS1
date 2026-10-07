<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Portal!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('BG.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            padding: 20px;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            text-align: center;
            color: #ffffff;
            max-width: 480px;
            width: 100%;
        }

        .logo {
            width: 90px;
            height: 90px;
            margin-bottom: 20px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .welcome-card h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .welcome-card > p {
            font-size: 1.20rem;
            margin-bottom: 25px;
            color: #e0e0e0;
            line-height: 1.4;
        }

        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 25px;
        }

        .btn {
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-login {
            background-color: #4CAF50;
            color: white;
        }

        .btn-login:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .btn-register {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-register:hover {
            background-color: white;
            color: #333;
            transform: translateY(-2px);
        }

        .demo-accounts-box {
            margin-top: 10px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            text-align: left;
        }

        .demo-accounts-box h2 {
            font-size: 1rem;
            margin-bottom: 12px;
            color: #ffffff;
            text-align: center;
        }

        .demo-accounts-box p {
            font-size: 1.30rem;
            margin-bottom: 8px;
            color: #f1f5f9;
            background: rgba(0, 0, 0, 0.25);
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #4CAF50;
            word-break: break-word;
            letter-spacing: normal;
        }
    </style>
</head>
<body>

    <div class="welcome-card">
        <img src="logo.png" alt="Portal Logo" class="logo">
        <h1>Welcome Back!😊</h1>
        <p>Access your Student, Teacher, or Principal Dashboard by logging in below.</p>
        
       
        <div class="btn-container">
            <a href="login.php" class="btn btn-login">Log In</a>
            <a href="register.php" class="btn btn-register">Register</a>
        </div> 

        <div class="demo-accounts-box">
            <h2>Demo Accounts:</h2>
            <p><strong>Admin:</strong> admin / admin@example.com <br> Password: admin123</p>
            <p><strong>Principal:</strong> cute / catalbas@gmail.com <br> Password: 123cute</p>
            <p><strong>Teacher:</strong> jam / noman@gmail.com <br> Password: jam123</p>
            <p><strong>Student:</strong> kmoa / krisolmss@gmail.com <br> Password: 67890</p>
        </div>
    </div>

</body>
</html>