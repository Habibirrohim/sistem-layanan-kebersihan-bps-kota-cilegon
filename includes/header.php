<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistem Monitoring Kebersihan Kantor - PPNPN'; ?></title>
    <link rel="icon" type="image/jpeg" href="assets/images/bps3.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body class="bg-white font-sans">
    <!-- Navigation Bar -->
    <nav class="bg-orange-700 header-gradient text-white shadow-lg fixed w-full top-0 z-50 " style="background: linear-gradient(135deg, #F28C28 0%, #E67E22 100%);">
        <div class="max-w-full mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Left Section - Burger Menu and Title -->
                <div class="flex items-center space-x-4 flex-1">
                    <!-- Burger Menu Button (Mobile and Desktop) -->
                    <button id="sidebarToggle" class="p-2 rounded-lg hover:bg-orange-800 hover:bg-opacity-70 transition-colors duration-200 sidebar-toggle-btn">
                        <i id="sidebarToggleIcon" class="fas fa-bars text-xl text-white"></i>
                    </button>
                    <!-- Logo and Title -->
                    <div class="flex items-center space-x-5">
                        <div class="flex items-center justify-center">
                            <div class="header">
                                <img
                                    src="assets/images/selasih2.png"
                                    alt="Logo Sistem Monitoring Kebersihan"
                                    class="w-15 h-10 object-contain rounded full transition-transform duration-300 hover:scale-110">
                            </div>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="italic font-arial font-bold text-2xl uppercase">
                                SELASIH
                            </h1>
                            <p class="text-xs italic font-arial font-bold text-3xl uppercase">
                                SISTEM LAYANAN KEBERSIHAN
                            </p>
                        </div>
                        <dic class="flex items-center justify-center">
                            <div class="header">
                                <img
                                    src="assets/images/wbk.png"
                                    alt="Logo Sistem Monitoring Kebersihan"
                                    class="w-15 h-10 object-contain rounded full">
                            </div>
                        </dic>
                        <dic class="flex items-center justify-center">
                            <div class="header">
                                <img
                                    src="assets/images/berakhlak.png"
                                    alt="Logo Sistem Monitoring Kebersihan"
                                    class="w-15 h-10 object-contain rounded full">
                            </div>
                        </dic>
                        <dic class="flex items-center justify-center">
                            <div class="header">
                                <img
                                    src="assets/images/se.png"
                                    alt="Logo Sistem Monitoring Kebersihan"
                                    class="w-15 h-10 object-contain rounded full">
                            </div>
                        </dic>
                    </div>
                </div>

                <!-- Right Section - User Info and Controls -->
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <!-- Current Time Display -->
                    <div class="hidden md:flex items-center header-time-display px-3 py-1 rounded-lg backdrop-blur-sm" style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-clock text-blue-100 mr-2"></i>
                        <span id="currentTime" class="text-sm font-medium text-white drop-shadow-sm"></span>
                    </div>

                    <!-- User Info -->
                    <div class="flex items-center space-x-3">
                        <!-- User Avatar and Name -->
                        <div class="hidden sm:flex items-center header-user-info px-3 py-2 rounded-lg backdrop-blur-sm" style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.2);">
                            <div class="w-8 h-8 bg-blue-300 rounded-full flex items-center justify-center mr-3 header-user-avatar">
                                <i class="fas fa-user text-blue-800 text-sm"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-white drop-shadow-sm"><?php echo $_SESSION['nama']; ?></p>
                                <p class="text-xs text-blue-100 drop-shadow-sm"><?php echo ucfirst($_SESSION['role']); ?></p>
                            </div>
                        </div>

                        <!-- Mobile User Info -->
                        <div class="flex sm:hidden items-center">
                            <div class="w-8 h-8 bg-blue-300 rounded full flex items-center justify-center header-user-avatar">
                                <i class="fas fa-user text-blue-800 text-sm"></i>
                            </div>
                        </div>

                        <!-- Role Badge -->
                        <div class="bg-gradient-to-r from-green-500 to-green-600 px-3 py-1 rounded full shadow-lg header-role-badge">
                            <span class="text-xs font-bold text-white flex items-center drop-shadow-sm">
                                <i class="fas <?php echo ($_SESSION['role'] == 'admin') ? 'fa-crown' : 'fa-user-check'; ?> mr-1"></i>
                                <?php echo ucfirst($_SESSION['role']); ?>
                            </span>
                        </div>

                        <!-- Logout Button -->
                        <a href="logout.php" class="bg-gradient-to-r from-red-500 to-red-600 px-3 py-2 rounded-lg text-sm font-semibold hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg header-logout-btn text-white">
                            <i class="fas fa-sign-out-alt mr-1"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar (optional loading indicator) -->
        <div id="loadingBar" class="h-1 header-loading-bar opacity-0 transition-opacity duration-300" style="background: linear-gradient(135deg, #F28C28 0%, #E67E22 100%);"></div>
    </nav>

    <!-- Enhanced Header JavaScript -->
    <script>
        // Real-time clock functionality
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: '2-digit',
                month: 'short'
            });

            const clockElement = document.getElementById('currentTime');
            if (clockElement) {
                clockElement.innerHTML = `${dateString}<br><span class="text-xs">${timeString}</span>`;
            }
        }

        // Update clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);

        // Enhanced sidebar toggle with animation
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            if (sidebar && overlay) {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');

                // Add ripple effect to button
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            }
        });

        // Loading bar functionality for page transitions
        function showLoadingBar() {
            const loadingBar = document.getElementById('loadingBar');
            if (loadingBar) {
                loadingBar.style.opacity = '1';
                loadingBar.style.width = '100%';
            }
        }

        function hideLoadingBar() {
            const loadingBar = document.getElementById('loadingBar');
            if (loadingBar) {
                setTimeout(() => {
                    loadingBar.style.opacity = '0';
                    loadingBar.style.width = '0%';
                }, 500);
            }
        }

        // Add loading animation to navigation links
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a moment for all elements to be ready
            setTimeout(function() {
                initializeSidebar();
            }, 100);
        });

        function initializeSidebar() {
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarIcon = document.getElementById('sidebarToggleIcon');
            let mainContent = document.querySelector('.main-content');
            const body = document.body;

            // Fallback to find main content by class if not found
            if (!mainContent) {
                mainContent = document.querySelector('[class*="lg:ml-64"]');
                if (mainContent) {
                    mainContent.classList.add('main-content');
                }
            }

            let sidebarOpen = window.innerWidth >= 1024; // Default to open on large screens

            function toggleSidebar() {
                const mainContentEl = mainContent || document.querySelector('.main-content') || document.querySelector('[class*="lg:ml-64"]');
                sidebarOpen = !sidebarOpen;

                if (sidebarOpen) {
                    // Open sidebar
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    sidebarIcon.classList.remove('fa-bars');
                    sidebarIcon.classList.add('fa-times');

                    if (window.innerWidth >= 1024) {
                        // Desktop: adjust main content margin
                        if (mainContentEl) {
                            mainContentEl.classList.remove('sidebar-closed-desktop');
                            mainContentEl.classList.add('sidebar-open-desktop');
                        }
                    } else {
                        // Mobile: add overlay and prevent body scroll
                        createOverlay();
                        body.classList.add('sidebar-open-mobile');
                        if (mainContentEl) {
                            mainContentEl.classList.add('sidebar-open-mobile');
                        }
                    }
                } else {
                    // Close sidebar
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('translate-x-0');
                    sidebarIcon.classList.add('fa-bars');
                    sidebarIcon.classList.remove('fa-times');

                    if (window.innerWidth >= 1024) {
                        // Desktop: remove main content margin
                        if (mainContentEl) {
                            mainContentEl.classList.remove('sidebar-open-desktop');
                            mainContentEl.classList.add('sidebar-closed-desktop');
                        }
                    } else {
                        // Mobile: remove overlay and restore body scroll
                        removeOverlay();
                        body.classList.remove('sidebar-open-mobile');
                        if (mainContentEl) {
                            mainContentEl.classList.remove('sidebar-open-mobile');
                        }
                    }
                }

                // Add transition class for smooth animation
                if (mainContentEl) {
                    mainContentEl.classList.add('transition-sidebar');
                }
            }

            function createOverlay() {
                // Remove existing overlay if any
                removeOverlay();

                const overlay = document.createElement('div');
                overlay.id = 'sidebarOverlay';
                overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden';
                overlay.style.top = '64px'; // Below header
                document.body.appendChild(overlay);

                // Close sidebar when clicking overlay
                overlay.addEventListener('click', toggleSidebar);
            }

            function removeOverlay() {
                const overlay = document.getElementById('sidebarOverlay');
                if (overlay) {
                    overlay.remove();
                }
            }

            // Enhanced window resize handler for responsive behavior
            window.addEventListener('resize', function() {
                const mainContentEl = mainContent || document.querySelector('.main-content') || document.querySelector('[class*="lg:ml-64"]');
                const body = document.body;

                // Force a reflow to ensure proper measurement
                requestAnimationFrame(function() {
                    if (window.innerWidth >= 1024) {
                        // Desktop mode - remove mobile classes and apply desktop behavior
                        removeOverlay();
                        body.classList.remove('sidebar-open-mobile');

                        if (mainContentEl) {
                            // Remove all mobile classes
                            mainContentEl.classList.remove('sidebar-open-mobile');

                            // Apply proper desktop state based on sidebar status
                            if (sidebarOpen) {
                                mainContentEl.classList.remove('sidebar-closed-desktop');
                                mainContentEl.classList.add('sidebar-open-desktop');
                            } else {
                                mainContentEl.classList.remove('sidebar-open-desktop');
                                mainContentEl.classList.add('sidebar-closed-desktop');
                            }

                            // Ensure main-content class is present
                            mainContentEl.classList.add('main-content', 'transition-sidebar');
                        }

                        // Update sidebar visual state for desktop
                        if (sidebarOpen) {
                            sidebar.classList.remove('-translate-x-full');
                            sidebar.classList.add('translate-x-0');
                            sidebarIcon.classList.remove('fa-bars');
                            sidebarIcon.classList.add('fa-times');
                        } else {
                            sidebar.classList.add('-translate-x-full');
                            sidebar.classList.remove('translate-x-0');
                            sidebarIcon.classList.add('fa-bars');
                            sidebarIcon.classList.remove('fa-times');
                        }

                    } else {
                        // Mobile/Tablet mode - remove desktop classes and apply mobile behavior
                        if (mainContentEl) {
                            // Remove all desktop classes
                            mainContentEl.classList.remove('sidebar-open-desktop', 'sidebar-closed-desktop');

                            // Apply mobile state based on sidebar status
                            if (sidebarOpen) {
                                createOverlay();
                                body.classList.add('sidebar-open-mobile');
                                mainContentEl.classList.add('sidebar-open-mobile');

                                // Show sidebar on mobile when open
                                sidebar.classList.remove('-translate-x-full');
                                sidebar.classList.add('translate-x-0');
                                sidebarIcon.classList.remove('fa-bars');
                                sidebarIcon.classList.add('fa-times');
                            } else {
                                removeOverlay();
                                body.classList.remove('sidebar-open-mobile');
                                mainContentEl.classList.remove('sidebar-open-mobile');

                                // Hide sidebar on mobile when closed
                                sidebar.classList.add('-translate-x-full');
                                sidebar.classList.remove('translate-x-0');
                                sidebarIcon.classList.add('fa-bars');
                                sidebarIcon.classList.remove('fa-times');
                            }

                            // Ensure main-content class is present
                            mainContentEl.classList.add('main-content', 'transition-sidebar');
                        }
                    }
                });
            });

            // Keyboard shortcut to toggle sidebar (Ctrl + B)
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'b') {
                    e.preventDefault();
                    toggleSidebar();
                }
            });

            // Enhanced initialization with responsive state management
            const mainContentEl = mainContent || document.querySelector('[class*="lg:ml-64"]');

            // Force proper initialization based on screen size
            function initializeResponsiveState() {
                if (window.innerWidth >= 1024) {
                    // Desktop - sidebar open by default with proper content adjustment
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    sidebarIcon.classList.remove('fa-bars');
                    sidebarIcon.classList.add('fa-times');

                    if (mainContentEl) {
                        // Ensure all classes are properly set for desktop
                        mainContentEl.classList.add('main-content', 'sidebar-open-desktop', 'transition-sidebar');
                        mainContentEl.classList.remove('sidebar-closed-desktop', 'sidebar-open-mobile');
                    }
                    sidebarOpen = true;
                } else {
                    // Mobile/Tablet - sidebar closed by default
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('translate-x-0');
                    sidebarIcon.classList.add('fa-bars');
                    sidebarIcon.classList.remove('fa-times');

                    if (mainContentEl) {
                        // Ensure all classes are properly set for mobile
                        mainContentEl.classList.add('main-content', 'transition-sidebar');
                        mainContentEl.classList.remove('sidebar-open-desktop', 'sidebar-closed-desktop', 'sidebar-open-mobile');
                    }

                    // Clean up any mobile overlay states
                    removeOverlay();
                    document.body.classList.remove('sidebar-open-mobile');
                    sidebarOpen = false;
                }

                // Force a layout recalculation
                if (mainContentEl) {
                    mainContentEl.offsetHeight; // Trigger reflow
                }
            }

            // Initialize state
            initializeResponsiveState();

            // Re-initialize on orientation change (mobile devices)
            window.addEventListener('orientationchange', function() {
                setTimeout(function() {
                    initializeResponsiveState();
                }, 100);
            });

            // Add click event to toggle button
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Sidebar toggle clicked, current state:', sidebarOpen);
                    toggleSidebar();
                });
            } else {
                console.error('Sidebar toggle button not found!');
            }

            // Ensure icons exist
            if (!sidebarIcon) {
                console.error('Sidebar toggle icon not found!');
            }

            // Log initial state for debugging
            console.log('Sidebar initialized:', {
                sidebarOpen: sidebarOpen,
                screenWidth: window.innerWidth,
                mainContent: !!mainContent,
                sidebar: !!sidebar,
                sidebarToggle: !!sidebarToggle,
                sidebarIcon: !!sidebarIcon
            });

            // Close sidebar when clicking on menu items (mobile only)
            const menuItems = document.querySelectorAll('#sidebar a.menu-item, #sidebar a[class*="menu-item"]');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth < 1024 && sidebarOpen) {
                        setTimeout(() => {
                            toggleSidebar();
                        }, 150); // Small delay to allow click to register
                    }
                });
            });

            // Add smooth transitions to all navigation links
            const navLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript:"])');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Only show loading for same-origin links
                    if (this.hostname === window.location.hostname) {
                        showLoadingBar();
                    }
                });
            });

            // Add hover effects to user avatar
            const userAvatar = document.querySelector('.w-8.h-8.bg-blue-300');
            if (userAvatar) {
                userAvatar.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1) rotate(5deg)';
                });
                userAvatar.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1) rotate(0deg)';
                });
            }

            // Add pulse animation to role badge
            const roleBadge = document.querySelector('.bg-gradient-to-r.from-green-500');
            if (roleBadge) {
                setInterval(() => {
                    roleBadge.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        roleBadge.style.transform = 'scale(1)';
                    }, 200);
                }, 5000);
            }
        } // End of initializeSidebar function

        // Hide loading bar when page is fully loaded
        window.addEventListener('load', hideLoadingBar);
    </script>