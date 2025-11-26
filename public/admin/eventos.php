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
        $success = "Evento creado exitosamente.";
    } elseif ($_GET['success'] == 2) {
        $success = "Evento actualizado exitosamente.";
    } elseif ($_GET['success'] == 3) {
        $success = "Evento eliminado exitosamente.";
    }
}

// Mostrar mensajes de error
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($db) {
    try {
        $query = "SELECT id, nombre, fecha, descripcion, estado FROM eventos ORDER BY fecha DESC";
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
    <title>Gestión de Eventos - Sistema de Votación</title>
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
            --btn-primary: #3b82f6;
            --btn-primary-hover: #2563eb;
            --btn-success: #10b981;
            --btn-success-hover: #059669;
            --btn-warning: #f59e0b;
            --btn-warning-hover: #d97706;
            --btn-info: #06b6d4;
            --btn-info-hover: #0891b2;
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
        }

        body.day-mode {
            background: var(--bg-day);
            color: var(--text-day);
            font-size: 17px;
        }

        /* ========== CONTENEDOR MÁS ANCHO ========== */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
        }
        
        /* ========== BOTÓN DE TEMA CON MÁS EFECTOS ========== */
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

        .theme-toggle .indicator {
            content: '';
            position: absolute;
            top: 8px;
            right: 8px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            transition: var(--transition);
            animation: blink 2s infinite;
        }

        body.day-mode .theme-toggle .indicator {
            background: #f59e0b;
            box-shadow: 0 0 8px #f59e0b;
        }

        /* ========== ENCABEZADO SIN EFECTO DE LUZ ========== */
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 40px;
            border-radius: var(--border-radius);
            margin-bottom: 40px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            animation: slideDown 0.8s ease-out;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
            animation: rainbow 3s linear infinite;
        }

        body.day-mode .header {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
            box-shadow: var(--shadow-day);
        }

        .header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
            animation: typewriter 2s steps(20) 0.5s 1 normal both;
            overflow: hidden;
            white-space: nowrap;
            line-height: 1.2;
        }

        body.day-mode .header h1 {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header p {
            color: var(--text-light);
            margin-bottom: 1rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            transition: var(--transition);
            font-weight: 500;
            animation: fadeInUp 1s ease 1s both;
            font-size: 1.4rem;
            line-height: 1.5;
        }

        body.day-mode .header p {
            color: var(--text-day);
            text-shadow: none;
        }

        /* ========== ESTADÍSTICAS SIN EFECTO DE LUZ ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
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
            animation: flipInY 0.6s ease both;
            position: relative;
            overflow: hidden;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* QUITADO EL EFECTO DE LUZ DE LAS ESTADÍSTICAS */
        /* .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.05) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        } */

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        body.day-mode .stat-card {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-gold);
            display: block;
            margin-bottom: 8px;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
            animation: countUp 1s ease-out;
            line-height: 1.2;
        }

        body.day-mode .stat-number {
            color: var(--btn-primary);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--text-light);
            font-weight: 500;
            line-height: 1.3;
        }

        body.day-mode .stat-label {
            color: var(--text-day);
        }

        /* ========== BOTONES PRINCIPALES - 2 ARRIBA Y 2 ABAJO ========== */
        .actions-grid-top {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .actions-grid-bottom {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 35px;
        }

        /* ESTILO BASE PARA TODOS LOS BOTONES */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px 30px;
            color: white;
            text-decoration: none;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 2px solid transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            position: relative;
            overflow: hidden;
            text-align: center;
            min-height: 70px;
            width: 100%;
            animation: slideInUp 0.6s ease both;
            background-size: 200% 200%;
        }

        .actions-grid-top .btn:nth-child(1) { animation-delay: 0.4s; }
        .actions-grid-top .btn:nth-child(2) { animation-delay: 0.5s; }
        .actions-grid-bottom .btn:nth-child(1) { animation-delay: 0.6s; }
        .actions-grid-bottom .btn:nth-child(2) { animation-delay: 0.7s; }

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

        .btn::after {
            content: '⚡';
            position: absolute;
            right: 20px;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s;
            font-size: 1.4rem;
        }

        .btn:hover::after {
            opacity: 1;
            transform: translateX(0);
        }
        
        .btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            animation: pulse 0.5s ease;
        }

        .btn:active {
            transform: translateY(-2px) scale(1.02);
        }

        /* BOTÓN SECUNDARIO CON DEGRADADO ESPECTACULAR */
        .btn-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: rgba(102, 126, 234, 0.5);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        /* BOTÓN DE ÉXITO CON DEGRADADO ESPECTACULAR */
        .btn-success {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-color: rgba(240, 147, 251, 0.5);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            box-shadow: 0 15px 30px rgba(245, 87, 108, 0.4);
        }

        /* BOTÓN DE INFORMACIÓN */
        .btn-info-large {
            background: linear-gradient(135deg, var(--btn-info), var(--btn-info-hover));
            border-color: rgba(6, 182, 212, 0.5);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .btn-info-large:hover {
            background: linear-gradient(135deg, var(--btn-info-hover), var(--btn-info));
            box-shadow: 0 15px 30px rgba(6, 182, 212, 0.4);
        }

        /* BOTÓN DE ADVERTENCIA */
        .btn-warning-large {
            background: linear-gradient(135deg, var(--btn-warning), var(--btn-warning-hover));
            border-color: rgba(245, 158, 11, 0.5);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .btn-warning-large:hover {
            background: linear-gradient(135deg, var(--btn-warning-hover), var(--btn-warning));
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.4);
        }

        /* BOTONES PEQUEÑOS PARA ACCIONES EN TABLA - 2 ARRIBA Y 2 ABAJO */
        .actions-cell {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--btn-warning), var(--btn-warning-hover));
            border-color: rgba(245, 158, 11, 0.5);
            padding: 10px 18px;
            font-size: 0.9rem;
            min-height: auto;
            animation: none;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--btn-info), var(--btn-info-hover));
            border-color: rgba(6, 182, 212, 0.5);
            padding: 10px 18px;
            font-size: 0.9rem;
            min-height: auto;
            animation: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--btn-danger), var(--btn-danger-hover));
            border-color: rgba(239, 68, 68, 0.5);
            padding: 10px 18px;
            font-size: 0.9rem;
            min-height: auto;
            animation: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--btn-primary), var(--btn-primary-hover));
            border-color: rgba(59, 130, 246, 0.5);
            padding: 10px 18px;
            font-size: 0.9rem;
            min-height: auto;
            animation: none;
        }

        /* ========== TABLA MÁS GRANDE ========== */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            margin-bottom: 40px;
            animation: zoomIn 0.8s ease-out;
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
            padding: 22px 25px;
            text-align: left;
            font-weight: 600;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            line-height: 1.4;
        }

        th::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 2s infinite;
        }
        
        td {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--text-light);
            transition: var(--transition);
            cursor: default;
            font-size: 1.1rem;
            line-height: 1.4;
        }

        body.day-mode td {
            color: var(--text-day);
        }
        
        tr {
            animation: slideInRight 0.5s ease both;
        }

        tr:nth-child(1) { animation-delay: 0.1s; }
        tr:nth-child(2) { animation-delay: 0.2s; }
        tr:nth-child(3) { animation-delay: 0.3s; }
        tr:nth-child(4) { animation-delay: 0.4s; }
        tr:nth-child(5) { animation-delay: 0.5s; }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        body.day-mode tr:hover {
            background: rgba(0,123,255,0.05);
        }
        
        .no-data {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-light);
            cursor: default;
            animation: bounce 2s infinite;
            font-size: 1.2rem;
        }

        body.day-mode .no-data {
            color: var(--text-day);
        }
        
        .no-data .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
            animation: wobble 2s infinite;
        }
        
        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 6px solid #dc3545;
            box-shadow: 0 4px 15px rgba(220,53,69,0.1);
            cursor: default;
            animation: shake 0.5s ease;
            font-size: 1.1rem;
        }
        
        .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 6px solid #28a745;
            box-shadow: 0 4px 15px rgba(40,167,69,0.1);
            cursor: default;
            animation: slideInRight 0.5s ease;
            font-size: 1.1rem;
        }

        /* ========== ESTILOS ESPECÍFICOS PARA EVENTOS ========== */
        .estado-activo {
            background: linear-gradient(135deg, var(--btn-success), var(--btn-success-hover));
            color: white;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: default;
            transition: var(--transition);
            animation: pulse 2s infinite;
        }

        .estado-activo:hover {
            transform: scale(1.1) rotate(5deg);
            animation: none;
        }
        
        .estado-cerrado {
            background: linear-gradient(135deg, var(--btn-secondary), var(--btn-secondary-hover));
            color: white;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: default;
            transition: var(--transition);
        }

        .estado-cerrado:hover {
            transform: scale(1.1) rotate(5deg);
        }
        
        .evento-id {
            font-weight: 600;
            color: var(--accent-turquoise);
            background: rgba(6, 182, 212, 0.1);
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 1rem;
            cursor: default;
            animation: flipInX 0.6s ease;
        }

        body.day-mode .evento-id {
            background: rgba(59, 130, 246, 0.1);
            color: var(--btn-primary);
        }
        
        .evento-nombre {
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.2rem;
            cursor: default;
            position: relative;
            line-height: 1.3;
        }

        .evento-nombre::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
            transition: width 0.3s ease;
        }

        tr:hover .evento-nombre::after {
            width: 100%;
        }

        body.day-mode .evento-nombre {
            color: var(--text-day);
        }
        
        .evento-fecha {
            color: var(--accent-gold);
            font-weight: 500;
            font-size: 1.1rem;
        }

        body.day-mode .evento-fecha {
            color: var(--btn-primary);
        }
        
        .descripcion {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-light);
            transition: var(--transition);
        }

        body.day-mode .descripcion {
            color: var(--text-day);
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

        /* ========== ANIMACIONES PERSONALIZADAS ========== */
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

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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

        @keyframes flipInY {
            from {
                opacity: 0;
                transform: perspective(400px) rotateY(90deg);
            }
            to {
                opacity: 1;
                transform: perspective(400px) rotateY(0);
            }
        }

        @keyframes flipInX {
            from {
                opacity: 0;
                transform: perspective(400px) rotateX(90deg);
            }
            to {
                opacity: 1;
                transform: perspective(400px) rotateX(0);
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

        @keyframes wobble {
            0%, 100% { transform: translateX(0); }
            15% { transform: translateX(-5px) rotate(-5deg); }
            30% { transform: translateX(4px) rotate(3deg); }
            45% { transform: translateX(-3px) rotate(-3deg); }
            60% { transform: translateX(2px) rotate(2deg); }
            75% { transform: translateX(-1px) rotate(-1deg); }
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

        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .container {
                max-width: 95%;
            }
            
            .actions-grid-top,
            .actions-grid-bottom {
                grid-template-columns: 1fr;
            }
            
            .btn {
                justify-content: center;
                min-height: 65px;
                font-size: 1.1rem;
            }
            
            th, td {
                padding: 15px 18px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .theme-toggle {
                top: 10px;
                right: 10px;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .header h1 {
                font-size: 2.5rem;
            }

            .header p {
                font-size: 1.2rem;
            }

            .actions-cell {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .descripcion {
                max-width: 150px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            body {
                font-size: 15px;
                padding: 15px;
            }

            body.day-mode {
                font-size: 16px;
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
        <div class="indicator"></div>
    </div>

    <div class="container">
        <div class="header">
            <h1>🎉 Gestión de Eventos</h1>
            <p class="info-text">Administra los concursos y eventos de votación del sistema</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success info-text">
                ✅ <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error info-text">
                ❌ <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo count($eventos); ?></span>
                <span class="stat-label">Total Eventos</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">
                    <?php 
                        $activos = array_filter($eventos, function($evento) {
                            return $evento['estado'] == 'Activo';
                        });
                        echo count($activos);
                    ?>
                </span>
                <span class="stat-label">Eventos Activos</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">
                    <?php 
                        $cerrados = array_filter($eventos, function($evento) {
                            return $evento['estado'] == 'Cerrado';
                        });
                        echo count($cerrados);
                    ?>
                </span>
                <span class="stat-label">Eventos Cerrados</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">100%</span>
                <span class="stat-label">Sistema Listo</span>
            </div>
        </div>

        <!-- BOTONES PRINCIPALES - 2 ARRIBA -->
        <div class="actions-grid-top">
            <a href="dashboard.php" class="btn btn-secondary interactive-element">
                ← Volver al Panel
            </a>
            <a href="agregar_evento.php" class="btn btn-success interactive-element">
                ➕ Crear Nuevo Evento
            </a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Evento</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventos) && empty($error)): ?>
                        <tr>
                            <td colspan="6" class="no-data info-text">
                                <span class="icon">📅</span>
                                <h3>No hay eventos creados</h3>
                                <p>Comienza creando el primer evento del sistema</p>
                                <a href="agregar_evento.php" class="btn btn-success interactive-element" style="margin-top: 15px;">
                                    ➕ Crear Primer Evento
                                </a>
                            </td>
                        </tr>
                    <?php elseif (!empty($error)): ?>
                        <tr>
                            <td colspan="6" class="no-data info-text">
                                <span class="icon">❌</span>
                                <h3>Error de conexión</h3>
                                <p>No se pueden cargar los datos en este momento</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td class="info-text">
                                <span class="evento-id">#<?php echo $evento['id']; ?></span>
                            </td>
                            <td class="info-text">
                                <div class="evento-nombre">
                                    <?php echo htmlspecialchars($evento['nombre']); ?>
                                </div>
                            </td>
                            <td class="info-text">
                                <div class="evento-fecha">
                                    <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?>
                                </div>
                            </td>
                            <td class="descripcion info-text" title="<?php echo htmlspecialchars($evento['descripcion']); ?>">
                                <?php echo htmlspecialchars($evento['descripcion']); ?>
                            </td>
                            <td class="info-text">
                                <?php if ($evento['estado'] == 'Activo'): ?>
                                    <span class="estado-activo interactive-element">
                                        ✅ <?php echo $evento['estado']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="estado-cerrado interactive-element">
                                        🔒 <?php echo $evento['estado']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <!-- 2 BOTONES ARRIBA -->
                                    <a href="editar_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-warning interactive-element" title="Editar evento">
                                        ✏️ Editar
                                    </a>
                                    <a href="categorias_evento.php?evento_id=<?php echo $evento['id']; ?>" class="btn btn-info interactive-element" title="Gestionar categorías">
                                        🏷️ Categorías
                                    </a>
                                    <!-- 2 BOTONES ABAJO -->
                                    <a href="participantes_evento.php?evento_id=<?php echo $evento['id']; ?>" class="btn btn-primary interactive-element" title="Gestionar participantes">
                                        👥 Participantes
                                    </a>
                                    <a href="eliminar_evento.php?id=<?php echo $evento['id']; ?>" class="btn btn-danger interactive-element" 
                                       onclick="return confirm('¿Estás seguro de que quieres eliminar el evento \"<?php echo htmlspecialchars(addslashes($evento['nombre'])); ?>\"? Se eliminarán también sus categorías y participantes.')"
                                       title="Eliminar evento">
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
    </div>

    <script>
        // SISTEMA DE CAMBIO DE TEMA - CÓDIGO COMPATIBLE
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

            // EFECTOS INTERACTIVOS MEJORADOS
            var interactiveElements = document.querySelectorAll('.interactive-element');
            for (var i = 0; i < interactiveElements.length; i++) {
                interactiveElements[i].addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.05)';
                });
                interactiveElements[i].addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            }

            // Efecto de carga para filas de la tabla
            var rows = document.querySelectorAll('tbody tr');
            for (var k = 0; k < rows.length; k++) {
                if (rows[k].cells.length > 1) {
                    rows[k].style.animationDelay = (k * 0.1) + 's';
                    rows[k].style.animation = 'slideInRight 0.5s ease both';
                }
            }

            // Efecto de escritura para el título
            var title = document.querySelector('.header h1');
            if (title) {
                title.style.animation = 'typewriter 2s steps(20) 0.5s 1 normal both';
            }

            // Tooltip para descripciones largas
            const descripciones = document.querySelectorAll('.descripcion');
            descripciones.forEach(desc => {
                desc.addEventListener('mouseenter', function() {
                    if (this.scrollWidth > this.clientWidth) {
                        // Mostrar tooltip personalizado si el texto está truncado
                        this.style.cursor = 'help';
                    }
                });
            });
        });
    </script>
</body>

</html>
