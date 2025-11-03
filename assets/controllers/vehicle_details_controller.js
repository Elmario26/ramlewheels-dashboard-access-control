import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slide', 'thumbnail', 'counter']
    
    connect() {
        this.currentSlide = 0;
        this.slides = this.element.querySelectorAll('.carousel-slide');
        this.thumbnails = this.element.querySelectorAll('.thumbnail-btn');
        this.counter = this.element.querySelector('.current-slide');
        
        // Ensure initial visibility state
        this.updateSlideDisplay();
        this.updateThumbnailDisplay();
        this.updateCounter();

        if (this.slides.length <= 1) return;
        
        this.setupNavigation();
        this.setupThumbnails();
        this.setupKeyboardNavigation();
        this.setupTouchNavigation();
        this.startAutoPlay();
    }

    setupNavigation() {
        const prevBtn = this.element.querySelector('.carousel-prev');
        const nextBtn = this.element.querySelector('.carousel-next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousSlide());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextSlide());
        }
    }

    setupThumbnails() {
        this.thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => this.goToSlide(index));
        });
    }

    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if (this.element.contains(document.activeElement) || this.element.matches(':hover')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.previousSlide();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.nextSlide();
                }
            }
        });
    }

    setupTouchNavigation() {
        let startX = 0;
        let startY = 0;
        
        this.element.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        this.element.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const diffX = startX - endX;
            const diffY = startY - endY;
            
            // Only trigger if horizontal swipe is more significant than vertical
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    this.nextSlide();
                } else {
                    this.previousSlide();
                }
            }
        });
    }

    startAutoPlay() {
        // Auto-advance slides every 5 seconds
        this.autoPlayInterval = setInterval(() => {
            this.nextSlide();
        }, 5000);
        
        // Pause auto-play on hover
        this.element.addEventListener('mouseenter', () => {
            clearInterval(this.autoPlayInterval);
        });
        
        this.element.addEventListener('mouseleave', () => {
            this.startAutoPlay();
        });
    }

    goToSlide(index) {
        if (index < 0 || index >= this.slides.length) return;
        
        this.currentSlide = index;
        this.updateSlideDisplay();
        this.updateThumbnailDisplay();
        this.updateCounter();
    }

    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        this.updateSlideDisplay();
        this.updateThumbnailDisplay();
        this.updateCounter();
    }

    previousSlide() {
        this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.updateSlideDisplay();
        this.updateThumbnailDisplay();
        this.updateCounter();
    }

    updateSlideDisplay() {
        this.slides.forEach((slide, index) => {
            const isActive = index === this.currentSlide;
            // Toggle a CSS class for semantics
            slide.classList.toggle('active', isActive);
            // Explicitly control visibility to avoid relying on external CSS
            slide.style.opacity = isActive ? '1' : '0';
            slide.style.pointerEvents = isActive ? 'auto' : 'none';
            slide.style.transitionProperty = 'opacity';
        });
    }

    updateThumbnailDisplay() {
        this.thumbnails.forEach((thumbnail, index) => {
            thumbnail.classList.toggle('border-white', index === this.currentSlide);
            thumbnail.classList.toggle('border-transparent', index !== this.currentSlide);
        });
    }

    updateCounter() {
        if (this.counter) {
            this.counter.textContent = this.currentSlide + 1;
        }
    }

    disconnect() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
        }
    }
}