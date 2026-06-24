<style>
    /* Force the main-content container to be a flex column so footer sticks to the bottom */
    .main-content {
        display: flex;
        flex-direction: column;
        --content-padding: 30px;
        padding: var(--content-padding, 30px) !important;
        overflow-x: hidden;
    }

    @media screen and (max-width: 1024px) {
        .main-content {
            --content-padding: 24px;
        }
    }

    @media screen and (max-width: 768px) {
        .main-content {
            --content-padding: 16px;
        }
    }

    .site-footer {
        background: #1e1e1e;
        color: #f5f5f5;
        padding: 24px;
        text-align: center;
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        margin-top: auto; /* Pushes the footer to the bottom of the main content area */
        margin-left: calc(-1 * var(--content-padding, 30px));
        margin-right: calc(-1 * var(--content-padding, 30px));
        margin-bottom: calc(-1 * var(--content-padding, 30px));
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 14px;
        box-sizing: border-box;
    }

    .footer-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .footer-title {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.03em;
    }

    .footer-subtitle {
        margin: 6px 0 0;
        font-size: 13px;
        color: #c8c8c8;
    }

    .developer-tag {
        color: #ffffff;
    }

    @media screen and (max-width: 480px) {
        .site-footer {
            padding: 20px 12px;
        }
        .footer-title {
            font-size: 13px;
        }
        .footer-subtitle {
            font-size: 11px;
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-container">
        <p class="footer-title">&copy; @ जिल्हा परिषद हिंगोली</p>
        <p class="footer-subtitle">DEVELOPED AND MAINTAINED BY <strong class="developer-tag">SETTribe</strong></p>
    </div>
</footer>
