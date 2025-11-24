<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$evento = null;

// Primero obtener información del evento para mostrar en la confirmación
if (isset($_GET['id'])) {
    $evento_id = $_GET['id'];
    
    try {
        // Obtener datos del evento para mostrar
        $query = "SELECT id, nombre, fecha FROM eventos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $evento_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Evento no encontrado.";
        }
    } catch (PDOException $e) {
        $error = "Error al cargar el evento: " . $e->getMessage();
    }
} else {
    $error = "ID de evento no especificado.";
}

// Procesar eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $evento_id = $_POST['evento_id'];
    
    try {
        // Verificar que el evento existe
        $query = "SELECT nombre FROM eventos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $evento_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $evento_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $nombre_evento = $evento_data['nombre'];
            
            // Eliminar el evento (las categorías y participantes se eliminarán en cascada)
            $query = "DELETE FROM eventos WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $evento_id);
            
            if ($stmt->execute()) {
                header("Location: eventos.php?success=3");
                exit();
            } else {
                $error = "Error al eliminar el evento.";
            }
        } else {
            $error = "Evento no encontrado.";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    }
}

// Si hay error al cargar, redirigir
if (!empty($error) && !$evento) {
    header("Location: eventos.php?error=" . urlencode($error));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación - Sistema de Votación</title>
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
            --text-light: #ffffff;
            --text-gray: #e2e8f0;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            
            /* VARIABLES PARA FONDOS CON IMÁGENES */
            --bg-day: url('./../../assets/img/1.jpg') center/cover fixed;
            --bg-night: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('./../../assets/img/2.jpg') center/cover fixed;
            --text-day: #000000;
            --text-gray-day: #2d3748;
            --glass-bg-day: rgba(255, 255, 255, 0.95);
            --glass-border-day: rgba(255, 255, 255, 0.3);
            --shadow-day: 0 10px 30px rgba(0,0,0,0.15);

            /* COLORES PARA ELEMENTOS INTERACTIVOS */
            --btn-danger: #ef4444;
            --btn-danger-hover: #dc2626;
            --btn-secondary: #6b7280;
            --btn-secondary-hover: #4b5563;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-night);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            transition: var(--transition);
            overflow-x: hidden;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.day-mode {
            background: var(--bg-day);
            color: var(--text-day);
            font-size: 17px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            animation: zoomIn 0.6s ease-out;
        }

        /* ========== BOTÓN DE TEMA ========== */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
            animation: float 3s ease-in-out infinite;
            font-size: 1.8rem;
        }

        .theme-toggle::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff);
            background-size: 400% 400%;
            border-radius: 50%;
            z-index: -1;
            animation: gradientShift 3s ease infinite;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .theme-toggle:hover::before {
            opacity: 1;
        }

        body.day-mode .theme-toggle {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        .theme-toggle:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.6);
        }

        body.day-mode .theme-toggle:hover {
            box-shadow: 0 0 30px rgba(236, 72, 153, 0.6);
        }

        .theme-icon {
            font-size: 1.8rem;
            transition: var(--transition);
            position: absolute;
            animation: pulse 2s infinite;
        }

        .sun { 
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
            opacity: 0;
            transform: scale(0.8);
        }
        
        .moon { 
            color: #e2e8f0;
            text-shadow: 0 0 10px rgba(226, 232, 240, 0.5);
            opacity: 1;
            transform: scale(1);
        }

        body.day-mode .sun {
            opacity: 1;
            transform: scale(1);
        }

        body.day-mode .moon {
            opacity: 0;
            transform: scale(0.8);
        }

        .theme-toggle::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: -45px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
            font-weight: 500;
        }

        .theme-toggle:hover::after {
            opacity: 1;
        }

        /* ========== TARJETA DE CONFIRMACIÓN ========== */
        .confirmation-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            animation: slideDown 0.8s ease-out;
        }

        .confirmation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
            animation: rainbow 3s linear infinite;
        }

        .confirmation-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }

        body.day-mode .confirmation-card {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
            box-shadow: var(--shadow-day);
        }

        .warning-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            display: block;
            animation: bounce 2s infinite;
        }

        .confirmation-card h1 {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
            animation: typewriter 1.5s steps(15) 0.5s 1 normal both;
            overflow: hidden;
            white-space: nowrap;
        }

        body.day-mode .confirmation-card h1 {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .confirmation-card p {
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.6;
            font-weight: 500;
            animation: fadeInUp 1s ease 1s both;
        }

        body.day-mode .confirmation-card p {
            color: var(--text-day);
        }

        /* ========== INFORMACIÓN DEL EVENTO ========== */
        .evento-info {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 6px solid var(--accent-gold);
            transition: var(--transition);
            animation: slideInLeft 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .evento-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.03) 50%, transparent 70%);
            animation: shimmer 4s infinite;
        }

        body.day-mode .evento-info {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        .evento-nombre {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--text-light);
            margin-bottom: 8px;
            animation: bounceIn 1s ease;
        }

        body.day-mode .evento-nombre {
            color: var(--text-day);
        }

        .evento-details {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        .detail-item {
            text-align: center;
            padding: 10px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            transition: var(--transition);
        }

        .detail-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        body.day-mode .detail-item {
            background: var(--glass-bg-day);
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 5px;
            font-weight: 500;
        }

        body.day-mode .detail-label {
            color: var(--text-day);
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.1rem;
        }

        body.day-mode .detail-value {
            color: var(--text-day);
        }

        /* ========== CONSECUENCIAS ========== */
        .consequences {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            text-align: left;
            transition: var(--transition);
            animation: slideInRight 0.8s ease-out;
        }

        body.day-mode .consequences {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        .consequences h4 {
            color: var(--text-light);
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        body.day-mode .consequences h4 {
            color: var(--text-day);
        }

        .consequences ul {
            color: var(--text-light);
            padding-left: 20px;
        }

        body.day-mode .consequences ul {
            color: var(--text-day);
        }

        .consequences li {
            margin-bottom: 8px;
            position: relative;
            padding-left: 5px;
            transition: var(--transition);
        }

        .consequences li:hover {
            transform: translateX(5px);
            color: var(--accent-gold);
        }

        /* ========== BOTONES CON EFECTO DE LUZ BLANCA ========== */
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 1.2s both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 2px solid transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            text-align: center;
            min-width: 180px;
        }

        /* EFECTO DE LUZ BLANCA QUE RECORRE EL BOTÓN - SOLO PARA ENLACES/FUNCIONES */
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-color: rgba(239, 68, 68, 0.5);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            box-shadow: 0 15px 30px rgba(239, 68, 68, 0.4);
            transform: translateY(-3px) scale(1.05);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            border-color: rgba(107, 114, 128, 0.5);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563, #6b7280);
            box-shadow: 0 15px 30px rgba(107, 114, 128, 0.4);
            transform: translateY(-3px) scale(1.05);
        }

        .btn::after {
            content: '⚡';
            position: absolute;
            right: 15px;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s;
            font-size: 1.2rem;
        }

        .btn:hover::after {
            opacity: 1;
            transform: translateX(0);
        }

        /* ========== MENSAJE DE ERROR ========== */
        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 6px solid #dc3545;
            box-shadow: 0 4px 15px rgba(220,53,69,0.1);
            cursor: default;
            animation: shake 0.5s ease;
            font-size: 1.1rem;
        }

        /* ========== PARTÍCULAS FLOTANTES ========== */
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s infinite ease-in-out;
        }

        .particle:nth-child(1) { top: 20%; left: 10%; width: 4px; height: 4px; animation-delay: 0s; }
        .particle:nth-child(2) { top: 60%; left: 80%; width: 6px; height: 6px; animation-delay: 1s; }
        .particle:nth-child(3) { top: 40%; left: 40%; width: 3px; height: 3px; animation-delay: 2s; }
        .particle:nth-child(4) { top: 80%; left: 30%; width: 5px; height: 5px; animation-delay: 3s; }
        .particle:nth-child(5) { top: 10%; left: 70%; width: 4px; height: 4px; animation-delay: 4s; }

        /* ========== ANIMACIONES ========== */
        @keyframes typewriter {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translateY(0);
            }
            40%, 43% {
                transform: translateY(-15px);
            }
            70% {
                transform: translateY(-7px);
            }
            90% {
                transform: translateY(-2px);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 480px) {
            .confirmation-card {
                padding: 30px 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .evento-details {
                flex-direction: column;
                gap: 10px;
            }
            
            .theme-toggle {
                top: 10px;
                right: 10px;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="night-mode">
    <!-- PARTÍCULAS FLOTANTES -->
    <div class="floating-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- BOTÓN DE TEMA -->
    <div class="theme-toggle interactive-element" id="themeToggle" data-tooltip="Cambiar a modo día">
        <span class="theme-icon sun">☀️</span>
        <span class="theme-icon moon">🌙</span>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="confirmation-card">
                <div class="error">
                    ❌ <strong>Error:</strong> <?php echo $error; ?>
                </div>
                <div style="margin-top: 20px;">
                    <a href="eventos.php" class="btn btn-secondary">
                        ← Volver a Eventos
                    </a>
                </div>
            </div>
        <?php elseif ($evento): ?>
            <div class="confirmation-card">
                <span class="warning-icon">⚠️</span>
                <h1>Confirmar Eliminación</h1>
                <p>¿Estás seguro de que deseas eliminar este evento? Esta acción no se puede deshacer.</p>
                
                <div class="evento-info">
                    <div class="evento-nombre"><?php echo htmlspecialchars($evento['nombre']); ?></div>
                    <div class="evento-details">
                        <div class="detail-item">
                            <div class="detail-label">📅 Fecha</div>
                            <div class="detail-value"><?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">🆔 ID</div>
                            <div class="detail-value">#<?php echo $evento['id']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="consequences">
                    <h4>⚠️ Esta acción eliminará también:</h4>
                    <ul>
                        <li>Todas las categorías asociadas al evento</li>
                        <li>Todos los participantes inscritos</li>
                        <li>Todos los registros de votación</li>
                        <li>Todos los resultados y estadísticas</li>
                    </ul>
                </div>
                
                <form method="POST" class="actions">
                    <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                    <button type="submit" name="confirmar" value="1" class="btn btn-danger">
                        🗑️ Sí, Eliminar Evento
                    </button>
                    <a href="eventos.php" class="btn btn-secondary">
                        ✋ Cancelar
                    </a>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // SISTEMA DE CAMBIO DE TEMA
        document.addEventListener('DOMContentLoaded', function() {
            var themeToggle = document.getElementById('themeToggle');
            var body = document.body;
            
            // Verificar preferencia guardada
            var savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'day') {
                body.classList.add('day-mode');
                if (themeToggle) themeToggle.setAttribute('data-tooltip', 'Cambiar a modo noche');
            } else {
                if (themeToggle) themeToggle.setAttribute('data-tooltip', 'Cambiar a modo día');
            }
            
            // Cambiar tema
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    var isDayMode = body.classList.toggle('day-mode');
                    
                    if (isDayMode) {
                        this.setAttribute('data-tooltip', 'Cambiar a modo noche');
                    } else {
                        this.setAttribute('data-tooltip', 'Cambiar a modo día');
                    }
                    
                    localStorage.setItem('theme', isDayMode ? 'day' : 'night');
                });
            }

            // Efectos interactivos para botones
            var buttons = document.querySelectorAll('.btn');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.05)';
                });
                buttons[i].addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            }
        });
    </script>
</body>
</html>