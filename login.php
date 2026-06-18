<?php
// User form with DB save to userdata.users.
include __DIR__ . '/include/header.php';
include __DIR__ . '/include/config.php';

$errors = [];
$submitted = false;
$data = [
    'name' => '', 'designation' => '', 'department' => '',
    'village' => '', 'grampanchayat' => '', 'taluka' => '', 'mobile' => '',
    'username' => '', 'password' => '', 'system_role' => '', 'role' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $k => $v) {
        $data[$k] = isset($_POST[$k]) ? trim($_POST[$k]) : '';
    }

    // Basic validation
    if ($data['name'] === '') { $errors[] = 'Name is required.'; }
    if ($data['username'] === '') { $errors[] = 'Username is required.'; }
    if ($data['password'] === '') { $errors[] = 'Password is required.'; }
    if ($data['mobile'] !== '' && !preg_match('/^[0-9]{10}$/', $data['mobile'])) { $errors[] = 'Mobile number must be exactly 10 digits.'; }
    if ($data['mobile'] === '') { $errors[] = 'Mobile number is required.'; }

    if (empty($errors)) {
        $mysqli = db_connect();

        $stmt = $mysqli->prepare(
            'INSERT INTO users (Name, Designation, Department, Village, Grampanchayat, Talika, `Mobile No`, Username, `Password`, `System Role`, Role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            $errors[] = 'DB prepare failed: ' . $mysqli->error;
        } else {
            $stmt->bind_param(
                'ssssssissss',
                $data['name'],
                $data['designation'],
                $data['department'],
                $data['village'],
                $data['grampanchayat'],
                $data['taluka'],
                $data['mobile'],
                $data['username'],
                $data['password'],
                $data['system_role'],
                $data['role']
            );

            if ($stmt->execute()) {
                $submitted = true;
            } else {
                $errors[] = 'DB insert failed: ' . $stmt->error;
            }

            $stmt->close();
        }

        $mysqli->close();
    }
}
?>

<style>
    .user-card {
        max-width: 900px;
        margin: 2.5rem auto;
        background: var(--bg-card);
        padding: 2rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        font-family: var(--font-body);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal), background var(--transition-normal);
    }
    .user-card:hover {
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.12);
    }
    .user-card-title {
        text-align: center;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
        font-family: var(--font-heading);
        font-weight: 800;
        font-size: 1.75rem;
        letter-spacing: -0.025em;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .user-card-subtitle {
        font-size: 0.95rem;
        color: var(--text-muted);
        text-align: center;
        margin-bottom: 2rem;
        font-family: var(--font-body);
    }
    .user-row {
        display: flex;
        gap: 1.5rem;
        margin-top: 1.25rem;
    }
    .user-col {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    label {
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: color var(--transition-fast);
    }
    .user-col:focus-within label {
        color: var(--primary-light);
    }
    label i {
        color: var(--text-muted);
        font-size: 0.95rem;
        transition: color var(--transition-fast);
    }
    .user-col:focus-within label i {
        color: var(--primary-light);
    }
    input[type=text], input[type=password], input[type=tel], select {
        width: 100%;
        height: 2.75rem;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        background: var(--bg-input);
        color: var(--text-primary);
        box-sizing: border-box;
        font-family: var(--font-body);
        transition: border-color var(--transition-fast), box-shadow var(--transition-fast), background var(--transition-fast);
    }
    input[type=text]:focus, input[type=password]:focus, input[type=tel]:focus, select:focus {
        border-color: var(--primary-light);
        background: var(--bg-card);
        box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.15);
        outline: none;
    }
    select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.1rem;
        padding-right: 2.5rem;
    }
    body.dark-theme select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23cbd5e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    }
    .actions {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
    }
    .btn {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: #fff;
        padding: 0.75rem 2rem;
        border-radius: var(--radius-md);
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-family: var(--font-heading);
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.25);
        transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
    }
    .btn:hover {
        filter: brightness(1.1);
        box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.35);
        transform: translateY(-1px);
    }
    .btn:active {
        transform: translateY(1px);
    }
    .btn.secondary {
        background: var(--bg-hover);
        color: var(--text-primary);
        box-shadow: none;
        border: 1px solid var(--border-color);
    }
    .btn.secondary:hover {
        background: var(--border-color);
        box-shadow: none;
        transform: translateY(-1px);
    }
    .errors {
        background: rgba(220, 38, 38, 0.08);
        border: 1px solid var(--danger-color);
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md);
        color: var(--danger-color);
        margin-bottom: 1.5rem;
        font-family: var(--font-body);
        font-size: 0.95rem;
    }
    .errors strong {
        font-family: var(--font-heading);
        font-weight: 700;
        display: block;
        margin-bottom: 0.5rem;
    }
    .errors ul {
        margin: 0;
        padding-left: 1.25rem;
    }
    .errors li {
        margin-top: 0.25rem;
    }
    .success {
        background: rgba(5, 150, 105, 0.08);
        border: 1px solid var(--success-color);
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md);
        color: var(--success-color);
        margin-bottom: 1.5rem;
        font-family: var(--font-body);
        font-size: 0.95rem;
    }
    .success strong {
        font-family: var(--font-heading);
        font-weight: 700;
    }
    .password-wrapper {
        position: relative;
    }
    .password-wrapper input {
        padding-right: 3rem;
    }
    .toggle-password {
        position: absolute;
        right: 0.75rem;
        bottom: 0.45rem;
        border: none;
        background: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1.8rem;
        height: 1.8rem;
        transition: color var(--transition-fast);
    }
    .toggle-password:hover {
        color: var(--primary-light);
    }
    .toggle-password:focus {
        outline: none;
    }
    .submitted-summary {
        margin-top: 1.5rem;
        padding: 1.25rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        background: var(--bg-hover);
        font-family: var(--font-body);
    }
    .submitted-summary-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px dashed var(--border-color);
    }
    .submitted-summary-item:last-child {
        border-bottom: none;
    }
    .submitted-summary-item strong {
        color: var(--text-secondary);
        font-family: var(--font-heading);
    }
    .submitted-summary-item span {
        color: var(--text-primary);
        font-weight: 500;
    }
    @media (max-width: 768px) {
        .user-row {
            flex-direction: column;
            gap: 1.25rem;
            margin-top: 1rem;
        }
        .user-card {
            margin: 1rem;
            padding: 1.25rem;
        }
    }
</style>

<div class="user-card">
    <div class="user-card-title">Create User / Officer</div>
    <div class="user-card-subtitle">Fill the details below and press Save. Data will be stored in the users database.</div>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <strong><i class="fa-solid fa-triangle-exclamation"></i> Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($submitted): ?>
        <div class="success"><strong><i class="fa-solid fa-circle-check"></i> Saved successfully.</strong> Below are the values submitted to the database.</div>
        <div class="submitted-summary">
            <?php foreach ($data as $k => $v): ?>
                <div class="submitted-summary-item">
                    <strong><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$k))); ?></strong>
                    <span><?php echo htmlspecialchars($v); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1.5rem"><a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn secondary">Add another</a></div>
    <?php else: ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="user-row">
            <div class="user-col">
                <label for="name"><i class="fa-solid fa-user-tie"></i> Name</label>
                <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($data['name']); ?>" />
            </div>
        </div>

        <div class="user-row">
            <div class="user-col">
                <label for="designation"><i class="fa-solid fa-briefcase"></i> Designation</label>
                <select id="designation" name="designation">
                    <option value="">-- निवडा पदवी --</option>
                    <option value="गट विकास अधिकारी" <?php echo $data['designation'] === 'गट विकास अधिकारी' ? 'selected' : ''; ?>>गट विकास अधिकारी</option>
                    <option value="तालुका आरोग्य अधिकारी" <?php echo $data['designation'] === 'तालुका आरोग्य अधिकारी' ? 'selected' : ''; ?>>तालुका आरोग्य अधिकारी</option>
                    <option value="गटशिक्षणाधिकारी" <?php echo $data['designation'] === 'गटशिक्षणाधिकारी' ? 'selected' : ''; ?>>गटशिक्षणाधिकारी</option>
                    <option value="बालविकास प्रकल्प अधिकारी" <?php echo $data['designation'] === 'बालविकास प्रकल्प अधिकारी' ? 'selected' : ''; ?>>बालविकास प्रकल्प अधिकारी</option>
                    <option value="विस्तार अधिकारी (कृषी)" <?php echo $data['designation'] === 'विस्तार अधिकारी (कृषी)' ? 'selected' : ''; ?>>विस्तार अधिकारी (कृषी)</option>
                    <option value="पशुवैद्यकीय अधिकारी" <?php echo $data['designation'] === 'पशुवैद्यकीय अधिकारी' ? 'selected' : ''; ?>>पशुवैद्यकीय अधिकारी</option>
                    <option value="उप. मुख्य कार्यकारी अधिकारी (सा)" <?php echo $data['designation'] === 'उप. मुख्य कार्यकारी अधिकारी (सा)' ? 'selected' : ''; ?>>उप. मुख्य कार्यकारी अधिकारी (सा)</option>
                    <option value="उप. मुख्य कार्यकारी अधिकारी (पं)" <?php echo $data['designation'] === 'उप. मुख्य कार्यकारी अधिकारी (पं)' ? 'selected' : ''; ?>>उप. मुख्य कार्यकारी अधिकारी (पं)</option>
                    <option value="जिल्हा आरोग्य अधिकारी" <?php echo $data['designation'] === 'जिल्हा आरोग्य अधिकारी' ? 'selected' : ''; ?>>जिल्हा आरोग्य अधिकारी</option>
                    <option value="प्रकल्प संचालक (जि.ग्रा.वि.य.)" <?php echo $data['designation'] === 'प्रकल्प संचालक (जि.ग्रा.वि.य.)' ? 'selected' : ''; ?>>प्रकल्प संचालक (जि.ग्रा.वि.य.)</option>
                    <option value="उप. मुख्य कार्यकारी अधिकारी (महिला आणि बाल विकास)" <?php echo $data['designation'] === 'उप. मुख्य कार्यकारी अधिकारी (महिला आणि बाल विकास)' ? 'selected' : ''; ?>>उप. मुख्य कार्यकारी अधिकारी (महिला आणि बाल विकास)</option>
                    <option value="कृषी विकास अधिकारी" <?php echo $data['designation'] === 'कृषी विकास अधिकारी' ? 'selected' : ''; ?>>कृषी विकास अधिकारी</option>
                    <option value="शिक्षणाधिकारी (प्राथमिक)" <?php echo $data['designation'] === 'शिक्षणाधिकारी (प्राथमिक)' ? 'selected' : ''; ?>>शिक्षणाधिकारी (प्राथमिक)</option>
                    <option value="शिक्षणाधिकारी (माध्यमिक)" <?php echo $data['designation'] === 'शिक्षणाधिकारी (माध्यमिक)' ? 'selected' : ''; ?>>शिक्षणाधिकारी (माध्यमिक)</option>
                    <option value="जिल्हा समाजकल्याण अधिकारी" <?php echo $data['designation'] === 'जिल्हा समाजकल्याण अधिकारी' ? 'selected' : ''; ?>>जिल्हा समाजकल्याण अधिकारी</option>
                    <option value="जिल्हा पशुसंवर्धन अधिकारी" <?php echo $data['designation'] === 'जिल्हा पशुसंवर्धन अधिकारी' ? 'selected' : ''; ?>>जिल्हा पशुसंवर्धन अधिकारी</option>
                    <option value="तालुका अभियान व्यवस्थापक" <?php echo $data['designation'] === 'तालुका अभियान व्यवस्थापक' ? 'selected' : ''; ?>>तालुका अभियान व्यवस्थापक</option>
                </select>
            </div>
            <div class="user-col">
                <label for="department"><i class="fa-solid fa-building-user"></i> Department</label>
                <select id="department" name="department">
                    <option value="">-- निवडा विभाग --</option>
                    <option value="पंचायत समिती" <?php echo $data['department'] === 'पंचायत समिती' ? 'selected' : ''; ?>>पंचायत समिती</option>
                    <option value="आरोग्य विभाग" <?php echo $data['department'] === 'आरोग्य विभाग' ? 'selected' : ''; ?>>आरोग्य विभाग</option>
                    <option value="शिक्षण विभाग" <?php echo $data['department'] === 'शिक्षण विभाग' ? 'selected' : ''; ?>>शिक्षण विभाग</option>
                    <option value="महिला व बालकल्याण विभाग" <?php echo $data['department'] === 'महिला व बालकल्याण विभाग' ? 'selected' : ''; ?>>महिला व बालकल्याण विभाग</option>
                    <option value="कृषी विभाग" <?php echo $data['department'] === 'कृषी विभाग' ? 'selected' : ''; ?>>कृषी विभाग</option>
                    <option value="पशुसंवर्धन विभाग" <?php echo $data['department'] === 'पशुसंवर्धन विभाग' ? 'selected' : ''; ?>>पशुसंवर्धन विभाग</option>
                    <option value="सामान्य प्रशासन विभाग" <?php echo $data['department'] === 'सामान्य प्रशासन विभाग' ? 'selected' : ''; ?>>सामान्य प्रशासन विभाग</option>
                    <option value="ग्रामपंचायत विभाग" <?php echo $data['department'] === 'ग्रामपंचायत विभाग' ? 'selected' : ''; ?>>ग्रामपंचायत विभाग</option>
                    <option value="जिल्हा ग्रामीण विकास यंत्रणा" <?php echo $data['department'] === 'जिल्हा ग्रामीण विकास यंत्रणा' ? 'selected' : ''; ?>>जिल्हा ग्रामीण विकास यंत्रणा</option>
                    <option value="शिक्षण विभाग (प्राथमिक)" <?php echo $data['department'] === 'शिक्षण विभाग (प्राथमिक)' ? 'selected' : ''; ?>>शिक्षण विभाग (प्राथमिक)</option>
                    <option value="शिक्षण विभाग (माध्यमिक)" <?php echo $data['department'] === 'शिक्षण विभाग (माध्यमिक)' ? 'selected' : ''; ?>>शिक्षण विभाग (माध्यमिक)</option>
                    <option value="समाज कल्याण विभाग" <?php echo $data['department'] === 'समाज कल्याण विभाग' ? 'selected' : ''; ?>>समाज कल्याण विभाग</option>
                </select>
            </div>
        </div>

        <div class="user-row">
            <div class="user-col">
                <label for="village"><i class="fa-solid fa-tree-city"></i> Village</label>
                <select id="village" name="village">
                    <option value="">-- निवडा गांव --</option>
                    <option value="आमदरी" <?php echo $data['village'] === 'आमदरी' ? 'selected' : ''; ?>>आमदरी</option>
                    <option value="आजलसोंडा" <?php echo $data['village'] === 'आजलसोंडा' ? 'selected' : ''; ?>>आजलसोंडा</option>
                    <option value="अंजनवाडा" <?php echo $data['village'] === 'अंजनवाडा' ? 'selected' : ''; ?>>अंजनवाडा</option>
                    <option value="अंजनवाडी" <?php echo $data['village'] === 'अंजनवाडी' ? 'selected' : ''; ?>>अंजनवाडी</option>
                    <option value="अंखली" <?php echo $data['village'] === 'अंखली' ? 'selected' : ''; ?>>अंखली</option>
                    <option value="आसोला + औंधा" <?php echo $data['village'] === 'आसोला + औंधा' ? 'selected' : ''; ?>>आसोला + औंधा</option>
                    <option value="आसोला ता./क." <?php echo $data['village'] === 'आसोला ता./क.' ? 'selected' : ''; ?>>आसोला ता./क.</option>
                    <option value="आसनाडा" <?php echo $data['village'] === 'आसनाडा' ? 'selected' : ''; ?>>आसनाडा</option>
                    <option value="बेरूळा" <?php echo $data['village'] === 'बेरूळा' ? 'selected' : ''; ?>>बेरूळा</option>
                    <option value="चीमेगाव" <?php echo $data['village'] === 'चीमेगाव' ? 'selected' : ''; ?>>चीमेगाव</option>
                    <option value="चिंचोली निलोजी" <?php echo $data['village'] === 'चिंचोली निलोजी' ? 'selected' : ''; ?>>चिंचोली निलोजी</option>
                    <option value="चौंडी शहापूर" <?php echo $data['village'] === 'चौंडी शहापूर' ? 'selected' : ''; ?>>चौंडी शहापूर</option>
                    <option value="दरेगाव" <?php echo $data['village'] === 'दरेगाव' ? 'selected' : ''; ?>>दरेगाव</option>
                    <option value="देवळा" <?php echo $data['village'] === 'देवळा' ? 'selected' : ''; ?>>देवळा</option>
                    <option value="देवळा तुर्क पिंपरी" <?php echo $data['village'] === 'देवळा तुर्क पिंपरी' ? 'selected' : ''; ?>>देवळा तुर्क पिंपरी</option>
                    <option value="धार" <?php echo $data['village'] === 'धार' ? 'selected' : ''; ?>>धार</option>
                    <option value="धेगज" <?php echo $data['village'] === 'धेगज' ? 'selected' : ''; ?>>धेगज</option>
                    <option value="धोडगाव" <?php echo $data['village'] === 'धोडगाव' ? 'selected' : ''; ?>>धोडगाव</option>
                    <option value="धुधाला" <?php echo $data['village'] === 'धुधाला' ? 'selected' : ''; ?>>धुधाला</option>
                    <option value="दुरचुना" <?php echo $data['village'] === 'दुरचुना' ? 'selected' : ''; ?>>दुरचुना</option>
                    <option value="गाढळा" <?php echo $data['village'] === 'गाढळा' ? 'selected' : ''; ?>>गाढळा</option>
                    <option value="गंगलवाडी" <?php echo $data['village'] === 'गंगलवाडी' ? 'selected' : ''; ?>>गंगलवाडी</option>
                    <option value="गोजेगाव" <?php echo $data['village'] === 'गोजेगाव' ? 'selected' : ''; ?>>गोजेगाव</option>
                    <option value="गोळेगाव" <?php echo $data['village'] === 'गोळेगाव' ? 'selected' : ''; ?>>गोळेगाव</option>
                    <option value="हिवरखेडा" <?php echo $data['village'] === 'हिवरखेडा' ? 'selected' : ''; ?>>हिवरखेडा</option>
                    <option value="हिवरा जातू" <?php echo $data['village'] === 'हिवरा जातू' ? 'selected' : ''; ?>>हिवरा जातू</option>
                    <option value="जडगाव" <?php echo $data['village'] === 'जडगाव' ? 'selected' : ''; ?>>जडगाव</option>
                    <option value="जलालधाबा" <?php echo $data['village'] === 'जलालधाबा' ? 'selected' : ''; ?>>जलालधाबा</option>
                    <option value="जलालपूर" <?php echo $data['village'] === 'जलालपूर' ? 'selected' : ''; ?>>जलालपूर</option>
                    <option value="जामगव्हन" <?php echo $data['village'] === 'जामगव्हन' ? 'selected' : ''; ?>>जामगव्हन</option>
                    <option value="जवळ बाजार" <?php echo $data['village'] === 'जवळ बाजार' ? 'selected' : ''; ?>>जवळ बाजार</option>
                    <option value="जोडपिंपरी" <?php echo $data['village'] === 'जोडपिंपरी' ? 'selected' : ''; ?>>जोडपिंपरी</option>
                    <option value="काकडधबा" <?php echo $data['village'] === 'काकडधबा' ? 'selected' : ''; ?>>काकडधबा</option>
                    <option value="कांजरा" <?php echo $data['village'] === 'कांजरा' ? 'selected' : ''; ?>>कांजरा</option>
                    <option value="कथोडा" <?php echo $data['village'] === 'कथोडा' ? 'selected' : ''; ?>>कथोडा</option>
                    <option value="कथोडा तांडा" <?php echo $data['village'] === 'कथोडा तांडा' ? 'selected' : ''; ?>>कथोडा तांडा</option>
                    <option value="केळी" <?php echo $data['village'] === 'केळी' ? 'selected' : ''; ?>>केळी</option>
                    <option value="कोंडाशी बु." <?php echo $data['village'] === 'कोंडाशी बु.' ? 'selected' : ''; ?>>कोंडाशी बु.</option>
                    <option value="कुंडकरपिंपरी" <?php echo $data['village'] === 'कुंडकरपिंपरी' ? 'selected' : ''; ?>>कुंडकरपिंपरी</option>
                    <option value="लाख" <?php echo $data['village'] === 'लाख' ? 'selected' : ''; ?>>लाख</option>
                    <option value="लांडळा" <?php echo $data['village'] === 'लांडळा' ? 'selected' : ''; ?>>लांडळा</option>
                    <option value="लक्ष्मणनाईक तांडा" <?php echo $data['village'] === 'लक्ष्मणनाईक तांडा' ? 'selected' : ''; ?>>लक्ष्मणनाईक तांडा</option>
                    <option value="लोहारा बु." <?php echo $data['village'] === 'लोहारा बु.' ? 'selected' : ''; ?>>लोहारा बु.</option>
                    <option value="लोहारा खं." <?php echo $data['village'] === 'लोहारा खं.' ? 'selected' : ''; ?>>लोहारा खं.</option>
                    <option value="मार्डी" <?php echo $data['village'] === 'मार्डी' ? 'selected' : ''; ?>>मार्डी</option>
                    <option value="माथा" <?php echo $data['village'] === 'माथा' ? 'selected' : ''; ?>>माथा</option>
                    <option value="मेथा" <?php echo $data['village'] === 'मेथा' ? 'selected' : ''; ?>>मेथा</option>
                    <option value="मूर्तिजापूर" <?php echo $data['village'] === 'मूर्तिजापूर' ? 'selected' : ''; ?>>मूर्तिजापूर</option>
                    <option value="नागेश्वाडी" <?php echo $data['village'] === 'नागेश्वाडी' ? 'selected' : ''; ?>>नागेश्वाडी</option>
                    <option value="नागझरी" <?php echo $data['village'] === 'नागझरी' ? 'selected' : ''; ?>>नागझरी</option>
                    <option value="नाळेगाव" <?php echo $data['village'] === 'नाळेगाव' ? 'selected' : ''; ?>>नाळेगाव</option>
                    <option value="नांदगाव" <?php echo $data['village'] === 'नांदगाव' ? 'selected' : ''; ?>>नांदगाव</option>
                    <option value="नांदखेडा" <?php echo $data['village'] === 'नांदखेडा' ? 'selected' : ''; ?>>नांदखेडा</option>
                    <option value="निशाणा" <?php echo $data['village'] === 'निशाणा' ? 'selected' : ''; ?>>निशाणा</option>
                    <option value="पांगरा लाख" <?php echo $data['village'] === 'पांगरा लाख' ? 'selected' : ''; ?>>पांगरा लाख</option>
                    <option value="पर्डी सवळी" <?php echo $data['village'] === 'पर्डी सवळी' ? 'selected' : ''; ?>>पर्डी सवळी</option>
                    <option value="पेरजबड" <?php echo $data['village'] === 'पेरजबड' ? 'selected' : ''; ?>>पेरजबड</option>
                    <option value="फुलधबा" <?php echo $data['village'] === 'फुलधबा' ? 'selected' : ''; ?>>फुलधबा</option>
                    <option value="पिमलाडारी" <?php echo $data['village'] === 'पिमलाडारी' ? 'selected' : ''; ?>>पिमलाडारी</option>
                    <option value="पिंपळा" <?php echo $data['village'] === 'पिंपळा' ? 'selected' : ''; ?>>पिंपळा</option>
                    <option value="पोटा बु." <?php echo $data['village'] === 'पोटा बु.' ? 'selected' : ''; ?>>पोटा बु.</option>
                    <option value="पोठ खं." <?php echo $data['village'] === 'पोठ खं.' ? 'selected' : ''; ?>>पोठ खं.</option>
                    <option value="पूर" <?php echo $data['village'] === 'पूर' ? 'selected' : ''; ?>>पूर</option>
                    <option value="पूरजळ" <?php echo $data['village'] === 'पूरजळ' ? 'selected' : ''; ?>>पूरजळ</option>
                    <option value="भोसी" <?php echo $data['village'] === 'भोसी' ? 'selected' : ''; ?>>भोसी</option>
                    <option value="राजदरी" <?php echo $data['village'] === 'राजदरी' ? 'selected' : ''; ?>>राजदरी</option>
                    <option value="रांजळा" <?php echo $data['village'] === 'रांजळा' ? 'selected' : ''; ?>>रांजळा</option>
                    <option value="सलाना" <?php echo $data['village'] === 'सलाना' ? 'selected' : ''; ?>>सलाना</option>
                    <option value="सांगनाईक तांडा" <?php echo $data['village'] === 'सांगनाईक तांडा' ? 'selected' : ''; ?>>सांगनाईक तांडा</option>
                    <option value="सरंगावाडी" <?php echo $data['village'] === 'सरंगावाडी' ? 'selected' : ''; ?>>सरंगावाडी</option>
                    <option value="सावळी खं." <?php echo $data['village'] === 'सावळी खं.' ? 'selected' : ''; ?>>सावळी खं.</option>
                    <option value="सावळी" <?php echo $data['village'] === 'सावळी' ? 'selected' : ''; ?>>सावळी</option>
                    <option value="सेन्दुरसना" <?php echo $data['village'] === 'सेन्दुरसना' ? 'selected' : ''; ?>>सेन्दुरसना</option>
                    <option value="शिरड शाहापूर" <?php echo $data['village'] === 'शिरड शाहापूर' ? 'selected' : ''; ?>>शिरड शाहापूर</option>
                    <option value="सिद्धेश्वर" <?php echo $data['village'] === 'सिद्धेश्वर' ? 'selected' : ''; ?>>सिद्धेश्वर</option>
                    <option value="सिरळा" <?php echo $data['village'] === 'सिरळा' ? 'selected' : ''; ?>>सिरळा</option>
                    <option value="सोनवाडी" <?php echo $data['village'] === 'सोनवाडी' ? 'selected' : ''; ?>>सोनवाडी</option>
                    <option value="सुकापूर" <?php echo $data['village'] === 'सुकापूर' ? 'selected' : ''; ?>>सुकापूर</option>
                    <option value="सुरेगाव" <?php echo $data['village'] === 'सुरेगाव' ? 'selected' : ''; ?>>सुरेगाव</option>
                    <option value="सुरवाडी" <?php echo $data['village'] === 'सुरवाडी' ? 'selected' : ''; ?>>सुरवाडी</option>
                    <option value="टाकलगव्हन" <?php echo $data['village'] === 'टाकलगव्हन' ? 'selected' : ''; ?>>टाकलगव्हन</option>
                    <option value="तामटी तांडा" <?php echo $data['village'] === 'तामटी तांडा' ? 'selected' : ''; ?>>तामटी तांडा</option>
                    <option value="तपोवन" <?php echo $data['village'] === 'तपोवन' ? 'selected' : ''; ?>>तपोवन</option>
                    <option value="उखळी" <?php echo $data['village'] === 'उखळी' ? 'selected' : ''; ?>>उखळी</option>
                    <option value="उमरा" <?php echo $data['village'] === 'उमरा' ? 'selected' : ''; ?>>उमरा</option>
                    <option value="उंडेगाव" <?php echo $data['village'] === 'उंडेगाव' ? 'selected' : ''; ?>>उंडेगाव</option>
                    <option value="वडद" <?php echo $data['village'] === 'वडद' ? 'selected' : ''; ?>>वडद</option>
                    <option value="वडचुना" <?php echo $data['village'] === 'वडचुना' ? 'selected' : ''; ?>>वडचुना</option>
                    <option value="वागरवाडी" <?php echo $data['village'] === 'वागरवाडी' ? 'selected' : ''; ?>>वागरवाडी</option>
                    <option value="वागरवाडी तांडा" <?php echo $data['village'] === 'वागरवाडी तांडा' ? 'selected' : ''; ?>>वागरवाडी तांडा</option>
                    <option value="वळकी" <?php echo $data['village'] === 'वळकी' ? 'selected' : ''; ?>>वळकी</option>
                    <option value="वासई" <?php echo $data['village'] === 'वासई' ? 'selected' : ''; ?>>वासई</option>
                    <option value="येड़ुत" <?php echo $data['village'] === 'येड़ुत' ? 'selected' : ''; ?>>येड़ुत</option>
                    <option value="येहळेगाव" <?php echo $data['village'] === 'येहळेगाव' ? 'selected' : ''; ?>>येहळेगाव</option>
                    <option value="बोरजा" <?php echo $data['village'] === 'बोरजा' ? 'selected' : ''; ?>>बोरजा</option>
                    <option value="ब्राह्मणवाडा" <?php echo $data['village'] === 'ब्राह्मणवाडा' ? 'selected' : ''; ?>>ब्राह्मणवाडा</option>
                    <option value="राजापूर" <?php echo $data['village'] === 'राजापूर' ? 'selected' : ''; ?>>राजापूर</option>
                    <option value="रामेश्वर" <?php echo $data['village'] === 'रामेश्वर' ? 'selected' : ''; ?>>रामेश्वर</option>
                    <option value="रुपूर" <?php echo $data['village'] === 'रुपूर' ? 'selected' : ''; ?>>रुपूर</option>
                    <option value="सावरखेडा" <?php echo $data['village'] === 'सावरखेडा' ? 'selected' : ''; ?>>सावरखेडा</option>
                    <option value="येळी" <?php echo $data['village'] === 'येळी' ? 'selected' : ''; ?>>येळी</option>
                    <option value="बाभुळगाव" <?php echo $data['village'] === 'बाभुळगाव' ? 'selected' : ''; ?>>बाभुळगाव</option>
                    <option value="बोराळा" <?php echo $data['village'] === 'बोराळा' ? 'selected' : ''; ?>>बोराळा</option>
                    <option value="आडगाव" <?php echo $data['village'] === 'आडगाव' ? 'selected' : ''; ?>>आडगाव</option>
                    <option value="आखरुखा" <?php echo $data['village'] === 'आखरुखा' ? 'selected' : ''; ?>>आखरुखा</option>
                    <option value="अकोली" <?php echo $data['village'] === 'अकोली' ? 'selected' : ''; ?>>अकोली</option>
                    <option value="अंबा" <?php echo $data['village'] === 'अंबा' ? 'selected' : ''; ?>>अंबा</option>
                    <option value="अराळ" <?php echo $data['village'] === 'अराळ' ? 'selected' : ''; ?>>अराळ</option>
                    <option value="असेगाव" <?php echo $data['village'] === 'असेगाव' ? 'selected' : ''; ?>>असेगाव</option>
                    <option value="बाळेगाव" <?php echo $data['village'] === 'बाळेगाव' ? 'selected' : ''; ?>>बाळेगाव</option>
                    <option value="भेंडेगाव" <?php echo $data['village'] === 'भेंडेगाव' ? 'selected' : ''; ?>>भेंडेगाव</option>
                    <option value="भोगावन" <?php echo $data['village'] === 'भोगावन' ? 'selected' : ''; ?>>भोगावन</option>
                    <option value="भोरिपगाव" <?php echo $data['village'] === 'भोरिपगाव' ? 'selected' : ''; ?>>भोरिपगाव</option>
                    <option value="बोरगाव" <?php echo $data['village'] === 'बोरगाव' ? 'selected' : ''; ?>>बोरगाव</option>
                    <option value="बोरगाव (बु)" <?php echo $data['village'] === 'बोरगाव (बु)' ? 'selected' : ''; ?>>बोरगाव (बु)</option>
                    <option value="बोरीसावंत" <?php echo $data['village'] === 'बोरीसावंत' ? 'selected' : ''; ?>>बोरीसावंत</option>
                    <option value="ब्रह्मणगाव बु." <?php echo $data['village'] === 'ब्रह्मणगाव बु.' ? 'selected' : ''; ?>>ब्रह्मणगाव बु.</option>
                    <option value="चिखली" <?php echo $data['village'] === 'चिखली' ? 'selected' : ''; ?>>चिखली</option>
                    <option value="चोंडी" <?php echo $data['village'] === 'चोंडी' ? 'selected' : ''; ?>>चोंडी</option>
                    <option value="दगडगाव" <?php echo $data['village'] === 'दगडगाव' ? 'selected' : ''; ?>>दगडगाव</option>
                    <option value="दगापिंपरी" <?php echo $data['village'] === 'दगापिंपरी' ? 'selected' : ''; ?>>दगापिंपरी</option>
                    <option value="दरेफळ" <?php echo $data['village'] === 'दरेफळ' ? 'selected' : ''; ?>>दरेफळ</option>
                    <option value="धाभडी" <?php echo $data['village'] === 'धाभडी' ? 'selected' : ''; ?>>धाभडी</option>
                    <option value="धामणगाव" <?php echo $data['village'] === 'धामणगाव' ? 'selected' : ''; ?>>धामणगाव</option>
                    <option value="धनोडा" <?php echo $data['village'] === 'धनोडा' ? 'selected' : ''; ?>>धनोडा</option>
                    <option value="धौलगाव" <?php echo $data['village'] === 'धौलगाव' ? 'selected' : ''; ?>>धौलगाव</option>
                    <option value="डिग्रस" <?php echo $data['village'] === 'डिग्रस' ? 'selected' : ''; ?>>डिग्रस</option>
                    <option value="डोंवाडा" <?php echo $data['village'] === 'डोंवाडा' ? 'selected' : ''; ?>>डोंवाडा</option>
                    <option value="एंजणगाव" <?php echo $data['village'] === 'एंजणगाव' ? 'selected' : ''; ?>>एंजणगाव</option>
                    <option value="गिरगाव" <?php echo $data['village'] === 'गिरगाव' ? 'selected' : ''; ?>>गिरगाव</option>
                    <option value="गुंडा" <?php echo $data['village'] === 'गुंडा' ? 'selected' : ''; ?>>गुंडा</option>
                    <option value="गुंज" <?php echo $data['village'] === 'गुंज' ? 'selected' : ''; ?>>गुंज</option>
                    <option value="हापसापूर" <?php echo $data['village'] === 'हापसापूर' ? 'selected' : ''; ?>>हापसापूर</option>
                    <option value="गणेशपूर" <?php echo $data['village'] === 'गणेशपूर' ? 'selected' : ''; ?>>गणेशपूर</option>
                    <option value="हयातनगर" <?php echo $data['village'] === 'हयातनगर' ? 'selected' : ''; ?>>हयातनगर</option>
                    <option value="हिरडगाव" <?php echo $data['village'] === 'हिरडगाव' ? 'selected' : ''; ?>>हिरडगाव</option>
                    <option value="हिवर (खं)" <?php echo $data['village'] === 'हिवर (खं)' ? 'selected' : ''; ?>>हिवर (खं)</option>
                    <option value="जवळा (बु)" <?php echo $data['village'] === 'जवळा (बु)' ? 'selected' : ''; ?>>जवळा (बु)</option>
                    <option value="जवलाट-बाभुळगाव" <?php echo $data['village'] === 'जवलाट-बाभुळगाव' ? 'selected' : ''; ?>>जवलाट-बाभुळगाव</option>
                    <option value="जुन्नुना" <?php echo $data['village'] === 'जुन्नुना' ? 'selected' : ''; ?>>जुन्नुना</option>
                    <option value="कगबन" <?php echo $data['village'] === 'कगबन' ? 'selected' : ''; ?>>कगबन</option>
                    <option value="कळांबा" <?php echo $data['village'] === 'कळांबा' ? 'selected' : ''; ?>>कळांबा</option>
                    <option value="कन्हेरगाव" <?php echo $data['village'] === 'कन्हेरगाव' ? 'selected' : ''; ?>>कन्हेरगाव</option>
                    <option value="कारंजी" <?php echo $data['village'] === 'कारंजी' ? 'selected' : ''; ?>>कारंजी</option>
                    <option value="हट्टा" <?php echo $data['village'] === 'हट्टा' ? 'selected' : ''; ?>>हट्टा</option>
                    <option value="खांडेगाव" <?php echo $data['village'] === 'खांडेगाव' ? 'selected' : ''; ?>>खांडेगाव</option>
                    <option value="खुडनापूर" <?php echo $data['village'] === 'खुडनापूर' ? 'selected' : ''; ?>>खुडनापूर</option>
                    <option value="किण्होलाज" <?php echo $data['village'] === 'किण्होलाज' ? 'selected' : ''; ?>>किण्होलाज</option>
                    <option value="कोनसा" <?php echo $data['village'] === 'कोनसा' ? 'selected' : ''; ?>>कोनसा</option>
                    <option value="कोनाथा" <?php echo $data['village'] === 'कोनाथा' ? 'selected' : ''; ?>>कोनाथा</option>
                    <option value="कोर्ता" <?php echo $data['village'] === 'कोर्ता' ? 'selected' : ''; ?>>कोर्ता</option>
                    <option value="कोठारी" <?php echo $data['village'] === 'कोठारी' ? 'selected' : ''; ?>>कोठारी</option>
                    <option value="कोउडगाव" <?php echo $data['village'] === 'कोउडगाव' ? 'selected' : ''; ?>>कोउडगाव</option>
                    <option value="कुडाळा" <?php echo $data['village'] === 'कुडाळा' ? 'selected' : ''; ?>>कुडाळा</option>
                    <option value="करंजाळा" <?php echo $data['village'] === 'करंजाळा' ? 'selected' : ''; ?>>करंजाळा</option>
                    <option value="कुरंडवाडी" <?php echo $data['village'] === 'कुरंडवाडी' ? 'selected' : ''; ?>>कुरंडवाडी</option>
                    <option value="कुरुंडा" <?php echo $data['village'] === 'कुरुंडा' ? 'selected' : ''; ?>>कुरुंडा</option>
                    <option value="लहान" <?php echo $data['village'] === 'लहान' ? 'selected' : ''; ?>>लहान</option>
                    <option value="लिंगी" <?php echo $data['village'] === 'लिंगी' ? 'selected' : ''; ?>>लिंगी</option>
                    <option value="लोण (बु)" <?php echo $data['village'] === 'लोण (बु)' ? 'selected' : ''; ?>>लोण (बु)</option>
                    <option value="लोलेश्वर" <?php echo $data['village'] === 'लोलेश्वर' ? 'selected' : ''; ?>>लोलेश्वर</option>
                    <option value="महागाव" <?php echo $data['village'] === 'महागाव' ? 'selected' : ''; ?>>महागाव</option>
                    <option value="महमदपूरवाडी" <?php echo $data['village'] === 'महमदपूरवाडी' ? 'selected' : ''; ?>>महमदपूरवाडी</option>
                    <option value="मालवटा" <?php echo $data['village'] === 'मालवटा' ? 'selected' : ''; ?>>मालवटा</option>
                    <option value="मारळपूर" <?php echo $data['village'] === 'मारळपूर' ? 'selected' : ''; ?>>मारळपूर</option>
                    <option value="मारसूल" <?php echo $data['village'] === 'मारसूल' ? 'selected' : ''; ?>>मारसूल</option>
                    <option value="मटेगाव" <?php echo $data['village'] === 'मटेगाव' ? 'selected' : ''; ?>>मटेगाव</option>
                    <option value="माथारगाव" <?php echo $data['village'] === 'माथारगाव' ? 'selected' : ''; ?>>माथारगाव</option>
                    <option value="मोहोळगाव" <?php echo $data['village'] === 'मोहोळगाव' ? 'selected' : ''; ?>>मोहोळगाव</option>
                    <option value="मुढी" <?php echo $data['village'] === 'मुढी' ? 'selected' : ''; ?>>मुढी</option>
                    <option value="मुरुंबा (बु)" <?php echo $data['village'] === 'मुरुंबा (बु)' ? 'selected' : ''; ?>>मुरुंबा (बु)</option>
                    <option value="नाहाड" <?php echo $data['village'] === 'नाहाड' ? 'selected' : ''; ?>>नाहाड</option>
                    <option value="पालसगाव" <?php echo $data['village'] === 'पालसगाव' ? 'selected' : ''; ?>>पालसगाव</option>
                    <option value="पांगरा" <?php echo $data['village'] === 'पांगरा' ? 'selected' : ''; ?>>पांगरा</option>
                    <option value="पांग्रासाती" <?php echo $data['village'] === 'पांग्रासाती' ? 'selected' : ''; ?>>पांग्रासाती</option>
                    <option value="पांग्राशिंदे" <?php echo $data['village'] === 'पांग्राशिंदे' ? 'selected' : ''; ?>>पांग्राशिंदे</option>
                    <option value="पर्डी (बु)" <?php echo $data['village'] === 'पर्डी (बु)' ? 'selected' : ''; ?>>पर्डी (बु)</option>
                    <option value="पर्डी (खं)" <?php echo $data['village'] === 'पर्डी (खं)' ? 'selected' : ''; ?>>पर्डी (खं)</option>
                    <option value="परजना" <?php echo $data['village'] === 'परजना' ? 'selected' : ''; ?>>परजना</option>
                    <option value="परळी" <?php echo $data['village'] === 'परळी' ? 'selected' : ''; ?>>परळी</option>
                    <option value="परवा" <?php echo $data['village'] === 'परवा' ? 'selected' : ''; ?>>परवा</option>
                    <option value="पिंपळगाव" <?php echo $data['village'] === 'पिंपळगाव' ? 'selected' : ''; ?>>पिंपळगाव</option>
                    <option value="कवٹھा" <?php echo $data['village'] === 'कवठा' ? 'selected' : ''; ?>>कवठा</option>
                    <option value="पिंप्राळा" <?php echo $data['village'] === 'पिंप्राळा' ? 'selected' : ''; ?>>पिंप्राळा</option>
                    <option value="पुयणी (बु)" <?php echo $data['village'] === 'पुयणी (बु)' ? 'selected' : ''; ?>>पुयणी (बु)</option>
                    <option value="पुयणी (खं)" <?php echo $data['village'] === 'पुयणी (खं)' ? 'selected' : ''; ?>>पुयणी (खं)</option>
                    <option value="रायवाडी" <?php echo $data['village'] === 'रायवाडी' ? 'selected' : ''; ?>>रायवाडी</option>
                    <option value="राजवाडी" <?php echo $data['village'] === 'राजवाडी' ? 'selected' : ''; ?>>राजवाडी</option>
                    <option value="रंजना" <?php echo $data['village'] === 'रंजना' ? 'selected' : ''; ?>>रंजना</option>
                    <option value="रेणकपूर" <?php echo $data['village'] === 'रेणकपूर' ? 'selected' : ''; ?>>रेणकपूर</option>
                    <option value="रेऊलगाव" <?php echo $data['village'] === 'रेऊलगाव' ? 'selected' : ''; ?>>रेऊलगाव</option>
                    <option value="खांबाळा" <?php echo $data['village'] === 'खांबाळा' ? 'selected' : ''; ?>>खांबाळा</option>
                    <option value="रोडगा" <?php echo $data['village'] === 'रोडगा' ? 'selected' : ''; ?>>रोडगा</option>
                    <option value="सरोळे" <?php echo $data['village'] === 'सरोळे' ? 'selected' : ''; ?>>सरोळे</option>
                    <option value="सातेफळ" <?php echo $data['village'] === 'सातेफळ' ? 'selected' : ''; ?>>सातेफळ</option>
                    <option value="सावंगी (बु)" <?php echo $data['village'] === 'सावंगी (बु)' ? 'selected' : ''; ?>>सावंगी (बु)</option>
                    <option value="सेलू" <?php echo $data['village'] === 'सेलू' ? 'selected' : ''; ?>>सेलू</option>
                    <option value="शिंडी" <?php echo $data['village'] === 'शिंडी' ? 'selected' : ''; ?>>शिंडी</option>
                    <option value="सिरळी" <?php echo $data['village'] === 'सिरळी' ? 'selected' : ''; ?>>सिरळी</option>
                </select>
            </div>
            <div class="user-col">
                <label for="grampanchayat"><i class="fa-solid fa-hotel"></i> Grampanchayat</label>
                <select id="grampanchayat" name="grampanchayat">
                    <option value="">-- निवडा ग्रामपंचायत --</option>
                    <option value="आमदरी" <?php echo $data['grampanchayat'] === 'आमदरी' ? 'selected' : ''; ?>>आमदरी</option>
                    <option value="आजलसोंडा" <?php echo $data['grampanchayat'] === 'आजलसोंडा' ? 'selected' : ''; ?>>आजलसोंडा</option>
                    <option value="अंजनवाडा" <?php echo $data['grampanchayat'] === 'अंजनवाडा' ? 'selected' : ''; ?>>अंजनवाडा</option>
                    <option value="अंजनवाडी" <?php echo $data['grampanchayat'] === 'अंजनवाडी' ? 'selected' : ''; ?>>अंजनवाडी</option>
                    <option value="अंखली" <?php echo $data['grampanchayat'] === 'अंखली' ? 'selected' : ''; ?>>अंखली</option>
                    <option value="आसोला + औंधा" <?php echo $data['grampanchayat'] === 'आसोला + औंधा' ? 'selected' : ''; ?>>आसोला + औंधा</option>
                    <option value="आसोला ता./क." <?php echo $data['grampanchayat'] === 'आसोला ता./क.' ? 'selected' : ''; ?>>आसोला ता./क.</option>
                    <option value="आसनाडा" <?php echo $data['grampanchayat'] === 'आसनाडा' ? 'selected' : ''; ?>>आसनाडा</option>
                    <option value="बेरूळा" <?php echo $data['grampanchayat'] === 'बेरूळा' ? 'selected' : ''; ?>>बेरूळा</option>
                    <option value="चीमेगाव" <?php echo $data['grampanchayat'] === 'चीमेगाव' ? 'selected' : ''; ?>>चीमेगाव</option>
                    <option value="चिंचोली निलोजी" <?php echo $data['grampanchayat'] === 'चिंचोली निलोजी' ? 'selected' : ''; ?>>चिंचोली निलोजी</option>
                    <option value="चौंडी शहापूर" <?php echo $data['grampanchayat'] === 'चौंडी शहापूर' ? 'selected' : ''; ?>>चौंडी शहापूर</option>
                    <option value="दरेगाव" <?php echo $data['grampanchayat'] === 'दरेगाव' ? 'selected' : ''; ?>>दरेगाव</option>
                    <option value="देवळा" <?php echo $data['grampanchayat'] === 'देवळा' ? 'selected' : ''; ?>>देवळा</option>
                    <option value="देवळा तुर्क पिंपरी" <?php echo $data['grampanchayat'] === 'देवळा तुर्क पिंपरी' ? 'selected' : ''; ?>>देवळा तुर्क पिंपरी</option>
                    <option value="धार" <?php echo $data['grampanchayat'] === 'धार' ? 'selected' : ''; ?>>धार</option>
                    <option value="धेगज" <?php echo $data['grampanchayat'] === 'धेगज' ? 'selected' : ''; ?>>धेगज</option>
                    <option value="धोडगाव" <?php echo $data['grampanchayat'] === 'धोडगाव' ? 'selected' : ''; ?>>धोडगाव</option>
                    <option value="धुधाला" <?php echo $data['grampanchayat'] === 'धुधाला' ? 'selected' : ''; ?>>धुधाला</option>
                    <option value="दुरचुना" <?php echo $data['grampanchayat'] === 'दुरचुना' ? 'selected' : ''; ?>>दुरचुना</option>
                    <option value="गाढळा" <?php echo $data['grampanchayat'] === 'गाढळा' ? 'selected' : ''; ?>>गाढळा</option>
                    <option value="गंगलवाडी" <?php echo $data['grampanchayat'] === 'गंगलवाडी' ? 'selected' : ''; ?>>गंगलवाडी</option>
                    <option value="गोजेगाव" <?php echo $data['grampanchayat'] === 'गोजेगाव' ? 'selected' : ''; ?>>गोजेगाव</option>
                    <option value="गोळेगाव" <?php echo $data['grampanchayat'] === 'गोळेगाव' ? 'selected' : ''; ?>>गोळेगाव</option>
                    <option value="हिवरखेडा" <?php echo $data['grampanchayat'] === 'हिवरखेडा' ? 'selected' : ''; ?>>हिवरखेडा</option>
                    <option value="हिवरा जातू" <?php echo $data['grampanchayat'] === 'हिवरा जातू' ? 'selected' : ''; ?>>हिवरा जातू</option>
                    <option value="जडगाव" <?php echo $data['grampanchayat'] === 'जडगाव' ? 'selected' : ''; ?>>जडगाव</option>
                    <option value="जलालधाबा" <?php echo $data['grampanchayat'] === 'जलालधाबा' ? 'selected' : ''; ?>>जलालधाबा</option>
                    <option value="जलालपूर" <?php echo $data['grampanchayat'] === 'जलालपूर' ? 'selected' : ''; ?>>जलालपूर</option>
                    <option value="जामगव्हन" <?php echo $data['grampanchayat'] === 'जामगव्हन' ? 'selected' : ''; ?>>जामगव्हन</option>
                    <option value="जवळ बाजार" <?php echo $data['grampanchayat'] === 'जवळ बाजार' ? 'selected' : ''; ?>>जवळ बाजार</option>
                    <option value="जोडपिंपरी" <?php echo $data['grampanchayat'] === 'जोडपिंपरी' ? 'selected' : ''; ?>>जोडपिंपरी</option>
                    <option value="काकडधबा" <?php echo $data['grampanchayat'] === 'काकडधबा' ? 'selected' : ''; ?>>काकडधबा</option>
                    <option value="कांजरा" <?php echo $data['grampanchayat'] === 'कांजरा' ? 'selected' : ''; ?>>कांजरा</option>
                    <option value="कथोडा" <?php echo $data['grampanchayat'] === 'कथोडा' ? 'selected' : ''; ?>>कथोडा</option>
                    <option value="कथोडा तांडा" <?php echo $data['grampanchayat'] === 'कथोडा तांडा' ? 'selected' : ''; ?>>कथोडा तांडा</option>
                    <option value="केळी" <?php echo $data['grampanchayat'] === 'केळी' ? 'selected' : ''; ?>>केळी</option>
                    <option value="कोंडाशी बु." <?php echo $data['grampanchayat'] === 'कोंडाशी बु.' ? 'selected' : ''; ?>>कोंडाशी बु.</option>
                    <option value="कुंडकरपिंपरी" <?php echo $data['grampanchayat'] === 'कुंडकरपिंपरी' ? 'selected' : ''; ?>>कुंडकरपिंपरी</option>
                    <option value="लाख" <?php echo $data['grampanchayat'] === 'लाख' ? 'selected' : ''; ?>>लाख</option>
                    <option value="लांडळा" <?php echo $data['grampanchayat'] === 'लांडळा' ? 'selected' : ''; ?>>लांडळा</option>
                    <option value="लक्ष्मणनाईक तांडा" <?php echo $data['grampanchayat'] === 'लक्ष्मणनाईक तांडा' ? 'selected' : ''; ?>>लक्ष्मणनाईक तांडा</option>
                    <option value="लोहारा बु." <?php echo $data['grampanchayat'] === 'लोहारा बु.' ? 'selected' : ''; ?>>लोहारा बु.</option>
                    <option value="लोहारा खं." <?php echo $data['grampanchayat'] === 'लोहारा खं.' ? 'selected' : ''; ?>>लोहारा खं.</option>
                    <option value="मार्डी" <?php echo $data['grampanchayat'] === 'मार्डी' ? 'selected' : ''; ?>>मार्डी</option>
                    <option value="माथा" <?php echo $data['grampanchayat'] === 'माथा' ? 'selected' : ''; ?>>माथा</option>
                    <option value="मेथा" <?php echo $data['grampanchayat'] === 'मेथा' ? 'selected' : ''; ?>>मेथा</option>
                    <option value="मूर्तिजापूर" <?php echo $data['grampanchayat'] === 'मूर्तिजापूर' ? 'selected' : ''; ?>>मूर्तिजापूर</option>
                    <option value="नागेश्वाडी" <?php echo $data['grampanchayat'] === 'नागेश्वाडी' ? 'selected' : ''; ?>>नागेश्वाडी</option>
                    <option value="नागझरी" <?php echo $data['grampanchayat'] === 'नागझरी' ? 'selected' : ''; ?>>नागझरी</option>
                    <option value="नाळेगाव" <?php echo $data['grampanchayat'] === 'नाळेगाव' ? 'selected' : ''; ?>>नाळेगाव</option>
                    <option value="नांदगाव" <?php echo $data['grampanchayat'] === 'नांदगाव' ? 'selected' : ''; ?>>नांदगाव</option>
                    <option value="नांदखेडा" <?php echo $data['grampanchayat'] === 'नांदखेडा' ? 'selected' : ''; ?>>नांदखेडा</option>
                    <option value="निशाणा" <?php echo $data['grampanchayat'] === 'निशाणा' ? 'selected' : ''; ?>>निशाणा</option>
                    <option value="पांगरा लाख" <?php echo $data['grampanchayat'] === 'पांगरा लाख' ? 'selected' : ''; ?>>पांगरा लाख</option>
                    <option value="पर्डी सवळी" <?php echo $data['grampanchayat'] === 'पर्डी सवळी' ? 'selected' : ''; ?>>पर्डी सवळी</option>
                    <option value="पेरजबड" <?php echo $data['grampanchayat'] === 'पेरजबड' ? 'selected' : ''; ?>>पेरजबड</option>
                    <option value="फुलधबा" <?php echo $data['grampanchayat'] === 'फुलधबा' ? 'selected' : ''; ?>>फुलधबा</option>
                    <option value="पिमलाडारी" <?php echo $data['grampanchayat'] === 'पिमलाडारी' ? 'selected' : ''; ?>>पिमलाडारी</option>
                    <option value="पिंपळा" <?php echo $data['grampanchayat'] === 'पिंपळा' ? 'selected' : ''; ?>>पिंपळा</option>
                    <option value="पोटा बु." <?php echo $data['grampanchayat'] === 'पोटा बु.' ? 'selected' : ''; ?>>पोटा बु.</option>
                    <option value="पोठ खं." <?php echo $data['grampanchayat'] === 'पोठ खं.' ? 'selected' : ''; ?>>पोठ खं.</option>
                    <option value="पूर" <?php echo $data['grampanchayat'] === 'पूर' ? 'selected' : ''; ?>>पूर</option>
                    <option value="पूरजळ" <?php echo $data['grampanchayat'] === 'पूरजळ' ? 'selected' : ''; ?>>पूरजळ</option>
                    <option value="भोसी" <?php echo $data['grampanchayat'] === 'भोसी' ? 'selected' : ''; ?>>भोसी</option>
                    <option value="राजदरी" <?php echo $data['grampanchayat'] === 'राजदरी' ? 'selected' : ''; ?>>राजदरी</option>
                    <option value="रांजळा" <?php echo $data['grampanchayat'] === 'रांजळा' ? 'selected' : ''; ?>>रांजळा</option>
                    <option value="सलाना" <?php echo $data['grampanchayat'] === 'सलाना' ? 'selected' : ''; ?>>सलाना</option>
                    <option value="सांगनाईक तांडा" <?php echo $data['grampanchayat'] === 'सांगनाईक तांडा' ? 'selected' : ''; ?>>सांगनाईक तांडा</option>
                    <option value="सरंगावाडी" <?php echo $data['grampanchayat'] === 'सरंगावाडी' ? 'selected' : ''; ?>>सरंगावाडी</option>
                    <option value="सावळी खं." <?php echo $data['grampanchayat'] === 'सावळी खं.' ? 'selected' : ''; ?>>सावळी खं.</option>
                    <option value="सावळी" <?php echo $data['grampanchayat'] === 'सावळी' ? 'selected' : ''; ?>>सावळी</option>
                    <option value="सेन्दुरसना" <?php echo $data['grampanchayat'] === 'सेन्दुरसना' ? 'selected' : ''; ?>>सेन्दुरसना</option>
                    <option value="शिरड शाहापूर" <?php echo $data['grampanchayat'] === 'शिरड शाहापूर' ? 'selected' : ''; ?>>शिरड शाहापूर</option>
                    <option value="सिद्धेश्वर" <?php echo $data['grampanchayat'] === 'सिद्धेश्वर' ? 'selected' : ''; ?>>सिद्धेश्वर</option>
                    <option value="सिरळा" <?php echo $data['grampanchayat'] === 'सिरळा' ? 'selected' : ''; ?>>सिरळा</option>
                    <option value="सोनवाडी" <?php echo $data['grampanchayat'] === 'सोनवाडी' ? 'selected' : ''; ?>>सोनवाडी</option>
                    <option value="सुकापूर" <?php echo $data['grampanchayat'] === 'सुकापूर' ? 'selected' : ''; ?>>सुकापूर</option>
                    <option value="सुरेगाव" <?php echo $data['grampanchayat'] === 'सुरेगाव' ? 'selected' : ''; ?>>सुरेगाव</option>
                    <option value="सुरवाडी" <?php echo $data['grampanchayat'] === 'सुरवाडी' ? 'selected' : ''; ?>>सुरवाडी</option>
                    <option value="टाकलगव्हन" <?php echo $data['grampanchayat'] === 'टाकलगव्हन' ? 'selected' : ''; ?>>टाकलगव्हन</option>
                    <option value="तामटी तांडा" <?php echo $data['grampanchayat'] === 'तामटी तांडा' ? 'selected' : ''; ?>>तामटी तांडा</option>
                    <option value="तपोवन" <?php echo $data['grampanchayat'] === 'तपोवन' ? 'selected' : ''; ?>>तपोवन</option>
                    <option value="उखळी" <?php echo $data['grampanchayat'] === 'उखळी' ? 'selected' : ''; ?>>उखळी</option>
                    <option value="उमरा" <?php echo $data['grampanchayat'] === 'उमरा' ? 'selected' : ''; ?>>उमरा</option>
                    <option value="उंडेगाव" <?php echo $data['grampanchayat'] === 'उंडेगाव' ? 'selected' : ''; ?>>उंडेगाव</option>
                    <option value="वडद" <?php echo $data['grampanchayat'] === 'वडद' ? 'selected' : ''; ?>>वडद</option>
                    <option value="वडचुना" <?php echo $data['grampanchayat'] === 'वडचुना' ? 'selected' : ''; ?>>वडचुना</option>
                    <option value="वागरवाडी" <?php echo $data['grampanchayat'] === 'वागरवाडी' ? 'selected' : ''; ?>>वागरवाडी</option>
                    <option value="वागरवाडी तांडा" <?php echo $data['grampanchayat'] === 'वागरवाडी तांडा' ? 'selected' : ''; ?>>वागरवाडी तांडा</option>
                    <option value="वळकी" <?php echo $data['grampanchayat'] === 'वळकी' ? 'selected' : ''; ?>>वळकी</option>
                    <option value="वासई" <?php echo $data['grampanchayat'] === 'वासई' ? 'selected' : ''; ?>>वासई</option>
                    <option value="येड़ुत" <?php echo $data['grampanchayat'] === 'येड़ुत' ? 'selected' : ''; ?>>येड़ुत</option>
                    <option value="येहळेगाव" <?php echo $data['grampanchayat'] === 'येहळेगाव' ? 'selected' : ''; ?>>येहळेगाव</option>
                    <option value="बोरजा" <?php echo $data['grampanchayat'] === 'बोरजा' ? 'selected' : ''; ?>>बोरजा</option>
                    <option value="ब्राह्मणवाडा" <?php echo $data['grampanchayat'] === 'ब्राह्मणवाडा' ? 'selected' : ''; ?>>ब्राह्मणवाडा</option>
                    <option value="राजापूर" <?php echo $data['grampanchayat'] === 'राजापूर' ? 'selected' : ''; ?>>राजापूर</option>
                    <option value="रामेश्वर" <?php echo $data['grampanchayat'] === 'रामेश्वर' ? 'selected' : ''; ?>>रामेश्वर</option>
                    <option value="रुपूर" <?php echo $data['grampanchayat'] === 'रुपूर' ? 'selected' : ''; ?>>रुपूर</option>
                    <option value="सावरखेडा" <?php echo $data['grampanchayat'] === 'सावरखेडा' ? 'selected' : ''; ?>>सावरखेडा</option>
                    <option value="येळी" <?php echo $data['grampanchayat'] === 'येळी' ? 'selected' : ''; ?>>येळी</option>
                    <option value="बाभुळगाव" <?php echo $data['grampanchayat'] === 'बाभुळगाव' ? 'selected' : ''; ?>>बाभुळगाव</option>
                    <option value="बोराळा" <?php echo $data['grampanchayat'] === 'बोराळा' ? 'selected' : ''; ?>>बोराळा</option>
                    <option value="आडगाव" <?php echo $data['grampanchayat'] === 'आडगाव' ? 'selected' : ''; ?>>आडगाव</option>
                    <option value="आखरुखा" <?php echo $data['grampanchayat'] === 'आखरुखा' ? 'selected' : ''; ?>>आखरुखा</option>
                    <option value="अकोली" <?php echo $data['grampanchayat'] === 'अकोली' ? 'selected' : ''; ?>>अकोली</option>
                    <option value="अंबा" <?php echo $data['grampanchayat'] === 'अंबा' ? 'selected' : ''; ?>>अंबा</option>
                    <option value="अराळ" <?php echo $data['grampanchayat'] === 'अराळ' ? 'selected' : ''; ?>>अराळ</option>
                    <option value="असेगाव" <?php echo $data['grampanchayat'] === 'असेगाव' ? 'selected' : ''; ?>>असेगाव</option>
                    <option value="बाळेगाव" <?php echo $data['grampanchayat'] === 'बाळेगाव' ? 'selected' : ''; ?>>बाळेगाव</option>
                    <option value="भेंडेगाव" <?php echo $data['grampanchayat'] === 'भेंडेगाव' ? 'selected' : ''; ?>>भेंडेगाव</option>
                    <option value="भोगावन" <?php echo $data['grampanchayat'] === 'भोगावन' ? 'selected' : ''; ?>>भोगावन</option>
                    <option value="भोरिपगाव" <?php echo $data['grampanchayat'] === 'भोरिपगाव' ? 'selected' : ''; ?>>भोरिपगाव</option>
                    <option value="बोरगाव" <?php echo $data['grampanchayat'] === 'बोरगाव' ? 'selected' : ''; ?>>बोरगाव</option>
                    <option value="बोरगाव (बु)" <?php echo $data['grampanchayat'] === 'बोरगाव (बु)' ? 'selected' : ''; ?>>बोरगाव (बु)</option>
                    <option value="बोरीसावंत" <?php echo $data['grampanchayat'] === 'बोरीसावंत' ? 'selected' : ''; ?>>बोरीसावंत</option>
                    <option value="ब्रह्मणगाव बु." <?php echo $data['grampanchayat'] === 'ब्रह्मणगाव बु.' ? 'selected' : ''; ?>>ब्रह्मणगाव बु.</option>
                    <option value="चिखली" <?php echo $data['grampanchayat'] === 'चिखली' ? 'selected' : ''; ?>>चिखली</option>
                    <option value="चोंडी" <?php echo $data['grampanchayat'] === 'चोंडी' ? 'selected' : ''; ?>>चोंडी</option>
                    <option value="दगडगाव" <?php echo $data['grampanchayat'] === 'दगडगाव' ? 'selected' : ''; ?>>दगडगाव</option>
                    <option value="दगापिंपरी" <?php echo $data['grampanchayat'] === 'दगापिंपरी' ? 'selected' : ''; ?>>दगापिंपरी</option>
                    <option value="दरेफळ" <?php echo $data['grampanchayat'] === 'दरेफळ' ? 'selected' : ''; ?>>दरेफळ</option>
                    <option value="धाभडी" <?php echo $data['grampanchayat'] === 'धाभडी' ? 'selected' : ''; ?>>धाभडी</option>
                    <option value="धामणगाव" <?php echo $data['grampanchayat'] === 'धामणगाव' ? 'selected' : ''; ?>>धामणगाव</option>
                    <option value="धनोडा" <?php echo $data['grampanchayat'] === 'धनोडा' ? 'selected' : ''; ?>>धनोडा</option>
                    <option value="धौलगाव" <?php echo $data['grampanchayat'] === 'धौलगाव' ? 'selected' : ''; ?>>धौलगाव</option>
                    <option value="डिग्रस" <?php echo $data['grampanchayat'] === 'डिग्रस' ? 'selected' : ''; ?>>डिग्रस</option>
                    <option value="डोंवाडा" <?php echo $data['grampanchayat'] === 'डोंवाडा' ? 'selected' : ''; ?>>डोंवाडा</option>
                    <option value="एंजणगाव" <?php echo $data['grampanchayat'] === 'एंजणगाव' ? 'selected' : ''; ?>>एंजणगाव</option>
                    <option value="गिरगाव" <?php echo $data['grampanchayat'] === 'गिरगाव' ? 'selected' : ''; ?>>गिरगाव</option>
                    <option value="गुंडा" <?php echo $data['grampanchayat'] === 'गुंडा' ? 'selected' : ''; ?>>गुंडा</option>
                    <option value="गुंज" <?php echo $data['grampanchayat'] === 'गुंज' ? 'selected' : ''; ?>>गुंज</option>
                    <option value="हापसापूर" <?php echo $data['grampanchayat'] === 'हापसापूर' ? 'selected' : ''; ?>>हापसापूर</option>
                    <option value="गणेशपूर" <?php echo $data['grampanchayat'] === 'गणेशपूर' ? 'selected' : ''; ?>>गणेशपूर</option>
                    <option value="हयातनगर" <?php echo $data['grampanchayat'] === 'हयातनगर' ? 'selected' : ''; ?>>हयातनगर</option>
                    <option value="हिरडगाव" <?php echo $data['grampanchayat'] === 'हिरडगाव' ? 'selected' : ''; ?>>हिरडगाव</option>
                    <option value="हिवर (खं)" <?php echo $data['grampanchayat'] === 'हिवर (खं)' ? 'selected' : ''; ?>>हिवर (खं)</option>
                    <option value="जवळा (बु)" <?php echo $data['grampanchayat'] === 'जवळा (बु)' ? 'selected' : ''; ?>>जवळा (बु)</option>
                    <option value="जवलाट-बाभुळगाव" <?php echo $data['grampanchayat'] === 'जवलाट-बाभुळगाव' ? 'selected' : ''; ?>>जवलाट-बाभुळगाव</option>
                    <option value="जुन्नुना" <?php echo $data['grampanchayat'] === 'जुन्नुना' ? 'selected' : ''; ?>>जुन्नुना</option>
                    <option value="कगबन" <?php echo $data['grampanchayat'] === 'कगबन' ? 'selected' : ''; ?>>कगबन</option>
                    <option value="कळांबा" <?php echo $data['grampanchayat'] === 'कळांबा' ? 'selected' : ''; ?>>कळांबा</option>
                    <option value="कन्हेरगाव" <?php echo $data['grampanchayat'] === 'कन्हेरगाव' ? 'selected' : ''; ?>>कन्हेरगाव</option>
                    <option value="कारंजी" <?php echo $data['grampanchayat'] === 'कारंजी' ? 'selected' : ''; ?>>कारंजी</option>
                    <option value="हट्टा" <?php echo $data['grampanchayat'] === 'हट्टा' ? 'selected' : ''; ?>>हट्टा</option>
                    <option value="खांडेगाव" <?php echo $data['grampanchayat'] === 'खांडेगाव' ? 'selected' : ''; ?>>खांडेगाव</option>
                    <option value="खुडनापूर" <?php echo $data['grampanchayat'] === 'खुडनापूर' ? 'selected' : ''; ?>>खुडनापूर</option>
                    <option value="किण्होलाज" <?php echo $data['grampanchayat'] === 'किण्होलाज' ? 'selected' : ''; ?>>किण्होलाज</option>
                    <option value="कोनसा" <?php echo $data['grampanchayat'] === 'कोनसा' ? 'selected' : ''; ?>>कोनसा</option>
                    <option value="कोनाथा" <?php echo $data['grampanchayat'] === 'कोनाथा' ? 'selected' : ''; ?>>कोनाथा</option>
                    <option value="कोर्ता" <?php echo $data['grampanchayat'] === 'कोर्ता' ? 'selected' : ''; ?>>कोर्ता</option>
                    <option value="कोठारी" <?php echo $data['grampanchayat'] === 'कोठारी' ? 'selected' : ''; ?>>कोठारी</option>
                    <option value="कोउडगाव" <?php echo $data['grampanchayat'] === 'कोउडगाव' ? 'selected' : ''; ?>>कोउडगाव</option>
                    <option value="कुडाळा" <?php echo $data['grampanchayat'] === 'कुडाळा' ? 'selected' : ''; ?>>कुडाळा</option>
                    <option value="करंजाळा" <?php echo $data['grampanchayat'] === 'करंजाळा' ? 'selected' : ''; ?>>करंजाळा</option>
                    <option value="कुरंडवाडी" <?php echo $data['grampanchayat'] === 'कुरंडवाडी' ? 'selected' : ''; ?>>कुरंडवाडी</option>
                    <option value="कुरुंडा" <?php echo $data['grampanchayat'] === 'कुरुंडा' ? 'selected' : ''; ?>>कुरुंडा</option>
                    <option value="लहान" <?php echo $data['grampanchayat'] === 'लहान' ? 'selected' : ''; ?>>लहान</option>
                    <option value="लिंगी" <?php echo $data['grampanchayat'] === 'लिंगी' ? 'selected' : ''; ?>>लिंगी</option>
                    <option value="लोण (बु)" <?php echo $data['grampanchayat'] === 'लोण (बु)' ? 'selected' : ''; ?>>लोण (बु)</option>
                    <option value="लोलेश्वर" <?php echo $data['grampanchayat'] === 'लोलेश्वर' ? 'selected' : ''; ?>>लोलेश्वर</option>
                    <option value="महागाव" <?php echo $data['grampanchayat'] === 'महागाव' ? 'selected' : ''; ?>>महागाव</option>
                    <option value="महमदपूरवाडी" <?php echo $data['grampanchayat'] === 'महमदपूरवाडी' ? 'selected' : ''; ?>>महमदपूरवाडी</option>
                    <option value="मालवटा" <?php echo $data['grampanchayat'] === 'मालवटा' ? 'selected' : ''; ?>>मालवटा</option>
                    <option value="मारळपूर" <?php echo $data['grampanchayat'] === 'मारळपूर' ? 'selected' : ''; ?>>मारळपूर</option>
                    <option value="मारसूल" <?php echo $data['grampanchayat'] === 'मारसूल' ? 'selected' : ''; ?>>मारसूल</option>
                    <option value="मटेगाव" <?php echo $data['grampanchayat'] === 'मटेगाव' ? 'selected' : ''; ?>>मटेगाव</option>
                    <option value="माथारगाव" <?php echo $data['grampanchayat'] === 'माथारगाव' ? 'selected' : ''; ?>>माथारगाव</option>
                    <option value="मोहोळगाव" <?php echo $data['grampanchayat'] === 'मोहोळगाव' ? 'selected' : ''; ?>>मोहोळगाव</option>
                    <option value="मुढी" <?php echo $data['grampanchayat'] === 'मुढी' ? 'selected' : ''; ?>>मुढी</option>
                    <option value="मुरुंबा (बु)" <?php echo $data['grampanchayat'] === 'मुरुंबा (बु)' ? 'selected' : ''; ?>>मुरुंबा (बु)</option>
                    <option value="नाहाड" <?php echo $data['grampanchayat'] === 'नाहाड' ? 'selected' : ''; ?>>नाहाड</option>
                    <option value="पालसगाव" <?php echo $data['grampanchayat'] === 'पालसगाव' ? 'selected' : ''; ?>>पालسगाव</option>
                    <option value="पांगरा" <?php echo $data['grampanchayat'] === 'पांगरा' ? 'selected' : ''; ?>>पांगरा</option>
                    <option value="पांग्रासाती" <?php echo $data['grampanchayat'] === 'पांग्रासाती' ? 'selected' : ''; ?>>पांग्रासाती</option>
                    <option value="पांग्राशिंदे" <?php echo $data['grampanchayat'] === 'पांग्राशिंदे' ? 'selected' : ''; ?>>পांग्राशिंदे</option>
                    <option value="पर्डी (बु)" <?php echo $data['grampanchayat'] === 'पर्डी (बु)' ? 'selected' : ''; ?>>पर्डी (बु)</option>
                    <option value="पर्डी (खं)" <?php echo $data['grampanchayat'] === 'पर्डी (खं)' ? 'selected' : ''; ?>>पर्डी (खं)</option>
                    <option value="परजना" <?php echo $data['grampanchayat'] === 'परजना' ? 'selected' : ''; ?>>परजना</option>
                    <option value="परळी" <?php echo $data['grampanchayat'] === 'परळी' ? 'selected' : ''; ?>>परळी</option>
                    <option value="परवा" <?php echo $data['grampanchayat'] === 'परवा' ? 'selected' : ''; ?>>परवा</option>
                    <option value="पिंपळगाव" <?php echo $data['grampanchayat'] === 'पिंपळगाव' ? 'selected' : ''; ?>>पिंपळगाव</option>
                    <option value="कवठा" <?php echo $data['grampanchayat'] === 'कवठा' ? 'selected' : ''; ?>>कवठा</option>
                    <option value="पिंप्राळा" <?php echo $data['grampanchayat'] === 'पिंप्राळा' ? 'selected' : ''; ?>>पिंप्राळा</option>
                    <option value="पुयणी (बु)" <?php echo $data['grampanchayat'] === 'पुयणी (बु)' ? 'selected' : ''; ?>>पुयणी (बु)</option>
                    <option value="पुयणी (खं)" <?php echo $data['grampanchayat'] === 'पुयणी (खं)' ? 'selected' : ''; ?>>पुयणी (खं)</option>
                    <option value="रायवाडी" <?php echo $data['grampanchayat'] === 'रायवाडी' ? 'selected' : ''; ?>>रायवाडी</option>
                    <option value="राजवाडी" <?php echo $data['grampanchayat'] === 'राजवाडी' ? 'selected' : ''; ?>>राजवाडी</option>
                    <option value="रंजना" <?php echo $data['grampanchayat'] === 'रंजना' ? 'selected' : ''; ?>>रंजना</option>
                    <option value="रेणकपूर" <?php echo $data['grampanchayat'] === 'रेणकपूर' ? 'selected' : ''; ?>>रेणकपूर</option>
                    <option value="रेऊलगाव" <?php echo $data['grampanchayat'] === 'रेऊलगाव' ? 'selected' : ''; ?>>रेऊलगाव</option>
                    <option value="खांबाळा" <?php echo $data['grampanchayat'] === 'खांबाळा' ? 'selected' : ''; ?>>खांबाळा</option>
                    <option value="रोडगा" <?php echo $data['grampanchayat'] === 'रोडगा' ? 'selected' : ''; ?>>रोडगा</option>
                    <option value="सरोळे" <?php echo $data['grampanchayat'] === 'सरोळे' ? 'selected' : ''; ?>>सरोळे</option>
                    <option value="सातेफळ" <?php echo $data['grampanchayat'] === 'सातेफळ' ? 'selected' : ''; ?>>सातेफळ</option>
                    <option value="सावंगी (बु)" <?php echo $data['grampanchayat'] === 'सावंगी (बु)' ? 'selected' : ''; ?>>सावंगी (बु)</option>
                    <option value="सेलू" <?php echo $data['grampanchayat'] === 'सेलू' ? 'selected' : ''; ?>>सेलू</option>
                    <option value="शिंडी" <?php echo $data['grampanchayat'] === 'शिंडी' ? 'selected' : ''; ?>>शिंडी</option>
                    <option value="सिरळी" <?php echo $data['grampanchayat'] === 'सिरळी' ? 'selected' : ''; ?>>सिरळी</option>
                </select>
            </div>
        </div>

        <div class="user-row">
            <div class="user-col">
                <label for="taluka"><i class="fa-solid fa-map-location-dot"></i> Taluka</label>
                <select id="taluka" name="taluka">
                    <option value="">-- निवडा तालुका --</option>
                    <option value="औंढा नागनाथ" <?php echo $data['taluka'] === 'औंढा नागनाथ' ? 'selected' : ''; ?>>औंढा नागनाथ</option>
                    <option value="बसमत" <?php echo $data['taluka'] === 'बसमत' ? 'selected' : ''; ?>>बसमत</option>
                    <option value="हिंगोली" <?php echo $data['taluka'] === 'हिंगोली' ? 'selected' : ''; ?>>हिंगोली</option>
                    <option value="कळमनुरी" <?php echo $data['taluka'] === 'कळमनुरी' ? 'selected' : ''; ?>>कळमनुरी</option>
                    <option value="सेनगांव" <?php echo $data['taluka'] === 'सेनगांव' ? 'selected' : ''; ?>>सेनगांव</option>
                </select>
            </div>
            <div class="user-col">
                <label for="mobile"><i class="fa-solid fa-phone"></i> Mobile no</label>
                <input id="mobile" name="mobile" type="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="10 digits" value="<?php echo htmlspecialchars($data['mobile']); ?>" />
            </div>
        </div>

        <div class="user-row">
            <div class="user-col">
                <label for="username"><i class="fa-solid fa-user-gear"></i> Username</label>
                <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($data['username']); ?>" />
            </div>
            <div class="user-col password-wrapper">
                <label for="password"><i class="fa-solid fa-key"></i> Password</label>
                <input id="password" name="password" type="password" value="" />
                <button type="button" class="toggle-password" aria-label="Press and hold to show password">👁️</button>
            </div>
        </div>

        <div class="user-row">
            <div class="user-col">
                <label for="system_role"><i class="fa-solid fa-shield-halved"></i> System Role</label>
                <select id="system_role" name="system_role">
                    <option value="">-- Select --</option>
                    <option value="admin" <?php echo $data['system_role']==='admin'?'selected':''; ?>>Admin</option>
                    <option value="user" <?php echo $data['system_role']==='user'?'selected':''; ?>>User</option>
                </select>
            </div>
            <div class="user-col">
                <label for="role"><i class="fa-solid fa-id-badge"></i> Role</label>
                <input id="role" name="role" type="text" value="<?php echo htmlspecialchars($data['role']); ?>" />
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn">Save</button>
        </div>
    </form>

    <script>
        document.getElementById('mobile').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        const passwordInput = document.getElementById('password');
        const passwordButton = document.querySelector('.toggle-password');

        function showPassword() {
            passwordInput.type = 'text';
            passwordButton.textContent = '🙈';
            passwordButton.setAttribute('aria-label', 'Release to hide password');
        }

        function hidePassword() {
            passwordInput.type = 'password';
            passwordButton.textContent = '👁️';
            passwordButton.setAttribute('aria-label', 'Press and hold to show password');
        }

        passwordButton.addEventListener('mousedown', function(e) {
            e.preventDefault();
            showPassword();
        });

        passwordButton.addEventListener('mouseup', function() {
            hidePassword();
        });

        passwordButton.addEventListener('mouseleave', function() {
            hidePassword();
        });

        passwordButton.addEventListener('touchstart', function(e) {
            e.preventDefault();
            showPassword();
        }, { passive: false });

        passwordButton.addEventListener('touchend', function() {
            hidePassword();
        });

        passwordButton.addEventListener('touchcancel', function() {
            hidePassword();
        });
    </script>

    <?php endif; ?>

</div>

<?php
// include footer if exists
if (file_exists(__DIR__ . '/include/footer.php')) include __DIR__ . '/include/footer.php';
?>
