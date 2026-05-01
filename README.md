[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/-qVJaaGL)

# Roulez.tn
 
> Plateforme tunisienne de location de véhicules entre particuliers — voitures, motos, scooters et vélos.

---

## 📋 Description du projet
 
**Roulez.tn** est un site web tunisien permettant la mise en relation entre particuliers pour la location de véhicules. Un propriétaire peut publier une annonce pour son véhicule inutilisé (voiture, moto, scooter ou vélo) avec ses disponibilités et son tarif. Un locataire peut parcourir les annonces, choisir un véhicule et réserver en ligne avec paiement sécurisé par carte bancaire. La plateforme prélève une commission de **3%** sur chaque transaction.

 
## 🗂️ Structure du projet
 
```
roulez_tn/
├── index.html           # Page d'accueil
├── browser.html          # Page de recherche et filtrage des véhicules
├── detail.html          # Fiche détail d'un véhicule + réservation
├── lend.html            # Formulaire de mise en location
├── css/
│   └── style.css        # Feuille de style externe unique
├── js/
│   └── main.js          # JavaScript externe
├── php/
│   ├── config.php       # Configuration base de données + session
│   ├── auth.php         # Inscription / Connexion / Déconnexion
│   ├── machines.php     # Gestion des annonces
│   └── bookings.php     # Gestion des réservations + paiement

└── database.sql         # Schéma SQL + données de démonstration