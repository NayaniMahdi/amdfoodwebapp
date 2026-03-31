/**
 * Nouriq — Custom Chart Library (Canvas-based)
 * Animated donut, bar, and line charts with no dependencies
 */

class NouriqCharts {
    
    // ============================================================
    // DONUT / RING CHART (Calorie tracker)
    // ============================================================
    static drawDonut(canvasId, value, max, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        
        const size = options.size || 200;
        canvas.width = size * dpr;
        canvas.height = size * dpr;
        canvas.style.width = size + 'px';
        canvas.style.height = size + 'px';
        ctx.scale(dpr, dpr);
        
        const centerX = size / 2;
        const centerY = size / 2;
        const radius = (size / 2) - 16;
        const lineWidth = options.lineWidth || 14;
        const percentage = Math.min(value / max, 1.5); // Allow over 100%
        const startAngle = -Math.PI / 2;
        const endAngle = startAngle + (2 * Math.PI * Math.min(percentage, 1));
        
        const bgColor = options.bgColor || 'rgba(255,255,255,0.06)';
        const colors = percentage > 1 
            ? ['#ff6b6b', '#ff8a80'] 
            : (options.colors || ['#6C5CE7', '#a29bfe']);
        
        // Animate
        let currentAngle = startAngle;
        const targetAngle = endAngle;
        const animDuration = 1200;
        const animStart = performance.now();
        
        function animate(time) {
            const elapsed = time - animStart;
            const progress = Math.min(elapsed / animDuration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            currentAngle = startAngle + (targetAngle - startAngle) * eased;
            
            ctx.clearRect(0, 0, size, size);
            
            // Background ring
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
            ctx.lineWidth = lineWidth;
            ctx.strokeStyle = bgColor;
            ctx.lineCap = 'round';
            ctx.stroke();
            
            // Value ring
            if (currentAngle > startAngle) {
                const gradient = ctx.createLinearGradient(0, 0, size, size);
                gradient.addColorStop(0, colors[0]);
                gradient.addColorStop(1, colors[1]);
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, startAngle, currentAngle);
                ctx.lineWidth = lineWidth;
                ctx.strokeStyle = gradient;
                ctx.lineCap = 'round';
                ctx.stroke();
                
                // Glow effect
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, startAngle, currentAngle);
                ctx.lineWidth = lineWidth + 8;
                ctx.strokeStyle = colors[0] + '20';
                ctx.lineCap = 'round';
                ctx.stroke();
            }
            
            if (progress < 1) requestAnimationFrame(animate);
        }
        
        requestAnimationFrame(animate);
    }
    
    // ============================================================
    // BAR CHART (Weekly view)
    // ============================================================
    static drawBarChart(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        
        const width = canvas.parentElement.clientWidth - 32;
        const height = options.height || 250;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.scale(dpr, dpr);
        
        const padding = { top: 20, right: 20, bottom: 40, left: 50 };
        const chartW = width - padding.left - padding.right;
        const chartH = height - padding.top - padding.bottom;
        
        const maxVal = Math.max(...data.map(d => d.value), options.target || 0) * 1.15;
        const barWidth = Math.min(40, (chartW / data.length) * 0.6);
        const barGap = (chartW - barWidth * data.length) / (data.length + 1);
        
        const colors = options.colors || ['#6C5CE7', '#a29bfe'];
        const targetLine = options.target || null;
        
        const animDuration = 800;
        const animStart = performance.now();
        
        function animate(time) {
            const elapsed = time - animStart;
            const progress = Math.min(elapsed / animDuration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            
            ctx.clearRect(0, 0, width, height);
            
            // Grid lines
            const gridCount = 5;
            ctx.strokeStyle = 'rgba(255,255,255,0.04)';
            ctx.lineWidth = 1;
            ctx.font = '11px "JetBrains Mono", monospace';
            ctx.fillStyle = 'rgba(136,136,160,0.6)';
            ctx.textAlign = 'right';
            
            for (let i = 0; i <= gridCount; i++) {
                const y = padding.top + (chartH / gridCount) * i;
                const val = Math.round(maxVal - (maxVal / gridCount) * i);
                
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                ctx.fillText(val.toLocaleString(), padding.left - 8, y + 4);
            }
            
            // Target line
            if (targetLine) {
                const targetY = padding.top + chartH * (1 - targetLine / maxVal);
                ctx.strokeStyle = '#00cec9';
                ctx.lineWidth = 1.5;
                ctx.setLineDash([6, 4]);
                ctx.beginPath();
                ctx.moveTo(padding.left, targetY);
                ctx.lineTo(width - padding.right, targetY);
                ctx.stroke();
                ctx.setLineDash([]);
                
                ctx.fillStyle = '#00cec9';
                ctx.textAlign = 'left';
                ctx.fillText('Goal', width - padding.right + 4, targetY + 4);
            }
            
            // Bars
            data.forEach((item, i) => {
                const x = padding.left + barGap + (barWidth + barGap) * i;
                const barH = (item.value / maxVal) * chartH * eased;
                const y = padding.top + chartH - barH;
                
                // Bar gradient
                const gradient = ctx.createLinearGradient(x, y, x, padding.top + chartH);
                const isOverTarget = targetLine && item.value > targetLine;
                if (isOverTarget) {
                    gradient.addColorStop(0, '#ff6b6b');
                    gradient.addColorStop(1, '#ff6b6b80');
                } else {
                    gradient.addColorStop(0, colors[0]);
                    gradient.addColorStop(1, colors[1] + '60');
                }
                
                // Rounded bar
                const r = Math.min(6, barWidth / 2);
                ctx.beginPath();
                ctx.moveTo(x, padding.top + chartH);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.lineTo(x + barWidth - r, y);
                ctx.quadraticCurveTo(x + barWidth, y, x + barWidth, y + r);
                ctx.lineTo(x + barWidth, padding.top + chartH);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();
                
                // Bar glow
                ctx.shadowColor = isOverTarget ? '#ff6b6b40' : colors[0] + '30';
                ctx.shadowBlur = 12;
                ctx.fill();
                ctx.shadowColor = 'transparent';
                ctx.shadowBlur = 0;
                
                // Labels
                ctx.fillStyle = 'rgba(136,136,160,0.8)';
                ctx.font = '11px "Inter", sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(item.label, x + barWidth / 2, height - padding.bottom + 20);
                
                // Value on top
                if (progress >= 0.9) {
                    ctx.fillStyle = 'rgba(240,240,245,0.7)';
                    ctx.font = '10px "JetBrains Mono", monospace';
                    ctx.fillText(Math.round(item.value), x + barWidth / 2, y - 8);
                }
            });
            
            if (progress < 1) requestAnimationFrame(animate);
        }
        
        requestAnimationFrame(animate);
    }
    
    // ============================================================
    // LINE CHART (Trends)
    // ============================================================
    static drawLineChart(canvasId, datasets, labels, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        
        const width = canvas.parentElement.clientWidth - 32;
        const height = options.height || 250;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.scale(dpr, dpr);
        
        const padding = { top: 20, right: 20, bottom: 40, left: 50 };
        const chartW = width - padding.left - padding.right;
        const chartH = height - padding.top - padding.bottom;
        
        const allValues = datasets.flatMap(d => d.data);
        const maxVal = (Math.max(...allValues) || 100) * 1.15;
        const minVal = 0;
        
        const pointCount = labels.length;
        const stepX = chartW / (pointCount - 1 || 1);
        
        const animDuration = 1000;
        const animStart = performance.now();
        
        function animate(time) {
            const elapsed = time - animStart;
            const progress = Math.min(elapsed / animDuration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            
            ctx.clearRect(0, 0, width, height);
            
            // Grid
            const gridCount = 5;
            for (let i = 0; i <= gridCount; i++) {
                const y = padding.top + (chartH / gridCount) * i;
                const val = Math.round(maxVal - (maxVal / gridCount) * i);
                
                ctx.strokeStyle = 'rgba(255,255,255,0.04)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                
                ctx.fillStyle = 'rgba(136,136,160,0.6)';
                ctx.font = '11px "JetBrains Mono", monospace';
                ctx.textAlign = 'right';
                ctx.fillText(val.toLocaleString(), padding.left - 8, y + 4);
            }
            
            // X labels
            ctx.fillStyle = 'rgba(136,136,160,0.8)';
            ctx.font = '11px "Inter", sans-serif';
            ctx.textAlign = 'center';
            labels.forEach((label, i) => {
                const x = padding.left + stepX * i;
                ctx.fillText(label, x, height - padding.bottom + 20);
            });
            
            // Lines
            datasets.forEach(dataset => {
                const points = dataset.data.map((val, i) => ({
                    x: padding.left + stepX * i,
                    y: padding.top + chartH * (1 - (val * eased) / maxVal)
                }));
                
                // Area fill
                if (dataset.fill) {
                    ctx.beginPath();
                    ctx.moveTo(points[0].x, padding.top + chartH);
                    points.forEach(p => ctx.lineTo(p.x, p.y));
                    ctx.lineTo(points[points.length - 1].x, padding.top + chartH);
                    ctx.closePath();
                    const grad = ctx.createLinearGradient(0, padding.top, 0, padding.top + chartH);
                    grad.addColorStop(0, dataset.color + '30');
                    grad.addColorStop(1, dataset.color + '05');
                    ctx.fillStyle = grad;
                    ctx.fill();
                }
                
                // Line
                ctx.beginPath();
                ctx.moveTo(points[0].x, points[0].y);
                
                // Smooth curve
                for (let i = 1; i < points.length; i++) {
                    const prev = points[i - 1];
                    const curr = points[i];
                    const midX = (prev.x + curr.x) / 2;
                    ctx.bezierCurveTo(midX, prev.y, midX, curr.y, curr.x, curr.y);
                }
                
                ctx.strokeStyle = dataset.color;
                ctx.lineWidth = 2.5;
                ctx.stroke();
                
                // Dots
                if (progress >= 0.8) {
                    points.forEach(p => {
                        ctx.beginPath();
                        ctx.arc(p.x, p.y, 4, 0, 2 * Math.PI);
                        ctx.fillStyle = dataset.color;
                        ctx.fill();
                        ctx.beginPath();
                        ctx.arc(p.x, p.y, 2, 0, 2 * Math.PI);
                        ctx.fillStyle = '#0a0a0f';
                        ctx.fill();
                    });
                }
            });
            
            if (progress < 1) requestAnimationFrame(animate);
        }
        
        requestAnimationFrame(animate);
    }

    // ============================================================
    // HORIZONTAL BAR (Macro breakdown)
    // ============================================================
    static drawHorizontalBars(containerId, data) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        let html = '';
        data.forEach(item => {
            const percentage = Math.min((item.value / item.max) * 100, 100);
            const overClass = item.value > item.max ? 'danger' : '';
            html += `
                <div class="macro-item">
                    <div class="macro-header">
                        <span class="macro-name">
                            <span class="macro-dot" style="background:${item.color}"></span>
                            ${item.label}
                        </span>
                        <span class="macro-values">${Math.round(item.value)}g / ${item.max}g</span>
                    </div>
                    <div class="macro-bar">
                        <div class="macro-bar-fill ${overClass}" style="width:${percentage}%;background:${item.color}"></div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        
        // Animate widths
        setTimeout(() => {
            container.querySelectorAll('.macro-bar-fill').forEach(bar => {
                bar.style.width = bar.style.width; // Trigger reflow
            });
        }, 50);
    }
}
