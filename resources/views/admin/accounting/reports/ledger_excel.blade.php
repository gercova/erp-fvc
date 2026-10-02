<table>
    <thead>
        <tr>
            <th colspan="8" style="font-weight: bold; font-size: 14pt; text-align: center;">{{ $title }}</th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center;">{{ $business?->nombre_comercial ?? 'ERP-FVC' }} - RUC: {{ $business?->ruc ?? '-' }}</th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center;">Generado el: {{ $generatedAt->format('d/m/Y H:i') }}</th>
        </tr>
        <tr></tr>
    </thead>
    <tbody>
        @foreach ($accounts as $code => $acc)
            <tr style="background-color: #bae6fd; font-weight: bold;">
                <td colspan="8">CUENTA: {{ $acc['account_code'] }} - {{ $acc['account_name'] }} (Naturaleza: {{ $acc['nature'] }})</td>
            </tr>
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <th>Fecha</th>
                <th>N° Asiento</th>
                <th>Glosa / Concepto</th>
                <th>Tercero / Centro Costo</th>
                <th>Doc. Ref.</th>
                <th>Debe (PEN)</th>
                <th>Haber (PEN)</th>
                <th>Saldo (PEN)</th>
            </tr>
            <tr style="background-color: #dcfce7; font-weight: bold;">
                <td>-</td>
                <td>-</td>
                <td colspan="3">SALDO INICIAL ACUMULADO</td>
                <td>{{ number_format($acc['opening_debit'], 2, '.', '') }}</td>
                <td>{{ number_format($acc['opening_credit'], 2, '.', '') }}</td>
                <td>{{ number_format($acc['opening_balance'], 2, '.', '') }}</td>
            </tr>
            @foreach ($acc['transactions'] as $tx)
                <tr>
                    <td>{{ $tx['entry_date'] }}</td>
                    <td>{{ $tx['entry_number'] }}</td>
                    <td>{{ $tx['concept'] }}</td>
                    <td>{{ $tx['third_party'] !== '-' ? $tx['third_party'] : ($tx['cost_center'] !== '-' ? $tx['cost_center'] : '') }}</td>
                    <td>{{ $tx['document_reference'] !== '-' ? $tx['document_reference'] : '' }}</td>
                    <td>{{ $tx['debit'] > 0 ? number_format($tx['debit'], 2, '.', '') : '0.00' }}</td>
                    <td>{{ $tx['credit'] > 0 ? number_format($tx['credit'], 2, '.', '') : '0.00' }}</td>
                    <td>{{ number_format($tx['running_balance'], 2, '.', '') }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #e2e8f0;">
                <td colspan="5" style="text-align: right;">TOTALES PERÍODO Y SALDO FINAL:</td>
                <td>{{ number_format($acc['period_debit'], 2, '.', '') }}</td>
                <td>{{ number_format($acc['period_credit'], 2, '.', '') }}</td>
                <td>{{ number_format($acc['final_balance'], 2, '.', '') }}</td>
            </tr>
            <tr></tr>
        @endforeach
        <tr style="font-weight: bold; background-color: #0284c7; color: white;">
            <td colspan="5" style="text-align: right;">RESUMEN CONSOLIDADO:</td>
            <td>{{ number_format($summary['period_debit'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($summary['period_credit'] ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($summary['final_balance'] ?? 0, 2, '.', '') }}</td>
        </tr>
    </tbody>
</table>
