document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const closeMenu = document.getElementById('close-menu');
    const mobileMenu = document.querySelector('.mobile-menu');
    const themeToggle = document.getElementById('theme-toggle');
    const mobileThemeToggle = document.getElementById('mobile-theme-toggle');
    const languageSelect = document.getElementById('language-select');
 

    // Scroll effect for sidebar
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            sidebar.classList.add('scrolled');
            console.log('Scrolled!');
        } else {
            sidebar.classList.remove('scrolled');
        }
    });
    let i=0;
    // Toggle mobile menu
    
    menuToggle.addEventListener('click', () => {
        if (i%2==0){
            console.log('Toggle clicked!');
            mobileMenu.classList.add('open');
            i++;
        }
        else{
            mobileMenu.classList.remove('open');
            i++;
        }
        
    });

    closeMenu.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
            i++;
        ;
    });

    // Theme toggle
    const toggleTheme = () => {
        
        document.body.classList.toggle('dark');
        // Add logic to persist theme if desired (e.g., localStorage)
    };

    themeToggle.addEventListener('click', toggleTheme);
    mobileThemeToggle.addEventListener('click', toggleTheme);
/*

    // Language change
    const handleLanguageChange = (event) => {
        const selectedLanguage = event.target.value;
        console.log(`Language changed to: ${selectedLanguage}`);
        // Add language change logic here if needed
    };

    languageSelect.addEventListener('change', handleLanguageChange);
    mobileLanguageSelect.addEventListener('change', handleLanguageChange);
*/
    // Dynamic nav link highlighting
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link, .nav-item, .sidebar-link, .bottom-nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        // Normalize paths (optional, adjust based on your needs)
        const normalizedCurrentPath = currentPath.endsWith('/') ? currentPath : currentPath + '/';
        const normalizedLinkPath = linkPath && (linkPath.endsWith('/') ? linkPath : linkPath + '/');
    
        if (normalizedLinkPath === normalizedCurrentPath) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});