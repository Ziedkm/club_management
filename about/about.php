<?php
session_start();
// Make sure session is started if needed by other parts of the site,
// though not strictly required by the content below unless checking login status
// session_start();

// Include database connection and functions
require_once '../config/database.php';
// --- PHP Part: Define Team Member Data ---
include '../test2.php'; // Inclure l'en-tête de la page
$team_members = [
    [
        'name' => 'Rayen Harrathi',
        'title' => '',
        'image' => '../rayen.png', // Assurez-vous que le chemin commence par img/
        'social' => [
            'facebook' => 'https://www.facebook.com/SIKIPON48',
            'linkedin' => 'https://www.linkedin.com/in/harrathi-rayen-21b8532a1/',
        ]
    ],
    [
        'name' => 'Zied Kmanter',
        'title' => '',
        'image' => '../zied.jpg', // Assurez-vous que le chemin commence par img/
        'social' => [
            'facebook' => 'https://www.facebook.com/zied.kmantar.16',
            'linkedin' => 'https://www.linkedin.com/in/zkmanter/',
        ]
    ],
    [
        'name' => 'Yassmine Farhat',
        'title' => '',
        'image' => '../yassmine.jpg', // Assurez-vous que le chemin commence par img/
         'social' => [
            'facebook' => 'https://www.facebook.com/profile.php?id=100006258317901',
            'linkedin' => '#',
        ]
    ],
    [
        'name' => 'MejdEddine Fadhloun',
        'title' => '',
        'image' => '../majd.jpg', // Assurez-vous que le chemin commence par img/
        'social' => [
            'facebook' => 'https://www.facebook.com/mejdeddine.fadhloun',
            'linkedin' => 'https://www.linkedin.com/in/mejdeddine-fadhloun-3635b929a/',
        ]
    ],
    [
        'name' => 'Azer Farhat',
        'title' => '',
        'image' => '../azer.jpg', // Assurez-vous que le chemin commence par img/
        'social' => [
            'facebook' => 'https://www.facebook.com/farhatazerr/',
            'linkedin' => 'https://www.linkedin.com/in/azer-farhat/',
        ]
    ]
];

?>
<link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../styles2.css">
    <link rel="stylesheet" href="../footer.css">
    <link rel="stylesheet" href="styleabout.css">
    <main class="main-content pb-0  px-0 py-0" style="width: 100%;">

    <!-- ===== SECTION À PROPOS (Fond clair pleine largeur) ===== -->
    <section id="about-section" class="section-padding section-bg-light about-section-wrapper">
        <div class="container"> <!-- Contenu centré -->
            <h2 class="about-main-title">À propos de ClubNest</h2>
            <p class="about-subtitle">
                Découvrez notre mission et comment nous facilitons la vie associative.
            </p>
            <div class="about-card">
                <div class="about-banner">
                    <img src="../bannerr.jpg" alt="Étudiants regardant un paysage - Mission ClubMantra">
                    <h3 class="mission-overlay-text">Notre Mission</h3>
                </div>
                <div class="about-content">
                    <?php
                        $about_paragraph1 = "Clubnest a été créé en 2025 avec une vision claire : dynamiser la vie associative universitaire en connectant les étudiants avec les clubs et associations qui correspondent à leurs passions et aspirations.";
                        $about_paragraph2 = "Nous croyons fermement que l'engagement étudiant dans les activités extra-académiques est essentiel au développement personnel et professionnel. Notre plateforme facilite la découverte, l'inscription et la participation aux nombreux clubs et événements qui animent notre institue.";
                    ?>
                    <p><?php echo htmlspecialchars($about_paragraph1); ?></p>
                    <p><?php echo htmlspecialchars($about_paragraph2); ?></p>
                </div>
            </div>
        </div>
    </section>
    <!-- ===== FIN SECTION À PROPOS ===== -->


    <!-- ===== Contenu Principal (Section Valeurs centrée) ===== -->
    <main>
        <div class="container">

            <!-- --- Section Valeurs --- -->
            <section id="valeurs" class="section-padding values-section">
                <h2 class="section-title">Nos Valeurs</h2>
                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon"><i class="fas fa-users"></i></div>
                        <h3>Communauté</h3>
                        <p>Nous favorisons un sentiment d'appartenance et d'inclusion pour tous les étudiants à travers les activités associatives.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon"><i class="fas fa-book-open"></i></div>
                        <h3>Apprentissage</h3>
                        <p>Nous encourageons l'acquisition de compétences et de connaissances complémentaires à la formation académique.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon"><i class="fas fa-medal"></i></div>
                        <h3>Excellence</h3>
                        <p>Nous soutenons les initiatives étudiantes qui visent l'excellence et l'innovation dans leurs domaines respectifs.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon"><i class="fas fa-heart"></i></div>
                        <h3>Passion</h3>
                        <p>Nous croyons en la puissance de la passion comme moteur d'engagement et de réussite personnelle.</p>
                    </div>
                </div>
            </section>

            <!-- SECTION CONTACT SUPPRIMÉE -->

        </div> <!-- /.container -->
    </main>
    <!-- ===== FIN Contenu Principal ===== -->


    <!-- ===== SECTION ÉQUIPE (Fond clair pleine largeur) ===== -->
    <section id="team-section" class="section-padding section-bg-light team-section-padding">
        <div class="container"> <!-- Contenu centré -->
            <h2 class="section-title">Meet The Team</h2>
            <p class="section-description">
                La plateforme <span style=" color: #00408e; font-weight: bold;">CLUB</span><span class="highlight-dark">NEST</span> est réalisée comme un Projet
                universitaire en 2025 par des étudiants Business Intelligence.
            </p>
            <div class="team-members-grid">
                <?php foreach ($team_members as $member): ?>
                    <div class="team-member">
                        <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="Photo de <?php echo htmlspecialchars($member['name']); ?>">
                        <div class="member-info-overlay">
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p class="member-title"><?php echo htmlspecialchars($member['title']); ?></p>
                            <div class="social-links">
                                <?php if (!empty($member['social']['facebook']) && $member['social']['facebook'] !== '#'): ?>
                                    <a href="<?php echo htmlspecialchars($member['social']['facebook']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook de <?php echo htmlspecialchars($member['name']); ?>"><i class="fab fa-facebook-f"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($member['social']['linkedin']) && $member['social']['linkedin'] !== '#'): ?>
                                    <a href="<?php echo htmlspecialchars($member['social']['linkedin']); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn de <?php echo htmlspecialchars($member['name']); ?>"><i class="fab fa-linkedin-in"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- ===== FIN SECTION ÉQUIPE ===== -->


    <!-- ===== NOUVELLE SECTION LOCALISATION (Fond blanc par défaut) ===== -->
    <section id="localisation-section" class="section-padding">
    <div class="container">
        <h2 class="section-title">Localisation</h2>
        <div class="localisation-video-wrapper" style="position: relative; overflow: hidden; width: 100%; height: 100%;">
            <!-- Remplacement de l'iframe par une balise vidéo -->
            <video 
                id="localisation-video"
                width="100%"
                height="450"
                style="border:0;"
                muted
                playsinline
                preload="auto"
                title="Vidéo de localisation de ClubNest">
                <source src="..\isgSousse.mp4" type="video/mp4">
                Votre navigateur ne supporte pas la balise vidéo.
            </video>
        </div>
    </div>
</section>
<?php include '../footer.php'; ?>
</main>
    <!-- Script pour la lecture automatique de la vidéo lors du défilement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('localisation-video');
    
    // Options pour l'Intersection Observer
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.5 // 50% de la vidéo visible
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // La vidéo est visible, on la joue
                video.play().catch(error => {
                    console.log('Erreur de lecture automatique:', error);
                });
            } else {
                // La vidéo n'est plus visible, on la met en pause
                video.pause();
            }
        });
    }, options);

    // On observe la vidéo
    observer.observe(video);
});

</script>
<script src="../scripts.js"></script>

</body>
</html>