<?php
namespace src\Models\Cargaison;

use Exception;
use src\Database\Database;
use src\Models\Produit\Produit;

abstract class Cargaison {
    protected $id;
    protected $numero;
    protected $poidsMax;
    protected $prixTotal;
    protected $lieuDepart;
    protected $lieuArrivee;
    public $coordonneesDepart;
    public $coordonneesArrivee;
    protected $distanceKm;
    protected $type;
    protected $etatAvancement;
    protected $etatGlobal;
    protected $dateDepart;
    protected $dateArrivee;
    protected $produits = [];

    public function __construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm) {
        $this->numero = $numero;
        $this->poidsMax = $poidsMax;
        $this->lieuDepart = $lieuDepart;
        $this->lieuArrivee = $lieuArrivee;
        $this->distanceKm = $distanceKm;
        $this->prixTotal = 0;
        $this->etatAvancement = 'en_attente';
        $this->etatGlobal = 'ouvert';
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNumero() { return $this->numero; }
    public function getPoidsMax() { return $this->poidsMax; }
    public function getPrixTotal() { return $this->prixTotal; }
    public function getLieuDepart() { return $this->lieuDepart; }
    public function getLieuArrivee() { return $this->lieuArrivee; }
    public function getDistanceKm() { return $this->distanceKm; }
    public function getType() { return $this->type; }
    public function getEtatAvancement() { return $this->etatAvancement; }
    public function getEtatGlobal() { return $this->etatGlobal; }
    public function getProduits() { return $this->produits; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setEtatAvancement($etat) { $this->etatAvancement = $etat; }
    public function setEtatGlobal($etat) { $this->etatGlobal = $etat; }
    public function setDateDepart($date) { $this->dateDepart = $date; }
    public function setDateArrivee($date) { $this->dateArrivee = $date; }

    // Méthodes abstraites
    abstract public function calculerFrais($produit);

    public function ajouterProduit(Produit $produit) {
        // Vérifier si la cargaison est fermée
        if ($this->etatGlobal === 'ferme') {
            throw new Exception("Impossible d'ajouter un produit à une cargaison fermée");
        }

        // Vérifier si la cargaison est pleine (max 10 produits)
        if (count($this->produits) >= 10) {
            throw new Exception("La cargaison est pleine (maximum 10 produits)");
        }

        // Vérifier si le produit peut être transporté par ce type de cargaison
        if (!$produit->peutEtreTransporteePar($this->type)) {
            throw new Exception("Ce produit ne peut pas être transporté par ce type de cargaison");
        }

        // Vérifier le poids maximum
        $poidsActuel = $this->getPoidsTotal();
        if (($poidsActuel + $produit->getPoids()) > $this->poidsMax) {
            throw new Exception("Le poids maximum de la cargaison serait dépassé");
        }

        $this->produits[] = $produit;

        // Calculer et ajouter le prix du produit
        $prixProduit = $this->calculerFrais($produit);
        $this->prixTotal += $prixProduit;

        return $prixProduit;
    }

    public function nbProduits() {
        return count($this->produits);
    }

    public function sommeTotal() {
        return $this->prixTotal;
    }

    public function getPoidsTotal() {
        $poids = 0;
        foreach ($this->produits as $produit) {
            $poids += $produit->getPoids();
        }
        return $poids;
    }

    public function fermerCargaison() {
        $this->etatGlobal = 'ferme';
        $this->save();
    }

    public function rouvrirCargaison() {
        if ($this->etatAvancement !== 'en_attente') {
            throw new Exception("Une cargaison ne peut être rouverte que si son état d'avancement est 'EN ATTENTE'");
        }
        $this->etatGlobal = 'ouvert';
        $this->save();
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'poids_max' => $this->poidsMax,
            'prix_total' => $this->prixTotal,
            'lieu_depart' => $this->lieuDepart,
            'lieu_arrivee' => $this->lieuArrivee,
            'coordonnees_depart' => $this->coordonneesDepart,
            'coordonnees_arrivee' => $this->coordonneesArrivee,
            'distance_km' => $this->distanceKm,
            'type' => $this->type,
            'etat_avancement' => $this->etatAvancement,
            'etat_global' => $this->etatGlobal,
            'date_depart' => $this->dateDepart,
            'date_arrivee' => $this->dateArrivee,
            'nb_produits' => $this->nbProduits(),
            'poids_total' => $this->getPoidsTotal()
        ];
    }

    public function save() {
        $db = Database::getInstance();

        if ($this->id) {
            // Update
            $sql = "UPDATE cargaisons SET numero = ?, poids_max = ?, prix_total = ?, lieu_depart = ?, 
                   lieu_arrivee = ?, coordonnees_depart = ?, coordonnees_arrivee = ?, distance_km = ?, 
                   type = ?, etat_avancement = ?, etat_global = ?, date_depart = ?, date_arrivee = ? 
                   WHERE id = ?";
            $params = [
                $this->numero, $this->poidsMax, $this->prixTotal, $this->lieuDepart, $this->lieuArrivee,
                $this->coordonneesDepart, $this->coordonneesArrivee, $this->distanceKm, $this->type,
                $this->etatAvancement, $this->etatGlobal, $this->dateDepart, $this->dateArrivee, $this->id
            ];
        } else {
            // Insert
            $sql = "INSERT INTO cargaisons (numero, poids_max, prix_total, lieu_depart, lieu_arrivee, 
                   coordonnees_depart, coordonnees_arrivee, distance_km, type, etat_avancement, etat_global, 
                   date_depart, date_arrivee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $this->numero, $this->poidsMax, $this->prixTotal, $this->lieuDepart, $this->lieuArrivee,
                $this->coordonneesDepart, $this->coordonneesArrivee, $this->distanceKm, $this->type,
                $this->etatAvancement, $this->etatGlobal, $this->dateDepart, $this->dateArrivee
            ];
        }

        $db->query($sql, $params);

        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }

        return $this;
    }

    public static function find($id) {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM cargaisons WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        // Factory pattern pour créer le bon type de cargaison
        switch ($data['type']) {
            case 'maritime':
                $cargaison = new CargaisonMaritime(
                    $data['numero'], $data['poids_max'], $data['lieu_depart'],
                    $data['lieu_arrivee'], $data['distance_km']
                );
                break;
            case 'aerienne':
                $cargaison = new CargaisonAerienne(
                    $data['numero'], $data['poids_max'], $data['lieu_depart'],
                    $data['lieu_arrivee'], $data['distance_km']
                );
                break;
            case 'routiere':
                $cargaison = new CargaisonRoutiere(
                    $data['numero'], $data['poids_max'], $data['lieu_depart'],
                    $data['lieu_arrivee'], $data['distance_km']
                );
                break;
            default:
                return null;
        }

        $cargaison->setId($data['id']);
        $cargaison->prixTotal = $data['prix_total'];
        $cargaison->coordonneesDepart = $data['coordonnees_depart'];
        $cargaison->coordonneesArrivee = $data['coordonnees_arrivee'];
        $cargaison->setEtatAvancement($data['etat_avancement']);
        $cargaison->setEtatGlobal($data['etat_global']);
        $cargaison->setDateDepart($data['date_depart']);
        $cargaison->setDateArrivee($data['date_arrivee']);

        return $cargaison;
    }

    public static function findByNumero($numero) {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM cargaisons WHERE numero = ?", [$numero]);

        if (!$data) {
            return null;
        }

        return self::find($data['id']);
    }
}