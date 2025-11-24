document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const sunIcon = document.querySelector('.sun');
    const moonIcon = document.querySelector('.moon');
    
    // Verificar preferencia guardada
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'day') {
        body.classList.add('day-mode');
        sunIcon.classList.add('active');
        moonIcon.classList.remove('active');
        themeToggle.setAttribute('data-tooltip', 'Cambiar a modo noche');
    } else {
        themeToggle.setAttribute('data-tooltip', 'Cambiar a modo día');
    }
    
    // Cambiar tema
    themeToggle.addEventListener('click', function() {
        const isDayMode = body.classList.toggle('day-mode');
        
        // Animación de iconos
        if (isDayMode) {
            moonIcon.classList.remove('active');
            setTimeout(() => {
                sunIcon.classList.add('active');
            }, 150);
            themeToggle.setAttribute('data-tooltip', 'Cambiar a modo noche');
        } else {
            sunIcon.classList.remove('active');
            setTimeout(() => {
                moonIcon.classList.add('active');
            }, 150);
            themeToggle.setAttribute('data-tooltip', 'Cambiar a modo día');
        }
        
        // Guardar preferencia
        localStorage.setItem('theme', isDayMode ? 'day' : 'night');
    });

    // EFECTOS INTERACTIVOS
    const interactiveElements = document.querySelectorAll('.interactive-element');
    
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Tooltip para descripciones largas
    const descripciones = document.querySelectorAll('.descripcion');
    descripciones.forEach(desc => {
        desc.addEventListener('mouseenter', function() {
            if (this.scrollWidth > this.clientWidth) {
                this.style.cursor = 'help';
            }
        });
    });
});