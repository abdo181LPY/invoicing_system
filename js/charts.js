/**
 * ملف إنشاء الرسوم البيانية والتحليلات
 */

// مخطط الأعمدة
function createBarChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'bar',
        data: data,
        options: chartOptions
    });
}

// مخطط الخط
function createLineChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'line',
        data: data,
        options: chartOptions
    });
}

// مخطط الدائرة
function createPieChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        }
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'pie',
        data: data,
        options: chartOptions
    });
}

// مخطط الدونات
function createDoughnutChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        cutout: '60%'
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: chartOptions
    });
}

// مخطط المساحة
function createAreaChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // تعديل البيانات لإنشاء مخطط المساحة (إضافة خاصية fill: true)
    if (data && data.datasets) {
        data.datasets.forEach(dataset => {
            dataset.fill = true;
        });
    }
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'line',
        data: data,
        options: chartOptions
    });
}

// مخطط الأعمدة المكدسة
function createStackedBarChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                stacked: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            x: {
                stacked: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'bar',
        data: data,
        options: chartOptions
    });
}

// مخطط الرادار
function createRadarChart(elementId, data, options = {}) {
    const ctx = document.getElementById(elementId).getContext('2d');
    
    // الخيارات الافتراضية
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            title: {
                display: options.title ? true : false,
                text: options.title || '',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 16
                }
            },
            tooltip: {
                rtl: true,
                textDirection: 'rtl'
            }
        },
        scales: {
            r: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                },
                pointLabels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };
    
    // دمج الخيارات المقدمة مع الخيارات الافتراضية
    const chartOptions = { ...defaultOptions, ...options };
    
    // إنشاء المخطط
    return new Chart(ctx, {
        type: 'radar',
        data: data,
        options: chartOptions
    });
}

// الحصول على ألوان عشوائية للمخططات
function getRandomColors(count) {
    const colors = [];
    const transparentColors = [];
    
    for (let i = 0; i < count; i++) {
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        
        colors.push(`rgb(${r}, ${g}, ${b})`);
        transparentColors.push(`rgba(${r}, ${g}, ${b}, 0.2)`);
    }
    
    return {
        solid: colors,
        transparent: transparentColors
    };
}

// الحصول على ألوان ثابتة للمخططات
function getChartColors() {
    return {
        solid: [
            'rgb(54, 162, 235)',    // أزرق
            'rgb(255, 99, 132)',    // أحمر
            'rgb(75, 192, 192)',    // تركواز
            'rgb(255, 159, 64)',    // برتقالي
            'rgb(153, 102, 255)',   // بنفسجي
            'rgb(255, 205, 86)',    // أصفر
            'rgb(201, 203, 207)',   // رمادي
            'rgb(0, 150, 136)',     // أخضر
            'rgb(233, 30, 99)',     // وردي
            'rgb(96, 125, 139)'     // أزرق رمادي
        ],
        transparent: [
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 99, 132, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(255, 159, 64, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(255, 205, 86, 0.2)',
            'rgba(201, 203, 207, 0.2)',
            'rgba(0, 150, 136, 0.2)',
            'rgba(233, 30, 99, 0.2)',
            'rgba(96, 125, 139, 0.2)'
        ]
    };
}

// تحويل البيانات من التنسيق العادي إلى تنسيق المخطط
function prepareChartData(labels, datasets, colorType = 'solid') {
    const colors = getChartColors();
    const chartData = {
        labels: labels,
        datasets: []
    };
    
    datasets.forEach((dataset, index) => {
        chartData.datasets.push({
            label: dataset.label,
            data: dataset.data,
            backgroundColor: colorType === 'transparent' ? 
                colors.transparent[index % colors.transparent.length] : 
                colors.solid[index % colors.solid.length],
            borderColor: colors.solid[index % colors.solid.length],
            borderWidth: 1
        });
    });
    
    return chartData;
}

// تحويل البيانات لمخطط الدائرة والدونات
function preparePieChartData(labels, data) {
    const colors = getChartColors();
    
    return {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors.solid.slice(0, data.length),
            borderColor: 'white',
            borderWidth: 1
        }]
    };
}

// تنسيق الأرقام في المخططات
function formatNumber(number, decimals = 0) {
    return number.toLocaleString('ar-IQ', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// تحويل التاريخ إلى تنسيق مناسب
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('ar-IQ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// إعداد مخطط المبيعات اليومية
function setupDailySalesChart(elementId, data) {
    const labels = data.map(item => formatDate(item.date));
    const datasets = [
        {
            label: 'المبيعات',
            data: data.map(item => item.total),
            yAxisID: 'y'
        },
        {
            label: 'عدد الفواتير',
            data: data.map(item => item.count),
            yAxisID: 'y1'
        }
    ];
    
    const chartData = prepareChartData(labels, datasets);
    
    return createLineChart(elementId, chartData, {
        title: 'المبيعات اليومية',
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'المبيعات (دينار)',
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: 'عدد الفواتير',
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    });
}

// إعداد مخطط المبيعات حسب المحافظة
function setupSalesByGovernorateChart(elementId, data) {
    const labels = data.map(item => item.governorate);
    const values = data.map(item => item.total);
    
    const chartData = preparePieChartData(labels, values);
    
    return createPieChart(elementId, chartData, {
        title: 'المبيعات حسب المحافظة',
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${formatNumber(value)} (${percentage}%)`;
                    }
                }
            }
        }
    });
}

// إعداد مخطط أداء الموظفين
function setupUserPerformanceChart(elementId, data) {
    const datasets = [];
    
    // تجميع البيانات حسب التاريخ
    const dateLabels = new Set();
    data.forEach(user => {
        user.daily_data.forEach(day => {
            dateLabels.add(day.date);
        });
    });
    
    // ترتيب التواريخ
    const sortedLabels = Array.from(dateLabels).sort();
    
    // إنشاء مجموعات البيانات لكل مستخدم
    data.forEach(user => {
        const userData = {
            label: user.username,
            data: []
        };
        
        // ملء البيانات لكل تاريخ
        sortedLabels.forEach(date => {
            const dayData = user.daily_data.find(day => day.date === date);
            userData.data.push(dayData ? dayData.count : 0);
        });
        
        datasets.push(userData);
    });
    
    const formattedLabels = sortedLabels.map(date => formatDate(date));
    const chartData = prepareChartData(formattedLabels, datasets);
    
    return createLineChart(elementId, chartData, {
        title: 'أداء الموظفين',
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'عدد الفواتير',
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    });
}

// تصدير الدوال
window.chartUtils = {
    createBarChart,
    createLineChart,
    createPieChart,
    createDoughnutChart,
    createAreaChart,
    createStackedBarChart,
    createRadarChart,
    getRandomColors,
    getChartColors,
    prepareChartData,
    preparePieChartData,
    formatNumber,
    formatDate,
    setupDailySalesChart,
    setupSalesByGovernorateChart,
    setupUserPerformanceChart
};
