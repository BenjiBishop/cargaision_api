<?php
namespace src\Models;
use Exception;
use src\Database\Database;
use src\Models\Cargaison\Cargaison;

class Colis {
    private $id;
    private $code;
    private $clientId;
    private $cargaisonId;
    private $nombreColis;
    private $poidsTotal;
    private $prix;
    private $typeProduit;
    private $typeCargaison;
    private $etat;
    private $destinataireNom;
    private $destinataireTelephone;
    private $destinataireAdresse;
    private $codeDestinataire;
    public $dateArriveePrevue;
    private $dateRecuperation;

    public function __construct($clientId, $nombreColis, $poidsTotal, $typeProduit, $typeCargaison) {
        $this->clientId = $clientId;
        $this->nombreColis = $nombreColis;
        $this->poidsTotal = $poidsTotal;
        $this->typeProduit = $typeProduit;
        $this->typeCargaison = $typeCargaison;
        $this->etat = 'en_attente';
        $this->code = $this->genererCode();
        $this->codeDestinataire = $this->genererCodeDestinataire();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getCode() { return $this->code; }
    public function getClientId() { return $this->clientId; }
    public function getCargaisonId() { return $this->cargaisonId; }
    public function getNombreColis() { return $this->nombreColis; }
    public function getPoidsTotal() { return $this->poidsTotal; }
    public function getPrix() { return $this->prix; }
    public function getTypeProduit() { return $this->typeProduit; }
    public function getTypeCargaison() { return $this->typeCargaison; }
    public function getEtat() { return $this->etat; }
    public function getCodeDestinataire() { return $this->codeDestinataire; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setCargaisonId($cargaisonId) { $this->cargaisonId = $cargaisonId; }
    public function setPrix($prix) {
        $db = Database::getInstance();
        $prixMinimum = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'prix_minimum'")['valeur'];
        $this->prix = max($prix, $prixMinimum);
    }
    public function setEtat($etat) { $this->etat = $etat; }
    public function setDestinataire($nom, $telephone, $adresse) {
        $this->destinataireNom = $nom;
        $this->destinataireTelephone = $telephone;
        $this->destinataireAdresse = $adresse;
    }

    private function genererCode() {
        return 'COL' . date('Ymd') . substr(uniqid(), -6);
    }

    private function genererCodeDestinataire() {
        return 'DEST' . substr(uniqid(), -8);
    }

    public function calculerPrix($distance) {
        $db = Database::getInstance();
        $prix = 0;

        switch ($this->typeProduit) {
            case 'alimentaire':
                switch ($this->typeCargaison) {
                    case 'routiere':
                        $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_routiere'")['valeur'];
                        $prix = $this->poidsTotal * $distance * $tarif;
                        break;
                    case 'maritime':
                        $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_maritime'")['valeur'];
                        $frais = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_chargement_maritime'")['valeur'];
                        $prix = ($this->poidsTotal * $distance * $tarif) + $frais;
                        break;
                    case 'aerienne':
                        $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_aerienne'")['valeur'];
                        $prix = $this->poidsTotal * $distance * $tarif;
                        break;
                }
                break;

            case 'materiel':
                switch ($this->typeCargaison) {
                    case 'routiere':
                    case 'maritime':
                        $cle = 'tarif_materiel_' . $this->typeCargaison;
                        $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = ?", [$cle])['valeur'];
                        $prix = $this->poidsTotal * $distance * $tarif;
                        if ($this->typeCargaison === 'maritime') {
                            $frais = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_chargement_maritime'")['valeur'];
                            $prix += $frais;
                        }
                        break;
                    case 'aerienne':
                        $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_aerienne'")['valeur'];
                        $prix = $this->poidsTotal * $tarif;
                        break;
                }
                break;
        }

        $this->setPrix($prix);
        return $this->prix;
    }

    public function marquerCommeRecupere() {
        $this->etat = 'recupere';
        $this->dateRecuperation = date('Y-m-d H:i:s');
        $this->save();
    }

    public function marquerCommePerdu() {
        $this->etat = 'perdu';
        $this->save();
    }

    public function archiver() {
        $this->etat = 'archive';
        $this->save();
    }

    public function annuler() {
        // Vérifier que la cargaison n'est pas fermée
        if ($this->cargaisonId) {
            $cargaison = Cargaison::find($this->cargaisonId);
            if ($cargaison && $cargaison->getEtatGlobal() === 'ferme') {
                throw new Exception("Impossible d'annuler un colis dont la cargaison est fermée");
            }
        }

        $this->etat = 'annule';
        $this->save();
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'client_id' => $this->clientId,
            'cargaison_id' => $this->cargaisonId,
            'nombre_colis' => $this->nombreColis,
            'poids_total' => $this->poidsTotal,
            'prix' => $this->prix,
            'type_produit' => $this->typeProduit,
            'type_cargaison' => $this->typeCargaison,
            'etat' => $this->etat,
            'destinataire_nom' => $this->destinataireNom,
            'destinataire_telephone' => $this->destinataireTelephone,
            'destinataire_adresse' => $this->destinataireAdresse,
            'code_destinataire' => $this->codeDestinataire,
            'date_arrivee_prevue' => $this->dateArriveePrevue,
            'date_recuperation' => $this->dateRecuperation
        ];
    }

    public function save() {
        $db = Database::getInstance();

        if ($this->id) {
            // Update
            $sql = "UPDATE colis SET client_id = ?, cargaison_id = ?, nombre_colis = ?, poids_total = ?, 
                   prix = ?, type_produit = ?, type_cargaison = ?, etat = ?, destinataire_nom = ?, 
                   destinataire_telephone = ?, destinataire_adresse = ?, code_destinataire = ?, 
                   date_arrivee_prevue = ?, date_recuperation = ? WHERE id = ?";
            $params = [
                $this->clientId, $this->cargaisonId, $this->nombreColis, $this->poidsTotal, $this->prix,
                $this->typeProduit, $this->typeCargaison, $this->etat, $this->destinataireNom,
                $this->destinataireTelephone, $this->destinataireAdresse, $this->codeDestinataire,
                $this->dateArriveePrevue, $this->dateRecuperation, $this->id
            ];
        } else {
            // Insert
            $sql = "INSERT INTO colis (code, client_id, cargaison_id, nombre_colis, poids_total, prix, 
                   type_produit, type_cargaison, etat, destinataire_nom, destinataire_telephone, 
                   destinataire_adresse, code_destinataire, date_arrivee_prevue, date_recuperation) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $this->code, $this->clientId, $this->cargaisonId, $this->nombreColis, $this->poidsTotal,
                $this->prix, $this->typeProduit, $this->typeCargaison, $this->etat, $this->destinataireNom,
                $this->destinataireTelephone, $this->destinataireAdresse, $this->codeDestinataire,
                $this->dateArriveePrevue, $this->dateRecuperation
            ];
        }

        $db->query($sql, $params);

        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }

        return $this;
    }

    public static function findByCode($code) {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM colis WHERE code = ?", [$code]);

        if (!$data) {
            return null;
        }

        return self::createFromData($data);
    }

    public static function find($id) {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM colis WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        return self::createFromData($data);
    }

    private static function createFromData($data) {
        $colis = new Colis(
            $data['client_id'],
            $data['nombre_colis'],
            $data['poids_total'],
            $data['type_produit'],
            $data['type_cargaison']
        );

        $colis->setId($data['id']);
        $colis->code = $data['code'];
        $colis->setCargaisonId($data['cargaison_id']);
        $colis->prix = $data['prix'];
        $colis->setEtat($data['etat']);
        $colis->destinataireNom = $data['destinataire_nom'];
        $colis->destinataireTelephone = $data['destinataire_telephone'];
        $colis->destinataireAdresse = $data['destinataire_adresse'];
        $colis->codeDestinataire = $data['code_destinataire'];
        $colis->dateArriveePrevue = $data['date_arrivee_prevue'];
        $colis->dateRecuperation = $data['date_recuperation'];

        return $colis;
    }
}