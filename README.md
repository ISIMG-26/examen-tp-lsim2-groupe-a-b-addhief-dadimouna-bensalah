# 🇹🇳 Roulez.tn - Location de Véhicules en Tunisie

## Configuration et Démarrage

### 1. **Base de données**
- Ouvrez **phpMyAdmin** : http://localhost/phpmyadmin
- Créez une nouvelle base de données (ou importez `database.sql`)
- La base doit s'appeler `roulez_tn`
- Importez les fichiers SQL fournis

### 2. **Configuration PHP**
- `config.php` contient les paramètres de connexion
- Base : `roulez_tn`
- Utilisateur : `root` (sans mot de passe par défaut dans XAMPP)

### 3. **Démarrage du serveur**
```
1. Ouvrez XAMPP Control Panel
2. Cliquez sur "Start" pour Apache et MySQL
3. Accédez à : http://localhost/mini_projet1/
```

### 4. **Fichiers importants**

| Fichier | Rôle |
|---------|------|
| `index.html` | Page d'accueil |
| `browser.html` | Recherche et annonces |
| `lend.html` | Ajouter une annonce |
| `detail.html` | Détails du véhicule & réservation |
| `auth.php` | Authentification (login/register) |
| `machines.php` | Gestion des véhicules |
| `bookings.php` | Gestion des réservations |
| `config.php` | Configuration BD & fonctions |
| `main.js` | Logique JavaScript partagée |
| `style.css` | Styles globaux |
| `uploads/` | Dossier pour les photos |

### 5. **Comptes de test**
```
Email: ahmed@example.com
Mot de passe: password

Email: fatma@example.com
Mot de passe: password
```
Les données de test sont dans `database.sql`

### 6. **Fonctionnalités principales**

✅ **Authentification** - Login et inscription  
✅ **Recherche** - Filtrer par type, ville, prix, dates  
✅ **Annonces** - Ajouter, consulter, réserver  
✅ **Réservations** - Gestion complète des réservations  
✅ **Paiement** - Validation de carte (simulation)  
✅ **Responsive** - Compatible mobile et desktop  

---

**Note:** Le site utilise du JavaScript natif (pas de framework).
