<?php /** footer.php - Pied de page commun. */ ?>
</main>
<footer class="pied">
    <div class="pied-interieur">
        <p class="pied-logo">europe.transports</p>
        <p>Opérateur ferroviaire fictif. Site réalisé pour le Projet Agile 3AE, ECAM-EPMI.</p>
    </div>
</footer>
<script src="<?= $racine ?? '' ?>assets/app.js?v=<?= filemtime(__DIR__ . '/../assets/app.js') ?>"></script>
</body>
</html>
