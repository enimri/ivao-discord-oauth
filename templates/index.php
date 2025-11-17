<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <meta name="description" content="Join the IVAO <?= htmlspecialchars($division_country ?? 'Middle East') ?> Discord server. Connect with fellow virtual aviators, participate in events, and access educational sessions.">
    <meta property="og:title" content="IVAO <?= htmlspecialchars($division_name ?? 'XM') ?> - Discord Authentication">
    <meta property="og:description" content="Join our Discord community - the main place for social interaction, official communications, events, and educational sessions.">
    <meta property="og:image" content="<?= htmlspecialchars($division_url ?? 'https://xm.ivao.aero') ?>/assets/img/logo.png">
    <meta property="og:url" content="<?= htmlspecialchars($division_url ?? 'https://xm.ivao.aero') ?>">
    <meta name="theme-color" content="#00d4ff">
    <title>IVAO <?= htmlspecialchars($division_name ?? 'XM') ?> - Discord Authentication</title>
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
                        <h1 class="main-title">Welcome to IVAO <?= htmlspecialchars($division_name ?? 'XM') ?></h1>
                        <p class="subtitle">Join our Discord community</p>
                    </div>
                </div>

                <!-- Info Section -->
                <div class="info-section">
                    <div class="info-card">
                        <div class="info-icon">💬</div>
                        <div class="info-content">
                            <h3>Community Hub</h3>
                            <p>Connect with fellow virtual aviators from <?= htmlspecialchars($division_country ?? 'Middle East') ?> and around the world.</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">🎯</div>
                        <div class="info-content">
                            <h3>Events & Activities</h3>
                            <p>Participate in group flights, virtual events, and special activities organized by the division.</p>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">📚</div>
                        <div class="info-content">
                            <h3>Learning Resources</h3>
                            <p>Access educational sessions, training materials, and get help from experienced members.</p>
                        </div>
                    </div>
                </div>

                <!-- Authentication Section -->
                <div class="auth-section">
                    <div class="steps-indicator">
                        <div class="step active">
                            <span class="step-number">1</span>
                            <span class="step-label">IVAO Login</span>
                        </div>
                        <div class="step-divider"></div>
                        <div class="step">
                            <span class="step-number">2</span>
                            <span class="step-label">Discord Join</span>
                        </div>
                    </div>
                    
                    <p class="auth-description">
                        Start by authenticating with your IVAO account. It only takes a few moments!
                    </p>
                    
                    <a href="<?= htmlspecialchars($ivaoUrl ?? '#') ?>" class="auth-button" aria-label="Login with IVAO account">
                        <svg class="button-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Continue with IVAO</span>
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
