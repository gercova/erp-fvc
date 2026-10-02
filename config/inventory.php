<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Cost Method
    |--------------------------------------------------------------------------
    |
    | Defines the valuation method for calculating inventory costs upon purchase:
    |
    | 1. 'weighted_average' (PMP - Precio Medio Ponderado / Promedio Ponderado):
    |    Calculates the new weighted average unit cost using previous stock and new purchase:
    |
    |      If previous current stock > 0:
    |        New Cost = ((Previous Current Stock * Previous Cost) + (Incoming Quantity * Purchase Price))
    |                   / (Previous Current Stock + Incoming Quantity)
    |
    |      If previous current stock <= 0 (e.g. 0 or negative due to sales without prior stock):
    |        New Cost = Purchase Price
    |
    |    Example (3-purchase sequence):
    |      - Purchase 1: 10 units @ S/ 10.00 -> Stock = 10, Cost = S/ 10.00
    |      - Purchase 2: 10 units @ S/ 20.00 -> Stock = 20, Cost = (10*10 + 10*20)/20 = S/ 15.00
    |      - Purchase 3: 20 units @ S/ 30.00 -> Stock = 40, Cost = (20*15 + 20*30)/40 = S/ 22.50
    |
    | 2. 'last_cost' (Último Costo de Compra):
    |    Sets the inventory unit cost directly to the incoming purchase price:
    |      New Cost = Purchase Price
    |
    */
    'cost_method' => env('INVENTORY_COST_METHOD', 'weighted_average'),

    /*
    |--------------------------------------------------------------------------
    | Sync Master Product Cost
    |--------------------------------------------------------------------------
    |
    | When true, recalculating the unit purchase price for a warehouse also
    | updates the default purchase price on the master product (products.precio_compra).
    |
    */
    'sync_product_master_cost' => env('INVENTORY_SYNC_MASTER_COST', true),

    /*
    |--------------------------------------------------------------------------
    | POS Boleta Anonymous Customer Limit
    |--------------------------------------------------------------------------
    |
    | Under SUNAT regulations, sales issued as Boleta de Venta (03) can be made
    | to an anonymous / general customer ("Clientes Varios" / Doc "00000000") only
    | up to a certain regulatory limit (by default S/ 700.00). Any sale exceeding
    | this amount requires an identified customer with valid identity document.
    |
    */
    'pos_boleta_anonymous_limit' => (float) env('POS_BOLETA_ANONYMOUS_LIMIT', 700.00),
];
