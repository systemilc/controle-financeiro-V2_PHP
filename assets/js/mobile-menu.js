// JavaScript para funcionalidades mobile
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile menu script carregado');
    
    // Verificar se Bootstrap está disponível
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap não está carregado!');
        return;
    }
    
    // Sincronizar contador de notificações entre desktop e mobile
    function syncNotificationCount() {
        const desktopCount = document.getElementById('notification-count');
        const mobileCount = document.getElementById('notification-count-mobile');
        
        if (desktopCount && mobileCount) {
            const count = desktopCount.textContent;
            mobileCount.textContent = count;
            mobileCount.style.display = count > 0 ? 'inline' : 'none';
        }
    }
    
    // Sincronizar inicialmente
    syncNotificationCount();
    
    // Fechar menu mobile ao clicar em um link
    const mobileLinks = document.querySelectorAll('#mobileSidebar .nav-link');
    console.log('Links encontrados:', mobileLinks.length);
    
    mobileLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            console.log('Link clicado:', this.href);
            
            // Fechar o offcanvas
            const offcanvasElement = document.getElementById('mobileSidebar');
            if (offcanvasElement) {
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                if (offcanvas) {
                    offcanvas.hide();
                } else {
                    // Se não há instância, criar uma nova
                    const newOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
                    newOffcanvas.hide();
                }
            }
        });
    });
    
    // Adicionar efeito de loading ao botão do menu mobile
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            console.log('Botão do menu clicado');
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-bars"></i>';
            }, 300);
        });
    }
    
    // Melhorar experiência de scroll em mobile
    if (window.innerWidth <= 768) {
        // Adicionar padding ao body para evitar sobreposição com o botão flutuante
        document.body.style.paddingTop = '80px';
        
        // Suavizar scroll
        document.documentElement.style.scrollBehavior = 'smooth';
    }
    
    // Adicionar indicador visual para página ativa no mobile
    const currentPage = window.location.pathname.split('/').pop();
    const activeLinks = document.querySelectorAll('#mobileSidebar .nav-link');
    activeLinks.forEach(function(link) {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
    
    console.log('Mobile menu script inicializado com sucesso');
});
