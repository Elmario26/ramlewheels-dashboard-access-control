import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    connect() {
        this.initializeSalesTrendChart();
        this.initializeInventoryChart();
        this.initializeConditionChart();
        this.initializeBrandsChart();
    }

    initializeSalesTrendChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        // Use dynamic data if available, otherwise fallback to sample data
        const monthlyData = window.dashboardData?.monthlyData;
        const labels = monthlyData?.labels || ['January', 'February', 'March', 'April', 'May', 'June'];
        const salesData = monthlyData?.sales || [12, 19, 15, 25, 22, 30];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: salesData,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#fff',
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
                        labels: {
                            color: 'rgb(156, 163, 175)'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(75, 85, 99, 0.2)'
                        },
                        ticks: {
                            color: 'rgb(156, 163, 175)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(75, 85, 99, 0.2)'
                        },
                        ticks: {
                            color: 'rgb(156, 163, 175)'
                        }
                    }
                }
            }
        });
    }

    initializeInventoryChart() {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) return;

        // Use dynamic data if available, otherwise fallback to sample data
        const monthlyData = window.dashboardData?.monthlyData;
        const labels = monthlyData?.labels || ['January', 'February', 'March', 'April', 'May', 'June'];
        const inventoryData = monthlyData?.inventory || [25, 35, 15, 10, 5];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Inventory Count',
                    data: inventoryData,
                    backgroundColor: 'rgba(168, 85, 247, 0.8)',
                    borderColor: '#A855F7',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(75, 85, 99, 0.2)'
                        },
                        ticks: {
                            color: 'rgb(156, 163, 175)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgb(156, 163, 175)'
                        }
                    }
                }
            }
        });
    }

    initializeConditionChart() {
        const ctx = document.getElementById('conditionChart');
        if (!ctx) return;

        // Use dynamic data if available, otherwise fallback to sample data
        const conditions = window.dashboardData?.conditions;
        const labels = ['Excellent', 'Good', 'Fair', 'Poor'];
        const data = conditions ? [
            conditions['Excellent'] || 0,
            conditions['Good'] || 0,
            conditions['Fair'] || 0,
            conditions['Poor'] || 0
        ] : [40, 35, 15, 10];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        '#22C55E',
                        '#3B82F6',
                        '#FBBF24',
                        '#EF4444'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgb(156, 163, 175)',
                            padding: 20
                        }
                    }
                }
            }
        });
    }

    initializeBrandsChart() {
        const ctx = document.getElementById('brandsChart');
        if (!ctx) return;

        // Use dynamic data if available, otherwise fallback to sample data
        const topBrands = window.dashboardData?.topBrands;
        const labels = topBrands ? Object.keys(topBrands) : ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes'];
        const data = topBrands ? Object.values(topBrands) : [28, 22, 18, 15, 12];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Vehicles',
                    data: data,
                    backgroundColor: [
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ],
                    borderColor: [
                        '#FB923C',
                        '#FBBF24',
                        '#22C55E',
                        '#3B82F6',
                        '#A855F7'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(75, 85, 99, 0.2)'
                        },
                        ticks: {
                            color: 'rgb(156, 163, 175)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgb(156, 163, 175)'
                        }
                    }
                }
            }
        });
    }
}