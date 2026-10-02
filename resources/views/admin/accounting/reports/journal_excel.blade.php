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
        <tr>
            <th style="font-weight: bold; background-color: #dbeafe;">Correlativo</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Fecha</th>
            <th style="font-weight: bold; background-color: #dbeafe;">N° Asiento</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Cuenta</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Denominación</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Glosa / Concepto</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Debe (PEN)</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Haber (PEN)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($entries as $entry)
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td>{{ $entry['seq_number'] }}</td>
                <td>{{ $entry['entry_date'] }}</td>
                <td>{{ $entry['entry_number'] }}</td>
                <td></td>
                <td>{{ $entry['concept'] }}</td>
                <td>{{ $entry['period_code'] }}</td>
                <td>{{ number_format($entry['total_debit'], 2, '.', '') }}</td>
                <td>{{ number_format($entry['total_credit'], 2, '.', '') }}</td>
            </tr>
            @foreach ($entry['lines'] as $line)
                <tr>
                    <td></td>
                    <td></td>
                    <td>{{ $line['document_reference'] !== '-' ? $line['document_reference'] : '' }}</td>
                    <td>{{ $line['account_code'] }}</td>
                    <td>{{ $line['account_name'] }}</td>
                    <td>{{ $line['glosa'] }}</td>
                    <td>{{ $line['debit'] > 0 ? number_format($line['debit'], 2, '.', '') : '0.00' }}</td>
                    <td>{{ $line['credit'] > 0 ? number_format($line['credit'], 2, '.', '') : '0.00' }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr style="font-weight: bold; background-color: #e2e8f0;">
            <td colspan="6" style="text-align: right;">TOTAL GENERAL (PEN):</td>
            <td>{{ number_format($grandDebit, 2, '.', '') }}</td>
            <td>{{ number_format($grandCredit, 2, '.', '') }}</td>
        </tr>
    </tbody>
</table>
