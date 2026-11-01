<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = current_user();

$roles = $_SESSION['roles'] ?? [];
$is_driver = in_array('driver', $roles);
$back_link = $is_driver ? 'driver_dashboard.php' : 'dashboard.php';

$driver_info = null;
if ($is_driver) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $driver_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $driver_info = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Account Profile & Security | Logico Fleet Operations</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: var(--font-body);
            min-height: 100vh;
        }
        .profile-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }
        .top-navbar {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-color);
            padding: 0.85rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .nav-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            transition: var(--transition);
        }
        .nav-back-btn:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }
        .avatar-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            font-family: var(--font-title);
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
            margin: 0 auto 1rem;
            border: 4px solid var(--bg-card);
        }
        .credential-pill {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 0.85rem 1.25rem;
        }
        .theme-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .theme-toggle-btn:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <!-- Sticky Top Navigation -->
    <header class="top-navbar shadow-sm">
        <div class="container-fluid d-flex align-items-center justify-content-between px-0">
            <a href="<?php echo htmlspecialchars($back_link); ?>" class="nav-back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                <span class="d-none d-sm-inline">Back to Dashboard</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold">
                    <i class="fa-solid fa-building-circle-check me-1"></i> Logico Eswatini
                </span>
                <button type="button" class="theme-toggle-btn" id="themeSwitchBtn" title="Toggle Light/Dark Theme">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="profile-container">
        <!-- Profile Identity Card -->
        <div class="glass-card text-center mb-4 p-4 position-relative overflow-hidden">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
            </div>

            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2 flex-wrap">
                <span class="text-muted small"><i class="fa-solid fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?></span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                    <i class="fa-solid fa-circle-check me-1"></i>Account Active
                </span>
            </div>

            <div class="d-flex justify-content-center gap-1 mt-2">
                <?php foreach ($roles as $r): ?>
                    <span class="badge bg-primary text-uppercase px-3 py-1 rounded-pill fw-semibold" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                        <i class="fa-solid fa-shield me-1"></i><?php echo htmlspecialchars(str_replace('_', ' ', $r)); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($is_driver && $driver_info): ?>
        <!-- Commercial Driver Credentials Banner -->
        <div class="glass-card mb-4 border-start border-primary border-4 p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold m-0 text-primary">
                    <i class="fa-solid fa-id-card me-2"></i>Eswatini Commercial Driver's License
                </h6>
                <span class="badge <?php echo ($driver_info['status'] === 'active' || $driver_info['status'] === 'on_trip') ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3 py-1 text-uppercase">
                    <?php echo htmlspecialchars($driver_info['status'] ?? 'Active'); ?>
                </span>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="credential-pill">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">License Number</small>
                        <span class="fw-bold font-monospace fs-6 text-main">
                            <i class="fa-solid fa-fingerprint text-primary me-1"></i>
                            <?php echo htmlspecialchars($driver_info['license_number'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="credential-pill">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.7rem;">Expiration Date</small>
                        <span class="fw-bold fs-6 text-main">
                            <i class="fa-regular fa-calendar-check text-success me-1"></i>
                            <?php echo htmlspecialchars($driver_info['license_expiry'] ?? 'Valid'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Profile & Security Settings Card -->
        <div class="glass-card p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="p-2 bg-primary-subtle text-primary rounded-3">
                    <i class="fa-solid fa-user-pen fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">Profile & Security Settings</h5>
                    <small class="text-muted">Update your contact information and access credentials</small>
                </div>
            </div>

            <form id="profileForm" novalidate>
                <!-- Email (Read Only) -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Logico Enterprise Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                        <input type="email" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled>
                    </div>
                    <small class="form-text text-muted" style="font-size: 0.75rem;">Company login emails are managed by Logico IT Operations.</small>
                </div>

                <!-- Phone Number -->
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold text-uppercase" for="phone">Mobile / Contact Phone</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-phone text-primary"></i></span>
                        <input type="tel" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+268 7600 0000 or 00268...">
                    </div>
                    <small class="form-text text-muted" style="font-size: 0.75rem;">Used for roadside emergency dispatch and depot notifications.</small>
                </div>

                <div class="border-top my-4"></div>

                <!-- Password Update Section -->
                <h6 class="fw-bold mb-3 text-main">
                    <i class="fa-solid fa-key me-2 text-warning"></i>Change Password
                </h6>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase" for="password">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" class="form-control" id="password" placeholder="Leave blank to keep current password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold text-uppercase" for="confirm_password">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-lock-check text-muted"></i></span>
                        <input type="password" class="form-control" id="confirm_password" placeholder="Repeat new password">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPasswordBtn">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-2 mt-4 pt-2">
                    <button type="submit" id="saveBtn" class="btn btn-primary py-2 px-4 fw-bold rounded-3 shadow-sm flex-grow-1">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                    </button>
                    <a href="<?php echo htmlspecialchars($back_link); ?>" class="btn btn-outline-secondary py-2 px-4 rounded-3">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Matsapha Hub Support Footer -->
        <div class="text-center mt-4 text-muted small">
            <p class="mb-1"><i class="fa-solid fa-truck-ramp-box me-1"></i> Logico Fleet Management System &bull; Matsapha Industrial Hub</p>
            <p class="mb-0">Need dispatch or technical support? Call <strong class="text-primary">+268 2518 4000</strong></p>
        </div>
    </main>

    <!-- Floating Toast Notification Container -->
    <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Theme Management
    const themeBtn = document.getElementById('themeSwitchBtn');
    const htmlElem = document.documentElement;

    function applyTheme(theme) {
        htmlElem.setAttribute('data-theme', theme);
        localStorage.setItem('logico_theme', theme);
        if (theme === 'dark') {
            themeBtn.innerHTML = '<i class="fa-solid fa-sun text-warning"></i>';
        } else {
            themeBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
        }
    }

    const savedTheme = localStorage.getItem('logico_theme') || 'light';
    applyTheme(savedTheme);

    themeBtn.addEventListener('click', () => {
        const currentTheme = htmlElem.getAttribute('data-theme');
        applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
    });

    // Password Visibility Toggles
    function setupVisibilityToggle(toggleBtnId, inputId) {
        const btn = document.getElementById(toggleBtnId);
        const input = document.getElementById(inputId);
        if (btn && input) {
            btn.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
            });
        }
    }
    setupVisibilityToggle('togglePasswordBtn', 'password');
    setupVisibilityToggle('toggleConfirmPasswordBtn', 'confirm_password');

    // Toast Notification System
    function showToast(title, message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const icons = {
            success: 'fa-circle-check text-success',
            danger: 'fa-circle-xmark text-danger',
            warning: 'fa-triangle-exclamation text-warning',
            info: 'fa-circle-info text-primary'
        };
        const icon = icons[type] || icons.info;
        const toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center border-0 shadow-lg mb-2 show';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-start gap-2">
                    <i class="fa-solid ${icon} fs-5 mt-1"></i>
                    <div>
                        <strong class="d-block">${title}</strong>
                        <span class="text-muted small">${message}</span>
                    </div>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    // Form Submission
    document.getElementById('profileForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        const saveBtn = document.getElementById('saveBtn');

        if (password && password !== confirm) {
            showToast('Validation Error', 'Password confirmation does not match the new password.', 'danger');
            return;
        }

        if (password && password.length < 6) {
            showToast('Security Alert', 'Password should be at least 6 characters long.', 'warning');
            return;
        }

        saveBtn.disabled = true;
        const originalHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving Updates...';

        try {
            const res = await fetch('../api/drivers/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone, password })
            });

            const data = await res.json();

            if (res.ok && data.success) {
                showToast('Success', 'Profile and security settings saved successfully!', 'success');
                document.getElementById('password').value = '';
                document.getElementById('confirm_password').value = '';
            } else {
                showToast('Error', data.error || 'Failed to update profile settings.', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network Error', 'Could not connect to the server. Please check your connection.', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalHtml;
        }
    });
    </script>
</body>
</html>
