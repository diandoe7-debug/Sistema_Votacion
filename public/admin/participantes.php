<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$eventos = [];
$error = '';
$success = '';

// Mostrar mensajes de éxito
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success = "Participante creado exitosamente.";
    } elseif ($_GET['success'] == 2) {
        $success = "Participante actualizado exitosamente.";
    } elseif ($_GET['success'] == 3) {
        $success = "Participante eliminado exitosamente.";
    }
}

if ($db) {
    try {
        // SOLUCIÓN: Usar JOIN en una sola consulta para evitar el problema del bucle
        $query = "SELECT e.id, e.nombre, e.fecha, e.estado, 
                         COUNT(p.id) as total_participantes 
                  FROM eventos e 
                  LEFT JOIN participantes p ON e.id = p.evento_id 
                  GROUP BY e.id 
                  ORDER BY e.fecha DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error al cargar los eventos: " . $e->getMessage();
    }
} else {
    $error = "No se pudo conectar a la base de datos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Participantes - Sistema de Votación</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.95)), url('./../../assets/img/2.jpg') center/cover fixed;
            min-height: 100vh;
            padding: 20px;
            color: #f8fafc;
            transition: all 0.4s ease;
        }
        
        body.day-mode {
            background: url('./../../assets/img/1.jpg') center/cover fixed;
            color: #000000;
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        body.day-mode .theme-toggle { 
            background: rgba(0,0,0,0.1);
            border: 2px solid rgba(0,0,0,0.3);
            color: #000000;
        }
        
        .theme-toggle:hover { 
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        }
        
        .sun { display: none; }
        .moon { display: block; }
        body.day-mode .sun { display: block; }
        body.day-mode .moon { display: none; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        body.day-mode .header { 
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fbbf24, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        body.day-mode .header h1 {
            background: linear-gradient(135deg, #007bff, #28a745);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        body.day-mode .header p {
            color: #000000;
        }
        
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
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.3);
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        body.day-mode .btn {
            background: linear-gradient(135deg, #007bff, #28a745);
            color: #000000;
            box-shadow: 0 6px 20px rgba(0,123,255,0.3);
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.4);
        }
        
        body.day-mode .btn:hover {
            box-shadow: 0 10px 25px rgba(0,123,255,0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        body.day-mode .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #000000;
        }
        
        .btn-small {
            padding: 10px 20px;
            font-size: 0.9rem;
            border-radius: 8px;
        }
        
        .eventos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .evento-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
            border-left: 5px solid #06b6d4;
            animation: fadeInUp 0.6s ease-out;
        }
        
        body.day-mode .evento-card { 
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-left: 5px solid #007bff;
        }
        
        .evento-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        body.day-mode .evento-card:hover {
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .evento-card h3 {
            margin: 0 0 15px 0;
            font-size: 1.4rem;
            color: #fbbf24;
        }
        
        body.day-mode .evento-card h3 {
            color: #007bff;
        }
        
        .evento-info {
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
            opacity: 0.9;
        }
        
        body.day-mode .evento-info {
            color: #000000;
        }
        
        .evento-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        body.day-mode .evento-stats {
            background: rgba(0,0,0,0.05);
        }
        
        .stat {
            text-align: center;
            flex: 1;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #06b6d4;
            display: block;
        }
        
        body.day-mode .stat-number {
            color: #007bff;
        }
        
        .stat-label {
            font-size: 0.8rem;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        body.day-mode .stat-label {
            color: #000000;
        }
        
        .evento-actions {
            display: flex;
            gap: 10px;
        }
        
        .estado-activo {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .estado-cerrado {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        body.day-mode .no-data {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.1);
            color: #000000;
        }
        
        .error {
            background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(220,38,38,0.3));
            color: #fca5a5;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid #ef4444;
            box-shadow: 0 8px 25px rgba(239,68,68,0.2);
        }
        
        body.day-mode .error { 
            background: linear-gradient(135deg, rgba(220,53,69,0.1), rgba(220,53,69,0.2));
            color: #000000;
            border-left: 5px solid #dc3545;
        }
        
        .success {
            background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(5,150,105,0.3));
            color: #86efac;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid #10b981;
            box-shadow: 0 8px 25px rgba(16,185,129,0.2);
        }
        
        body.day-mode .success { 
            background: linear-gradient(135deg, rgba(40,167,69,0.1), rgba(40,167,69,0.2));
            color: #000000;
            border-left: 5px solid #28a745;
        }
        
        .total-counter {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        body.day-mode .total-counter { 
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(0,0,0,0.1);
            color: #000000;
        }
        
        @media (max-width: 768px) {
            .actions { flex-direction: column; }
            .btn { justify-content: center; }
            .eventos-grid { grid-template-columns: 1fr; }
            .evento-stats { flex-direction: column; gap: 10px; }
            .evento-actions { flex-direction: column; }
            .theme-toggle { top: 15px; right: 15px; width: 45px; height: 45px; }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="night-mode">
    <div class="theme-toggle" id="themeToggle">
        <span class="sun">☀️</span>
        <span class="moon">🌙</span>
    </div>

    <div class="container">
        <div class="header">
            <h1>Gestión de Participantes por Evento</h1>
            <p>Seleccione un evento para gestionar sus concursantes</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="actions">
            <a href="dashboard.php" class="btn">← Volver al Panel</a>
            <div class="total-counter">Total: <?php echo count($eventos); ?> eventos</div>
        </div>

        <?php if (empty($eventos)): ?>
            <div class="no-data">
                <h3>No hay eventos creados en el sistema</h3>
                <p>Comienza creando el primer evento para agregar participantes</p>
                <a href="eventos.php" class="btn btn-success" style="margin-top: 20px;">
                    Crear Primer Evento
                </a>
            </div>
        <?php else: ?>
            <div class="eventos-grid">
                <?php foreach ($eventos as $evento): ?>
                <div class="evento-card">
                    <h3><?php echo htmlspecialchars($evento['nombre']); ?></h3>
                    
                    <div class="evento-info">
                        <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?><br>
                        <strong>Estado:</strong> 
                        <?php if ($evento['estado'] == 'Activo'): ?>
                            <span class="estado-activo"><?php echo $evento['estado']; ?></span>
                        <?php else: ?>
                            <span class="estado-cerrado"><?php echo $evento['estado']; ?></span>
                        <?php endif; ?>
                        <br>
                        <strong>ID:</strong> #<?php echo $evento['id']; ?>
                    </div>

                    <div class="evento-stats">
                        <div class="stat">
                            <div class="stat-number"><?php echo $evento['total_participantes']; ?></div>
                            <div class="stat-label">Participantes</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">-</div>
                            <div class="stat-label">Categorías</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">#<?php echo $evento['id']; ?></div>
                            <div class="stat-label">ID Evento</div>
                        </div>
                    </div>

                    <div class="evento-actions">
                        <a href="gestionar_participantes.php?evento_id=<?php echo $evento['id']; ?>" class="btn btn-success btn-small">
                            Gestionar Participantes
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" class="btn">← Volver al Panel de Administración</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'day') body.classList.add('day-mode');
            
            themeToggle.addEventListener('click', function() {
                body.classList.toggle('day-mode');
                localStorage.setItem('theme', body.classList.contains('day-mode') ? 'day' : 'night');
            });

            const cards = document.querySelectorAll('.evento-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                
                card.addEventListener('mouseenter', function() {
                    this.style.borderLeftColor = '#10b981';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.borderLeftColor = '#06b6d4';
                });
            });

            body.day-mode && cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.borderLeftColor = '#28a745';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.borderLeftColor = '#007bff';
                });
            });
        });
    </script>
</body>
</html>