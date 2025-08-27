<?php
namespace src\Models\Produit;

use InvalidArgumentException;
use src\Database\Database;

class ProduitChimique extends Produit {
    private $degreToxicite;

    public function __construct($libelle, $poids, $degreToxicite) {
        parent::__construct($libelle, $poids);
        $this->type = 'chimique';
        $this->setDegreToxicite($degreToxicite);
    }

    // Getters
    public function getDegreToxicite() {
        return $this->degreToxicite;
    }

    // Setters
    public function setDegreToxicite($degreToxicite) {
        if ($degreToxicite < 1 || $degreToxicite > 10) {
            throw new InvalidArgumentException("Le degré de toxicité doit être entre 1 et 10");
        }
        $this->degreToxicite = $degreToxicite;
    }

    public function info() {
        return "Produit Chimique: {$this->libelle}, Poids: {$this->poids} kg, Toxicité: {$this->degreToxicite}/10";
    }

    public function calculerPrix($typeCargaison, $distance) {
        $db = Database::getInstance();

        switch ($typeCargaison) {
            case 'maritime':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_chimique_maritime'")['valeur'];
                $fraisEntretien = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_entretien_chimique'")['valeur'];
                return ($this->poids * $tarif * $this->degreToxicite) + $fraisEntretien;
            case 'routiere':
            case 'aerienne':
                // Les produits chimiques ne peuvent pas être transportés par route ou air
                return 0;
            default:
                return 0;
        }
    }

    public function peutEtreTransporteePar($typeCargaison) {
        // Les produits chimiques ne peuvent être transportés que par voie maritime
        return $typeCargaison === 'maritime';
    }

    public function toArray() {
        $array = parent::toArray();
        $array['degre_toxicite'] = $this->degreToxicite;
        return $array;
    }

    public function save() {
        $db = Database::getInstance();

        if ($this->id) {
            // Update
            $sql = "UPDATE produits SET libelle = ?, poids = ?, type = ?, degre_toxicite = ? WHERE id = ?";
            $params = [$this->libelle, $this->poids, $this->type, $this->degreToxicite, $this->id];
        } else {
            // Insert
            $sql = "INSERT INTO produits (libelle, poids, type, degre_toxicite) VALUES (?, ?, ?, ?)";
            $params = [$this->libelle, $this->poids, $this->type, $this->degreToxicite];
        }

        $db->query($sql, $params);

        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }

        return $this;
    }
}