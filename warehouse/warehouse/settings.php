<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">⚙️ Settings</a>
        </div>
    </nav>

    <!-- Settings Container -->
    <div class="container">
        <h2 class="text-center mt-4">User & System Settings</h2>
        <p class="text-center text-muted">Manage your preferences and system configurations.</p>

        <div class="row mt-4">
            
            <!-- User Settings -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <i class="fa-solid fa-user-gear"></i> User Settings
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="admin">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="admin@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Change Password</label>
                                <input type="password" class="form-control" placeholder="New Password">
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <i class="fa-solid fa-sliders"></i> System Settings
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Timezone</label>
                                <select class="form-select">
                                    <option selected>UTC +0</option>
                                    <option>UTC +1</option>
                                    <option>UTC -5</option>
                                    <option>UTC +8</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Theme</label>
                                <select class="form-select">
                                    <option selected>Light</option>
                                    <option>Dark</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notification Settings</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" checked>
                                    <label class="form-check-label">Enable Email Notifications</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox">
                                    <label class="form-check-label">Enable SMS Alerts</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
