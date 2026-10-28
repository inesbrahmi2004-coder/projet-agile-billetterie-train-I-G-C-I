# Product Backlog - Système de Réservation de Billets de Train
**Projet Agile - 3AE MSI / RSI (Ecam-Epmi)**  
**Professeur :** M. Tran  
**Date de soutenance :** 1er décembre 2026  

---

## 📋 Vue d'ensemble du Projet
Le projet consiste à développer une application web de réservation de billets de train ergonomique et intuitive, répondant aux standards du marché (type OUI.sncf / Trainline en version simplifiée).

---

## 📝 Liste des User Stories (US)

| ID | Titre de la User Story | Description (En tant que... Je veux... Afin de...) | Priorité | Estimation | Statut |
| :--- | :--- | :--- | :---: | :---: | :---: |
| **US01** | Recherche d'itinéraire | En tant qu'**utilisateur**, je veux **rechercher des trains en renseignant une gare de départ, d'arrivée et une date** afin de **trouver les trajets correspondants**. | **Haute** | 3 pts | $\color{green}{\text{Terminé}}$ |
| **US02** | Affichage des résultats | En tant qu'**utilisateur**, je veux **voir la liste des trains disponibles (horaires, prix, durée)** afin de **choisir le trajet qui me convient**. | **Haute** | 5 pts | $\color{orange}{\text{En cours}}$ |
| **US03** | Sélection du voyage | En tant qu'**utilisateur**, je veux **cliquer sur un train** pour **voir le détail des classes (1ère / 2nde) et valider mon choix**. | **Moyenne** | 3 pts | $\color{red}{\text{À faire}}$ |
| **US04** | Paiement / Réservation | En tant qu'**utilisateur**, je veux **saisir mes coordonnées et simuler un paiement** afin de **valider définitivement ma réservation**. | **Moyenne** | 5 pts | $\color{red}{\text{À faire}}$ |
| **US05** | Confirmation de commande | En tant qu'**utilisateur**, je veux **recevoir un récapitulatif de ma réservation avec un numéro de billet** afin de **pouvoir monter à bord**. | **Basse** | 2 pts | $\color{red}{\text{À faire}}$ |

---

## 🎯 Critères d'Acceptation (Exemple pour l'US01)
* **Scénario nominal :** L'utilisateur remplit les champs obligatoires (Départ, Arrivée, Date) et clique sur le bouton de recherche. Le système redirige ou affiche les correspondances.
* **Gestion des erreurs :** Si un champ est vide, le navigateur bloque l'envoi et demande de remplir le champ manquant.