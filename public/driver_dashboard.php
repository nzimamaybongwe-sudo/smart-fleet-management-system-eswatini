<?php
// public/driver_dashboard.php - Enterprise Driver Operations Portal for Logico Fleet
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (!in_array('driver', $_SESSION['roles'] ?? [])) {
    redirect('dashboard.php');
}
$user = current_user();

// Fetch driver details
$stmtDrv = $pdo->prepare('SELECT d.*, v.license_plate, v.make, v.model, v.id as assigned_vehicle_id, v.odometer as current_odo
                          FROM drivers d 
                          LEFT JOIN vehicles v ON 1=1 AND v.status = "active"
                          WHERE d.user_id = ? LIMIT 1');
$stmtDrv->execute([$user['id']]);
$driverProfile = $stmtDrv->fetch();

// Fetch vehicles list for fuel refill dropdown
$stmtVehList = $pdo->query('SELECT id, license_plate, make, model, odometer FROM vehicles ORDER BY license_plate ASC');
$vehicles = $stmtVehList->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver Operations — Logico Fleet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; padding-bottom: 90px; }
        .driver-header { background: linear-gradient(135deg, #0284c7, #1e3a8a); color: white; padding: 2rem 1rem 3rem 1rem; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; box-shadow: 0 6px 20px rgba(2,132,199,0.25); }
        .driver-header h3 { margin: 0; font-weight: 700; font-family: 'Outfit'; }
        .main-card { margin-top: -35px; border-radius: 18px; border: 1px solid var(--border-color); box-shadow: var(--shadow-hover); background: var(--bg-card); z-index: 10; position: relative; }
        .action-btn { height: 95px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 16px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; border: 1px solid var(--border-color); background: var(--bg-card); }
        .action-btn i { font-size: 1.8rem; margin-bottom: 6px; }
        .action-btn:active { transform: scale(0.96); }
        .btn-fuel { color: #0284c7; background: rgba(2, 132, 199, 0.08); }
        .btn-insp { color: #10b981; background: rgba(16, 185, 129, 0.08); }
        .btn-incident { color: #ef4444; background: rgba(239, 68, 68, 0.08); }
        
        .signature-baseline-guide {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            border-bottom: 1px dashed #cbd5e1;
            text-align: right;
            font-size: 0.7rem;
            color: #94a3b8;
            pointer-events: none;
        }

        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: var(--bg-card); box-shadow: 0 -4px 20px rgba(0,0,0,0.06); display: flex; justify-content: space-around; padding: 12px 0; z-index: 1000; border-top: 1px solid var(--border-color); backdrop-filter: blur(12px); }
        .nav-item { text-align: center; color: var(--text-muted); text-decoration: none; font-size: 0.78rem; font-weight: 600; }
        .nav-item i { display: block; font-size: 1.25rem; margin-bottom: 2px; }
        .nav-item.active { color: var(--primary); }
    </style>
</head>
<body>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toast-container"></div>

    <!-- Header -->
    <div class="driver-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-white text-primary fw-bold px-2 py-1">LOGICO FLEET</span>
                    <small class="opacity-75">Matsapha Depot</small>
                </div>
                <h3>Sawubona, <?php echo explode(' ', $user['name'])[0]; ?></h3>
                <small class="opacity-75">License: <?php echo e($driverProfile['license_number'] ?? 'DL-ESW-VALID'); ?></small>
            </div>
            <a href="profile_edit.php" class="text-decoration-none">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow" style="width: 52px; height: 52px; font-family: 'Outfit';">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
            </a>
        </div>
    </div>

    <div class="container px-3">
        <!-- Current Assignment / Manifest Card -->
        <div class="card main-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-uppercase text-muted fw-bold m-0" style="font-size: 0.75rem; letter-spacing: 0.08em;">Active Manifest & Dispatch</h6>
                <button class="btn btn-sm btn-outline-secondary border-0 p-1" onclick="loadDriverTrip()" title="Refresh"><i class="fa-solid fa-rotate-right"></i></button>
            </div>
            
            <div id="trip-container">
                <div class="text-center p-4 text-muted">
                    <i class="fa-solid fa-spinner fa-spin fs-2 mb-2 text-primary"></i>
                    <p class="mb-0">Loading your assignment from dispatch...</p>
                </div>
            </div>
        </div>

        <!-- Operational Quick Actions -->
        <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.08em;">Driver Operations</h6>
        <div class="row g-3 mb-4">
            <div class="col-4">
                <button class="action-btn btn-insp w-100" data-bs-toggle="modal" data-bs-target="#modal-inspection">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Pre-Trip</span>
                </button>
            </div>
            <div class="col-4">
                <button class="action-btn btn-fuel w-100" data-bs-toggle="modal" data-bs-target="#modal-fuel">
                    <i class="fa-solid fa-gas-pump"></i>
                    <span>Log Fuel</span>
                </button>
            </div>
            <div class="col-4">
                <a href="incident_report.php" class="action-btn btn-incident w-100">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Incident</span>
                </a>
            </div>
        </div>

        <!-- Emergency Dispatch Speed Dial -->
        <div class="glass-card p-3 mb-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                    <i class="fa-solid fa-headset fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold small">Matsapha Central Dispatch</div>
                    <small class="text-muted">Emergency & Road Assistance</small>
                </div>
            </div>
            <a href="tel:+26876000000" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-phone me-1"></i> Call Radio
            </a>
        </div>

        <!-- Recent Trips History -->
        <div class="glass-card p-3 mb-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Deliveries</h6>
            <div id="past-trips-container" class="small text-muted">
                Loading history...
            </div>
        </div>
    </div>

    <!-- PRE-TRIP INSPECTION MODAL -->
    <div class="modal fade" id="modal-inspection" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-clipboard-check me-2"></i>Pre-Trip Safety & Cold Chain Checklist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="inspectionForm">
                    <div class="modal-body p-4">
                        <p class="small text-muted mb-3">Mandatory fleet safety compliance before departing Matsapha Distribution Centre.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <select name="vehicle_id" id="insp_vehicle_id" class="form-select" required>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?php echo $v['id']; ?>"><?php echo e($v['license_plate'] . ' — ' . $v['make'] . ' ' . $v['model']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Odometer (KM)</label>
                            <input type="number" name="odometer_reading" id="insp_odometer" class="form-control" value="<?php echo $vehicles[0]['odometer'] ?? 68400; ?>" required>
                        </div>

                        <h6 class="fw-bold mt-4 mb-2 small text-uppercase text-muted">Safety Verifications</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="check_tires" checked>
                            <label class="form-check-label" for="check_tires">Tires & Pressure (Tread, spare wheel)</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="check_brakes" checked>
                            <label class="form-check-label" for="check_brakes">Braking System & Air Pressure Tanks</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="check_lights" checked>
                            <label class="form-check-label" for="check_lights">Headlights, Indicators & Brake Lights</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="check_fluids" checked>
                            <label class="form-check-label" for="check_fluids">Engine Oil, Coolant & Windshield Washer</label>
                        </div>

                        <h6 class="fw-bold mt-4 mb-2 small text-uppercase text-muted">Cold Chain Verification</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small">Reefer Compartment Temp (°C)</label>
                                <input type="number" step="0.1" id="insp_reefer_temp" class="form-control" placeholder="e.g. 3.5 or -18.0" value="3.8">
                            </div>
                            <div class="col-4 d-flex align-items-end">
                                <span class="badge bg-info bg-opacity-10 text-info border p-2 w-100 text-center">FMCG Grade</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4"><i class="fa-solid fa-check me-1"></i> Submit Inspection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LOG FUEL MODAL -->
    <div class="modal fade" id="modal-fuel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-gas-pump me-2"></i>Log Fuel Refill</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="fuelForm">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vehicle</label>
                            <select name="vehicle_id" id="fuel_vehicle_id" class="form-select" required>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?php echo $v['id']; ?>"><?php echo e($v['license_plate'] . ' — ' . $v['make'] . ' ' . $v['model']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Volume (Liters)</label>
                                <input type="number" step="0.01" id="fuel_liters" class="form-control" placeholder="e.g. 180" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Total Cost (E / SZL)</label>
                                <input type="number" step="0.01" id="fuel_cost" class="form-control" placeholder="e.g. 4250.00" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Odometer Reading (KM)</label>
                                <input type="number" id="fuel_odometer" class="form-control" value="68400" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Tank Level (%)</label>
                                <input type="number" id="fuel_level" class="form-control" value="100" min="1" max="100" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fa-solid fa-save me-1"></i> Save Fuel Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DIGITAL PROOF OF DELIVERY (e-POD) MODAL -->
    <div class="modal fade" id="modal-epod" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-signature me-2"></i>Electronic Proof of Delivery (e-POD)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="epodForm">
                    <input type="hidden" id="epod_trip_id">
                    <div class="modal-body p-4">
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="fw-bold fs-6" id="epod_destination">Shoprite Hyper, Mbabane</div>
                            <small class="text-muted" id="epod_consignment">Consignment: LOG-ESW-84920</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Store Receiving Manager / Customer Name</label>
                            <input type="text" id="epod_recipient" class="form-control" placeholder="e.g. Sipho Dlamini (Receiving Manager)" required>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold m-0">Store Manager Digital Signature</label>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" onclick="clearSignature()">Clear Signature</button>
                            </div>
                            <div class="sig-canvas-wrapper position-relative">
                                <canvas id="signatureCanvas"></canvas>
                                <div class="signature-baseline-guide">Sign above this line ✍️</div>
                            </div>
                            <small class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-hand-pointer me-1"></i>Draw signature with finger or stylus</small>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Ending Odometer (KM)</label>
                                <input type="number" id="epod_end_odo" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fuel Level (%)</label>
                                <input type="number" id="epod_fuel_level" class="form-control" value="85" min="1" max="100" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Delivery Inspection Notes</label>
                            <textarea id="epod_notes" class="form-control" rows="2" placeholder="e.g. All 140 crates received cold & sealed. Temp checked at 3.8°C."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success fw-bold px-4"><i class="fa-solid fa-stamp me-1"></i> Confirm & Seal e-POD</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-link nav-item active">
            <i class="fa-solid fa-truck"></i> Operations
        </a>
        <a href="profile_edit.php" class="nav-link nav-item">
            <i class="fa-solid fa-user"></i> Profile
        </a>
        <a href="logout.php" class="nav-link nav-item text-danger">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // TOAST HELPER
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

    let currentActiveTrip = null;

    async function loadDriverTrip() {
        const tripContainer = document.getElementById('trip-container');
        const pastContainer = document.getElementById('past-trips-container');

        try {
            const res = await fetch('../api/trips.php?my_trips=1');
            const data = await res.json();
            
            if (!res.ok) throw new Error(data.error || 'Failed to load');

            currentActiveTrip = data.active_trip;

            if (!currentActiveTrip) {
                tripContainer.innerHTML = `
                    <div class="text-center py-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex p-3 mb-3">
                            <i class="fa-solid fa-circle-check fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-1">All Deliveries Complete</h5>
                        <p class="text-muted small mb-3">You currently have no pending trips. You are in standby at Matsapha Central Depot.</p>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="loadDriverTrip()"><i class="fa-solid fa-rotate-right me-1"></i> Check New Dispatches</button>
                    </div>
                `;
            } else {
                const isCold = currentActiveTrip.cold_chain_capable == 1;
                const tempDisplay = isCold ? `<span class="badge badge-cold ms-2"><i class="fa-solid fa-snowflake"></i> ${currentActiveTrip.temp_celsius || '3.8'}°C</span>` : '';
                const isInProgress = currentActiveTrip.status === 'in_progress';
                const statusBadge = isInProgress
                    ? `<span class="badge bg-warning text-dark"><i class="fa-solid fa-truck-moving me-1"></i> In Transit</span>`
                    : `<span class="badge bg-info text-dark"><i class="fa-solid fa-calendar me-1"></i> Scheduled</span>`;

                const progressWidth = isInProgress ? '66%' : '33%';

                tripContainer.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                <i class="fa-solid fa-file-invoice fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">${currentActiveTrip.consignment_no || 'LOG-ESW-' + currentActiveTrip.id}</h6>
                                <small class="text-muted">${currentActiveTrip.cargo_type || 'FMCG Distribution'}</small>
                            </div>
                        </div>
                        <div>${statusBadge}${tempDisplay}</div>
                    </div>

                    <!-- ROUTE STEPPER -->
                    <div class="route-stepper">
                        <div class="stepper-progress" style="width: ${progressWidth};"></div>
                        <div class="step-node completed">
                            <div class="step-circle"><i class="fa-solid fa-clipboard-check"></i></div>
                            <span>Inspected</span>
                        </div>
                        <div class="step-node ${isInProgress ? 'completed' : 'active'}">
                            <div class="step-circle"><i class="fa-solid fa-warehouse"></i></div>
                            <span>Departed</span>
                        </div>
                        <div class="step-node ${isInProgress ? 'active' : ''}">
                            <div class="step-circle"><i class="fa-solid fa-truck-moving"></i></div>
                            <span>In Transit</span>
                        </div>
                        <div class="step-node">
                            <div class="step-circle"><i class="fa-solid fa-signature"></i></div>
                            <span>e-POD</span>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Vehicle</small>
                                <span class="fw-bold text-primary">${currentActiveTrip.license_plate}</span>
                                <small class="d-block text-muted" style="font-size:0.75rem;">${currentActiveTrip.make} ${currentActiveTrip.model}</small>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted d-block">Store / Client</small>
                                <span class="fw-bold">${currentActiveTrip.client_name || 'Retail Partner'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 border-start border-3 border-primary ms-3 ps-3 py-1 mb-4 position-relative">
                        <div class="position-absolute top-0 start-0 translate-middle p-1 bg-primary rounded-circle"></div>
                        <div class="position-absolute bottom-0 start-0 translate-middle p-1 bg-success rounded-circle"></div>
                        <div>
                            <small class="text-muted d-block" style="font-size:0.75rem;">ORIGIN</small>
                            <span class="fw-semibold">${currentActiveTrip.origin}</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted d-block" style="font-size:0.75rem;">DESTINATION</small>
                            <span class="fw-semibold text-primary">${currentActiveTrip.destination}</span>
                        </div>
                    </div>

                    ${currentActiveTrip.status === 'scheduled' 
                        ? `<button class="btn btn-primary w-100 py-3 fw-bold shadow-sm" onclick="startTrip(${currentActiveTrip.id})">
                               <i class="fa-solid fa-play me-2"></i> Start Delivery Route
                           </button>`
                        : `<button class="btn btn-success w-100 py-3 fw-bold shadow-sm" onclick="openEpodModal()">
                               <i class="fa-solid fa-signature me-2"></i> Arrived at Store — Complete e-POD
                           </button>`
                    }
                `;
            }

            // Past trips list
            if (data.past_trips && data.past_trips.length > 0) {
                let html = '<div class="list-group list-group-flush">';
                data.past_trips.forEach(t => {
                    html += `
                        <div class="list-group-item bg-transparent px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-semibold text-dark">${t.destination}</span>
                                    <small class="d-block text-muted">${t.consignment_no || 'Trip #' + t.id} • ${t.license_plate}</small>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Delivered</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                pastContainer.innerHTML = html;
            } else {
                pastContainer.innerHTML = '<p class="m-0 text-muted">No completed deliveries yet today.</p>';
            }

        } catch (err) {
            tripContainer.innerHTML = `<div class="alert alert-danger py-2">Error: ${err.message}</div>`;
        }
    }

    async function startTrip(id) {
        if (!confirm('Start route departure from Matsapha Central Depot?')) return;
        try {
            const res = await fetch('../api/trips.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: 'in_progress' })
            });
            const data = await res.json();
            if (res.ok) {
                showToast('Route departed from Matsapha DC!', 'success');
                loadDriverTrip();
            } else {
                showToast(data.error || 'Failed to start trip', 'error');
            }
        } catch (e) {
            showToast('Network error starting route', 'error');
        }
    }

    function openEpodModal() {
        if (!currentActiveTrip) return;
        document.getElementById('epod_trip_id').value = currentActiveTrip.id;
        document.getElementById('epod_destination').innerText = currentActiveTrip.destination;
        document.getElementById('epod_consignment').innerText = 'Consignment: ' + (currentActiveTrip.consignment_no || 'LOG-ESW-' + currentActiveTrip.id);
        document.getElementById('epod_end_odo').value = (parseInt(currentActiveTrip.start_odometer) || 68350) + 45;
        
        const modal = new bootstrap.Modal(document.getElementById('modal-epod'));
        modal.show();
        setTimeout(initSignaturePad, 300);
    }

    // SIGNATURE CANVAS LOGIC
    let canvas, ctx, isDrawing = false;
    function initSignaturePad() {
        canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        ctx = canvas.getContext('2d');
        canvas.width = canvas.parentElement.clientWidth;
        canvas.height = 180;
        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        canvas.onmousedown = (e) => { isDrawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
        canvas.onmousemove = (e) => { if (isDrawing) { const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); } };
        window.onmouseup = () => { isDrawing = false; };

        canvas.ontouchstart = (e) => { e.preventDefault(); isDrawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
        canvas.ontouchmove = (e) => { e.preventDefault(); if (isDrawing) { const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); } };
        canvas.ontouchend = () => { isDrawing = false; };
    }

    function clearSignature() {
        if (ctx && canvas) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    // SUBMIT e-POD
    document.getElementById('epodForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const tripId = document.getElementById('epod_trip_id').value;
        const recipient = document.getElementById('epod_recipient').value;
        const endOdo = document.getElementById('epod_end_odo').value;
        const endFuel = document.getElementById('epod_fuel_level').value;
        const notes = document.getElementById('epod_notes').value;
        const sigData = canvas ? canvas.toDataURL() : '';

        try {
            const res = await fetch('../api/trips.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: tripId,
                    status: 'completed',
                    end_odometer: endOdo,
                    end_fuel_level: endFuel,
                    pod_signature: sigData,
                    pod_recipient: recipient,
                    pod_notes: notes
                })
            });
            const data = await res.json();
            if (res.ok) {
                showToast('e-POD Sealed & Delivery Confirmed!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modal-epod')).hide();
                loadDriverTrip();
            } else {
                showToast(data.error || 'Failed to complete e-POD', 'error');
            }
        } catch (e) {
            showToast('Network error submitting e-POD', 'error');
        }
    });

    // SUBMIT INSPECTION
    document.getElementById('inspectionForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            vehicle_id: document.getElementById('insp_vehicle_id').value,
            odometer_reading: document.getElementById('insp_odometer').value,
            tires_passed: document.getElementById('check_tires').checked ? 1 : 0,
            brakes_passed: document.getElementById('check_brakes').checked ? 1 : 0,
            lights_passed: document.getElementById('check_lights').checked ? 1 : 0,
            fluids_passed: document.getElementById('check_fluids').checked ? 1 : 0,
            reefer_temp_checked: 1,
            recorded_temp: document.getElementById('insp_reefer_temp').value,
            notes: 'Mobile driver pre-trip submission'
        };

        try {
            const res = await fetch('../api/inspections.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                showToast(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modal-inspection')).hide();
                loadDriverTrip();
            } else {
                showToast(data.error || 'Inspection failed', 'error');
            }
        } catch (err) {
            showToast('Error submitting inspection', 'error');
        }
    });

    // SUBMIT FUEL
    document.getElementById('fuelForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const payload = {
            vehicle_id: document.getElementById('fuel_vehicle_id').value,
            liters: document.getElementById('fuel_liters').value,
            cost: document.getElementById('fuel_cost').value,
            odometer_reading: document.getElementById('fuel_odometer').value,
            new_fuel_level: document.getElementById('fuel_level').value
        };

        try {
            const res = await fetch('../api/fuel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                showToast('Fuel refill recorded successfully in Emalangeni!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modal-fuel')).hide();
                document.getElementById('fuelForm').reset();
            } else {
                showToast(data.error || 'Failed to log fuel', 'error');
            }
        } catch (err) {
            showToast('Network error logging fuel', 'error');
        }
    });

    // Initial Load
    document.addEventListener('DOMContentLoaded', loadDriverTrip);
    </script>
</body>
</html>
