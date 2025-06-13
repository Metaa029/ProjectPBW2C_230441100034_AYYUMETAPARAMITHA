<?php
session_start();
// Koneksi database
$conn = new mysqli("localhost", "root", "", "project_manager");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
// Ambil user id dari session, ganti sesuai session yang kamu pakai
$uid = $_SESSION['user']['id'] ?? 0;

// Proses tambah tugas
if (isset($_POST['tambah_tugas'])) {
    $id_proyek = $_POST['id_proyek'] ?? null;
    $nama_tugas = $_POST['nama_tugas'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $deadline = $_POST['deadline'] ?? '';

    if ($id_proyek && $nama_tugas && $deskripsi && $deadline) {
        $stmt = $conn->prepare("INSERT INTO tasks (id_proyek, nama_tugas, deskripsi, deadline) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $id_proyek, $nama_tugas, $deskripsi, $deadline);

        if ($stmt->execute()) {
            echo "<script>alert('Tugas berhasil ditambahkan!'); window.location='?';</script>";
            exit;
        } else {
            echo "<div class='alert alert-danger'>Error menambahkan tugas: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Mohon isi semua kolom.</div>";
    }
}

// Query ulang projects untuk dropdown form tambah tugas
$projectsForTasks = $conn->query("SELECT * FROM projects WHERE id_user=$uid ORDER BY id DESC");
if (!$projectsForTasks) {
    echo "<div class='alert alert-danger'>Error mengambil proyek: " . htmlspecialchars($conn->error) . "</div>";
}
?>
<?php
// Update proyek
if (isset($_POST['update_project'])) {
    $id = $_POST['edit_project_id'];
    $name = $_POST['project_name'];
    $description = $_POST['project_description'];
    $stmt = $conn->prepare("UPDATE projects SET name=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $description, $id);
    $stmt->execute();
    echo "<script>alert('Proyek berhasil diperbarui'); window.location='?';</script>";
}

// Update tugas
if (isset($_POST['update_task'])) {
    $id = $_POST['edit_task_id'];
    $title = $_POST['task_title'];
    $status = $_POST['task_status'];
    $stmt = $conn->prepare("UPDATE tasks SET title=?, status=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $status, $id);
    $stmt->execute();
    echo "<script>alert('Tugas berhasil diperbarui'); window.location='?';</script>";
}
?>

<?php
// session_start();

// Koneksi database
$conn = new mysqli("localhost", "root", "", "project_manager");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);


// Fungsi untuk escape string aman
function esc($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// REGISTER
if (isset($_POST['register'])) {
    $nama = esc($_POST['nama']);
    $email = esc($_POST['email']);
    $password = $_POST['password'];

    // Cek apakah email sudah terdaftar
    $cek = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($cek->num_rows > 0) {
        $error_register = "Email sudah terdaftar!";
    } else {
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (nama, email, password) VALUES ('$nama', '$email', '$pass_hash')");
        header("Location: ?");
        exit;
    }
}

// LOGIN
if (isset($_POST['login'])) {
    $email = esc($_POST['email']);
    $pass = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($pass, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: ?");
            exit;
        } else {
            $error_login = "Password salah!";
        }
    } else {
        $error_login = "Email tidak ditemukan!";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// Jika belum login, tampilkan form login/register
if (!isset($_SESSION['user'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login / Register</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
        <style>
            body {
                background: linear-gradient(135deg, #6a11cb, #2575fc);
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #333;
            }
            .auth-container {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 6px 24px rgba(0,0,0,0.15);
                width: 100%;
                max-width: 420px;
                animation: fadeIn 0.5s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            h3 {
                text-align: center;
                margin-bottom: 25px;
                font-weight: 700;
                color: #0d6efd;
            }
            .form-control {
                border-radius: 10px;
                padding: 12px 15px;
                font-size: 16px;
            }
            .form-control:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 8px rgba(13,110,253,0.25);
            }
            button {
                border-radius: 10px;
                padding: 10px 0;
                font-weight: 600;
                font-size: 16px;
            }
            .toggle-link {
                display: block;
                margin-top: 15px;
                text-align: center;
                color: #0d6efd;
                cursor: pointer;
                text-decoration: underline;
            }
            .error-msg {
                color: red;
                margin-bottom: 15px;
                text-align: center;
            }
        </style>
    </head>
    <body>
    <div class="auth-container">
        <div id="loginForm" <?= isset($error_register) ? 'style="display:none;"' : '' ?>>
            <h3>Login</h3>
            <?php if (isset($error_login)) echo '<div class="error-msg">' . htmlspecialchars($error_login) . '</div>'; ?>
            <form method="post" autocomplete="off">
                <input name="email" type="email" class="form-control mb-3" placeholder="Email" required>
                <input name="password" type="password" class="form-control mb-3" placeholder="Password" required>
                <button name="login" class="btn btn-primary w-100">Login</button>
            </form>
            <span class="toggle-link" onclick="toggleForm()">Belum punya akun? Daftar sekarang</span>
        </div>

        <div id="registerForm" style="<?= isset($error_register) ? 'display:block;' : 'display:none;' ?>">
            <h3>Register</h3>
            <?php if (isset($error_register)) echo '<div class="error-msg">' . htmlspecialchars($error_register) . '</div>'; ?>
            <form method="post" autocomplete="off">
                <input name="nama" type="text" class="form-control mb-3" placeholder="Nama Lengkap" required>
                <input name="email" type="email" class="form-control mb-3" placeholder="Email" required>
                <input name="password" type="password" class="form-control mb-3" placeholder="Password" required>
                <button name="register" class="btn btn-success w-100">Register</button>
            </form>
            <span class="toggle-link" onclick="toggleForm()">Sudah punya akun? Login sekarang</span>
        </div>
    </div>

    <script>
        function toggleForm() {
            const login = document.getElementById("loginForm");
            const register = document.getElementById("registerForm");
            if (login.style.display === "none") {
                login.style.display = "block";
                register.style.display = "none";
            } else {
                login.style.display = "none";
                register.style.display = "block";
            }
        }
    </script>
    </body>
    </html>
    <?php
    exit;
}

// Jika sudah login
$uid = (int)$_SESSION['user']['id'];

// Ambil statistik
$jml_proyek = $conn->query("SELECT COUNT(*) AS total FROM projects WHERE id_user=$uid")->fetch_assoc()['total'];
$jml_tugas = $conn->query("SELECT COUNT(*) AS total FROM tasks t JOIN projects p ON t.id_proyek = p.id WHERE p.id_user = $uid")->fetch_assoc()['total'];
$tugas_selesai = $conn->query("SELECT COUNT(*) AS total FROM tasks t JOIN projects p ON t.id_proyek = p.id WHERE p.id_user = $uid AND t.status='Selesai'")->fetch_assoc()['total'];
$tugas_aktif = $conn->query("SELECT COUNT(*) AS total FROM tasks t JOIN projects p ON t.id_proyek = p.id WHERE p.id_user = $uid AND t.status<>'Selesai'")->fetch_assoc()['total'];

// Tambah Proyek
if (isset($_POST['tambah_proyek'])) {
    $nama = esc($_POST['nama_proyek']);
    $desc = esc($_POST['deskripsi']);
    $start = $_POST['tanggal_mulai'];
    $end = $_POST['tanggal_selesai'];
    $conn->query("INSERT INTO projects (nama_proyek, deskripsi, tanggal_mulai, tanggal_selesai, status, id_user) VALUES ('$nama', '$desc', '$start', '$end', 'Aktif', $uid)");
    header("Location: ?");
    exit;
}

// Tambah Tugas
if (isset($_POST['tambah_tugas'])) {
    $nama = esc($_POST['nama_tugas']);
    $desc = esc($_POST['deskripsi']);
    $deadline = $_POST['deadline'];
    $status = esc($_POST['status']);
    $proyek = (int)$_POST['id_proyek'];
    $assigned = (int)$_POST['assigned_to'];
    $conn->query("INSERT INTO tasks (nama_tugas, deskripsi, deadline, status, id_proyek, assigned_to) VALUES ('$nama', '$desc', '$deadline', '$status', $proyek, $assigned)");
    header("Location: ?");
    exit;
}

// Edit Proyek
if (isset($_POST['edit_project'])) {
    $pid = (int)$_POST['project_id'];
    $name = esc($_POST['edit_name']);
    $desc = esc($_POST['edit_desc']);
    $start = $_POST['edit_start'];
    $end = $_POST['edit_end'];
    $conn->query("UPDATE projects SET nama_proyek='$name', deskripsi='$desc', tanggal_mulai='$start', tanggal_selesai='$end' WHERE id=$pid AND id_user=$uid");
    header("Location: ?");
    exit;
}

// Edit Tugas
if (isset($_POST['edit_task'])) {
    $tid = (int)$_POST['task_id'];
    $name = esc($_POST['edit_name_task']);
    $desc = esc($_POST['edit_desc_task']);
    $deadline = $_POST['edit_deadline'];
    $status = esc($_POST['edit_status']);
    $conn->query("UPDATE tasks SET nama_tugas='$name', deskripsi='$desc', deadline='$deadline', status='$status' WHERE id=$tid");
    header("Location: ?");
    exit;
}

// Ubah status proyek
if (isset($_POST['ubah_status_proyek'])) {
    $pid = (int)$_POST['project_id_status'];
    $status = esc($_POST['status_proyek']);
    $conn->query("UPDATE projects SET status='$status' WHERE id=$pid AND id_user=$uid");
    header("Location: ?");
    exit;
}

// Hapus proyek dan tugas terkait
if (isset($_GET['hapus_proyek'])) {
    $pid = (int)$_GET['hapus_proyek'];
    $conn->query("DELETE FROM tasks WHERE id_proyek=$pid");
    $conn->query("DELETE FROM projects WHERE id=$pid AND id_user=$uid");
    header("Location: ?");
    exit;
}

// Hapus tugas
if (isset($_GET['hapus_tugas'])) {
    $tid = (int)$_GET['hapus_tugas'];
    $conn->query("DELETE FROM tasks WHERE id=$tid");
    header("Location: ?");
    exit;
}

// Ambil data proyek dan users untuk dropdown
$projects = $conn->query("SELECT * FROM projects WHERE id_user=$uid ORDER BY id DESC");
$users = $conn->query("SELECT * FROM users");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        :root {
            --blue: #0d6efd;
            --blue-dark: #0b5ed7;
            --gray-light: #f8f9fa;
            --gray-dark: #343a40;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-light);
            margin: 0;
            padding: 0;
        }
        .sidebar {
            background-color: var(--blue);
            height: 100vh;
            width: 250px;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 20px;
        }
        .sidebar h2 {
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .sidebar nav a {
            color: white;
            display: block;
            margin-bottom: 18px;
            font-weight: 600;
            text-decoration: none;
            font-size: 18px;
            padding-left: 6px;
            border-left: 4px solid transparent;
            transition: border-color 0.3s;
        }
        .sidebar nav a:hover {
            border-left: 4px solid #ffc107;
            color: #ffc107;
        }
        main {
            margin-left: 250px;
            padding: 30px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        header h1 {
            font-weight: 700;
            font-size: 32px;
            color: var(--gray-dark);
        }
        .btn-logout {
            background-color: var(--blue);
            color: white;
            border: none;
            padding: 10px 18px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-logout:hover {
            background-color: var(--blue-dark);
        }
        .stats {
            display: flex;
            gap: 25px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .card-stats {
            flex: 1 1 220px;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgb(0 0 0 / 0.07);
            text-align: center;
            color: var(--gray-dark);
            font-weight: 600;
            font-size: 20px;
        }
        .card-stats i {
            font-size: 40px;
            margin-bottom: 10px;
            color: var(--blue);
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgb(0 0 0 / 0.07);
            margin-bottom: 40px;
        }
        form h3 {
            margin-bottom: 20px;
            font-weight: 700;
            color: var(--blue-dark);
        }
        form .form-control {
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: 16px;
        }
        form button {
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            background-color: var(--blue);
            color: white;
            border: none;
            padding: 12px 0;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        form button:hover {
            background-color: var(--blue-dark);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgb(0 0 0 / 0.07);
            margin-bottom: 50px;
        }
        table thead {
            background-color: var(--blue);
            color: white;
        }
        table thead th {
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            text-align: left;
        }
        table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #ddd;
            font-size: 15px;
            color: var(--gray-dark);
        }
        table tbody tr:hover {
            background-color: #f1f5fb;
        }
        .btn-action {
            border: none;
            padding: 6px 10px;
            margin-right: 4px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-edit {
            background-color: #ffc107;
            color: #343a40;
        }
        .btn-edit:hover {
            background-color: #e0a800;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #bb2d3b;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }
        .status-aktif {
            background-color: #198754;
        }
        .status-selesai {
            background-color: #0d6efd;
        }
        .status-tertunda {
            background-color: #ffc107;
            color: #343a40;
        }
        @media(max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 20px 10px;
            }
            .sidebar h2 {
                font-size: 18px;
                margin-bottom: 20px;
            }
            main {
                margin-left: 60px;
                padding: 20px;
            }
            .stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>ProManage</h2>
        <nav>
            <a href="#dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="#projects"><i class="bi bi-kanban-fill"></i> Proyek</a>
            <a href="#tasks"><i class="bi bi-check2-square"></i> Tugas</a>
            <a href="?logout" class="btn-logout mt-4 d-inline-block text-center">Logout <i class="bi bi-box-arrow-right"></i></a>
        </nav>
    </aside>

    <main>
        <header>
            <h1>Hai, <?= htmlspecialchars($_SESSION['user']['nama']) ?></h1>
            <button onclick="window.location='?logout'" class="btn-logout">Logout</button>
        </header>

        <!-- Statistik -->
        <section class="stats" id="dashboard">
            <div class="card-stats">
                <i class="bi bi-kanban-fill"></i>
                <div>Proyek</div>
                <strong><?= $jml_proyek ?></strong>
            </div>
            <div class="card-stats">
                <i class="bi bi-list-task"></i>
                <div>Total Tugas</div>
                <strong><?= $jml_tugas ?></strong>
            </div>
            <div class="card-stats">
                <i class="bi bi-check-circle"></i>
                <div>Tugas Selesai</div>
                <strong><?= $tugas_selesai ?></strong>
            </div>
            <div class="card-stats">
                <i class="bi bi-clock-history"></i>
                <div>Tugas Aktif</div>
                <strong><?= $tugas_aktif ?></strong>
            </div>
        </section>

        <!-- Form tambah proyek -->
        <section id="projects">
            <form method="post" autocomplete="off">
                <h3>Tambah Proyek Baru</h3>
                <input name="nama_proyek" type="text" placeholder="Nama Proyek" class="form-control" required>
                <textarea name="deskripsi" rows="3" placeholder="Deskripsi Proyek" class="form-control" required></textarea>
                <label>Tanggal Mulai</label>
                <input name="tanggal_mulai" type="date" class="form-control" required>
                <label>Tanggal Selesai</label>
                <input name="tanggal_selesai" type="date" class="form-control" required>
                <button name="tambah_proyek">Tambah Proyek</button>
            </form>
        </section>
<!-- Daftar Proyek -->
<section>
    <h3 class="mb-3">Daftar Proyek</h3>
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Deskripsi</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($projects->num_rows > 0): ?>
                <?php while($p = $projects->fetch_assoc()) : ?>
                    <?php 
                    $st = $p['status'];
                    $cls = ($st == 'Aktif') ? 'status-aktif' : (($st == 'Selesai') ? 'status-selesai' : 'status-tertunda');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama_proyek']) ?></td>
                        <td><?= htmlspecialchars($p['deskripsi']) ?></td>
                        <td><?= $p['tanggal_mulai'] ?></td>
                        <td><?= $p['tanggal_selesai'] ?></td>
                        <td><span class="status-badge <?= $cls ?>"><?= $st ?></span></td>
                        <td>
                            <a href="?edit_project_id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?hapus_proyek=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus proyek ini? Semua tugas terkait juga akan dihapus.')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">Belum ada proyek.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
// Edit proyek
if (isset($_GET['edit_project_id'])) {
    $id = intval($_GET['edit_project_id']);
    // Tambahkan pengecekan kepemilikan proyek
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id=? AND id_user=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    if ($project):
?>
    <div class="card mt-4">
        <div class="card-header bg-warning text-white">Edit Proyek</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                <div class="form-group mb-2">
                    <label>Nama Proyek</label>
                    <input type="text" name="edit_name" class="form-control" value="<?= htmlspecialchars($project['nama_proyek']) ?>" required>
                </div>
                <div class="form-group mb-2">
                    <label>Deskripsi</label>
                    <textarea name="edit_desc" class="form-control" required><?= htmlspecialchars($project['deskripsi']) ?></textarea>
                </div>
                <div class="form-group mb-2">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="edit_start" class="form-control" value="<?= $project['tanggal_mulai'] ?>" required>
                </div>
                <div class="form-group mb-2">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="edit_end" class="form-control" value="<?= $project['tanggal_selesai'] ?>" required>
                </div>
                <button type="submit" name="edit_project" class="btn btn-success">Simpan Perubahan</button>
                <a href="?" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
<?php
    endif;
}
?>

<!-- Form tambah tugas -->
<section id="tasks">
    <form method="post" autocomplete="off">
        <h3>Tambah Tugas Baru</h3>
        <select name="id_proyek" class="form-control" required>
            <option value="" disabled selected>Pilih Proyek</option>
            <?php while($pr = $projectsForTasks->fetch_assoc()) : ?>
                <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['nama_proyek']) ?></option>
            <?php endwhile; ?>
        </select>
        <input name="nama_tugas" type="text" placeholder="Nama Tugas" class="form-control" required>
        <textarea name="deskripsi" rows="3" placeholder="Deskripsi Tugas" class="form-control" required></textarea>
        <label>Tenggat Waktu</label>
        <input name="deadline" type="date" class="form-control" required>
        <button name="tambah_tugas" type="submit" class="btn btn-primary mt-2">Tambah Tugas</button>
    </form>
</section>


<!-- Daftar tugas -->
<section>
    <h3 class="mb-3">Daftar Tugas</h3>
    <table>
        <thead>
            <tr>
                <th>Proyek</th>
                <th>Nama</th>
                <th>Deskripsi</th>
                <th>Tenggat</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query tugas dengan join proyek
            $tasks = $conn->query("SELECT t.*, p.nama_proyek FROM tasks t JOIN projects p ON t.id_proyek=p.id WHERE p.id_user=$uid ORDER BY t.id DESC");
            if ($tasks->num_rows > 0):
                while ($t = $tasks->fetch_assoc()):
                    // Penyesuaian kelas status sesuai status tugas (Pending, In Progress, Completed)
                    $statusLower = strtolower($t['status']);
                    $cls = '';
                    if ($statusLower == 'completed') $cls = 'status-selesai';
                    else if ($statusLower == 'in progress') $cls = 'status-aktif';
                    else $cls = 'status-tertunda';
            ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nama_proyek']) ?></td>
                        <td><?= htmlspecialchars($t['nama_tugas']) ?></td>
                        <td><?= htmlspecialchars($t['deskripsi']) ?></td>
                        <td><?= $t['deadline'] ?></td>
                        <td><span class="status-badge <?= $cls ?>"><?= htmlspecialchars($t['status']) ?></span></td>
                        <td>
                            <a href="?edit_task_id=<?= $t['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?hapus_tugas=<?= $t['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus tugas ini?')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr><td colspan="6" class="text-center">Belum ada tugas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php
// Edit tugas
if (isset($_GET['edit_task_id'])) {
    $id = intval($_GET['edit_task_id']);
    // Tambahkan pengecekan kepemilikan tugas melalui join dengan proyek
    $stmt = $conn->prepare("SELECT t.* FROM tasks t JOIN projects p ON t.id_proyek=p.id WHERE t.id=? AND p.id_user=?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    if ($task):
?>
    <div class="card mt-4">
        <div class="card-header bg-warning text-white">Edit Tugas</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                <div class="form-group mb-2">
                    <label>Nama Tugas</label>
                    <input type="text" name="edit_name_task" class="form-control" value="<?= htmlspecialchars($task['nama_tugas']) ?>" required>
                </div>
                <div class="form-group mb-2">
                    <label>Deskripsi</label>
                    <textarea name="edit_desc_task" class="form-control" required><?= htmlspecialchars($task['deskripsi']) ?></textarea>
                </div>
                <div class="form-group mb-2">
                    <label>Tenggat Waktu</label>
                    <input type="date" name="edit_deadline" class="form-control" value="<?= $task['deadline'] ?>" required>
                </div>
                <div class="form-group mb-2">
                    <label>Status</label>
                    <select name="edit_status" class="form-control" required>
                        <option value="Pending" <?= $task['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= $task['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Completed" <?= $task['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <button type="submit" name="edit_task" class="btn btn-success">Simpan Perubahan</button>
                <a href="?" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
<?php
    endif;
}
?>