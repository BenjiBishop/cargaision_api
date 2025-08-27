# API Gestion Cargaison - GrP du Monde

## 🚀 Description

API REST complète pour la gestion des cargaisons et colis de l'entreprise GrP du Monde. Cette application permet de gérer le transport maritime, aérien et routier de différents types de produits (alimentaires, chimiques, matériels).

## 📋 Fonctionnalités

### Gestion des Cargaisons
- ✅ Création de cargaisons (maritime, aérienne, routière)
- ✅ Recherche par code, lieu, date, type
- ✅ Fermeture et réouverture des cargaisons
- ✅ Suivi de l'état d'avancement
- ✅ Calcul automatique des prix

### Gestion des Colis
- ✅ Enregistrement des colis avec informations client
- ✅ Génération automatique de codes de suivi
- ✅ Calcul des prix selon les tarifs
- ✅ Suivi en temps réel
- ✅ Gestion des états (en attente, en cours, arrivé, récupéré, perdu, archivé)

### Gestion des Produits
- ✅ Produits alimentaires (tous types de transport)
- ✅ Produits chimiques (maritime uniquement, avec degré de toxicité)
- ✅ Produits matériels fragiles (aérien et routier uniquement)
- ✅ Produits matériels incassables (tous types de transport)

### Fonctionnalités Métier
- ✅ Validation des règles de transport par type de produit
- ✅ Prix minimum de 10.000 F par colis
- ✅ Maximum 10 produits par cargaison
- ✅ Archivage automatique des colis arrivés
- ✅ Annulation possible avant fermeture de cargaison

## 🏗 Architecture

```
cargo-api/
├── public/
│   ├── index.php          # Point d'entrée principal
│   └── .htaccess          # Configuration Apache
├── src/
│   ├── Models/
│   │   ├── Produit/       # Classes produits (Alimentaire, Chimique, Matériel)
│   │   ├── Cargaison/     # Classes cargaisons (Maritime, Aérienne, Routière)
│   │   ├── Client.php     # Modèle Client
│   │   └── Colis.php      # Modèle Colis
│   ├── Controllers/       # Contrôleurs API
│   ├── Database/          # Gestion base de données
│   ├── Services/          # Services métier
│   └── Router/            # Routeur API
├── config/
│   └── database.php       # Configuration DB
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## 🚢 Installation avec Docker

### Prérequis
- Docker et Docker Compose installés
- Port 80 et 3306 disponibles

### Démarrage rapide

1. **Cloner et configurer**
```bash
git clone <votre-repo>
cd cargo-api
```

2. **Démarrer les services**
```bash
docker-compose up -d
```

3. **Exécuter les migrations**
```bash
# Attendre que MySQL soit prêt
sleep 30

# Exécuter les migrations
docker-compose exec mysql mysql -u cargo_user -ppasser cargo_db < src/Database/migrations.sql
```

4. **Tester l'API**
```bash
curl http://localhost/api/health
```

## 📚 Documentation API

### Base URL
```
http://localhost/api
```

### Endpoints principaux

#### 🚢 Cargaisons

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/cargaisons` | Créer une cargaison |
| GET | `/cargaisons/{id}` | Obtenir une cargaison |
| GET | `/cargaisons/numero/{numero}` | Recherche par numéro |
| GET | `/cargaisons` | Rechercher avec filtres |
| PUT | `/cargaisons/{id}/close` | Fermer une cargaison |
| PUT | `/cargaisons/{id}/reopen` | Rouvrir une cargaison |

#### 📦 Colis

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/colis` | Créer un colis |
| GET | `/colis/{code}` | Obtenir par code |
| PUT | `/colis/{id}/received` | Marquer comme récupéré |
| PUT | `/colis/{id}/lost` | Marquer comme perdu |
| PUT | `/colis/{id}/archive` | Archiver |
| PUT | `/colis/{id}/cancel` | Annuler |

#### 👥 Clients

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/clients` | Lister les clients |
| GET | `/clients/{id}` | Obtenir un client |

#### 📍 Suivi

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/suivi/{code}` | Suivre un colis |

## 💡 Exemples d'utilisation

### 1. Créer une cargaison maritime

```bash
curl -X POST http://localhost/api/cargaisons \
  -H "Content-Type: application/json" \
  -d '{
    "numero": "MAR001",
    "poids_max": 5000,
    "lieu_depart": "Dakar",
    "lieu_arrivee": "Marseille",
    "distance_km": 4500,
    "type": "maritime",
    "coordonnees_depart": "14.6928,-17.4467",
    "coordonnees_arrivee": "43.2965,5.3698"
  }'
```

### 2. Créer un colis

```bash
curl -X POST http://localhost/api/colis \
  -H "Content-Type: application/json" \
  -d '{
    "client": {
      "nom": "Diop",
      "prenom": "Amadou",
      "telephone": "+221771234567",
      "adresse": "Plateau, Dakar",
      "email": "amadou.diop@email.com"
    },
    "nombre_colis": 2,
    "poids_total": 25.5,
    "type_produit": "alimentaire",
    "type_cargaison": "maritime",
    "distance": 4500,
    "destinataire": {
      "nom": "Martin",
      "telephone": "+33123456789",
      "adresse": "13001 Marseille"
    }
  }'
```

### 3. Suivre un colis

```bash
curl http://localhost/api/suivi/COL20250816ABC123
```

### 4. Rechercher des cargaisons

```bash
curl "http://localhost/api/cargaisons?lieu_depart=Dakar&type=maritime&page=1&limit=10"
```

## 🧪 Tests

### Exécuter les tests unitaires
```bash
php test.php
```

### Tests des règles métier

Le fichier `test.php` inclut des tests pour :
- ✅ Création de cargaisons de chaque type
- ✅ Ajout de produits avec calcul de prix
- ✅ Validation des restrictions de transport
- ✅ Limite de 10 produits par cargaison
- ✅ Fermeture/réouverture des cargaisons
- ✅ Gestion des erreurs

## 📊 Tarification

### Produits Alimentaires
- **Routière** : 100 F/kg/km
- **Maritime** : 90 F/kg/km + 5000 F (chargement)
- **Aérienne** : 300 F/kg/km

### Produits Chimiques (Maritime uniquement)
- **Maritime** : 500 F/kg × degré toxicité + 10000 F (entretien) + 5000 F (chargement)

### Produits Matériels
- **Routière** : 80 F/kg/km
- **Maritime** : 400 F/kg/km + 5000 F (chargement)
- **Aérienne** : 1000 F/kg

### Restrictions
- **Prix minimum** : 10.000 F par colis
- **Produits chimiques** : Maritime uniquement
- **Produits fragiles** : Aérien et routier uniquement
- **Maximum** : 10 produits par cargaison

## 🔧 Configuration

### Variables d'environnement
```bash
DB_HOST=mysql
DB_NAME=cargo_db
DB_USER=cargo_user
DB_PASSWORD=passer
APP_PORT=80
```

### Base de données
- **SGBD** : MySQL 8.0
- **Encodage** : UTF8MB4
- **Port** : 3306

## 🛡 Sécurité

- ✅ Validation des données d'entrée
- ✅ Protection contre l'injection SQL (PDO)
- ✅ Échappement des données de sortie
- ✅ Headers de sécurité configurés
- ✅ Gestion des erreurs sécurisée

## 📈 Monitoring

### Santé de l'API
```bash
curl http://localhost/api/health
```

### Statistiques
```bash
curl http://localhost/api/cargaisons/statistics
```

## 🔄 États des colis

1. **En attente** - Colis enregistré, en attente de traitement
2. **En cours** - Colis en transit
3. **Arrivé** - Colis arrivé à destination
4. **Récupéré** - Colis récupéré par le destinataire
5. **Perdu** - Colis déclaré perdu
6. **Archivé** - Colis archivé (automatique après 30 jours)

## 🚀 Déploiement

### Production
1. Modifier les variables d'environnement
2. Utiliser HTTPS
3. Configurer les sauvegardes DB
4. Mettre en place la supervision

### Scaling
- Utiliser un load balancer
- Répliquer la base de données
- Implémenter un cache Redis
- Ajouter la journalisation centralisée

## 🐛 Dépannage

### Problèmes courants

**MySQL non accessible**
```bash
docker-compose logs mysql
docker-compose restart mysql
```

**API non disponible**
```bash
docker-compose logs back
curl http://localhost/api/health
```

**Erreurs PHP**
```bash
docker-compose exec back tail -f /var/log/apache2/error.log
```

## 👨‍💻 Développement

### Ajouter un endpoint
1. Créer la méthode dans le contrôleur approprié
2. Ajouter la route dans `public/index.php`
3. Tester avec curl
4. Documenter l'endpoint

### Modifier la base de données
1. Créer le script de migration
2. L'exécuter via Docker
3. Mettre à jour les modèles
4. Tester les changements

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---

**Développé pour GrP du Monde** 🌍📦

Pour toute question ou support, contactez l'équipe de développement.