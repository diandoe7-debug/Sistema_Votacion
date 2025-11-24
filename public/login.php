<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: jurado/dashboard.php");
        exit();
    }
}

require_once '../src/config/database.php';

$error = '';
$correo_value = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $correo_value = htmlspecialchars($correo);
    
    if (empty($correo) || empty($contrasena)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            try {
                $query = "SELECT id, nombre, correo, contraseña, rol FROM usuarios WHERE correo = :correo";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':correo', $correo);
                $stmt->execute();
                
                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($contrasena == $user['contraseña']) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['rol'];
                        $_SESSION['user_name'] = $user['nombre'];
                        
                        if ($user['rol'] == 'admin') {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: jurado/dashboard.php");
                        }
                        exit();
                    } else {
                        $error = "Contraseña incorrecta.";
                    }
                } else {
                    $error = "Usuario no encontrado.";
                }
            } catch (PDOException $e) {
                $error = "Error de base de datos: " . $e->getMessage();
            }
        } else {
            $error = "Error de conexión a la base de datos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Elección Estudiantil</title>

    <style>
        /* FUENTES LOCALES POPPINS */
            @font-face {
                font-family: 'Poppins';
                src: url('./../../assets/fonts/poppins-light.woff2') format('woff2');
                font-weight: 300;
                font-style: normal;
            }

            @font-face {
                font-family: 'Poppins';
                src: url('./../../assets/fonts/poppins-regular.woff2') format('woff2');
                font-weight: 400;
                font-style: normal;
            }

            @font-face {
                font-family: 'Poppins';
                src: url('./../../assets/fonts/poppins-medium.woff2') format('woff2');
                font-weight: 500;
                font-style: normal;
            }

            @font-face {
                font-family: 'Poppins';
                src: url('./../../assets/fonts/poppins-semibold.woff2') format('woff2');
                font-weight: 600;
                font-style: normal;
            }

            @font-face {
                font-family: 'Poppins';
                src: url('./../../assets/fonts/poppins-bold.woff2') format('woff2');
                font-weight: 700;
                font-style: normal;
            }
        :root {
            --primary-dark: #0f172a;
            --primary-light: #1e293b;
            --accent-gold: #fbbf24;
            --accent-pink: #ec4899;
            --accent-turquoise: #06b6d4;
            --text-light: #f8fafc;
            --text-gray: #000000ff;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--primary-dark);
            color: var(--text-light);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Estilos del carrusel */
        .carousel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
        }
        .carousel::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* ← Negrito con 50% de opacidad */
            z-index: 1;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        /* Contenido principal */
        .main-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .logo p {
            font-size: 1.2rem;
            color: var(--text-gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Botón circular */
        .cta-button {
            position: relative;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            text-decoration: none;
            box-shadow: var(--shadow), 0 0 30px rgba(251, 191, 36, 0.4);
            transition: var(--transition);
            cursor: pointer;
            overflow: hidden;
            z-index: 1;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), transparent);
            z-index: -1;
            transition: var(--transition);
        }

        .cta-button:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow), 0 0 40px rgba(251, 191, 36, 0.6);
        }

        .cta-button:hover::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), transparent);
        }

        /* Modal de login */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            width: 90%;
            max-width: 450px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(15px);
            transform: translateY(20px);
            transition: var(--transition);
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .modal-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .modal-header p {
            color: var(--text-gray);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-light);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--accent-gold);
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fecaca;
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .system-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            color: var(--text-gray);
            text-align: left;
        }

        .system-info strong {
            color: var(--accent-gold);
            display: block;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo h1 {
                font-size: 2.5rem;
            }

            .cta-button {
                width: 150px;
                height: 150px;
                font-size: 1rem;
            }
        }
    </style>
    
    
</head>
<body class="night-mode">
    <!-- Carrusel de fondo -->
    <div class="carousel">        
        <div class="carousel-slide" style="background-image: url('./../assets/img/1.jpg')"></div>
        <div class="carousel-slide" style="background-image: url('./../assets/img/2.jpg')"></div>
        <div class="carousel-slide" style="background-image: url('./../assets/img/3.jpg')"></div>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="logo">
            <h1>Elección Estudiantil</h1>
            
        </div>
        <div class="cta-button" id="openModal">
            <span>Iniciar Sesión</span>
        </div>
    </div>

    <!-- Modal de login -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <button class="close-modal" id="closeModal">&times;</button>
            <div class="modal-header">
                <h2>Acceso al Sistema</h2>
                <p>Ingresa tus credenciales para continuar</p>
            </div>
            
            <?php if ($error != '') { ?>
                <div class="error"><?php echo $error; ?></div>
            <?php } ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" class="form-control" value="<?php echo $correo_value; ?>" placeholder="Ingresa tu correo electrónico" required>
                </div>
                
                <div class="form-group">
                    <label for="contrasena">Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" class="form-control" placeholder="Ingresa tu contraseña" required>
                </div>
                
                <button type="submit" class="btn">Ingresar al Sistema</button>
            </form>
            
            <div class="system-info">
                <strong>Credenciales de prueba:</strong><br>
                Admin: admin@test.com / admin123<br>
                Jurado: jurado@test.com / jurado123
            </div>
        </div>
    </div>

    <script>
        // Inicialización cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            initializeCarousel();
            setupEventListeners();
        });

       // Inicializar el carrusel de imágenes
        function initializeCarousel() {
            const slides = document.querySelectorAll('.carousel-slide');
            let currentSlide = 0;
            
        // Activar la primera slide inmediatamente
        slides[currentSlide].classList.add('active');
        
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 2000);
        }
        
        
        // Configurar event listeners
        function setupEventListeners() {
            // Modal de login
            document.getElementById('openModal').addEventListener('click', openModal);
            document.getElementById('closeModal').addEventListener('click', closeModal);
            
            // Cerrar modal al hacer clic fuera del contenido
            document.getElementById('loginModal').addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
        }

        // Abrir modal de login
        function openModal() {
            document.getElementById('loginModal').classList.add('active');
        }

        // Cerrar modal de login
        function closeModal() {
            document.getElementById('loginModal').classList.remove('active');
        }
    </script>
</body>
</html>