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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $fecha = $_POST['fecha'];
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'];

    // Validaciones
    if (empty($nombre) || empty($fecha) || empty($descripcion)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            // Verificar si ya existe un evento con el mismo nombre
            $query = "SELECT id FROM eventos WHERE nombre = :nombre";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Ya existe un evento con ese nombre.";
            } else {
                // Insertar nuevo evento
                $query = "INSERT INTO eventos (nombre, fecha, descripcion, estado) VALUES (:nombre, :fecha, :descripcion, :estado)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':fecha', $fecha);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':estado', $estado);
                
                if ($stmt->execute()) {
                    // Redirigir a la lista de eventos después de agregar
                    header("Location: eventos.php?success=1");
                    exit();
                } else {
                    $error = "Error al crear el evento.";
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
    <title>Crear Evento - Sistema de Votación</title>
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
            max-width: 700px;
            margin: 0 auto;
        }

        .header {
            padding: 30px;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-day);
            border-bottom: none;
            text-align: center;
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
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.4s ease;
        }

        .day-mode label {
            color: #2c3e50;
        }

        .night-mode label {
            color: var(--text-super-bright);
        }
        
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }

        .day-mode input[type="text"],
        .day-mode input[type="date"],
        .day-mode select,
        .day-mode textarea {
            border-color: #e9ecef;
            background: rgba(121, 173, 233, 0.47);
            color: #2c3e50;
        }

        .night-mode input[type="text"],
        .night-mode input[type="date"],
        .night-mode select,
        .night-mode textarea {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-super-bright);
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            transform: translateY(-2px);
        }

        .day-mode input:focus,
        .day-mode select:focus,
        .day-mode textarea:focus {
            border-color: var(--primary-day);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
            background: white;
        }

        .night-mode input:focus,
        .night-mode select:focus,
        .night-mode textarea:focus {
            border-color: var(--primary-night);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            color: #000000;
            text-decoration: none;
            border: none;
            border-radius: 10px;
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
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.3), rgba(195, 230, 203, 0.3));
            color: #bbf7d0;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .left-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .right-actions {
            display: flex;
            gap: 10px;
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
        
        .form-note {
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            border-left: 4px solid var(--primary-day);
            transition: all 0.4s ease;
        }

        .day-mode .form-note {
            background: #e7f3ff;
        }

        .night-mode .form-note {
            background: rgba(30, 41, 59, 0.8);
            border-left-color: var(--primary-night);
        }
        
        .form-note h4 {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s ease;
        }

        .day-mode .form-note h4 {
            color: #0056b3;
        }

        .night-mode .form-note h4 {
            color: var(--primary-night);
        }
        
        .form-note ul {
            margin-left: 20px;
            line-height: 1.6;
            transition: all 0.4s ease;
        }

        .day-mode .form-note ul {
            color: #495057;
        }

        .night-mode .form-note ul {
            color: #cbd5e1;
        }
        
        .form-note li {
            margin-bottom: 5px;
        }
        
        .character-count {
            text-align: right;
            font-size: 0.8rem;
            margin-top: 5px;
            transition: all 0.4s ease;
        }

        .day-mode .character-count {
            color: #6c757d;
        }

        .night-mode .character-count {
            color: #94a3b8;
        }
        
        .character-count.warning {
            color: var(--warning-day);
        }
        
        .character-count.danger {
            color: var(--danger-day);
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .left-actions, .right-actions {
                justify-content: center;
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
                min-width: 140px;
            }

            .form-container {
                padding: 25px;
            }
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
        
        .form-container {
            animation: slideIn 0.6s ease-out;
        }
        
        .estado-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
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

        .night-mode small {
            color: #cbd5e1;
        }

        /* Placeholder styling */
        .night-mode ::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .night-mode select option {
            background: var(--card-bg-night);
            color: var(--text-super-bright);
        }
    </style>
</head>
<body class="night-mode">

    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    
    <div class="container">
        <div class="header">
            <h1> Crear Nuevo Evento</h1>
            <p>Configure los detalles del nuevo concurso o evento de votación</p>
        </div>

        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="eventoForm">
                <div class="form-group">
                    <label for="nombre">📝 Nombre del Evento:</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" 
                           required placeholder="Ej: Reina del Colegio 2024 - Elección Estudiantil">
                    <div class="form-help">Nombre descriptivo que identifique claramente el evento</div>
                </div>

                <div class="form-group">
                    <label for="fecha">📅 Fecha del Evento:</label>
                    <input type="date" id="fecha" name="fecha" 
                           value="<?php echo isset($fecha) ? $fecha : ''; ?>" required>
                    <div class="form-help">Fecha en la que se realizará o culminará el evento (puede ser cualquier fecha desde el año 2000)</div>
                </div>

                <div class="form-group">
                    <label for="descripcion">📋 Descripción del Evento:</label>
                    <textarea id="descripcion" name="descripcion" required 
                              placeholder="Describa el propósito, reglas y detalles importantes del evento..."
                              oninput="updateCharacterCount(this)"><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>
                    <div class="character-count" id="charCount">0/500 caracteres</div>
                    <div class="form-help">Información detallada sobre el evento, criterios de evaluación, etc.</div>
                </div>

                <div class="form-group">
                    <label for="estado">⚡ Estado del Evento:</label>
                    <select id="estado" name="estado" required>
                        <option value="Activo" <?php echo (isset($estado) && $estado == 'Activo') ? 'selected' : ''; ?>>
                            ✅ Activo - Puede recibir votos
                        </option>
                        <option value="Cerrado" <?php echo (isset($estado) && $estado == 'Cerrado') ? 'selected' : ''; ?>>
                            🔒 Cerrado - No puede recibir votos
                        </option>
                    </select>
                    <div class="form-help">Los eventos "Activos" permiten a los jurados emitir votos</div>
                </div>

                <div class="form-note">
                    <h4>💡 Información importante:</h4>
                    <ul>
                        <li>Después de crear el evento, podrá agregar categorías y participantes</li>
                        <li>Los eventos "Activos" aparecerán en el panel de los jurados</li>
                        <li>Puede cambiar el estado del evento en cualquier momento</li>
                        <li>Configure las categorías antes de agregar participantes</li>
                        <li>Puede seleccionar cualquier fecha desde el año 2000 en adelante</li>
                    </ul>
                </div>

                <div class="form-actions">
                    <div class="left-actions">
                        <a href="dashboard.php" class="btn btn-secondary">
                            🏠 Panel Principal
                        </a>
                        <a href="eventos.php" class="btn btn-secondary">
                            📋 Lista de Eventos
                        </a>
                    </div>
                    <div class="right-actions">
                        <button type="submit" class="btn btn-success">
                            ✅ Crear Evento
                        </button>
                    </div>
                </div>
            </form>
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

        // Establecer fecha mínima como 1 de enero del 2000
        const minDate = '2000-01-01';
        document.getElementById('fecha').min = minDate;
        
        // Establecer fecha por defecto en 7 días si no hay valor
        const fechaInput = document.getElementById('fecha');
        if (!fechaInput.value) {
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            fechaInput.value = defaultDate.toISOString().split('T')[0];
        }

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

        // Inicializar contador de caracteres
        const descripcionTextarea = document.getElementById('descripcion');
        updateCharacterCount(descripcionTextarea);

        // Validación del formulario antes de enviar
        document.getElementById('eventoForm').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const descripcion = document.getElementById('descripcion').value.trim();
            const fecha = document.getElementById('fecha').value;
            
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
            
            // Validar que la fecha no sea anterior al 2000
            const selectedDate = new Date(fecha);
            const minAllowedDate = new Date('2000-01-01');
            if (selectedDate < minAllowedDate) {
                e.preventDefault();
                alert('❌ La fecha no puede ser anterior al 1 de enero del 2000');
                document.getElementById('fecha').focus();
                return false;
            }
            
            // Confirmación antes de crear
            if (!confirm('¿Está seguro de que desea crear este evento?')) {
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
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });

        // Validación en tiempo real de la fecha
        const fechaInputElement = document.getElementById('fecha');
        fechaInputElement.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const minAllowedDate = new Date('2000-01-01');
            
            if (selectedDate < minAllowedDate) {
                this.style.borderColor = '#dc3545';
                alert('⚠️ La fecha no puede ser anterior al 1 de enero del 2000');
            } else {
                this.style.borderColor = '#28a745';
            }
        });

        // Efectos en botones
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>