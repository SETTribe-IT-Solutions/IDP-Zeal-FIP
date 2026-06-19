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
    .landing-hero {
        position: relative;
        display: grid;
        grid-template-columns: 1.2fr 0.9fr;
        gap: 30px;
        background: linear-gradient(140deg, rgba(15, 23, 42, 0.96), rgba(14, 165, 233, 0.85));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-lg);
        padding: 42px;
        overflow: hidden;
        color: #ffffff;
        margin-bottom: 40px;
    }

    .landing-hero::before,
    .landing-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.5;
    }

    .landing-hero::before {
        width: 240px;
        height: 240px;
        right: -80px;
        top: -80px;
        background: rgba(59, 130, 246, 0.35);
    }

    .landing-hero::after {
        width: 180px;
        height: 180px;
        left: -60px;
        bottom: -50px;
        background: rgba(236, 72, 153, 0.18);
    }

    .landing-hero .hero-panel {
        position: relative;
        z-index: 1;
    }

    .landing-hero .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        padding: 12px 18px;
        border-radius: 999px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        margin-bottom: 22px;
        animation: slideDown 0.7s ease-out;
    }

    .landing-hero .hero-title {
        font-family: var(--font-heading);
        font-size: clamp(2.3rem, 3vw, 3.4rem);
        line-height: 1.02;
        max-width: 680px;
        margin-bottom: 14px;
        letter-spacing: -0.03em;
        animation: slideUp 0.9s ease-out 0.1s both;
    }

    .landing-hero .hero-title-sub {
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 18px;
        animation: slideUp 0.9s ease-out 0.2s both;
    }

    .landing-hero .hero-text {
        max-width: 660px;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 28px;
        font-size: 1.05rem;
        line-height: 1.8;
        animation: slideUp 0.9s ease-out 0.3s both;
    }

    .landing-hero .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        animation: slideUp 0.9s ease-out 0.4s both;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .landing-hero .hero-actions .btn {
        min-width: 170px;
        padding: 14px 22px;
        font-weight: 700;
    }



    .landing-notes {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .landing-note {
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.96);
        border-radius: var(--radius-lg);
        padding: 24px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-left: 5px solid transparent;
        box-shadow: var(--shadow-sm);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }

    .landing-note:nth-child(1) {
        border-left-color: #0ea5e9;
    }

    .landing-note:nth-child(2) {
        border-left-color: #3b82f6;
    }

    .landing-note:nth-child(3) {
        border-left-color: #06b6d4;
    }

    .landing-note:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: rgba(14, 165, 233, 0.35);
    }

    .landing-note::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(14, 165, 233, 0.12);
    }

    .landing-note::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: -20px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.08);
    }

    .landing-note strong {
        display: block;
        margin-bottom: 8px;
        font-family: var(--font-heading);
        color: var(--text-primary);
        font-size: 1.03rem;
    }

    .landing-note .note-lang {
        display: block;
        font-size: 0.9rem;
        color: #0ea5e9;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .landing-note span {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.8;
        position: relative;
        z-index: 1;
    }

    .feature-grid,
    .quick-list {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }

    .feature-card,
    .quick-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        padding: 26px;
        box-shadow: var(--shadow-sm);
        border: 1px solid transparent;
        transition: transform var(--transition-fast), border-color var(--transition-fast), box-shadow var(--transition-fast), background 0.3s ease;
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
        background: linear-gradient(90deg, #0ea5e9, #3b82f6, #0ea5e9);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.35s ease;
    }

    .info-banner {
        display: grid;
        grid-template-columns: repeat(4, minmax(220px, 1fr));
        gap: 18px;
        margin-bottom: 32px;
    }

    .info-card {
        background: linear-gradient(180deg, rgba(14, 165, 233, 0.14), rgba(59, 130, 246, 0.08));
        border: 1px solid rgba(14, 165, 233, 0.2);
        border-radius: var(--radius-lg);
        padding: 22px;
        box-shadow: 0 20px 50px rgba(14, 165, 233, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 24px 60px rgba(14, 165, 233, 0.14);
    }

    .info-card h4 {
        font-family: var(--font-heading);
        font-size: 1.05rem;
        margin-bottom: 10px;
        color: var(--text-primary);
    }

    .notice-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }

    .notice-tab {
        padding: 12px 18px;
        border-radius: 999px;
        border: 1px solid rgba(14, 165, 233, 0.24);
        background: rgba(255, 255, 255, 0.92);
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 700;
    }

    .notice-tab.active {
        background: linear-gradient(135deg, #0ea5e9, #3b82f6);
        color: white;
        border-color: transparent;
    }

    .notice-panel {
        display: none;
        padding-top: 16px;
    }

    .notice-panel.active {
        display: block;
    }

    .notice-list {
        display: grid;
        gap: 12px;
    }

    .notice-item {
        padding: 18px 20px;
        background: var(--bg-card);
        border: 1px solid rgba(14, 165, 233, 0.12);
        border-radius: var(--radius-md);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .notice-item:hover {
        transform: translateY(-2px);
        border-color: #0ea5e9;
    }

    .notice-item a {
        display: block;
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 6px;
    }

    .notice-item span {
        display: block;
        color: var(--text-secondary);
        font-size: 0.92rem;
    }

    .leadership-grid,
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .leadership-card,
    .gallery-item {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        border: 1px solid rgba(14, 165, 233, 0.12);
        padding: 22px;
        box-shadow: var(--shadow-sm);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .leadership-card:hover,
    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .leadership-icon {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        background: rgba(14, 165, 233, 0.16);
        color: #0e3b74;
        margin-bottom: 16px;
        font-size: 1.35rem;
    }

    .gallery-item {
        display: grid;
        gap: 16px;
    }

    .gallery-thumb {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.95), rgba(14, 165, 233, 0.75));
        border-radius: 18px;
        height: 150px;
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }

    .gallery-thumb::after {
        content: 'PHOTO';
        position: absolute;
        right: 12px;
        bottom: 12px;
        color: rgba(255,255,255,0.8);
        font-size: 0.75rem;
        letter-spacing: 0.08em;
    }

    .gallery-item h4 {
        margin-bottom: 6px;
    }

    .gallery-item p {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .feature-card:hover::before,
    .quick-card:hover::before {
        transform: scaleX(1);
    }

    .feature-card:hover,
    .quick-card:hover {
        transform: translateY(-8px);
        border-color: rgba(14, 165, 233, 0.35);
        box-shadow: 0 20px 50px rgba(14, 165, 233, 0.15);
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(240, 249, 255, 0.98));
    }

    .feature-icon,
    .quick-icon {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        font-size: 1.3rem;
        margin-bottom: 18px;
        color: #0f172a;
        background: rgba(14, 165, 233, 0.12);
        transition: transform 0.3s ease, background-color 0.3s ease;
    }

    .feature-card:hover .feature-icon,
    .quick-card:hover .quick-icon {
        transform: scale(1.08) rotate(5deg);
        background: rgba(14, 165, 233, 0.22);
    }

    .feature-card h4,
    .quick-card h4 {
        font-family: var(--font-heading);
        font-size: 1.08rem;
        margin-bottom: 10px;
        color: var(--text-primary);
        font-weight: 700;
    }

    .feature-card-subtitle {
        font-size: 0.85rem;
        color: #0ea5e9;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .feature-card p,
    .quick-card p {
        color: var(--text-secondary);
        font-size: 0.96rem;
        line-height: 1.8;
    }

    .section-title {
        margin-bottom: 24px;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-primary);
        position: relative;
        padding-bottom: 12px;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 4px;
        background: linear-gradient(90deg, #0ea5e9, #3b82f6);
        border-radius: 2px;
    }

    .section-title i {
        color: #0ea5e9;
        font-size: 1.3rem;
    }

    .section-container {
        animation: fadeInUp 0.8s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 1024px) {
        .landing-hero {
            grid-template-columns: 1fr;
            padding: 32px;
        }

        .landing-notes,
        .feature-grid,
        .quick-list {
            grid-template-columns: 1fr;
        }

        .landing-hero .hero-visual {
            min-height: 320px;
        }
    }

    @media (max-width: 768px) {
        .landing-hero {
            padding: 28px;
            gap: 20px;
        }

        .landing-notes {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .feature-grid,
        .quick-list {
            grid-template-columns: 1fr;
        }

        .landing-hero .hero-title {
            font-size: 2.15rem;
        }

        .section-title {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 640px) {
        .landing-hero {
            padding: 22px;
            gap: 20px;
        }

        .landing-hero .hero-title {
            font-size: 1.95rem;
        }

        .landing-hero .hero-text {
            font-size: 0.95rem;
        }

        .feature-grid,
        .quick-list {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .feature-card,
        .quick-card {
            padding: 20px;
        }

        .hero-illustration {
            max-width: 300px;
            height: 300px;
        }

        .person-avatar {
            width: 75px;
            height: 95px;
            font-size: 2rem;
        }
    }

    .landing-hero {
        background: linear-gradient(140deg, rgba(7, 33, 91, 0.92), rgba(14, 165, 233, 0.90));
    }

    .hero-actions .btn {
        border-radius: 999px;
        transition: transform 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease;
    }

    .hero-actions .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 32px rgba(14, 165, 233, 0.3);
    }

    .section-container {
        padding: 32px 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
    }

    .section-container:last-of-type {
        border-bottom: none;
    }

    .feature-grid,
    .quick-list,
    .leadership-grid,
    .gallery-grid,
    .info-banner {
        gap: 24px;
    }

    .notice-tabs {
        justify-content: center;
    }

    .notice-panel {
        background: rgba(255, 255, 255, 0.95);
        border-radius: var(--radius-lg);
        padding: 20px;
        border: 1px solid rgba(14, 165, 233, 0.16);
    }

    .notice-item {
        background: rgba(248, 250, 252, 0.95);
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
                <a href="login.php" class="btn btn-primary">लॉगिन करा / Login <i class="fa-solid fa-right-to-bracket ms-2"></i></a>
                <a href="create_user.php" class="btn btn-outline-light text-white" style="border-color: rgba(255,255,255,0.4);">नवीन खाते / Register <i class="fa-solid fa-user-plus ms-2"></i></a>
            </div>
        </div>
        <div class="hero-visual">
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
        <h2 class="section-title"><i class="fa-solid fa-users-line"></i> नेतृत्व व महत्त्वाच्या अधिकारी / Leadership</h2>
        <div class="leadership-grid">
            <div class="leadership-card">
                <div class="leadership-icon"><i class="fa-solid fa-user-tie"></i></div>
                <h4>मुख्य कार्यकारी अधिकारी</h4>
                <p>विवेक गायकवाड (भा.प्र.से.)</p>
            </div>
            <div class="leadership-card">
                <div class="leadership-icon"><i class="fa-solid fa-user-shield"></i></div>
                <h4>पालकमंत्री</h4>
                <p>नरहरी सावित्रीबाई सीताराम झिरवाल</p>
            </div>
            <div class="leadership-card">
                <div class="leadership-icon"><i class="fa-solid fa-person-booth"></i></div>
                <h4>मुख्य मंत्री</h4>
                <p>श्री. देवेंद्र फडणवीस</p>
            </div>
            <div class="leadership-card">
                <div class="leadership-icon"><i class="fa-solid fa-user-gear"></i></div>
                <h4>उपमुख्यमंत्री</h4>
                <p>श्री. एकनाथ शिंदे</p>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.notice-tab');
        const panels = document.querySelectorAll('.notice-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const target = this.dataset.target;

                tabs.forEach(t => t.classList.toggle('active', t === this));
                panels.forEach(panel => panel.classList.toggle('active', panel.id === target));
            });
        });
    });
</script>

<?php include 'include/footer.php'; ?>
