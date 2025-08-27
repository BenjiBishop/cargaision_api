<?php

namespace src\Models\Cargaison;

class CargaisonAerienne extends Cargaison
{
    public function __construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm) {
        parent::__construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm);
        $this->type = 'aerienne';
    }

    public function calculerFrais($produit) {
        return $produit->calculerPrix($this->type, $this->distanceKm);
    }

}