# Sprint 3 - Application PHP / MySQL complète
**Projet Agile - Billetterie Train**

## 🎯 Objectif du Sprint 3
Passer du prototype en JavaScript (Sprints 1 et 2) à l'application demandée par la consigne : base de données MySQL, programmation en PHP et couverture de toutes les Epic Stories du Product Backlog fourni par le Product Owner.

---

## 👥 Équipe & Rôles
* **Product Owner :** M. L. Tran
* **Développeuse :** Ines Brahmi
* **Membres de l'équipe :** [Noms des autres membres si applicables]

---

## 📦 Stories incluses dans ce Sprint
* **Epic 1 (US1 à US5) :** recherche aller-retour, calendrier des prix, choix des horaires, options → **Terminé**
* **Epic 2 (US1, US2) :** panier (retirer / ajouter des options), récapitulatif → **Terminé**
* **Epic 3 (US1, US2) :** informations du voyageur, coordonnées bancaires → **Terminé**
* **Epic 4 (US1, US2) :** réduction adhérent (−10 €), paiement, billets PDF → **Terminé**
* **Epic 5 (TS1 à TS5) :** espace administrateur et gestion des comptes adhérents → **Terminé**

---

## 🛠️ Réalisations techniques
* Modèle Conceptuel de Données (9 tables) et création de la base MySQL (`database/schema.sql`).
* Jeu de données de démonstration : 7 gares, 5 520 voyages du 01/11/2026 au 31/01/2027 (`database/seed.sql`).
* Pages PHP avec requêtes préparées PDO, panier en session, paiement enregistré dans une transaction SQL.
* Génération des billets au format PDF sans librairie externe (`app/includes/pdf.php`).
* Sécurité : protection contre les injections SQL, les failles XSS et CSRF ; mots de passe hachés (bcrypt) ; seuls les 4 derniers chiffres de la carte bancaire sont stockés.

## ✅ Recette (scénario du Product Owner)
Aller Paris Gare de Lyon → Lyon Part-Dieu le 07/12/2026 à 8h (45 €), retour le 15/12/2026 à 10h (49 €), les 5 options (13,90 €), carte adhérent + code promo (−10 €) : **total payé 97,90 €**, billets PDF générés avec les numéros de réservation.
