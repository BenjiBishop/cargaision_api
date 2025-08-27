<?php

namespace src\Models\Produit;

use src\Database\Database;

abstract class Produit
{
    protected $id;
    protected $libelle;
    protected $poids;
    protected $type;

    public function __construct($libelle, $poids)
    {
        $this->libelle = $libelle;
        $this->poids = $poids;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getLibelle()
    {
        return $this->libelle;
    }

    public function getPoids()
    {
        return $this->poids;
    }

    public function getType()
    {
        return $this->type;
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setLibelle($libelle)
    {
        $this->libelle = $libelle;
    }

    public function setPoids($poids)
    {
        $this->poids = $poids;
    }

    // Méthode abstraite à implémenter dans les classes filles
    abstract public function info();

    // Méthode pour calculer le prix selon le type de cargaison
    abstract public function calculerPrix($typeCargaison, $distance);

    // Méthode pour vérifier si le produit peut être transporté par un type de cargaison
    abstract public function peutEtreTransporteePar($typeCargaison);

    public function toArray()
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'poids' => $this->poids,
            'type' => $this->type
        ];
    }

    public function save()
    {
        $db = Database::getInstance();

        if ($this->id) {
            // Update
            $sql = "UPDATE produits SET libelle = ?, poids = ?, type = ? WHERE id = ?";
            $params = [$this->libelle, $this->poids, $this->type, $this->id];
        } else {
            // Insert
            $sql = "INSERT INTO produits (libelle, poids, type) VALUES (?, ?, ?)";
            $params = [$this->libelle, $this->poids, $this->type];
        }

        $db->query($sql, $params);

        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }

        return $this;
    }

    public static function find($id)
    {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM produits WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        // Factory pattern pour créer le bon type de produit
        switch ($data['type']) {
            case 'alimentaire':
                $produit = new ProduitAlimentaire($data['libelle'], $data['poids']);
                break;
            case 'chimique':
                $produit = new ProduitChimique($data['libelle'], $data['poids'], $data['degre_toxicite']);
                break;
            case 'materiel':
                if ($data['sous_type'] === 'fragile') {
                    $produit = new Fragile($data['libelle'], $data['poids']);
                } else {
                    $produit = new Incassable($data['libelle'], $data['poids']);
                }
                break;
            default:
                return null;
        }

        $produit->setId($data['id']);
        return $produit;
    }
}