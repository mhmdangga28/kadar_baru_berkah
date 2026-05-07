<?php
include 'views/template/header.php';

$employeeId = $_SESSION['employee_id'] ?? '';
$userid = $_SESSION['user_id'] ?? '';
?>

<link href="/assets/plugins/select2/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/dx.fluent.custom-scheme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<style>
    .comparison-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }

    .comparison-card .label {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 6px;
    }

    .comparison-card .value {
        font-size: 20px;
        font-weight: 700;
        color: #212529;
    }

    .filter-box {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        padding: 18px;
        margin-bottom: 18px;
    }

    .select2-container .select2-selection--single {
        height: 42px !important;
        border: 1px solid #ced4da !important;
        border-radius: 10px !important;
        padding: 6px 10px !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        color: #495057 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }

    .select2-dropdown {
        border-radius: 10px !important;
        overflow: hidden;
    }

    .price-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
    }

    .price-badge-lowest {
        background: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }

    .price-badge-normal {
        background: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
    }

    #comparisonGrid .dx-row>td {
        vertical-align: middle;
    }
</style>

<div class="app-content">
    <nav aria-label="breadcrumb overflow-hidden">
        <ol class="breadcrumb breadcrumb-container breadcrumb-container-light bg-white breadcrumb-separator-chevron rounded">
            <li class="breadcrumb-item"><span class="text-primary">Purchasing</span></li>
            <li class="breadcrumb-item active" aria-current="page">Perbandingan Harga Material</li>
        </ol>
    </nav>

    <div class="card overflow-hidden">
        <div class="card-header bg-primary text-white border-bottom rounded-top d-flex justify-content-between align-items-center py-4">
            <div>
                <h3 class="fw-bold mb-0">Perbandingan Harga Material</h3>
                <small class="text-white-50">Menampilkan riwayat harga material per vendor berdasarkan data BP/pembelian.</small>
            </div>
        </div>

        <div class="card-body mt-4">
            <div class="filter-box">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="filterMaterial" class="form-label fw-semibold">Material <span class="text-danger">*</span></label>
                        <select id="filterMaterial" class="form-control" style="width:100%;"></select>
                    </div>

                    <div class="col-md-4">
                        <label for="filterVendor" class="form-label fw-semibold">Vendor</label>
                        <select id="filterVendor" class="form-control" style="width:100%;"></select>
                    </div>

                    <div class="col-md-4">
                        <label for="filterDateRange" class="form-label fw-semibold">Range Tanggal BP</label>
                        <input type="text" id="filterDateRange" class="form-control" placeholder="Pilih range tanggal" readonly>
                    </div>

                    <div class="col-md-4">
                        <label for="filterProject" class="form-label fw-semibold">Cabang</label>
                        <select id="filterProject" class="form-control" style="width:100%;">
                            <option value="">Semua cabang</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="filterPerumahan" class="form-label fw-semibold">Perumahan</label>
                        <select id="filterPerumahan" class="form-control" style="width:100%;">
                            <option value="">Semua perumahan</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="button" id="btnReset" class="btn btn-outline-secondary w-100">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="comparison-card">
                        <div class="label">Jumlah Vendor</div>
                        <div class="value" id="summaryVendor">0</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="comparison-card">
                        <div class="label">Harga Termurah</div>
                        <div class="value" id="summaryMin">Rp 0</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="comparison-card">
                        <div class="label">Harga Tertinggi</div>
                        <div class="value" id="summaryMax">Rp 0</div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="comparison-card">
                        <div class="label">Rata-rata Harga</div>
                        <div class="value" id="summaryAvg">Rp 0</div>
                    </div>
                </div>
            </div>

            <div id="comparisonGrid"></div>
        </div>
    </div>
</div>

<?php include 'views/template/footer.php'; ?>

<script src="/assets/plugins/select2/js/select2.full.min.js"></script>

<script src="https://cdn3.devexpress.com/jslib/22.1.4/js/jszip.min.js"></script>
<script src="https://cdn3.devexpress.com/jslib/22.1.4/js/dx.all.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.2/FileSaver.min.js"></script>
<script src="https://cdn3.devexpress.com/jslib/22.1.4/js/localization/dx.messages.id.js"></script>

<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
    $(document).ready(function() {
        let comparisonGridInstance;

        const modelUrl = '/model/m_perbanding_harga_material.php';

        function formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        }

        function resetSummary() {
            $('#summaryVendor').text('0');
            $('#summaryMin').text('Rp 0');
            $('#summaryMax').text('Rp 0');
            $('#summaryAvg').text('Rp 0');
        }

        function updateSummary(data) {
            if (!Array.isArray(data) || data.length === 0) {
                resetSummary();
                return;
            }

            const vendorSet = new Set();
            const prices = [];

            data.forEach(function(row) {
                if (row.vendor_id || row.vendor) {
                    vendorSet.add(row.vendor_id || row.vendor);
                }

                const harga = Number(row.harga_satuan || 0);
                if (harga > 0) {
                    prices.push(harga);
                }
            });

            if (prices.length === 0) {
                $('#summaryVendor').text(vendorSet.size);
                $('#summaryMin').text('Rp 0');
                $('#summaryMax').text('Rp 0');
                $('#summaryAvg').text('Rp 0');
                return;
            }

            const min = Math.min(...prices);
            const max = Math.max(...prices);
            const avg = prices.reduce((a, b) => a + b, 0) / prices.length;

            $('#summaryVendor').text(vendorSet.size);
            $('#summaryMin').text(formatRupiah(min));
            $('#summaryMax').text(formatRupiah(max));
            $('#summaryAvg').text(formatRupiah(avg));
        }

        function initSelect2() {
            $('#filterMaterial').select2({
                placeholder: 'Pilih material',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: modelUrl + '?action=materialOptions',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: (data || []).map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.name
                                };
                            })
                        };
                    },
                    error: function(xhr, status, error) {
                        console.error('Material options error:', error);
                        console.error('RAW RESPONSE:', xhr.responseText);
                    }
                }
            });

            $('#filterVendor').select2({
                placeholder: 'Semua vendor',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: modelUrl + '?action=vendorOptions',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            material_id: $('#filterMaterial').val() || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: (data || []).map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.name
                                };
                            })
                        };
                    }
                }
            });

            $('#filterProject').select2({
                placeholder: 'Semua cabang',
                allowClear: true,
                width: '100%'
            });

            $('#filterPerumahan').select2({
                placeholder: 'Semua perumahan',
                allowClear: true,
                width: '100%'
            });
        }

        function loadFilterOptions() {
            $.ajax({
                url: modelUrl + '?action=filterOptions',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (!res || res.status !== 'success') {
                        return;
                    }

                    const $project = $('#filterProject');
                    const $perumahan = $('#filterPerumahan');

                    $project.empty().append('<option value="">Semua cabang</option>');
                    $perumahan.empty().append('<option value="">Semua perumahan</option>');

                    (res.data.projects || []).forEach(function(item) {
                        $project.append(`<option value="${item.name}">${item.name}</option>`);
                    });

                    (res.data.perumahan || []).forEach(function(item) {
                        $perumahan.append(`<option value="${item.name}">${item.name}</option>`);
                    });

                    $project.trigger('change.select2');
                    $perumahan.trigger('change.select2');
                },
                error: function(xhr, status, error) {
                    console.error('Gagal memuat filter:', error);
                    console.error('RAW RESPONSE:', xhr.responseText);
                }
            });
        }

        $('#filterDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Pilih',
                cancelLabel: 'Batal',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                customRangeLabel: 'Custom',
                weekLabel: 'M',
                daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                monthNames: [
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ],
                firstDay: 1
            },
            opens: 'left'
        });

        $('#filterDateRange').on('apply.daterangepicker', function(ev, picker) {
            const startDate = picker.startDate.format('YYYY-MM-DD');
            const endDate = picker.endDate.format('YYYY-MM-DD');

            $(this).val(startDate + ' - ' + endDate);
            $(this).data('start', startDate);
            $(this).data('end', endDate);

            autoRefreshComparison();
        });

        $('#filterDateRange').on('cancel.daterangepicker', function() {
            $(this).val('');
            $(this).removeData('start');
            $(this).removeData('end');

            autoRefreshComparison();
        });

        function getFilterParams() {
            return {
                material_id: $('#filterMaterial').val() || '',
                vendor_id: $('#filterVendor').val() || '',
                project: ($('#filterProject').val() || '').trim(),
                perumahan: ($('#filterPerumahan').val() || '').trim(),
                start_date: $('#filterDateRange').data('start') || '',
                end_date: $('#filterDateRange').data('end') || ''
            };
        }

        function loadComparisonData() {
            const params = getFilterParams();

            if (!params.material_id) {
                resetSummary();
                return [];
            }

            return $.ajax({
                url: modelUrl + '?action=comparisonData',
                method: 'GET',
                dataType: 'json',
                data: params
            }).then(function(res) {
                if (res && res.status === 'error') {
                    DevExpress.ui.notify(res.message || 'Gagal memuat data perbandingan harga', 'error', 5000);
                    console.error(res.message);
                    updateSummary([]);
                    return [];
                }

                if (!Array.isArray(res)) {
                    DevExpress.ui.notify('Format response perbandingan harga tidak sesuai', 'error', 5000);
                    console.error('Format response tidak sesuai:', res);
                    updateSummary([]);
                    return [];
                }

                updateSummary(res);
                return res;
            }, function(xhr, status, error) {
                DevExpress.ui.notify('Gagal memuat data. Cek console untuk detail error.', 'error', 5000);
                console.error('AJAX error:', error);
                console.error('RAW RESPONSE:', xhr.responseText);
                updateSummary([]);
                return [];
            });
        }

        let autoFilterTimer = null;
        let isResettingFilter = false;

        function autoRefreshComparison() {
            if (isResettingFilter) {
                return;
            }

            clearTimeout(autoFilterTimer);

            autoFilterTimer = setTimeout(function() {
                if (!comparisonGridInstance) {
                    return;
                }

                const materialId = $('#filterMaterial').val();

                if (!materialId) {
                    resetSummary();
                    comparisonGridInstance.refresh();
                    return;
                }

                comparisonGridInstance.refresh();
            }, 300);
        }

        initSelect2();
        loadFilterOptions();

        comparisonGridInstance = $('#comparisonGrid').dxDataGrid({
            dataSource: {
                load: function() {
                    return loadComparisonData();
                }
            },

            remoteOperations: false,

            paging: {
                pageSize: 10
            },

            pager: {
                showPageSizeSelector: true,
                allowedPageSizes: [10, 20, 30, 50],
                showInfo: true
            },

            searchPanel: {
                visible: true,
                placeholder: 'Cari data...'
            },

            filterRow: {
                visible: true,
                applyFilter: 'auto'
            },

            columnAutoWidth: true,
            wordWrapEnabled: true,
            showBorders: true,
            rowAlternationEnabled: true,
            allowColumnResizing: true,

            scrolling: {
                mode: 'standard',
                showScrollbar: 'always',
                useNative: true
            },

            sorting: {
                mode: 'multiple'
            },

            columns: [{
                    dataField: 'tanggal_bp',
                    caption: 'Tanggal BP',
                    dataType: 'date',
                    format: 'dd/MM/yyyy',
                    minWidth: 120
                },
                {
                    dataField: 'kode_bp',
                    caption: 'No BP',
                    minWidth: 130,
                    cellTemplate: function(container, options) {
                        const row = options.data;
                        const kode = row.kode_bp || '-';

                        $('<span>')
                            .text(kode)
                            .css({
                                fontWeight: '600'
                            })
                            .appendTo(container);
                    }
                },
                {
                    dataField: 'material',
                    caption: 'Material',
                    minWidth: 220
                },
                {
                    dataField: 'vendor',
                    caption: 'Vendor',
                    minWidth: 180
                },
                {
                    dataField: 'qty',
                    caption: 'Qty',
                    dataType: 'number',
                    alignment: 'right',
                    minWidth: 90,
                    customizeText: function(e) {
                        return Number(e.value || 0).toLocaleString('id-ID');
                    }
                },
                {
                    dataField: 'unit',
                    caption: 'Satuan',
                    minWidth: 90,
                    customizeText: function(e) {
                        return e.value || '-';
                    }
                },
                {
                    dataField: 'harga_satuan',
                    caption: 'Harga Satuan',
                    dataType: 'number',
                    alignment: 'right',
                    minWidth: 150,
                    customizeText: function(e) {
                        return formatRupiah(e.value);
                    }
                },
                {
                    dataField: 'total_harga',
                    caption: 'Total Harga',
                    dataType: 'number',
                    alignment: 'right',
                    minWidth: 150,
                    customizeText: function(e) {
                        return formatRupiah(e.value);
                    }
                },
                {
                    dataField: 'status_harga',
                    caption: 'Status Harga',
                    alignment: 'center',
                    minWidth: 130,
                    cellTemplate: function(container, options) {
                        const row = options.data;
                        const isTermurah = row.is_termurah === true || row.is_termurah === 1 || row.is_termurah === '1';

                        $('<span>')
                            .addClass('price-badge ' + (isTermurah ? 'price-badge-lowest' : 'price-badge-normal'))
                            .text(isTermurah ? 'Termurah' : 'Lebih tinggi')
                            .appendTo(container);
                    }
                },
                {
                    dataField: 'selisih_termurah',
                    caption: 'Selisih',
                    dataType: 'number',
                    alignment: 'right',
                    minWidth: 130,
                    customizeText: function(e) {
                        const value = Number(e.value || 0);
                        return value <= 0 ? '-' : formatRupiah(value);
                    }
                },
                {
                    dataField: 'cabang',
                    caption: 'Cabang',
                    minWidth: 130
                },
                {
                    dataField: 'perumahan',
                    caption: 'Perumahan',
                    minWidth: 160
                },
                {
                    dataField: 'kode_sib',
                    caption: 'Kode SIB',
                    minWidth: 120
                },
                {
                    dataField: 'kode_rab',
                    caption: 'Kode RAB',
                    minWidth: 110
                },
                {
                    dataField: 'kavling',
                    caption: 'Kavling',
                    minWidth: 100
                },
                {
                    caption: 'Aksi',
                    width: 220,
                    alignment: 'center',
                    allowSorting: false,
                    allowFiltering: false,
                    cellTemplate: function(container, options) {
                        const row = options.data;

                        const wrapper = $('<div>').css({
                            display: 'flex',
                            gap: '6px',
                            justifyContent: 'center',
                            flexWrap: 'wrap'
                        });

                        if (row.kode_bp && row.kode_bp !== '-') {
                            $('<a>')
                                .attr('href', '/teknik/bp-purchasing/detail?code=' + encodeURIComponent(row.kode_bp))
                                .attr('target', '_blank')
                                .addClass('btn btn-sm btn-outline-primary')
                                .text('Detail BP')
                                .appendTo(wrapper);
                        }

                        if (row.work_order_id && row.work_order_id !== '-') {
                            $('<a>')
                                .attr('href', '/teknik/sib/detail?id=' + encodeURIComponent(row.work_order_id))
                                .attr('target', '_blank')
                                .addClass('btn btn-sm btn-outline-secondary')
                                .text('SIB')
                                .appendTo(wrapper);
                        }

                        wrapper.appendTo(container);
                    }
                }
            ],

            onRowPrepared: function(e) {
                if (e.rowType === 'data') {
                    const isTermurah = e.data.is_termurah === true || e.data.is_termurah === 1 || e.data.is_termurah === '1';

                    if (isTermurah) {
                        e.rowElement.css({
                            backgroundColor: '#f4fff8'
                        });
                    }
                }
            },

            export: {
                enabled: true
            },

            onExporting: function(e) {
                const workbook = new ExcelJS.Workbook();
                const worksheet = workbook.addWorksheet('Perbandingan Harga Material');

                DevExpress.excelExporter.exportDataGrid({
                    worksheet: worksheet,
                    component: e.component,
                    customizeCell: function(options) {
                        options.excelCell.font = {
                            name: 'Arial',
                            size: 11
                        };

                        options.excelCell.alignment = {
                            horizontal: 'left'
                        };
                    }
                }).then(function() {
                    workbook.xlsx.writeBuffer().then(function(buffer) {
                        saveAs(new Blob([buffer], {
                            type: 'application/octet-stream'
                        }), 'PerbandinganHargaMaterial.xlsx');
                    });
                });
            }
        }).dxDataGrid('instance');

        $('#btnReset').on('click', function() {
            isResettingFilter = true;

            $('#filterMaterial').val(null).trigger('change.select2');
            $('#filterVendor').val(null).trigger('change.select2');
            $('#filterProject').val('').trigger('change.select2');
            $('#filterPerumahan').val('').trigger('change.select2');

            $('#filterDateRange').val('');
            $('#filterDateRange').removeData('start');
            $('#filterDateRange').removeData('end');

            resetSummary();

            isResettingFilter = false;

            comparisonGridInstance.refresh();
        });

        $('#filterMaterial').on('change', function() {
            $('#filterVendor').val(null).trigger('change.select2');

            autoRefreshComparison();
        });

        $('#filterVendor').on('change', function() {
            autoRefreshComparison();
        });

        $('#filterProject').on('change', function() {
            autoRefreshComparison();
        });

        $('#filterPerumahan').on('change', function() {
            autoRefreshComparison();
        });
    });
</script>