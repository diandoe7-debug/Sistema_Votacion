<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Procesar subida de foto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $participante_id = $_POST['participante_id'];
    
    // Configuración
    $directorio_originales = '../uploads/fotos/originales/';
    $directorio_thumbnails = '../uploads/fotos/thumbnails/';
    $directorio_optimized = '../uploads/fotos/optimized/';
    
    // Crear directorios si no existen
    if (!is_dir($directorio_originales)) mkdir($directorio_originales, 0777, true);
    if (!is_dir($directorio_thumbnails)) mkdir($directorio_thumbnails, 0777, true);
    if (!is_dir($directorio_optimized)) mkdir($directorio_optimized, 0777, true);
    
    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $nombre_archivo = "participante_{$participante_id}_" . time() . ".{$extension}";
    
    // Validar tipo de archivo
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($extension, $extensiones_permitidas)) {
        
        // Mover archivo original
        if (move_uploaded_file($archivo['tmp_name'], $directorio_originales . $nombre_archivo)) {
            
            // Crear miniatura (150x150px)
            crear_miniatura($directorio_originales . $nombre_archivo, $directorio_thumbnails . $nombre_archivo, 150);
            
            // Crear versión optimizada (800x600px)
            crear_miniatura($directorio_originales . $nombre_archivo, $directorio_optimized . $nombre_archivo, 800);
            
            // Actualizar base de datos
            $query = "UPDATE participantes SET foto = :foto WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':foto', $nombre_archivo);
            $stmt->bindParam(':id', $participante_id);
            $stmt->execute();
            
            $success = "Foto actualizada exitosamente.";
        }
    } else {
        $error = "Formato de archivo no permitido. Use JPG, PNG o GIF.";
    }
}

function crear_miniatura($origen, $destino, $ancho_maximo) {
    list($ancho_orig, $alto_orig, $tipo) = getimagesize($origen);
    
    switch ($tipo) {
        case IMAGETYPE_JPEG: $imagen = imagecreatefromjpeg($origen); break;
        case IMAGETYPE_PNG: $imagen = imagecreatefrompng($origen); break;
        case IMAGETYPE_GIF: $imagen = imagecreatefromgif($origen); break;
        default: return false;
    }
    
    $ratio = $ancho_orig / $alto_orig;
    $alto_maximo = $ancho_maximo / $ratio;
    
    $miniatura = imagecreatetruecolor($ancho_maximo, $alto_maximo);
    imagecopyresampled($miniatura, $imagen, 0, 0, 0, 0, $ancho_maximo, $alto_maximo, $ancho_orig, $alto_orig);
    
    switch ($tipo) {
        case IMAGETYPE_JPEG: imagejpeg($miniatura, $destino, 85); break;
        case IMAGETYPE_PNG: imagepng($miniatura, $destino, 8); break;
        case IMAGETYPE_GIF: imagegif($miniatura, $destino); break;
    }
    
    imagedestroy($imagen);
    imagedestroy($miniatura);
    return true;
}

// Obtener información del participante para mostrar
$participante = null;
if (isset($_GET['id'])) {
    try {
        $query = "SELECT p.*, e.nombre as evento_nombre FROM participantes p 
                 JOIN eventos e ON p.evento_id = e.id 
                 WHERE p.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        $participante = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al cargar el participante: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Foto - Sistema de Votación</title>
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
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 20px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
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
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            padding: 30px;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            border-bottom: none;
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
            font-size: 2.2rem;
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
        
        .form-container {
            padding: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            border-top: none;
            transition: all 0.4s ease;
        }

        .day-mode .form-container {
            background: var(--glass-bg-day);
            border-color: var(--glass-border-day);
        }

        .night-mode .form-container {
            background: var(--glass-bg-night);
            border-color: var(--glass-border-night);
        }

        .participante-info {
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid var(--primary-day);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
        }

        .day-mode .participante-info {
            background: linear-gradient(135deg, #e7f3ff, #d1ecf1);
        }

        .night-mode .participante-info {
            background: linear-gradient(135deg, #082f49, #0f172a);
            border-left-color: var(--primary-night);
        }
        
        .participante-info h3 {
            margin-bottom: 10px;
            font-size: 1.4rem;
            font-weight: 600;
            transition: all 0.4s ease;
        }

        .day-mode .participante-info h3 {
            color: var(--text-color-day);
        }

        .night-mode .participante-info h3 {
            color: var(--text-super-bright);
        }

        .participante-info p {
            font-weight: 500;
            transition: all 0.4s ease;
        }

        .day-mode .participante-info p {
            color: var(--text-color-day);
        }

        .night-mode .participante-info p {
            color: var(--text-super-bright);
        }

        .foto-section {
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px dashed;
            text-align: center;
            transition: all 0.4s ease;
        }

        .day-mode .foto-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-color: #dee2e6;
        }

        .night-mode .foto-section {
            background: linear-gradient(135deg, rgba(248, 249, 250, 0.1), rgba(233, 236, 239, 0.1));
            border-color: rgba(255, 255, 255, 0.2);
        }

        .foto-preview {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            border-radius: 12px;
            overflow: hidden;
            border: 4px solid var(--primary-day);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .night-mode .foto-preview {
            border-color: var(--primary-night);
        }
        
        .foto-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0,123,255,0.2);
        }
        
        .foto-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .foto-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #6c757d;
            font-size: 3rem;
            transition: all 0.4s ease;
        }

        .day-mode .foto-placeholder {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
        }

        .night-mode .foto-placeholder {
            background: linear-gradient(135deg, rgba(233, 236, 239, 0.2), rgba(222, 226, 230, 0.2));
            color: #cbd5e1;
        }

        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.4s ease;
        }

        .day-mode label {
            color: var(--text-color-day);
        }

        .night-mode label {
            color: var(--text-super-bright);
        }
        
        input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }

        .day-mode input[type="file"] {
            border-color: #e9ecef;
            background: rgba(255,255,255,0.9);
            color: var(--text-color-day);
        }

        .night-mode input[type="file"] {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-super-bright);
        }
        
        input[type="file"]:focus {
            outline: none;
            transform: translateY(-2px);
        }

        .day-mode input[type="file"]:focus {
            border-color: var(--primary-day);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .night-mode input[type="file"]:focus {
            border-color: var(--primary-night);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
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
        
        .success {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid var(--secondary-day);
            box-shadow: 0 4px 15px rgba(40,167,69,0.1);
            transition: all 0.4s ease;
        }

        .day-mode .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .night-mode .success {
            background: linear-gradient(135deg, #0d4a1f, #093318);
            color: #bbf7d0;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .form-help {
            font-size: 0.85rem;
            margin-top: 8px;
            font-style: italic;
            transition: all 0.4s ease;
        }

        .day-mode .form-help {
            color: #6c757d;
        }

        .night-mode .form-help {
            color: #cbd5e1;
        }

        .current-foto-info {
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-weight: 500;
            transition: all 0.4s ease;
        }

        .day-mode .current-foto-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }

        .night-mode .current-foto-info {
            background: linear-gradient(135deg, rgba(209, 236, 241, 0.2), rgba(190, 229, 235, 0.2));
            color: #a5f3fc;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .foto-preview {
                width: 150px;
                height: 150px;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-container {
            animation: fadeIn 0.6s ease-out;
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
            <h1>📷 Subir Foto del Participante</h1>
            <p>Actualice la fotografía del concursante</p>
        </div>

        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error">
                    ❌ <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($participante): ?>
                <div class="participante-info">
                    <h3>👤 <?php echo htmlspecialchars($participante['nombre']); ?></h3>
                    <p><strong>Evento:</strong> <?php echo htmlspecialchars($participante['evento_nombre']); ?> | 
                       <strong>ID:</strong> #<?php echo $participante['id']; ?></p>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="participante_id" value="<?php echo $participante['id']; ?>">
                    
                    <div class="foto-section">
                        <label>📷 Nueva Foto del Participante</label>
                        
                        <div class="foto-preview" id="fotoPreviewContainer">
                            <?php if (!empty($participante['foto']) && file_exists('../uploads/fotos/originales/' . $participante['foto'])): ?>
                                <img src="../uploads/fotos/originales/<?php echo $participante['foto']; ?>" 
                                     alt="<?php echo htmlspecialchars($participante['nombre']); ?>"
                                     id="fotoPreview">
                            <?php else: ?>
                                <div class="foto-placeholder" id="fotoPlaceholder">
                                    👤
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($participante['foto'])): ?>
                            <div class="current-foto-info">
                                ✅ Foto actual cargada
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <input type="file" id="foto" name="foto" accept="image/*" onchange="previewImage(this)" required>
                            <div class="form-help">Formatos: JPG, PNG, GIF (Máx. 5MB). Se crearán versiones optimizadas automáticamente.</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="editar_participante.php?id=<?php echo $participante['id']; ?>" class="btn btn-secondary">
                            ← Volver
                        </a>
                        <button type="submit" class="btn btn-success">
                            💾 Subir Foto
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="error">
                    ❌ No se puede cargar la información del participante.
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="participantes.php" class="btn btn-primary">
                        📋 Volver a la gestión de participantes
                    </a>
                </div>
            <?php endif; ?>
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

        function previewImage(input) {
            const preview = document.getElementById('fotoPreview');
            const placeholder = document.getElementById('fotoPlaceholder');
            const container = document.getElementById('fotoPreviewContainer');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Ocultar placeholder si existe
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    // Crear o actualizar la imagen de preview
                    if (!preview) {
                        const newPreview = document.createElement('img');
                        newPreview.id = 'fotoPreview';
                        newPreview.src = e.target.result;
                        newPreview.style.width = '100%';
                        newPreview.style.height = '100%';
                        newPreview.style.objectFit = 'cover';
                        container.appendChild(newPreview);
                    } else {
                        preview.src = e.target.result;
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Efectos en inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                    if (document.body.classList.contains('day-mode')) {
                        this.style.boxShadow = '0 8px 25px rgba(0,123,255,0.15)';
                    } else {
                        this.style.boxShadow = '0 8px 25px rgba(6, 182, 212, 0.15)';
                    }
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Prevenir envío duplicado
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '⏳ Subiendo...';
                        submitBtn.disabled = true;
                    }
                });
            }
        });
    </script>
</body>
</html>