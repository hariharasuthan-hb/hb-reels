import '../bootstrap';
import Alpine from 'alpinejs';
import registerCarousel from './carousel';

window.Alpine = Alpine;
registerCarousel(Alpine);
Alpine.start();

// Frontend specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    }
    
    // Smooth scroll for homepage section links (#about, /#services, etc.)
    document.querySelectorAll('a[href*="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || href === '#') {
                return;
            }

            let url;
            try {
                url = new URL(href, window.location.origin);
            } catch (err) {
                return;
            }

            if (!url.hash || url.hash === '#') {
                return;
            }

            const samePage = url.pathname.replace(/\/$/, '') === window.location.pathname.replace(/\/$/, '');
            if (!samePage) {
                return;
            }

            const target = document.querySelector(url.hash);
            if (!target) {
                return;
            }

            e.preventDefault();
            const header = document.querySelector('header');
            const offset = header ? header.offsetHeight + 12 : 0;
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
            history.pushState(null, '', url.pathname + url.hash);

            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
    });
});
