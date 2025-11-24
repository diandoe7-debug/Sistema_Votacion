<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'jurado') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$evento = null;
$participante = null;
$categorias = [];
$votos_existentes = [];
$error = '';
$success = '';

// Verificar parámetros
if (!isset($_GET['evento_id']) || !isset($_GET['participante_id'])) {
    header("Location: dashboard.php");
    exit();
}

$evento_id = $_GET['evento_id'];
$participante_id = $_GET['participante_id'];
$jurado_id = $_SESSION['user_id'];

try {
    // Obtener información del evento
    $query = "SELECT id, nombre FROM eventos WHERE id = :id AND estado = 'Activo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $evento_id);
    $stmt->execute();
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener información del participante
    $query = "SELECT id, nombre, representante, foto, descripcion FROM participantes WHERE id = :id AND evento_id = :evento_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $participante_id);
    $stmt->bindParam(':evento_id', $evento_id);
    $stmt->execute();
    $participante = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener categorías del evento
    $query = "SELECT id, nombre, puntaje_maximo FROM categorias WHERE evento_id = :evento_id ORDER BY nombre";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':evento_id', $evento_id);
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener votos existentes del jurado para este participante
    $query = "SELECT categoria_id, puntaje FROM votos 
              WHERE jurado_id = :jurado_id 
              AND participante_id = :participante_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':jurado_id', $jurado_id);
    $stmt->bindParam(':participante_id', $participante_id);
    $stmt->execute();
    $votos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar votos existentes
    foreach ($votos_raw as $voto) {
        $votos_existentes[$voto['categoria_id']] = $voto['puntaje'];
    }

} catch (PDOException $e) {
    $error = "Error al cargar los datos: " . $e->getMessage();
}

// Procesar el formulario de votación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_votos'])) {
    try {
        $db->beginTransaction();
        $votos_procesados = 0;

        foreach ($categorias as $categoria) {
            $input_name = "puntaje_{$categoria['id']}";
            
            if (isset($_POST[$input_name]) && !empty($_POST[$input_name])) {
                $puntaje = intval($_POST[$input_name]);
                
                // Validar puntaje
                if ($puntaje >= 1 && $puntaje <= $categoria['puntaje_maximo']) {
                    
                    // Verificar si ya existe un voto
                    $query = "SELECT id FROM votos 
                             WHERE jurado_id = :jurado_id 
                             AND participante_id = :participante_id 
                             AND categoria_id = :categoria_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jurado_id', $jurado_id);
                    $stmt->bindParam(':participante_id', $participante_id);
                    $stmt->bindParam(':categoria_id', $categoria['id']);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        // Actualizar voto existente
                        $query = "UPDATE votos SET puntaje = :puntaje WHERE jurado_id = :jurado_id AND participante_id = :participante_id AND categoria_id = :categoria_id";
                    } else {
                        // Insertar nuevo voto
                        $query = "INSERT INTO votos (jurado_id, participante_id, categoria_id, puntaje) 
                                 VALUES (:jurado_id, :participante_id, :categoria_id, :puntaje)";
                    }
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':jurado_id', $jurado_id);
                    $stmt->bindParam(':participante_id', $participante_id);
                    $stmt->bindParam(':categoria_id', $categoria['id']);
                    $stmt->bindParam(':puntaje', $puntaje);
                    $stmt->execute();
                    
                    $votos_procesados++;
                }
            }
        }
        
        $db->commit();
        
        // ✅ REDIRECCIÓN AUTOMÁTICA DESPUÉS DE GUARDAR
        $_SESSION['success'] = "✅ Votos guardados exitosamente para {$participante['nombre']}.";
        header("Location: votacion.php?evento_id=" . $evento_id);
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Error al guardar los votos: " . $e->getMessage();
    }
}

// Mostrar mensaje de éxito si existe (para cuando se edita un voto sin redirección)
if (isset($_SESSION['success_edit'])) {
    $success = $_SESSION['success_edit'];
    unset($_SESSION['success_edit']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votar Participante - Jurado</title>
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
            --glass-bg-night: rgba(1, 3, 10, 0.85);
            --glass-border-day: rgba(255, 255, 255, 0);
            --glass-border-night: rgba(15, 4, 56, 0.07);
            --card-bg-day: rgba(255, 255, 255, 0.92);
            --card-bg-night: rgba(1, 8, 21, 0.85);
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
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            transition: all 0.4s ease;
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

        .user-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .user-card {
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid;
            transition: all 0.4s ease;
        }

        .day-mode .user-card {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            border-color: rgba(255,255,255,0.3);
        }

        .night-mode .user-card {
            background: linear-gradient(135deg, rgba(255, 234, 167, 0.2), rgba(253, 203, 110, 0.2));
            border-color: rgba(255,255,255,0.2);
        }

        .user-card strong {
            font-size: 1.1rem;
            transition: all 0.4s ease;
        }

        .day-mode .user-card strong {
            color: #2d3436;
        }

        .night-mode .user-card strong {
            color: var(--text-super-bright);
        }

        .participante-info {
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            transition: all 0.4s ease;
        }

        .day-mode .participante-info {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .participante-info {
            background: var(--glass-bg-night);
            border-color: var(--glass-border-night);
        }

        /* ESTILOS MEJORADOS PARA LAS IMÁGENES */
        .foto-container {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            flex-shrink: 0;
            border: 4px solid var(--primary-day);
        }

        .night-mode .foto-container {
            border-color: var(--primary-night);
        }

        .foto-participante {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: all 0.3s ease;
        }

        .foto-container:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }

        .foto-container:hover .foto-participante {
            transform: scale(1.1);
        }

        /* Placeholder transparente */
        .foto-placeholder-transparent {
            width: 200px;
            height: 200px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
            border: 4px solid var(--primary-day);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .night-mode .foto-placeholder-transparent {
            border-color: var(--primary-night);
        }

        .placeholder-icon {
            font-size: 4rem;
            transition: all 0.3s ease;
        }

        .day-mode .placeholder-icon {
            color: rgba(0, 123, 255, 0.5);
        }

        .night-mode .placeholder-icon {
            color: rgba(6, 182, 212, 0.5);
        }

        .foto-placeholder-transparent:hover .placeholder-icon {
            transform: scale(1.1);
        }

        .day-mode .foto-placeholder-transparent:hover .placeholder-icon {
            color: rgba(0, 123, 255, 0.8);
        }

        .night-mode .foto-placeholder-transparent:hover .placeholder-icon {
            color: rgba(6, 182, 212, 0.8);
        }

        .participante-details h2 {
            margin-bottom: 10px;
            font-size: 1.8rem;
            transition: all 0.4s ease;
        }

        .day-mode .participante-details h2 {
            color: #2d3436;
        }

        .night-mode .participante-details h2 {
            color: var(--text-super-bright);
        }

        .participante-details p {
            margin-bottom: 8px;
            font-size: 1.1rem;
            transition: all 0.4s ease;
        }

        .day-mode .participante-details p {
            color: #2d3436;
        }

        .night-mode .participante-details p {
            color: var(--text-super-bright);
        }

        .evento-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }

        .day-mode .evento-badge {
            background: linear-gradient(135deg, var(--secondary-day), #20c997);
            color: white;
        }

        .night-mode .evento-badge {
            background: linear-gradient(135deg, var(--secondary-night), #a78bfa);
            color: white;
        }

        .categorias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .categoria-card {
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.74);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            position: relative;
            overflow: hidden;
        }

        .day-mode .categoria-card {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .categoria-card {
            background: var(--card-bg-night);
            border-color: var(--glass-border-night);
        }

        .categoria-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-day), #0984e3);
            transition: all 0.4s ease;
        }

        .night-mode .categoria-card:before {
            background: linear-gradient(135deg, var(--primary-night), #8b5cf6);
        }

        .categoria-card.votado {
            border-color: var(--secondary-day);
        }

        .day-mode .categoria-card.votado {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }

        .night-mode .categoria-card.votado {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.3), rgba(195, 230, 203, 0.3));
        }

        .categoria-card.votado:before {
            background: linear-gradient(135deg, var(--secondary-day), #20c997);
        }

        .categoria-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        input[type="number"] {
            width: 100%;
            padding: 15px;
            border: 2px solid;
            border-radius: 10px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .day-mode input[type="number"] {
            border-color: #e9ecef;
            background: white;
            color: #2d3436;
        }

        .night-mode input[type="number"] {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-super-bright);
        }

        input[type="number"]:focus {
            outline: none;
            transform: scale(1.02);
        }

        .day-mode input[type="number"]:focus {
            border-color: var(--primary-day);
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.3);
        }

        .night-mode input[type="number"]:focus {
            border-color: var(--primary-night);
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.3);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            text-decoration: none;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }

        .day-mode .btn {
            color: #000000;
        }

        .night-mode .btn {
            color: #ffffff;
        }
        
        .btn:hover {
            transform: translateY(-3px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-day), #0056b3);
            box-shadow: 0 6px 20px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--secondary-day), #1e7e34);
            box-shadow: 0 6px 20px rgba(40,167,69,0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(40,167,69,0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            box-shadow: 0 6px 20px rgba(108,117,125,0.3);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(108,117,125,0.4);
        }

        .error {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid var(--danger-day);
            box-shadow: 0 5px 20px rgba(220,53,69,0.1);
            transition: all 0.4s ease;
            font-weight: 500;
            text-align: center;
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
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid var(--secondary-day);
            box-shadow: 0 5px 20px rgba(40,167,69,0.1);
            transition: all 0.4s ease;
            text-align: center;
        }

        .day-mode .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .night-mode .success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.3), rgba(195, 230, 203, 0.3));
            color: #bbf7d0;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .puntaje-info {
            font-size: 0.9rem;
            margin-top: 10px;
            text-align: center;
            transition: all 0.4s ease;
        }

        .day-mode .puntaje-info {
            color: #666;
        }

        .night-mode .puntaje-info {
            color: #cbd5e1;
        }

        .votado-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }

        .day-mode .votado-badge {
            background: linear-gradient(135deg, var(--secondary-day), #20c997);
            color: white;
        }

        .night-mode .votado-badge {
            background: linear-gradient(135deg, var(--secondary-night), #a78bfa);
            color: white;
        }

        .info-box {
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            border-left: 5px solid var(--primary-day);
            transition: all 0.4s ease;
        }

        .day-mode .info-box {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .info-box {
            background: var(--glass-bg-night);
            border-color: var(--glass-border-night);
            border-left-color: var(--primary-night);
        }

        .categoria-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .categoria-header h3 {
            margin: 0;
            font-size: 1.3rem;
            transition: all 0.4s ease;
        }

        .day-mode .categoria-header h3 {
            color: #0a2127ff;
        }

        .night-mode .categoria-header h3 {
            color: var(--text-super-bright);
        }

        .floating-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            text-align: center;
        }

        .participante-descripcion {
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 1rem;
            border-left: 3px solid var(--primary-day);
            transition: all 0.4s ease;
        }

        .day-mode .participante-descripcion {
            background: rgba(116, 185, 255, 0.1);
            color: #555;
        }

        .night-mode .participante-descripcion {
            background: rgba(6, 182, 212, 0.1);
            color: #cbd5e1;
            border-left-color: var(--primary-night);
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .participante-info {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .foto-container,
            .foto-placeholder-transparent {
                width: 150px;
                height: 150px;
            }
            
            .categorias-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .categoria-card, .participante-info, .info-box {
            animation: fadeIn 0.6s ease-out;
        }

        /* Mejorar visibilidad de textos en modo noche */
        .night-mode {
            color: var(--text-super-bright) !important;
        }

        .night-mode strong {
            color: var(--text-super-bright);
        }

        .night-mode p {
            color: var(--text-super-bright);
        }

        .night-mode h4 {
            color: var(--text-super-bright);
        }
    </style>
</head>
<body class="night-mode">
    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    
    <div class="container">
        <div class="header">
            <div class="floating-icon">🗳️</div>
            <h1>Votar Participante</h1>
            
            <div class="user-info">
                <div class="user-card">
                    <span>👤 <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                </div>
                <div class="user-card">
                    <span>🎯 <strong>Rol:</strong> Jurado</span>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error">
                ❌ <strong>Error:</strong><br>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success">
                ✅ <strong>Éxito:</strong><br>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($evento && $participante): ?>
            <div class="participante-info">
                <?php if (!empty($participante['foto']) && file_exists('../uploads/fotos/' . $participante['foto'])): ?>
                    <div class="foto-container">
                        <img src="../uploads/fotos/<?php echo $participante['foto']; ?>" 
                             alt="<?php echo htmlspecialchars($participante['nombre']); ?>"
                             class="foto-participante">
                    </div>
                <?php else: ?>
                    <div class="foto-placeholder-transparent">
                        <span class="placeholder-icon">👤</span>
                    </div>
                <?php endif; ?>
                
                <div class="participante-details">
                    <h2><?php echo htmlspecialchars($participante['nombre']); ?></h2>
                    <p><strong>🏢 Representa:</strong> <?php echo htmlspecialchars($participante['representante']); ?></p>
                    
                    <?php if (!empty($participante['descripcion'])): ?>
                        <div class="participante-descripcion">
                            <?php echo htmlspecialchars($participante['descripcion']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="evento-badge">
                        🎯 <?php echo htmlspecialchars($evento['nombre']); ?>
                    </div>
                </div>
            </div>

            <!-- Información sobre la votación -->
            <div class="info-box">
                <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                    💡 Información importante
                </h4>
                <p style="margin: 0; font-size: 1rem; line-height: 1.6;">
                    Después de guardar los votos, serás redirigido automáticamente a la lista de participantes 
                    donde podrás continuar votando a los demás concursantes.
                </p>
            </div>

            <form method="POST" action="">
                <div class="categorias-grid">
                    <?php foreach ($categorias as $categoria): ?>
                        <?php $ya_votado = isset($votos_existentes[$categoria['id']]); ?>
                        <div class="text-color=black categoria-card <?php echo $ya_votado ? 'votado' : ''; ?>">
                            <div class="categoria-header">
                                <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                                <?php if ($ya_votado): ?>
                                    <span class="votado-badge">✅ Ya Votado</span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <label for="puntaje_<?php echo $categoria['id']; ?>" style="font-weight: 600;">
                                    Puntaje (1-<?php echo $categoria['puntaje_maximo']; ?>):
                                </label>
                            </div>
                            
                            <input type="number" 
                                   id="puntaje_<?php echo $categoria['id']; ?>"
                                   name="puntaje_<?php echo $categoria['id']; ?>" 
                                   min="1" 
                                   max="<?php echo $categoria['puntaje_maximo']; ?>" 
                                   value="<?php echo $ya_votado ? $votos_existentes[$categoria['id']] : ''; ?>"
                                   placeholder="0"
                                   required>
                            
                            <div class="puntaje-info">
                                Escala: 1 (Mínimo) - <?php echo $categoria['puntaje_maximo']; ?> (Máximo)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="actions">
                    <button type="submit" name="guardar_votos" class="btn btn-success">
                        💾 Guardar Votos y Continuar
                    </button>
                    <a href="votacion.php?evento_id=<?php echo $evento_id; ?>" class="btn btn-secondary">
                        ← Volver a la Lista
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        🏠 Panel Principal
                    </a>
                </div>
            </form>

        <?php else: ?>
            <div class="error">
                ❌ <strong>No se pudo cargar la información del participante</strong>
                <div class="actions" style="margin-top: 20px;">
                    <a href="votacion.php?evento_id=<?php echo $evento_id; ?>" class="btn btn-secondary">← Volver a la Lista</a>
                    <a href="dashboard.php" class="btn btn-secondary">🏠 Panel Principal</a>
                </div>
            </div>
        <?php endif; ?>
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

        // Confirmación antes de enviar el formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmacion = confirm('¿Estás seguro de que quieres guardar los votos? Serás redirigido a la lista de participantes.');
            if (!confirmacion) {
                e.preventDefault();
            }
        });

        // Validación en tiempo real de los puntajes
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.max);
                const value = parseInt(this.value);
                
                if (value < 1) {
                    this.value = 1;
                    alert('El puntaje mínimo es 1');
                } else if (value > max) {
                    this.value = max;
                    alert(`El puntaje máximo permitido es ${max}`);
                }
            });

            // Efecto visual al enfocar
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Efectos de interacción suaves
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.categoria-card');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Efectos en botones
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>