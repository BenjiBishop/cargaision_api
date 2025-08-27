-- table client
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    adresse TEXT NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- table cargaison
CREATE TABLE IF NOT EXISTS cargaisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(50) UNIQUE NOT NULL,
    poids_max DECIMAL(10,2) NOT NULL,
    prix_total DECIMAL(15,2) DEFAULT 0,
    lieu_depart VARCHAR(255) NOT NULL,
    lieu_arrivee VARCHAR(255) NOT NULL,
    coordonnees_depart VARCHAR(100),
    coordonnees_arrivee VARCHAR(100),
    distance_km DECIMAL(10,2),
    type ENUM('maritime', 'aerienne', 'routiere') NOT NULL,
    etat_avancement ENUM('en_attente', 'en_cours', 'arrive', 'retard') DEFAULT 'en_attente',
    etat_global ENUM('ouvert', 'ferme') DEFAULT 'ouvert',
    date_depart DATETIME,
    date_arrivee DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- table produit
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(255) NOT NULL,
    poids DECIMAL(10,2) NOT NULL,
    type ENUM('alimentaire', 'chimique', 'materiel') NOT NULL,
    sous_type ENUM('fragile', 'incassable') NULL, -- Pour les produits matériels
    degre_toxicite INT NULL CHECK (degre_toxicite BETWEEN 1 AND 10), -- Pour les produits chimiques
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- table colis
CREATE TABLE IF NOT EXISTS colis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    cargaison_id INT,
    nombre_colis INT NOT NULL DEFAULT 1,
    poids_total DECIMAL(10,2) NOT NULL,
    prix DECIMAL(15,2) NOT NULL,
    type_produit ENUM('alimentaire', 'chimique', 'materiel') NOT NULL,
    type_cargaison ENUM('maritime', 'aerienne', 'routiere') NOT NULL,
    etat ENUM('en_attente', 'en_cours', 'arrive', 'recupere', 'perdu', 'archive', 'annule') DEFAULT 'en_attente',
    destinataire_nom VARCHAR(100),
    destinataire_telephone VARCHAR(20),
    destinataire_adresse TEXT,
    code_destinataire VARCHAR(50),
    date_arrivee_prevue DATETIME,
    date_recuperation DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (cargaison_id) REFERENCES cargaisons(id)
    );

-- table de liaison colis-produit
CREATE TABLE IF NOT EXISTS colis_produits (
   id INT AUTO_INCREMENT PRIMARY KEY,
   colis_id INT NOT NULL,
   produit_id INT NOT NULL,
   quantite INT NOT NULL DEFAULT 1,
   FOREIGN KEY (colis_id) REFERENCES colis(id) ON DELETE CASCADE,
   FOREIGN KEY (produit_id) REFERENCES produits(id)
    );

-- tables des parametre systeme
CREATE TABLE IF NOT EXISTS parametres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(100) UNIQUE NOT NULL,
    valeur TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- insertion des parametres par defaut
INSERT INTO parametres (cle, valeur, description) VALUES
('prix_minimum', '10000', 'Prix minimum par colis'),
('duree_archivage_auto', '30', 'Durée en jours avant archivage automatique'),
('tarif_alimentaire_routiere', '100', 'Tarif F/kg/km pour produit alimentaire en routière'),
('tarif_alimentaire_maritime', '90', 'Tarif F/kg/km pour produit alimentaire en maritime'),
('tarif_alimentaire_aerienne', '300', 'Tarif F/kg/km pour produit alimentaire en aérienne'),
('tarif_chimique_maritime', '500', 'Tarif F/kg pour produit chimique en maritime (par degré)'),
('tarif_materiel_routiere', '80', 'Tarif F/kg/km pour produit matériel en routière'),
('tarif_materiel_maritime', '400', 'Tarif F/kg/km pour produit matériel en maritime'),
('tarif_materiel_aerienne', '1000', 'Tarif F/kg pour produit matériel en aérienne'),
('frais_chargement_maritime', '5000', 'Frais de chargement maritime'),
('frais_entretien_chimique', '10000', 'Frais entretien pour produits chimiques');

-- Index pour optimiser les recherches
CREATE INDEX idx_colis_code ON colis(code);
CREATE INDEX idx_colis_etat ON colis(etat);
CREATE INDEX idx_cargaison_numero ON cargaisons(numero);
CREATE INDEX idx_cargaison_type ON cargaisons(type);
CREATE INDEX idx_cargaison_etat ON cargaisons(etat_avancement);