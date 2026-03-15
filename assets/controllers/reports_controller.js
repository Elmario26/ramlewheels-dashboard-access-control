import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static targets = ['salesTrend', 'pipeline', 'topBrands', 'inventory', 'service', 'documents'];
    static values = {
        charts: { type: String, default: '{}' },
    };

    initialize() {
        this.charts = [];
    }

    connect() {
        try {
            this.renderSalesTrend();
        } catch (e) {
            console.error('Error rendering sales trend:', e);
        }
        try {
            this.renderPipeline();
        } catch (e) {
            console.error('Error rendering pipeline:', e);
        }
        try {
            this.renderTopBrands();
        } catch (e) {
            console.error('Error rendering top brands:', e);
        }
        try {
            this.renderInventory();
        } catch (e) {
            console.error('Error rendering inventory:', e);
        }
        try {
            this.renderService();
        } catch (e) {
            console.error('Error rendering service:', e);
        }
        try {
            this.renderDocuments();
        } catch (e) {
            console.error('Error rendering documents:', e);
        }
    }

    disconnect() {
        this.charts.forEach((chart) => chart?.destroy());
    }

    register(chart) {
        this.charts.push(chart);
    }

    get chartData() {
        let data = {};
        try {
            const parsed = JSON.parse(this.chartsValue);
            data = parsed;
        } catch (e) {
            console.error('Error parsing chart data:', e);
            data = {};
        }
        return data;
    }

    renderSalesTrend() {
        if (!this.hasSalesTrendTarget) return;
        const { monthlyLabels = [], monthlySales = [], monthlyRevenue = [] } = this.chartData;

        const ctx = this.salesTrendTarget.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [
                    {
                        label: 'Sales',
                        data: monthlySales,
                        borderColor: '#60A5FA',
                        backgroundColor: 'rgba(96, 165, 250, 0.15)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#60A5FA',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Revenue',
                        data: monthlyRevenue,
                        borderColor: '#34D399',
                        backgroundColor: 'rgba(52, 211, 153, 0.1)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#34D399',
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#CBD5E1' },
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                if (context.dataset.label === 'Revenue') {
                                    return `${context.dataset.label}: ₱${Number(context.parsed.y || 0).toLocaleString()}`;
                                }
                                return `${context.dataset.label}: ${context.parsed.y}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: '#94A3B8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94A3B8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        ticks: {
                            color: '#94A3B8',
                            callback: (value) => `₱${Number(value || 0).toLocaleString()}`,
                        },
                        grid: { drawOnChartArea: false },
                    },
                },
            },
        });

        this.register(chart);
    }

    renderPipeline() {
        if (!this.hasPipelineTarget) return;
        const { salesPipeline = { labels: [], values: [] } } = this.chartData;

        const ctx = this.pipelineTarget.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: salesPipeline.labels,
                datasets: [
                    {
                        label: 'Deals',
                        data: salesPipeline.values,
                        backgroundColor: ['#A855F7', '#FBBF24', '#10B981', '#6366F1'],
                        borderRadius: 10,
                        maxBarThickness: 32,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { color: '#94A3B8' },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94A3B8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' },
                    },
                },
            },
        });

        this.register(chart);
    }

    renderTopBrands() {
        if (!this.hasTopBrandsTarget) return;
        const { topBrands = { labels: [], sales: [] } } = this.chartData;

        const ctx = this.topBrandsTarget.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: topBrands.labels,
                datasets: [
                    {
                        label: 'Sales',
                        data: topBrands.sales,
                        backgroundColor: '#60A5FA',
                        borderRadius: 8,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Revenue',
                        data: topBrands.revenue || [],
                        backgroundColor: '#22C55E',
                        borderRadius: 8,
                        maxBarThickness: 28,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#CBD5E1' },
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const label = context.dataset.label || '';
                                if (label === 'Revenue') {
                                    return `${label}: ₱${Number(context.parsed.x || 0).toLocaleString()}`;
                                }
                                return `${label}: ${context.parsed.x}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: '#94A3B8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' },
                    },
                    y: {
                        ticks: { color: '#94A3B8' },
                        grid: { display: false },
                    },
                    y1: {
                        position: 'top',
                        display: false,
                    },
                },
            },
        });

        this.register(chart);
    }

    renderInventory() {
        if (!this.hasInventoryTarget) return;
        const { inventory = { labels: [], values: [] } } = this.chartData;

        const ctx = this.inventoryTarget.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: inventory.labels,
                datasets: [
                    {
                        data: inventory.values,
                        backgroundColor: ['#10B981', '#3B82F6', '#FBBF24', '#EF4444'],
                        borderColor: '#0F172A',
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#CBD5E1' },
                    },
                },
            },
        });

        this.register(chart);
    }

    renderService() {
        if (!this.hasServiceTarget) return;
        const { service = {} } = this.chartData;
        const labels = ['Completed', 'In Progress', 'Pending'];
        const values = [service.completed || 0, service.inProgress || 0, service.pending || 0];

        const ctx = this.serviceTarget.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Services',
                        data: values,
                        backgroundColor: ['#22C55E', '#FBBF24', '#F97316'],
                        borderRadius: 10,
                        maxBarThickness: 36,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { color: '#94A3B8' },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94A3B8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' },
                    },
                },
            },
        });

        this.register(chart);
    }

    renderDocuments() {
        if (!this.hasDocumentsTarget) return;
        const { documents = { labels: [], values: [] } } = this.chartData;

        const ctx = this.documentsTarget.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: documents.labels,
                datasets: [
                    {
                        data: documents.values,
                        backgroundColor: ['#F472B6', '#38BDF8', '#A78BFA', '#F59E0B', '#22C55E'],
                        borderColor: '#0F172A',
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#CBD5E1' },
                    },
                },
            },
        });

        this.register(chart);
    }
}

