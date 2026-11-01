<?php
require_once __DIR__ . '/../includes/init.php';
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Logico Fleet Enterprise — Portal Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      padding: 20px;
    }
    .login-box {
      width: 100%;
      max-width: 440px;
      background: rgba(255, 255, 255, 0.96);
      border-radius: 20px;
      box-shadow: 0 20px 45px rgba(0,0,0,0.4);
      padding: 2.25rem;
    }
    .demo-pill {
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      padding: 10px 14px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .demo-pill:hover {
      background: #e2e8f0;
      border-color: #3b82f6;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="text-center mb-4">
      <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-4 p-3 mb-3 shadow">
        <i class="fa-solid fa-truck-ramp-box fs-2"></i>
      </div>
      <h3 class="fw-bold mb-1" style="font-family:'Outfit'; color:#0f172a;">Logico Fleet</h3>
      <p class="text-muted small mb-0">Enterprise FMCG Logistics & Supply Chain Platform</p>
      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary mt-2">Matsapha Central DC • Eswatini</span>
    </div>

    <form id="loginForm">
      <div class="mb-3">
        <label class="form-label fw-semibold small text-muted">Work Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
          <input type="email" name="email" id="email" class="form-control border-start-0" placeholder="admin@logico.test" required>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold small text-muted">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
          <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="••••••••" required>
        </div>
      </div>
      <input type="hidden" id="csrf" value="<?php echo e($csrf); ?>">
      
      <button class="btn btn-primary w-100 py-3 fw-bold shadow-sm" type="submit" id="btnSubmit">
        <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Fleet Portal
      </button>
    </form>
    
    <div id="alert" class="mt-3"></div>

    <hr class="my-4">

    <!-- 1-Click Demo Logins -->
    <div class="text-center mb-2">
      <small class="text-muted fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.08em;">Quick Demo Credentials</small>
    </div>
    <div class="d-flex flex-column gap-2">
      <div class="demo-pill d-flex justify-content-between align-items-center" onclick="fillCreds('admin@logico.test', 'Admin@123')">
        <div>
          <strong class="d-block text-dark small"><i class="fa-solid fa-user-shield text-primary me-1"></i> Executive Admin</strong>
          <small class="text-muted">admin@logico.test</small>
        </div>
        <span class="badge bg-primary">Click to Fill</span>
      </div>

      <div class="demo-pill d-flex justify-content-between align-items-center" onclick="fillCreds('driver1@logico.test', 'Driver@123')">
        <div>
          <strong class="d-block text-dark small"><i class="fa-solid fa-truck text-success me-1"></i> Fleet Driver (David M.)</strong>
          <small class="text-muted">driver1@logico.test</small>
        </div>
        <span class="badge bg-success">Click to Fill</span>
      </div>
    </div>
  </div>

  <script>
  function fillCreds(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
  }

  document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const alert = document.getElementById('alert');
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const csrf = document.getElementById('csrf').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Authenticating...';

    try {
      const res = await fetch('../api/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, csrf })
      });
      const data = await res.json();
      
      if (res.ok) {
        alert.innerHTML = '<div class="alert alert-success py-2 text-center"><i class="fa-solid fa-circle-check me-1"></i> Authenticated. Launching portal...</div>';
        const redirectUrl = data.role === 'driver' ? 'driver_dashboard.php' : 'dashboard.php';
        setTimeout(()=> { window.location.href = redirectUrl; }, 600);
      } else {
        alert.innerHTML = '<div class="alert alert-danger py-2 text-center">' + (data.error || 'Login failed') + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Fleet Portal';
      }
    } catch (err) {
      alert.innerHTML = '<div class="alert alert-danger py-2 text-center">Connection error. Please try again.</div>';
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Fleet Portal';
    }
  });
  </script>
</body>
</html>
