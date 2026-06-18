<?php
// Improved user creation form UI. Includes header and shows submitted values.
include __DIR__ . '/include/header.php';

$errors = [];
$submitted = false;
$data = [
    'id' => '', 'name' => '', 'designation' => '', 'department' => '',
    'village' => '', 'grampanchayat' => '', 'taluka' => '', 'mobile' => '',
    'username' => '', 'password' => '', 'system_role' => '', 'role' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $k => $v) {
        $data[$k] = isset($_POST[$k]) ? trim($_POST[$k]) : '';
    }

    // Basic validation
    if ($data['id'] === '') { $errors[] = 'Id is required.'; }
    if ($data['name'] === '') { $errors[] = 'Name is required.'; }
    if ($data['username'] === '') { $errors[] = 'Username is required.'; }
    if ($data['password'] === '') { $errors[] = 'Password is required.'; }
    if ($data['mobile'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $data['mobile'])) { $errors[] = 'Mobile number looks invalid.'; }

    if (empty($errors)) {
        $submitted = true;
        // Note: database persistence omitted intentionally.
    }
}
?>

<style>
    :root{--bg:#f7f8fb;--card:#ffffff;--accent:#0f62fe;--muted:#6b7280}
    body{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;background:transparent}
    .user-card{max-width:980px;margin:2.2rem auto;background:var(--card);padding:1.25rem;border-radius:14px;box-shadow:0 14px 40px rgba(15,23,42,0.06)}
    .header-row{display:flex;align-items:center;gap:1rem}
    .brand-title{flex:1;text-align:center}
    .brand-title h2{margin:0;font-size:1.55rem;font-weight:700;color:#0b1220}
    .brand-title p{margin:0.35rem 0 0;color:var(--muted);font-weight:600}
    form.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-top:1rem}
    .full{grid-column:1/-1}
    label{display:block;font-size:0.85rem;color:#374151;margin-bottom:0.35rem}
    input[type=text], input[type=password], select{width:100%;padding:0.7rem;border:1px solid #e6e9ef;border-radius:10px;font-size:0.95rem;background:#fff;transition:box-shadow .15s, border-color .15s}
    input:focus, select:focus{outline:none;border-color:var(--accent);box-shadow:0 6px 18px rgba(15,98,254,0.08)}
    .actions{display:flex;justify-content:center;margin-top:1.35rem}
    .btn{background:linear-gradient(90deg,var(--accent),#2563eb);color:#fff;padding:0.7rem 1.25rem;border-radius:10px;border:none;cursor:pointer;font-weight:700;box-shadow:0 8px 18px rgba(37,99,235,0.15)}
    .btn.secondary{background:#eef2ff;color:#0b1220}
    .note{font-size:0.9rem;color:var(--muted);text-align:center;margin-top:0.25rem}
    .errors{background:#fff5f5;border:1px solid #ffd3d3;padding:0.8rem;border-radius:8px;color:#9b1c1c;margin-bottom:1rem}
    .success{background:#f3fff4;border:1px solid #c9f7d6;padding:0.8rem;border-radius:8px;color:#14532d;margin-bottom:1rem}
    @media(max-width:740px){form.grid{grid-template-columns:1fr} .brand-title h2{font-size:1.2rem} .user-card{margin:1rem;border-radius:10px}}
</style>

<div class="user-card">
    <div class="header-row">
        <div style="flex:0 0 auto"><img src="include/../assets/ashoka-chakra-png-46987.png" alt="left" style="height:56px;display:block"/></div>
        <div class="brand-title">
            <h2>Interdepartment cooordination portal</h2>
            <p>Zilla parishad, hingoli</p>
        </div>
        <div style="flex:0 0 auto"><img src="include/../assets/20241226854242478.jpg" alt="right" style="height:56px;display:block"/></div>
    </div>

    <div class="note">Fill the form below. This page demonstrates the UI; implement server-side storage as needed.</div>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($submitted): ?>
        <div class="success"><strong>Submitted successfully.</strong> Below are the received values (demo only).</div>
        <div style="padding:0.75rem 1rem;border:1px solid #eef2ff;border-radius:10px;background:#fbfbff">
            <?php foreach ($data as $k => $v): ?>
                <div style="margin:0.35rem 0"><strong><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$k))); ?>:</strong>
                    <?php echo htmlspecialchars($v); ?></div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1rem"><a href="create_user.php" class="btn secondary">Add another</a></div>
    <?php else: ?>

    <form method="post" action="create_user.php" class="grid">
        <div>
            <label for="id">Id</label>
            <input id="id" name="id" type="text" value="<?php echo htmlspecialchars($data['id']); ?>" />
        </div>

        <div>
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($data['name']); ?>" />
        </div>

        <div>
            <label for="designation">Designation</label>
            <input id="designation" name="designation" type="text" value="<?php echo htmlspecialchars($data['designation']); ?>" />
        </div>

        <div>
            <label for="department">Department</label>
            <input id="department" name="department" type="text" value="<?php echo htmlspecialchars($data['department']); ?>" />
        </div>

        <div>
            <label for="village">Village</label>
            <input id="village" name="village" type="text" value="<?php echo htmlspecialchars($data['village']); ?>" />
        </div>

        <div>
            <label for="grampanchayat">Grampanchayat</label>
            <input id="grampanchayat" name="grampanchayat" type="text" value="<?php echo htmlspecialchars($data['grampanchayat']); ?>" />
        </div>

        <div>
            <label for="taluka">Taluka</label>
            <input id="taluka" name="taluka" type="text" value="<?php echo htmlspecialchars($data['taluka']); ?>" />
        </div>

        <div>
            <label for="mobile">Mobile no</label>
            <input id="mobile" name="mobile" type="text" value="<?php echo htmlspecialchars($data['mobile']); ?>" />
        </div>

        <div>
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($data['username']); ?>" />
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" value="" />
        </div>

        <div>
            <label for="system_role">System_role</label>
            <select id="system_role" name="system_role">
                <option value="">-- Select --</option>
                <option value="admin" <?php echo $data['system_role']==='admin'?'selected':''; ?>>Admin</option>
                <option value="user" <?php echo $data['system_role']==='user'?'selected':''; ?>>User</option>
            </select>
        </div>

        <div>
            <label for="role">Role</label>
            <input id="role" name="role" type="text" value="<?php echo htmlspecialchars($data['role']); ?>" />
        </div>

        <div class="full actions">
            <button type="submit" class="btn">Save</button>
        </div>
    </form>

    <?php endif; ?>

</div>

<?php
if (file_exists(__DIR__ . '/include/footer.php')) include __DIR__ . '/include/footer.php';
?>
