<?php
// public/incident_report.php - Driver incident & road breakdown reporting
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = current_user();

// Fetch vehicles for dropdown
$stmtVeh = $pdo->query('SELECT id, license_plate, make, model FROM vehicles ORDER BY license_plate ASC');
$vehicles = $stmtVeh->fetchAll();

// Check if driver has an active assigned vehicle
$stmtDriverVeh = $pdo->prepare('SELECT v.id FROM trips t 
                                JOIN drivers d ON t.driver_id = d.id 
                                JOIN vehicles v ON t.vehicle_id = v.id 
                                WHERE d.user_id = ? AND t.status = "in_progress" LIMIT 1');
$stmtDriverVeh->execute([$_SESSION['user_id']]);
$assignedVehId = (int)$stmtDriverVeh->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Report Incident / Road Breakdown — Logico Fleet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; padding-bottom: 70px; }
        .page-header { background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; padding: 1.75rem 1rem 2.5rem 1rem; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 0 4px 15px rgba(239,68,68,0.25); }
        .form-card { margin-top: -25px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--bg-card); box-shadow: var(--shadow-card); }
        .quick-btn { font-size: 0.75rem; border-radius: 20px; padding: 4px 10px; border: 1px solid var(--border-color); background: var(--bg-main); color: var(--text-muted); cursor: pointer; transition: all 0.2s; }
        .quick-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toast-container"></div>

    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="driver_dashboard.php" class="btn btn-sm btn-light rounded-circle shadow-sm" style="width:36px; height:36px; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-arrow-left text-danger"></i>
                </a>
                <div>
                    <h4 class="m-0 fw-bold">Report Incident</h4>
                    <small class="opacity-75">Logico Rapid Response Dispatch</small>
                </div>
            </div>
            <i class="fa-solid fa-triangle-exclamation fs-2 opacity-50"></i>
        </div>
    </div>

    <div class="container px-3 mt-2">
        <div class="card form-card p-4">
            <form id="incidentForm">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Vehicle</label>
                    <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo ($assignedVehId === (int)$v['id']) ? 'selected' : ''; ?>>
                                <?php echo e($v['license_plate'] . ' — ' . $v['make'] . ' ' . $v['model']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Incident Category</label>
                    <select name="incident_type" id="incident_type" class="form-select" required>
                        <option value="breakdown">Mechanical Breakdown / Engine Fault</option>
                        <option value="puncture">Tire Puncture / Blowout</option>
                        <option value="cold_chain_breach">Cold Chain Temperature Breach</option>
                        <option value="accident">Road Collision / Accident</option>
                        <option value="delay">Border / Traffic Road Delay</option>
                        <option value="other">Other Roadside Emergency</option>
                    </select>
                </div>

                <!-- Visual Severity Cards -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Urgency & Severity Level</label>
                    <input type="hidden" name="severity" id="severity" value="medium">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="severity-card" id="sev-low" onclick="setSeverity('low')">
                                <i class="fa-solid fa-circle-info text-info mb-1 fs-5"></i>
                                <div class="small fw-bold">Low</div>
                                <small class="text-muted" style="font-size:0.68rem;">Non-Disruptive</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="severity-card selected" id="sev-medium" onclick="setSeverity('medium')">
                                <i class="fa-solid fa-triangle-exclamation text-warning mb-1 fs-5"></i>
                                <div class="small fw-bold">Medium</div>
                                <small class="text-muted" style="font-size:0.68rem;">Need Support</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="severity-card sev-critical" id="sev-critical" onclick="setSeverity('critical')">
                                <i class="fa-solid fa-skull-crossbones text-danger mb-1 fs-5"></i>
                                <div class="small fw-bold">Critical</div>
                                <small class="text-muted" style="font-size:0.68rem;">Immobilized</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold m-0">Current Location / Landmark</label>
                        <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" onclick="detectGpsLocation()">
                            <i class="fa-solid fa-crosshairs me-1"></i> Detect My GPS
                        </button>
                    </div>
                    <input type="text" name="location" id="location" class="form-control" placeholder="e.g. MR3 Highway near Malkerns Turnoff" required>
                    <div class="mt-2 d-flex flex-wrap gap-1">
                        <span class="quick-btn" onclick="setLocation('MR3 Highway — Malkerns Turnoff')">MR3 Malkerns</span>
                        <span class="quick-btn" onclick="setLocation('Mbabane Hill Climb / Malagwane')">Malagwane Hill</span>
                        <span class="quick-btn" onclick="setLocation('Matsapha Central Industrial DC')">Matsapha Hub</span>
                        <span class="quick-btn" onclick="setLocation('Manzini City Centre / Wholesalers')">Manzini Hub</span>
                        <span class="quick-btn" onclick="setLocation('Ngwenya / Oshoek Border Post')">Ngwenya Border</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Situation Details & Assistance Needed</label>
                    <textarea name="description" id="description" rows="4" class="form-control" placeholder="Describe what happened, current vehicle status, and whether a mobile mechanic, spare wheel, or towing unit is required..." required></textarea>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-danger w-100 py-3 fw-bold shadow-sm">
                    <i class="fa-solid fa-paper-plane me-2"></i> Transmit Urgent Incident Report
                </button>
            </form>
        </div>
    </div>

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

    function setLocation(loc) {
        document.getElementById('location').value = loc;
    }

    function setSeverity(sev) {
        document.getElementById('severity').value = sev;
        document.querySelectorAll('.severity-card').forEach(c => c.classList.remove('selected'));
        const el = document.getElementById('sev-' + sev);
        if (el) el.classList.add('selected');
    }

    function detectGpsLocation() {
        if (!navigator.geolocation) {
            showToast('Geolocation is not supported by your browser', 'warning');
            return;
        }
        showToast('Acquiring GPS coordinates...', 'info');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude.toFixed(4);
                const lng = pos.coords.longitude.toFixed(4);
                document.getElementById('location').value = `GPS: ${lat}, ${lng} (Near Matsapha Corridor)`;
                showToast('GPS position locked!', 'success');
            },
            (err) => {
                showToast('Unable to retrieve GPS. Please select a landmark above.', 'warning');
            },
            { timeout: 7000 }
        );
    }

    document.getElementById('incidentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Transmitting to Dispatch...';

        const payload = {
            vehicle_id: document.getElementById('vehicle_id').value,
            incident_type: document.getElementById('incident_type').value,
            severity: document.getElementById('severity').value,
            location: document.getElementById('location').value,
            description: document.getElementById('description').value
        };

        try {
            const res = await fetch('../api/incidents.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (res.ok) {
                showToast(`Incident #${data.id} Logged! Dispatch alerted.`, 'success');
                document.getElementById('incidentForm').reset();
                setTimeout(() => {
                    window.location.href = 'driver_dashboard.php';
                }, 1500);
            } else {
                showToast(data.error || 'Failed to submit report', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> Transmit Urgent Incident Report';
            }
        } catch (err) {
            showToast('Network error. Please call dispatcher radio.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> Transmit Urgent Incident Report';
        }
    });
    </script>
</body>
</html>
