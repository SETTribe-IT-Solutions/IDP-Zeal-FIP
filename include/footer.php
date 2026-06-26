<style>
    .site-footer {
        background: #1e1e1e;
        color: #f5f5f5;
        padding: 24px;
        text-align: center;
        font-family: 'Inter', sans-serif;
        line-height: 1.6;
        margin-top: 40px;
        margin-left: -30px;
        margin-right: -30px;
        margin-bottom: -30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 14px;
    }

    /* Force the main-content container to be a flex column so footer sticks to the bottom */
    .main-content {
        display: flex;
        flex-direction: column;
    }

    .site-footer {
        margin-top: auto; /* Pushes the footer to the bottom of the main content area */
    }

    /* Adjust negative margins for pages with padding variations */
    @media screen and (max-width: 1024px) {
        .site-footer {
            margin-left: -24px;
            margin-right: -24px;
        }
    }

    @media screen and (max-width: 768px) {
        .site-footer {
            margin-left: -16px;
            margin-right: -16px;
            margin-bottom: -20px;
            padding: 20px 16px;
        }
    }
</style>

<footer class="site-footer">
    <div style="max-width: 900px; margin: 0 auto;">
        <p style="margin: 0; font-size: 15px; font-weight: 600; letter-spacing: 0.03em;">&copy; @ जिल्हा परिषद हिंगोली</p>
        <p style="margin: 6px 0 0; font-size: 13px; color: #c8c8c8;">DEVELOPED AND MAINTAINED BY <strong style="color: #ffffff;">SETTribe</strong></p>
    </div>
</footer>

