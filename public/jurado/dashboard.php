<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'jurado') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$eventos = [];
$error = '';

// Obtener eventos activos para el jurado
if ($db) {
    try {
        $query = "SELECT id, nombre, fecha, descripcion, estado FROM eventos WHERE estado = 'Activo' ORDER BY fecha DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error al cargar los eventos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Jurado - Sistema de Votación</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
            transition: all 0.4s ease;
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
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }
        
        .header {
            padding: 40px;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            border-bottom: none;
            transition: all 0.4s ease;
            text-align: center;
            margin-bottom: 0;
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
            margin-bottom: 15px;
            font-size: 2.5rem;
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

        .user-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 25px 0;
            flex-wrap: wrap;
        }

        .user-card {
            padding: 15px 25px;
            border-radius: 12px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .day-mode .user-card {
            background: rgba(0, 123, 255, 0.1);
            border: 1px solid rgba(0, 123, 255, 0.2);
            color: var(--text-color-day);
        }

        .night-mode .user-card {
            background: rgba(6, 182, 212, 0.15);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: var(--text-super-bright);
        }

        .user-card strong {
            font-size: 1rem;
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            transition: all 0.4s ease;
        }

        .day-mode .role-badge {
            background: var(--primary-day);
            color: white;
        }

        .night-mode .role-badge {
            background: var(--primary-night);
            color: white;
        }
        
        .eventos { 
            padding: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            transition: all 0.4s ease;
        }

        .day-mode .eventos {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .eventos {
            background: var(--glass-bg-night);
            border-color: var(--glass-border-night);
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            transition: all 0.4s ease;
            position: relative;
        }

        .day-mode .section-title {
            color: var(--text-color-day);
        }

        .night-mode .section-title {
            color: var(--text-super-bright);
        }

        .section-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            margin: 10px auto;
            border-radius: 2px;
            transition: all 0.4s ease;
        }

        .day-mode .section-title:after {
            background: linear-gradient(135deg, var(--primary-day), var(--secondary-day));
        }

        .night-mode .section-title:after {
            background: linear-gradient(135deg, var(--primary-night), var(--secondary-night));
        }

        .eventos-grid {
            display: grid;
            gap: 25px;
        }

        .evento { 
            padding: 30px;
            border-radius: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .day-mode .evento {
            background: var(--card-bg-day);
            border: 1px solid rgba(0,0,0,0.1);
        }

        .night-mode .evento {
            background: var(--card-bg-night);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .evento:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            transition: all 0.4s ease;
        }

        .day-mode .evento:before {
            background: linear-gradient(135deg, var(--primary-day), var(--secondary-day));
        }

        .night-mode .evento:before {
            background: linear-gradient(135deg, var(--primary-night), var(--secondary-night));
        }

        .evento.activo { 
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .evento:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .evento h3 {
            margin: 0 0 15px 0;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.4s ease;
        }

        .day-mode .evento h3 {
            color: var(--text-color-day);
        }

        .night-mode .evento h3 {
            color: var(--text-super-bright);
        }

        .evento-info {
            margin-bottom: 20px;
            transition: all 0.4s ease;
        }

        .day-mode .evento-info {
            color: #666;
        }

        .night-mode .evento-info {
            color: #cbd5e1;
        }

        .evento-descripcion {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            border-left: 4px solid;
            transition: all 0.4s ease;
        }

        .day-mode .evento-descripcion {
            background: rgba(0, 123, 255, 0.05);
            border-left-color: var(--primary-day);
        }

        .night-mode .evento-descripcion {
            background: rgba(6, 182, 212, 0.1);
            border-left-color: var(--primary-night);
        }

        .btn { 
            padding: 12px 30px; 
            color: white; 
            text-decoration: none; 
            border-radius: 10px; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--secondary-day), #1e7e34);
            box-shadow: 0 4px 15px rgba(40,167,69,0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 6px 20px rgba(40,167,69,0.4);
        }

        .logout { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px; 
            background: linear-gradient(135deg, var(--danger-day), #c82333);
            color: white; 
            text-decoration: none; 
            border-radius: 10px; 
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220,53,69,0.4);
        }

        .no-events {
            text-align: center;
            padding: 60px 40px;
            border-radius: 15px;
            border: 2px dashed;
            transition: all 0.4s ease;
        }

        .day-mode .no-events {
            background: #f8f9fa;
            border-color: #dee2e6;
            color: #666;
        }

        .night-mode .no-events {
            background: rgba(30, 41, 59, 0.5);
            border-color: rgba(255,255,255,0.2);
            color: #cbd5e1;
        }

        .no-events h3 {
            margin-bottom: 15px;
            font-size: 1.5rem;
            transition: all 0.4s ease;
        }

        .day-mode .no-events h3 {
            color: #6c757d;
        }

        .night-mode .no-events h3 {
            color: var(--text-super-bright);
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
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            transition: all 0.4s ease;
        }

        .day-mode .badge {
            background: linear-gradient(135deg, var(--secondary-day), #1e7e34);
            color: white;
        }

        .night-mode .badge {
            background: linear-gradient(135deg, var(--secondary-night), #7c3aed);
            color: white;
        }

        .evento-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .stat {
            padding: 8px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            transition: all 0.4s ease;
        }

        .day-mode .stat {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0,0,0,0.1);
        }

        .night-mode .stat {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .actions {
            text-align: center;
            margin-top: 20px;
        }

        .welcome-message {
            font-size: 1.1rem;
            margin-bottom: 10px;
            transition: all 0.4s ease;
        }

        .day-mode .welcome-message {
            color: #666;
        }

        .night-mode .welcome-message {
            color: var(--text-super-bright);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .user-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .evento-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .evento {
                padding: 20px;
            }
            
            .eventos {
                padding: 25px;
            }
        }

        .floating-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            text-align: center;
        }

        .evento-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .evento-title {
            flex: 1;
            min-width: 250px;
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
        
        .eventos {
            animation: slideIn 0.6s ease-out;
        }

        /* Mejorar visibilidad de textos en modo noche */
        .night-mode {
            color: var(--text-super-bright) !important;
        }

        .night-mode strong {
            color: var(--text-super-bright);
        }
    </style>
</head>
<body class="night-mode">

    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    
    <div class="container">
        <div class="header">
            <div class="floating-icon">🧑‍⚖️</div>
            <h1>Panel del Jurado</h1>
            <p class="welcome-message">Bienvenido al sistema de votación certificado</p>
            
            <div class="user-info">
                <div class="user-card">
                    <span>👤 <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                </div>
                <div class="user-card">
                    <span>🎯 <strong>Rol: </strong><span class="role-badge"><?php echo $_SESSION['user_role']; ?></span></span>
                </div>
            </div>
            
            <p style="margin-top: 15px; font-size: 1rem; transition: all 0.4s ease;" class="welcome-message">
                Seleccione un evento activo para comenzar a votar
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error">
                ❌ <strong>Error:</strong><br>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="eventos">
            <h2 class="section-title">🎯 Eventos Activos para Votación</h2>
            
            <?php if (empty($eventos)): ?>
                <div class="no-events">
                    <div style="font-size: 4rem; margin-bottom: 20px;">📝</div>
                    <h3>No hay eventos activos</h3>
                    <p>Actualmente no hay eventos disponibles para votación.</p>
                    <p style="margin-top: 10px;">
                        Los eventos aparecerán aquí cuando estén activos y listos para recibir votos.
                    </p>
                </div>
            <?php else: ?>
                <div class="eventos-grid">
                    <?php foreach ($eventos as $evento): ?>
                    <div class="evento activo">
                        <div class="evento-header">
                            <div class="evento-title">
                                <h3>🎯 <?php echo htmlspecialchars($evento['nombre']); ?></h3>
                            </div>
                            <div class="badge">✅ ACTIVO</div>
                        </div>
                        
                        <div class="evento-stats">
                            <div class="stat">
                                <strong>📅</strong> <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?>
                            </div>
                            <div class="stat">
                                <strong>🆔</strong> #<?php echo $evento['id']; ?>
                            </div>
                            <div class="stat">
                                <strong>⚡</strong> Listo para votar
                            </div>
                        </div>

                        <?php if (!empty($evento['descripcion'])): ?>
                            <div class="evento-descripcion">
                                <strong>📋 Descripción del Evento:</strong><br>
                                <?php echo htmlspecialchars($evento['descripcion']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="actions">
                            <a href="votacion.php?evento_id=<?php echo $evento['id']; ?>" class="btn btn-success">
                                🗳️ Ingresar a Votar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../logout.php" class="logout">
                🚪 Cerrar Sesión
            </a>
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

        // Efectos de interacción suaves
        document.addEventListener('DOMContentLoaded', function() {
            const eventos = document.querySelectorAll('.evento');
            
            eventos.forEach(evento => {
                evento.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    if (document.body.classList.contains('day-mode')) {
                        this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.15)';
                    } else {
                        this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.3)';
                    }
                });
                
                evento.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 5px 20px rgba(0,0,0,0.1)';
                });
            });

            // Efectos en botones
            const buttons = document.querySelectorAll('.btn, .logout');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>