(function($) {
    'use strict';
    
    class PaymentTimer {
        constructor(element) {
            this.element = element;
            this.expiresAt = new Date(element.dataset.expires).getTime();
            this.labelElement = element.querySelector('.cpg-timer-label');
            this.pathElement = element.querySelector('.cpg-timer-path-remaining');
            
            this.FULL_DASH_ARRAY = 283;
            this.WARNING_THRESHOLD = 300;
            this.ALERT_THRESHOLD = 60;
            this.TOTAL_TIME = 600;
            
            this.init();
        }
        
        init() {
            this.timer = setInterval(() => this.updateTimer(), 1000);
        }
        
        updateTimer() {
            const now = new Date().getTime();
            const timeLeft = Math.round((this.expiresAt - now) / 1000);
            
            if (timeLeft <= 0) {
                clearInterval(this.timer);
                this.labelElement.textContent = '00:00';
                this.pathElement.style.strokeDasharray = `0 ${this.FULL_DASH_ARRAY}`;
                this.element.closest('.cpg-payment-info').classList.add('expired');
                return;
            }
            
            this.labelElement.textContent = this.formatTime(timeLeft);
            this.setCircleDasharray(timeLeft);
            this.setRemainingPathColor(timeLeft);
        }
        
        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        setCircleDasharray(timeLeft) {
            const fraction = timeLeft / this.TOTAL_TIME;
            const dasharray = `${(fraction * this.FULL_DASH_ARRAY).toFixed(0)} ${this.FULL_DASH_ARRAY}`;
            this.pathElement.style.strokeDasharray = dasharray;
        }
        
        setRemainingPathColor(timeLeft) {
            this.pathElement.classList.remove('warning', 'alert');
            if (timeLeft <= this.ALERT_THRESHOLD) {
                this.pathElement.classList.add('alert');
            } else if (timeLeft <= this.WARNING_THRESHOLD) {
                this.pathElement.classList.add('warning');
            }
        }
    }
    
    // Initialize timers
    document.querySelectorAll('.cpg-timer').forEach(timer => {
        new PaymentTimer(timer);
    });
    
    // Copy card number functionality
    $('.cpg-card-number').on('click', function() {
        const el = document.createElement('textarea');
        el.value = $(this).text().replace(/\s/g, '');
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        
        const originalText = $(this).text();
        $(this).text('کپی شد!');
        setTimeout(() => $(this).text(originalText), 1000);
    });
    
})(jQuery); 