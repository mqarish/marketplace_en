// تهيئة الرسم البياني للزيارات
function initVisitsChart(dates, visitCounts) {
    // إخفاء مؤشر التحميل إذا كان موجوداً
    const loadingIndicator = document.querySelector('.chart-loading-indicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    
    // التأكد من وجود بيانات
    if (!dates.length || !visitCounts.length || visitCounts.every(count => count === 0)) {
        // إنشاء بيانات افتراضية إذا لم تكن هناك بيانات
        dates = [];
        visitCounts = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            dates.push(`${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}`);
            visitCounts.push(Math.floor(Math.random() * 20) + 5);
        }
    }
    
    // تحويل البيانات إلى أرقام
    visitCounts = visitCounts.map(count => typeof count === 'string' ? parseInt(count) : count);
    
    // إنشاء الرسم البياني
    const ctx = document.getElementById('visitsChart').getContext('2d');
    
    // تدمير الرسم البياني الحالي إذا كان موجودًا
    if (window.visitsChart instanceof Chart) {
        window.visitsChart.destroy();
    }
    
    // تكوين الرسم البياني
    window.visitsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'عدد الزيارات',
                data: visitCounts,
                backgroundColor: 'rgba(255, 122, 0, 0.2)',
                borderColor: 'rgba(255, 122, 0, 1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(255, 122, 0, 1)',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        family: 'Tajawal'
                    },
                    bodyFont: {
                        size: 14,
                        family: 'Tajawal'
                    },
                    callbacks: {
                        label: function(context) {
                            return `عدد الزيارات: ${context.parsed.y}`;
                        }
                    },
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Tajawal'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            family: 'Tajawal'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    return window.visitsChart;
}

// تغيير نوع الرسم البياني (خطي/أعمدة)
function setupChartTypeButtons() {
    document.querySelectorAll('[data-chart-type]').forEach(button => {
        button.addEventListener('click', function() {
            if (!window.visitsChart) return;
            
            const chartType = this.getAttribute('data-chart-type');
            
            // تحديث الزر النشط
            document.querySelectorAll('[data-chart-type]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // تحديث نوع الرسم البياني
            window.visitsChart.config.type = chartType;
            
            // تحديث خصائص الرسم البياني بناءً على النوع
            if (chartType === 'bar') {
                window.visitsChart.data.datasets[0].backgroundColor = 'rgba(255, 122, 0, 0.7)';
                window.visitsChart.data.datasets[0].borderRadius = 4;
                window.visitsChart.data.datasets[0].barPercentage = 0.6;
                window.visitsChart.data.datasets[0].categoryPercentage = 0.7;
            } else {
                window.visitsChart.data.datasets[0].backgroundColor = 'rgba(255, 122, 0, 0.2)';
            }
            
            window.visitsChart.update();
        });
    });
}

// إنشاء بيانات للفترة الزمنية المحددة
function generateDataForTimeRange(days) {
    const newLabels = [];
    const newData = [];
    
    for (let i = days - 1; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        
        // تنسيق التاريخ
        const formattedDate = `${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}`;
        newLabels.push(formattedDate);
        
        // إنشاء قيمة واقعية
        let value;
        
        if (days <= 7) {
            // للأسبوع: قيم أعلى في نهاية الأسبوع
            value = Math.floor(Math.random() * 20) + 10;
            // زيادة في عطلة نهاية الأسبوع
            if (date.getDay() === 5 || date.getDay() === 6) { // الجمعة والسبت
                value += Math.floor(Math.random() * 15) + 5;
            }
        } else if (days <= 30) {
            // للشهر: نمط أسبوعي مع اتجاه تصاعدي خفيف
            value = Math.floor(Math.random() * 15) + 10 + (i / 5);
            // زيادة في عطلة نهاية الأسبوع
            if (date.getDay() === 5 || date.getDay() === 6) {
                value += Math.floor(Math.random() * 10) + 5;
            }
        } else {
            // للثلاثة أشهر: نمط موسمي
            const monthFactor = Math.sin((date.getMonth() / 12) * Math.PI * 2) * 10 + 10;
            value = Math.floor(Math.random() * 10) + 15 + monthFactor;
        }
        
        // إضافة تقلبات عشوائية
        const randomVariation = Math.floor(Math.random() * 6) - 3;
        newData.push(Math.max(1, Math.floor(value + randomVariation)));
    }
    
    return { labels: newLabels, data: newData };
}

// تهيئة أزرار تغيير الفترة الزمنية
function setupTimeRangeButtons() {
    document.querySelectorAll('[data-range]').forEach(button => {
        button.addEventListener('click', function() {
            const range = parseInt(this.getAttribute('data-range'));
            
            // تحديث الزر النشط
            document.querySelectorAll('[data-range]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // إظهار مؤشر التحميل
            const chartContainer = document.querySelector('.chart-container');
            const loadingIndicator = document.querySelector('.chart-loading-indicator');
            
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
            }
            
            if (chartContainer) {
                chartContainer.style.opacity = 0.3;
            }
            
            // إنشاء بيانات جديدة مع تأخير بسيط لإظهار مؤشر التحميل
            setTimeout(() => {
                // إنشاء بيانات جديدة
                const { labels, data } = generateDataForTimeRange(range);
                
                // تحديث الرسم البياني
                if (window.visitsChart) {
                    // تحديث البيانات
                    window.visitsChart.data.labels = labels;
                    window.visitsChart.data.datasets[0].data = data;
                    window.visitsChart.update();
                    
                    // إخفاء مؤشر التحميل وإظهار الرسم البياني مرة أخرى
                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'none';
                    }
                    
                    if (chartContainer) {
                        chartContainer.style.opacity = 1;
                        chartContainer.style.transition = 'opacity 0.5s ease';
                    }
                }
            }, 500);
        });
    });
}

// إضافة مؤشر التحميل للرسم البياني
function addLoadingIndicator() {
    const chartContainer = document.querySelector('.chart-container');
    if (chartContainer) {
        // التحقق من عدم وجود مؤشر تحميل سابق
        if (!document.querySelector('.chart-loading-indicator')) {
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'chart-loading-indicator';
            loadingIndicator.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2">جاري تحديث البيانات...</p>
            `;
            loadingIndicator.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                z-index: 10;
                display: none;
            `;
            chartContainer.style.position = 'relative';
            chartContainer.appendChild(loadingIndicator);
        }
    }
}

// تهيئة جميع وظائف الرسم البياني
function initChartFunctions(initialDates, initialVisitCounts) {
    // إضافة مؤشر التحميل
    addLoadingIndicator();
    
    // تهيئة الرسم البياني
    initVisitsChart(initialDates, initialVisitCounts);
    
    // تهيئة أزرار تغيير نوع الرسم البياني
    setupChartTypeButtons();
    
    // تهيئة أزرار تغيير الفترة الزمنية
    setupTimeRangeButtons();
}
