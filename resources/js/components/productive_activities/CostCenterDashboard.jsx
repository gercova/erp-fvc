import React, { useState, useEffect, useMemo } from 'react';

/**
 * CostCenterDashboard Component (ERP-FVC)
 * Tablero de Control de Centro de Costos por Actividad Productiva y Empresarial (APE)
 * Replicando fielmente la estructura analítica de la hoja RESUMEN de Germán Cotrina
 * Incluye: Matriz de Tabulación Cruzada (Actividad × Mes), Gráficos de Barras Comparativos
 * (Mes y Actividad) y Módulo de Importación/Exportación de formatos Excel institucionales.
 */
export default function CostCenterDashboard({ initialYear = new Date().getFullYear(), apiBaseUrl = '/productive-activities' }) {
    const [year, setYear] = useState(initialYear);
    const [selectedType, setSelectedType] = useState('ALL');
    const [selectedFundSource, setSelectedFundSource] = useState('ALL');
    const [searchTerm, setSearchTerm] = useState('');
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState({
        activities: [],
        month_totals: {},
        grand_totals: { income: 0, expense: 0, balance: 0 },
        chart_data: { labels: [], incomes: [], expenses: [], balances: [] },
        activity_chart_data: { labels: [], incomes: [], expenses: [], balances: [] },
    });

    // Tooltip State for Charts
    const [chartTooltip, setChartTooltip] = useState(null);

    // Import Modal State
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importYear, setImportYear] = useState(initialYear);
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const [importError, setImportError] = useState(null);

    const activityTypes = [
        { id: 'ALL', label: 'Todas las Actividades', icon: 'fas fa-th-large' },
        { id: 'AGRICULTURAL', label: 'Agrícola', icon: 'fas fa-seedling' },
        { id: 'FORESTRY', label: 'Forestal', icon: 'fas fa-tree' },
        { id: 'AQUACULTURE', label: 'Piscícola', icon: 'fas fa-fish' },
        { id: 'LIVESTOCK', label: 'Pecuario', icon: 'fas fa-paw' },
        { id: 'INSTITUTIONAL', label: 'Institucional', icon: 'fas fa-landmark' },
        { id: 'SERVICES', label: 'Servicios', icon: 'fas fa-concierge-bell' },
    ];

    const months = [
        { num: 1, name: 'Ene' }, { num: 2, name: 'Feb' }, { num: 3, name: 'Mar' },
        { num: 4, name: 'Abr' }, { num: 5, name: 'May' }, { num: 6, name: 'Jun' },
        { num: 7, name: 'Jul' }, { num: 8, name: 'Ago' }, { num: 9, name: 'Set' },
        { num: 10, name: 'Oct' }, { num: 11, name: 'Nov' }, { num: 12, name: 'Dic' },
    ];

    // Cargar datos del tablero
    const fetchData = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                year: year.toString(),
                type: selectedType,
            });
            if (selectedFundSource !== 'ALL') {
                params.append('fund_source_id', selectedFundSource);
            }

            const response = await fetch(`${apiBaseUrl}/cost-center/data?${params.toString()}`);
            if (response.ok) {
                const result = await response.json();
                setData(result);
            }
        } catch (error) {
            console.error('Error fetching cost center data:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, [year, selectedType, selectedFundSource]);

    // Filtrar actividades por término de búsqueda
    const filteredActivities = useMemo(() => {
        if (!searchTerm.trim()) return data.activities || [];
        const term = searchTerm.toLowerCase();
        return (data.activities || []).filter(a =>
            a.name.toLowerCase().includes(term) ||
            a.code.toLowerCase().includes(term) ||
            a.cost_center.toLowerCase().includes(term) ||
            a.head_name.toLowerCase().includes(term)
        );
    }, [data.activities, searchTerm]);

    // Manejo de Exportación a Excel
    const handleExportExcel = (mode = 'summary') => {
        const params = new URLSearchParams({
            year: year.toString(),
            type: selectedType,
        });
        if (selectedFundSource !== 'ALL') {
            params.append('fund_source_id', selectedFundSource);
        }
        const endpoint = mode === 'detailed' ? 'export-detailed' : 'export-excel';
        window.location.href = `${apiBaseUrl}/cost-center/${endpoint}?${params.toString()}`;
    };

    // Manejo de la Importación de Archivo Histórico
    const handleImportSubmit = async (e) => {
        e.preventDefault();
        if (!importFile) {
            alert('Por favor seleccione un archivo Excel (.xlsx o .xls)');
            return;
        }

        setImporting(true);
        setImportError(null);
        setImportResult(null);

        const formData = new FormData();
        formData.append('excel_file', importFile);
        formData.append('year', importYear);

        // Obtener el token CSRF si existe en el DOM
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch(`${apiBaseUrl}/cost-center/import-excel`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                }
            });

            const result = await response.json();
            if (response.ok && result.status === 'success') {
                setImportResult(result.summary);
                fetchData(); // Refrescar los datos inmediatamente
            } else {
                setImportError(result.message || 'Error durante la migración del archivo.');
            }
        } catch (err) {
            console.error('Import error:', err);
            setImportError('No se pudo comunicar con el servidor de migración.');
        } finally {
            setImporting(false);
        }
    };

    // Formatear montos a moneda peruana
    const formatCurrency = (val) => {
        const num = parseFloat(val) || 0;
        return `S/ ${num.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    // Cálculo para Gráfico Mensual
    const maxMonthlyValue = useMemo(() => {
        if (!data.chart_data?.incomes?.length) return 1;
        const allVals = [...data.chart_data.incomes, ...data.chart_data.expenses];
        return Math.max(...allVals, 1000);
    }, [data.chart_data]);

    // Cálculo para Gráfico por Actividad
    const maxActivityValue = useMemo(() => {
        if (!data.activity_chart_data?.incomes?.length) return 1;
        const allVals = [...data.activity_chart_data.incomes, ...data.activity_chart_data.expenses];
        return Math.max(...allVals, 1000);
    }, [data.activity_chart_data]);

    return (
        <div className="cost-center-dashboard">
            {/* Top Toolbar & Segmented Controls */}
            <div className="card shadow-sm border-0 mb-4">
                <div className="card-body p-3">
                    <div className="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        {/* Segmented Control de Tipo de Actividad */}
                        <div className="btn-group segmented-control flex-wrap" role="group">
                            {activityTypes.map(t => (
                                <button
                                    key={t.id}
                                    type="button"
                                    className={`btn btn-sm ${selectedType === t.id ? 'btn-primary active' : 'btn-outline-secondary'}`}
                                    onClick={() => setSelectedType(t.id)}
                                >
                                    <i className={`${t.icon} me-1`}></i>
                                    {t.label}
                                </button>
                            ))}
                        </div>

                        {/* Controles de Año, Fuente de Fondos y Acciones */}
                        <div className="d-flex align-items-center gap-2 flex-wrap ms-auto">
                            <select
                                className="form-select form-select-sm"
                                style={{ width: '110px' }}
                                value={year}
                                onChange={(e) => setYear(parseInt(e.target.value))}
                            >
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                                <option value="2027">2027</option>
                            </select>

                            <select
                                className="form-select form-select-sm"
                                style={{ width: '180px' }}
                                value={selectedFundSource}
                                onChange={(e) => setSelectedFundSource(e.target.value)}
                            >
                                <option value="ALL">Todas las Fuentes</option>
                                <option value="1">Banco de la Nación</option>
                                <option value="2">Coop. Tocache</option>
                                <option value="3">Caja Chica</option>
                            </select>

                            <button
                                type="button"
                                className="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm"
                                onClick={() => { setShowImportModal(true); setImportResult(null); setImportError(null); }}
                                title="Importar formato histórico del informe económico Excel"
                            >
                                <i className="fas fa-file-import"></i>
                                <span>Importar Histórico</span>
                            </button>

                            <div className="btn-group">
                                <button
                                    type="button"
                                    className="btn btn-sm btn-outline-success dropdown-toggle d-flex align-items-center gap-1 shadow-sm"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <i className="fas fa-file-excel"></i>
                                    <span>Exportar Excel</span>
                                </button>
                                <ul className="dropdown-menu dropdown-menu-end shadow">
                                    <li>
                                        <button className="dropdown-item py-2 small" onClick={() => handleExportExcel('summary')}>
                                            <i className="fas fa-table me-2 text-success"></i> Formato Resumen (Matriz Actividad × Mes)
                                        </button>
                                    </li>
                                    <li>
                                        <button className="dropdown-item py-2 small" onClick={() => handleExportExcel('detailed')}>
                                            <i className="fas fa-copy me-2 text-primary"></i> Informe Multi-Hoja (Hoja por Actividad)
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* KPIs Card Bar */}
            <div className="row g-3 mb-4">
                <div className="col-12 col-md-4">
                    <div className="card border-start-lg border-start-success shadow-sm h-100">
                        <div className="card-body">
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <div className="text-muted small fw-bold text-uppercase">Ingresos Totales ({year})</div>
                                    <div className="h3 fw-bold text-success mb-0">{formatCurrency(data.grand_totals.income)}</div>
                                </div>
                                <div className="rounded-circle bg-success-soft p-3 text-success">
                                    <i className="fas fa-arrow-down fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="col-12 col-md-4">
                    <div className="card border-start-lg border-start-danger shadow-sm h-100">
                        <div className="card-body">
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <div className="text-muted small fw-bold text-uppercase">Egresos Totales ({year})</div>
                                    <div className="h3 fw-bold text-danger mb-0">{formatCurrency(data.grand_totals.expense)}</div>
                                </div>
                                <div className="rounded-circle bg-danger-soft p-3 text-danger">
                                    <i className="fas fa-arrow-up fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="col-12 col-md-4">
                    <div className="card border-start-lg border-start-primary shadow-sm h-100">
                        <div className="card-body">
                            <div className="d-flex align-items-center justify-content-between">
                                <div>
                                    <div className="text-muted small fw-bold text-uppercase">Saldo Neto Operacional</div>
                                    <div className={`h3 fw-bold mb-0 ${data.grand_totals.balance >= 0 ? 'text-primary' : 'text-danger'}`}>
                                        {formatCurrency(data.grand_totals.balance)}
                                    </div>
                                </div>
                                <div className="rounded-circle bg-primary-soft p-3 text-primary">
                                    <i className="fas fa-balance-scale fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Gráficos de Barras Comparativos (Requisito Germán Cotrina: "Gráficos de barras y datos unificados") */}
            <div className="row g-3 mb-4">
                {/* 1. Comparativa Mensual de Ingresos vs Egresos */}
                <div className="col-12 col-lg-7">
                    <div className="card shadow-sm border-0 h-100">
                        <div className="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h6 className="mb-0 text-primary fw-bold">
                                <i className="fas fa-chart-column me-2"></i>
                                Comparativa Mensual: Ingresos vs. Egresos y Saldo ({year})
                            </h6>
                            <div className="d-flex align-items-center gap-2 small">
                                <span className="badge bg-success-soft text-success"><i className="fas fa-circle me-1" style={{ fontSize: '8px' }}></i>Ingresos</span>
                                <span className="badge bg-danger-soft text-danger"><i className="fas fa-circle me-1" style={{ fontSize: '8px' }}></i>Egresos</span>
                                <span className="badge bg-primary-soft text-primary"><i className="fas fa-circle me-1" style={{ fontSize: '8px' }}></i>Saldo</span>
                            </div>
                        </div>
                        <div className="card-body p-3">
                            <div style={{ position: 'relative', width: '100%', height: '260px' }}>
                                <svg viewBox="0 0 600 240" className="w-100 h-100" style={{ overflow: 'visible' }}>
                                    {/* Grid Lines */}
                                    {[0, 0.25, 0.5, 0.75, 1].map((pct, i) => {
                                        const y = 200 - (pct * 170);
                                        return (
                                            <g key={i}>
                                                <line x1="30" y1={y} x2="590" y2={y} stroke="#e9ecef" strokeWidth="1" strokeDasharray="3,3" />
                                                <text x="25" y={y + 3} fill="#adb5bd" fontSize="9" textAnchor="end">
                                                    {(maxMonthlyValue * pct / 1000).toFixed(0)}k
                                                </text>
                                            </g>
                                        );
                                    })}

                                    {/* Month Bars */}
                                    {months.map((m, idx) => {
                                        const xGroup = 45 + (idx * 45);
                                        const inc = data.chart_data?.incomes?.[idx] || 0;
                                        const exp = data.chart_data?.expenses?.[idx] || 0;
                                        const bal = data.chart_data?.balances?.[idx] || 0;

                                        const hInc = (inc / maxMonthlyValue) * 170;
                                        const hExp = (exp / maxMonthlyValue) * 170;
                                        const yInc = 200 - hInc;
                                        const yExp = 200 - hExp;

                                        return (
                                            <g key={m.num} style={{ cursor: 'pointer' }}
                                               onMouseEnter={() => setChartTooltip({ title: `${m.name} ${year}`, inc, exp, bal })}
                                               onMouseLeave={() => setChartTooltip(null)}>
                                                {/* Bar Income */}
                                                <rect x={xGroup} y={yInc} width="14" height={Math.max(hInc, 2)} fill="#198754" rx="2" opacity="0.85" />
                                                {/* Bar Expense */}
                                                <rect x={xGroup + 16} y={yExp} width="14" height={Math.max(hExp, 2)} fill="#dc3545" rx="2" opacity="0.85" />
                                                {/* Month Label */}
                                                <text x={xGroup + 15} y="218" fill="#6c757d" fontSize="10" textAnchor="middle">{m.name}</text>
                                            </g>
                                        );
                                    })}
                                </svg>

                                {chartTooltip && (
                                    <div className="position-absolute bg-dark text-white p-2 rounded shadow small"
                                         style={{ top: '10px', right: '15px', zIndex: 10, pointerEvents: 'none' }}>
                                        <div className="fw-bold border-bottom pb-1 mb-1">{chartTooltip.title}</div>
                                        <div className="text-success"><i className="fas fa-arrow-down me-1"></i>Ingresos: {formatCurrency(chartTooltip.inc)}</div>
                                        <div className="text-danger"><i className="fas fa-arrow-up me-1"></i>Egresos: {formatCurrency(chartTooltip.exp)}</div>
                                        <div className={chartTooltip.bal >= 0 ? 'text-info' : 'text-warning'}>
                                            <i className="fas fa-balance-scale me-1"></i>Saldo: {formatCurrency(chartTooltip.bal)}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* 2. Comparativa por Actividad Productiva */}
                <div className="col-12 col-lg-5">
                    <div className="card shadow-sm border-0 h-100">
                        <div className="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h6 className="mb-0 text-primary fw-bold">
                                <i className="fas fa-chart-bar me-2"></i>
                                Distribución por Actividad
                            </h6>
                            <span className="badge bg-light text-muted border small">Ing vs Egr</span>
                        </div>
                        <div className="card-body p-3">
                            <div style={{ position: 'relative', width: '100%', height: '260px' }}>
                                <svg viewBox="0 0 450 240" className="w-100 h-100" style={{ overflow: 'visible' }}>
                                    {(data.activity_chart_data?.labels || []).slice(0, 5).map((label, idx) => {
                                        const inc = data.activity_chart_data?.incomes?.[idx] || 0;
                                        const exp = data.activity_chart_data?.expenses?.[idx] || 0;
                                        const yBase = 25 + (idx * 44);

                                        const wInc = (inc / maxActivityValue) * 230;
                                        const wExp = (exp / maxActivityValue) * 230;

                                        const shortName = label.length > 18 ? label.substring(0, 18) + '...' : label;

                                        return (
                                            <g key={idx}>
                                                <text x="135" y={yBase + 12} fill="#495057" fontSize="10" fontWeight="bold" textAnchor="end">{shortName}</text>
                                                {/* Bar Income */}
                                                <rect x="145" y={yBase} width={Math.max(wInc, 4)} height="9" fill="#198754" rx="2" opacity="0.85" />
                                                {/* Bar Expense */}
                                                <rect x="145" y={yBase + 11} width={Math.max(wExp, 4)} height="9" fill="#dc3545" rx="2" opacity="0.85" />
                                            </g>
                                        );
                                    })}
                                </svg>
                                <div className="text-center text-muted small mt-2">
                                    <span className="me-3"><span className="badge bg-success p-1 me-1"> </span> Ingresos</span>
                                    <span><span className="badge bg-danger p-1 me-1"> </span> Egresos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Cross-Tabulation Matrix: Activity × Month */}
            <div className="card shadow-sm border-0 mb-4">
                <div className="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h5 className="mb-0 text-primary fw-bold">
                        <i className="fas fa-table me-2"></i>
                        Matriz de Ejecución Presupuestal y Económica: Actividades × Meses ({year})
                    </h5>
                    <div className="d-flex align-items-center" style={{ width: '260px' }}>
                        <div className="input-group input-group-sm">
                            <span className="input-group-text bg-light border-end-0"><i className="fas fa-search text-muted"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0"
                                placeholder="Filtrar actividad..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                <div className="card-body p-0">
                    <div className="table-responsive" style={{ maxHeight: '650px' }}>
                        <table className="table table-bordered table-hover table-sm align-middle mb-0 text-nowrap" style={{ fontSize: '0.82rem' }}>
                            <thead className="table-dark sticky-top" style={{ zIndex: 5 }}>
                                <tr className="text-center align-middle">
                                    <th rowSpan="2" style={{ minWidth: '180px' }} className="text-start ps-3">Actividad Productiva</th>
                                    <th rowSpan="2" style={{ width: '80px' }}>C. Costo</th>
                                    <th rowSpan="2" style={{ width: '100px' }}>Tipo</th>
                                    {months.map(m => (
                                        <th key={m.num} colSpan="3" className="border-start">{m.name}</th>
                                    ))}
                                    <th colSpan="3" className="border-start bg-secondary">TOTALES ANUAL</th>
                                </tr>
                                <tr className="text-center small" style={{ fontSize: '0.72rem' }}>
                                    {months.map(m => (
                                        <React.Fragment key={m.num}>
                                            <th className="text-success border-start">Ing</th>
                                            <th className="text-danger">Egr</th>
                                            <th className="text-primary fw-bold">Sal</th>
                                        </React.Fragment>
                                    ))}
                                    <th className="text-success border-start bg-dark">Ingreso</th>
                                    <th className="text-danger bg-dark">Egreso</th>
                                    <th className="text-warning fw-bold bg-dark">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading ? (
                                    <tr>
                                        <td colSpan="42" className="text-center py-5">
                                            <div className="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                            <span>Consolidando balances de centro de costos...</span>
                                        </td>
                                    </tr>
                                ) : filteredActivities.length === 0 ? (
                                    <tr>
                                        <td colSpan="42" className="text-center py-4 text-muted">
                                            No se encontraron actividades productivas para el criterio seleccionado.
                                        </td>
                                    </tr>
                                ) : (
                                    filteredActivities.map((act) => (
                                        <tr key={act.id}>
                                            <td className="fw-bold ps-3 text-start">
                                                <a href={`/productive-activities/${act.id}`} className="text-dark text-decoration-none hover-primary">
                                                    {act.name}
                                                </a>
                                                <div className="text-muted small fw-normal">{act.code} &bull; {act.head_name}</div>
                                            </td>
                                            <td className="text-center text-muted small">{act.cost_center}</td>
                                            <td className="text-center">
                                                <span className="badge bg-light text-dark border small">{act.type}</span>
                                            </td>

                                            {months.map(m => {
                                                const mData = act.months[m.num] || { income: 0, expense: 0, balance: 0 };
                                                return (
                                                    <React.Fragment key={m.num}>
                                                        <td className="text-end border-start text-success font-monospace" style={{ fontSize: '0.78rem' }}>
                                                            {mData.income > 0 ? mData.income.toFixed(2) : '-'}
                                                        </td>
                                                        <td className="text-end text-danger font-monospace" style={{ fontSize: '0.78rem' }}>
                                                            {mData.expense > 0 ? mData.expense.toFixed(2) : '-'}
                                                        </td>
                                                        <td className={`text-end font-monospace fw-bold ${mData.balance < 0 ? 'text-danger' : (mData.balance > 0 ? 'text-primary' : 'text-muted')}`} style={{ fontSize: '0.78rem' }}>
                                                            {mData.balance !== 0 ? mData.balance.toFixed(2) : '-'}
                                                        </td>
                                                    </React.Fragment>
                                                );
                                            })}

                                            {/* Totales Anuales de la Actividad */}
                                            <td className="text-end fw-bold text-success border-start bg-light font-monospace">
                                                {act.total_income.toFixed(2)}
                                            </td>
                                            <td className="text-end fw-bold text-danger bg-light font-monospace">
                                                {act.total_expense.toFixed(2)}
                                            </td>
                                            <td className={`text-end fw-bold bg-light font-monospace ${act.final_balance < 0 ? 'text-danger' : 'text-primary'}`}>
                                                {act.final_balance.toFixed(2)}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                            <tfoot className="table-secondary sticky-bottom fw-bold" style={{ zIndex: 4 }}>
                                <tr className="align-middle">
                                    <td colSpan="3" className="text-end pe-3 text-uppercase">
                                        TOTALES CONSOLIDADOS INSTITUCIONALES (APE):
                                    </td>
                                    {months.map(m => {
                                        const mTot = data.month_totals[m.num] || { income: 0, expense: 0, balance: 0 };
                                        return (
                                            <React.Fragment key={m.num}>
                                                <td className="text-end text-success border-start font-monospace">
                                                    {mTot.income > 0 ? mTot.income.toFixed(2) : '-'}
                                                </td>
                                                <td className="text-end text-danger font-monospace">
                                                    {mTot.expense > 0 ? mTot.expense.toFixed(2) : '-'}
                                                </td>
                                                <td className={`text-end font-monospace ${mTot.balance < 0 ? 'text-danger' : 'text-primary'}`}>
                                                    {mTot.balance !== 0 ? mTot.balance.toFixed(2) : '-'}
                                                </td>
                                            </React.Fragment>
                                        );
                                    })}
                                    <td className="text-end text-success border-start bg-primary-soft font-monospace">
                                        {data.grand_totals.income.toFixed(2)}
                                    </td>
                                    <td className="text-end text-danger bg-primary-soft font-monospace">
                                        {data.grand_totals.expense.toFixed(2)}
                                    </td>
                                    <td className="text-end text-primary fw-bolder bg-primary-soft font-monospace">
                                        {data.grand_totals.balance.toFixed(2)}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal: Importación de Archivo Histórico Excel */}
            {showImportModal && (
                <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }} tabIndex="-1">
                    <div className="modal-dialog modal-lg modal-dialog-centered">
                        <div className="modal-content shadow-lg border-0">
                            <div className="modal-header bg-primary text-white">
                                <h5 className="modal-title fw-bold">
                                    <i className="fas fa-file-import me-2"></i>
                                    Importador Histórico de Formatos Excel (APE)
                                </h5>
                                <button type="button" className="btn-close btn-close-white" onClick={() => setShowImportModal(false)}></button>
                            </div>
                            <form onSubmit={handleImportSubmit}>
                                <div className="modal-body p-4">
                                    <div className="alert alert-info border-0 shadow-sm small mb-3">
                                        <strong>Motor de Migración Histórico:</strong> Procesa el formato de reporte económico institucional (hojas por actividad, bloques de ingresos/egresos con columnas de meses y fuentes de fondos).
                                    </div>

                                    <div className="row g-3 mb-3">
                                        <div className="col-md-8">
                                            <label className="form-label fw-bold">Seleccionar Archivo Excel (.xlsx / .xls) <span className="text-danger">*</span></label>
                                            <input
                                                type="file"
                                                className="form-control"
                                                accept=".xlsx,.xls"
                                                onChange={(e) => setImportFile(e.target.files[0] || null)}
                                                required
                                            />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label fw-bold">Año Fiscal Asignado</label>
                                            <select
                                                className="form-select"
                                                value={importYear}
                                                onChange={(e) => setImportYear(parseInt(e.target.value))}
                                            >
                                                <option value="2024">2024</option>
                                                <option value="2025">2025 (Histórico)</option>
                                                <option value="2026">2026</option>
                                                <option value="2027">2027</option>
                                            </select>
                                        </div>
                                    </div>

                                    {importing && (
                                        <div className="text-center py-4">
                                            <div className="spinner-border text-primary mb-2" role="status"></div>
                                            <p className="text-muted fw-bold mb-0">Procesando y migrando transacciones...</p>
                                        </div>
                                    )}

                                    {importError && (
                                        <div className="alert alert-danger border-0 small">
                                            <i className="fas fa-exclamation-circle me-1"></i> {importError}
                                        </div>
                                    )}

                                    {importResult && (
                                        <div className="border rounded p-3 bg-light">
                                            <h6 className="fw-bold text-success mb-3"><i className="fas fa-check-circle me-1"></i> Resumen de la Migración Realizada</h6>
                                            <div className="row g-2 mb-3 text-center">
                                                <div className="col-3">
                                                    <div className="p-2 border rounded bg-white">
                                                        <div className="small text-muted">Hojas</div>
                                                        <div className="h5 fw-bold text-dark mb-0">{importResult.total_sheets_processed}</div>
                                                    </div>
                                                </div>
                                                <div className="col-3">
                                                    <div className="p-2 border rounded bg-white">
                                                        <div className="small text-muted">Filas Rubros</div>
                                                        <div className="h5 fw-bold text-primary mb-0">{importResult.total_rows_imported}</div>
                                                    </div>
                                                </div>
                                                <div className="col-3">
                                                    <div className="p-2 border rounded bg-white">
                                                        <div className="small text-muted">Transacciones</div>
                                                        <div className="h5 fw-bold text-info mb-0">{importResult.total_transactions_created}</div>
                                                    </div>
                                                </div>
                                                <div className="col-3">
                                                    <div className="p-2 border rounded bg-white">
                                                        <div className="small text-muted">Monto Total</div>
                                                        <div className="h6 fw-bold text-success mb-0">{formatCurrency(importResult.total_amount_imported)}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Supuestos */}
                                            {importResult.assumptions_applied && (
                                                <div className="mb-2">
                                                    <strong className="small text-dark">Supuestos Técnicos Aplicados:</strong>
                                                    <ul className="small text-muted mb-0 ps-3">
                                                        {importResult.assumptions_applied.map((a, i) => (
                                                            <li key={i}>{a}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}

                                            {/* Filas Pendientes */}
                                            {importResult.pending_manual_review?.length > 0 && (
                                                <div className="mt-3">
                                                    <strong className="small text-warning">
                                                        <i className="fas fa-exclamation-triangle me-1"></i> Filas que requieren revisión manual ({importResult.pending_manual_review.length}):
                                                    </strong>
                                                    <div className="table-responsive mt-1" style={{ maxHeight: '150px' }}>
                                                        <table className="table table-bordered table-sm table-striped small align-middle mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Hoja</th>
                                                                    <th>Fila</th>
                                                                    <th>Col</th>
                                                                    <th>Concepto</th>
                                                                    <th>Valor</th>
                                                                    <th>Motivo</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {importResult.pending_manual_review.map((p, i) => (
                                                                    <tr key={i}>
                                                                        <td>{p.sheet}</td>
                                                                        <td>{p.row}</td>
                                                                        <td>{p.col}</td>
                                                                        <td>{p.concept}</td>
                                                                        <td className="text-danger font-monospace">{p.value}</td>
                                                                        <td className="text-muted">{p.reason}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                                <div className="modal-footer bg-light">
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={() => setShowImportModal(false)}>Cerrar</button>
                                    <button type="submit" className="btn btn-primary btn-sm shadow-sm" disabled={importing}>
                                        <i className="fas fa-upload me-1"></i> Iniciar Migración
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
