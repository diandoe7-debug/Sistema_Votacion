<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// RUTA ABSOLUTA
require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    // Validaciones
    if (empty($nombre) || empty($correo) || empty($contrasena)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($contrasena != $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($contrasena) < 4) {
        $error = "La contraseña debe tener al menos 4 caracteres.";
    } else {
        try {
            // Verificar si el correo ya existe
            $query = "SELECT id FROM usuarios WHERE correo = :correo";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':correo', $correo);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "El correo electrónico ya está registrado.";
            } else {
                // Insertar nuevo jurado
                $query = "INSERT INTO usuarios (nombre, correo, contraseña, rol) VALUES (:nombre, :correo, :contrasena, 'jurado')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':correo', $correo);
                $stmt->bindParam(':contrasena', $contrasena);
                
                if ($stmt->execute()) {
                    // Redirigir a la lista de jurados después de agregar
                    header("Location: jurados.php?success=1");
                    exit();
                } else {
                    $error = "Error al agregar el jurado.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
}

// Mostrar mensaje de éxito si viene de redirección
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Jurado agregado exitosamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Jurado - Sistema de Votación</title>
    <style>
        :root {
            --bg-day: url('./../../assets/img/1.jpg');
            --bg-night: url('./../../assets/img/2.jpg');
            --text-color-day: #000000;
            --text-color-night: #ffffff;
            --text-bright-night: #f8fafc;
            --text-super-bright: #ffffff;
            --primary-day: #007bff;
            --secondary-day: #28a745;
            --warning-day: #ffc107;
            --danger-day: #dc3545;
            --info-day: #17a2b8;
            --primary-night: #06b6d4;
            --secondary-night: #8b5cf6;
            --glass-bg-day: rgba(255, 255, 255, 0.95);
            --glass-bg-night: rgba(15, 23, 42, 0.85);
            --glass-border-day: rgba(255, 255, 255, 0.2);
            --glass-border-night: rgba(255, 255, 255, 0.15);
            --card-bg-day: rgba(255, 255, 255, 0.92);
            --card-bg-night: rgba(30, 41, 59, 0.85);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 20px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.day-mode {
            background: var(--bg-day) center/cover no-repeat, linear-gradient(135deg, var(--primary-day), var(--secondary-day));
            color: var(--text-color-day);
        }

        body.night-mode {
            background: var(--bg-night) center/cover no-repeat, linear-gradient(135deg, var(--primary-night), var(--secondary-night));
            color: var(--text-super-bright);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
            border-radius: 50px;
            padding: 10px;
            cursor: pointer;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            z-index: 1000;
        }

        .night-mode .theme-toggle {
            background: var(--glass-bg-night);
            border: 1px solid var(--glass-border-night);
            color: var(--text-super-bright);
        }
        
        .container {
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            padding: 30px;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            border-bottom: none;
            transition: all 0.4s ease;
            text-align: center;
        }

        .day-mode .header {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .header {
            background: var(--glass-bg-night);
            border-color: var(--glass-border-night);
        }
        
        .header h1 {
            margin-bottom: 10px;
            font-size: 2.2rem;
            transition: all 0.4s ease;
        }

        .day-mode .header h1 {
            background: linear-gradient(135deg, var(--primary-day), var(--secondary-day));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .night-mode .header h1 {
            background: linear-gradient(135deg, var(--primary-night), var(--secondary-night));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.4s ease;
        }

        .day-mode .header p {
            color: var(--text-color-day);
        }

        .night-mode .header p {
            color: var(--text-super-bright);
        }
        
        .form-container {
            padding: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            transition: all 0.4s ease;
        }

        .day-mode .form-container {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .form-container {
            background: var(--glass-bg-night);
            border-color: var(--glass-border-night);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.4s ease;
        }

        .day-mode label {
            color: var(--text-color-day);
        }

        .night-mode label {
            color: var(--text-super-bright);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }

        .day-mode input[type="text"],
        .day-mode input[type="email"],
        .day-mode input[type="password"] {
            border-color: #e9ecef;
            background: rgba(255,255,255,0.9);
            color: var(--text-color-day);
        }

        .night-mode input[type="text"],
        .night-mode input[type="email"],
        .night-mode input[type="password"] {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-super-bright);
        }
        
        input:focus {
            outline: none;
            transform: translateY(-2px);
        }

        .day-mode input:focus {
            border-color: var(--primary-day);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
            background: white;
        }

        .night-mode input:focus {
            border-color: var(--primary-night);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            color: #000000;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-family: Arial, sans-serif;
        }

        .day-mode .btn {
            color: #000000;
        }

        .night-mode .btn {
            color: #ffffff;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-day), #0056b3);
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--secondary-day), #1e7e34);
            box-shadow: 0 4px 15px rgba(40,167,69,0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 6px 20px rgba(40,167,69,0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-day), #e0a800);
            box-shadow: 0 4px 15px rgba(255,193,7,0.3);
        }
        
        .btn-warning:hover {
            box-shadow: 0 6px 20px rgba(255,193,7,0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            box-shadow: 0 4px 15px rgba(108,117,125,0.3);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 6px 20px rgba(108,117,125,0.4);
        }
        
        .error {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid var(--danger-day);
            box-shadow: 0 4px 15px rgba(220,53,69,0.1);
            transition: all 0.4s ease;
            font-weight: 500;
        }

        .day-mode .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .night-mode .error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.25), rgba(245, 198, 203, 0.25));
            color: #ffb8b8;
        }
        
        .success {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid var(--secondary-day);
            box-shadow: 0 4px 15px rgba(40,167,69,0.1);
            transition: all 0.4s ease;
        }

        .day-mode .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .night-mode .success {
            background: linear-gradient(135deg, #0d4a1f, #093318);
            color: #bbf7d0;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .left-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .right-actions {
            display: flex;
            gap: 10px;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
            transition: all 0.4s ease;
        }

        .day-mode .password-strength {
            color: #6c757d;
        }

        .night-mode .password-strength {
            color: #cbd5e1;
        }
        
        .form-note {
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid var(--primary-day);
            transition: all 0.4s ease;
        }

        .day-mode .form-note {
            background: #e7f3ff;
        }

        .night-mode .form-note {
            background: rgba(6, 182, 212, 0.1);
            border-left-color: var(--primary-night);
        }

        .form-note h4 {
            margin-bottom: 5px;
            transition: all 0.4s ease;
        }

        .day-mode .form-note h4 {
            color: #0056b3;
        }

        .night-mode .form-note h4 {
            color: var(--text-super-bright);
        }

        .form-note p {
            font-size: 0.9rem;
            margin: 0;
            transition: all 0.4s ease;
        }

        .day-mode .form-note p {
            color: #495057;
        }

        .night-mode .form-note p {
            color: var(--text-super-bright);
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .left-actions, .right-actions {
                justify-content: center;
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
                min-width: 140px;
            }
        }
        
        /* Animaciones */
        @keyframes slideIn {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .form-container {
            animation: slideIn 0.6s ease-out;
        }

        /* Mejorar visibilidad de textos en modo noche */
        .night-mode {
            color: var(--text-super-bright) !important;
        }

        .night-mode strong {
            color: var(--text-super-bright);
        }

        .night-mode input::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }
    </style>
</head>
<body class="night-mode">
    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    
    <div class="container">
        <div class="header">
            <h1>🧑‍⚖️ Agregar Nuevo Jurado</h1>
            <p>Complete los datos del nuevo usuario jurado del sistema</p>
        </div>

        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="juradoForm">
                <div class="form-group">
                    <label for="nombre">👤 Nombre Completo:</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" 
                           placeholder="Ingrese el nombre completo del jurado" required>
                </div>

                <div class="form-group">
                    <label for="correo">📧 Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" 
                           value="<?php echo isset($correo) ? htmlspecialchars($correo) : ''; ?>" 
                           placeholder="correo@ejemplo.com" required>
                </div>

                <div class="form-group">
                    <label for="contrasena">🔒 Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" 
                           placeholder="Mínimo 4 caracteres" required
                           oninput="checkPasswordStrength(this.value)">
                    <div class="password-strength" id="passwordStrength">
                        🔓 La contraseña debe tener al menos 4 caracteres
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmar_contrasena">✅ Confirmar Contraseña:</label>
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" 
                           placeholder="Repita la contraseña" required
                           oninput="checkPasswordMatch()">
                    <div class="password-strength" id="passwordMatch">
                        🔄 Las contraseñas deben coincidir
                    </div>
                </div>

                <div class="form-note">
                    <h4>📋 Información importante:</h4>
                    <p>• El jurado recibirá el rol automáticamente<br>
                       • Podrá acceder al sistema con su correo y contraseña<br>
                       • Podrá votar en los eventos activos asignados</p>
                </div>

                <div class="form-actions">
                    <div class="left-actions">
                        <a href="dashboard.php" class="btn btn-secondary">
                            🏠 Panel Principal
                        </a>
                        <a href="jurados.php" class="btn btn-secondary">
                            📋 Lista de Jurados
                        </a>
                    </div>
                    <div class="right-actions">
                        <button type="submit" class="btn btn-success">
                            ✅ Agregar Jurado
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const body = document.body;
            const themeToggle = document.querySelector('.theme-toggle');
            
            if (body.classList.contains('day-mode')) {
                body.classList.remove('day-mode');
                body.classList.add('night-mode');
                themeToggle.innerHTML = '☀️';
            } else {
                body.classList.remove('night-mode');
                body.classList.add('day-mode');
                themeToggle.innerHTML = '🌙';
            }
        }

        function checkPasswordStrength(password) {
            const strengthElement = document.getElementById('passwordStrength');
            if (password.length === 0) {
                strengthElement.innerHTML = '🔓 La contraseña debe tener al menos 4 caracteres';
                if (document.body.classList.contains('day-mode')) {
                    strengthElement.style.color = '#6c757d';
                } else {
                    strengthElement.style.color = '#cbd5e1';
                }
            } else if (password.length < 4) {
                strengthElement.innerHTML = '❌ Muy corta (mínimo 4 caracteres)';
                strengthElement.style.color = '#dc3545';
            } else if (password.length < 6) {
                strengthElement.innerHTML = '⚠️ Contraseña aceptable';
                strengthElement.style.color = '#ffc107';
            } else {
                strengthElement.innerHTML = '✅ Contraseña segura';
                strengthElement.style.color = '#28a745';
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('contrasena').value;
            const confirmPassword = document.getElementById('confirmar_contrasena').value;
            const matchElement = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchElement.innerHTML = '🔄 Las contraseñas deben coincidir';
                if (document.body.classList.contains('day-mode')) {
                    matchElement.style.color = '#6c757d';
                } else {
                    matchElement.style.color = '#cbd5e1';
                }
            } else if (password !== confirmPassword) {
                matchElement.innerHTML = '❌ Las contraseñas no coinciden';
                matchElement.style.color = '#dc3545';
            } else {
                matchElement.innerHTML = '✅ Las contraseñas coinciden';
                matchElement.style.color = '#28a745';
            }
        }

        // Efectos en inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                    if (document.body.classList.contains('day-mode')) {
                        this.style.boxShadow = '0 8px 25px rgba(0,123,255,0.15)';
                    } else {
                        this.style.boxShadow = '0 8px 25px rgba(6, 182, 212, 0.15)';
                    }
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Validación del formulario antes de enviar
            document.getElementById('juradoForm').addEventListener('submit', function(e) {
                const password = document.getElementById('contrasena').value;
                const confirmPassword = document.getElementById('confirmar_contrasena').value;
                
                if (password.length < 4) {
                    e.preventDefault();
                    alert('❌ La contraseña debe tener al menos 4 caracteres');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('❌ Las contraseñas no coinciden');
                    return false;
                }

                // Prevenir envío duplicado
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '⏳ Agregando...';
                    submitBtn.disabled = true;
                }
            });
        });
    </script>
</body>
</html>oc