/**
 * TestMaster - Main JavaScript
 * Funcionalidades básicas del frontend
 */

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('TestMaster cargado');
    
    // Inicializar funcionalidades
    initTooltips();
    initConfirmDialogs();
});

/**
 * Inicializar tooltips para elementos con atributo title
 */
function initTooltips() {
    const elementsWithTitle = document.querySelectorAll('[title]');
    elementsWithTitle.forEach(element => {
        element.addEventListener('mouseenter', function() {
            // Aquí podrías añadir tooltips personalizados
        });
    });
}

/**
 * Confirmar acciones destructivas (borrar, etc)
 */
function initConfirmDialogs() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || '¿Estás seguro?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Mostrar mensaje de alerta temporal
 */
function showAlert(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        error: 'alert-error',
        info: 'alert-info'
    };
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertTypes[type]}`;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '250px';
    alert.style.animation = 'slideIn 0.3s ease-out';
    
    document.body.appendChild(alert);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

/**
 * Validar formularios
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    return isValid;
}

/**
 * Formatear fecha relativa (hace X minutos/horas/días)
 */
function timeAgo(date) {
    const now = new Date();
    const diffMs = now - new Date(date);
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    
    if (diffSec < 60) return 'Ahora mismo';
    if (diffMin < 60) return `Hace ${diffMin} minuto${diffMin > 1 ? 's' : ''}`;
    if (diffHour < 24) return `Hace ${diffHour} hora${diffHour > 1 ? 's' : ''}`;
    if (diffDay < 7) return `Hace ${diffDay} día${diffDay > 1 ? 's' : ''}`;
    
    return new Date(date).toLocaleDateString('es-ES');
}

// Añadir estilos para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);