<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserManualController extends Controller
{
    /**
     * Módulos documentados en el sistema
     */
    protected array $modules = [
        'ape' => [
            'id' => 'ape',
            'name' => 'Actividades Productivas y Empresariales (APE)',
            'short_name' => 'Actividades (APE)',
            'code' => 'APE',
            'icon' => 'fas fa-briefcase',
            'feather' => 'briefcase',
            'color' => 'primary',
            'badge' => 'Gestión Operativa y RDR',
            'description' => 'Planificación y monitoreo de unidades de negocio institucional, centros de costos, registro de transacciones, recaudaciones CUT, préstamos internos y conciliaciones bancarias.',
            'main_route' => 'productive_activities.index',
            'view' => 'admin.manuals.modules.ape',
            'process_count' => 7,
        ],
        'agreements' => [
            'id' => 'agreements',
            'name' => 'Convenios y Servicios Tecnológicos',
            'short_name' => 'Convenios y Servicios',
            'code' => 'CONV-SERV',
            'icon' => 'fas fa-file-contract',
            'feather' => 'file-text',
            'color' => 'success',
            'badge' => 'Alianzas y Extensión',
            'description' => 'Gestión contractual de convenios marco/específicos, seguimiento de obligaciones y evidencias, addendas, cobranza de cuotas, servicios técnicos, capacitaciones y emisión de certificados.',
            'main_route' => 'agreements.index',
            'view' => 'admin.manuals.modules.agreements_services',
            'process_count' => 10,
        ],
        'production' => [
            'id' => 'production',
            'name' => 'Producción y Comercialización',
            'short_name' => 'Producción',
            'code' => 'PROD',
            'icon' => 'fas fa-industry',
            'feather' => 'layers',
            'color' => 'warning',
            'badge' => 'Transformación y Costos',
            'description' => 'Planes de producción y campañas agrícolas/industriales, división en fases/lotes, costeo de mano de obra y jornales, salidas de insumos, registro de cosechas, publicación al catálogo de ventas y rentabilidad.',
            'main_route' => 'production.plans.index',
            'view' => 'admin.manuals.modules.production',
            'process_count' => 8,
        ],
        'agrolivestock' => [
            'id' => 'agrolivestock',
            'name' => 'Agropecuaria y Forestal',
            'short_name' => 'Agro y Forestal',
            'code' => 'AGRO',
            'icon' => 'fas fa-seedling',
            'feather' => 'sun',
            'color' => 'teal',
            'badge' => 'Campo y Viveros',
            'description' => 'Gestión catastral y agronómica de parcelas y lotes, viveros forestales y ornamentales, control zootécnico y sanitario del hato ganadero, pesaje de campo y conciliación balanza vs factura.',
            'main_route' => 'agrolivestock.plots.index',
            'view' => 'admin.manuals.modules.agrolivestock',
            'process_count' => 8,
        ],
        'accounting' => [
            'id' => 'accounting',
            'name' => 'Contabilidad y Tesorería',
            'short_name' => 'Contabilidad',
            'code' => 'CONT',
            'icon' => 'fas fa-book-open',
            'feather' => 'book-open',
            'color' => 'indigo',
            'badge' => 'Finanzas y PCGE',
            'description' => 'Plan Contable General Empresarial (PCGE), Libro Diario (Formato SUNAT 5.1), Libro Mayor, Balance de Comprobación, Estados Financieros, Presupuestos PIA/PIM, Conciliación Bancaria y Cierres de Período.',
            'main_route' => 'accounting.journal.index',
            'view' => 'admin.manuals.modules.accounting',
            'process_count' => 10,
        ],
    ];

    /**
     * Muestra la vista principal o un módulo específico del manual
     */
    public function index(Request $request): View
    {
        $activeModule = $request->query('module');
        if ($activeModule && !array_key_exists($activeModule, $this->modules)) {
            $activeModule = null;
        }

        $modules = $this->modules;
        $totalProcesses = array_sum(array_column($modules, 'process_count'));

        return view('admin.manuals.index', compact('modules', 'activeModule', 'totalProcesses'));
    }

    /**
     * Acceso directo por módulo en la URL
     */
    public function showModule(string $module): View
    {
        if (!array_key_exists($module, $this->modules)) {
            abort(404, 'Módulo de manual no encontrado');
        }

        $activeModule = $module;
        $modules = $this->modules;
        $totalProcesses = array_sum(array_column($modules, 'process_count'));

        return view('admin.manuals.index', compact('modules', 'activeModule', 'totalProcesses'));
    }
}
