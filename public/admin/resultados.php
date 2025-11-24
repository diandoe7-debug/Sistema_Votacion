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
$estadisticas = [];
$resultados_detallados = [];
$error = '';
$success = '';

// Obtener eventos para el selector
if ($db) {
    try {
        $query = "SELECT id, nombre, fecha, estado FROM eventos ORDER BY fecha DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error al cargar los eventos: " . $e->getMessage();
    }
}

// Procesar solicitud de estadísticas
$evento_seleccionado = null;
if (isset($_GET['evento_id']) && !empty($_GET['evento_id'])) {
    $evento_id = $_GET['evento_id'];
    
    try {
        // Obtener información del evento seleccionado
        $query = "SELECT id, nombre, fecha, estado FROM eventos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $evento_id);
        $stmt->execute();
        $evento_seleccionado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($evento_seleccionado) {
            // Obtener estadísticas básicas
            // Total de participantes
            $query = "SELECT COUNT(*) as total FROM participantes WHERE evento_id = :evento_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':evento_id', $evento_id);
            $stmt->execute();
            $estadisticas['total_participantes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de categorías
            $query = "SELECT COUNT(*) as total FROM categorias WHERE evento_id = :evento_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':evento_id', $evento_id);
            $stmt->execute();
            $estadisticas['total_categorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de jurados
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'jurado'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $estadisticas['total_jurados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de votos
            $query = "SELECT COUNT(*) as total 
                      FROM votos v 
                      JOIN participantes p ON v.participante_id = p.id 
                      WHERE p.evento_id = :evento_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':evento_id', $evento_id);
            $stmt->execute();
            $estadisticas['total_votos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener ranking de participantes (puntaje total)
            $query = "SELECT p.id, p.nombre, p.representante, 
                             COALESCE(SUM(v.puntaje), 0) as puntaje_total,
                             COUNT(v.id) as votos_recibidos
                      FROM participantes p 
                      LEFT JOIN votos v ON p.id = v.participante_id 
                      WHERE p.evento_id = :evento_id 
                      GROUP BY p.id, p.nombre, p.representante
                      ORDER BY puntaje_total DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':evento_id', $evento_id);
            $stmt->execute();
            $estadisticas['ranking'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener información de jurados que han completado la votación
            $query = "SELECT u.id, u.nombre,
                             (SELECT COUNT(DISTINCT participante_id) 
                              FROM votos 
                              WHERE jurado_id = u.id 
                              AND participante_id IN (SELECT id FROM participantes WHERE evento_id = :evento_id)
                             ) as participantes_votados
                      FROM usuarios u 
                      WHERE u.rol = 'jurado'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':evento_id', $evento_id);
            $stmt->execute();
            $jurados_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $estadisticas['jurados_completados'] = 0;
            $total_participantes = $estadisticas['total_participantes'];
            
            foreach ($jurados_info as $jurado) {
                if ($jurado['participantes_votados'] == $total_participantes) {
                    $estadisticas['jurados_completados']++;
                }
            }

            // Determinar estado de la votación
            if ($estadisticas['jurados_completados'] == 0) {
                $estadisticas['estado_votacion'] = 'sin_votos';
            } elseif ($estadisticas['jurados_completados'] < $estadisticas['total_jurados']) {
                $estadisticas['estado_votacion'] = 'votando';
            } else {
                $estadisticas['estado_votacion'] = 'completado';
            }

            // Si hay votos, obtener resultados detallados por categoría
            if ($estadisticas['total_votos'] > 0) {
                // Obtener categorías del evento
                $query = "SELECT id, nombre, puntaje_maximo FROM categorias WHERE evento_id = :evento_id ORDER BY nombre";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':evento_id', $evento_id);
                $stmt->execute();
                $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Calcular resultados por categoría
                foreach ($categorias as $categoria) {
                    $query = "SELECT p.id, p.nombre, p.representante,
                                     AVG(v.puntaje) as promedio,
                                     COUNT(v.puntaje) as total_votos,
                                     SUM(v.puntaje) as puntaje_total
                              FROM participantes p
                              LEFT JOIN votos v ON p.id = v.participante_id AND v.categoria_id = :categoria_id
                              WHERE p.evento_id = :evento_id
                              GROUP BY p.id, p.nombre, p.representante
                              ORDER BY promedio DESC";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':categoria_id', $categoria['id']);
                    $stmt->bindParam(':evento_id', $evento_id);
                    $stmt->execute();
                    $resultados_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Calcular porcentajes
                    $max_puntaje = $categoria['puntaje_maximo'];
                    foreach ($resultados_categoria as &$resultado) {
                        if ($resultado['promedio']) {
                            $resultado['porcentaje'] = round(($resultado['promedio'] / $max_puntaje) * 100, 1);
                        } else {
                            $resultado['porcentaje'] = 0;
                            $resultado['promedio'] = 0;
                        }
                    }

                    $resultados_detallados[$categoria['id']] = [
                        'categoria_nombre' => $categoria['nombre'],
                        'puntaje_maximo' => $max_puntaje,
                        'participantes' => $resultados_categoria
                    ];
                }

                // Calcular resultados generales (promedio de todos los votos)
                $query = "SELECT p.id, p.nombre, p.representante,
                                 AVG(v.puntaje) as promedio_general,
                                 COUNT(v.puntaje) as total_votos
                          FROM participantes p
                          LEFT JOIN votos v ON p.id = v.participante_id
                          WHERE p.evento_id = :evento_id
                          GROUP BY p.id, p.nombre, p.representante
                          ORDER BY promedio_general DESC";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':evento_id', $evento_id);
                $stmt->execute();
                $resultados_generales = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $resultados_detallados['general'] = [
                    'categoria_nombre' => 'Resultado General',
                    'participantes' => $resultados_generales
                ];
            }
            
        }
        
    } catch (PDOException $e) {
        $error = "Error al cargar las estadísticas: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados y Estadísticas - Sistema de Votación</title>
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
            --glass-border-day: rgba(255, 255, 255, 0.08);
            --glass-border-night: rgba(255, 255, 255, 0.05);
            --card-bg-day: rgba(255, 255, 255, 0.92);
            --card-bg-night: rgba(5, 15, 30, 0.85);
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
        }
        
        .header {
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            margin-bottom: 10px;
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
        
        .selector-evento {
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            transition: all 0.4s ease;
        }

        .day-mode .selector-evento {
            background: var(--card-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .selector-evento {
            background: var(--card-bg-night);
            border-color: var(--glass-border-night);
        }
        
        .selector-evento h3 {
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .selector-evento h3 {
            color: var(--text-color-day);
        }

        .night-mode .selector-evento h3 {
            color: var(--text-super-bright);
        }
        
        .select-evento {
            width: 100%;
            padding: 15px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
            font-weight: 500;
        }

        .day-mode .select-evento {
            border-color: #dee2e6;
            background: rgba(255,255,255,0.95);
            color: var(--text-color-day);
        }

        .night-mode .select-evento {
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.15);
            color: var(--text-super-bright);
        }
        
        .select-evento:focus {
            outline: none;
            transform: translateY(-2px);
        }

        .day-mode .select-evento:focus {
            border-color: var(--primary-day);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .night-mode .select-evento:focus {
            border-color: var(--primary-night);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
        }
        
        .evento-info {
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid var(--primary-day);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
        }

        .day-mode .evento-info {
            background: linear-gradient(135deg, #e7f3ff, #d1ecf1);
        }

        .night-mode .evento-info {
            background: linear-gradient(135deg, #082f49, #0f172a);
            border-left-color: var(--primary-night);
        }
        
        .evento-info h3 {
            margin-bottom: 10px;
            font-size: 1.4rem;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .evento-info h3 {
            color: var(--text-color-day);
        }

        .night-mode .evento-info h3 {
            color: var(--text-super-bright);
        }

        .evento-info p {
            font-weight: 500;
            transition: all 0.4s ease;
        }

        .day-mode .evento-info p {
            color: var(--text-color-day);
        }

        .night-mode .evento-info p {
            color: var(--text-super-bright);
        }
        
        .estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-day);
        }

        .day-mode .stat-card {
            background: var(--card-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .stat-card {
            background: var(--card-bg-night);
            border-color: var(--glass-border-night);
            border-left-color: var(--primary-night);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            transition: all 0.4s ease;
        }

        .day-mode .stat-number {
            color: var(--primary-day);
        }

        .night-mode .stat-number {
            color: var(--primary-night);
        }
        
        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .stat-label {
            color: var(--text-color-day);
        }

        .night-mode .stat-label {
            color: var(--text-super-bright);
        }
        
        .table-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            margin-bottom: 30px;
            transition: all 0.4s ease;
        }

        .day-mode .table-container {
            background: var(--card-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .table-container {
            background: var(--card-bg-night);
            border-color: var(--glass-border-night);
        }
        
        .table-header {
            padding: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .table-header {
            background: linear-gradient(135deg, var(--primary-day), #0056b3);
            color: white;
        }

        .night-mode .table-header {
            background: linear-gradient(135deg, var(--primary-night), #7c3aed);
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            border-bottom: 2px solid;
            transition: all 0.4s ease;
        }

        .day-mode th {
            background: rgba(0,123,255,0.1);
            color: var(--text-color-day);
            border-bottom-color: rgba(0,123,255,0.2);
        }

        .night-mode th {
            background: rgba(6, 182, 212, 0.15);
            color: var(--text-super-bright);
            border-bottom-color: rgba(6, 182, 212, 0.3);
        }
        
        td {
            padding: 16px 20px;
            border-bottom: 1px solid;
            transition: all 0.4s ease;
            font-weight: 500;
        }

        .day-mode td {
            color: var(--text-color-day);
            border-bottom-color: rgba(0,0,0,0.08);
        }

        .night-mode td {
            color: var(--text-super-bright);
            border-bottom-color: rgba(255,255,255,0.1);
        }
        
        tr:hover {
            transition: all 0.3s ease;
        }

        .day-mode tr:hover {
            background: rgba(0,123,255,0.05);
        }

        .night-mode tr:hover {
            background: rgba(6, 182, 212, 0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            color: #000000;
            text-decoration: none;
            border-radius: 10px;
            border: none;
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
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-day), #c82333);
            box-shadow: 0 4px 15px rgba(220,53,69,0.3);
        }
        
        .btn-danger:hover {
            box-shadow: 0 6px 20px rgba(220,53,69,0.4);
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
        }

        .day-mode .no-data {
            background: var(--card-bg-day);
            color: var(--text-color-day);
        }

        .night-mode .no-data {
            background: var(--card-bg-night);
            color: var(--text-super-bright);
        }
        
        .no-data .icon {
            font-size: 4rem;
            margin-bottom: 15px;
            display: block;
        }

        .no-data h3 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .no-data p {
            font-weight: 500;
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
        
        .puesto-1 { 
            font-weight: bold;
            transition: all 0.4s ease;
        }

        .day-mode .puesto-1 { 
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        }

        .night-mode .puesto-1 { 
            background: linear-gradient(135deg, rgba(255, 243, 205, 0.3), rgba(255, 234, 167, 0.3));
            color: var(--text-super-bright);
        }

        .puesto-2 { 
            transition: all 0.4s ease;
            font-weight: 500;
        }

        .day-mode .puesto-2 { 
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
        }

        .night-mode .puesto-2 { 
            background: linear-gradient(135deg, rgba(233, 236, 239, 0.25), rgba(222, 226, 230, 0.25));
            color: var(--text-super-bright);
        }

        .puesto-3 { 
            transition: all 0.4s ease;
            font-weight: 500;
        }

        .day-mode .puesto-3 { 
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        }

        .night-mode .puesto-3 { 
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.25), rgba(245, 198, 203, 0.25));
            color: var(--text-super-bright);
        }
        
        .medal { 
            font-size: 1.3em; 
            margin-right: 8px;
            display: inline-block;
        }
        
        .info-box {
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid var(--info-day);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
        }

        .day-mode .info-box {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        }

        .night-mode .info-box {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.25), rgba(190, 229, 235, 0.25));
            border-left-color: var(--primary-night);
        }

        .info-box h4 {
            font-weight: 600;
            margin-bottom: 10px;
            transition: all 0.4s ease;
        }

        .day-mode .info-box h4 {
            color: var(--text-color-day);
        }

        .night-mode .info-box h4 {
            color: var(--text-super-bright);
        }

        .info-box p, .info-box li {
            font-weight: 500;
            transition: all 0.4s ease;
        }

        .day-mode .info-box p, .day-mode .info-box li {
            color: var(--text-color-day);
        }

        .night-mode .info-box p, .night-mode .info-box li {
            color: var(--text-super-bright);
        }
        
        .estado-votacion {
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid var(--warning-day);
            box-shadow: 0 8px 25px rgba(60, 12, 12, 0.57);
            text-align: center;
            transition: all 0.4s ease;
        }

        .day-mode .estado-votacion {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        }

        .night-mode .estado-votacion {
            background: linear-gradient(135deg, rgba(26, 3, 71, 0.25), rgba(1, 0, 14, 0.25));
            border-left-color: var(--warning-day);
        }

        .estado-votacion h3 {
            font-weight: 600;
            margin-bottom: 10px;
            transition: all 0.4s ease;
        }

        .day-mode .estado-votacion h3 {
            color: var(--text-color-day);
        }

        .night-mode .estado-votacion h3 {
            color: var(--text-super-bright);
        }

        .estado-votacion p {
            font-weight: 500;
            transition: all 0.4s ease;
        }

        .day-mode .estado-votacion p {
            color: var(--text-color-day);
        }

        .night-mode .estado-votacion p {
            color: var(--text-super-bright);
        }
                
        .estado-completado {
            transition: all 0.4s ease;
        }

        .day-mode .estado-completado {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 5px solid var(--secondary-day);
        }

        .night-mode .estado-completado {
            background: linear-gradient(135deg, #1a1f1c, #151915);
            border-left: 5px solid var(--secondary-night);
        }

        .resultados-detallados {
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            margin-bottom: 30px;
            transition: all 0.4s ease;
        }

        .day-mode .resultados-detallados {
            background: var(--card-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .resultados-detallados {
            background: var(--card-bg-night);
            border-color: var(--glass-border-night);
        }

        .resultados-detallados h2 {
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .resultados-detallados h2 {
            color: var(--text-color-day);
        }

        .night-mode .resultados-detallados h2 {
            color: var(--text-super-bright);
        }
        
        .categoria-resultados {
            margin-bottom: 35px;
        }
        
        .categoria-header {
            padding: 20px;
            border-radius: 12px 12px 0 0;
            margin-bottom: 0;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .categoria-header {
            background: linear-gradient(135deg, var(--primary-day), #0056b3);
            color: white;
        }

        .night-mode .categoria-header {
            background: linear-gradient(135deg, var(--primary-night), #7c3aed);
            color: white;
        }
        
        .barra-progreso {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 8px 0;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .night-mode .barra-progreso {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .progreso {
            background: linear-gradient(90deg, var(--secondary-day), #20c997);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
            box-shadow: 0 2px 4px rgba(40,167,69,0.3);
        }

        .night-mode .progreso {
            background: linear-gradient(90deg, var(--secondary-night), #10b981);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .footer-actions {
            text-align: center;
            margin-top: 20px;
        }

        /* Textos pequeños mejorados para modo noche */
        small {
            font-weight: 500;
        }

        .day-mode small {
            color: var(--text-color-day);
        }

        .night-mode small {
            color: var(--text-super-bright);
            opacity: 0.9;
        }

        strong {
            font-weight: 600;
        }

        .night-mode strong {
            color: var(--text-super-bright);
        }

        /* Mejorar visibilidad de todos los textos en modo noche */
        .night-mode {
            color: var(--text-super-bright) !important;
        }

        .night-mode *:not(.btn):not(.table-header):not(.categoria-header) {
            color: var(--text-super-bright) !important;
        }

        .night-mode input::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .night-mode select option {
            background: var(--card-bg-night);
            color: var(--text-super-bright);
        }
        
        @media (max-width: 768px) {
            .estadisticas-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            th, td {
                padding: 12px 15px;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .table-container, .resultados-detallados, .estadisticas-grid {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body class="night-mode">
    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    
    <div class="container">
        <div class="header">
            <h1>📊 Resultados y Estadísticas</h1>
            <p>Consulte reportes y rankings de los eventos</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error">
                ❌ <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="selector-evento">
            <h3>🎯 Seleccionar Evento para Ver Resultados</h3>
            <form method="GET" action="">
                <select name="evento_id" class="select-evento" onchange="this.form.submit()">
                    <option value="">-- Seleccione un evento --</option>
                    <?php foreach ($eventos as $evento): ?>
                        <option value="<?php echo $evento['id']; ?>" 
                            <?php echo (isset($_GET['evento_id']) && $_GET['evento_id'] == $evento['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($evento['nombre']); ?> 
                            (<?php echo date('d/m/Y', strtotime($evento['fecha'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($evento_seleccionado): ?>
            <div class="evento-info">
                <h3>📅 <?php echo htmlspecialchars($evento_seleccionado['nombre']); ?></h3>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($evento_seleccionado['fecha'])); ?> | 
                   <strong>Estado:</strong> <?php echo $evento_seleccionado['estado']; ?> | 
                   <strong>ID:</strong> #<?php echo $evento_seleccionado['id']; ?></p>
            </div>

            <!-- Información sobre datos disponibles -->
            <?php if ($estadisticas['total_participantes'] == 0): ?>
                <div class="info-box">
                    <h4>📝 Información</h4>
                    <p>Este evento no tiene participantes inscritos todavía. Los resultados se mostrarán cuando:</p>
                    <ul style="margin-top: 15px; padding-left: 20px;">
                        <li>✅ Se inscriban participantes</li>
                        <li>✅ Se configuren categorías</li>
                        <li>✅ Los jurados emitan votos</li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Estado de la votación -->
                <?php if ($estadisticas['estado_votacion'] == 'sin_votos'): ?>
                    <div class="selector-evento">
                        <h3>⏳ Esperando Votos</h3>
                        <p>Ningún jurado ha completado la votación de todos los participantes.</p>
                        <p>Los resultados detallados se mostrarán cuando los jurados comiencen a votar.</p>
                    </div>
                <?php elseif ($estadisticas['estado_votacion'] == 'votando'): ?>
                    <div class="estado-votacion">
                        <h3>📊 Votación en Progreso</h3>
                        <p><strong><?php echo $estadisticas['jurados_completados']; ?> de <?php echo $estadisticas['total_jurados']; ?></strong> jurados han completado la votación.</p>
                        <p>Esperando a que todos los jurados completen sus votaciones para mostrar los resultados finales.</p>
                    </div>
                <?php elseif ($estadisticas['estado_votacion'] == 'completado'): ?>
                    <div class="estado-votacion estado-completado">
                        <h3>✅ Votación Completada</h3>
                        <p><strong>Todos los jurados han completado la votación.</strong> Resultados finales disponibles.</p>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas generales -->
                <div class="estadisticas-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $estadisticas['total_participantes']; ?></span>
                        <span class="stat-label">👥 Participantes</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $estadisticas['total_categorias']; ?></span>
                        <span class="stat-label">🏷️ Categorías</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $estadisticas['total_jurados']; ?></span>
                        <span class="stat-label">🧑‍⚖️ Jurados Totales</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $estadisticas['jurados_completados']; ?></span>
                        <span class="stat-label">✅ Jurados Completaron</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $estadisticas['total_votos']; ?></span>
                        <span class="stat-label">🗳️ Votos Emitidos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">
                            <?php if ($estadisticas['total_participantes'] > 0): ?>
                                <?php echo round(($estadisticas['total_votos'] / ($estadisticas['total_participantes'] * $estadisticas['total_jurados'])) * 100, 1); ?>%
                            <?php else: ?>
                                0%
                            <?php endif; ?>
                        </span>
                        <span class="stat-label">📈 Progreso Total</span>
                    </div>
                </div>

                <!-- Ranking general de participantes -->
                <div class="table-container">
                    <div class="table-header">
                        🏆 Ranking General de Participantes
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Puesto</th>
                                <th>Participante</th>
                                <th>Representa</th>
                                <th>Puntaje Total</th>
                                <th>Votos Recibidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($estadisticas['ranking']) || $estadisticas['total_votos'] == 0): ?>
                                <tr>
                                    <td colspan="5" class="no-data">
                                        <span class="icon">📝</span>
                                        <h3>No hay votos registrados</h3>
                                        <?php if ($estadisticas['total_participantes'] > 0): ?>
                                            <p>Los resultados aparecerán cuando los jurados comiencen a votar.</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($estadisticas['ranking'] as $index => $participante): ?>
                                    <tr class="puesto-<?php echo min($index + 1, 3); ?>">
                                        <td>
                                            <?php if ($index == 0): ?>
                                                <span class="medal">🥇</span> 1°
                                            <?php elseif ($index == 1): ?>
                                                <span class="medal">🥈</span> 2°
                                            <?php elseif ($index == 2): ?>
                                                <span class="medal">🥉</span> 3°
                                            <?php else: ?>
                                                #<?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($participante['nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($participante['representante']); ?></td>
                                        <td><strong><?php echo $participante['puntaje_total']; ?> pts</strong></td>
                                        <td><?php echo $participante['votos_recibidos']; ?> votos</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resultados detallados por categoría -->
                <?php if ($estadisticas['total_votos'] > 0 && $estadisticas['estado_votacion'] != 'sin_votos' && !empty($resultados_detallados)): ?>
                    <div class="resultados-detallados">
                        <h2>📈 Resultados Detallados por Categoría</h2>
                        
                        <?php foreach ($resultados_detallados as $categoria_id => $categoria_data): ?>
                            <?php if ($categoria_id != 'general'): ?>
                                <div class="categoria-resultados">
                                    <div class="categoria-header">
                                        <?php echo htmlspecialchars($categoria_data['categoria_nombre']); ?> 
                                        <small style="opacity: 0.9;">(Máximo: <?php echo $categoria_data['puntaje_maximo']; ?> puntos)</small>
                                    </div>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Puesto</th>
                                                <th>Participante</th>
                                                <th>Promedio</th>
                                                <th>Porcentaje</th>
                                                <th>Total Votos</th>
                                                <th>Progreso</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categoria_data['participantes'] as $index => $participante): ?>
                                                <tr class="puesto-<?php echo min($index + 1, 3); ?>">
                                                    <td>
                                                        <?php if ($index == 0): ?>
                                                            <span class="medal">🥇</span> 1°
                                                        <?php elseif ($index == 1): ?>
                                                            <span class="medal">🥈</span> 2°
                                                        <?php elseif ($index == 2): ?>
                                                            <span class="medal">🥉</span> 3°
                                                        <?php else: ?>
                                                            #<?php echo $index + 1; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo htmlspecialchars($participante['nombre']); ?></strong></td>
                                                    <td><strong><?php echo number_format($participante['promedio'], 2); ?></strong></td>
                                                    <td><strong><?php echo $participante['porcentaje']; ?>%</strong></td>
                                                    <td><?php echo $participante['total_votos']; ?></td>
                                                    <td style="width: 150px;">
                                                        <div class="barra-progreso">
                                                            <div class="progreso" style="width: <?php echo $participante['porcentaje']; ?>%"></div>
                                                        </div>
                                                        <small><?php echo $participante['porcentaje']; ?>%</small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Resultados generales -->
                        <?php if (isset($resultados_detallados['general'])): ?>
                            <div class="categoria-resultados">
                                <div class="categoria-header">
                                    <?php echo $resultados_detallados['general']['categoria_nombre']; ?>
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Puesto</th>
                                            <th>Participante</th>
                                            <th>Promedio General</th>
                                            <th>Total Votos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultados_detallados['general']['participantes'] as $index => $participante): ?>
                                            <tr class="puesto-<?php echo min($index + 1, 3); ?>">
                                                <td>
                                                    <?php if ($index == 0): ?>
                                                        <span class="medal">🥇</span> 1°
                                                    <?php elseif ($index == 1): ?>
                                                        <span class="medal">🥈</span> 2°
                                                    <?php elseif ($index == 2): ?>
                                                        <span class="medal">🥉</span> 3°
                                                    <?php else: ?>
                                                        #<?php echo $index + 1; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($participante['nombre']); ?></strong></td>
                                                <td><strong><?php echo number_format($participante['promedio_general'], 2); ?></strong></td>
                                                <td><?php echo $participante['total_votos']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <div class="actions">
                    <?php if ($estadisticas['total_votos'] > 0): ?>
                        <a href="exportar_resultados.php?evento_id=<?php echo $evento_seleccionado['id']; ?>" class="btn btn-success">📄 Exportar a PDF</a>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn btn-primary">← Volver al Panel</a>
                </div>
            <?php endif; ?>

        <?php elseif (isset($_GET['evento_id']) && empty($evento_seleccionado)): ?>
            <div class="error">❌ Evento no encontrado.</div>
        <?php endif; ?>

        <?php if (empty($_GET['evento_id'])): ?>
            <div class="no-data">
                <span class="icon">📊</span>
                <h3>Seleccione un evento</h3>
                <p>Use el menú desplegable superior para ver resultados y estadísticas</p>
            </div>
        <?php endif; ?>

        <div class="footer-actions">
            <a href="dashboard.php" class="btn btn-secondary">
                ← Volver al Panel de Administración
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

        // Efectos interactivos
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            const rows = document.querySelectorAll('tbody tr');
            
            // Animación escalonada para las tarjetas
            statCards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                card.style.animation = 'fadeIn 0.6s ease-out forwards';
                card.style.opacity = '0';
            });

            // Efecto hover en filas
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });

            // Efecto en el selector
            const selector = document.querySelector('.select-evento');
            if (selector) {
                selector.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                selector.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                });
            }
        });
    </script>
</body>
</html>