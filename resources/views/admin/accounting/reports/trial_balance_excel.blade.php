<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 14pt; text-align: center;">{{ $title }}</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">{{ $business?->nombre_comercial ?? 'ERP-FVC' }} - RUC: {{ $business?->ruc ?? '-' }}</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Generado el: {{ $generatedAt->format('d/m/Y H:i') }}</th>
        </tr>
        <tr></tr>
        <tr>
            <th rowspan="2" style="font-weight: bold; background-color: #d1fae5; text-align: center;">Código</th>
            <th rowspan="2" style="font-weight: bold; background-color: #d1fae5;">Denominación de la Cuenta</th>
            <th colspan="2" style="font-weight: bold; background-color: #bbf7d0; text-align: center;">SUMAS DEL MAYOR</th>
            <th colspan="2" style="font-weight: bold; background-color: #fed7aa; text-align: center;">SALDOS</th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #dcfce7; text-align: right;">Debe (PEN)</th>
            <th style="font-weight: bold; background-color: #fee2e2; text-align: right;">Haber (PEN)</th>
            <th style="font-weight: bold; background-color: #dcfce7; text-align: right;">Deudor (PEN)</th>
            <th style="font-weight: bold; background-color: #fee2e2; text-align: right;">Acreedor (PEN)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($accounts as $acc)
            <tr>
                <td style="text-align: center;">{{ $acc['code'] }}</td>
                <td>{{ $acc['name'] }}</td>
                <td style="text-align: right;">{{ number_format($acc['total_debit'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($acc['total_credit'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($acc['saldo_deudor'], 2, '.', '') }}</td>
                <td style="text-align: right;">{{ number_format($acc['saldo_acreedor'], 2, '.', '') }}</td>
            </tr>
        @endforeach
        <tr style="font-weight: bold; background-color: #e2e8f0;">
            <td colspan="2" style="text-align: right;">TOTALES GENERALES (PEN):</td>
            <td style="text-align: right;">{{ number_format($totals['total_debit'] ?? 0, 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($totals['total_credit'] ?? 0, 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($totals['saldo_deudor'] ?? 0, 2, '.', '') }}</td>
            <td style="text-align: right;">{{ number_format($totals['saldo_acreedor'] ?? 0, 2, '.', '') }}</td>
        </tr>
        <tr></tr>
        <tr>
            <td colspan="6" style="font-weight: bold;">
                ESTADO DE PARTIDA DOBLE: {{ $verification['status'] ?? 'CUADRADO' }}
                (Diferencia Sumas: S/. {{ number_format($verification['debit_credit_diff'] ?? 0, 2, '.', '') }}, Diferencia Saldos: S/. {{ number_format($verification['balances_diff'] ?? 0, 2, '.', '') }})
            </td>
        </tr>
    </tbody>
</table>
