jQuery(document).ready(function($) {
    function startTimer(expiresAt) {
        const timerElement = $('.cpg-timer');
        const labelElement = timerElement.find('.cpg-timer-label');
        const pathElement = timerElement.find('.cpg-timer-path-remaining');
        
        const FULL_DASH_ARRAY = 283; // 2 * π * 45
        const WARNING_THRESHOLD = 300; // 5 minutes
        const ALERT_THRESHOLD = 60; // 1 minute
        
        const expiresTime = new Date(expiresAt).getTime();
        const totalTime = 600; // 10 minutes in seconds
        
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        function setCircleDasharray(timeLeft) {
            const fraction = timeLeft / totalTime;
            const dasharray = `${(fraction * FULL_DASH_ARRAY).toFixed(0)} ${FULL_DASH_ARRAY}`;
            pathElement.attr('stroke-dasharray', dasharray);
        }
        
        function setRemainingPathColor(timeLeft) {
            if (timeLeft <= ALERT_THRESHOLD) {
                pathElement.css('stroke', '#ff0000');
            } else if (timeLeft <= WARNING_THRESHOLD) {
                pathElement.css('stroke', '#ff9900');
            }
        }
        
        const timer = setInterval(() => {
            const now = new Date().getTime();
            const timeLeft = Math.round((expiresTime - now) / 1000);
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                labelElement.text('00:00');
                pathElement.css('stroke-dasharray', '0 283');
                $('.cpg-payment-info').addClass('expired');
                return;
            }
            
            labelElement.text(formatTime(timeLeft));
            setCircleDasharray(timeLeft);
            setRemainingPathColor(timeLeft);
        }, 1000);
    }
    
    $('.cpg-timer').each(function() {
        const expiresAt = $(this).data('expires');
        startTimer(expiresAt);
    });
}); 