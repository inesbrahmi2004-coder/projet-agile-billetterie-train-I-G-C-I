# Product Backlog — Portail Web de réservation de billets de train

**Projet Agile 3AE — ECAM-EPMI**
**Product Owner (MOA) :** M. L. Tran
**Méthode :** Scrum — US (User Story) / TS (Technical Story)
**Soutenance :** 1er décembre 2026

---

## Epic Story 1 : Choix et sélection *(profil Voyageur)*

| ID | User Story | Réalisation | Statut |
|---|---|---|:---:|
| E1-US1 | En tant que voyageur, je veux réserver mes billets sur Internet sur le nouveau site europe.transports, donc l'accès au site doit être disponible. | `app/index.php` | ✅ Terminé |
| E1-US2 | En tant que voyageur, je voudrais un aller de Paris Gare de Lyon à Lyon Part-Dieu le 07/12/2026 et un retour le 15/12/2026. | Formulaire aller-retour, `app/index.php` | ✅ Terminé |
| E1-US3 | En tant que voyageur, je veux consulter le Calendrier des prix afin de choisir les moins chers possibles. | Calendrier sur 7 jours, meilleur prix en vert, `app/recherche.php` | ✅ Terminé |
| E1-US4 | En tant que voyageur, je veux sélectionner le billet Aller à 45 € et le Retour à 49 €, et choisir mes dates et heures (aller 07/12 à 8h, retour 15/12 à 10h). | Liste des horaires du jour, `app/recherche.php` | ✅ Terminé |
| E1-US5 | En tant que voyageur, je veux choisir mes options : place tranquille (+3 €), prise électrique (+2 €), bagage volumineux (+5 €), SMS (+1 €), garantie annulation (+2,90 €). | `app/options.php` | ✅ Terminé |

## Epic Story 2 : Panier et récapitulatif *(profil Voyageur)*

| ID | User Story | Réalisation | Statut |
|---|---|---|:---:|
| E2-US1 | En tant que voyageur, je veux visualiser mon Panier d'achat afin de retirer ou ajouter une ou plusieurs options. | Boutons Retirer / Ajouter, `app/panier.php` | ✅ Terminé |
| E2-US2 | En tant que voyageur, je veux valider mon panier et voir le récapitulatif de ma réservation. | `app/recapitulatif.php` | ✅ Terminé |

## Epic Story 3 : Saisie des informations du voyageur *(profil Voyageur)*

| ID | User Story | Réalisation | Statut |
|---|---|---|:---:|
| E3-US1 | En tant que voyageur, je veux saisir mes informations : M/Mme, Nom, Prénom, Tél mobile, E-mail, Confirmation d'e-mail. | Contrôles côté serveur, `app/voyageur.php` | ✅ Terminé |
| E3-US2 | En tant que voyageur, je veux saisir mes coordonnées bancaires afin de payer en ligne. | Contrôle de Luhn, expiration, cryptogramme, `app/paiement.php` | ✅ Terminé |

## Epic Story 4 : Réduction pour les adhérents et paiement par carte *(profil Voyageur)*

| ID | User Story | Réalisation | Statut |
|---|---|---|:---:|
| E4-US1 | En tant que voyageur adhérent, je saisis mon numéro de carte d'abonnement puis mon code de promotion (−10 €). | Vérification carte + code en base, `app/paiement.php` | ✅ Terminé |
| E4-US2 | En tant que voyageur, je veux payer par carte bancaire et imprimer mes billets aller/retour avec les numéros de réservation au format PDF. | `app/confirmation.php`, `app/billet.php` | ✅ Terminé |

## Epic Story 5 : Gestion de comptes adhérents *(profil Admin)*

| ID | Technical Story | Réalisation | Statut |
|---|---|---|:---:|
| E5-TS1 | En tant qu'Administrateur système, je me connecte à mon espace. | `app/admin/index.php` | ✅ Terminé |
| E5-TS2 | Message info après 2 tentatives sur fond rouge : « ATTENTION : C'est votre dernier essai… » | Compteur en base, blocage 5 min au 3ᵉ échec | ✅ Terminé |
| E5-TS3 | Implémenter « l'œil » pour voir en clair le mot de passe. | `app/assets/app.js` | ✅ Terminé |
| E5-TS4 | En tant qu'Administrateur, je veux créer un compte utilisateur sur demande du voyageur. | N° de carte généré automatiquement, `app/admin/tableau.php` | ✅ Terminé |
| E5-TS5 | En tant qu'Administrateur, je veux supprimer un compte utilisateur s'il n'est plus adhérent. | `app/admin/tableau.php` | ✅ Terminé |

---

## Travail à faire (consigne) — correspondance

| Consigne | Livrable |
|---|---|
| 1/ Décrire le Modèle Conceptuel de Données | [`docs/MCD.md`](MCD.md) + schéma [`docs/mcd.png`](mcd.png) |
| 2/ Créer la base et les tables sous MySQL | [`database/schema.sql`](../database/schema.sql) + données [`database/seed.sql`](../database/seed.sql) |
| 3/ Utiliser un langage de programmation (PHP) | PHP 8 + PDO, dossier [`app/`](../app) |
| 4/ Créer une IHM pour afficher les résultats | Pages web responsive (ordinateur et mobile) |
| 5/ Commenter les programmes | En-tête et commentaires dans chaque fichier PHP, SQL et JS |
