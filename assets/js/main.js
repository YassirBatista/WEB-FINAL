// NEXUS STELLAR SHIPYARDS — JavaScript Principal
// Año 2926 — Sistema de Gestión de Taller

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar timers si existen
    initTimers();

    // Inicializar sidebar móvil
    initMobileSidebar();

    // Efectos hover en tarjetas
    initCardEffects();
});

// Timers en tiempo real para reparaciones
function initTimers() {
    const timers = document.querySelectorAll('.timer');
    if (timers.length === 0) return;

    setInterval(() => {
        timers.forEach(el => {
            let seconds = parseInt(el.dataset.seconds || 0);
            if (seconds > 0) {
                seconds--;
                el.dataset.seconds = seconds;
                el.textContent = formatTime(seconds);
            }
        });
    }, 1000);
}

function formatTime(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return String(h).padStart(2, '0') + ':' + 
           String(m).padStart(2, '0') + ':' + 
           String(s).padStart(2, '0');
}

// Sidebar móvil
function initMobileSidebar() {
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }
}

// Efectos en tarjetas
function initCardEffects() {
    document.querySelectorAll('.panel-card, .nave-card, .hangar-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// AJAX para actualizar datos en tiempo real (simulado)
function refreshStats() {
    fetch('../api/get_stats.php')
        .then(r => r.json())
        .then(data => {
            // Actualizar contadores si existen elementos con data-stat
            document.querySelectorAll('[data-stat]').forEach(el => {
                const key = el.dataset.stat;
                if (data[key] !== undefined) {
                    el.textContent = data[key];
                }
            });
        })
        .catch(err => console.log('NEXUS: Error de conexión', err));
}

// Actualizar cada 30 segundos
setInterval(refreshStats, 30000);
