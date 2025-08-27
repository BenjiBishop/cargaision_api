<?php
namespace src\Models\Produit;
use src\Database\Database;

abstract class ProduitMateriel extends Produit {
    protected $sousType;

    public function __construct($libelle, $poids) {
        parent::__construct($libelle, $poids);
        $this->type = 'materiel';
    }

    public function getSousType() {
        return $this->sousType;
    }

    public function toArray() {
        $array = parent::toArray();
        $array['sous_type'] = $this->sousType;
        return $array;
    }

    public function save() {
        $db = Database::getInstance();

        if ($this->id) {
            // Update
            $sql = "UPDATE produits SET libelle = ?, poids = ?, type = ?, sous_type = ? WHERE id = ?";
            $params = [$this->libelle, $this->poids, $this->type, $this->sousType, $this->id];
        } else {
            // Insert
            $sql = "INSERT INTO produits (libelle, poids, type, sous_type) VALUES (?, ?, ?, ?)";
            $params = [$this->libelle, $this->poids, $this->type, $this->sousType];
        }

        $db->query($sql, $params);

        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }

        return $this;
    }
}


// src/Models/Produit/Incassable.php
require_once 'ProduitMateriel.php';

class Incassable extends ProduitMateriel {

    public function __construct($libelle, $poids) {
        parent::__construct($libelle, $poids);
        $this->sousType = 'incassable';
    }

    public function info() {
        return "Produit Matériel Incassable: {$this->libelle}, Poids: {$this->poids} kg";
    }

    public function calculerPrix($typeCargaison, $distance) {
        $db = Database::getInstance();

        switch ($typeCargaison) {
            case 'routiere':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_routiere'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'maritime':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_maritime'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'aerienne':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_aerienne'")['valeur'];
                return $this->poids * $tarif;
            default:
                return 0;
        }
    }

    public function peutEtreTransporteePar($typeCargaison) {
        // Les produits incassables peuvent être transportés par tous les types de cargaison
        return in_array($typeCargaison, ['routiere', 'maritime', 'aerienne']);
    }
}