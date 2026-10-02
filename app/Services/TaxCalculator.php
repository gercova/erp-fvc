<?php

namespace App\Services;

use App\Models\IgvTypeAffection;

class TaxCalculator
{
    /**
     * Resolve tax affection details by id or Sunat code.
     */
    public function resolveTaxAffection(mixed $idcodigoIgv): array
    {
        static $affections          = null;
        static $affectionsByCode    = null;

        if ($affections === null) {
            $all                = IgvTypeAffection::all();
            $affections         = $all->keyBy('id');
            $affectionsByCode   = $all->keyBy('codigo');
        }

        $affection = null;
        if (! empty($idcodigoIgv)) {
            $affection = $affections->get($idcodigoIgv) ?? $affectionsByCode->get((string) $idcodigoIgv);
        }

        $code = $affection ? trim((string) $affection->codigo) : '10';
        $id = $affection ? (int) $affection->id : ($affectionsByCode->get('10')?->id ?? 1);

        return [
            'id'        => $id,
            'codigo'    => $code,
            'affection' => $affection,
        ];
    }

    /**
     * Calculate tax breakdown (gravada, exonerada, inafecta, gratuita, igv, total)
     * using the standard tax algorithm across POS, Quotes, and Sales Notes.
     */
    public function calculate(array $products, float $globalDiscount = 0.0): array
    {
        $items = collect($products)->map(function ($product) {
            $quantity   = max(1, (float) ($product['cantidad'] ?? 1));
            $unitPrice  = (float) ($product['precio_venta'] ?? 0);
            $baseTotal  = round($unitPrice * $quantity, 2);

            return array_merge($product, [
                'cantidad'          => $quantity,
                'precio_venta'      => $unitPrice,
                'precio_total_base' => $baseTotal,
            ]);
        })->values();

        $grossTotal         = round((float) $items->sum('precio_total_base'), 2);
        $discountToApply    = round(min(max($globalDiscount, 0), $grossTotal), 2);
        $runningDiscount    = $discountToApply;

        $mapped = $items->map(function ($product, $index) use ($items, $grossTotal, $discountToApply, &$runningDiscount) {
            $isLast         = $index === ($items->count() - 1);
            $baseTotal      = (float) $product['precio_total_base'];
            $lineDiscount   = $isLast
                ? round($runningDiscount, 2)
                : round(($grossTotal > 0 ? ($baseTotal / $grossTotal) : 0) * $discountToApply, 2);

            $runningDiscount = round($runningDiscount - $lineDiscount, 2);
            $lineTotal       = round(max($baseTotal - $lineDiscount, 0), 2);
            $quantity        = max((float) ($product['cantidad'] ?? 1), 1);

            $taxAffection    = $this->resolveTaxAffection($product['idcodigo_igv'] ?? $product['id_afectacion_igv'] ?? null);
            $code            = $taxAffection['codigo'];
            $affectionId     = $taxAffection['id'];

            // 10: Gravado (18% IGV)
            // 20: Exonerado (0% IGV)
            // 30: Inafecto (0% IGV)
            // 40: Exportacion (0% IGV)
            // 11-16, 21, 31-37: Gratuita (0 costo al cliente, valor referencial)
            $isGratuita     = in_array($code, ['11', '12', '13', '14', '15', '16', '21', '31', '32', '33', '34', '35', '36', '37'], true);
            $isExonerada    = ($code === '20');
            $isInafecta     = in_array($code, ['30', '40'], true);

            if ($isGratuita) {
                $taxStatus          = 'gratuita';
                $valorReferencial   = $lineTotal;
                $lineTotalFinal     = 0.00;
                $valorTotal         = 0.00;
                $igvAmount          = 0.00;
                $unitGross          = 0.00;
                $unitNet            = 0.00;
            } elseif ($isExonerada) {
                $taxStatus          = 'exonerada';
                $valorReferencial   = 0.00;
                $lineTotalFinal     = $lineTotal;
                $valorTotal         = $lineTotal;
                $igvAmount          = 0.00;
                $unitGross          = round($lineTotal / $quantity, 2);
                $unitNet            = $unitGross;
            } elseif ($isInafecta) {
                $taxStatus          = 'inafecta';
                $valorReferencial   = 0.00;
                $lineTotalFinal     = $lineTotal;
                $valorTotal         = $lineTotal;
                $igvAmount          = 0.00;
                $unitGross          = round($lineTotal / $quantity, 2);
                $unitNet            = $unitGross;
            } else {
                $taxStatus          = 'gravada';
                $valorReferencial   = 0.00;
                $lineTotalFinal     = $lineTotal;
                $igvFactor          = 1.18;
                $valorTotal         = round($lineTotal / $igvFactor, 2);
                $igvAmount          = round($lineTotal - $valorTotal, 2);
                $unitGross          = round($lineTotal / $quantity, 2);
                $unitNet            = round($unitGross / $igvFactor, 10);
            }

            return array_merge($product, [
                'id_afectacion_igv'         => $affectionId,
                'codigo_afectacion'         => $code,
                'tax_status'                => $taxStatus,
                'valor_referencial'         => $valorReferencial,
                'descuento'                 => number_format($lineDiscount, 2, '.', ''),
                'precio_total_descuento'    => number_format($lineTotalFinal, 2, '.', ''),
                'precio_unitario_descuento' => number_format($unitGross, 2, '.', ''),
                'valor_total_descuento'     => number_format($valorTotal, 2, '.', ''),
                'valor_unitario_descuento'  => number_format($unitNet, 10, '.', ''),
                'igv_monto'                 => number_format($igvAmount, 2, '.', ''),
            ]);
        });

        $gravada    = round((float) $mapped->where('tax_status', 'gravada')->sum('valor_total_descuento'), 2);
        $exonerada  = round((float) $mapped->where('tax_status', 'exonerada')->sum('valor_total_descuento'), 2);
        $inafecta   = round((float) $mapped->where('tax_status', 'inafecta')->sum('valor_total_descuento'), 2);
        $gratuita   = round((float) $mapped->where('tax_status', 'gratuita')->sum('valor_referencial'), 2);
        $igv        = round((float) $mapped->sum('igv_monto'), 2);
        $total      = round((float) $mapped->sum('precio_total_descuento'), 2);
        $subtotal   = round($gravada + $exonerada + $inafecta, 2);

        return [
            'discount'  => number_format($discountToApply, 2, '.', ''),
            'subtotal'  => number_format($subtotal, 2, '.', ''),
            'gravada'   => number_format($gravada, 2, '.', ''),
            'exonerada' => number_format($exonerada, 2, '.', ''),
            'inafecta'  => number_format($inafecta, 2, '.', ''),
            'gratuita'  => number_format($gratuita, 2, '.', ''),
            'igv'       => number_format($igv, 2, '.', ''),
            'total'     => number_format($total, 2, '.', ''),
            'items'     => $mapped->all(),
        ];
    }
}
