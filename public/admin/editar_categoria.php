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

// Obtener datos de la categoría a editar
$categoria = null;
$evento = null;
if (isset($_GET['id'])) {
    try {
        $query = "SELECT c.id, c.nombre, c.puntaje_maximo, c.evento_id, e.nombre as evento_nombre 
                 FROM categorias c 
                 JOIN eventos e ON c.evento_id = e.id 
                 WHERE c.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            $evento = [
                'id' => $categoria['evento_id'],
                'nombre' => $categoria['evento_nombre']
            ];
        } else {
            $error = "Categoría no encontrada.";
        }
    } catch (PDOException $e) {
        $error = "Error al cargar la categoría: " . $e->getMessage();
    }
} else {
    $error = "ID de categoría no especificado.";
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $categoria) {
    $nombre = trim($_POST['nombre']);
    $puntaje_maximo = 10; // Siempre 10 puntos

    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre de la categoría es obligatorio.";
    } else {
        try {
            // Verificar si el nombre ya existe en otro evento
            $query = "SELECT id FROM categorias WHERE nombre = :nombre AND evento_id = :evento_id AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':evento_id', $categoria['evento_id']);
            $stmt->bindParam(':id', $categoria['id']);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Ya existe una categoría con ese nombre en este evento.";
            } else {
                // Actualizar categoría
                $query = "UPDATE categorias SET nombre = :nombre, puntaje_maximo = :puntaje_maximo WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':puntaje_maximo', $puntaje_maximo);
                $stmt->bindParam(':id', $categoria['id']);
                
                if ($stmt->execute()) {
                    $success = "Categoría actualizada exitosamente.";
                    // Actualizar datos locales
                    $categoria['nombre'] = $nombre;
                } else {
                    $error = "Error al actualizar la categoría.";
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
    <title>Editar Categoría - Sistema de Votación</title>
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
        .container { 
            max-width: 800px; 
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .content-wrapper {
            width: 100%;
        }

        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            padding: 30px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            margin-bottom: 0;
            box-shadow: var(--shadow);
            text-align: center;
            border-bottom: none;
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
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            border-top: none;
            animation: slideIn 0.6s ease-out;
        }

        body.day-mode .form-container { background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0,0,0,0.1); }

        .evento-info {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(139, 92, 246, 0.2));
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 5px solid var(--accent-turquoise);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
        }

        body.day-mode .evento-info { 
            background: linear-gradient(135deg, #e7f3ff, #d1ecf1); 
            border-left: 5px solid #007bff;
        }

        .current-info {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(236, 72, 153, 0.2));
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--accent-gold);
            text-align: center;
            font-weight: 600;
        }

        body.day-mode .current-info { background: #fff3cd; }

        .puntaje-info {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.2), rgba(59, 130, 246, 0.2));
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--accent-turquoise);
            text-align: center;
            font-weight: 500;
        }

        body.day-mode .puntaje-info { background: #d1ecf1; }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 1rem;
        }

        input[type="text"] {
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

        body.day-mode input[type="text"] {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0,0,0,0.1);
            color: var(--text-dark);
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--accent-turquoise);
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.2);
            transform: translateY(-2px);
        }

        /* Botones */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
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
            flex-wrap: wrap;
            gap: 15px;
        }

        .left-actions, .right-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Puntaje fijo */
        .puntaje-fijo {
            background: linear-gradient(135deg, var(--accent-turquoise), var(--accent-purple));
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: var(--shadow);
        }

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
            .theme-toggle { top: 15px; right: 15px; width: 50px; height: 50px; }
            .form-container { padding: 25px; }
            .container { padding: 10px; }
        }
    </style>
</head>
<body class="night-mode">
    <div class="theme-toggle" id="themeToggle">
        <span class="theme-icon sun"><i class="far fa-sun" style="color: #000000;"></i></span>
        <span class="theme-icon moon"><i class="fas fa-moon" style="color: #ffffff;"></i></span>
    </div>

    <div class="container">
        <div class="content-wrapper">
            <div class="header">
                <h1><i class="fas fa-edit"></i> Editar Categoría</h1>
                <p>Modifique los datos de la categoría de evaluación</p>
            </div>

            <div class="form-container">
                <?php if (!empty($error)): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($categoria && $evento): ?>
                    <div class="evento-info">
                        <h3><i class="fas fa-calendar-star"></i> <?php echo htmlspecialchars($evento['nombre']); ?></h3>
                        <div style="opacity: 0.8; font-size: 0.9rem;">
                            ID del Evento: #<?php echo $evento['id']; ?>
                        </div>
                    </div>

                    <div class="current-info">
                        <i class="fas fa-pencil-alt"></i> Editando categoría: <strong>#<?php echo $categoria['id']; ?></strong>
                    </div>

                    <div class="puntaje-info">
                        <i class="fas fa-info-circle"></i> <strong>Información:</strong> El puntaje máximo está configurado en <strong>10 puntos</strong> para todas las categorías.
                    </div>

                    <form method="POST" action="" id="editarCategoriaForm">
                        <div class="form-group">
                            <label for="nombre"><i class="fas fa-tag"></i> Nombre de la Categoría</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($categoria['nombre']); ?>" 
                                   required 
                                   placeholder="Ej: Elegancia, Simpatía, Carisma">
                            <div class="form-help">Nombre descriptivo del criterio de evaluación</div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-star"></i> Puntaje Máximo</label>
                            <div class="puntaje-fijo">
                                <i class="fas fa-bullseye"></i> 10 puntos (configuración fija del sistema)
                            </div>
                            <div class="form-help">Todas las categorías usan escala del 1 al 10</div>
                        </div>

                        <div class="form-actions">
                            <div class="left-actions">
                                <a href="gestionar_categorias.php?evento_id=<?php echo $evento['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                                <a href="categorias.php" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> Todos los Eventos
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
                        <i class="fas fa-exclamation-triangle"></i> No se puede cargar la información de la categoría.
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="categorias.php" class="btn">
                            <i class="fas fa-list"></i> Volver a la gestión de categorías
                        </a>
                    </div>
                <?php endif; ?>
            </div>
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

            // Efectos interactivos
            const nombreInput = document.getElementById('nombre');
            if (nombreInput) {
                nombreInput.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(6, 182, 212, 0.2)';
                });
                
                nombreInput.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            }

            // Prevenir envío duplicado del formulario
            const form = document.getElementById('editarCategoriaForm');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                        submitBtn.disabled = true;
                    }
                });
            }

            // Validación en tiempo real del nombre
            if (nombreInput) {
                nombreInput.addEventListener('input', function() {
                    if (this.value.length >= 3) {
                        this.style.borderColor = '#10b981';
                    } else {
                        this.style.borderColor = '';
                    }
                });

                // Inicializar validación visual
                if (nombreInput.value.length >= 3) {
                    nombreInput.style.borderColor = '#10b981';
                }
            }
        });
    </script>
</body>
</html>