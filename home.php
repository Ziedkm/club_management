<?php
session_start(); // Uncomment if session is needed elsewhere
require_once 'config/database.php';
// Assuming getAllClubs() and getClubMembers() are defined
include_once 'header.php'; // Assumes header.php includes necessary basic HTML structure, potentially <head>

// --- Data Fetching (Keep as is) ---
$all_clubs = function_exists('getAllClubs') ? getAllClubs() : [];
$featured_clubs_data = []; // We'll fetch inside the loop for simplicity here
?>

<!-- Embedded Styles - REMOVE the link to styles2.css from your header.php -->
<style>
/* --- General Enhancements & Utilities --- */
:root {
    --primary-color-darker: hsl(210, 100%, 45%);
    --primary-color-lighter: hsl(210, 100%, 95%);
    --primary-color-dark-mode: hsl(210, 100%, 70%); /* Lighter blue for dark */
    --secondary-color: hsl(260, 80%, 65%); /* Example secondary */
    --text-color: #212529; /* Darker text */
    --muted-text-color: #6c757d;
    --border-color: #dee2e6;
    --card-bg: #ffffff;
    --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
    --card-hover-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

body.dark {
    --primary-color-darker: hsl(210, 100%, 60%);
    --primary-color-lighter: hsla(210, 100%, 70%, 0.1);
    --secondary-color: hsl(260, 80%, 75%);
    --background-color: #0a0a2a; /* Darker blue-ish */
    --text-color: #e9ecef;
    --muted-text-color: #adb5bd;
    --border-color: #343a40;
    --card-bg: #1a1a4a; /* Existing dark card bg */
    --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    --card-hover-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);

    /* --- ADD DARK MODE SPECIFICS FOR BASE ELEMENTS IF MISSING --- */
    background-color: var(--background-color);
    color: var(--text-color);

  

     #language-selector select{
        background-color: #555;
        color: #e0e0e0; /* Lighter text for dark mode */
        border-color: #1E90FF; /* Keep or adjust */
    }
    #theme-toggle{
        color: white;
        background-color: var(--card-bg); /* Adjust based on where toggle is */
    }
     .main-content { /* Ensure main content bg is dark */
        background-color: var(--background-color);
        color: var(--text-color);
    }
     .nav-link:hover {
        color: rgb(255, 255, 255);
        background-color: rgb(59 130 246 / .5);
    }
    .nav-link {
        color: hsl(210, 100%, 75%); /* Lighter blue */
    }
    .text-muted { color: var(--muted-text-color); }
}

/* --- Ensure base body styles are present --- */
body {
    margin: 0;
    background-color: var(--background-color);
    color: var(--text-color);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; /* Modern Font Stack */
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    line-height: 1.5; /* Default line height */
}




/* --- Animation --- */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-30px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Apply animations using classes */
.animate-fade-up { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
.animate-fade-in-left { animation: fadeInLeft 0.7s ease-out forwards; opacity: 0; }
.animate-fade-in-right { animation: fadeInRight 0.7s ease-out forwards; opacity: 0; }
.animate-fade-in { animation: fadeIn 0.8s ease-out forwards; opacity: 0; }


/* --- Buttons Enhancement --- */
.btn {
   
    transition: all 0.3s ease; /* Smoother transition */
    border: 1px solid transparent; /* Base border */
    
}

.btn-primary {
    background-color: var(--primary-color);
    color: #ffffff;
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--primary-color-darker);
    border-color: var(--primary-color-darker);
    transform: translateY(-2px); /* Subtle lift */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
body.dark .btn-primary:hover {
     box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
     background-color: var(--primary-color-darker); /* Explicitly set for dark */
     border-color: var(--primary-color-darker);
}


.btn-secondary { /* Added secondary style */
    background-color: var(--card-bg); /* Use card bg (white in light, dark in dark) */
    color: var(--primary-color);
    border: 1px solid var(--border-color);
}
/* No specific body.dark needed for bg if --card-bg is correct */

.btn-secondary:hover {
    background-color: var(--primary-color-lighter); /* light blue bg */
    border-color: var(--primary-color);
    color: var(--primary-color-darker);
     transform: translateY(-2px);
}
body.dark .btn-secondary:hover {
    background-color: hsla(210, 100%, 70%, 0.1); /* Use the lighter primary variable */
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-color-lighter); /* Use variable */
    color: var(--primary-color-darker);
    transform: translateY(-2px);
}
body.dark .btn-outline:hover {
    background-color: hsla(210, 100%, 70%, 0.1); /* Use the lighter primary variable for dark */
     color: var(--primary-color);
}

.btn-lg { /* Larger button variant */
    padding: 1rem 2rem;
    font-size: 1.1rem;
}


/* --- Hero Section --- */
.hero-section {
    background: linear-gradient(135deg, hsla(210, 100%, 98%, 0.5) 0%, hsla(210, 100%, 95%, 0) 70%), var(--background-color);
    position: relative; /* For potential absolute elements */
    overflow: hidden; /* Contain background shapes */
    padding-top: 4rem; /* Adjusted padding */
    padding-bottom: 5rem;
}
body.dark .hero-section {
    background: linear-gradient(135deg, hsla(210, 100%, 70%, 0.1) 0%, hsla(210, 100%, 50%, 0) 70%), var(--background-color);
}

.hero-content-wrapper { /* Wrapper inside container if needed */
     display: grid;
     grid-template-columns: 1fr; /* Mobile first */
     gap: 2.5rem;
     align-items: center;
     position: relative; /* Ensure content is above background elements */
     z-index: 10;
}

@media (min-width: 992px) { /* lg breakpoint approx */
    .hero-section {
         padding-top: 6rem;
         padding-bottom: 7rem;
    }
    .hero-content-wrapper {
         grid-template-columns: repeat(2, 1fr);
         gap: 3rem;
         text-align: left; /* Align text left on larger screens */
    }
     .hero-text {
        order: 1; /* Text first */
    }
    .hero-image {
        order: 2; /* Image second */
    }
}


.hero-text {
    /* Styles applied directly or within media query */
     text-align: center; /* Default center */
}


.hero-title {
    font-size: clamp(2.2rem, 5vw, 3.2rem); /* Adjusted clamp */
    font-weight: 800; /* Extra bold */
    line-height: 1.25;
    color: var(--text-color);
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: clamp(1rem, 2.5vw, 1.1rem); /* Adjusted clamp */
    color: var(--muted-text-color);
    line-height: 1.6;
    max-width: 550px; /* Limit width */
    margin-left: auto; /* Center on mobile/default */
    margin-right: auto;
    margin-bottom: 2rem;
}
@media (min-width: 992px) {
    .hero-subtitle {
        margin-left: 0; /* Align left on desktop */
        margin-right: 0;
    }
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem; /* Consistent gap */
    justify-content: center; /* Center buttons by default */
}
@media (min-width: 992px) {
    .hero-actions {
        justify-content: flex-start; /* Align left on desktop */
    }
}


.hero-image {
    position: relative; /* For potential layering */
    text-align: center; /* Center image */
    
}
.hero-image .hero-illustration {
    max-width: 90%; /* Control size */
    height: auto;
    margin-left: auto;
    margin-right: auto;
    display: block; /* Remove extra space */
}
@media (min-width: 992px) {
    .hero-image .hero-illustration {
        max-width: 80%;
    }
}


/* --- Section Headers --- */
.section-header {
    text-align: center;
    margin-bottom: 3rem; /* More space */
    max-width: 700px; /* Limit width */
    margin-left: auto;
    margin-right: auto;
}
.section-header .section-title {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 0.75rem; /* Space below title */
}
.section-header .section-subtitle {
    font-size: clamp(1rem, 2.5vw, 1.1rem);
    color: var(--muted-text-color);
    line-height: 1.6;
}

/* --- Features Section --- */
.features-section {
    background-color: var(--card-bg); /* Use card bg for contrast */
     border-top: 1px solid var(--border-color);
     border-bottom: 1px solid var(--border-color);
     padding-top: 4rem;
     padding-bottom: 4rem;
}
body.dark .features-section {
    background-color: var(--background-color); /* Dark section bg */
    border-color: var(--border-color);
}
@media (min-width: 992px) {
    .features-section {
        padding-top: 5rem;
        padding-bottom: 5rem;
    }
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Slightly larger min */
    gap: 1.5rem; /* Default gap */
}
@media (min-width: 768px) {
    .features-grid {
        gap: 2rem; /* More gap on larger screens */
    }
}


.feature-card { /* Enhancing .card for features */
    text-align: center;
    padding: 2rem 1.5rem; /* More padding */
    background-color: var(--background-color); /* Card background different from section */
    border: 1px solid var(--border-color);
    border-radius: 12px; /* More rounding */
    box-shadow: var(--card-shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
body.dark .feature-card {
    background-color: var(--card-bg); /* Use dark card bg */
    border-color: var(--border-color); /* Ensure dark border */
}
.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-hover-shadow);
}

.feature-icon-background {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4rem; /* Larger icon bg */
    height: 4rem;
    border-radius: 50%;
     /* Gradient background */
    background: linear-gradient(135deg, var(--primary-color-lighter), hsla(210, 100%, 56%, 0.2));
    margin-bottom: 1.5rem; /* More space */
    color: var(--primary-color); /* Icon color */
}
.feature-icon-background i {
    font-size: 1.75rem; /* Larger icon */
    line-height: 1; /* Prevent icon descenders causing alignment issues */
}
body.dark .feature-icon-background {
     background: linear-gradient(135deg, hsla(210, 100%, 70%, 0.1), hsla(210, 100%, 70%, 0.25));
     color: var(--primary-color); /* Use the dark mode primary variable */
}
.feature-card .card-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-color); /* Ensure text color */
}
.feature-card .card-description {
    font-size: 0.95rem;
    color: var(--muted-text-color);
    line-height: 1.6;
}

/* --- Featured Clubs Section --- */
.featured-clubs-section {
    padding-top: 4rem;
    padding-bottom: 4rem;
}
@media (min-width: 992px) {
    .featured-clubs-section {
        padding-top: 5rem;
        padding-bottom: 5rem;
    }
}

.clubs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 1.5rem;
}
@media (min-width: 768px) {
    .clubs-grid {
        gap: 2rem;
    }
}

.club-card { /* Enhancing .card for clubs */
     background-color: var(--card-bg);
     border: 1px solid var(--border-color);
     border-radius: 12px;
     box-shadow: var(--card-shadow);
     transition: transform 0.3s ease, box-shadow 0.3s ease;
     display: flex; /* Flex layout for better control */
     flex-direction: column;
     overflow: hidden; /* Contain image placeholder */
}
.club-card:hover {
     transform: translateY(-5px);
     box-shadow: var(--card-hover-shadow);
}

.club-image-placeholder {
    height: 180px; /* Taller placeholder */
    display: flex;
    align-items: center;
    justify-content: center;
    /* Removed top radius, handled by overflow:hidden on parent */
    border-bottom: 1px solid var(--border-color);
    overflow: hidden; /* Ensure gradients don't overflow */
    position: relative; /* For potential overlays */
}
body.dark .club-image-placeholder {
    border-bottom-color: var(--border-color); /* Ensure dark border */
}
.club-image-placeholder i {
    font-size: 3.5rem; /* Larger icon */
    opacity: 0.6;
    z-index: 5; /* Above background pattern */
    line-height: 1;
}

/* Example placeholder styles - cycle using PHP class */
.club-image-placeholder.placeholder-1 {
    background: linear-gradient(135deg, hsl(210, 80%, 92%), hsl(210, 80%, 85%));
    color: hsl(210, 60%, 60%);
}
.club-image-placeholder.placeholder-2 {
    background: linear-gradient(135deg, hsl(160, 70%, 92%), hsl(160, 70%, 85%));
    color: hsl(160, 50%, 60%);
}
.club-image-placeholder.placeholder-3 {
    background: linear-gradient(135deg, hsl(30, 90%, 92%), hsl(30, 90%, 85%));
    color: hsl(30, 70%, 60%);
}

body.dark .club-image-placeholder.placeholder-1 {
    background: linear-gradient(135deg, hsl(210, 30%, 30%), hsl(210, 30%, 20%));
    color: hsl(210, 50%, 70%);
}
body.dark .club-image-placeholder.placeholder-2 {
     background: linear-gradient(135deg, hsl(160, 30%, 30%), hsl(160, 30%, 20%));
     color: hsl(160, 40%, 70%);
}
body.dark .club-image-placeholder.placeholder-3 {
     background: linear-gradient(135deg, hsl(30, 30%, 30%), hsl(30, 30%, 20%));
     color: hsl(30, 50%, 70%);
}

.club-card .card-content {
    padding: 1.25rem; /* Consistent padding */
    flex-grow: 1; /* Allow content to fill space */
    display: flex;
    flex-direction: column;
}
.club-card .card-title {
    font-size: 1.2rem; /* Slightly smaller */
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-color); /* Ensure text color */
}
.club-card .card-description {
    font-size: 0.9rem;
    color: var(--muted-text-color);
    line-height: 1.5;
    margin-bottom: 1rem;
    flex-grow: 1; /* Push tags/button down */
}
.club-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem; /* More space before button */
}
.tag {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.8rem; /* Adjust padding */
    border-radius: 9999px;
    font-size: 0.75rem; /* Smaller tags */
    font-weight: 500;
    line-height: 1.4;
}
.tag i {
    margin-right: 0.3rem;
    font-size: 0.8em; /* Smaller icon in tag */
    opacity: 0.9;
    line-height: 1;
}
.tag-members {
    background-color: var(--primary-color-lighter);
    color: var(--primary-color);
}
.tag-category {
     background-color: #e9ecef; /* Lighter gray */
     color: #495057;
}
body.dark .tag-members {
    background-color: hsla(210, 100%, 70%, 0.15); /* Use variable */
    color: var(--primary-color); /* Use variable */
}
body.dark .tag-category {
     background-color: #343a40; /* Darker gray */
     color: #ced4da; /* Light gray text */
}
.club-card .btn { /* Style button within card */
    margin-top: auto; /* Push button to bottom */
}


/* --- Call to Action Section --- */
.cta-section {
    background-color: var(--primary-color-lighter);
    color: var(--primary-color-darker); /* Text color contrasts with light bg */
    text-align: center;
    padding-top: 4rem;
    padding-bottom: 4rem;
}
body.dark .cta-section {
    background-color: #1a1a4a; /* Darker background */
    color: #fff; /* White text */
}
@media (min-width: 992px) {
    .cta-section {
        padding-top: 5rem;
        padding-bottom: 5rem;
    }
}

.cta-content {
    max-width: 700px;
    margin: 0 auto;
}
.cta-title {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 700;
    color: inherit; /* Inherit from section */
    margin-bottom: 1rem;
}
.cta-subtitle {
    font-size: clamp(1rem, 2.5vw, 1.1rem);
    color: inherit; /* Inherit */
    opacity: 0.85;
    line-height: 1.6;
    margin-bottom: 2rem;
}
body.dark .cta-subtitle {
     opacity: 0.9;
}
.cta-section .btn-primary {
    /* Make CTA button stand out more */
    background-color: #fff;
    color: var(--primary-color);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
body.dark .cta-section .btn-primary {
    background-color: var(--primary-color);
    color: #111; /* Dark text on light blue */
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    border-color: var(--primary-color); /* Ensure border color */
}
.cta-section .btn-primary:hover {
     background-color: #f8f9fa;
     color: var(--primary-color-darker);
     transform: translateY(-3px) scale(1.05);
}
body.dark .cta-section .btn-primary:hover {
    background-color: var(--primary-color-darker);
     color: #fff;
     border-color: var(--primary-color-darker);
}

/* --- Responsive Adjustments & Mobile Nav Padding --- */
/* Add padding to main content to prevent overlap with fixed bottom nav */
body.has-bottom-nav .main-content {
    padding-bottom: 5rem; /* Adjust value based on bottom nav height */
}

@media (max-width: 767px) {
    /* Hero adjustments */
    .hero-section {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
     .hero-content-wrapper {
        /* Already mobile first grid */
        gap: 2rem;
     }
    .hero-actions .btn {
        width: 100%; /* Full width buttons */
        max-width: 320px;
    }
    .hero-image .hero-illustration {
         max-width: 75%; /* Smaller image on mobile */
    }

    /* Section padding */
    .features-section, .featured-clubs-section, .cta-section {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }

    /* Reduce gaps */
    .features-grid, .clubs-grid {
        gap: 1.5rem;
    }

    .section-header {
        margin-bottom: 2rem;
    }
    .section-header .section-title {
        font-size: 1.8rem;
    }
     .section-header .section-subtitle {
        font-size: 1rem;
    }

    /* Ensure main-content uses full width available unless sidebar is visible */
    .main-content {
        margin-left: 0; /* Remove margin when sidebar is hidden */
        /* padding-bottom is handled by body.has-bottom-nav */
    }
    
}

/* Desktop Sidebar Styles (Only apply when sidebar is visible) */
@media (min-width: 768px) {
    
   
   
}


/* --- ADD ANY OTHER MISSING STYLES (e.g., from styles.css if needed) --- */
/* Example: Basic Form Styles if not inherited */
.form-group { margin-bottom: 1rem; }
label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
input[type="text"], input[type="email"], input[type="password"], textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 1rem;
    background-color: var(--background-color); /* Use light bg */
    color: var(--text-color);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
input:focus, textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px hsla(210, 100%, 56%, 0.2);
}
body.dark input[type="text"], body.dark input[type="email"], body.dark input[type="password"], body.dark textarea {
     background-color: #343a40; /* Darker input */
     border-color: #495057;
     color: #e0e0e0;
}
body.dark input:focus, body.dark textarea:focus {
     border-color: var(--primary-color); /* Dark mode primary */
     box-shadow: 0 0 0 3px hsla(210, 100%, 70%, 0.25);
}


/* Utility Classes from original files (ensure they exist if used in HTML) */
.text-primary { color: var(--primary-color); }
.text-center { text-align: center; }
.mb-4 { margin-bottom: 1rem; }
.mb-8 { margin-bottom: 2rem; }
.mt-8 { margin-top: 2rem; }
.mt-12 { margin-top: 3rem; }
/* Add any other utilities like mx-auto, p-6, shadow-md, rounded-lg if you rely on them heavily and they weren't part of the core styles */

</style>

<!-- Main Content Area -->
<main class="main-content pb-0 px-0 py-0"> <!-- Removed initial padding, handled by sections -->

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container"> 
            <div class="hero-content-wrapper"> 
                <div class="hero-text animate-fade-in-left">
                    <h1 class="hero-title"> 
                        Connect, Engage, Thrive <span class="text-primary">.</span>
                    </h1>
                    <p class="hero-subtitle"> 
                        Discover diverse clubs, join vibrant communities, and stay updated on campus events. ISG Clubs is your central hub for university life.
                    </p>
                    <div class="hero-actions">
                        <a href="/cm/clubs.php" class="btn btn-primary shadow-lg transform hover:scale-105">
                            Explore Clubs Now <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <a href="/cm/login.php" class="btn btn-outline transform hover:scale-105">
                            Join the Community
                        </a>
                    </div>
                </div>
                <div class="hero-image animate-fade-in-right">
                     <img src="assets/images/7826321.webp" alt="Students interacting and connecting through clubs" class="hero-illustration" >
                    
                </div>
            </div>
        </div>
        
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header animate-fade-in"> 
                <h2 class="section-title">Why Choose ISG Clubs?</h2> 
                <p class="section-subtitle"> 
                    Streamline your club experience – whether you're looking to join, manage, or just stay informed.
                </p>
            </div>

            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card animate-fade-up" data-delay="100">
                    <div class="feature-icon-background">
                         <i class="fas fa-search"></i>
                    </div>
                    <h3 class="card-title">Effortless Discovery</h3>
                    <p class="card-description">
                        Easily browse, search, and filter clubs by category or interest. Find your perfect match in minutes.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card animate-fade-up" data-delay="200">
                    <div class="feature-icon-background">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="card-title">Stay Updated</h3>
                    <p class="card-description">
                        Never miss out. Get timely notifications for upcoming events, meetings, and important announcements.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card animate-fade-up" data-delay="300">
                     <div class="feature-icon-background">
                         <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="card-title">Simplified Management</h3>
                    <p class="card-description">
                        Club leaders can easily manage members, post events, send messages, and track engagement.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Clubs Section -->
    <section class="featured-clubs-section" id="clubs">
         <div class="container">
            <div class="section-header animate-fade-in"> 
                <h2 class="section-title">Explore Popular Clubs</h2> 
                <p class="section-subtitle"> 
                    Get a glimpse of the vibrant communities thriving on campus right now.
                </p>
            </div>

            <div class="clubs-grid">
                <?php
                $clubs = function_exists('getAllClubs') ? getAllClubs() : [];
                $count = 0;
                foreach ($clubs as $club) {
                    if ($count >= 3) break;
                    if ($club['status'] !== 'active') continue; // Only show active clubs

                    $members = function_exists('getClubMembers') ? getClubMembers($club['id']) : [];
                    $memberCount = count($members);
                    $animation_delay = $count * 150; // Stagger animation
                    $club_image_placeholder_class = 'placeholder-' . (($club['id'] % 3) + 1);
                ?>
                <div class="club-card animate-fade-up" data-delay="<?php echo $animation_delay; ?>">
                    <div class="club-image-placeholder <?php echo $club_image_placeholder_class; ?>">
                         <i class="fas fa-users default-banner-icon"></i>
                    </div>
                    <div class="card-content"> 
                        <h3 class="card-title"><?php echo htmlspecialchars($club['name']); ?></h3> 
                        <p class="card-description">
                            <?php echo htmlspecialchars(substr($club['description'], 0, 90)) . (strlen($club['description']) > 90 ? '...' : ''); ?>
                        </p>
                        <div class="club-tags">
                            <span class="tag tag-members">
                                <i class="fas fa-users"></i> <?php echo $memberCount; ?> Members
                            </span>
                            <span class="tag tag-category">
                                <?php echo htmlspecialchars($club['category']); ?>
                            </span>
                        </div>
                        <a href="/cm/club-detail.php?page=club-detail&id=<?php echo $club['id']; ?>" class="btn btn-primary w-full">
                            View Details
                        </a>
                    </div>
                </div>
                <?php
                    $count++;
                }

                if ($count === 0) {
                     // Use a div for better centering/styling if needed
                     echo '<div class="col-span-full text-center text-muted py-8">'; // Assuming grid parent
                     echo 'No active clubs featured currently. Check back soon!';
                     echo '</div>';
                }
                ?>
            </div>

            <div class="text-center mt-12 animate-fade-in">
                <a href="/cm/clubs.php" class="btn btn-secondary group">
                    Discover All Clubs
                    <i class="fas fa-arrow-right ml-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- big image section with fading borders -->
    <section class="big-image-section" id="big-image">
        
            <div class="text-center animate-fade-in">
                <img src="assets/images/app ios android.jpg" alt="Students interacting and connecting through clubs" class="hero-illustration" >
                
            
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
         <div class="container">
            <div class="cta-content animate-fade-in">
                <h2 class="cta-title">Ready to Dive In?</h2>
                <p class="cta-subtitle">
                    Join ISG Clubs today to connect with peers, explore your passions, and make the most of your university experience.
                </p>
                <a href="/cm/register.php" class="btn btn-primary btn-lg shadow-lg transform hover:scale-105">
                    Sign Up Free
                </a>
            </div>
        </div>
    </section>
    <?php
// Include footer
include 'footer.php'; // Assumes footer.php includes closing </body> and </html>, and potentially JS
?>

</main> <!-- Closed main content -->




<!-- Add JS Snippet (Ideally in footer.php before </body>) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Staggered animation delay
  const animatedElements = document.querySelectorAll('[data-delay]');
  animatedElements.forEach(el => {
    el.style.animationDelay = `${el.dataset.delay}ms`;
  });

  // Add class to body if bottom nav exists (for padding adjustment)
  // Check if the mobile bottom nav element is present and potentially visible
  const bottomNav = document.querySelector('.bottom-nav');
  if (bottomNav && window.getComputedStyle(bottomNav).display !== 'none') {
     document.body.classList.add('has-bottom-nav');
  }
  // Optional: Re-check on resize if layout changes drastically
  // window.addEventListener('resize', () => { ... });
});
</script>

</body>
</html>