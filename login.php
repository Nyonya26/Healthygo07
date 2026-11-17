
<?php
session_start();

// Jika sudah login, langsung masuk dashboard
if (isset($_SESSION["username"])) {
    header("Location: index.php?dashboard=true");
    exit;
}

$error = "";

// Proses ketika tombol login ditekan
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Email dan password wajib diisi!";
    } else {

        // Simpan username ke session
        session_regenerate_id(true);
        $_SESSION["username"] = $email;

        // Masuk ke dashboard HealthyGo
        header("Location: index.php?dashboard=true");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login HealthyGo</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff00b;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .auth-container {
            display: block !important;
            width: 100%;
            max-width: 400px;
            padding: 35px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            text-align: center;
        }

        .auth-container h2 {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .auth-container h3 {
            margin-bottom: 10px;
        }

        .auth-container p {
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .auth-container form {
            display: flex !important;
            flex-direction: column !important;
            width: 100%;
            gap: 10px;
            text-align: left;
        }

        .auth-container label {
            display: block;
            font-weight: bold;
        }

        .auth-container input {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .auth-container button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background: #f1d900;
            color: #111;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .auth-container a {
            display: inline-block;
            margin-top: 18px;
            color: #333;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="auth-container">

        <h2>HealthyGo 💛</h2>

        <h3>Selamat Datang!</h3>

        <p>Silakan login untuk melanjutkan olahraga.</p>

        <?php if ($error !== ""): ?>
            <p style="color:red;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label>Email</label>
            <input type="email"
                   name="email"
                   placeholder="Masukkan email"
                   required>

            <label>Password</label>
            <input type="password"
                   name="password"
                   placeholder="Masukkan password"
                   required>

            <button type="submit">Login</button>

        </form>

        <a href="index.php">Kembali ke Beranda</a>

    </div>

</body>
</html>
