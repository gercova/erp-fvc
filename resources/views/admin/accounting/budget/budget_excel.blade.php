<table>
    <thead>
        <tr>
            <th colspan="13" style="font-size: 16px; font-weight: bold; text-align: center;">
                {{ $business->nombre ?? 'INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO "FRANCISCO VIGO CABALLERO"' }}
            </th>
        </tr>
        <tr>
            <th colspan="13" style="font-size: 13px; font-weight: bold; text-align: center;">
                REPORTE DE EJECUCIÓN PRESUPUESTAL - EJERCICIO FISCAL {{ $budget['fiscal_year'] ?? date('Y') }}
            </th>
        </tr>
        <tr>
            <th colspan="13" style="text-align: center; color: #555;">
                Presupuesto: {{ $budget['budget_code'] }} - {{ $budget['budget_name'] }} | Estado: {{ $budget['status'] }} | Generado: {{ $generatedAt->format('d/m/Y H:i') }}
            </th>
        </tr>
        <tr><th colspan="13"></th></tr>
        <tr style="background-color: #2c3e50; color: #ffffff; font-weight: bold;">
            <th>Mes</th>
            <th>Cuenta / Partida</th>
            <th>Rubro / Descripción</th>
            <th>Centro de Costo</th>
            <th>Fuente</th>
            <th>Presupuesto Inicial (PIA)</th>
            <th>Modificaciones</th>
            <th>Presupuesto Vigente (PIM)</th>
            <th>Comprometido</th>
            <th>Devengado (Contabilidad)</th>
            <th>Girado / Pagado</th>
            <th>Saldo Disponible</th>
            <th>% Ejecución</th>
            <th>Semáforo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($budget['lines'] ?? [] as $line)
            <tr>
                <td style="text-align: center;">Mes {{ $line['period_month'] }}</td>
                <td style="text-align: left;">{{ $line['account_code'] ?? '-' }}</td>
                <td>{{ $line['category_name'] ?? '' }}</td>
                <td>{{ $line['cost_center_code'] ?? 'General' }}</td>
                <td>{{ $line['fund_source_id'] ? 'FTE-'.$line['fund_source_id'] : 'Institucional' }}</td>
                <td style="text-align: right;">{{ number_format($line['allocated_amount'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($line['modified_amount'], 2, '.', '') }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($line['current_amount'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($line['committed_amount'], 2, '.', '') }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($line['accrued_amount'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($line['paid_amount'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($line['available_amount'], 2, '.', '') }}</td>
                <td style="text-align: center;">{{ $line['execution_percentage'] }}%</td>
                <td style="text-align: center;">{{ $line['traffic_light'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background-color: #ecf0f1; font-weight: bold;">
            <td colspan="5" style="text-align: right;">TOTAL GENERAL</td>
            <td style="text-align: right;">{{ number_format($budget['total_allocated'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($budget['total_modified'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($budget['total_current'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($budget['total_committed'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($budget['total_accrued'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($budget['total_paid'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($budget['total_available'], 2, '.', '') }}</td>
            <td style="text-align: center;">{{ $budget['overall_percentage'] }}%</td>
            <td style="text-align: center;">{{ $budget['traffic_light'] }}</td>
        </tr>
    </tfoot>
</table>

@if(!empty($matrix['activities']))
<br><br>
<table>
    <thead>
        <tr>
            <th colspan="17" style="font-size: 14px; font-weight: bold; background-color: #34495e; color: #ffffff;">
                MATRIZ DE EJECUCIÓN POR CENTROS DE COSTO × MES (CALCULADO DESDE ASIENTOS CONTABLES)
            </th>
        </tr>
        <tr style="background-color: #bdc3c7; font-weight: bold;">
            <th>Actividad / Centro de Costo</th>
            @for($m = 1; $m <= 12; $m++)
                <th>Mes {{ $m }} (Ejec / PIM)</th>
            @endfor
            <th>Total PIM</th>
            <th>Total Ejecutado</th>
            <th>% Ejec.</th>
            <th>Semáforo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($matrix['activities'] as $act)
            <tr>
                <td style="font-weight: bold;">{{ $act['activity_name'] }} ({{ $act['cost_center_code'] }})</td>
                @for($m = 1; $m <= 12; $m++)
                    @php $cell = $act['months'][$m]; @endphp
                    <td style="text-align: center;">
                        {{ number_format($cell['executed'], 2, '.', '') }} / {{ number_format($cell['budgeted'], 2, '.', '') }}
                    </td>
                @endfor
                <td style="text-align: right; font-weight: bold;">{{ number_format($act['annual_budgeted'], 2, '.', '') }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($act['annual_executed'], 2, '.', '') }}</td>
                <td style="text-align: center; font-weight: bold;">{{ $act['annual_percentage'] }}%</td>
                <td style="text-align: center;">{{ $act['traffic_light'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background-color: #ecf0f1; font-weight: bold;">
            <td>TOTAL INSTITUCIONAL</td>
            @for($m = 1; $m <= 12; $m++)
                @php $mTot = $matrix['monthly_totals'][$m]; @endphp
                <td style="text-align: center;">
                    {{ number_format($mTot['executed'], 2, '.', '') }} / {{ number_format($mTot['budgeted'], 2, '.', '') }}
                </td>
            @endfor
            <td style="text-align: right;">{{ number_format($matrix['annual_grand_budget'], 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($matrix['annual_grand_executed'], 2, '.', '') }}</td>
            <td style="text-align: center;">{{ $matrix['annual_grand_pct'] }}%</td>
            <td style="text-align: center;">{{ $budget['traffic_light'] }}</td>
        </tr>
    </tfoot>
</table>
@endif
