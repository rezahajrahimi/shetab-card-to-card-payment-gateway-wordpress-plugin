(function($) {
    'use strict';
    
    class PaymentTimer {
        constructor(element) {
            this.element = element;
            this.expiresAt = new Date(element.dataset.expires).getTime();
            this.labelElement = element.querySelector('.cpg-timer-label');
            this.pathElement = element.querySelector('.cpg-timer-path-remaining');
            
            this.FULL_DASH_ARRAY = 283; // 2 * π * 45
            this.WARNING_THRESHOLD = 300;
            this.ALERT_THRESHOLD = 60;
            
            // تنظیم stroke-dasharray اولیه
            this.pathElement.style.strokeDasharray = `${this.FULL_DASH_ARRAY} ${this.FULL_DASH_ARRAY}`;
            
            this.init();
        }
        
        init() {
            this.updateTimer();
            this.timer = setInterval(() => this.updateTimer(), 1000);
        }
        
        updateTimer() {
            const now = new Date().getTime();
            const timeLeft = Math.round((this.expiresAt - now) / 1000);
            
            if (timeLeft <= 0) {
                clearInterval(this.timer);
                this.labelElement.textContent = '00:00';
                this.pathElement.style.strokeDashoffset = this.FULL_DASH_ARRAY;
                this.element.closest('.cpg-payment-info').classList.add('expired');
                location.reload(); // رفرش صفحه برای نمایش پیام انقضا
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            this.labelElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // محاسبه stroke-dashoffset
            const timeLeftFraction = timeLeft / (24 * 60 * 60); // 24 ساعت به ثانیه
            const dashoffset = this.FULL_DASH_ARRAY * (1 - timeLeftFraction);
            this.pathElement.style.strokeDashoffset = dashoffset;
            
            // تغییر رنگ بر اساس زمان باقی‌مانده
            if (timeLeft <= this.ALERT_THRESHOLD) {
                this.pathElement.style.stroke = '#f44336';
            } else if (timeLeft <= this.WARNING_THRESHOLD) {
                this.pathElement.style.stroke = '#ff9800';
            }
        }
    }
    
    // راه‌اندازی تایمر
    $('.cpg-timer').each(function() {
        new PaymentTimer(this);
    });
    
    // کپی شماره کارت
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