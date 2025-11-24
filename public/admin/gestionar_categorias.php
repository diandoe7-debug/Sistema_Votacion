<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$categorias = [];
$evento = null;
$error = '';
$success = '';

// Mostrar mensajes de éxito/error
if (isset($_GET['success'])) {
    if ($_GET['success'] == 3) {
        $success = "Categoría eliminada exitosamente.";
    }
}

if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Obtener información del evento
if (isset($_GET['evento_id'])) {
    $evento_id = $_GET['evento_id'];
    
    try {
        // Obtener datos del evento
        $query = "SELECT id, nombre, fecha, estado FROM eventos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $evento_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener categorías del evento
            $query = "SELECT id, nombre, puntaje_maximo FROM categorias WHERE evento_id = :evento_id ORDER BY nombre";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':evento_id', $evento_id);
            $stmt->execute();
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Evento no encontrado.";
        }
    } catch (PDOException $e) {
        $error = "Error al cargar las categorías: " . $e->getMessage();
    }
} else {
    $error = "ID de evento no especificado.";
}

// Procesar agregar categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_categoria']) && $evento) {
    $nombre = trim($_POST['nombre']);
    $puntaje_maximo = 10; // Siempre 10 puntos

    if (empty($nombre)) {
        $error = "El nombre de la categoría es obligatorio.";
    } else {
        try {
            // Verificar si ya existe esta categoría en el evento
            $query = "SELECT id FROM categorias WHERE nombre = :nombre AND evento_id = :evento_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':evento_id', $evento['id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Ya existe una categoría con ese nombre en este evento.";
            } else {
                // Insertar nueva categoría
                $query = "INSERT INTO categorias (nombre, puntaje_maximo, evento_id) VALUES (:nombre, :puntaje_maximo, :evento_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':puntaje_maximo', $puntaje_maximo);
                $stmt->bindParam(':evento_id', $evento['id']);
                
                if ($stmt->execute()) {
                    $success = "Categoría '$nombre' agregada exitosamente.";
                    // Recargar categorías
                    $query = "SELECT id, nombre, puntaje_maximo FROM categorias WHERE evento_id = :evento_id ORDER BY nombre";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':evento_id', $evento['id']);
                    $stmt->execute();
                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = "Error al agregar la categoría.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Categorías - Sistema de Votación</title>
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
        
        .container { max-width: 1000px; margin: 0 auto; }
        
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
        
        .evento-info {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(15px);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid #06b6d4;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        body.day-mode .evento-info { 
            background: rgba(255,255,255,0.9);
            border-left: 5px solid #007bff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .evento-info h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #fbbf24;
        }
        
        body.day-mode .evento-info h3 {
            color: #007bff;
        }
        
        .evento-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            background: rgba(255,255,255,0.15);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        body.day-mode .detail-item { 
            background: rgba(255,255,255,0.8);
            border: 1px solid rgba(0,0,0,0.1);
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
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #000000;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
        }
        
        body.day-mode .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #000000;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }
        
        body.day-mode .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #000000;
        }
        
        .btn-small { 
            padding: 8px 16px; 
            font-size: 0.85rem; 
            border-radius: 8px;
        }
        
        .form-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        body.day-mode .form-container { 
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .form-container h3 {
            margin-bottom: 20px;
            color: #fbbf24;
            font-size: 1.3rem;
        }
        
        body.day-mode .form-container h3 {
            color: #007bff;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 20px;
            align-items: end;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: inherit;
        }
        
        body.day-mode label {
            color: #000000;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        body.day-mode input[type="text"] {
            border: 2px solid rgba(0,0,0,0.2);
            background: rgba(255,255,255,0.9);
            color: #000000;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
            transform: translateY(-2px);
        }
        
        body.day-mode input[type="text"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }
        
        .puntaje-fijo {
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        body.day-mode .puntaje-fijo {
            background: linear-gradient(135deg, #007bff, #28a745);
            color: #000000;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .table-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        body.day-mode .table-container { 
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(0,0,0,0.1);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        table { width: 100%; border-collapse: collapse; }
        
        th {
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            color: white;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        body.day-mode th {
            background: linear-gradient(135deg, #007bff, #28a745);
            color: #000000;
        }
        
        td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        body.day-mode td { 
            border-bottom: 1px solid rgba(0,0,0,0.1);
            color: #000000;
        }
        
        tr:hover {
            background: rgba(255,255,255,0.05);
        }
        
        body.day-mode tr:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .no-data { 
            text-align: center; 
            padding: 60px 20px; 
            color: #94a3b8;
        }
        
        body.day-mode .no-data {
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
        
        .puntaje-badge {
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        body.day-mode .puntaje-badge {
            background: linear-gradient(135deg, #007bff, #28a745);
            color: #000000;
        }
        
        .action-buttons { display: flex; gap: 8px; }
        
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
            .form-row { grid-template-columns: 1fr; gap: 15px; }
            .actions { flex-direction: column; }
            .btn { justify-content: center; }
            .action-buttons { flex-direction: column; }
            .theme-toggle { top: 15px; right: 15px; width: 45px; height: 45px; }
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
            <h1>Gestionar Categorías del Evento</h1>
            <p>Agregue, edite o elimine criterios de evaluación</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($evento): ?>
            <div class="evento-info">
                <h3><?php echo htmlspecialchars($evento['nombre']); ?></h3>
                <div class="evento-details">
                    <div class="detail-item">
                        <div>Fecha: <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div>Estado: <?php echo $evento['estado']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div>ID: #<?php echo $evento['id']; ?></div>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="categorias.php" class="btn">← Volver</a>
                <div class="total-counter"><?php echo count($categorias); ?> categorías</div>
            </div>

            <div class="form-container">
                <h3>Agregar Nueva Categoría</h3>
                <form method="POST" action="">
                    <input type="hidden" name="agregar_categoria" value="1">
                    <div class="form-row">
                        <div>
                            <label>Nombre de la Categoría</label>
                            <input type="text" name="nombre" required placeholder="Ej: Elegancia, Simpatía">
                        </div>
                        <div>
                            <label>Puntaje Máximo</label>
                            <div class="puntaje-fijo">10 puntos</div>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-success">Agregar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Puntaje</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="4" class="no-data">
                                    No hay categorías. ¡Agregue la primera!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td>#<?php echo $categoria['id']; ?></td>
                                <td><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                                <td><span class="puntaje-badge"><?php echo $categoria['puntaje_maximo']; ?> pts</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="editar_categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-warning btn-small">Editar</a>
                                        <a href="eliminar_categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-danger btn-small" 
                                           onclick="return confirm('¿Eliminar \'<?php echo htmlspecialchars($categoria['nombre']); ?>\'?')">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="categorias.php" class="btn">← Volver a Eventos</a>
            </div>

        <?php else: ?>
            <div style="text-align: center;">
                <a href="categorias.php" class="btn">← Volver</a>
            </div>
        <?php endif; ?>
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
        });
    </script>
</body>
</html>