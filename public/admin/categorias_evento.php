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

// Obtener información del evento
if (isset($_GET['evento_id'])) {
    $evento_id = $_GET['evento_id'];
    
    try {
        // Obtener datos del evento
        $query = "SELECT id, nombre, fecha FROM eventos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $evento_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener categorías del evento
            $query = "SELECT id, nombre, puntaje_maximo FROM categorias WHERE evento_id = :evento_id ORDER BY id";
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías del Evento - Sistema de Votación</title>
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
            --text-light: #ffffff; /* BLANCO PURO para modo noche */
            --text-gray: #e2e8f0;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            
            /* VARIABLES PARA FONDOS CON IMÁGENES */
            --bg-day: url('./../../assets/img/1.jpg') center/cover fixed;
            --bg-night: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('./../../assets/img/2.jpg') center/cover fixed;
            --text-day: #000000; /* NEGRO PURO para modo día */
            --text-gray-day: #2d3748; /* GRIS OSCURO para modo día */
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
            color: var(--text-light); /* TEXTO BLANCO en modo noche */
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            transition: var(--transition);
            overflow-x: hidden;
            font-size: 16px; /* TAMAÑO BASE MÁS GRANDE */
        }

        body.day-mode {
            background: var(--bg-day);
            color: var(--text-day); /* TEXTO NEGRO en modo día */
            font-size: 17px; /* TEXTO UN POCO MÁS GRANDE EN MODO DÍA */
        }

        /* ========== CONTENEDOR MÁS ANCHO ========== */
        .container {
            max-width: 1400px; /* CONTENEDOR MÁS ANCHO */
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
            width: 60px; /* MÁS GRANDE */
            height: 60px; /* MÁS GRANDE */
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
            z-index: 1000;
            overflow: hidden;
            animation: float 3s ease-in-out infinite;
            font-size: 1.8rem; /* ICONO MÁS GRANDE */
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
            font-size: 1.8rem; /* ICONO MÁS GRANDE */
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
            font-size: 0.9rem; /* TEXTO MÁS GRANDE */
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

        /* ========== ENCABEZADO CON EFECTO MÁQUINA DE ESCRIBIR ========== */
        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 40px; /* MÁS ESPACIADO */
            border-radius: var(--border-radius);
            margin-bottom: 40px; /* MÁS ESPACIADO */
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
            height: 5px; /* MÁS GRUESO */
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
            animation: rainbow 3s linear infinite;
        }

        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }

        body.day-mode .header {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
            box-shadow: var(--shadow-day);
        }

        .header h1 {
            font-size: 3.5rem; /* TÍTULO MÁS GRANDE */
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
            font-size: 1.4rem; /* DESCRIPCIÓN MÁS GRANDE */
            line-height: 1.5;
        }

        body.day-mode .header p {
            color: var(--text-day); /* NEGRO en modo día */
            text-shadow: none;
        }
        
        /* ========== INFORMACIÓN DEL EVENTO ========== */
        .evento-info {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 35px; /* MÁS ESPACIADO */
            border-radius: var(--border-radius);
            margin-bottom: 35px; /* MÁS ESPACIADO */
            box-shadow: var(--shadow);
            border-left: 6px solid var(--accent-turquoise); /* MÁS GRUESO */
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

        .evento-info h3 {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 2rem; /* MÁS GRANDE */
            font-weight: 600;
            animation: bounceIn 1s ease;
            line-height: 1.3;
        }

        body.day-mode .evento-info h3 {
            color: var(--text-day); /* NEGRO en modo día */
        }
        
        .evento-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* MÁS ANCHO */
            gap: 20px; /* MÁS ESPACIADO */
            margin-top: 20px;
        }
        
        .detail-item {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 20px; /* MÁS ESPACIADO */
            border-radius: 12px; /* MÁS REDONDEADO */
            text-align: center;
            transition: var(--transition);
            animation: zoomIn 0.6s ease both;
            position: relative;
            overflow: hidden;
        }

        .detail-item:nth-child(1) { animation-delay: 0.1s; }
        .detail-item:nth-child(2) { animation-delay: 0.2s; }
        .detail-item:nth-child(3) { animation-delay: 0.3s; }

        .detail-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .detail-item:hover::before {
            left: 100%;
        }

        .detail-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        body.day-mode .detail-item {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }
        
        .detail-label {
            font-size: 1rem; /* MÁS GRANDE */
            color: var(--text-light);
            margin-bottom: 8px;
            font-weight: 500;
        }

        body.day-mode .detail-label {
            color: var(--text-day); /* NEGRO en modo día */
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.4rem; /* MÁS GRANDE */
            line-height: 1.3;
        }

        body.day-mode .detail-value {
            color: var(--text-day); /* NEGRO en modo día */
        }
        
        /* ========== BOTONES PRINCIPALES CON EFECTOS MEJORADOS ========== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px; /* MÁS ESPACIADO */
            margin-bottom: 35px; /* MÁS ESPACIADO */
        }

        /* ESTILO BASE PARA TODOS LOS BOTONES */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px; /* MÁS ESPACIADO */
            padding: 20px 30px; /* MÁS ESPACIADO */
            color: white;
            text-decoration: none;
            border-radius: 15px; /* MÁS REDONDEADO */
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 2px solid transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem; /* TEXTO MÁS GRANDE */
            position: relative;
            overflow: hidden;
            text-align: center;
            min-height: 70px; /* MÁS ALTO */
            width: 100%;
            animation: slideInUp 0.6s ease both;
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .btn:nth-child(1) { animation-delay: 0.4s; }
        .btn:nth-child(2) { animation-delay: 0.5s; }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
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
        
        /* ========== ESTADÍSTICAS ========== */
        .stats-summary {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            padding: 35px; /* MÁS ESPACIADO */
            border-radius: var(--border-radius);
            margin-bottom: 35px; /* MÁS ESPACIADO */
            box-shadow: var(--shadow);
            transition: var(--transition);
            animation: slideInRight 0.8s ease-out;
        }

        body.day-mode .stats-summary {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* MÁS ANCHO */
            gap: 20px; /* MÁS ESPACIADO */
        }
        
        .stat-item {
            text-align: center;
            padding: 25px; /* MÁS ESPACIADO */
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 12px; /* MÁS REDONDEADO */
            transition: var(--transition);
            animation: flipInY 0.6s ease both;
            position: relative;
            overflow: hidden;
        }

        .stat-item:nth-child(1) { animation-delay: 0.6s; }
        .stat-item:nth-child(2) { animation-delay: 0.7s; }
        .stat-item:nth-child(3) { animation-delay: 0.8s; }
        .stat-item:nth-child(4) { animation-delay: 0.9s; }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.05) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        .stat-item:hover {
            transform: translateY(-5px) rotate(1deg);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        body.day-mode .stat-item {
            background: var(--glass-bg-day);
            border: 1px solid var(--glass-border-day);
        }
        
        .stat-number {
            font-size: 2.5rem; /* MÁS GRANDE */
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
            font-size: 1rem; /* MÁS GRANDE */
            color: var(--text-light);
            font-weight: 500;
            line-height: 1.3;
        }

        body.day-mode .stat-label {
            color: var(--text-day); /* NEGRO en modo día */
        }
        
        /* ========== TABLA MÁS GRANDE ========== */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            margin-bottom: 40px; /* MÁS ESPACIADO */
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
            padding: 22px 25px; /* MÁS ESPACIADO */
            text-align: left;
            font-weight: 600;
            font-size: 1.1rem; /* MÁS GRANDE */
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
            padding: 20px 25px; /* MÁS ESPACIADO */
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--text-light);
            transition: var(--transition);
            cursor: default;
            font-size: 1.1rem; /* MÁS GRANDE */
            line-height: 1.4;
        }

        body.day-mode td {
            color: var(--text-day); /* NEGRO en modo día */
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
            padding: 80px 20px; /* MÁS ESPACIADO */
            color: var(--text-light);
            cursor: default;
            animation: bounce 2s infinite;
            font-size: 1.2rem; /* MÁS GRANDE */
        }

        body.day-mode .no-data {
            color: var(--text-day); /* NEGRO en modo día */
        }
        
        .no-data .icon {
            font-size: 4rem; /* MÁS GRANDE */
            margin-bottom: 20px;
            display: block;
            animation: wobble 2s infinite;
        }
        
        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            padding: 25px; /* MÁS ESPACIADO */
            border-radius: 12px; /* MÁS REDONDEADO */
            margin-bottom: 30px; /* MÁS ESPACIADO */
            border-left: 6px solid #dc3545; /* MÁS GRUESO */
            box-shadow: 0 4px 15px rgba(220,53,69,0.1);
            cursor: default;
            animation: shake 0.5s ease;
            font-size: 1.1rem; /* MÁS GRANDE */
        }
        
        .puntaje-badge {
            background: linear-gradient(135deg, var(--btn-info), var(--btn-info-hover));
            color: white;
            padding: 10px 18px; /* MÁS ESPACIADO */
            border-radius: 25px; /* MÁS REDONDEADO */
            font-size: 1rem; /* MÁS GRANDE */
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: default;
            transition: var(--transition);
            animation: pulse 2s infinite;
        }

        .puntaje-badge:hover {
            transform: scale(1.1) rotate(5deg);
            animation: none;
        }
        
        .categoria-id {
            font-weight: 600;
            color: var(--accent-turquoise);
            background: rgba(6, 182, 212, 0.1);
            padding: 8px 14px; /* MÁS ESPACIADO */
            border-radius: 10px; /* MÁS REDONDEADO */
            font-size: 1rem; /* MÁS GRANDE */
            cursor: default;
            animation: flipInX 0.6s ease;
        }

        body.day-mode .categoria-id {
            background: rgba(59, 130, 246, 0.1);
            color: var(--btn-primary);
        }
        
        .categoria-nombre {
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.2rem; /* MÁS GRANDE */
            cursor: default;
            position: relative;
            line-height: 1.3;
        }

        .categoria-nombre::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 3px; /* MÁS GRUESO */
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
            transition: width 0.3s ease;
        }

        tr:hover .categoria-nombre::after {
            width: 100%;
        }

        body.day-mode .categoria-nombre {
            color: var(--text-day); /* NEGRO en modo día */
        }
        
        .footer-actions {
            text-align: center;
            margin-top: 30px; /* MÁS ESPACIADO */
        }

        /* BOTÓN DEL FOOTER MÁS GRANDE */
        .footer-actions .btn {
            min-width: 300px; /* MÁS ANCHO */
            font-size: 1.3rem; /* MÁS GRANDE */
            padding: 22px 35px; /* MÁS ESPACIADO */
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .container {
                max-width: 95%; /* MÁS ANCHO EN MÓVIL */
            }
            
            .actions-grid {
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
            
            .evento-details {
                grid-template-columns: 1fr;
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

        .table-container {
            animation: fadeIn 0.6s ease-out;
        }

        /* ========== INDICADORES VISUALES ========== */
        .btn::after {
            content: '↗';
            font-size: 0.9em;
            margin-left: 4px;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .btn:hover::after {
            opacity: 1;
        }

        .info-text {
            cursor: default;
            user-select: text;
        }

        .interactive-element {
            cursor: pointer;
            user-select: none;
        }

        /* PARTÍCULAS FLOTANTES */
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
            <h1>Categorías del Evento</h1>
            <p class="info-text">Lista de criterios de evaluación configurados para este evento</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error info-text">
                ❌ <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($evento): ?>
            <div class="evento-info">
                <h3>🎯 <?php echo htmlspecialchars($evento['nombre']); ?></h3>
                <div class="evento-details">
                    <div class="detail-item">
                        <div class="detail-label">📅 Fecha del Evento</div>
                        <div class="detail-value"><?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">🆔 ID del Evento</div>
                        <div class="detail-value">#<?php echo $evento['id']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">🏷️ Total Categorías</div>
                        <div class="detail-value"><?php echo count($categorias); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($categorias)): ?>
                <div class="stats-summary">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($categorias); ?></span>
                            <span class="stat-label">Total Categorías</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php 
                                    $totalPuntaje = array_sum(array_column($categorias, 'puntaje_maximo'));
                                    echo $totalPuntaje;
                                ?>
                            </span>
                            <span class="stat-label">Puntaje Total</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php 
                                    $maxPuntaje = max(array_column($categorias, 'puntaje_maximo'));
                                    echo $maxPuntaje;
                                ?>
                            </span>
                            <span class="stat-label">Puntaje Máx. Individual</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?php 
                                    $avgPuntaje = count($categorias) > 0 ? $totalPuntaje / count($categorias) : 0;
                                    echo round($avgPuntaje, 1);
                                ?>
                            </span>
                            <span class="stat-label">Promedio por Categoría</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- BOTONES PRINCIPALES - 2 ARRIBA -->
            <div class="actions-grid">
                <a href="eventos.php" class="btn btn-secondary interactive-element">
                    ← Volver a Eventos
                </a>
                <a href="categorias.php" class="btn btn-success interactive-element">
                    ⚙️ Gestionar Todas las Categorías
                </a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de la Categoría</th>
                            <th>Puntaje Máximo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="3" class="no-data info-text">
                                    <span class="icon">📝</span>
                                    <h3>Este evento no tiene categorías</h3>
                                    <p>Configura las categorías de evaluación para este evento</p>
                                    <a href="categorias.php" class="btn btn-success interactive-element" style="margin-top: 15px;">
                                        ⚙️ Configurar Categorías
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td class="info-text">
                                    <span class="categoria-id">#<?php echo $categoria['id']; ?></span>
                                </td>
                                <td class="info-text">
                                    <div class="categoria-nombre">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </div>
                                </td>
                                <td class="info-text">
                                    <span class="puntaje-badge">
                                        ⭐ <?php echo $categoria['puntaje_maximo']; ?> puntos
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- BOTÓN INFERIOR - SOLO 1 (Manteniendo el original) -->
            <div class="footer-actions">
                <a href="eventos.php" class="btn btn-secondary interactive-element">
                    ← Volver a la Lista de Eventos
                </a>
            </div>

        <?php else: ?>
            <div style="text-align: center; margin-top: 25px;">
                <a href="eventos.php" class="btn btn-secondary interactive-element">
                    ← Volver a Eventos
                </a>
            </div>
        <?php endif; ?>
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

            // Efectos en badges de puntaje
            var puntajeBadges = document.querySelectorAll('.puntaje-badge');
            for (var j = 0; j < puntajeBadges.length; j++) {
                puntajeBadges[j].addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.2) rotate(10deg)';
                });
                puntajeBadges[j].addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1) rotate(0)';
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
        });
    </script>
</body>
</html>