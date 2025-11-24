<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Votación</title>
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
            --text-gray: #000000;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            
            /* Variables para modo día */
            --bg-day: url('./../../assets/img/1.jpg') center/cover fixed;
            --bg-night: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('./../../assets/img/2.jpg') center/cover fixed;
            --text-day: #2c3e50;
            --text-gray-day: #000000;
            --glass-bg-day: rgba(255, 255, 255, 0.92);
            --glass-border-day: rgba(255, 255, 255, 0.3);
            --shadow-day: 0 10px 30px rgba(0,0,0,0.15);
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
        }

        body.day-mode {
            background: var(--bg-day);
            color: var(--text-day);
        }

        /* Botón de cambio de tema */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
            z-index: 1000;
        }

        body.day-mode .theme-toggle {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-icon {
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .sun { display: none; }
        .moon { display: block; }

        body.day-mode .sun { display: block; }
        body.day-mode .moon { display: none; }

        /* Contenido principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        body.day-mode .header {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
            box-shadow: var(--shadow-day);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
        }

        body.day-mode .header h1 {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header p {
            color: var(--text-gray);
            margin-bottom: 1rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            transition: var(--transition);
            font-weight: 500;
        }

        body.day-mode .header p {
            color: var(--text-gray-day);
            text-shadow: none;
        }

        .user-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .user-badge {
            background: linear-gradient(135deg, var(--accent-turquoise), var(--accent-pink));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.7);
        }

        body.day-mode .user-badge {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .user-badge.role {
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-item {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        body.day-mode .stat-item {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-gold);
            display: block;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        body.day-mode .stat-number {
            color: #007bff;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-gray);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            font-weight: 500;
        }

        body.day-mode .stat-label {
            color: var(--text-gray-day);
            text-shadow: none;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .menu-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 30px 25px;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--text-light);
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        body.day-mode .menu-card {
            background: var(--glass-bg-day);
            color: var(--text-day);
            border: 1px solid var(--glass-border-day);
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        body.day-mode .menu-card:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-8px) scale(1.02);
        }

        .menu-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .menu-card h3 {
            color: var(--accent-gold);
            margin-bottom: 10px;
            font-size: 1.4rem;
            font-weight: 600;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        body.day-mode .menu-card h3 {
            color: #2c3e50;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .menu-card p {
            color: var(--text-gray);
            line-height: 1.5;
            font-size: 0.95rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            font-weight: 500;
        }

        body.day-mode .menu-card p {
            color: var(--text-gray-day);
            text-shadow: none;
        }

        .actions {
            text-align: center;
            margin-top: 30px;
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220,53,69,0.4);
        }

        /* Animaciones */
        .menu-card {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards, floating 3s ease-in-out infinite;
        }

        .menu-card:nth-child(1) { animation-delay: 0.1s, 0s; }
        .menu-card:nth-child(2) { animation-delay: 0.2s, 0.2s; }
        .menu-card:nth-child(3) { animation-delay: 0.3s, 0.4s; }
        .menu-card:nth-child(4) { animation-delay: 0.4s, 0.6s; }
        .menu-card:nth-child(5) { animation-delay: 0.5s, 0.8s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
            
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .theme-toggle {
                top: 10px;
                right: 10px;
                width: 45px;
                height: 45px;
            }
        }
    </style>
</head>
<body class="night-mode">
    <!-- Botón de cambio de tema -->
    <div class="theme-toggle" id="themeToggle">
        <span class="theme-icon sun">☀️</span>
        <span class="theme-icon moon">🌙</span>
    </div>

    <div class="container">
        <div class="header">
            <h1> Panel de Administración</h1>
            <p>Bienvenido al centro de control del sistema</p>
            
            <div class="user-info">
                <div class="user-badge">
                    👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </div>
                <div class="user-badge role">
                    ⚡ <?php echo htmlspecialchars($_SESSION['user_role']); ?>
                </div>
            </div>
            
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-number">5</span>
                    <span class="stat-label">Módulos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Disponible</span>
                </div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="jurados.php" class="menu-card">
                <span class="menu-icon">🧑‍⚖️</span>
                <h3>Gestión de Jurados</h3>
                <p>Administrar usuarios jurados y sus permisos</p>
            </a>
            
            <a href="eventos.php" class="menu-card">
                <span class="menu-icon">🎉</span>
                <h3>Eventos</h3>
                <p>Crear y gestionar eventos de votación</p>
            </a>
            
            <a href="categorias.php" class="menu-card">
                <span class="menu-icon">🏷️</span>
                <h3>Categorías</h3>
                <p>Configurar criterios de evaluación</p>
            </a>
            
            <a href="participantes.php" class="menu-card">
                <span class="menu-icon">👩‍🎤</span>
                <h3>Participantes</h3>
                <p>Gestionar concursantes y representantes</p>
            </a>
            
            <a href="resultados.php" class="menu-card">
                <span class="menu-icon">📊</span>
                <h3>Resultados</h3>
                <p>Ver reportes y estadísticas detalladas</p>
            </a>
        </div>

        <div class="actions">
            <a href="../logout.php" class="btn-logout">
                <span>🚪</span> Cerrar Sesión
            </a>
        </div>
    </div>

    <script>
        // Sistema de cambio de tema
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            // Verificar si hay una preferencia guardada
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'day') {
                body.classList.add('day-mode');
            }
            
            // Cambiar tema al hacer clic
            themeToggle.addEventListener('click', function() {
                body.classList.toggle('day-mode');
                
                // Guardar preferencia
                if (body.classList.contains('day-mode')) {
                    localStorage.setItem('theme', 'day');
                } else {
                    localStorage.setItem('theme', 'night');
                }
            });

            // Efectos interactivos
            const cards = document.querySelectorAll('.menu-card');
            const logoutBtn = document.querySelector('.btn-logout');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    if (body.classList.contains('day-mode')) {
                        this.style.background = 'rgba(255, 255, 255, 1)';
                    } else {
                        this.style.background = 'rgba(255, 255, 255, 0.2)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (body.classList.contains('day-mode')) {
                        this.style.background = 'var(--glass-bg-day)';
                    } else {
                        this.style.background = 'var(--glass-bg)';
                    }
                });
            });
            
            logoutBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.05)';
            });
            
            logoutBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>