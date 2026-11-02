# europe.transports — Portail de réservation de billets de TGV

Projet Agile (Scrum) 3AE MSI / RSI — ECAM-EPMI — Enseignant : L. Tran

Site de réservation de billets de TGV aller/retour : calendrier des prix, options, panier, réduction adhérent, paiement par carte (simulé), billets PDF et espace administrateur.

**Technologies :** PHP 8 (PDO), MySQL, HTML, CSS et JavaScript. Polices Bricolage Grotesque et Instrument Sans incluses dans `app/assets/fonts` (licence OFL), donc le site s'affiche correctement même sans Internet.

## Arborescence
```
app/                  Application PHP (à servir par Apache / MAMP)
├── index.php         Accueil + recherche aller/retour          (Epic 1)
├── recherche.php     Calendrier des prix + choix des horaires  (Epic 1)
├── options.php       Options payantes                          (Epic 1)
├── panier.php        Panier : retirer / ajouter des options    (Epic 2)
├── recapitulatif.php Récapitulatif                             (Epic 2)
├── voyageur.php      Informations du voyageur                  (Epic 3)
├── paiement.php      Carte d'abonnement, code promo, CB        (Epic 3 et 4)
├── confirmation.php  Réservation confirmée                     (Epic 4)
├── billet.php        Billets aller/retour en PDF               (Epic 4)
├── admin/            Espace administrateur                     (Epic 5)
├── includes/         Connexion BDD, fonctions, en-tête, PDF
└── assets/           CSS et JavaScript
database/
├── schema.sql        Création de la base et des tables
└── seed.sql          Données de démonstration
docs/                 MCD, Product Backlog, rapports de sprint
client/               Prototype JavaScript des Sprints 1 et 2 (GitHub Pages)
```

## Installation avec MAMP (Mac)
1. Installer [MAMP](https://www.mamp.info/) et le lancer (**Start**).
2. Importer la base : MAMP → **Open WebStart page** → **Tools → phpMyAdmin** → onglet **Importer** :
   d'abord `database/schema.sql`, puis `database/seed.sql`.
3. Copier le dossier du projet dans `/Applications/MAMP/htdocs/`.
4. Ouvrir http://localhost:8888/projet-agile-billetterie-train-I-G-C-I/app/

Avec **XAMPP / WAMP** (Windows) : mettre le port `3306` et le mot de passe `''` dans `app/includes/config.php`, puis ouvrir http://localhost/projet-agile-billetterie-train-I-G-C-I/app/

Sans MAMP, en ligne de commande :
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
DB_PORT=3306 DB_PASS=votre_mdp php -S localhost:8000 -t app
```

## Données de test
| Élément | Valeur |
|---|---|
| Trajet du scénario | Paris Gare de Lyon ⇄ Lyon Part-Dieu, aller 07/12/2026 8h (45 €), retour 15/12/2026 10h (49 €) |
| Carte d'abonnement | `EU-2026-0001` (Jean Dupont) ou `EU-2026-0002` (Marie Martin) |
| Code promo (−10 €) | `PROMO10` (`EXPIRE` pour tester un code expiré) |
| Carte bancaire de test | `4111 1111 1111 1111`, expiration `12/28`, cryptogramme `123` |
| Administrateur | identifiant `admin`, mot de passe `Admin2026!` |

Les voyages couvrent la période du 01/11/2026 au 31/01/2027.

## Documentation Agile
- [Modèle Conceptuel de Données](docs/MCD.md)
- [Product Backlog](docs/backlog.md)
- [Sprint 1](docs/sprint1.md) · [Sprint 2](docs/sprint2.md) · [Sprint 3](docs/sprint3.md)

## Sécurité
Requêtes préparées (anti-injection SQL), échappement de l'affichage (anti-XSS), jeton CSRF sur tous les formulaires, mots de passe hachés en bcrypt, blocage du compte après 3 échecs de connexion, aucune donnée bancaire complète enregistrée (4 derniers chiffres seulement).
