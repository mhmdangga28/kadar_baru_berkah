<?php
session_start();

include 'conn.php';

ini_set('display_errors', 0); // Mematikan tampilan error PHP ke halaman/browser.
error_reporting(E_ALL); // deteksi semua jenis error
mysqli_report(MYSQLI_REPORT_OFF); // Mematikan mode laporan error otomatis dari MySQLi.

// mengecek dulu apakah variabel $conn sudah ada dan benar-benar merupakan koneksi database
if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

function jsonResponse($payload, $httpCode = 200)
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function bindParams($stmt, $types, $params)
{
    if (empty($types) || empty($params)) {
        return true;
    }

    $refs = [];
    $refs[] = $types;

    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }

    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

function getAccessJoin()
{
    return "
        LEFT JOIN user_project up 
            ON up.project_id = mpro.id 
            AND up.user_id = ?

        LEFT JOIN employee emp_login 
            ON emp_login.id = ?
    ";
}

function getAccessWhere()
{
    return "
        AND (
            '09166716' = emp_login.statuspenempatan_id
            OR up.user_id IS NOT NULL
        )
    ";
}

function getMaterialOptions()
{
    global $conn;

    $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

    $sql = "
        SELECT
            mat.id AS id,
            COALESCE(mat.code, '') AS code,
            COALESCE(NULLIF(mat.name, ''), mat.id) AS name
        FROM materials mat
        WHERE
            (
                mat.project_id IS NULL
                OR mat.project_id = ''
            )
            AND (
                mat.perumahan_id IS NULL
                OR mat.perumahan_id = ''
            )
            AND (
                ? = ''
                OR mat.name LIKE CONCAT('%', ?, '%')
                OR mat.code LIKE CONCAT('%', ?, '%')
                OR mat.id LIKE CONCAT('%', ?, '%')
            )
        ORDER BY mat.name ASC
        LIMIT 100
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Query material gagal: ' . $conn->error
        ], 500);
    }

    $params = [
        $keyword,
        $keyword,
        $keyword,
        $keyword
    ];

    bindParams($stmt, 'ssss', $params);

    if (!$stmt->execute()) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Execute material gagal: ' . $stmt->error
        ], 500);
    }

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $label = trim((string) $row['code']) !== ''
            ? $row['code'] . ' - ' . $row['name']
            : $row['name'];

        $data[] = [
            'id'   => $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'text' => $label
        ];
    }

    $stmt->close();

    jsonResponse($data);
}

function getVendorOptions()
{
    global $conn;

    $employeeId = $_SESSION['employee_id'] ?? '';
    $userId     = $_SESSION['user_id'] ?? '';
    $materialId = isset($_GET['material_id']) ? trim($_GET['material_id']) : '';
    $keyword    = isset($_GET['q']) ? trim($_GET['q']) : '';

    $sql = "
        SELECT DISTINCT
            msup.id,
            msup.name
        FROM purchase_vendor_detail pvd
        INNER JOIN purchase_vendor pv 
            ON pv.id = pvd.purchase_vendor_id
        LEFT JOIN ms_suplier msup 
            ON msup.id = pvd.vendor_id
        LEFT JOIN work_order_detail wod 
            ON wod.id = pv.work_order_detail_id
        LEFT JOIN work_order wo 
            ON wo.id = wod.work_order_id
        LEFT JOIN ms_perumahan mprum 
            ON mprum.id = wo.perumahan_id
        LEFT JOIN ms_project mpro 
            ON mpro.id = mprum.project_id

        " . getAccessJoin() . "

        WHERE 
            pv.status = 1
            AND pvd.status = 1
            AND msup.id IS NOT NULL
            " . getAccessWhere() . "
            AND (? = '' OR pvd.material_id = ?)
            AND (? = '' OR msup.name LIKE CONCAT('%', ?, '%'))
        ORDER BY msup.name ASC
        LIMIT 50
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Query vendor gagal: ' . $conn->error
        ], 500);
    }

    $params = [
        $userId,
        $employeeId,
        $materialId,
        $materialId,
        $keyword,
        $keyword
    ];

    bindParams($stmt, 'ssssss', $params);

    if (!$stmt->execute()) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Execute vendor gagal: ' . $stmt->error
        ], 500);
    }

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id'   => $row['id'],
            'name' => $row['name'],
            'text' => $row['name']
        ];
    }

    $stmt->close();

    jsonResponse($data);
}

function getFilterOptions()
{
    global $conn;

    $employeeId = $_SESSION['employee_id'] ?? '';
    $userId     = $_SESSION['user_id'] ?? '';

    $sqlProject = "
        SELECT DISTINCT
            mpro.name
        FROM purchase_vendor_detail pvd
        INNER JOIN purchase_vendor pv 
            ON pv.id = pvd.purchase_vendor_id
        LEFT JOIN work_order_detail wod 
            ON wod.id = pv.work_order_detail_id
        LEFT JOIN work_order wo 
            ON wo.id = wod.work_order_id
        LEFT JOIN ms_perumahan mprum 
            ON mprum.id = wo.perumahan_id
        LEFT JOIN ms_project mpro 
            ON mpro.id = mprum.project_id

        " . getAccessJoin() . "

        WHERE 
            pv.status = 1
            AND pvd.status = 1
            AND mpro.name IS NOT NULL
            " . getAccessWhere() . "
        ORDER BY mpro.name ASC
    ";

    $stmtProject = $conn->prepare($sqlProject);

    if (!$stmtProject) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Query filter cabang gagal: ' . $conn->error
        ], 500);
    }

    $paramsProject = [$userId, $employeeId];
    bindParams($stmtProject, 'ss', $paramsProject);

    if (!$stmtProject->execute()) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Execute filter cabang gagal: ' . $stmtProject->error
        ], 500);
    }

    $resultProject = $stmtProject->get_result();

    $projects = [];
    while ($row = $resultProject->fetch_assoc()) {
        $projects[] = [
            'name' => $row['name']
        ];
    }

    $stmtProject->close();

    $sqlPerumahan = "
        SELECT DISTINCT
            mprum.name
        FROM purchase_vendor_detail pvd
        INNER JOIN purchase_vendor pv 
            ON pv.id = pvd.purchase_vendor_id
        LEFT JOIN work_order_detail wod 
            ON wod.id = pv.work_order_detail_id
        LEFT JOIN work_order wo 
            ON wo.id = wod.work_order_id
        LEFT JOIN ms_perumahan mprum 
            ON mprum.id = wo.perumahan_id
        LEFT JOIN ms_project mpro 
            ON mpro.id = mprum.project_id

        " . getAccessJoin() . "

        WHERE 
            pv.status = 1
            AND pvd.status = 1
            AND mprum.name IS NOT NULL
            " . getAccessWhere() . "
        ORDER BY mprum.name ASC
    ";

    $stmtPerumahan = $conn->prepare($sqlPerumahan);

    if (!$stmtPerumahan) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Query filter perumahan gagal: ' . $conn->error
        ], 500);
    }

    $paramsPerumahan = [$userId, $employeeId];
    bindParams($stmtPerumahan, 'ss', $paramsPerumahan);

    if (!$stmtPerumahan->execute()) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Execute filter perumahan gagal: ' . $stmtPerumahan->error
        ], 500);
    }

    $resultPerumahan = $stmtPerumahan->get_result();

    $perumahan = [];
    while ($row = $resultPerumahan->fetch_assoc()) {
        $perumahan[] = [
            'name' => $row['name']
        ];
    }

    $stmtPerumahan->close();

    jsonResponse([
        'status' => 'success',
        'data' => [
            'projects'  => $projects,
            'perumahan' => $perumahan
        ]
    ]);
}

function getComparisonData()
{
    global $conn;

    $employeeId = $_SESSION['employee_id'] ?? '';
    $userId     = $_SESSION['user_id'] ?? '';

    $materialId = isset($_GET['material_id']) ? trim($_GET['material_id']) : '';
    $vendorId   = isset($_GET['vendor_id']) ? trim($_GET['vendor_id']) : '';
    $project    = isset($_GET['project']) ? trim($_GET['project']) : '';
    $perumahan  = isset($_GET['perumahan']) ? trim($_GET['perumahan']) : '';
    $startDate  = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $endDate    = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

    if ($materialId === '') {
        jsonResponse([]);
    }

    $sql = "
        SELECT
            pv.id AS purchase_vendor_id,
            pv.code AS kode_bp,
            pv.date AS tanggal_bp,

            msup.id AS vendor_id,
            COALESCE(msup.name, '-') AS vendor,

            pvd.material_id,
            COALESCE(mat.code, '') AS kode_material,
            COALESCE(NULLIF(mat.name, ''), NULLIF(pvd.material_name, ''), pvd.material_id, '-') AS material,

            pvd.volume AS qty,
            pvd.unit AS unit,
            pvd.price_unit AS harga_satuan,
            pvd.price_total AS total_harga,

            wo.id AS work_order_id,
            wo.new_code AS kode_sib,

            wod.id AS work_order_detail_id,
            be.code AS kode_rab,

            mpro.name AS cabang,
            mprum.name AS perumahan,
            mkav.code AS kavling

        FROM purchase_vendor_detail pvd
        INNER JOIN purchase_vendor pv 
            ON pv.id = pvd.purchase_vendor_id
        LEFT JOIN ms_suplier msup 
            ON msup.id = pvd.vendor_id
        LEFT JOIN materials mat 
            ON mat.id = pvd.material_id
        LEFT JOIN work_order_detail wod 
            ON wod.id = pv.work_order_detail_id
        LEFT JOIN work_order wo 
            ON wo.id = wod.work_order_id
        LEFT JOIN budget_estimation be 
            ON be.id = wod.budget_estimation_id
        LEFT JOIN ms_kavling mkav 
            ON mkav.id = pvd.kavling_id
        LEFT JOIN ms_perumahan mprum 
            ON mprum.id = wo.perumahan_id
        LEFT JOIN ms_project mpro 
            ON mpro.id = mprum.project_id

        " . getAccessJoin() . "

        WHERE 
            pv.status = 1
            AND pvd.status = 1
            AND pvd.material_id = ?
            " . getAccessWhere() . "
            AND (? = '' OR msup.id = ?)
            AND (? = '' OR mpro.name LIKE CONCAT('%', ?, '%'))
            AND (? = '' OR mprum.name LIKE CONCAT('%', ?, '%'))
            AND (? = '' OR DATE(pv.date) >= ?)
            AND (? = '' OR DATE(pv.date) <= ?)

        ORDER BY 
            pvd.price_unit ASC,
            pv.date DESC,
            msup.name ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Query perbandingan harga gagal: ' . $conn->error
        ], 500);
    }

    $params = [
        $userId,
        $employeeId,
        $materialId,
        $vendorId,
        $vendorId,
        $project,
        $project,
        $perumahan,
        $perumahan,
        $startDate,
        $startDate,
        $endDate,
        $endDate
    ];

    bindParams($stmt, 'sssssssssssss', $params);

    if (!$stmt->execute()) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Execute perbandingan harga gagal: ' . $stmt->error
        ], 500);
    }

    $result = $stmt->get_result();

    $rows = [];
    $prices = [];

    while ($row = $result->fetch_assoc()) {
        $hargaSatuan = (float) ($row['harga_satuan'] ?? 0);
        $qty = (float) ($row['qty'] ?? 0);
        $totalHarga = (float) ($row['total_harga'] ?? 0);

        if ($totalHarga <= 0 && $qty > 0 && $hargaSatuan > 0) {
            $totalHarga = $qty * $hargaSatuan;
        }

        if ($hargaSatuan > 0) {
            $prices[] = $hargaSatuan;
        }

        $row['qty'] = $qty;
        $row['harga_satuan'] = $hargaSatuan;
        $row['total_harga'] = $totalHarga;

        $row['tanggal_bp'] = $row['tanggal_bp'] ?: null;
        $row['kode_bp'] = $row['kode_bp'] ?: '-';
        $row['vendor'] = $row['vendor'] ?: '-';
        $row['material'] = $row['material'] ?: '-';
        $row['unit'] = $row['unit'] ?: '-';
        $row['kavling'] = $row['kavling'] ?: '-';
        $row['kode_sib'] = $row['kode_sib'] ?: '-';
        $row['kode_rab'] = $row['kode_rab'] ?: '-';
        $row['cabang'] = $row['cabang'] ?: '-';
        $row['perumahan'] = $row['perumahan'] ?: '-';

        $rows[] = $row;
    }

    $stmt->close();

    $minPrice = !empty($prices) ? min($prices) : null;

    foreach ($rows as &$row) {
        $hargaSatuan = (float) $row['harga_satuan'];

        $row['is_termurah'] = $minPrice !== null && $hargaSatuan == $minPrice;
        $row['selisih_termurah'] = $minPrice !== null ? ($hargaSatuan - $minPrice) : 0;
        $row['status_harga'] = $row['is_termurah'] ? 'Termurah' : 'Lebih tinggi';
    }

    unset($row);

    jsonResponse($rows);
}

function handleRequest()
{
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'materialOptions':
            getMaterialOptions();
            break;

        case 'vendorOptions':
            getVendorOptions();
            break;

        case 'filterOptions':
            getFilterOptions();
            break;

        case 'comparisonData':
            getComparisonData();
            break;

        default:
            jsonResponse([
                'status' => 'error',
                'message' => 'Action tidak ditemukan'
            ], 404);
            break;
    }
}

handleRequest();

$conn->close();
