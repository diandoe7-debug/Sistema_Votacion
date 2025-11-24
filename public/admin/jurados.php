<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$jurados = [];
$error = '';
$success = '';

// Mostrar mensaje de éxito si se agregó un jurado
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Jurado agregado exitosamente.";
}

// Mostrar mensaje de éxito para eliminación
if (isset($_GET['success']) && $_GET['success'] == 3) {
    $success = "Jurado eliminado exitosamente.";
}

// Mostrar mensajes de error
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($db) {
    try {
        $query = "SELECT id, nombre, correo, rol FROM usuarios WHERE rol = 'jurado' ORDER BY nombre";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $jurados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al cargar los jurados: " . $e->getMessage();
    }
} else {
    $error = "No se pudo conectar a la base de datos. Verifica la configuración.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Jurados - Sistema de Votación</title>
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
            max-width: 1400px;
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

        /* Mensajes */
        .success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(34, 139, 34, 0.3));
            color: #d4edda;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 5px solid #28a745;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        body.day-mode .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(178, 34, 34, 0.3));
            color: #f8d7da;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 5px solid #dc3545;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        body.day-mode .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        body.day-mode .stat-card {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-gold);
            display: block;
            margin-bottom: 5px;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
        }

        body.day-mode .stat-number {
            color: #007bff;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--text-gray);
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
        }

        body.day-mode .stat-label {
            color: var(--text-gray-day);
            text-shadow: none;
        }

        /* Botones */
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--accent-turquoise), var(--accent-pink));
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--accent-gold), #e0a800);
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        /* Tabla */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            margin-bottom: 30px;
            transition: var(--transition);
        }

        body.day-mode .table-container {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, var(--accent-turquoise), var(--accent-pink));
            color: white;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-light);
            transition: var(--transition);
        }

        body.day-mode td {
            color: var(--text-day);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        body.day-mode tr:hover {
            background: rgba(0,123,255,0.03);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-gray);
        }

        body.day-mode .no-data {
            color: var(--text-gray-day);
        }

        .no-data .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .role-badge {
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .actions-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .footer-actions {
            text-align: center;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                justify-content: center;
            }
            
            th, td {
                padding: 12px 15px;
            }
            
            .actions-cell {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }

            .theme-toggle {
                top: 10px;
                right: 10px;
                width: 45px;
                height: 45px;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-container {
            animation: fadeIn 0.6s ease-out;
        }

        tr {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
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
            <h1> Gestión de Jurados</h1>
            <p>Administra los usuarios con rol de jurado en el sistema</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success">
                ✅ <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error">
                ❌ <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo count($jurados); ?></span>
                <span class="stat-label">Total de Jurados</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo count($jurados); ?></span>
                <span class="stat-label">Jurados Activos</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">0</span>
                <span class="stat-label">Eventos Activos</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">100%</span>
                <span class="stat-label">Sistema Listo</span>
            </div>
        </div>

        <div class="actions">
            <a href="dashboard.php" class="btn">
                ← Volver al Panel
            </a>
            <a href="agregar_jurado.php" class="btn btn-success">
                ➕ Agregar Nuevo Jurado
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jurados) && empty($error)): ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <span class="icon">📝</span>
                                <h3>No hay jurados registrados</h3>
                                <p>Comienza agregando el primer jurado al sistema</p>
                                <a href="agregar_jurado.php" class="btn btn-success" style="margin-top: 15px;">
                                    ➕ Crear Primer Jurado
                                </a>
                            </td>
                        </tr>
                    <?php elseif (!empty($error)): ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <span class="icon">❌</span>
                                <h3>Error de conexión</h3>
                                <p>No se pueden cargar los datos en este momento</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jurados as $jurado): ?>
                        <tr>
                            <td><strong>#<?php echo $jurado['id']; ?></strong></td>
                            <td>
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($jurado['nombre']); ?>
                                </div>
                            </td>
                            <td>
                                <div style="color: var(--text-black); font-weight: 700;">
                                    <?php echo htmlspecialchars($jurado['correo']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge">
                                    👨‍⚖️ <?php echo $jurado['rol']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <a href="editar_jurado.php?id=<?php echo $jurado['id']; ?>" class="btn btn-edit">
                                        ✏️ Editar
                                    </a>
                                    <a href="eliminar_jurado.php?id=<?php echo $jurado['id']; ?>" class="btn btn-danger" 
                                       onclick="return confirm('¿Estás seguro de que quieres eliminar al jurado <?php echo htmlspecialchars(addslashes($jurado['nombre'])); ?>? Esta acción no se puede deshacer.')">
                                        🗑️ Eliminar
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-actions">
            <a href="dashboard.php" class="btn">
                ← Volver al Panel de Administración
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

            // Efectos para las filas de la tabla
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach((row, index) => {
                // Animación escalonada para las filas
                row.style.animationDelay = (index * 0.1) + 's';
                
                // Efecto hover mejorado
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>