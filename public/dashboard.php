<?php
// public/dashboard.php - Logico Fleet Enterprise Executive Management System
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (!in_array('admin', $_SESSION['roles'] ?? [])) {
    redirect('driver_dashboard.php');
}
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logico Fleet Enterprise — Executive Operations Center</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS for Interactive GPS Mapping -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom Premium Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        const savedTheme = localStorage.getItem('fleet_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toast-container"></div>

    <!-- SIDEBAR -->
    <nav id="sidebar">
        <div class="logo-area">
            <i class="fa-solid fa-truck-ramp-box fs-3 text-primary"></i>
            <div>
                <span class="logo-text">Logico Fleet</span>
                <span class="d-block text-muted text-uppercase" style="font-size:0.6rem; letter-spacing:0.12em; font-weight:700;">Enterprise FMCG</span>
            </div>
        </div>
        
        <div class="text-uppercase text-muted fw-bold mb-2 ps-2" style="font-size: 0.68rem; letter-spacing: 0.1em;">Operations Control</div>
        <a href="#" class="nav-link active" data-target="view-overview">
            <i class="fa-solid fa-chart-pie fa-fw"></i> Executive Overview
        </a>
        <a href="#" class="nav-link" data-target="view-map">
            <i class="fa-solid fa-map-location-dot fa-fw"></i> Live GPS Fleet Map
        </a>
        <a href="#" class="nav-link" data-target="view-consignments">
            <i class="fa-solid fa-file-signature fa-fw"></i> Consignments & e-POD
        </a>
        <a href="#" class="nav-link" data-target="view-coldchain">
            <i class="fa-solid fa-snowflake fa-fw"></i> Cold Chain Telemetry
        </a>
        <a href="#" class="nav-link" data-target="view-incidents">
            <i class="fa-solid fa-triangle-exclamation fa-fw text-danger"></i> Roadside Incidents
        </a>
        
        <div class="text-uppercase text-muted fw-bold mb-2 mt-3 ps-2" style="font-size: 0.68rem; letter-spacing: 0.1em;">Fleet & Resources</div>
        <a href="#" class="nav-link" data-target="view-vehicles">
            <i class="fa-solid fa-truck fa-fw"></i> Vehicles Inventory
        </a>
        <a href="#" class="nav-link" data-target="view-drivers">
            <i class="fa-solid fa-id-card fa-fw"></i> Drivers Roster
        </a>
        <a href="#" class="nav-link" data-target="view-trips">
            <i class="fa-solid fa-route fa-fw"></i> Trip Dispatch
        </a>
        
        <div class="text-uppercase text-muted fw-bold mb-2 mt-3 ps-2" style="font-size: 0.68rem; letter-spacing: 0.1em;">Costs & Commercial</div>
        <a href="#" class="nav-link" data-target="view-maintenance">
            <i class="fa-solid fa-wrench fa-fw"></i> Maintenance & CPK
        </a>
        <a href="#" class="nav-link" data-target="view-fuel">
            <i class="fa-solid fa-gas-pump fa-fw"></i> Fuel Costs (E)
        </a>
        <a href="#" class="nav-link text-success fw-bold" data-target="view-pitch">
            <i class="fa-solid fa-briefcase fa-fw"></i> Commercial ROI Deck
        </a>

        <div class="mt-auto pt-4">
            <div class="px-2 mb-3">
                <div class="p-2 bg-primary bg-opacity-10 rounded-3 text-center border border-primary border-opacity-25">
                    <small class="d-block text-muted" style="font-size:0.7rem;">Primary Hub</small>
                    <span class="fw-bold text-primary" style="font-size:0.75rem;">Matsapha Central DC</span>
                </div>
            </div>
            <hr class="border-color">
            <a href="profile_edit.php" class="nav-link text-muted mb-1">
                <i class="fa-solid fa-gear fa-fw"></i> Settings & Profile
            </a>
            <a href="logout.php" class="nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket fa-fw"></i> Secure Logout
            </a>
        </div>
    </nav>

    <!-- MAIN CONTENT WRAPPER -->
    <main class="main-wrapper">
        <!-- Top App Bar -->
        <header class="top-app-bar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary border-0 d-lg-none" id="btn-toggle-sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h4 id="page-title" class="m-0 fw-bold">Executive Overview</h4>
                    <small class="text-muted d-none d-md-inline">Logico Unlimited Supply Chain • Eswatini</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Currency Badge -->
                <span class="badge bg-success bg-opacity-10 text-success border border-success d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-coins"></i> Currency: Emalangeni (E / SZL)
                </span>

                <!-- Quick Action Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-plus me-1"></i> Quick Action
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                        <li><a class="dropdown-item rounded-2 py-2" href="#" data-bs-toggle="modal" data-bs-target="#modal-trip"><i class="fa-solid fa-route text-primary me-2 fa-fw"></i>Dispatch Manifest</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="#" data-bs-toggle="modal" data-bs-target="#modal-vehicle"><i class="fa-solid fa-truck text-success me-2 fa-fw"></i>Register Vehicle</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="#" data-bs-toggle="modal" data-bs-target="#modal-driver"><i class="fa-solid fa-user-plus text-info me-2 fa-fw"></i>Register Driver</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="#" data-bs-toggle="modal" data-bs-target="#modal-maintenance"><i class="fa-solid fa-wrench text-warning me-2 fa-fw"></i>Log Workshop Service</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="#" data-bs-toggle="modal" data-bs-target="#modal-fuel"><i class="fa-solid fa-gas-pump text-primary me-2 fa-fw"></i>Log Fuel Refill</a></li>
                    </ul>
                </div>

                <!-- Theme Switcher -->
                <button class="theme-switch" id="theme-toggle" title="Toggle Theme">
                    <i class="fa-solid fa-moon"></i>
                </button>
                
                <!-- User Profile -->
                <div class="d-flex align-items-center gap-2">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold" style="font-size:0.9rem;"><?php echo e($user['name']); ?></div>
                        <div class="text-muted" style="font-size:0.75rem;">Fleet Operations Director</div>
                    </div>
                    <a href="profile_edit.php" class="text-decoration-none">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; font-family: 'Outfit';">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-area">
            
            <!-- 1. OVERVIEW VIEW -->
            <section id="view-overview" class="view-section">
                <!-- Executive Greeting -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Good <span id="greeting-time">day</span>, <?php echo explode(' ', $user['name'])[0]; ?>.</h2>
                        <p class="text-muted mb-0">Live operations intelligence for Logico Eswatini distribution network.</p>
                    </div>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="printReport()"><i class="fa-solid fa-print me-1"></i> Print Summary</button>
                        <button class="btn btn-primary" onclick="loadStats()"><i class="fa-solid fa-rotate-right me-1"></i> Refresh Data</button>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="glass-card h-100 border-start border-4 border-primary">
                            <div class="text-muted fw-semibold small">Fleet Utilization</div>
                            <div class="metric-value"><span id="stat-utilization">-</span>%</div>
                            <div class="insight-text mt-2"><i class="fa-solid fa-truck-moving text-primary me-1"></i> <span id="stat-active-trips">0</span> active trips in transit</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="glass-card h-100 border-start border-4 border-danger">
                            <div class="text-muted fw-semibold small">30d Operating Cost</div>
                            <div class="metric-value text-danger" id="stat-operating-cost">E -</div>
                            <div class="insight-text text-muted mt-2"><i class="fa-solid fa-chart-line me-1"></i> Fuel + Scheduled Maintenance</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="glass-card h-100 border-start border-4 border-warning">
                            <div class="text-muted fw-semibold small">Cost Per KM (CPK)</div>
                            <div class="metric-value text-warning" id="stat-cpk">E 0.00</div>
                            <div class="insight-text text-muted mt-2"><i class="fa-solid fa-gauge me-1"></i> Over <span id="stat-km">-</span> km covered</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="glass-card h-100 border-start border-4 border-success">
                            <div class="text-muted fw-semibold small">Available Drivers</div>
                            <div class="metric-value text-success" id="stat-active-drivers">-</div>
                            <div class="insight-text text-muted mt-2"><i class="fa-solid fa-id-badge me-1"></i> Ready at Matsapha Hub</div>
                        </div>
                    </div>
                </div>

                <!-- Operational Status Banners -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 d-flex align-items-center justify-content-between cursor-pointer" onclick="switchNav('view-incidents')">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-triangle-exclamation text-danger fs-3"></i>
                                <div>
                                    <div class="fw-bold text-danger">Roadside Incidents</div>
                                    <small class="text-muted">Breakdowns / Punctures reported</small>
                                </div>
                            </div>
                            <span class="fs-4 fw-bold text-danger" id="stat-incidents">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3 d-flex align-items-center justify-content-between cursor-pointer" onclick="switchNav('view-coldchain')">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-snowflake text-info fs-3"></i>
                                <div>
                                    <div class="fw-bold text-info">Cold Chain Integrity</div>
                                    <small class="text-muted">Reefer temperature monitoring</small>
                                </div>
                            </div>
                            <span class="badge bg-success" id="stat-coldchain">All Compliant</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 d-flex align-items-center justify-content-between cursor-pointer" onclick="switchNav('view-maintenance')">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-screwdriver-wrench text-warning fs-3"></i>
                                <div>
                                    <div class="fw-bold text-warning">Preventative Service Due</div>
                                    <small class="text-muted">Vehicles approaching interval</small>
                                </div>
                            </div>
                            <span class="fs-4 fw-bold text-warning" id="stat-service-due">0</span>
                        </div>
                    </div>
                </div>

                <!-- Charts & Map Preview Row -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-7">
                        <div class="glass-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="m-0 fw-bold">7-Day Fleet Operating Expenses (E / SZL)</h5>
                                    <small class="text-muted">Fuel consumption vs. preventative maintenance spend</small>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Daily Audit</span>
                            </div>
                            <canvas id="mainChart" height="260"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="glass-card h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="m-0 fw-bold">Eswatini Depot & Route GPS</h5>
                                    <small class="text-muted">Live vehicle tracking around Matsapha</small>
                                </div>
                                <a href="#" class="btn btn-sm btn-outline-primary" onclick="switchNav('view-map')"><i class="fa-solid fa-expand me-1"></i> Full Map</a>
                            </div>
                            <div id="overview-map" style="height: 270px; border-radius: 12px; border: 1px solid var(--border-color); flex-grow: 1;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Audit & Activity Log -->
                <div class="row">
                    <div class="col-12">
                        <div class="glass-card">
                            <h5 class="mb-3 fw-bold">System Audit & Dispatch Trail</h5>
                            <div class="table-responsive">
                                <table class="table table-borderless table-custom m-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Operator</th>
                                            <th>Event Category</th>
                                            <th>Operational Log</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-activity-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. LIVE GPS FLEET MAP VIEW -->
            <section id="view-map" class="view-section d-none">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Eswatini Interactive GPS Fleet Telemetry</h4>
                        <p class="text-muted mb-0">Real-time positions for Logico trucks, reefers, and regional distribution corridors.</p>
                    </div>
                    <div class="d-flex gap-2 mt-3 mt-md-0">
                        <button class="btn btn-sm btn-outline-primary" onclick="initLeafletMap()"><i class="fa-solid fa-crosshairs me-1"></i> Center on Matsapha DC</button>
                    </div>
                </div>

                <div class="glass-card p-3 mb-4">
                    <div id="leaflet-map"></div>
                </div>

                <!-- Depots and Legend -->
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="glass-card">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-warehouse text-primary me-2"></i>Logico Key Hubs & Regional Cross-Docks</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-custom align-middle m-0">
                                    <thead>
                                        <tr>
                                            <th>Facility Name</th>
                                            <th>Coordinates</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-depots"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card h-100">
                            <h6 class="fw-bold mb-3">Map Legend</h6>
                            <ul class="list-unstyled d-flex flex-column gap-2 small m-0">
                                <li class="d-flex align-items-center gap-2">
                                    <span class="custom-truck-pin active" style="width:24px;height:24px;font-size:10px;"><i class="fa-solid fa-truck"></i></span>
                                    <span>Active / Available at Hub</span>
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="custom-truck-pin on_trip" style="width:24px;height:24px;font-size:10px;"><i class="fa-solid fa-route"></i></span>
                                    <span>In Transit / Delivering Manifest</span>
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="custom-truck-pin maintenance" style="width:24px;height:24px;font-size:10px;"><i class="fa-solid fa-wrench"></i></span>
                                    <span>In Matsapha Heavy Workshop</span>
                                </li>
                                <li class="d-flex align-items-center gap-2">
                                    <span class="custom-depot-pin" style="width:24px;height:24px;font-size:10px;"><i class="fa-solid fa-warehouse"></i></span>
                                    <span>Logico Depot / Border Corridor</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. CONSIGNMENTS & e-POD VIEW -->
            <section id="view-consignments" class="view-section d-none">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Consignments & Electronic Proof of Delivery (e-POD)</h4>
                        <p class="text-muted mb-0">Eliminate missing invoice disputes with timestamped digital receiver signatures.</p>
                    </div>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-consignments-wrap', 'logico_consignments.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-trip">
                            <i class="fa-solid fa-plus me-2"></i> Create Manifest
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-consignments-wrap">
                    <!-- Search & Filter Toolbar -->
                    <div class="table-toolbar">
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-consignments" placeholder="Search waybill, client, or store..." onkeyup="filterConsignments()">
                        </div>
                        <div class="filter-pills-wrap">
                            <span class="filter-pill active" onclick="setConsignmentFilter('all', this)">All</span>
                            <span class="filter-pill" onclick="setConsignmentFilter('in_progress', this)">In Transit</span>
                            <span class="filter-pill" onclick="setConsignmentFilter('completed', this)">Delivered</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>Waybill / Consignment</th>
                                    <th>Destination & Client</th>
                                    <th>Cargo Type</th>
                                    <th>Vehicle & Driver</th>
                                    <th>Delivery Status</th>
                                    <th>Proof of Delivery</th>
                                </tr>
                            </thead>
                            <tbody id="table-consignments"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 4. COLD CHAIN TELEMETRY VIEW -->
            <section id="view-coldchain" class="view-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Cold Chain & Food Safety Telemetry</h4>
                        <p class="text-muted mb-0">Real-time refrigerated truck cargo temperatures for dairy, meat, and frozen confectionery.</p>
                    </div>
                    <button class="btn btn-outline-info" onclick="loadColdChain()"><i class="fa-solid fa-rotate-right me-1"></i> Scan Sensors</button>
                </div>

                <div class="row g-4" id="coldchain-cards-container"></div>
            </section>

            <!-- 5. ROADSIDE INCIDENTS VIEW -->
            <section id="view-incidents" class="view-section d-none">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Roadside Incidents & Emergency Response</h4>
                        <p class="text-muted mb-0">Real-time tracking of driver mechanical breakdowns, punctures, and highway delays.</p>
                    </div>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-incidents-wrap', 'logico_incidents.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-danger px-4 shadow-sm" onclick="window.location.href='incident_report.php'">
                            <i class="fa-solid fa-plus me-2"></i> Log Incident
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-incidents-wrap">
                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>ID</th>
                                    <th>Severity</th>
                                    <th>Vehicle & Driver</th>
                                    <th>Incident Category</th>
                                    <th>Location Reported</th>
                                    <th>Situation Details</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Resolution</th>
                                </tr>
                            </thead>
                            <tbody id="table-incidents"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 6. VEHICLES VIEW -->
            <section id="view-vehicles" class="view-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Vehicle Inventory & Asset Health</h4>
                        <p class="text-muted mb-0">Manage fleet specifications, service odometers, and refrigeration capability.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-vehicles-wrap', 'logico_vehicles.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-vehicle">
                            <i class="fa-solid fa-plus me-2"></i> Register Vehicle
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-vehicles-wrap">
                    <!-- Search & Filter Toolbar -->
                    <div class="table-toolbar">
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-vehicles" placeholder="Search plate, make, model..." onkeyup="filterVehicles()">
                        </div>
                        <div class="filter-pills-wrap">
                            <span class="filter-pill active" onclick="setVehicleFilter('all', this)">All Assets</span>
                            <span class="filter-pill" onclick="setVehicleFilter('active', this)">Active</span>
                            <span class="filter-pill" onclick="setVehicleFilter('maintenance', this)">In Workshop</span>
                            <span class="filter-pill" onclick="setVehicleFilter('reefer', this)">Reefer Cold Chain</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>Plate</th>
                                    <th>Make & Model</th>
                                    <th>Category</th>
                                    <th>Home Depot</th>
                                    <th>Status</th>
                                    <th>Fuel Tank</th>
                                    <th>Odometer</th>
                                    <th>Cold Chain</th>
                                </tr>
                            </thead>
                            <tbody id="table-vehicles"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 7. DRIVERS VIEW -->
            <section id="view-drivers" class="view-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Commercial Driver Roster</h4>
                        <p class="text-muted mb-0">Manage professional driver licenses, duty statuses, and contact details.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-drivers-wrap', 'logico_drivers.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-driver">
                            <i class="fa-solid fa-user-plus me-2"></i> Register Driver
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-drivers-wrap">
                    <!-- Search & Filter Toolbar -->
                    <div class="table-toolbar">
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-drivers" placeholder="Search driver name, license #..." onkeyup="filterDrivers()">
                        </div>
                        <div class="filter-pills-wrap">
                            <span class="filter-pill active" onclick="setDriverFilter('all', this)">All Drivers</span>
                            <span class="filter-pill" onclick="setDriverFilter('active', this)">Ready at Hub</span>
                            <span class="filter-pill" onclick="setDriverFilter('on_trip', this)">On Trip</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>Driver Profile</th>
                                    <th>Contact Info</th>
                                    <th>License Number</th>
                                    <th>Expiry Date</th>
                                    <th>Duty Status</th>
                                </tr>
                            </thead>
                            <tbody id="table-drivers"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 8. TRIP DISPATCH VIEW -->
            <section id="view-trips" class="view-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Trip Dispatch & Route Management</h4>
                        <p class="text-muted mb-0">Authorize routes from Matsapha Central DC to regional stores.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-trips-wrap', 'logico_trips.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-trip">
                            <i class="fa-solid fa-route me-2"></i> Dispatch New Trip
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-trips-wrap">
                    <!-- Search & Filter Toolbar -->
                    <div class="table-toolbar">
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-trips" placeholder="Search vehicle, driver, route..." onkeyup="filterTrips()">
                        </div>
                        <div class="filter-pills-wrap">
                            <span class="filter-pill active" onclick="setTripFilter('all', this)">All Routes</span>
                            <span class="filter-pill" onclick="setTripFilter('in_progress', this)">In Transit</span>
                            <span class="filter-pill" onclick="setTripFilter('scheduled', this)">Scheduled</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>Vehicle Assigned</th>
                                    <th>Driver</th>
                                    <th>Route Details</th>
                                    <th>Status</th>
                                    <th>Departure</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="table-trips"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 9. MAINTENANCE VIEW -->
            <section id="view-maintenance" class="view-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Preventative Maintenance & Workshop Logs</h4>
                        <p class="text-muted mb-0">Track workshop service intervals, tire relining, and Cost Per Kilometer (CPK).</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-maintenance-wrap', 'logico_maintenance.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-maintenance">
                            <i class="fa-solid fa-plus me-2"></i> Log Service Record
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-maintenance-wrap">
                    <div class="table-toolbar">
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-maintenance" placeholder="Search vehicle or service type..." onkeyup="filterMaintenance()">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>Service Date</th>
                                    <th>Vehicle</th>
                                    <th>Service Type</th>
                                    <th>Cost (E)</th>
                                    <th>Workshop Notes</th>
                                </tr>
                            </thead>
                            <tbody id="table-maintenance"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 10. FUEL LOGS VIEW -->
            <section id="view-fuel" class="view-section d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Fuel Consumption & Expense Audit</h4>
                        <p class="text-muted mb-0">Record station receipts, smart fuel card logs, and reconcile litres dispensed.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('table-fuel-wrap', 'logico_fuel_logs.csv')">
                            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                        </button>
                        <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-fuel">
                            <i class="fa-solid fa-plus me-2"></i> Log Refuel Transaction
                        </button>
                    </div>
                </div>

                <div class="glass-card p-0 overflow-hidden" id="table-fuel-wrap">
                    <div class="table-toolbar">
                        <div class="search-input-group">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-fuel" placeholder="Search plate or driver..." onkeyup="filterFuel()">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-custom m-0 align-middle">
                            <thead class="bg-dark bg-opacity-10">
                                <tr>
                                    <th>Refuel Date</th>
                                    <th>Vehicle</th>
                                    <th>Driver</th>
                                    <th>Volume Dispensed</th>
                                    <th>Total Cost (E)</th>
                                </tr>
                            </thead>
                            <tbody id="table-fuel"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 11. COMMERCIAL ROI DECK & PITCH VIEW -->
            <section id="view-pitch" class="view-section d-none">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success mb-2 px-3 py-1">LOGICO UNLIMITED BOARDROOM PITCH</span>
                        <h3 class="fw-bold mb-1">Commercial Value Proposition & ROI Deck</h3>
                        <p class="text-muted mb-0">Financial savings and operational efficiency projections for Logico's leadership team.</p>
                    </div>
                    <div class="mt-3 mt-md-0 d-flex gap-2">
                        <button class="btn btn-success fw-bold px-4 shadow-sm" onclick="printReport()"><i class="fa-solid fa-file-pdf me-2"></i> Export Executive Pitch PDF</button>
                    </div>
                </div>

                <!-- ROI Projection Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="roi-card">
                            <small class="text-muted fw-bold text-uppercase">Fuel Loss Reduction</small>
                            <h3 class="fw-bold text-success mt-2 mb-1">E 450,000</h3>
                            <small class="text-muted"><i class="fa-solid fa-shield-halved text-success me-1"></i>18% savings via fuel card reconciliation & tamper audit</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="roi-card" style="border-left-color: #3b82f6;">
                            <small class="text-muted fw-bold text-uppercase">Preventative Maintenance</small>
                            <h3 class="fw-bold text-primary mt-2 mb-1">E 280,000</h3>
                            <small class="text-muted"><i class="fa-solid fa-wrench text-primary me-1"></i>22% reduction in breakdowns and roadside towing</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="roi-card" style="border-left-color: #0ea5e9;">
                            <small class="text-muted fw-bold text-uppercase">Invoice Payment Acceleration</small>
                            <h3 class="fw-bold text-info mt-2 mb-1">E 195,000</h3>
                            <small class="text-muted"><i class="fa-solid fa-signature text-info me-1"></i>Instant digital e-POD eliminates billing disputes</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="roi-card" style="border-left-color: #f59e0b;">
                            <small class="text-muted fw-bold text-uppercase">Projected Payback</small>
                            <h3 class="fw-bold text-warning mt-2 mb-1">3.2 Months</h3>
                            <small class="text-muted"><i class="fa-solid fa-calculator text-warning me-1"></i>Total projected annual ROI: <strong>E 925,000</strong></small>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="glass-card h-100">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-award text-success me-2"></i>Why Logico Needs This Platform</h5>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex gap-3">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center p-3" style="width:48px;height:48px;"><i class="fa-solid fa-snowflake fs-5"></i></div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Cold Chain Compliance for Global Brands</h6>
                                        <p class="text-muted small mb-0">Protects sensitive goods (Lactalis dairy, Nestlé confectionery, frozen meats) with real-time temperature telemetry, eliminating stock spoilage claims.</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center p-3" style="width:48px;height:48px;"><i class="fa-solid fa-signature fs-5"></i></div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Immediate Retailer Proof of Delivery (e-POD)</h6>
                                        <p class="text-muted small mb-0">Shoprite, Spar, and Boxer store managers sign directly on the driver's screen upon receiving crates. No lost paper manifests, speeding up cash flow.</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center p-3" style="width:48px;height:48px;"><i class="fa-solid fa-road fs-5"></i></div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Southern Africa Cross-Border Corridors</h6>
                                        <p class="text-muted small mb-0">Pre-mapped geofences for Ngwenya/Oshoek and Lavumisa border posts track turnaround times for shipments moving between Eswatini and South Africa.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="glass-card h-100">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-handshake text-primary me-2"></i>Commercial Licensing & Implementation Model</h5>
                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-0">Enterprise Tier: Logico Unlimited Eswatini</h6>
                                        <small class="text-muted">Includes unlimited vehicles, Matsapha dispatch center & mobile driver portal</small>
                                    </div>
                                    <span class="badge bg-primary">Full Deployment</span>
                                </div>
                            </div>
                            <ul class="list-group list-group-flush small mb-3">
                                <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                    <span><i class="fa-solid fa-check text-success me-2"></i>Zero External Hosting Dependencies: Runs inside Logico's secure local network</span>
                                    <strong class="text-success">Ready</strong>
                                </li>
                                <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                    <span><i class="fa-solid fa-check text-success me-2"></i>Driver Smartphone Friendly: No expensive hardware required</span>
                                    <strong class="text-success">Ready</strong>
                                </li>
                                <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                    <span><i class="fa-solid fa-check text-success me-2"></i>Full Source Code & Database Ownership for Logico IT</span>
                                    <strong class="text-success">Included</strong>
                                </li>
                            </ul>
                            <button class="btn btn-primary w-100 py-3 fw-bold shadow-sm" onclick="printReport()">
                                <i class="fa-solid fa-print me-2"></i> Print Complete Boardroom Proposal
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- ========================================================================= -->
    <!-- ENTERPRISE OPERATIONAL MODALS -->
    <!-- ========================================================================= -->

    <!-- 1. REGISTER VEHICLE MODAL -->
    <div class="modal fade" id="modal-vehicle" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-truck me-2"></i>Register Fleet Vehicle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formVehicle">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">License Plate (Eswatini)</label>
                                <input type="text" id="v_plate" class="form-control" placeholder="e.g. L-105-SD" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Vehicle Type</label>
                                <select id="v_type" class="form-select" required>
                                    <option value="truck">Heavy Truck (Tautliner/Hauler)</option>
                                    <option value="truck">Reefer Truck (Cold Chain)</option>
                                    <option value="van">Distribution Van (Sprinter)</option>
                                    <option value="car">Supervisor Bakkie / Hilux</option>
                                    <option value="trailer">Tri-Axle Trailer</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Make</label>
                                <input type="text" id="v_make" class="form-control" placeholder="e.g. Scania, Volvo, Isuzu" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Model</label>
                                <input type="text" id="v_model" class="form-control" placeholder="e.g. R450 24T" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Year</label>
                                <input type="number" id="v_year" class="form-control" value="2023" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Starting Odometer (KM)</label>
                                <input type="number" id="v_odo" class="form-control" value="50000" required>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="v_cold_chain">
                            <label class="form-check-label fw-semibold" for="v_cold_chain">Cold Chain Refrigeration Capable (Reefer)</label>
                        </div>
                        <div id="v_alert" class="mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Register Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. REGISTER DRIVER MODAL -->
    <div class="modal fade" id="modal-driver" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Register Commercial Driver</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formDriver">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" id="d_name" class="form-control" placeholder="e.g. Bongani Mthembu" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" id="d_email" class="form-control" placeholder="e.g. driver4@logico.test" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" id="d_phone" class="form-control" placeholder="+268 7600 0000" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">License Number</label>
                                <input type="text" id="d_license" class="form-control" placeholder="DL-ESW-19283" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">License Expiry Date</label>
                                <input type="date" id="d_expiry" class="form-control" value="2028-12-31" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Account Password</label>
                            <input type="password" id="d_pass" class="form-control" value="Driver@123" required>
                        </div>
                        <div id="d_alert" class="mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Register Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. DISPATCH NEW TRIP MODAL -->
    <div class="modal fade" id="modal-trip" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-route me-2"></i>Dispatch FMCG Delivery Manifest</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formTrip">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Vehicle</label>
                                <select id="t_vehicle" class="form-select" required></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Assigned Driver</label>
                                <select id="t_driver" class="form-select" required></select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Consignment / Waybill #</label>
                                <input type="text" id="t_consignment" class="form-control" placeholder="e.g. LOG-ESW-84920">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Client / Supermarket</label>
                                <input type="text" id="t_client" class="form-control" placeholder="e.g. Shoprite Mbabane Store #12" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Cargo Description</label>
                            <input type="text" id="t_cargo" class="form-control" placeholder="e.g. Chilled Dairy & Cold Deli (Lactalis)" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Origin Hub</label>
                                <input type="text" id="t_origin" class="form-control" value="Logico Central DC, Matsapha" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Destination</label>
                                <input type="text" id="t_destination" class="form-control" placeholder="e.g. Mbabane Commercial District" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Dispatch Action</label>
                            <select id="t_status" class="form-select">
                                <option value="in_progress">Dispatch Immediately (In Transit)</option>
                                <option value="scheduled">Schedule for Later Departure</option>
                            </select>
                        </div>
                        <div id="t_alert" class="mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Authorize Dispatch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 4. LOG SERVICE / MAINTENANCE MODAL -->
    <div class="modal fade" id="modal-maintenance" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-wrench me-2"></i>Log Workshop Service Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formMaintenance">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <select id="m_vehicle" class="form-select" required></select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Service Date</label>
                                <input type="date" id="m_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Service Type</label>
                                <select id="m_type" class="form-select" required>
                                    <option value="Full Scheduled Service (15,000 KM)">Full Scheduled Service (15,000 KM)</option>
                                    <option value="Brake Lining & Disc Inspection">Brake Lining & Disc Inspection</option>
                                    <option value="Reefer Cold Unit Calibration">Reefer Cold Unit Calibration</option>
                                    <option value="Tire Replacement & Wheel Alignment">Tire Replacement & Wheel Alignment</option>
                                    <option value="Engine & Transmission Diagnostics">Engine & Transmission Diagnostics</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Total Cost (E / SZL)</label>
                                <input type="number" step="0.01" id="m_cost" class="form-control" placeholder="e.g. 5800.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Odometer Reading (KM)</label>
                                <input type="number" id="m_odo" class="form-control" value="68400" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Technician / Workshop Notes</label>
                            <textarea id="m_desc" class="form-control" rows="2" placeholder="e.g. Matsapha Workshop. Oil changed, air filters replaced, reefer pressure tested."></textarea>
                        </div>
                        <div id="m_alert" class="mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning fw-bold px-4">Save Maintenance Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. LOG FUEL MODAL -->
    <div class="modal fade" id="modal-fuel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-gas-pump me-2"></i>Log Fuel Refill Transaction</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formFuel">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Vehicle</label>
                                <select id="f_vehicle" class="form-select" required></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Driver</label>
                                <select id="f_driver" class="form-select" required></select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Volume (Liters)</label>
                                <input type="number" step="0.01" id="f_liters" class="form-control" placeholder="e.g. 240" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Total Cost (E / SZL)</label>
                                <input type="number" step="0.01" id="f_cost" class="form-control" placeholder="e.g. 5200.00" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Odometer (KM)</label>
                                <input type="number" id="f_odo" class="form-control" value="68400" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Tank Level (%)</label>
                                <input type="number" id="f_level" class="form-control" value="100" min="1" max="100" required>
                            </div>
                        </div>
                        <div id="f_alert" class="mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Record Refuel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. VIEW e-POD MODAL -->
    <div class="modal fade" id="modal-view-epod" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-stamp me-2"></i>Verified Proof of Delivery (e-POD)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="p-3 bg-light rounded-3 mb-3 border text-start">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Consignment:</span>
                            <span class="fw-bold" id="vepod_consignment">LOG-ESW-84920</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Store / Client:</span>
                            <span class="fw-bold" id="vepod_client">Shoprite Hyper Mbabane</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Receiver Name:</span>
                            <span class="fw-bold text-primary" id="vepod_recipient">Sipho Dlamini</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Timestamp:</span>
                            <span class="small" id="vepod_time">2026-10-31 16:45</span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted small text-uppercase mb-2 text-start">Store Receiver Digital Signature</h6>
                    <div class="p-3 bg-white border rounded-3 mb-3 d-flex align-items-center justify-content-center" style="min-height: 150px;">
                        <img id="vepod_sig_img" src="" alt="Digital Signature" style="max-height: 140px; max-width: 100%;">
                    </div>

                    <div class="text-start">
                        <small class="text-muted fw-bold d-block">Delivery Inspection Notes:</small>
                        <p class="small text-muted mb-0" id="vepod_notes">All goods received in good order. Cold chain seal verified.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print Proof</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // TOAST NOTIFICATION HELPER
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `fleet-toast toast-${type}`;
        
        let icon = 'fa-circle-check text-success';
        if (type === 'error') icon = 'fa-circle-xmark text-danger';
        if (type === 'warning') icon = 'fa-triangle-exclamation text-warning';
        if (type === 'info') icon = 'fa-circle-info text-primary';

        toast.innerHTML = `
            <i class="fa-solid ${icon} fs-5"></i>
            <div class="flex-grow-1 small fw-semibold">${message}</div>
            <button class="btn btn-sm btn-link text-muted p-0 border-0 ms-2" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
        `;
        
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // CSV EXPORT HELPER
    function exportTableToCSV(containerId, filename) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const table = container.querySelector('table');
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll('td, th');
            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/(\s\s+)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            csv.push(row.join(','));
        }

        const csvString = csv.join('\n');
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast(`Exported ${filename} successfully!`, 'info');
    }

    // Greeting
    const hour = new Date().getHours();
    document.getElementById('greeting-time').innerText = hour < 12 ? 'morning' : (hour < 18 ? 'afternoon' : 'evening');

    // Theme Switcher
    const themeToggleBtn = document.getElementById('theme-toggle');
    const updateThemeIcon = () => {
        const theme = document.documentElement.getAttribute('data-theme');
        themeToggleBtn.innerHTML = theme === 'dark' ? '<i class="fa-solid fa-sun text-warning"></i>' : '<i class="fa-solid fa-moon"></i>';
    };
    updateThemeIcon();

    themeToggleBtn.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('fleet_theme', newTheme);
        updateThemeIcon();
        if(chartInstance) loadStats();
    });

    // Mobile Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const btnToggleSidebar = document.getElementById('btn-toggle-sidebar');
    if (btnToggleSidebar) {
        btnToggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Navigation Switcher
    function switchNav(targetId) {
        document.querySelectorAll('.nav-link[data-target]').forEach(l => l.classList.remove('active'));
        const activeLink = document.querySelector(`.nav-link[data-target="${targetId}"]`);
        if (activeLink) activeLink.classList.add('active');

        document.querySelectorAll('.view-section').forEach(sec => sec.classList.add('d-none'));
        const targetSec = document.getElementById(targetId);
        if (targetSec) targetSec.classList.remove('d-none');

        if (activeLink) {
            document.getElementById('page-title').innerText = activeLink.innerText.trim();
        }

        if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }

        if(targetId === 'view-overview') loadStats();
        if(targetId === 'view-map') setTimeout(initLeafletMap, 200);
        if(targetId === 'view-consignments') loadConsignments();
        if(targetId === 'view-coldchain') loadColdChain();
        if(targetId === 'view-incidents') loadIncidents();
        if(targetId === 'view-vehicles') loadVehicles();
        if(targetId === 'view-drivers') loadDrivers();
        if(targetId === 'view-trips') loadTrips();
        if(targetId === 'view-maintenance') loadMaintenance();
        if(targetId === 'view-fuel') loadFuel();
    }

    document.querySelectorAll('.nav-link[data-target]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = e.currentTarget.getAttribute('data-target');
            switchNav(targetId);
        });
    });

    // API Helper
    async function apiFetch(endpoint, options = {}) {
        try {
            const res = await fetch('../api/' + endpoint, options);
            if (!res.ok) throw new Error(res.statusText);
            return await res.json();
        } catch(e) {
            console.error('API Error:', e);
            return null;
        }
    }

    let chartInstance = null;
    let mainMap = null, overviewMap = null;
    let mapVehiclesData = [], mapDepotsData = [];

    // Cached Data for Instant Filtering
    let allVehicles = [], vehicleFilter = 'all';
    let allDrivers = [], driverFilter = 'all';
    let allTrips = [], tripFilter = 'all';
    let allConsignments = [], consignmentFilter = 'all';
    let allMaintenance = [];
    let allFuel = [];

    // 1. LOAD STATS & DASHBOARD DATA
    async function loadStats() {
        const data = await apiFetch('stats.php');
        if(!data) return;

        mapVehiclesData = data.map_vehicles || [];
        mapDepotsData = data.depots || [];

        // Metrics
        document.getElementById('stat-utilization').innerText = data.stats.utilization_rate;
        document.getElementById('stat-active-trips').innerText = data.stats.active_trips;
        document.getElementById('stat-active-drivers').innerText = data.stats.active_drivers;
        document.getElementById('stat-operating-cost').innerText = 'E ' + parseFloat(data.stats.operating_cost).toLocaleString(undefined, {minimumFractionDigits:2});
        document.getElementById('stat-cpk').innerText = 'E ' + parseFloat(data.stats.cpk || 7.20).toFixed(2);
        document.getElementById('stat-km').innerText = (data.stats.total_km || 3450).toLocaleString();
        
        document.getElementById('stat-incidents').innerText = data.stats.open_incidents || 0;
        document.getElementById('stat-service-due').innerText = data.stats.service_due || 0;

        const coldBadge = document.getElementById('stat-coldchain');
        if (data.stats.cold_chain_breaches > 0) {
            coldBadge.className = 'badge bg-danger';
            coldBadge.innerText = `${data.stats.cold_chain_breaches} Alert(s)`;
        } else {
            coldBadge.className = 'badge bg-success';
            coldBadge.innerText = 'All Compliant';
        }

        // Chart
        const ctx = document.getElementById('mainChart').getContext('2d');
        if(chartInstance) chartInstance.destroy();
        
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.chart.labels,
                datasets: [
                    {
                        label: 'Fuel Spend (E)',
                        data: data.chart.fuel_costs,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }, 
                    {
                        label: 'Workshop & Maintenance (E)',
                        data: data.chart.maint_costs,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: textColor } },
                    y: { stacked: true, grid: { color: gridColor }, ticks: { color: textColor } }
                },
                plugins: { legend: { labels: { color: textColor, font: { family: 'Inter' } } } }
            }
        });

        // Activity Logs
        const tbody = document.getElementById('recent-activity-tbody');
        tbody.innerHTML = '';
        (data.recent_logs || []).forEach(l => {
            tbody.innerHTML += `
                <tr>
                    <td class="text-muted"><small>${l.created_at}</small></td>
                    <td><div class="d-flex align-items-center gap-2"><div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:28px;height:28px;font-size:0.75rem;">${l.user_name ? l.user_name[0] : 'S'}</div> ${l.user_name || 'System Dispatch'}</div></td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">${l.action}</span></td>
                    <td class="text-muted">${l.description}</td>
                </tr>
            `;
        });

        initOverviewMap();
    }

    // LEAFLET MAP FUNCTIONS
    function initOverviewMap() {
        const container = document.getElementById('overview-map');
        if (!container || overviewMap) return;

        overviewMap = L.map('overview-map', { zoomControl: false }).setView([-26.5167, 31.3167], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap'
        }).addTo(overviewMap);

        renderMapMarkers(overviewMap);
    }

    function initLeafletMap() {
        const container = document.getElementById('leaflet-map');
        if (!container) return;

        if (!mainMap) {
            mainMap = L.map('leaflet-map').setView([-26.52, 31.35], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap • Logico Fleet Telemetry'
            }).addTo(mainMap);
        } else {
            mainMap.invalidateSize();
            mainMap.setView([-26.52, 31.35], 10);
        }

        renderMapMarkers(mainMap);
        renderDepotsTable();
    }

    function renderMapMarkers(map) {
        if (!map) return;

        mapDepotsData.forEach(d => {
            const depotIcon = L.divIcon({
                className: '',
                html: `<div class="custom-depot-pin" title="${d.name}"><i class="fa-solid fa-warehouse"></i></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            L.marker([d.lat, d.lng], { icon: depotIcon }).addTo(map)
                .bindPopup(`<strong>${d.name}</strong><br><small class="text-muted">Role: ${d.type.toUpperCase()}</small>`);
        });

        mapVehiclesData.forEach(v => {
            const isCold = v.cold_chain == 1;
            const iconClass = v.status === 'on_trip' ? 'on_trip' : (v.status === 'maintenance' ? 'maintenance' : 'active');
            const iconSymbol = isCold ? 'fa-snowflake' : 'fa-truck';

            const truckIcon = L.divIcon({
                className: '',
                html: `<div class="custom-truck-pin ${iconClass}" title="${v.license_plate}"><i class="fa-solid ${iconSymbol}"></i></div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 17]
            });

            let popupContent = `
                <div style="min-width:180px;">
                    <div class="fw-bold">${v.license_plate} — ${v.make}</div>
                    <small class="text-muted">${v.model}</small>
                    <hr class="my-1">
                    <div class="small"><strong>Status:</strong> ${v.status.toUpperCase()}</div>
                    <div class="small"><strong>Fuel:</strong> ${v.fuel_level}%</div>
                    ${isCold ? `<div class="small text-info fw-bold"><i class="fa-solid fa-snowflake me-1"></i>Temp: ${v.temp_celsius}°C</div>` : ''}
                    ${v.active_trip ? `
                        <div class="mt-2 p-1 bg-light rounded border small">
                            <strong>Destination:</strong> ${v.active_trip.destination}<br>
                            <strong>Driver:</strong> ${v.active_trip.driver}<br>
                            <strong>Manifest:</strong> ${v.active_trip.consignment_no}
                        </div>
                    ` : '<div class="small text-success mt-1">Ready for dispatch</div>'}
                </div>
            `;

            L.marker([v.lat, v.lng], { icon: truckIcon }).addTo(map)
                .bindPopup(popupContent);
        });
    }

    function renderDepotsTable() {
        const tbody = document.getElementById('table-depots');
        if (!tbody) return;
        tbody.innerHTML = '';
        mapDepotsData.forEach(d => {
            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold"><i class="fa-solid fa-location-dot text-primary me-2"></i>${d.name}</td>
                    <td class="font-monospace text-muted small">${d.lat.toFixed(4)}, ${d.lng.toFixed(4)}</td>
                    <td><span class="badge badge-depot">${d.type}</span></td>
                    <td><span class="badge bg-success bg-opacity-10 text-success">Operational</span></td>
                </tr>
            `;
        });
    }

    function badgeFormat(status) {
        return `<span class="badge bg-${status==='active'?'success':(status==='in_progress'||status==='on_trip'?'primary':(status==='maintenance'?'warning':'danger'))}">${status.replace('_', ' ')}</span>`;
    }

    // 2. CONSIGNMENTS & e-POD (With Real-Time Search & Filter)
    async function loadConsignments() {
        const data = await apiFetch('trips.php');
        if(!data) return;
        allConsignments = data;
        renderConsignments();
    }

    function setConsignmentFilter(filter, el) {
        consignmentFilter = filter;
        document.querySelectorAll('#table-consignments-wrap .filter-pill').forEach(p => p.classList.remove('active'));
        if (el) el.classList.add('active');
        renderConsignments();
    }

    function filterConsignments() {
        renderConsignments();
    }

    function renderConsignments() {
        const tbody = document.getElementById('table-consignments');
        if (!tbody) return;
        const q = (document.getElementById('search-consignments')?.value || '').toLowerCase();

        const filtered = allConsignments.filter(t => {
            const matchesFilter = (consignmentFilter === 'all') ||
                (consignmentFilter === 'in_progress' && t.status === 'in_progress') ||
                (consignmentFilter === 'completed' && t.status === 'completed');
            
            const matchSearch = !q || 
                (t.consignment_no && t.consignment_no.toLowerCase().includes(q)) ||
                (t.client_name && t.client_name.toLowerCase().includes(q)) ||
                (t.destination && t.destination.toLowerCase().includes(q)) ||
                (t.cargo_type && t.cargo_type.toLowerCase().includes(q));

            return matchesFilter && matchSearch;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-inbox"></i><h6>No consignments found</h6><small>Try clearing your search query or dispatch a new manifest.</small></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(t => {
            const hasPod = !!t.pod_signature;
            tbody.innerHTML += `
                <tr>
                    <td><span class="fw-bold font-monospace text-primary">${t.consignment_no || 'LOG-ESW-' + t.id}</span></td>
                    <td>
                        <div class="fw-bold">${t.client_name || t.destination}</div>
                        <small class="text-muted"><i class="fa-solid fa-arrow-right opacity-50 me-1"></i>${t.destination}</small>
                    </td>
                    <td><span class="badge bg-light text-dark border">${t.cargo_type || 'General FMCG'}</span></td>
                    <td>
                        <span class="badge bg-dark bg-opacity-10 text-main border mb-1">${t.license_plate}</span>
                        <div class="small text-muted">${t.driver_name}</div>
                    </td>
                    <td>${badgeFormat(t.status)}</td>
                    <td>
                        ${hasPod 
                            ? `<button class="btn btn-sm btn-success fw-bold px-3 shadow-sm" onclick='viewEpod(${JSON.stringify(t)})'>
                                   <i class="fa-solid fa-stamp me-1"></i> View e-POD
                               </button>` 
                            : `<span class="badge bg-secondary bg-opacity-10 text-muted">Awaiting Delivery</span>`
                        }
                    </td>
                </tr>
            `;
        });
    }

    function viewEpod(trip) {
        document.getElementById('vepod_consignment').innerText = trip.consignment_no || 'LOG-ESW-' + trip.id;
        document.getElementById('vepod_client').innerText = trip.client_name || trip.destination;
        document.getElementById('vepod_recipient').innerText = trip.pod_recipient || 'Store Receiving Manager';
        document.getElementById('vepod_time').innerText = trip.pod_timestamp || trip.end_time || 'Delivered';
        document.getElementById('vepod_notes').innerText = trip.pod_notes || 'All goods received in good order. Cold chain seal verified.';
        document.getElementById('vepod_sig_img').src = trip.pod_signature || '';

        new bootstrap.Modal(document.getElementById('modal-view-epod')).show();
    }

    // 3. COLD CHAIN TELEMETRY
    async function loadColdChain() {
        const data = await apiFetch('vehicles.php');
        if(!data) return;
        const container = document.getElementById('coldchain-cards-container');
        container.innerHTML = '';

        const reefers = data.filter(v => v.cold_chain_capable == 1);
        if (reefers.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="empty-state"><i class="fa-solid fa-snowflake"></i><h6>No Cold Chain Reefers Configured</h6><small>Register a reefer truck to begin monitoring temperature sensors.</small></div></div>';
            return;
        }

        reefers.forEach(v => {
            const temp = parseFloat(v.temp_celsius || 3.8);
            const isBreach = temp > 5.0;
            container.innerHTML += `
                <div class="col-md-4">
                    <div class="glass-card h-100 ${isBreach ? 'border-danger' : 'border-info'}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge ${isBreach ? 'badge-breach' : 'badge-cold'}">
                                <i class="fa-solid fa-snowflake"></i> ${isBreach ? 'TEMPERATURE ALERT' : 'COMPLIANT'}
                            </span>
                            <span class="badge bg-dark bg-opacity-10 text-main border">${v.license_plate}</span>
                        </div>
                        <h4 class="fw-bold mb-1">${v.make} ${v.model}</h4>
                        <small class="text-muted d-block mb-3">Assigned Route: Matsapha Central DC</small>
                        
                        <div class="p-3 bg-light rounded-3 mb-3 text-center border">
                            <small class="text-muted d-block">Cargo Compartment Temp</small>
                            <span class="fs-1 fw-bold ${isBreach ? 'text-danger' : 'text-primary'}">${temp > 0 ? '+' : ''}${temp}°C</span>
                            <div class="small text-muted mt-1">Target Threshold: +2°C to +5°C (Chilled FMCG)</div>
                        </div>

                        <div class="d-flex justify-content-between small text-muted">
                            <span>Status: <strong>${v.status.toUpperCase()}</strong></span>
                            <span>Fuel: <strong>${v.fuel_level}%</strong></span>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // 4. ROADSIDE INCIDENTS
    async function loadIncidents() {
        const data = await apiFetch('incidents.php');
        if(!data) return;
        const tbody = document.getElementById('table-incidents');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-circle-check text-success"></i><h6>All Clear on Eswatini Corridors</h6><small>No breakdowns or emergencies reported by drivers.</small></div></td></tr>`;
            return;
        }

        data.forEach(inc => {
            const sevBadge = inc.severity === 'critical' ? 'bg-danger' : (inc.severity === 'medium' ? 'bg-warning text-dark' : 'bg-info text-dark');
            tbody.innerHTML += `
                <tr>
                    <td>#${inc.id}</td>
                    <td><span class="badge ${sevBadge} text-uppercase">${inc.severity}</span></td>
                    <td>
                        <span class="badge bg-dark bg-opacity-10 text-main border mb-1">${inc.license_plate}</span>
                        <div class="small text-muted">${inc.driver_name} (${inc.driver_phone || 'Radio'})</div>
                    </td>
                    <td class="fw-bold text-capitalize">${inc.incident_type.replace('_', ' ')}</td>
                    <td><i class="fa-solid fa-location-dot text-danger me-1"></i> ${inc.location}</td>
                    <td><small class="text-muted">${inc.description}</small></td>
                    <td><span class="badge bg-${inc.status==='resolved'?'success':'danger'}">${inc.status.toUpperCase()}</span></td>
                    <td class="text-end pe-4">
                        ${inc.status === 'reported' 
                            ? `<button class="btn btn-sm btn-outline-success" onclick="resolveIncident(${inc.id})"><i class="fa-solid fa-check me-1"></i> Resolve</button>` 
                            : `<span class="small text-success"><i class="fa-solid fa-circle-check"></i> Handled</span>`
                        }
                    </td>
                </tr>
            `;
        });
    }

    async function resolveIncident(id) {
        const notes = prompt('Enter resolution summary for incident #' + id + ' (e.g. Mobile tyre repair completed, vehicle continued route):');
        if (!notes) return;
        try {
            const res = await fetch('../api/incidents.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: 'resolved', resolution_notes: notes })
            });
            if (res.ok) {
                showToast(`Incident #${id} resolved successfully!`, 'success');
                loadIncidents();
                loadStats();
            }
        } catch (e) {
            showToast('Error updating incident', 'error');
        }
    }

    // 5. VEHICLES TABLE & LIVE SEARCH
    async function loadVehicles() {
        const data = await apiFetch('vehicles.php');
        if(!data) return;
        allVehicles = data;

        // Populate modal selects
        const selects = ['t_vehicle', 'm_vehicle', 'f_vehicle'];
        selects.forEach(sId => {
            const sel = document.getElementById(sId);
            if (sel) {
                sel.innerHTML = '<option value="">-- Select Vehicle --</option>';
                data.forEach(v => {
                    sel.innerHTML += `<option value="${v.id}">${v.license_plate} — ${v.make} ${v.model}</option>`;
                });
            }
        });

        renderVehicles();
    }

    function setVehicleFilter(filter, el) {
        vehicleFilter = filter;
        document.querySelectorAll('#table-vehicles-wrap .filter-pill').forEach(p => p.classList.remove('active'));
        if (el) el.classList.add('active');
        renderVehicles();
    }

    function filterVehicles() {
        renderVehicles();
    }

    function renderVehicles() {
        const tbody = document.getElementById('table-vehicles');
        if (!tbody) return;
        const q = (document.getElementById('search-vehicles')?.value || '').toLowerCase();

        const filtered = allVehicles.filter(v => {
            const matchFilter = (vehicleFilter === 'all') ||
                (vehicleFilter === 'active' && v.status === 'active') ||
                (vehicleFilter === 'maintenance' && (v.status === 'maintenance' || v.status === 'out_of_service')) ||
                (vehicleFilter === 'reefer' && v.cold_chain_capable == 1);

            const matchSearch = !q ||
                v.license_plate.toLowerCase().includes(q) ||
                v.make.toLowerCase().includes(q) ||
                v.model.toLowerCase().includes(q);

            return matchFilter && matchSearch;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-truck"></i><h6>No vehicles found</h6><small>Adjust your search filters or register a new fleet asset.</small></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(v => {
            tbody.innerHTML += `
                <tr>
                    <td><span class="badge bg-dark bg-opacity-10 text-main border border-secondary">${v.license_plate}</span></td>
                    <td class="fw-bold">${v.make} <span class="text-muted fw-normal">${v.model} (${v.year})</span></td>
                    <td class="text-capitalize text-muted">${v.type}</td>
                    <td><span class="badge badge-depot">${v.depot_location || 'Matsapha DC'}</span></td>
                    <td>${badgeFormat(v.status)}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress progress-custom flex-grow-1" style="width:60px">
                                <div class="progress-custom-bar bg-${v.fuel_level > 20 ? 'primary' : 'danger'}" style="width: ${v.fuel_level}%"></div>
                            </div>
                            <small class="text-muted">${v.fuel_level}%</small>
                        </div>
                    </td>
                    <td class="text-muted"><i class="fa-solid fa-gauge-high me-1 opacity-50"></i> ${parseInt(v.odometer).toLocaleString()} KM</td>
                    <td>${v.cold_chain_capable == 1 ? `<span class="badge badge-cold"><i class="fa-solid fa-snowflake"></i> ${v.temp_celsius || '3.8'}°C</span>` : '<span class="text-muted small">Ambient</span>'}</td>
                </tr>
            `;
        });
    }

    // 6. DRIVERS TABLE & LIVE SEARCH
    async function loadDrivers() {
        const data = await apiFetch('drivers.php');
        if(!data) return;
        allDrivers = data;

        const selects = ['t_driver', 'f_driver'];
        selects.forEach(sId => {
            const sel = document.getElementById(sId);
            if (sel) {
                sel.innerHTML = '<option value="">-- Select Driver --</option>';
                data.forEach(d => {
                    sel.innerHTML += `<option value="${d.id}">${d.name} (${d.license_number})</option>`;
                });
            }
        });

        renderDrivers();
    }

    function setDriverFilter(filter, el) {
        driverFilter = filter;
        document.querySelectorAll('#table-drivers-wrap .filter-pill').forEach(p => p.classList.remove('active'));
        if (el) el.classList.add('active');
        renderDrivers();
    }

    function filterDrivers() {
        renderDrivers();
    }

    function renderDrivers() {
        const tbody = document.getElementById('table-drivers');
        if (!tbody) return;
        const q = (document.getElementById('search-drivers')?.value || '').toLowerCase();

        const filtered = allDrivers.filter(d => {
            const matchFilter = (driverFilter === 'all') ||
                (driverFilter === 'active' && d.status === 'active') ||
                (driverFilter === 'on_trip' && d.status === 'on_trip');

            const matchSearch = !q ||
                d.name.toLowerCase().includes(q) ||
                d.email.toLowerCase().includes(q) ||
                d.license_number.toLowerCase().includes(q);

            return matchFilter && matchSearch;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fa-solid fa-user-xmark"></i><h6>No drivers found</h6><small>Clear search or register a new commercial driver.</small></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(d => {
            tbody.innerHTML += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;">${d.name[0]}</div> 
                            <span class="fw-bold">${d.name}</span>
                        </div>
                    </td>
                    <td><div class="text-muted small">${d.email}<br>${d.phone||'+268 7600 0000'}</div></td>
                    <td><span class="font-monospace text-muted">${d.license_number}</span></td>
                    <td class="text-muted">${d.license_expiry}</td>
                    <td>${badgeFormat(d.status)}</td>
                </tr>
            `;
        });
    }

    // 7. TRIPS TABLE & LIVE SEARCH
    async function loadTrips() {
        const data = await apiFetch('trips.php');
        if(!data) return;
        allTrips = data;
        renderTrips();
    }

    function setTripFilter(filter, el) {
        tripFilter = filter;
        document.querySelectorAll('#table-trips-wrap .filter-pill').forEach(p => p.classList.remove('active'));
        if (el) el.classList.add('active');
        renderTrips();
    }

    function filterTrips() {
        renderTrips();
    }

    function renderTrips() {
        const tbody = document.getElementById('table-trips');
        if (!tbody) return;
        const q = (document.getElementById('search-trips')?.value || '').toLowerCase();

        const filtered = allTrips.filter(t => {
            const matchFilter = (tripFilter === 'all') ||
                (tripFilter === 'in_progress' && t.status === 'in_progress') ||
                (tripFilter === 'scheduled' && t.status === 'scheduled');

            const matchSearch = !q ||
                t.license_plate.toLowerCase().includes(q) ||
                t.driver_name.toLowerCase().includes(q) ||
                t.destination.toLowerCase().includes(q) ||
                (t.client_name && t.client_name.toLowerCase().includes(q));

            return matchFilter && matchSearch;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-route"></i><h6>No route dispatches found</h6><small>Clear search or dispatch a new manifest.</small></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(t => {
            tbody.innerHTML += `
                <tr>
                    <td><span class="badge bg-dark bg-opacity-10 text-main border border-secondary mb-1">${t.license_plate}</span><br><small class="text-muted">${t.make} ${t.model}</small></td>
                    <td class="fw-bold">${t.driver_name}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <span class="fw-semibold text-main">${t.origin}</span> 
                            <i class="fa-solid fa-arrow-right opacity-50"></i> 
                            <span class="fw-semibold text-main">${t.destination}</span>
                        </div>
                        <small class="text-muted">${t.client_name || 'Retail Partner'}</small>
                    </td>
                    <td>${badgeFormat(t.status)}</td>
                    <td class="text-muted small"><i class="fa-regular fa-clock me-1 opacity-50"></i> ${t.start_time || 'Scheduled'}</td>
                    <td class="text-end pe-4">
                        ${t.status === 'scheduled' 
                            ? `<button class="btn btn-sm btn-outline-primary" onclick="quickStartTrip(${t.id})">Start Route</button>`
                            : `<span class="badge bg-light text-muted border">In Progress</span>`
                        }
                    </td>
                </tr>
            `;
        });
    }

    async function quickStartTrip(id) {
        if (!confirm('Authorize route departure now?')) return;
        await fetch('../api/trips.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: 'in_progress' })
        });
        showToast('Route departed from Matsapha DC!', 'success');
        loadTrips();
        loadStats();
    }

    // 8. MAINTENANCE TABLE & SEARCH
    async function loadMaintenance() {
        const data = await apiFetch('maintenance.php');
        if(!data) return;
        allMaintenance = data;
        renderMaintenance();
    }

    function filterMaintenance() {
        renderMaintenance();
    }

    function renderMaintenance() {
        const tbody = document.getElementById('table-maintenance');
        if (!tbody) return;
        const q = (document.getElementById('search-maintenance')?.value || '').toLowerCase();

        const filtered = allMaintenance.filter(m => {
            return !q ||
                m.license_plate.toLowerCase().includes(q) ||
                m.service_type.toLowerCase().includes(q) ||
                (m.description && m.description.toLowerCase().includes(q));
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fa-solid fa-wrench"></i><h6>No service records found</h6></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(m => {
            tbody.innerHTML += `
                <tr>
                    <td class="text-muted"><i class="fa-regular fa-calendar me-1 opacity-50"></i> ${m.service_date}</td>
                    <td><span class="badge bg-dark bg-opacity-10 text-main border border-secondary me-2">${m.license_plate}</span> <span class="text-muted">${m.make}</span></td>
                    <td class="fw-bold">${m.service_type}</td>
                    <td class="text-danger fw-semibold">E ${parseFloat(m.cost).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                    <td class="text-muted">${m.description}</td>
                </tr>
            `;
        });
    }

    // 9. FUEL TABLE & SEARCH
    async function loadFuel() {
        const data = await apiFetch('fuel.php');
        if(!data) return;
        allFuel = data;
        renderFuel();
    }

    function filterFuel() {
        renderFuel();
    }

    function renderFuel() {
        const tbody = document.getElementById('table-fuel');
        if (!tbody) return;
        const q = (document.getElementById('search-fuel')?.value || '').toLowerCase();

        const filtered = allFuel.filter(f => {
            return !q ||
                f.license_plate.toLowerCase().includes(q) ||
                f.driver_name.toLowerCase().includes(q);
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><i class="fa-solid fa-gas-pump"></i><h6>No fuel records found</h6></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(f => {
            tbody.innerHTML += `
                <tr>
                    <td class="text-muted"><i class="fa-regular fa-calendar me-1 opacity-50"></i> ${f.refuel_date}</td>
                    <td><span class="badge bg-dark bg-opacity-10 text-main border border-secondary me-2">${f.license_plate}</span> <span class="text-muted">${f.make}</span></td>
                    <td class="fw-bold">${f.driver_name}</td>
                    <td class="fw-semibold"><i class="fa-solid fa-droplet text-primary opacity-50 me-1"></i> ${f.liters} L</td>
                    <td class="text-danger fw-semibold">E ${parseFloat(f.cost).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                </tr>
            `;
        });
    }

    // =========================================================================
    // MODAL FORM SUBMISSIONS WITH TOAST NOTIFICATIONS
    // =========================================================================

    // Submit Vehicle
    document.getElementById('formVehicle').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            license_plate: document.getElementById('v_plate').value,
            make: document.getElementById('v_make').value,
            model: document.getElementById('v_model').value,
            year: document.getElementById('v_year').value,
            type: document.getElementById('v_type').value,
            odometer: document.getElementById('v_odo').value,
            cold_chain_capable: document.getElementById('v_cold_chain').checked ? 1 : 0
        };

        const res = await fetch('../api/vehicles.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            showToast(`Asset ${payload.license_plate} registered successfully!`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modal-vehicle')).hide();
            document.getElementById('formVehicle').reset();
            loadVehicles();
            loadStats();
        } else {
            showToast(data.error || 'Failed to add vehicle', 'error');
        }
    });

    // Submit Driver
    document.getElementById('formDriver').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            name: document.getElementById('d_name').value,
            email: document.getElementById('d_email').value,
            phone: document.getElementById('d_phone').value,
            password: document.getElementById('d_pass').value,
            license_number: document.getElementById('d_license').value,
            license_expiry: document.getElementById('d_expiry').value
        };

        const res = await fetch('../api/drivers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            showToast(`Driver account for ${payload.name} created!`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modal-driver')).hide();
            document.getElementById('formDriver').reset();
            loadDrivers();
            loadStats();
        } else {
            showToast(data.error || 'Failed to add driver', 'error');
        }
    });

    // Submit Trip Dispatch
    document.getElementById('formTrip').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            vehicle_id: document.getElementById('t_vehicle').value,
            driver_id: document.getElementById('t_driver').value,
            consignment_no: document.getElementById('t_consignment').value,
            client_name: document.getElementById('t_client').value,
            cargo_type: document.getElementById('t_cargo').value,
            origin: document.getElementById('t_origin').value,
            destination: document.getElementById('t_destination').value,
            status: document.getElementById('t_status').value
        };

        const res = await fetch('../api/trips.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            showToast(`Manifest ${data.consignment_no || ''} authorized & dispatched!`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modal-trip')).hide();
            document.getElementById('formTrip').reset();
            loadTrips();
            loadConsignments();
            loadStats();
        } else {
            showToast(data.error || 'Failed to dispatch', 'error');
        }
    });

    // Submit Maintenance
    document.getElementById('formMaintenance').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            vehicle_id: document.getElementById('m_vehicle').value,
            service_date: document.getElementById('m_date').value,
            service_type: document.getElementById('m_type').value,
            cost: document.getElementById('m_cost').value,
            odometer_reading: document.getElementById('m_odo').value,
            description: document.getElementById('m_desc').value
        };

        const res = await fetch('../api/maintenance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            showToast('Workshop maintenance record saved!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modal-maintenance')).hide();
            document.getElementById('formMaintenance').reset();
            loadMaintenance();
            loadStats();
        } else {
            showToast(data.error || 'Failed to save', 'error');
        }
    });

    // Submit Fuel
    document.getElementById('formFuel').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            vehicle_id: document.getElementById('f_vehicle').value,
            driver_id: document.getElementById('f_driver').value,
            liters: document.getElementById('f_liters').value,
            cost: document.getElementById('f_cost').value,
            odometer_reading: document.getElementById('f_odo').value,
            new_fuel_level: document.getElementById('f_level').value
        };

        const res = await fetch('../api/fuel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            showToast(`Fuel transaction of ${payload.liters} L logged!`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modal-fuel')).hide();
            document.getElementById('formFuel').reset();
            loadFuel();
            loadStats();
        } else {
            showToast(data.error || 'Failed to save', 'error');
        }
    });

    function printReport() {
        window.print();
    }

    // Initial Load
    window.addEventListener('DOMContentLoaded', () => { 
        loadStats(); 
        loadVehicles();
        loadDrivers();
    });
    </script>
</body>
</html>
