<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="description" content="Join the IVAO <?= htmlspecialchars($division_country ?? 'Middle East') ?> Discord server. You're almost there!">
    <meta property="og:title" content="Join Discord - IVAO <?= htmlspecialchars($division_name ?? 'XM') ?>">
    <meta property="og:description" content="You're just one step away from joining our Discord community!">
    <meta property="og:image" content="<?= htmlspecialchars($division_url ?? 'https://xm.ivao.aero') ?>/assets/img/logo.png">
    <meta property="og:url" content="<?= htmlspecialchars($division_url ?? 'https://xm.ivao.aero') ?>">
    <meta name="theme-color" content="#00d4ff">
    <title>Join Discord - IVAO <?= htmlspecialchars($division_name ?? 'XM') ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css?v=2.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-wrapper">
        <!-- Background Elements -->
        <div class="background-animation"></div>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="auth-card">
                <!-- Logo Section -->
                <div class="logo-section">
                    <img src="/assets/img/logo.png" alt="IVAO Logo" class="logo-img" loading="eager">
                    <div class="logo-text">
                        <h1 class="main-title">Almost There!</h1>
                        <p class="subtitle">You're just one step away</p>
                    </div>
                </div>

                <!-- Info Section -->
                <div class="info-section">
                    <div class="info-card">
                        <div class="info-icon">✅</div>
                        <div class="info-content">
                            <h3>IVAO Authentication</h3>
                            <p>You've successfully authenticated with your IVAO account. Great job!</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">🎮</div>
                        <div class="info-content">
                            <h3>Join Discord Server</h3>
                            <p>Click the button below to join our Discord server and connect with the community.</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">🚀</div>
                        <div class="info-content">
                            <h3>Get Started</h3>
                            <p>Once you join, you'll have access to events, group flights, and educational sessions.</p>
                        </div>
                    </div>
                </div>

                <!-- Authentication Section -->
                <div class="auth-section">
                    <div class="steps-indicator">
                        <div class="step active">
                            <span class="step-number" data-check="true">✓</span>
                            <span class="step-label">IVAO Login</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step active">
                            <span class="step-number">2</span>
                            <span class="step-label">Discord Join</span>
                        </div>
                    </div>
                    
                    <p class="auth-description">
                        You've completed step 1! Now join our Discord server to complete the authentication process.
                    </p>
                    
                    <a href="<?= htmlspecialchars($discordUrl ?? '#') ?>" class="auth-button" aria-label="Join Discord server">
                        <svg class="button-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z" fill="currentColor"/>
                        </svg>
                        <span>Join Discord Server</span>
                        <svg class="button-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    
                    <p class="security-note">
                        🔒 Your authentication is secure and managed by IVAO's official systems
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="page-footer">
            <p>© <?= date('Y') ?> IVAO <?= htmlspecialchars($division_name ?? 'XM') ?> Division</p>
            <a href="<?= htmlspecialchars($division_url ?? 'https://xm.ivao.aero') ?>" target="_blank" rel="noopener noreferrer" class="footer-link">
                Visit Division Website
            </a>
        </footer>
    </div>

    <script>
        // Clear localStorage on page load
        if (typeof localStorage !== 'undefined') {
            localStorage.clear();
        }
        
        // Dynamic scaling based on screen resolution - iPhone optimized
        (function() {
            function updateScale() {
                const vw = window.innerWidth || window.screen.width;
                const vh = window.innerHeight || window.screen.height;
                const isMobile = vw < 768;
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
                
                // Get the card element
                const card = document.querySelector('.auth-card');
                if (!card) {
                    setTimeout(updateScale, 50);
                    return;
                }
                
                // For iOS, use a different approach
                if (isIOS && isMobile) {
                    // Remove all transforms first
                    card.style.transform = 'none';
                    card.style.zoom = '1';
                    card.style.webkitTransform = 'none';
                    
                    // Force reflow
                    void card.offsetHeight;
                    
                    // Get actual dimensions
                    const cardRect = card.getBoundingClientRect();
                    const cardWidth = cardRect.width;
                    const cardHeight = card.scrollHeight || cardRect.height;
                    
                    // iPhone 11 Pro: 375x812
                    // Calculate available space (account for safe areas)
                    const safeAreaTop = window.safeAreaInsets?.top || 0;
                    const safeAreaBottom = window.safeAreaInsets?.bottom || 0;
                    const availableWidth = vw - 10;
                    const availableHeight = vh - safeAreaTop - safeAreaBottom - 10;
                    
                    // Calculate scale
                    const scaleX = availableWidth / cardWidth;
                    const scaleY = availableHeight / cardHeight;
                    let scale = Math.min(scaleX, scaleY);
                    
                    // Be very aggressive on iOS
                    scale = Math.min(scale, 0.9);
                    scale = Math.max(scale, 0.4);
                    
                    // Apply scaling
                    const scaleValue = scale.toFixed(3);
                    card.style.transform = 'scale(' + scaleValue + ')';
                    card.style.webkitTransform = 'scale(' + scaleValue + ')';
                    card.style.transformOrigin = 'center top';
                    card.style.webkitTransformOrigin = 'center top';
                    
                    // Also adjust the container
                    const mainContent = document.querySelector('.main-content');
                    if (mainContent) {
                        mainContent.style.paddingTop = (safeAreaTop + 5) + 'px';
                        mainContent.style.paddingBottom = (safeAreaBottom + 5) + 'px';
                    }
                } else {
                    // Desktop/non-iOS approach
                    card.style.transform = 'scale(1)';
                    card.style.zoom = '1';
                    void card.offsetHeight;
                    
                    const cardWidth = card.offsetWidth;
                    const cardHeight = card.scrollHeight;
                    const availableWidth = vw - (isMobile ? 8 : 40);
                    const availableHeight = vh - (isMobile ? 5 : 40);
                    
                    const scaleX = availableWidth / cardWidth;
                    const scaleY = availableHeight / cardHeight;
                    let scale = Math.min(scaleX, scaleY);
                    
                    if (!isMobile) {
                        scale = Math.min(scale, 1);
                    } else {
                        scale = Math.min(scale, 0.95);
                        if (cardHeight * scale > availableHeight) {
                            scale = (availableHeight / cardHeight) * 0.92;
                        }
                    }
                    
                    scale = Math.max(scale, 0.35);
                    card.style.transform = 'scale(' + scale + ')';
                    card.style.zoom = scale;
                }
                
                document.documentElement.style.setProperty('--vh', vh + 'px');
            }
            
            // Run immediately and multiple times
            updateScale();
            setTimeout(updateScale, 100);
            setTimeout(updateScale, 300);
            setTimeout(updateScale, 500);
            
            // Handle DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    updateScale();
                    setTimeout(updateScale, 100);
                    setTimeout(updateScale, 300);
                });
            }
            
            // Update on resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(updateScale, 50);
            });
            
            // Update on orientation change (important for iOS)
            window.addEventListener('orientationchange', function() {
                setTimeout(updateScale, 100);
                setTimeout(updateScale, 300);
                setTimeout(updateScale, 500);
            });
            
            // iOS specific: handle viewport changes
            if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                window.addEventListener('resize', updateScale);
                // Fix iOS viewport height issue
                function setVH() {
                    const vh = window.innerHeight * 0.01;
                    document.documentElement.style.setProperty('--vh', vh + 'px');
                }
                setVH();
                window.addEventListener('resize', setVH);
            }
        })();
        
        // Prevent all scrolling
        (function() {
            function preventScroll(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            
            // Prevent scroll on document
            document.addEventListener('wheel', preventScroll, { passive: false });
            document.addEventListener('touchmove', preventScroll, { passive: false });
            document.addEventListener('touchstart', preventScroll, { passive: false });
            document.addEventListener('scroll', preventScroll, { passive: false });
            document.addEventListener('keydown', function(e) {
                if ([32, 33, 34, 35, 36, 37, 38, 39, 40].indexOf(e.keyCode) > -1) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Lock body scroll
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
            document.body.style.height = '100vh';
            document.documentElement.style.overflow = 'hidden';
            document.documentElement.style.position = 'fixed';
            document.documentElement.style.width = '100%';
            document.documentElement.style.height = '100vh';
            
            // Prevent scroll on window
            window.addEventListener('scroll', function() {
                window.scrollTo(0, 0);
            });
            
            // Prevent scroll restoration
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
        })();
    </script>
</body>
</html>
