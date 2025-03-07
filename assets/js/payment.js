(function($) {
            this.labelElement = element.querySelector('.cpg-timer-label');
            this.pathElement = element.querySelector('.cpg-timer-path-remaining');
            
            this.FULL_DASH_ARRAY = 283;
            this.WARNING_THRESHOLD = 180; // 3 دقیقه
            this.ALERT_THRESHOLD = 60; // 1 دقیقه
            this.TOTAL_TIME = 600; // 10 دقیقه در ثانیه
            
            this.pathElement.style.strokeDasharray = `${this.FULL_DASH_ARRAY} ${this.FULL_DASH_ARRAY}`;
            
            this.init();
        }
        
        init() {
            if (this.element.dataset.initialized === 'true') {
                return;
            }
            this.element.dataset.initialized = 'true';
            
            this.updateTimer();
            this.timer = setInterval(() => this.updateTimer(), 1000);
        }
        
        updateTimer() {
            const now = new Date();
            const timeLeft = Math.round((this.expiresAt - now) / 1000);
            
            if (timeLeft <= 0) {
                clearInterval(this.timer);
                this.labelElement.textContent = '00:00';
                this.pathElement.style.strokeDashoffset = this.FULL_DASH_ARRAY;
                this.element.closest('.cpg-payment-info').classList.add('expired');
                return;
            }
            
            // محاسبه دقیقه و ثانیه
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            this.labelElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            // محاسبه stroke-dashoffset
            const fraction = 1 - (timeLeft / this.TOTAL_TIME);
            const dashoffset = this.FULL_DASH_ARRAY * fraction;
            this.pathElement.style.strokeDashoffset = dashoffset;
            
            // تغییر رنگ بر اساس زمان باقی‌مانده
            if (timeLeft <= this.ALERT_THRESHOLD) {
                this.pathElement.style.stroke = '#f44336';
            } else if (timeLeft <= this.WARNING_THRESHOLD) {
                this.pathElement.style.stroke = '#ff9800';
            }
        }
    }
    
    // حذف تایمرهای قبلی
    if (window.paymentTimers) {
        window.paymentTimers.forEach(timer => clearInterval(timer));
    }
    window.paymentTimers = [];
    
    // راه‌اندازی تایمر جدید
    const timerElement = document.querySelector('.cpg-timer');
    if (timerElement && !timerElement.dataset.initialized) {
        const timer = new PaymentTimer(timerElement);
        window.paymentTimers.push(timer);
    }
    
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