// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeErrorPage();
    updateTimestamp();
    addInteractiveEffects();
    createParticleEffect();
});

// Fonction d'initialisation principale
function initializeErrorPage() {
    // Animation d'entrée séquentielle
    const elements = document.querySelectorAll('.content > *');
    elements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Effet de typing sur le titre
    typeWriter();
    
    // Démarrage des animations de fond
    startBackgroundAnimations();
}

// Mise à jour du timestamp
function updateTimestamp() {
    const timestampElement = document.getElementById('timestamp');
    const now = new Date();
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    
    timestampElement.textContent = now.toLocaleDateString('fr-FR', options);
    
    // Mise à jour toutes les secondes
    setInterval(() => {
        const currentTime = new Date();
        timestampElement.textContent = currentTime.toLocaleDateString('fr-FR', options);
    }, 1000);
}

// Effet de machine à écrire sur le titre
function typeWriter() {
    const title = document.querySelector('.error-title');
    const text = title.textContent;
    title.textContent = '';
    title.style.borderRight = '2px solid #10b981';
    
    let i = 0;
    const timer = setInterval(() => {
        if (i < text.length) {
            title.textContent += text.charAt(i);
            i++;
        } else {
            clearInterval(timer);
            // Suppression du curseur après un délai
            setTimeout(() => {
                title.style.borderRight = 'none';
            }, 1000);
        }
    }, 100);
}

// Ajout d'effets interactifs
function addInteractiveEffects() {
    // Effet de parallaxe sur les formes flottantes
    document.addEventListener('mousemove', (e) => {
        const shapes = document.querySelectorAll('.shape');
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        
        shapes.forEach((shape, index) => {
            const speed = (index + 1) * 0.5;
            const xPos = (x - 0.5) * speed * 20;
            const yPos = (y - 0.5) * speed * 20;
            
            shape.style.transform = `translate(${xPos}px, ${yPos}px)`;
        });
    });
    
    // Effet de hover sur les boutons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.05)';
            createRippleEffect(this);
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        button.addEventListener('click', function(e) {
            createClickEffect(e, this);
        });
    });
    
    // Effet de hover sur les éléments de la liste d'aide
    const helpItems = document.querySelectorAll('.help-list li');
    helpItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(10px)';
            this.style.background = 'rgba(16, 185, 129, 0.1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
            this.style.background = 'rgba(255, 255, 255, 0.05)';
        });
    });
}

// Création d'un effet de particules
function createParticleEffect() {
    const container = document.querySelector('.background-animation');
    
    for (let i = 0; i < 20; i++) {
        setTimeout(() => {
            createParticle(container);
        }, i * 200);
    }
    
    // Création continue de particules
    setInterval(() => {
        createParticle(container);
    }, 3000);
}

function createParticle(container) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    // Styles de la particule
    particle.style.position = 'absolute';
    particle.style.width = Math.random() * 4 + 2 + 'px';
    particle.style.height = particle.style.width;
    particle.style.background = '#10b981';
    particle.style.borderRadius = '50%';
    particle.style.opacity = Math.random() * 0.5 + 0.2;
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = '100%';
    particle.style.pointerEvents = 'none';
    particle.style.zIndex = '5';
    
    container.appendChild(particle);
    
    // Animation de la particule
    const duration = Math.random() * 3000 + 2000;
    const drift = (Math.random() - 0.5) * 100;
    
    particle.animate([
        {
            transform: 'translateY(0px) translateX(0px)',
            opacity: particle.style.opacity
        },
        {
            transform: `translateY(-${window.innerHeight + 100}px) translateX(${drift}px)`,
            opacity: 0
        }
    ], {
        duration: duration,
        easing: 'linear'
    }).onfinish = () => {
        particle.remove();
    };
}

// Effet de ripple sur les boutons
function createRippleEffect(button) {
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'rgba(255, 255, 255, 0.3)';
    ripple.style.transform = 'scale(0)';
    ripple.style.animation = 'ripple 0.6s linear';
    ripple.style.left = '50%';
    ripple.style.top = '50%';
    ripple.style.width = '20px';
    ripple.style.height = '20px';
    ripple.style.marginLeft = '-10px';
    ripple.style.marginTop = '-10px';
    
    button.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Effet de clic
function createClickEffect(e, button) {
    const rect = button.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    const clickEffect = document.createElement('span');
    clickEffect.style.position = 'absolute';
    clickEffect.style.left = x + 'px';
    clickEffect.style.top = y + 'px';
    clickEffect.style.width = '0px';
    clickEffect.style.height = '0px';
    clickEffect.style.borderRadius = '50%';
    clickEffect.style.background = 'rgba(255, 255, 255, 0.5)';
    clickEffect.style.transform = 'translate(-50%, -50%)';
    clickEffect.style.animation = 'clickWave 0.5s ease-out';
    clickEffect.style.pointerEvents = 'none';
    
    button.appendChild(clickEffect);
    
    setTimeout(() => {
        clickEffect.remove();
    }, 500);
}

// Animations de fond
function startBackgroundAnimations() {
    // Animation de pulsation pour l'icône
    const icon = document.querySelector('.error-icon i');
    setInterval(() => {
        icon.style.animation = 'none';
        setTimeout(() => {
            icon.style.animation = 'pulse 2s ease-in-out infinite';
        }, 10);
    }, 10000);
    
    // Changement de couleur subtil du code d'erreur
    const errorCode = document.querySelector('.code-number');
    const colors = ['#10b981', '#34d399', '#059669', '#047857'];
    let colorIndex = 0;
    
    setInterval(() => {
        colorIndex = (colorIndex + 1) % colors.length;
        errorCode.style.background = `linear-gradient(135deg, ${colors[colorIndex]}, ${colors[(colorIndex + 1) % colors.length]})`;
        errorCode.style.webkitBackgroundClip = 'text';
        errorCode.style.backgroundClip = 'text';
    }, 5000);
}

// Fonctions des boutons d'action
function goBack() {
    // Animation de sortie
    document.querySelector('.content').style.animation = 'slideDown 0.5s ease-in forwards';
    
    setTimeout(() => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = '/';
        }
    }, 500);
}

function goHome() {
    // Animation de sortie
    document.querySelector('.content').style.animation = 'slideDown 0.5s ease-in forwards';
    
    setTimeout(() => {
        window.location.href = '/';
    }, 500);
}

function contactSupport() {
    // Simulation d'ouverture d'un modal ou redirection
    const supportModal = createSupportModal();
    document.body.appendChild(supportModal);
    
    setTimeout(() => {
        supportModal.style.opacity = '1';
        supportModal.querySelector('.modal-content').style.transform = 'scale(1)';
    }, 10);
}

// Création d'un modal de support
function createSupportModal() {
    const modal = document.createElement('div');
    modal.className = 'support-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.style.cssText = `
        background: linear-gradient(135deg, #1f2937, #374151);
        padding: 2rem;
        border-radius: 16px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        border: 1px solid rgba(16, 185, 129, 0.3);
        transform: scale(0.8);
        transition: transform 0.3s ease;
    `;
    
    modalContent.innerHTML = `
        <h3 style="color: #10b981; margin-bottom: 1rem;">Contacter le Support</h3>
        <p style="color: #d1d5db; margin-bottom: 1.5rem;">
            Pour obtenir de l'aide, veuillez contacter notre équipe de support :
        </p>
        <div style="margin-bottom: 1.5rem;">
            <p style="color: #9ca3af; margin-bottom: 0.5rem;">
                <i class="fas fa-envelope" style="color: #10b981; margin-right: 0.5rem;"></i>
                support@example.com
            </p>
            <p style="color: #9ca3af;">
                <i class="fas fa-phone" style="color: #10b981; margin-right: 0.5rem;"></i>
                +33 1 23 45 67 89
            </p>
        </div>
        <button onclick="closeSupportModal()" style="
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        ">Fermer</button>
    `;
    
    modal.appendChild(modalContent);
    
    // Fermeture en cliquant à l'extérieur
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeSupportModal();
        }
    });
    
    return modal;
}

// Fermeture du modal de support
function closeSupportModal() {
    const modal = document.querySelector('.support-modal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Ajout des animations CSS dynamiques
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes clickWave {
        to {
            width: 50px;
            height: 50px;
            opacity: 0;
        }
    }
    
    @keyframes slideDown {
        to {
            opacity: 0;
            transform: translateY(30px);
        }
    }
`;
document.head.appendChild(style);

// Gestion du redimensionnement de la fenêtre
window.addEventListener('resize', () => {
    // Recalcul des positions des particules si nécessaire
    const particles = document.querySelectorAll('.particle');
    particles.forEach(particle => {
        if (parseInt(particle.style.left) > window.innerWidth) {
            particle.style.left = window.innerWidth + 'px';
        }
    });
});

// Gestion des raccourcis clavier
document.addEventListener('keydown', (e) => {
    switch(e.key) {
        case 'Escape':
            closeSupportModal();
            break;
        case 'Backspace':
            if (e.altKey) {
                goBack();
            }
            break;
        case 'h':
            if (e.altKey) {
                goHome();
            }
            break;
    }
});

console.log('🚀 Page d\'erreur 403 initialisée avec succès!');

