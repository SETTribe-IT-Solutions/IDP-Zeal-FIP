<?php
session_start();

if (isset($_SESSION['username'])) {
    header('Location: user_dashboard.php');
    exit;
}

// Set page context for header
$active_page = 'landingpage';
$page_title = 'Home';
$page_description = 'Official Zilla Parishad Hingoli portal for inter-department issue management and district information.';

include 'include/header.php';
?>

<style>
    /* --- Main Layout Override --- */
    .main-content {
        padding: 40px 24px;
        min-height: calc(100vh - var(--header-height));
        background-color: var(--bg-body);
        transition: background-color var(--transition-normal);
        max-width: 1450px;
        margin: 0 auto;
    }

    /* --- Hero Section --- */
    .landing-hero {
        position: relative;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 40px;
        background: linear-gradient(135deg, #07215b 0%, #0a3a82 50%, #0e54b6 100%);
        border-radius: var(--radius-lg);
        padding: 48px;
        overflow: hidden;
        color: #ffffff;
        margin-bottom: 40px;
        box-shadow: 0 20px 40px rgba(7, 33, 91, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.06);
        align-items: center;
    }

    body.dark-theme .landing-hero {
        background: linear-gradient(135deg, #020617 0%, #0f172a 100%);
        border-color: rgba(255, 255, 255, 0.04);
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
    }

    .landing-hero::before,
    .landing-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.45;
        pointer-events: none;
    }

    .landing-hero::before {
        width: 240px;
        height: 240px;
        right: -60px;
        top: -60px;
        background: rgba(59, 130, 246, 0.4);
    }

    .landing-hero::after {
        width: 180px;
        height: 180px;
        left: -40px;
        bottom: -40px;
        background: rgba(236, 72, 153, 0.22);
    }

    .landing-hero .hero-panel {
        position: relative;
        z-index: 2;
    }

    .landing-hero .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        padding: 8px 16px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 24px;
        animation: slideDown 0.6s ease-out;
        color: rgba(255, 255, 255, 0.95);
    }

    .landing-hero .hero-badge i {
        color: #f59e0b;
    }

    .landing-hero .hero-title {
        font-family: var(--font-heading);
        font-size: clamp(2.2rem, 3.2vw, 3.2rem);
        font-weight: 800;
        line-height: 1.15;
        max-width: 700px;
        margin-bottom: 12px;
        letter-spacing: -0.02em;
        animation: slideUp 0.8s ease-out 0.1s both;
    }

    .landing-hero .hero-title-sub {
        font-size: 1.1rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 24px;
        letter-spacing: 0.01em;
        animation: slideUp 0.8s ease-out 0.2s both;
    }

    .landing-hero .hero-text {
        max-width: 650px;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 36px;
        font-size: 1.05rem;
        line-height: 1.75;
        animation: slideUp 0.8s ease-out 0.3s both;
    }

    .landing-hero .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        animation: slideUp 0.8s ease-out 0.4s both;
    }

    .landing-hero .hero-actions .btn {
        min-width: 180px;
        padding: 14px 28px;
        font-weight: 700;
        border-radius: var(--radius-full);
        font-family: var(--font-heading);
        font-size: 15px;
        text-align: center;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease, background-color 0.25s ease, border-color 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
    }

    .landing-hero .hero-actions .btn-primary {
        background: #ffffff !important;
        color: #07215b !important;
        box-shadow: 0 10px 25px rgba(255, 255, 255, 0.15);
        border: 2px solid transparent;
    }

    .landing-hero .hero-actions .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.95) !important;
    }

    .landing-hero .hero-actions .btn-outline {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        border: 2px solid rgba(255, 255, 255, 0.35);
        backdrop-filter: blur(10px);
    }

    .landing-hero .hero-actions .btn-outline:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 0.18) !important;
        border-color: #ffffff;
        box-shadow: 0 15px 30px rgba(255, 255, 255, 0.1);
    }

    /* --- Hero Visual Illustration --- */
    .landing-hero .hero-visual {
        min-height: 380px;
        width: 100%;
        border-radius: 28px;
        background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.32), rgba(14, 165, 233, 0.08) 45%),
                    radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.18), transparent 40%);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
        position: relative;
        overflow: hidden;
        display: grid;
        place-items: center;
        z-index: 1;
        animation: slideUp 1s ease-out;
    }

    .landing-hero .hero-visual::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.12), transparent 25%);
        pointer-events: none;
    }

    .landing-hero .hero-visual .hero-illustration {
        width: 100%;
        max-width: 380px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: center;
        justify-items: center;
        gap: 20px;
        padding: 30px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(12px);
    }

    .landing-hero .hero-visual .hero-logo,
    .landing-hero .hero-visual .hero-emblem {
        width: 100px;
        height: 100px;
        object-fit: contain;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.18);
        padding: 10px;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .landing-hero .hero-visual .hero-logo:hover,
    .landing-hero .hero-visual .hero-emblem:hover {
        transform: scale(1.1) rotate(4deg);
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .landing-hero .hero-visual .hero-illustration-text {
        grid-column: span 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 18px;
        border-radius: var(--radius-full);
        background: rgba(0, 0, 0, 0.3);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.85rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid rgba(255, 255, 255, 0.12);
        margin-top: 10px;
    }

    /* --- Landing Notes --- */
    .landing-notes {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
        margin-bottom: 40px;
        align-items: stretch;
    }

    .landing-note {
        position: relative;
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 28px;
        border: 1px solid var(--border-color);
        border-left: 5px solid transparent;
        box-shadow: var(--shadow-sm);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        overflow: hidden;
    }

    .landing-note:nth-child(1) { border-left-color: #0ea5e9; }
    .landing-note:nth-child(2) { border-left-color: #3b82f6; }
    .landing-note:nth-child(3) { border-left-color: #06b6d4; }

    .landing-note:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
        border-color: rgba(14, 165, 233, 0.25);
    }

    .landing-note::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(14, 165, 233, 0.05);
        pointer-events: none;
    }


    .landing-note strong {
        display: block;
        font-family: var(--font-heading);
        color: var(--text-primary);
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .landing-note .note-lang {
        display: block;
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 600;
        margin-top: 2px;
    }

    .landing-note span {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.7;
    }

    /* --- Sections Styling --- */
    .section-container {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 36px 32px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        margin-bottom: 32px;
        animation: fadeInUp 0.8s ease-out;
        transition: background-color var(--transition-normal), border-color var(--transition-normal);
    }

    .section-title {
        margin-bottom: 28px;
        font-size: 1.35rem;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-primary);
        position: relative;
        padding-bottom: 12px;
        font-family: var(--font-heading);
        font-weight: 700;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 4px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6);
        border-radius: var(--radius-full);
    }

    .section-title i {
        color: #0ea5e9;
        font-size: 1.4rem;
    }

    /* --- District Info Grid --- */
    .info-banner {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 24px;
    }

    .info-card {
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 24px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .info-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.35s ease;
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    }

    .info-card:hover::after {
        transform: scaleX(1);
    }

    .info-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--shadow-md);
        border-color: rgba(14, 165, 233, 0.35);
    }


    .info-card h4 {
        font-family: var(--font-heading);
        font-size: 1.1rem;
        margin-bottom: 12px;
        color: var(--text-primary);
        font-weight: 700;
    }

    .info-card p {
        color: var(--text-secondary);
        font-size: 0.93rem;
        line-height: 1.65;
    }

    /* --- Features & How to Get Started Cards --- */
    .feature-grid,
    .quick-list {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 24px;
    }

    .feature-card,
    .quick-card {
        background: var(--bg-body);
        border-radius: var(--radius-lg);
        padding: 28px 24px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .feature-card::before,
    .quick-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.35s ease;
    }

    .feature-card:hover::before,
    .quick-card:hover::before {
        transform: scaleX(1);
    }

    .feature-card:hover,
    .quick-card:hover {
        transform: translateY(-8px);
        border-color: rgba(14, 165, 233, 0.35);
        box-shadow: 0 16px 36px rgba(14, 165, 233, 0.12);
    }

    body.light-theme .feature-card:hover,
    body.light-theme .quick-card:hover {
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(240, 249, 255, 0.98));
    }

    body.dark-theme .feature-card:hover,
    body.dark-theme .quick-card:hover {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.98));
    }

    .feature-icon,
    .quick-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        font-size: 1.35rem;
        margin-bottom: 20px;
        color: #0ea5e9;
        background: rgba(14, 165, 233, 0.12);
        transition: transform 0.3s ease, background-color 0.3s ease;
    }

    body.dark-theme .feature-icon,
    body.dark-theme .quick-icon {
        color: #38bdf8;
        background: rgba(56, 189, 248, 0.15);
    }

    .feature-card:hover .feature-icon,
    .quick-card:hover .quick-icon {
        transform: scale(1.1) rotate(6deg);
        background: rgba(14, 165, 233, 0.22);
    }

    body.dark-theme .feature-card:hover .feature-icon,
    body.dark-theme .quick-card:hover .quick-icon {
        background: rgba(56, 189, 248, 0.24);
    }

    .feature-card h4,
    .quick-card h4 {
        font-family: var(--font-heading);
        font-size: 1.15rem;
        margin-bottom: 8px;
        color: var(--text-primary);
        font-weight: 700;
    }

    .feature-card-subtitle {
        font-size: 0.82rem;
        color: #0ea5e9;
        font-weight: 600;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .feature-card p,
    .quick-card p {
        color: var(--text-secondary);
        font-size: 0.94rem;
        line-height: 1.7;
    }

    /* --- Interactive Notice Board --- */
    .notice-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }

    .notice-tab {
        padding: 12px 24px;
        border-radius: var(--radius-full);
        border: 1px solid var(--border-color);
        background: var(--bg-body);
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.25s ease;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .notice-tab:hover {
        background: var(--bg-hover);
        color: var(--text-primary);
        border-color: #0ea5e9;
    }

    .notice-tab.active {
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        color: #ffffff;
        border-color: transparent;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
    }

    .notice-panel {
        display: none;
        animation: fadeInUp 0.4s ease-out;
    }

    .notice-panel.active {
        display: block;
    }

    .notice-list {
        display: grid;
        gap: 16px;
    }

    .notice-item {
        padding: 20px;
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .notice-item:hover {
        transform: translateY(-2px);
        border-color: #0ea5e9;
        box-shadow: var(--shadow-sm);
    }

    .notice-item a {
        display: block;
        color: var(--text-primary);
        font-weight: 600;
        font-size: 1.05rem;
        line-height: 1.45;
        transition: color var(--transition-fast);
    }

    .notice-item a:hover {
        color: #2563eb;
    }

    .notice-item span {
        display: block;
        color: var(--text-secondary);
        font-size: 0.92rem;
        line-height: 1.6;
    }



    /* --- Animation Declarations --- */
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(35px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* --- Responsive Queries --- */
    @media (max-width: 1200px) {
        .feature-grid,
        .quick-list,
        .info-banner {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
    }

    @media (max-width: 992px) {
        .landing-hero {
            grid-template-columns: 1fr;
            padding: 36px;
            gap: 30px;
        }
        .landing-hero .hero-visual {
            min-height: 320px;
        }
        .landing-notes {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .section-container {
            padding: 28px 24px;
        }
    }

    @media (max-width: 640px) {
        .main-content {
            padding: 24px 16px;
        }
        .landing-hero {
            padding: 28px 20px;
        }
        .landing-hero .hero-badge {
            font-size: 10px;
            padding: 6px 12px;
            margin-bottom: 16px;
        }
        .landing-hero .hero-title {
            font-size: 1.85rem;
        }
        .landing-hero .hero-title-sub {
            font-size: 0.95rem;
        }
        .landing-hero .hero-text {
            font-size: 0.92rem;
            margin-bottom: 24px;
        }
        .landing-hero .hero-actions {
            flex-direction: column;
            width: 100%;
            align-items: stretch;
            gap: 12px;
        }
        .landing-hero .hero-actions .btn {
            width: 100%;
            min-width: unset;
        }
        .landing-hero .hero-visual {
            min-height: 280px;
        }
        .landing-hero .hero-visual .hero-illustration {
            padding: 20px;
            max-width: 320px;
        }
        .landing-hero .hero-visual .hero-logo,
        .landing-hero .hero-visual .hero-emblem {
            width: 80px;
            height: 80px;
        }
        .feature-grid,
        .quick-list,
        .info-banner {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
</style>

<main class="main-content">
    <section class="landing-hero">
        <div class="hero-panel">
            <span class="hero-badge"><i class="fa-solid fa-shield-halved"></i> जिल्हा परिषद हिंगोली पोर्टल | Zilha Parishad Hingoli Portal</span>
            <h1 class="hero-title">हिंगोली जिल्हा परिषद आंतर-विभागीय समस्या व्यवस्थापन पोर्टल</h1>
            <p class="hero-title-sub">Hingoli District Inter-Departmental Issue Management Portal</p>
            <p class="hero-text">हिंगोली जिल्हा परिषद आणि सविस्तर सेवांसाठी अधिकृत समस्यांचे निवारण पोर्टल. एकाच ठिकाणी तक्रारी नोंदवा, प्रगती बघा आणि स्थानिक प्रशासनाशी जोडलेले अपडेट मिळवा.</p>
            <div class="hero-actions">
                <a href="login.php" class="btn btn-primary hero-btn-login">लॉगिन करा / Login <i class="fa-solid fa-right-to-bracket ms-2"></i></a>
                <a href="create_user.php" class="btn btn-outline hero-btn-register">नवीन खाते / Register <i class="fa-solid fa-user-plus ms-2"></i></a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-illustration">
                <img src="assets/zp-logo.png" alt="ZP Hingoli Logo" class="hero-logo">
                <img src="assets/maharashtra-emblem.png" alt="Maharashtra Emblem" class="hero-emblem">
                <span class="hero-illustration-text"><i class="fa-solid fa-city"></i> Official District Service Portal</span>
            </div>
        </div>
    </section>

    <div class="landing-notes">
        <div class="landing-note">
            <strong>वेगवान नोंदणी</strong>
            <span class="note-lang">Fast Registration</span>
            <span>समस्या फॉर्म जलद भरून लगेच विभागाला पाठवता येतात. Submit issues instantly to departments.</span>
        </div>
        <div class="landing-note">
            <strong>आता होणारी प्रगती</strong>
            <span class="note-lang">Real-time Updates</span>
            <span>डॅशबोर्डवर तक्रारींची स्थिती आणि अहवाल एकत्र पहा. View complaint status and reports together.</span>
        </div>
        <div class="landing-note">
            <strong>विश्वसनी सेवा</strong>
            <span class="note-lang">Secure Access</span>
            <span>प्रमाणित लॉगिन आणि विभागीय वापरकर्त्यांसाठी सुरक्षित प्रवेश. Verified login for authorized users only.</span>
        </div>
    </div>

    <div class="section-container">
        <h2 class="section-title"><i class="fa-solid fa-map-location-dot"></i> हिंगोली जिल्हा माहिती / Hingoli District Overview</h2>
        <div class="info-banner">
            <div class="info-card">
                <h4>जिल्ह्याचा परिचय</h4>
                <p>हिंगोली महाराष्ट्रातील मराठवाड्याच्या उत्तरेस स्थित असून त्याची सीमा अकोला, यवतमाळ, परभणी आणि नांदेड जिल्ह्याशी जोडलेली आहे.</p>
            </div>
            <div class="info-card">
                <h4>स्थापना</h4>
                <p>हिंगोली जिल्ह्याची स्थापना 1 मे 1999 रोजी परभणी जिल्ह्यापासून विभाजन करून करण्यात आली.</p>
            </div>
            <div class="info-card">
                <h4>परिवहन</h4>
                <p>जवळचे रेल्वे स्टेशन हिंगोली आहे आणि परभणी हे सर्वात जवळचे मोठे रेल्वे स्टेशन आहे.</p>
            </div>
            <div class="info-card">
                <h4>अधिकृत संकेतस्थळ</h4>
                <p>या पोर्टलद्वारे स्थानिक प्रशासनातील सेवा आणि अधिकृत माहिती सुलभरित्या उपलब्ध होते.</p>
            </div>
        </div>
    </div>



    <div class="section-container">
        <h2 class="section-title"><i class="fa-solid fa-sparkles"></i> मुख्य सुविधा / Key Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                <h4>समस्या नोंदणी</h4>
                <p class="feature-card-subtitle">Issue Registration</p>
                <p>सर्व विभागांसाठी तक्रार व सूचना पटकन नोंदवा. Submit complaints to all departments.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-chart-simple"></i></div>
                <h4>डॅशबोर्ड अपडेट</h4>
                <p class="feature-card-subtitle">Dashboard Updates</p>
                <p>एकाच पानावर सर्व मुद्दे व प्रगती त्वरित पहा. View all issues and progress instantly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-file-invoice"></i></div>
                <h4>अहवाल व ट्रॅकिंग</h4>
                <p class="feature-card-subtitle">Reports & Tracking</p>
                <p>तक्रारींची संपूर्ण नोंद, फिल्टर आणि निर्यात क्षमता. Complete records with export options.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-user-shield"></i></div>
                <h4>सुरक्षित प्रवेश</h4>
                <p class="feature-card-subtitle">Secure Access</p>
                <p>अधिकृत खाते वापरून सहज व सुरक्षितरित्या लॉगिन करा. Login safely with authorized accounts.</p>
            </div>
        </div>
    </div>

    <div class="section-container">
        <h2 class="section-title"><i class="fa-solid fa-rocket"></i> सुरुवात कशी कराल? / How to Get Started?</h2>
        <div class="quick-list">
            <div class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>
                <h4>लॉगिन करा</h4>
                <p class="feature-card-subtitle">Step 1: Login</p>
                <p>आपले वापरकर्तानाव व पासवर्ड वापरून पोर्टलमध्ये प्रवेश मिळवा. Sign in with your credentials.</p>
            </div>
            <div class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
                <h4>समस्या भरा</h4>
                <p class="feature-card-subtitle">Step 2: Register Issue</p>
                <p>समस्येचा तपशील, विभाग व स्थान निवडा व सबमिट करा. Fill details and submit your issue.</p>
            </div>
            <div class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-eye"></i></div>
                <h4>स्थिती पहा</h4>
                <p class="feature-card-subtitle">Step 3: Check Status</p>
                <p>प्रगतीची स्थिती व विभागीय प्रतिसाद नियमित पाहा. Monitor progress and responses.</p>
            </div>
            <div class="quick-card">
                <div class="quick-icon"><i class="fa-solid fa-file-export"></i></div>
                <h4>अहवाल तयार करा</h4>
                <p class="feature-card-subtitle">Step 4: Generate Report</p>
                <p>तुमच्या कामासाठी रिपोर्ट्स तयार आणि डाउनलोड करा. Create and download reports.</p>
            </div>
        </div>
    </div>

</main>

<?php include 'include/footer.php'; ?>
</body>
</html>
