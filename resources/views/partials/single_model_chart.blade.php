<!-- Lightweight Charts (fixed version) -->
<script src="https://unpkg.com/lightweight-charts@4.1.1/dist/lightweight-charts.standalone.production.js"></script>

<div class="card" style="padding:0; margin-bottom:25px;">
  <h3 style="padding:12px 16px;">Total Account Value</h3>
  <div id="equity-chart-single" style="height:380px;"></div>
</div>

<script>
(function () {
    const container = document.getElementById('equity-chart-single');
    if (!container || typeof window.LightweightCharts === 'undefined') {
        console.warn('LightweightCharts not loaded or container missing');
        return;
    }

    const chart = window.LightweightCharts.createChart(container, {
        width: container.clientWidth,
        height: 360,
        layout: {
            background: { color: '#ffffff' },
            textColor: '#444444',
        },
        grid: {
            vertLines: { color: '#f3f4f6' },
            horzLines: { color: '#f3f4f6' },
        },
        rightPriceScale: {
            borderColor: '#e5e7eb',
        },
        timeScale: {
            borderColor: '#e5e7eb',
            timeVisible: true,
            secondsVisible: false,
        },
    });

    const equityData = @json(
        collect($equityHistory ?? [])->map(fn($row) => [
            'time'  => $row['time'],
            'value' => (float) $row['value'],
        ])
    );

    const startEquity = {{ (float)($startEquity ?? 10000) }};

    if (typeof chart.addAreaSeries === 'function') {
        const drawdownSeries = chart.addAreaSeries({
            lineColor: 'rgba(0,0,0,0)',
            topColor: 'rgba(248, 113, 113, 0.35)',
            bottomColor: 'rgba(248, 113, 113, 0.10)',
        });

        const shadeData = equityData.map(p => ({
            time: p.time,
            value: startEquity,
        }));
        drawdownSeries.setData(shadeData);
    } else {
        console.warn('chart.addAreaSeries is not available – skipping drawdown shading');
    }

    const lineSeries = chart.addLineSeries({
        color: '#059669',
        lineWidth: 2,
    });
    lineSeries.setData(equityData);

    const last = equityData[equityData.length - 1];
    if (last) {
        const label = document.createElement('div');
        label.style.position = 'absolute';
        label.style.transform = 'translate(0, -50%)';
        label.style.padding = '4px 10px';
        label.style.borderRadius = '999px';
        label.style.background = '#059669';
        label.style.color = '#fff';
        label.style.fontSize = '12px';
        label.style.fontWeight = '600';
        label.style.whiteSpace = 'nowrap';
        label.innerText = '$' + last.value.toLocaleString(undefined, { maximumFractionDigits: 2 });

        container.style.position = 'relative';
        container.appendChild(label);

        const api = chart;

        function placeLabel() {
            const timeScale = api.timeScale();
            const x = timeScale.timeToCoordinate(last.time);
            const y = lineSeries.priceToCoordinate(last.value);
            if (x == null || y == null) return;

            label.style.left = (x + 8) + 'px';
            label.style.top  = y + 'px';
        }

        placeLabel();
        api.timeScale().subscribeVisibleTimeRangeChange(placeLabel);
        window.addEventListener('resize', () => {
            api.applyOptions({ width: container.clientWidth });
            placeLabel();
        });
    }
})();
</script>
