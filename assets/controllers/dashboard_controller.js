import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['stat']
    
    connect() {
        console.log('Dashboard controller connected');
        this.animateStats();
    }
    
    animateStats() {
        // Animate stat numbers on page load
        this.statTargets.forEach(stat => {
            const finalValue = parseInt(stat.dataset.value);
            const duration = 1000; // 1 second
            const stepTime = 20; // Update every 20ms
            const steps = duration / stepTime;
            const increment = finalValue / steps;
            let currentValue = 0;
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                stat.textContent = Math.floor(currentValue).toLocaleString();
            }, stepTime);
        });
    }
    
    refreshData() {
        // Future: Add AJAX refresh functionality
        console.log('Refreshing dashboard data...');
        window.location.reload();
    }
}

