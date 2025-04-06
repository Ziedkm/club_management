// scripts.js
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const closeMenu = document.getElementById('close-menu');
    const mobileMenu = document.querySelector('.mobile-menu');

    // Sidebar Selectors
    const themeToggle = document.getElementById('theme-toggle'); // Gets the first one (sidebar)
    const languageSelect = document.getElementById('language-select'); // Gets the first one (sidebar)

    // *** NEW: Mobile Menu Selectors using UNIQUE IDs ***
    const mobileThemeToggle = document.getElementById('mobile-theme-toggle');
    const mobileLanguageSelect = document.getElementById('mobile-language-select'); // Need this if uncommenting language logic

    // Scroll effect for sidebar
    window.addEventListener('scroll', () => {
        // Check if sidebar exists before adding/removing class
        if (sidebar) {
            if (window.scrollY > 10) {
                sidebar.classList.add('scrolled');
            } else {
                sidebar.classList.remove('scrolled');
            }
        }
    });

    // Toggle mobile menu (Improved Logic - no 'i' counter)
    if (menuToggle && mobileMenu) { // Check if elements exist
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('open'); // Simple toggle
        });
    }

    if (closeMenu && mobileMenu) { // Check if elements exist
        closeMenu.addEventListener('click', () => {
            mobileMenu.classList.remove('open'); // Just remove
        });
    }

    // Theme toggle
    const toggleTheme = () => {
        document.body.classList.toggle('dark');
        // Optional: Save preference
        // if (document.body.classList.contains('dark')) {
        //     localStorage.setItem('theme', 'dark');
        // } else {
        //     localStorage.setItem('theme', 'light');
        // }
    };

    // Apply theme on load (Optional)
    // if (localStorage.getItem('theme') === 'dark') {
    //     document.body.classList.add('dark');
    // }

    // Add listeners only if buttons exist
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    // *** USE CORRECT VARIABLE FOR MOBILE ***
    if (mobileThemeToggle) {
        mobileThemeToggle.addEventListener('click', toggleTheme);
    }


    /* Language Handling (Example if uncommented)
    const handleLanguageChange = (event) => {
        const selectedLanguage = event.target.value;
        console.log(`Language changed to: ${selectedLanguage}`);
        // Add actual language change logic here (e.g., redirect, load resources)
    };

    if (languageSelect) {
        languageSelect.addEventListener('change', handleLanguageChange);
    }
    // *** USE CORRECT VARIABLE FOR MOBILE ***
    if (mobileLanguageSelect) {
        mobileLanguageSelect.addEventListener('change', handleLanguageChange);
    }
    */


    // --- Active Nav Link Highlighting ---
    const currentPath = window.location.pathname; // e.g., /cm/home.php
    // Make selector more specific if needed, but this should be okay
    const navLinks = document.querySelectorAll('.nav-link, .nav-item');

    console.log("Current Path:", currentPath); // Debug
    console.log("Found Links:", navLinks.length); // Debug

    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');

        // Important Check: Ensure linkPath exists and is not empty
        if (linkPath) {
            console.log("Comparing:", currentPath, "with", linkPath); // Debug

            // *** Option 1: Direct Comparison (Often simpler and sufficient) ***
            if (linkPath === currentPath) {
                link.classList.add('active');
                 console.log("Activated (Direct Match):", linkPath); // Debug
            } else {
                link.classList.remove('active');
            }

            /* // *** Option 2: Normalization (Keep if direct comparison fails edge cases) ***
            const normalizedCurrentPath = currentPath.endsWith('/') ? currentPath : currentPath + '/';
            // Ensure linkPath exists before calling endsWith
            const normalizedLinkPath = linkPath.endsWith('/') ? linkPath : linkPath + '/';

            console.log("Normalized Compare:", normalizedCurrentPath, "with", normalizedLinkPath); // Debug

            if (normalizedLinkPath === normalizedCurrentPath) {
                link.classList.add('active');
                console.log("Activated (Normalized Match):", linkPath); // Debug
            } else {
                link.classList.remove('active');
            }
            */

        } else {
            console.warn("Link skipped (no href):", link); // Debug
             link.classList.remove('active'); // Ensure no active state if no href
        }
    });
}); // End of DOMContentLoaded