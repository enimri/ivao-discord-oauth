<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/png" href="/favicon.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>Welcome! - IVAO <?= $division_name ?? 'XM' ?></title>
</head>
<body>
    <div class="container">
        <div class="bubble">
            <img src="/assets/img/logo.png" alt="IVAO Logo">
            <h1><?= htmlspecialchars($user->getFullName()) ?>, welcome aboard!</h1>
            <p>You have successfully entered our Discord server! You can find more useful information by clicking on the buttons below.</p>
            <a class="nav-link" href="<?= htmlspecialchars($division_url ?? 'https://xm.ivao.aero') ?>">
                <button>Division website</button>
            </a>
        </div>
    </div>
    <script>
        // Dynamic scaling based on screen resolution
        (function() {
            function updateScale() {
                const vw = window.innerWidth;
                const vh = window.innerHeight;
                
                // Different base dimensions for mobile vs desktop
                const isMobile = vw < 768;
                const baseWidth = isMobile ? 375 : 1200;
                const baseHeight = isMobile ? 667 : 800;
                
                // Calculate scale - more aggressive on mobile
                const scaleX = vw / baseWidth;
                const scaleY = vh / baseHeight;
                let scale = Math.min(scaleX, scaleY);
                
                // Cap at 1 for larger screens, but allow smaller on mobile
                if (!isMobile) {
                    scale = Math.min(scale, 1);
                }
                
                // Ensure minimum scale to prevent too small content
                scale = Math.max(scale, 0.5);
                
                document.documentElement.style.setProperty('--scale', scale);
                document.documentElement.style.setProperty('--vh', vh + 'px');
            }
            
            updateScale();
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(updateScale, 100);
            });
            window.addEventListener('orientationchange', function() {
                setTimeout(updateScale, 100);
            });
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

