<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once 'C:/xampp/htdocs/votacion/src/config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Obtener datos del evento a editar
$evento = null;
if (isset($_GET['id'])) {
    try {
        $query = "SELECT id, nombre, fecha, descripcion, estado FROM eventos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            // Formatear fecha para el input
            $evento['fecha'] = date('Y-m-d', strtotime($evento['fecha']));
        } else {
            $error = "Evento no encontrado.";
        }
    } catch (PDOException $e) {
        $error = "Error al cargar el evento: " . $e->getMessage();
    }
} else {
    $error = "ID de evento no especificado.";
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $evento) {
    $nombre = trim($_POST['nombre']);
    $fecha = $_POST['fecha'];
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'];

    // Validaciones
    if (empty($nombre) || empty($fecha) || empty($descripcion)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            // Verificar si el nombre ya existe en otro evento
            $query = "SELECT id FROM eventos WHERE nombre = :nombre AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $evento['id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Ya existe un evento con ese nombre.";
            } else {
                // Actualizar evento
                $query = "UPDATE eventos SET nombre = :nombre, fecha = :fecha, descripcion = :descripcion, estado = :estado WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':estado', $estado);
                $stmt->bindParam(':id', $evento['id']);
                
                if ($stmt->execute()) {
                    $success = "Evento actualizado exitosamente.";
                    // Actualizar datos locales
                    $evento['nombre'] = $nombre;
                    $evento['fecha'] = $fecha;
                    $evento['descripcion'] = $descripcion;
                    $evento['estado'] = $estado;
                } else {
                    $error = "Error al actualizar el evento.";
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
    <title>Editar Evento - Sistema de Votación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');

        :root {
            --primary-dark: #0f172a;
            --accent-gold: #fbbf24;
            --accent-pink: #ec4899;
            --accent-turquoise: #06b6d4;
            --accent-purple: #8b5cf6;
            --text-light: #f8fafc;
            --text-dark: #000000;
            --border-radius: 16px;
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.95)), url('./../../assets/img/2.jpg') center/cover fixed;
            color: var(--text-light);
            min-height: 100vh;
            padding: 20px;
            transition: var(--transition);
        }

        body.day-mode {
            background: url('./../../assets/img/1.jpg') center/cover fixed;
            color: var(--text-dark);
        }

        /* Botón tema */
        .theme-toggle {
            position: fixed;
            top: 25px;
            right: 25px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
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
        }

        body.day-mode .theme-toggle { background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(0,0,0,0.2); }
        .theme-toggle:hover { transform: scale(1.1); }

        .sun { display: none; color: #000000; }
        .moon { display: block; color: #ffffff; }
        body.day-mode .sun { display: block; }
        body.day-mode .moon { display: none; }

        /* Contenido */
        .container { max-width: 800px; margin: 0 auto; }

        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        body.day-mode .header { background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0,0,0,0.1); }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--accent-gold), var(--accent-pink), var(--accent-purple));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .header p { font-weight: 500; font-size: 1.1rem; }

        /* Formulario */
        .form-container {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            margin-bottom: 30px;
            animation: slideIn 0.6s ease-out;
        }

        body.day-mode .form-container { background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0,0,0,0.1); }

        .current-info {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(139, 92, 246, 0.2));
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 5px solid var(--accent-turquoise);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
        }

        body.day-mode .current-info { 
            background: linear-gradient(135deg, #e7f3ff, #d1ecf1); 
            border-left: 5px solid #007bff;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 1rem;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--glass-border);
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
        }

        body.day-mode input[type="text"],
        body.day-mode input[type="date"],
        body.day-mode select,
        body.day-mode textarea {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0,0,0,0.1);
            color: var(--text-dark);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent-turquoise);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }

        /* Botones */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--accent-turquoise), var(--accent-purple));
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
            border: 2px solid rgba(255,255,255,0.3);
            cursor: pointer;
        }

        body.day-mode .btn { color: #000000; border: 2px solid rgba(0,0,0,0.2); }
        .btn:hover { transform: translateY(-3px); }

        .btn-warning { background: linear-gradient(135deg, var(--accent-gold), #d97706); }
        .btn-secondary { background: linear-gradient(135deg, #6c757d, #5a6268); }

        /* Mensajes */
        .success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(34, 139, 34, 0.3));
            color: #d4edda;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 5px solid #28a745;
        }

        .error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(178, 34, 34, 0.3));
            color: #f8d7da;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 5px solid #dc3545;
        }

        /* Acciones del formulario */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .left-actions, .right-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Detalles del evento */
        .evento-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        body.day-mode .detail-item { background: rgba(255, 255, 255, 0.7); }

        .detail-label {
            font-size: 0.8rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .detail-value {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Nota del formulario */
        .form-note {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(236, 72, 153, 0.2));
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 25px;
            border-left: 4px solid var(--accent-gold);
        }

        body.day-mode .form-note { background: #fff3cd; }

        .form-note h4 {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-note ul {
            margin-left: 20px;
            line-height: 1.6;
        }

        .form-note li {
            margin-bottom: 5px;
        }

        /* Contador de caracteres */
        .character-count {
            text-align: right;
            font-size: 0.8rem;
            margin-top: 5px;
            opacity: 0.7;
        }

        .character-count.warning { color: var(--accent-gold); }
        .character-count.danger { color: #dc3545; }

        .form-help {
            font-size: 0.85rem;
            margin-top: 8px;
            opacity: 0.8;
            font-style: italic;
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

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 1.8rem; }
            .form-actions { flex-direction: column; }
            .left-actions, .right-actions { justify-content: center; width: 100%; }
            .btn { justify-content: center; min-width: 140px; }
            .evento-details { grid-template-columns: 1fr; }
            .theme-toggle { top: 15px; right: 15px; width: 50px; height: 50px; }
            .form-container { padding: 25px; }
        }
    </style>
</head>
<body class="night-mode">
    <div class="theme-toggle" id="themeToggle">
        <span class="theme-icon sun"><i class="far fa-sun" style="color: #000000;"></i></span>
        <span class="theme-icon moon"><i class="fas fa-moon" style="color: #ffffff;"></i></span>
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Editar Evento</h1>
            <p>Modifique los detalles del evento de votación</p>
        </div>

        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($evento): ?>
                <div class="current-info">
                    <strong><i class="fas fa-calendar-star"></i> Editando Evento:</strong><br>
                    <span style="font-size: 1.2rem; font-weight: 600;">
                        <?php echo htmlspecialchars($evento['nombre']); ?>
                    </span>
                    
                    <div class="evento-details">
                        <div class="detail-item">
                            <div class="detail-label">ID del Evento</div>
                            <div class="detail-value">#<?php echo $evento['id']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Estado Actual</div>
                            <div class="detail-value">
                                <?php if ($evento['estado'] == 'Activo'): ?>
                                    <span style="color: #10b981;"><i class="fas fa-play-circle"></i> Activo</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;"><i class="fas fa-lock"></i> Cerrado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Fecha Original</div>
                            <div class="detail-value"><?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="" id="editarEventoForm">
                    <div class="form-group">
                        <label for="nombre"><i class="fas fa-pencil-alt"></i> Nombre del Evento:</label>
                        <input type="text" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($evento['nombre']); ?>" 
                               required placeholder="Ej: Reina del Colegio 2024 - Elección Estudiantil">
                        <div class="form-help">Nombre descriptivo que identifique claramente el evento</div>
                    </div>

                    <div class="form-group">
                        <label for="fecha"><i class="fas fa-calendar-alt"></i> Fecha del Evento:</label>
                        <input type="date" id="fecha" name="fecha" 
                               value="<?php echo $evento['fecha']; ?>" required>
                        <div class="form-help">Fecha en la que se realizará o culminará el evento</div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion"><i class="fas fa-file-alt"></i> Descripción del Evento:</label>
                        <textarea id="descripcion" name="descripcion" required 
                                  placeholder="Describa el propósito, reglas y detalles importantes del evento..."
                                  oninput="updateCharacterCount(this)"><?php echo htmlspecialchars($evento['descripcion']); ?></textarea>
                        <div class="character-count" id="charCount"><?php echo strlen($evento['descripcion']); ?>/500 caracteres</div>
                        <div class="form-help">Información detallada sobre el evento, criterios de evaluación, etc.</div>
                    </div>

                    <div class="form-group">
                        <label for="estado"><i class="fas fa-bolt"></i> Estado del Evento:</label>
                        <select id="estado" name="estado" required>
                            <option value="Activo" <?php echo ($evento['estado'] == 'Activo') ? 'selected' : ''; ?>>
                                <i class="fas fa-play-circle"></i> Activo - Puede recibir votos
                            </option>
                            <option value="Cerrado" <?php echo ($evento['estado'] == 'Cerrado') ? 'selected' : ''; ?>>
                                <i class="fas fa-lock"></i> Cerrado - No puede recibir votos
                            </option>
                        </select>
                        <div class="form-help">Los eventos "Activos" permiten a los jurados emitir votos</div>
                    </div>

                    <div class="form-note">
                        <h4><i class="fas fa-lightbulb"></i> Información importante:</h4>
                        <ul>
                            <li>Los cambios se aplicarán inmediatamente a todos los usuarios</li>
                            <li>Al cambiar el estado a "Cerrado", los jurados no podrán votar</li>
                            <li>Los resultados existentes se mantendrán intactos</li>
                            <li>Revise cuidadosamente los datos antes de guardar</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <div class="left-actions">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Panel Principal
                            </a>
                            <a href="eventos.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Lista de Eventos
                            </a>
                        </div>
                        <div class="right-actions">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> No se puede cargar la información del evento.
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <a href="eventos.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Volver a la lista de eventos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tema claro/oscuro
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'day') body.classList.add('day-mode');
            
            themeToggle.addEventListener('click', function() {
                body.classList.toggle('day-mode');
                localStorage.setItem('theme', body.classList.contains('day-mode') ? 'day' : 'night');
            });

            // Inicializar contador de caracteres
            updateCharacterCount(document.getElementById('descripcion'));
        });

        // Contador de caracteres para la descripción
        function updateCharacterCount(textarea) {
            const charCount = textarea.value.length;
            const charCountElement = document.getElementById('charCount');
            charCountElement.textContent = `${charCount}/500 caracteres`;
            
            // Cambiar color según la cantidad de caracteres
            if (charCount > 400) {
                charCountElement.className = 'character-count danger';
            } else if (charCount > 300) {
                charCountElement.className = 'character-count warning';
            } else {
                charCountElement.className = 'character-count';
            }
        }

        // Validación del formulario antes de enviar
        document.getElementById('editarEventoForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            
            if (nombre.length < 5) {
                e.preventDefault();
                alert('❌ El nombre del evento debe tener al menos 5 caracteres');
                document.getElementById('nombre').focus();
                return false;
            }
            
            if (descripcion.length < 10) {
                e.preventDefault();
                alert('❌ La descripción debe tener al menos 10 caracteres');
                document.getElementById('descripcion').focus();
                return false;
            }
            
            if (descripcion.length > 500) {
                e.preventDefault();
                alert('❌ La descripción no puede exceder los 500 caracteres');
                document.getElementById('descripcion').focus();
                return false;
            }
            
            // Confirmación antes de guardar cambios
            if (!confirm('¿Está seguro de que desea guardar los cambios en este evento?')) {
                e.preventDefault();
                return false;
            }
        });

        // Efecto de focus mejorado
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Validación en tiempo real del nombre
        const nombreInput = document.getElementById('nombre');
        nombreInput.addEventListener('input', function() {
            if (this.value.length >= 5) {
                this.style.borderColor = '#10b981';
            } else {
                this.style.borderColor = '';
            }
        });

        // Inicializar validación visual del nombre
        if (nombreInput.value.length >= 5) {
            nombreInput.style.borderColor = '#10b981';
        }
    </script>
</body>
</html>