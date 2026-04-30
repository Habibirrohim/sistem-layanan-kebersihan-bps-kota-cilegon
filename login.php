<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verify password
            if (md5($password) === $user['password']) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Email atau password salah!';
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Monitoring Kebersihan</title>
    <link rel="icon" type="image/jpeg" href="assets/images/bps3.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Background pakai gambar */
        .gradient-bg {
            background-image: url("assets/images/bg1.png");
            /* ganti sesuai nama file gambar */
            background-size: cover;
            /* supaya full layar */
            background-position: center;
            /* posisi gambar di tengah */
            background-repeat: no-repeat;
            /* tidak diulang */
        }

        /* Glass morphism effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }

        /* Logo styling */
        .logo-enhanced {
            filter: brightness(1.1) contrast(1.1) saturate(1.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .logo-enhanced:hover {
            transform: scale(1.1) rotate(5deg);
            filter: brightness(1.3) contrast(1.3) saturate(1.4);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.5);
        }

        /* Input field enhancements */
        .input-enhanced {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(59, 130, 246, 0.1);
        }

        .input-enhanced:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        /* Button enhancements */
        .btn-enhanced {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-enhanced:hover::before {
            left: 100%;
        }

        .btn-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        /* Floating particles */
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 80%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 40%;
            left: 5%;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 85%;
            animation-delay: 6s;
        }

        /* Error alert enhancement */
        .alert-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #ef4444;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Demo account styling */
        .demo-account {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
            border: 1px solid rgba(156, 163, 175, 0.2);
        }

        /* Title animation */
        .title-animated {
            background: linear-gradient(135deg, #1f2937, #374151, #1f2937);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleShine 3s ease-in-out infinite;
        }

        @keyframes titleShine {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>

<body class="gradient-bg min-h-screen relative overflow-hidden">
    <!-- Floating particles -->
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 relative z-10">
        <div class="max-w-md w-full space-y-8">
            <!-- Login Card -->
            <div class="glass-card rounded-2xl p-8 space-y-6">
                <!-- Header Section -->
                <div class="text-center">
                    <div class="flex justify-center mb-6">
                        <img src="assets/images/selasih.png"
                            alt="Logo BPS"
                            class="w-64 h-auto object-contain animate-floating">
                    </div>
                    <style>
                        @keyframes floating {
                            0%,
                            100% {
                                transform: translateY(0px);
                            }
                            50% {
                                transform: translateY(-12px);
                            }
                        }
                        .animate-floating {
                            animation: floating 3s ease-in-out infinite;
                        }
                    </style>

                    <h1 class="text-2xl font-bold title-animated mb-2">BPS KOTA CILEGON</h1>
                    <p class="text-gray-500 text-sm mt-2">Silakan masukkan kredensial Anda untuk melanjutkan</p>
                </div>

                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="alert-error border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form class="space-y-6" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-500"></i>Email Address
                            </label>
                            <input type="email" name="email" required
                                class="input-enhanced w-full px-4 py-3 rounded-xl focus:outline-none"
                                placeholder="Masukkan email Anda">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2 text-blue-500"></i>Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                    class="input-enhanced w-full px-4 py-3 rounded-xl focus:outline-none pr-12"
                                    placeholder="Masukkan password Anda">
                                <button type="button" onclick="togglePassword()"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="login"
                        class="btn-enhanced w-full py-3 px-6 rounded-xl text-white font-semibold text-lg relative">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Masuk ke Sistem
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center text-white/100 text-sm">
                <p>Hak Cipta &copy; <?php echo date('Y'); ?> Badan Pusat Statistik Kota Cilegon. Sistem Monitoring Kebersihan Kantor</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Auto-hide error after 5 seconds
        setTimeout(function() {
            const errorAlert = document.querySelector('.alert-error');
            if (errorAlert) {
                errorAlert.style.animation = 'slideOut 0.5s ease-out';
                setTimeout(() => errorAlert.remove(), 500);
            }
        }, 5000);

        // Add keyframes for slideOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(-10px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>