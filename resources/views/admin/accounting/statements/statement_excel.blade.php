<table>
    <thead>
        <tr>
            <th colspan="{{ $has_comparison ? 6 : 3 }}" style="font-weight: bold; font-size: 14pt; text-align: center;">{{ $title }}</th>
        </tr>
        <tr>
            <th colspan="{{ $has_comparison ? 6 : 3 }}" style="text-align: center;">{{ $business?->nombre_comercial ?? 'ERP-FVC' }} - RUC: {{ $business?->ruc ?? '-' }}</th>
        </tr>
        <tr>
            <th colspan="{{ $has_comparison ? 6 : 3 }}" style="text-align: center;">
                Período: {{ $period['code'] ?? ($period['from'] . ' AL ' . $period['to']) }}
                @if($has_comparison)
                    | Comparativo con: {{ $comparison['period']['code'] ?? ($comparison['period']['from'] . ' AL ' . $comparison['period']['to']) }}
                @endif
                | Generado el: {{ $generatedAt->format('d/m/Y H:i') }}
            </th>
        </tr>
        <tr></tr>
        <tr>
            <th style="font-weight: bold; background-color: #dbeafe; text-align: center;">Código</th>
            <th style="font-weight: bold; background-color: #dbeafe;">Denominación / Rubro</th>
            <th style="font-weight: bold; background-color: #bfdbfe; text-align: right;">Actual (PEN)</th>
            @if($has_comparison)
                <th style="font-weight: bold; background-color: #e2e8f0; text-align: right;">Anterior (PEN)</th>
                <th style="font-weight: bold; background-color: #fed7aa; text-align: right;">Var. Absoluta (PEN)</th>
                <th style="font-weight: bold; background-color: #fef08a; text-align: right;">Var. (%)</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($lines as $line)
            @php
                $isHeader = $line['line_type'] === 'HEADER';
                $isSubtotal = $line['line_type'] === 'SUBTOTAL';
                $isTotal = $line['line_type'] === 'TOTAL';
                $bgColor = $isHeader ? '#f1f5f9' : ($isTotal ? '#bfdbfe' : ($isSubtotal ? '#f8fafc' : '#ffffff'));
                $fontWeight = ($isHeader || $isSubtotal || $isTotal || ($line['is_bold'] ?? false)) ? 'bold' : 'normal';
                $indent = str_repeat('    ', $line['indent_level'] ?? 0);
            @endphp
            <tr style="background-color: {{ $bgColor }};">
                <td style="text-align: center; font-weight: {{ $fontWeight }};">{{ $line['line_code'] }}</td>
                <td style="font-weight: {{ $fontWeight }};">{{ $indent }}{{ $line['line_name'] }}</td>
                <td style="text-align: right; font-weight: {{ $fontWeight }};">
                    @if(!$isHeader)
                        {{ number_format($line['current_amount'], 2, '.', '') }}
                    @endif
                </td>
                @if($has_comparison)
                    <td style="text-align: right; font-weight: {{ $fontWeight }};">
                        @if(!$isHeader && $line['comparison_amount'] !== null)
                            {{ number_format($line['comparison_amount'], 2, '.', '') }}
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: {{ $fontWeight }};">
                        @if(!$isHeader && $line['variance_abs'] !== null)
                            {{ number_format($line['variance_abs'], 2, '.', '') }}
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: {{ $fontWeight }};">
                        @if(!$isHeader && $line['variance_pct'] !== null)
                            {{ number_format($line['variance_pct'], 2, '.', '') }}%
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        <tr></tr>
        @if(isset($totals['is_balanced']))
            <tr>
                <td colspan="{{ $has_comparison ? 6 : 3 }}" style="font-weight: bold; background-color: {{ $totals['is_balanced'] ? '#dcfce7' : '#fee2e2' }};">
                    ESTADO DE ECUACIÓN CONTABLE: {{ $totals['is_balanced'] ? 'CUADRADO (Activo = Pasivo + Patrimonio)' : 'DESCUADRADO' }}
                    | Total Activo: S/ {{ number_format($totals['total_assets'] ?? 0, 2, '.', '') }}
                    | Total Pasivo + Patrimonio: S/ {{ number_format($totals['total_liabilities_and_equity'] ?? 0, 2, '.', '') }}
                    | Descuadre: S/ {{ number_format($totals['imbalance'] ?? 0, 2, '.', '') }}
                </td>
            </tr>
        @endif
        <tr>
            <td colspan="{{ $has_comparison ? 6 : 3 }}" style="font-weight: bold; background-color: #f1f5f9;">
                RESULTADO DEL EJERCICIO (NETO): S/ {{ number_format($net_result, 2, '.', '') }}
            </td>
        </tr>
    </tbody>
</table>
