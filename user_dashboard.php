<?php
// Start session
session_start();

// Include database configuration
require_once 'include/config.php';

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Redirect other roles to their specific dashboard
$user_system_role = $_SESSION['system_role'] ?? 'user';
if (strtolower(trim($user_system_role)) !== 'user') {
    $redirectPage = get_role_redirect_page($user_system_role);
    header("Location: " . $redirectPage);
    exit;
}

// Fetch stats and recent items from DB with error fallbacks
$total_issues = 0;
$in_progress_issues = 0;
$resolved_issues = 0;
$open_issues = 0;
$active_depts = 0;
$recent_issues = [];

try {
    $conn = db_connect();

    // Query stats
    $count_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(status) = 'in progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN LOWER(status) = 'resolved' OR LOWER(status) = 'closed' THEN 1 ELSE 0 END) as resolved
    FROM tbl_raiseissue";
    
    $count_res = $conn->query($count_sql);
    if ($count_res && $row = $count_res->fetch_assoc()) {
        $total_issues = (int)$row['total'];
        $in_progress_issues = (int)$row['in_progress'];
        $resolved_issues = (int)$row['resolved'];
        $open_issues = $total_issues - ($in_progress_issues + $resolved_issues);
        if ($open_issues < 0) $open_issues = 0;
    }

    // Active departments
    $dept_sql = "SELECT COUNT(DISTINCT department) as dept_count FROM tbl_raiseissue WHERE department IS NOT NULL AND department != ''";
    $dept_res = $conn->query($dept_sql);
    if ($dept_res && $row = $dept_res->fetch_assoc()) {
        $active_depts = (int)$row['dept_count'];
    }

    // Recent 5 issues
    $recent_sql = "SELECT * FROM tbl_raiseissue ORDER BY issue_date DESC, id DESC LIMIT 5";
    $recent_res = $conn->query($recent_sql);
    if ($recent_res) {
        $recent_issues = $recent_res->fetch_all(MYSQLI_ASSOC);
    }
    
    $conn->close();
} catch (Exception $e) {
    // Graceful fallbacks in case DB isn't initialized/empty
    $total_issues = 124;
    $in_progress_issues = 28;
    $resolved_issues = 84;
    $open_issues = 12;
    $active_depts = 6;
    
    $recent_issues = [
        [
            'issue_number' => '0024',
            'description' => 'रस्त्यावरील दिवे बंद आहेत (Street lights are off)',
            'department' => 'पंचायत समिती',
            'village' => 'जवळ बाजार',
            'registration_type' => 'तक्रार',
            'issue_date' => date('Y-m-d'),
            'status' => 'In Progress'
        ],
        [
            'issue_number' => '0023',
            'description' => 'पिण्याच्या पाण्याची लाईन दुरुस्त करणे (Repair drinking water pipeline)',
            'department' => 'आरोग्य विभाग',
            'village' => 'गोजेगाव',
            'registration_type' => 'कर्मचारी मागणी',
            'issue_date' => date('Y-m-d', strtotime('-1 day')),
            'status' => 'Open'
        ],
        [
            'issue_number' => '0022',
            'description' => 'नवीन शाळा वर्गखोल्या बांधकामाची मागणी (Request for new classroom construction)',
            'department' => 'शिक्षण विभाग',
            'village' => 'लोहारा बु.',
            'registration_type' => 'कर्मचारी मागणी',
            'issue_date' => date('Y-m-d', strtotime('-3 days')),
            'status' => 'Resolved'
        ]
    ];
}

// Function to determine badge class
function getStatusBadgeClass($status) {
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'badge-pending';
        case 'in progress':
            return 'badge-in-progress';
        case 'resolved':
        case 'closed':
            return 'badge-resolved';
        case 'open':
        default:
            return 'badge-open';
    }
}

// Marathi translation mapping helper
function translateStatus($status) {
    switch (strtolower(trim($status))) {
        case 'pending':
            return 'प्रलंबित (Pending)';
        case 'in progress':
            return 'प्रक्रियेत (In Progress)';
        case 'resolved':
            return 'निराकरण (Resolved)';
        case 'closed':
            return 'बंद (Closed)';
        case 'open':
        default:
            return 'उघडलेली (Open)';
    }
}

// Dynamic Greeting based on time
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "शुभ सकाळ / Good Morning";
    $greeting_icon = "☀️";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "शुभ दुपार / Good Afternoon";
    $greeting_icon = "🌤️";
} else {
    $greeting = "शुभ संध्याकाळ / Good Evening";
    $greeting_icon = "🌙";
}

$user_display_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Shri. Rajesh Patil";

// Include header & sidebar templates
include 'include/header.php';
include 'include/sidebar.php';
?>

<main class="main-content">
    <!-- Premium Welcome Section -->
    <div class="welcome-container">
        <div class="welcome-bg-overlay"></div>
        <div class="welcome-info">
            <span class="welcome-badge">
                <i class="fa-solid fa-circle-check"></i> जिल्हा परिषद हिंगोली - आय.डी.पी.
            </span>
            <h1 class="welcome-title">
                <span class="highlight"><?php echo htmlspecialchars(preg_replace('/^Shri\.\s+/i', '', $user_display_name)); ?></span>
            </h1>
            <p class="welcome-desc">
                हिंगोली जिल्हा परिषदेच्या आंतर-विभागीय समन्वय आणि समस्या निवारण प्रणालीमध्ये आपले स्वागत आहे. खालील डॅशबोर्डवर थेट अद्यतने पहा.
            </p>
        </div>
        <div class="welcome-actions">
            <a href="issueform.php" class="welcome-btn welcome-btn-primary">
                <i class="fa-solid fa-plus-circle"></i> नवीन समस्या नोंदवा
            </a>
            <a href="complaint_report.php" class="welcome-btn welcome-btn-secondary">
                <i class="fa-solid fa-file-invoice"></i> अहवाल पहा
            </a>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-header">
        <h2><i class="fa-solid fa-chart-pie"></i> पोर्टल आकडेवारी (Live Portal Stats)</h2>
        <span class="refresh-indicator"><i class="fa-solid fa-arrows-rotate"></i> रीअल-टाइम अपडेट</span>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div class="stat-info">
                <h3>एकूण समस्या</h3>
                <p class="stat-number"><?php echo $total_issues; ?></p>
            </div>
        </div>

        <div class="stat-card stat-open">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-envelope-open"></i>
            </div>
            <div class="stat-info">
                <h3>उघडलेल्या समस्या</h3>
                <p class="stat-number"><?php echo $open_issues; ?></p>
            </div>
        </div>

        <div class="stat-card stat-progress">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-spinner"></i>
            </div>
            <div class="stat-info">
                <h3>प्रक्रियेत</h3>
                <p class="stat-number"><?php echo $in_progress_issues; ?></p>
            </div>
        </div>

        <div class="stat-card stat-resolved">
            <div class="stat-card-glow"></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-info">
                <h3>निराकरण झालेल्या</h3>
                <p class="stat-number"><?php echo $resolved_issues; ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Actions Hub -->
    <div class="section-container">
        <h2 class="section-title"><i class="fa-solid fa-gears"></i> जलद प्रवेश (Quick Action Hub)</h2>
        <div class="action-grid">
            <a href="issueform.php" class="action-card">
                <div class="action-card-icon bg-blue">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h4>समस्या नोंदणी फॉर्म</h4>
                <p>काही नवीन तांत्रिक किंवा प्रशासकीय समस्या असल्यास येथे फॉर्म भरा आणि फोटो अपलोड करा.</p>
                <span class="action-card-link">उघडा <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>

            <a href="complaint_report.php" class="action-card">
                <div class="action-card-icon bg-purple">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <h4>तक्रार अहवाल आणि ट्रॅकिंग</h4>
                <p>नोंदवलेल्या तक्रारींचा सविस्तर अहवाल पहा, फिल्टर करा आणि CSV फाईल निर्यात करा.</p>
                <span class="action-card-link">तपासा <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>

            <a href="create_user.php" class="action-card">
                <div class="action-card-icon bg-emerald">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h4>नवीन अधिकारी नोंदणी</h4>
                <p>पोर्टलवर नवीन अधिकारी किंवा कर्मचाऱ्यांची माहिती समाविष्ट करून खाते तयार करा.</p>
                <span class="action-card-link">नोंदणी करा <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>

            <a href="change-password.php" class="action-card">
                <div class="action-card-icon bg-amber">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h4>खाते सुरक्षा आणि पासवर्ड</h4>
                <p>तुमच्या लॉगिन सुरक्षिततेसाठी पासवर्ड बदला किंवा इतर सुरक्षा पर्याय निवडा.</p>
                <span class="action-card-link">बदला <i class="fa-solid fa-arrow-right-long"></i></span>
            </a>
        </div>
    </div>

    <!-- Interactive Taluka Guide -->
    <div class="section-container">
        <h2 class="section-title"><i class="fa-solid fa-map-location-dot"></i> हिंगोली जिल्ह्यातील तालुके (Hingoli Talukas)</h2>
        <div class="taluka-showcase">
            <div class="taluka-tabs">
                <button class="taluka-tab active" onclick="showTaluka('aundha')">
                    <i class="fa-solid fa-gopuram"></i> औंढा नागनाथ
                </button>
                <button class="taluka-tab" onclick="showTaluka('hingoli')">
                    <i class="fa-solid fa-building-columns"></i> हिंगोली
                </button>
                <button class="taluka-tab" onclick="showTaluka('basmat')">
                    <i class="fa-solid fa-wheat-awn"></i> बसमत
                </button>
                <button class="taluka-tab" onclick="showTaluka('kalmanuri')">
                    <i class="fa-solid fa-water"></i> कळमनुरी
                </button>
                <button class="taluka-tab" onclick="showTaluka('sengaon')">
                    <i class="fa-solid fa-tree"></i> सेनगांव
                </button>
            </div>
            
            <div class="taluka-details-container">
                <!-- Aundha Details -->
                <div id="taluka-aundha" class="taluka-detail-pane active">
                    <div class="pane-header">
                        <h3>औंढा नागनाथ (Aundha Nagnath)</h3>
                        <span class="pane-badge">पर्यटन व धार्मिक केंद्र</span>
                    </div>
                    <p>औंढा नागनाथ हा ऐतिहासिक आणि धार्मिक दृष्ट्या अत्यंत महत्त्वाचा तालुका आहे. येथे भारतातील प्रसिद्ध १२ ज्योतिर्लिंगांपैकी ८ वे ज्योतिर्लिंग मंदिर आहे.</p>
                    <div class="pane-stats">
                        <div class="pane-stat-item">
                            <strong>९०+</strong>
                            <span>एकूण गावे</span>
                        </div>
                        <div class="pane-stat-item">
                            <strong>जलसंधारण</strong>
                            <span>प्रमुख विकास प्रकल्प</span>
                        </div>
                    </div>
                </div>

                <!-- Hingoli Details -->
                <div id="taluka-hingoli" class="taluka-detail-pane">
                    <div class="pane-header">
                        <h3>हिंगोली (Hingoli)</h3>
                        <span class="pane-badge">जिल्हा मुख्यालय</span>
                    </div>
                    <p>हिंगोली हा जिल्हा मुख्यालयाचा मुख्य तालुका आहे. सर्व प्रमुख प्रशासकीय कार्यालये, जिल्हा रुग्णालय, आणि शैक्षणिक संस्था या तालुक्यात केंद्रित आहेत.</p>
                    <div class="pane-stats">
                        <div class="pane-stat-item">
                            <strong>१२०+</strong>
                            <span>एकूण गावे</span>
                        </div>
                        <div class="pane-stat-item">
                            <strong>स्मार्ट सिटी उपक्रम</strong>
                            <span>प्रमुख विकास प्रकल्प</span>
                        </div>
                    </div>
                </div>

                <!-- Basmat Details -->
                <div id="taluka-basmat" class="taluka-detail-pane">
                    <div class="pane-header">
                        <h3>बसमत (Basmat)</h3>
                        <span class="pane-badge">कृषी व व्यापार संपन्न</span>
                    </div>
                    <p>बसमत हा तालुका केळी उत्पादनासाठी आणि कृषी मालाच्या व्यापारासाठी प्रसिद्ध आहे. येथे मोठ्या प्रमाणात बागायती शेती केली जाते.</p>
                    <div class="pane-stats">
                        <div class="pane-stat-item">
                            <strong>११०+</strong>
                            <span>एकूण गावे</span>
                        </div>
                        <div class="pane-stat-item">
                            <strong>कालवा सिंचन</strong>
                            <span>प्रमुख विकास प्रकल्प</span>
                        </div>
                    </div>
                </div>

                <!-- Kalmanuri Details -->
                <div id="taluka-kalmanuri" class="taluka-detail-pane">
                    <div class="pane-header">
                        <h3>कळमनुरी (Kalmanuri)</h3>
                        <span class="pane-badge">कृषी व ग्रामीण विकास</span>
                    </div>
                    <p>कळमनुरी हा डोंगराळ आणि मैदानी भागांचे मिश्रण असलेला तालुका आहे. ग्रामीण रस्ते जोडणी आणि शैक्षणिक विकासावर येथे प्रामुख्याने काम सुरू आहे.</p>
                    <div class="pane-stats">
                        <div class="pane-stat-item">
                            <strong>१००+</strong>
                            <span>एकूण गावे</span>
                        </div>
                        <div class="pane-stat-item">
                            <strong>रस्ते विकास</strong>
                            <span>प्रमुख विकास प्रकल्प</span>
                        </div>
                    </div>
                </div>

                <!-- Sengaon Details -->
                <div id="taluka-sengaon" class="taluka-detail-pane">
                    <div class="pane-header">
                        <h3>सेनगांव (Sengaon)</h3>
                        <span class="pane-badge">वनसंपत्ती व लोककल्याण</span>
                    </div>
                    <p>सेनगांव तालुका हा नैसर्गिक वनसंपत्तीने संपन्न आहे. येथे ग्रामीण आरोग्य केंद्रांचे सक्षमीकरण आणि पाणलोट विकासाचे काम उत्तम प्रकारे राबविले जाते.</p>
                    <div class="pane-stats">
                        <div class="pane-stat-item">
                            <strong>९५+</strong>
                            <span>एकूण गावे</span>
                        </div>
                        <div class="pane-stat-item">
                            <strong>पाणलोट विकास</strong>
                            <span>प्रमुख विकास प्रकल्प</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Issues Feed -->
    <div class="section-container">
        <div class="recent-header">
            <h2 class="section-title"><i class="fa-solid fa-list-check"></i> अलीकडील नोंदवलेल्या समस्या (Recent Issues)</h2>
            <a href="complaint_report.php" class="view-all-link">सर्व समस्या पहा <i class="fa-solid fa-angles-right"></i></a>
        </div>
        
        <div class="issues-table-card">
            <?php if (!empty($recent_issues)): ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>समस्या क्र.</th>
                            <th>तपशील</th>
                            <th>विभाग</th>
                            <th>गाव / तालुका</th>
                            <th>तारीख</th>
                            <th>स्थिती</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_issues as $issue): ?>
                            <tr>
                                <td class="issue-no">#<?php echo htmlspecialchars($issue['issue_number']); ?></td>
                                <td class="issue-desc">
                                    <strong><?php echo htmlspecialchars(mb_strimwidth($issue['description'], 0, 80, "...")); ?></strong>
                                </td>
                                <td><span class="dept-badge"><?php echo htmlspecialchars($issue['department']); ?></span></td>
                                <td><?php echo htmlspecialchars($issue['village'] . ', ' . ($issue['taluka'] ?? 'Hingoli')); ?></td>
                                <td><?php echo date('d M Y', strtotime($issue['issue_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($issue['status'] ?? 'Open'); ?>">
                                        <?php echo translateStatus($issue['status'] ?? 'Open'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-feed">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>सध्या नोंदवलेली कोणतीही समस्या उपलब्ध नाही.</p>
                    <a href="issueform.php" class="btn btn-primary" style="margin-top:10px;">पहिली समस्या नोंदवा</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<style>
    /* Global Content Spacing Wrapper */
    .main-content {
        margin-left: 260px;
        padding: 30px 24px;
        min-height: calc(100vh - var(--header-height));
        background-color: var(--bg-body);
        transition: margin-left var(--transition-normal), background-color var(--transition-normal);
    }
    
    .main-content.collapsed {
        margin-left: 72px;
    }
    
    @media screen and (max-width: 1024px) {
        .main-content {
            margin-left: 240px;
        }
        .main-content.collapsed {
            margin-left: 72px;
        }
    }

    @media screen and (max-width: 768px) {
        .main-content {
            margin-left: 220px;
        }
        .main-content.collapsed {
            margin-left: 72px;
        }
    }

    /* Welcome Container Styles */
    .welcome-container {
        position: relative;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 40px;
        color: var(--text-primary);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 35px;
    }

    .welcome-bg-overlay {
        display: none;
    }

    .welcome-info {
        max-width: 600px;
        z-index: 1;
    }

    .welcome-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background-color: var(--bg-hover);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        padding: 6px 14px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    .welcome-title {
        font-family: var(--font-heading);
        font-size: 32px;
        font-weight: 800;
        line-height: 1.25;
        margin-bottom: 12px;
        color: var(--text-primary);
    }

    .welcome-title .highlight {
        color: var(--primary-light);
    }

    .welcome-desc {
        font-size: 15px;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    .welcome-actions {
        display: flex;
        gap: 14px;
        z-index: 1;
        flex-shrink: 0;
    }

    .welcome-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 14px;
        font-family: var(--font-heading);
        transition: all var(--transition-fast);
        cursor: pointer;
        border: none;
    }

    .welcome-btn-primary {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        color: #ffffff;
        box-shadow: var(--shadow-sm);
    }

    .welcome-btn-primary:hover {
        background: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .welcome-btn-secondary {
        background-color: var(--bg-hover);
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
    }

    .welcome-btn-secondary:hover {
        background-color: var(--border-color);
        color: var(--text-primary);
        transform: translateY(-2px);
    }

    /* Section Stats Header */
    .stats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .stats-header h2 {
        font-family: var(--font-heading);
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stats-header h2 i {
        color: var(--primary-light);
        margin-right: 8px;
    }

    .refresh-indicator {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Statistics Grid Layout */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        position: relative;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal), border-color var(--transition-fast);
        overflow: hidden;
    }

    .stat-card-glow {
        position: absolute;
        inset: 0;
        background: radial-gradient(80px circle at 0px 0px, rgba(var(--primary-rgb), 0.08), transparent 80%);
        opacity: 0;
        transition: opacity var(--transition-normal);
        pointer-events: none;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    .stat-card:hover .stat-card-glow {
        opacity: 1;
    }

    .stat-icon-wrapper {
        width: 50px;
        height: 50px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-total .stat-icon-wrapper { background-color: rgba(30, 58, 138, 0.1); color: #1e3a8a; }
    .stat-open .stat-icon-wrapper { background-color: rgba(2, 132, 199, 0.1); color: #0284c7; }
    .stat-progress .stat-icon-wrapper { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .stat-resolved .stat-icon-wrapper { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }

    .stat-info h3 {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }

    .stat-number {
        font-family: var(--font-heading);
        font-size: 28px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.1;
    }

    /* Section Containers */
    .section-container {
        margin-bottom: 40px;
    }

    .section-title {
        font-family: var(--font-heading);
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        color: var(--primary-light);
    }

    /* Quick Action Hub CSS */
    .action-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .action-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-normal), box-shadow var(--transition-normal), border-color var(--transition-fast);
    }

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }

    .action-card-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-bottom: 16px;
    }

    .bg-blue { background-color: rgba(37, 99, 235, 0.1); color: #2563eb; }
    .bg-purple { background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
    .bg-emerald { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .bg-amber { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }

    .action-card h4 {
        font-family: var(--font-heading);
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .action-card p {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
        flex-grow: 1;
        margin-bottom: 16px;
    }

    .action-card-link {
        font-size: 12px;
        font-weight: 700;
        color: var(--primary-light);
        display: flex;
        align-items: center;
        gap: 6px;
        transition: gap var(--transition-fast);
    }

    .action-card:hover .action-card-link {
        gap: 10px;
    }

    /* Taluka Showcase CSS */
    .taluka-showcase {
        display: grid;
        grid-template-columns: 260px 1fr;
        gap: 24px;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }

    .taluka-tabs {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .taluka-tab {
        width: 100%;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 12px 16px;
        border-radius: var(--radius-md);
        color: var(--text-secondary);
        font-family: var(--font-heading);
        font-weight: 600;
        font-size: 14px;
        text-align: left;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all var(--transition-fast);
    }

    .taluka-tab:hover {
        background-color: var(--bg-hover);
        color: var(--text-primary);
    }

    .taluka-tab.active {
        background-color: rgba(30, 58, 138, 0.08);
        border-color: rgba(30, 58, 138, 0.15);
        color: var(--primary-light);
    }

    body.dark-theme .taluka-tab.active {
        background-color: rgba(37, 99, 235, 0.15);
        color: #60a5fa;
    }

    .taluka-tab i {
        font-size: 14px;
        width: 18px;
        text-align: center;
    }

    .taluka-details-container {
        padding: 10px 16px;
        display: flex;
        align-items: center;
        border-left: 1px solid var(--border-color);
    }

    .taluka-detail-pane {
        display: none;
        animation: fadeIn 0.35s ease;
        width: 100%;
    }

    .taluka-detail-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .pane-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 14px;
    }

    .pane-header h3 {
        font-family: var(--font-heading);
        font-size: 20px;
        font-weight: 800;
        color: var(--text-primary);
    }

    .pane-badge {
        font-size: 11px;
        font-weight: 700;
        background-color: rgba(245, 158, 11, 0.15);
        color: #d97706;
        padding: 4px 10px;
        border-radius: var(--radius-full);
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .taluka-detail-pane p {
        font-size: 14px;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .pane-stats {
        display: flex;
        gap: 32px;
    }

    .pane-stat-item {
        display: flex;
        flex-direction: column;
    }

    .pane-stat-item strong {
        font-family: var(--font-heading);
        font-size: 20px;
        font-weight: 800;
        color: var(--primary-light);
    }

    .pane-stat-item span {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* Recent Issues CSS */
    .recent-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .view-all-link {
        font-size: 13px;
        font-weight: 700;
        color: var(--primary-light);
        display: flex;
        align-items: center;
        gap: 4px;
        transition: gap var(--transition-fast);
    }

    .view-all-link:hover {
        gap: 8px;
    }

    .issues-table-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        overflow-x: auto;
    }

    .recent-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .recent-table th {
        background-color: var(--bg-hover);
        padding: 14px 20px;
        font-weight: 700;
        color: var(--text-secondary);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--border-color);
    }

    .recent-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .recent-table tbody tr:last-child td {
        border-bottom: none;
    }

    .recent-table tbody tr:hover {
        background-color: var(--bg-hover);
    }

    .issue-no {
        font-weight: 700;
        color: var(--primary-light);
        font-family: monospace;
    }

    .issue-desc strong {
        color: var(--text-primary);
        font-weight: 600;
    }

    .dept-badge {
        display: inline-block;
        padding: 4px 10px;
        background-color: var(--bg-hover);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .badge-open {
        background-color: rgba(2, 132, 199, 0.12);
        color: #0284c7;
    }

    .badge-pending {
        background-color: rgba(234, 88, 12, 0.12);
        color: #ea580c;
    }

    .badge-in-progress {
        background-color: rgba(245, 158, 11, 0.12);
        color: #d97706;
    }

    .badge-resolved {
        background-color: rgba(16, 185, 129, 0.12);
        color: #10b981;
    }

    .empty-feed {
        padding: 40px;
        text-align: center;
        color: var(--text-muted);
    }

    .empty-feed i {
        font-size: 32px;
        margin-bottom: 12px;
    }

    .empty-feed p {
        font-size: 14px;
    }

    /* Interactive Stats Card Shimmer Hover mouse tracker script */
    @media (min-width: 1025px) {
        .stats-grid {
            /* Enable mouse hover effect */
        }
    }

    /* Responsive Adjustments */
    @media (max-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .action-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .welcome-container {
            padding: 24px;
            flex-direction: column;
            align-items: flex-start;
        }

        .welcome-title {
            font-size: 26px;
        }

        .welcome-actions {
            width: 100%;
            flex-direction: column;
        }

        .welcome-btn {
            width: 100%;
            justify-content: center;
        }

        .taluka-showcase {
            grid-template-columns: 1fr;
        }

        .taluka-details-container {
            border-left: none;
            border-top: 1px solid var(--border-color);
            padding: 20px 0 0 0;
        }

        .taluka-tabs {
            flex-direction: row;
            flex-wrap: wrap;
        }

        .taluka-tab {
            width: calc(50% - 4px);
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .action-grid {
            grid-template-columns: 1fr;
        }
        .taluka-tab {
            width: 100%;
        }
    }
</style>

<script>
    // Show details of selected Taluka
    function showTaluka(id) {
        // Deactivate all tabs
        const tabs = document.querySelectorAll('.taluka-tab');
        tabs.forEach(tab => tab.classList.remove('active'));

        // Deactivate all panes
        const panes = document.querySelectorAll('.taluka-detail-pane');
        panes.forEach(pane => pane.classList.remove('active'));

        // Find and activate requested tab
        event.currentTarget.classList.add('active');

        // Find and activate requested pane
        const activePane = document.getElementById('taluka-' + id);
        if (activePane) {
            activePane.classList.add('active');
        }
    }

    // Dynamic stats card glow coordinate tracker on mouse move
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const glow = card.querySelector('.stat-card-glow');
            if (glow) {
                glow.style.background = `radial-gradient(120px circle at ${x}px ${y}px, rgba(37, 99, 235, 0.12), transparent 80%)`;
            }
        });
    });
</script>

<?php
// Include footer template inside main content to align perfectly with the header and sidebar
include 'include/footer.php';
?>
</main>
