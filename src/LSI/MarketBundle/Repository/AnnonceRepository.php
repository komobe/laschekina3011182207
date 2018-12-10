<?php

namespace LSI\MarketBundle\Repository;


use Doctrine\ORM\EntityRepository;
use LSI\MarketBundle\Entity\Categorie;

class AnnonceRepository extends EntityRepository {

    // Recuperer l'auteur d'une annonce.
    public function findAuteurAnnonce($id){ // $id est l'id de la mairie auteure de l'annonce
        $qb = $this->createQueryBuilder('m')
                    ->join('m.id', 'LSI\MarketBundle\Entity\User u')
                    ->addSelect('u')
                    ;

        $qb
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->andWhere('m.id = u.mairie')
            ;
        return $qb->getQuery()->getScalarResult();
    }

    // Recuperer les annonces en fonction de leurs auteurs.
    public function findMesAnnonces($id){ //$id est l'id de l'auteur de l'annonce
        $qb = $this->createQueryBuilder('a');

        $qb
            ->where('a.mairie = :id')
                ->setParameter('id', $id);

        //Retourne un tableau d'annonce.
        return $qb->getQuery()->getResult();
    }

    // recuperer le titre de l'annonce en fonction de son id
    public function titreAnnonce($id){
        $qb = $this->createQueryBuilder('a')
                    ->select('a.titre') // Pour selectionner le titre
        ;

        $qb->where('a.id = :id')
            ->setParameter('id', $id)
            ;
        // Retourner un seul resultat, le titre de l'annonce concernee.
        return $qb->getQuery()->getSingleResult();
    }

    /*
     * Requête de recherche d'une annonce à travers le titre ou la description
     * */
    public function findAnnParTitreOuDescp($motcle)  {
        $queryb = $this->createQueryBuilder('a');
        $queryb->innerJoin('a.images', 'img')
            ->where('a.titre like :titre or a.description like :description')
            ->setParameter('titre', '%'.$motcle.'%')
            ->setParameter('description', '%'.$motcle.'%');

        return $queryb->getQuery()->getResult();
    }


// Recherche par titre ou description
    public function findTitreDescp($mot){
        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.images', 'img')
            ->innerJoin('a.categorie', 'categ')
            ->addSelect('categ')
            ->addSelect('img')
            ->innerJoin('a.adresse', 'adress')
            ->addSelect('adress')
            ->where('a.titre like :titre or a.description like :description')
            ->setParameter('titre', '%'.$mot.'%')
            ->setParameter('description', '%'.$mot.'%');

        $result = $qb->getQuery()->setMaxResults(10)->getResult();
        return $result;
    }

    // Recherche par categorie recherche avancée
    public function findByCategorie($categorie){
        $query = $this->createQueryBuilder('a');
        $query->innerJoin('a.images', 'img')
            ->addSelect('img')
            ->innerJoin('a.categorie', 'categ')
            ->addSelect('categ')
            ->innerJoin('a.adresse', 'adr')
            ->addSelect('adr');

            $query->where('categ.id= :id')
            ->setParameter('id', $categorie);
        $result = $query->getQuery()->getResult();

        return $result;
    }

    // Recherche par categorie ou prix
    public function findCategorieAndPrix($categorie, $prix){
        $query = $this->createQueryBuilder('an');
        $query->innerJoin('a.images', 'img')
            ->addSelect('img')
            ->innerJoin('a.categorie', 'categ')
            ->addSelect('categ')
            ->where('categ.id= :id')
            ->setParameter('id', $categorie)
            ->orwhere('a.prixDefaut < :prixDefaut')
            ->setParameter('prixDefaut', $prix);
        $query->getQuery()->getResult();

        return $query;
    }

    // Recherche par Budget
    public function findBudget($budget){
        $query = $this->createQueryBuilder('an');
        $query->innerJoin('an.images', 'img')
            ->addSelect('img')
            ->innerJoin('an.categorie', 'categ')
            ->addSelect('categ')
            ->where('an.prixDefaut<= :prixDefaut')
            ->setParameter('prixDefaut', $budget);
        $result = $query->getQuery()->getResult();

        return $result;
    }

    // Recherche par periode
    public function findPeriode($date){
        $query = $this->createQueryBuilder('an');
        $query->innerJoin('an.images', 'img')
            ->addSelect('img')
            ->innerJoin('an.categorie', 'categ')
            ->addSelect('categ')
            ->where('an.dateCreation= :dateCreation')
            ->setParameter('dateCreation', $date);
        $result = $query->getQuery()->getResult();

        return $result;
    }

    // Recherche par la ville
    public function findVille($ville){
        $query = $this->createQueryBuilder('an');
        $query->innerJoin('an.images', 'img')
            ->addSelect('img')
            ->innerJoin('an.categorie', 'categ')
            ->addSelect('categ')
            ->innerJoin('an.mairie', 'm')
            ->addSelect('m')
            ->innerJoin('m.user', 'u')
            ->addSelect('u')
            ->where('u.ville= :ville')
            ->setParameter('dateCreation', $ville);
        $result = $query->getQuery()->getResult();

        return $result;
    }

    // Recherche par la ville et categorie et prix et statut
    public function findVilleAndPeriodAndCategAndDate($ville,$categ, $prix,$libelle){
        $query = $this->createQueryBuilder('an');
        $query->innerJoin('an.images', 'img')
            ->addSelect('img')
            ->innerJoin('an.statut', 'stat')
            ->addSelect('stat')
            ->where('an.prixDefaut >= :prixDefaut')
            ->setParameter('prixDefaut', $prix)
            ->andWhere('stat.libelle >= :libelle')
            ->setParameter('libelle', $libelle)
            ->innerJoin('an.categorie', 'categ')
            ->addSelect('categ')
            ->where('categ.id= :id')
            ->setParameter('id', $categ)
            ->innerJoin('an.mairie', 'm')
            ->addSelect('m')
            ->innerJoin('m.user', 'u')
            ->addSelect('u')
            ->where('u.ville= :ville')
            ->setParameter('dateCreation', $ville);
        $result = $query->getQuery()->getResult();

        return $result;
    }

    public function requeteAffiner(Categorie $cat, $ville, $budget, $periode, $prix){
        $qb = $this->createQueryBuilder('a')
                    ->leftJoin('a.image', 'i')
                    ->addSelect('i')
        ;

        // Ajoute la catégorie si elle n'est pas vide...
        if ($cat !== null) {
            $qb->leftJoin('a.categorie', 'c')
                ->addSelect('c')
                ->where('c.id = :cat')
                ->setParameter('cat', $cat);
        }

        // ...
        if ($ville !== null) {
            $qb->leftJoin('a.mairie', 'm')
                ->addSelect('m')
                ->join('m.id', 'u')
                ->addSelect('u')
                ->andWhere('m.id = u.mairie')
                ->andWhere('u.ville = :ville')
                ->setParameter('ville', $ville);
        }

        // ...
        if($budget !== null){
            $qb->andWhere('a.prixDefaut <= :budget')
                ->setParameter('budget', $budget);
        }

        // Période n'est pas encore définie dans la création de l'annonce...
        if($periode !== null){

        }

        if($prix !== null){
            $qb->andWhere('a.prixDefaut = :prix')
                ->setParameter('prix', $prix);
        }

        return $qb->getQuery()->getResult();
    }

    // 
    public function findPeriodDispo($debut, $fin) {
        $qb = $this->createQueryBuilder('a')
                   ->innerJoin('a.calendrier', 'c')
                    ->addSelect('c')
                    ->where('c.debut = :debut')
                    ->setParameter('debut', $debut)
                    ->andWhere('c.fin = :fin')
                    ->setParameter('fin', $fin);
        return $qb->getQuery()->setMaxResults(10)->getResult();

    }

    // Récupère le calendrier d'une annonce
    public function findAnnoncesAvecCalendrier($id){
        $qb = $this->createQueryBuilder('a')
            ->join('a.calendrier', 'c')
            ->addSelect('c')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ;
        return $qb->getQuery()->getResult();

    }

    // Requête qui renvoie id de la mairie qui a cree l'annonce
    public function idmairieannonce($id){
        $req = $this->createQueryBuilder('a');
                    $req->innerJoin('a.mairie', 'm')
                        ->addSelect('m');

        $req->where('a.id= :id')
            ->setParameter('id', $id);

        return $req->getQuery()->getResult();
    }

    // Requete pour afficher le resultat de la barre de recherche principale

    public function findTitreDescpP($mot){
        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.images', 'img')
            ->addSelect('img')
            ->innerJoin('a.categorie', 'categ')
            ->addSelect('categ')
            ->innerJoin('a.mairie', 'm')
            ->addSelect('m')
            ->innerJoin('a.adresse', 'adress')
            ->addSelect('adress')
            ->where('a.titre like :titre or a.description like :description')
            ->setParameter('titre', '%'.$mot.'%')
            ->setParameter('description', '%'.$mot.'%');

        $result = $qb->getQuery()->setMaxResults(10)->getResult();
        return $result;
    }

    // Afficher l'image d'une annonce lors de la modification
    public function affichimg($id){
        $req = $this->createQueryBuilder('a');
                $req->innerJoin('a.images', 'img')
                    ->addSelect('img');

                $req->where('a.id= :id')
                    ->setParameter('id', $id);

                return $req->getQuery()->getResult();
    }

    // Requête de recherche pour la page de recherche

    /**
     * @author Moro KONÉ
     * @param array $params
     * @return array
     */
    public function findRechIndex($params=array()){

        extract($params); // Extrait les variables

        $req = $this->createQueryBuilder('a');

        $req->innerJoin('a.adresse', 'adress')->addSelect('adress');


        if (!empty($titre) ) {
            $req->andWhere('a.titre= :titre')->setParameter('titre', $titre);
        }

        if (!empty($ville)) {
            $req->andWhere('adress.ville= :ville')->setParameter('ville', $ville);
        }

        if (!empty($datedebut)  || !empty($datefin)) {
            $req->innerJoin('a.calendrier', 'calendar')
                ->addSelect('calendar');

            if (!empty($datedebut)) {
                $req->andWhere('calendar.debut >= :debut')->setParameter('debut', $datedebut);
        	}

            if (!empty($datefin)) {
                $req->andWhere('calendar.fin <= :fin')->setParameter('fin', $datefin);
            }

        }

        return $req->getQuery()->setMaxResults(10)->getResult();
    }

//
//    // titre
//    public function findrechetitreindex($titre){
//
//        $req = $this->createQueryBuilder('a');
//
//        $req->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress');
//
//        $req->where('a.titre= :titre')
//            ->setParameter('titre', $titre);
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // ville
//
//    public function findrechetitrevilleindex($ville){
//
//        $req = $this->createQueryBuilder('a');
//        $req->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress');
//        $req->where('adress.ville= :ville')
//            ->setParameter('ville', $ville);
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // Titre et la ville
//
//    public function findtitretitrevilleindex($titre, $ville){
//
//        $req = $this->createQueryBuilder('a');
//        $req->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress');
//        $req->where('adress.ville= :ville')
//            ->setParameter('ville', $ville)
//            ->andWhere('a.titre= :titre')
//            ->setParameter('titre', $titre);
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // disponibilite
//
//
//    public function finddispoindex($datedebut, $datefin){
//
//        $req = $this->createQueryBuilder('a');
//        $req->innerJoin('a.calendrier', 'calendar')
//            ->addSelect('calendar')
//            ->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress')
//            ;
//
//        $req->where('calendar.debut >= :debut')
//            ->setParameter('debut', $datedebut)
//            ->andWhere('calendar.fin <= :fin')
//            ->setParameter('fin', $datefin);
//       /* $req->where(
//                    $req->expr()->between('calendar.debut, calendar.fin',  debut, fin )
//                    )
//                    ->setParameter('debut', $datedebut)
//                    ->setParameter('fin', $datefin);*/
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // titre et disponibilite
//
//    public function titreperiodeindex($titre, $datedebut, $datefin){
//
//        $req = $this->createQueryBuilder('a');
//        $req->innerJoin('a.calendrier', 'calendar')
//            ->addSelect('calendar')
//            ->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress')
//            ;
//        $req->where('a.titre= :titre')
//            ->setParameter('titre', $titre)
//            ->andWhere('calendar.debut >= :debut')
//            ->setParameter('debut', $datedebut)
//            ->andWhere('calendar.fin <= :fin')
//            ->setParameter('fin', $datefin)
//        ;
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // Ville et disponibiite
//
//    public function villeperiodeindex($ville, $datedebut, $datefin){
//
//        $req = $this->createQueryBuilder('a');
//
//        $req->innerJoin('a.calendrier', 'calendar')
//            ->addSelect('calendar')
//            ->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress');
//
//        $req->where('adress.ville= :ville')
//            ->setParameter('ville', $ville)
//            ->andWhere('calendar.debut >= :debut')
//            ->setParameter('debut', $datedebut)
//            ->andWhere('calendar.fin <= :fin')
//            ->setParameter('fin', $datefin)
//        ;
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // titre et ville et disponibilite
//
//    public function titrevilleperiodeindex($titre, $ville, $datedebut, $datefin){
//
//        $req = $this->createQueryBuilder('a');
//
//        $req->innerJoin('a.calendrier', 'calendar')
//            ->addSelect('calendar')
//            ->innerJoin('a.adresse', 'adress')
//            ->addSelect('adress');
//
//        $req->where('a.titre= :titre')
//            ->setParameter('titre', $titre)
//            ->andWhere('adress.ville= :ville')
//            ->setParameter('ville', $ville)
//            ->andWhere('calendar.debut >= :debut')
//            ->setParameter('debut', $datedebut)
//            ->andWhere('calendar.fin <= :fin')
//            ->setParameter('fin', $datefin)
//        ;
//
//        return $req->getQuery()->setMaxResults(10)->getResult();
//    }
//
//    // Requête pour la gestion de la disponibilité
//
//    public function disponibilite($id){
//
//        $req = $this->createQueryBuilder('a');
//        $req->innerJoin('a.calendrier', 'c')
//            ->addSelect('c')
//            ->innerJoin('c.statut', 's')
//            ->addSelect('s');
//
//        $req->where('a.id= :id')
//            ->setParameter('id', $id);
//
//        return $req->getQuery()->getResult();
//    }

    /*Recherche avancée*/
/*
    public function findByAnnonce($categorie, $pays, $depart, $ville, $prixmini, $prixmax, $datdebut, $datefin ){
        $req = $this->createQueryBuilder('a');

        $req
            ->innerJoin('a.adresse', 'adrss')
            ->addSelect('adrss')
            ->innerJoin('a.categorie', 'cat')
            ->addSelect('cat')
            ->innerJoin('a.calendrier', 'calendar')
            ->addSelect('calendar')
            ;

        if($categorie != null && $pays == null && $depart == null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null ) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie);

            return $req->getQuery()->setMaxResults(10)->getResult();

        }elseif ($categorie == null && $pays != null && $depart == null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('adrss.pays= :pays')
                ->setParameter('pays', $pays);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart != null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('adrss.pays= :pays')
                ->setParameter('pays', $pays);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart == null && $ville != null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('adrss.ville= :ville')
                ->setParameter('ville', $ville);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart == null && $ville == null &&
            $prixmini != null && $prixmax != null && $datdebut == null && $datefin == null) {

            $req->where($req->expr()->between('a.prixDefaut', $prixmini, $prixmax));

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart == null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut != null && $datefin != null) {

            $req->where('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie != null && $pays != null && $depart == null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie)
                ->andWhere('adrss.pays= :pays')
                ->setParameter('pays', $pays);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie != null && $pays == null && $depart != null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie)
                ->andWhere('adrss.pays= :pays')
                ->setParameter('pays', $pays);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie != null && $pays == null && $depart == null && $ville != null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie)
                ->andWhere('adrss.ville= :ville')
                ->setParameter('ville', $ville);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie != null && $pays == null && $depart == null && $ville == null &&
            $prixmini != null && $prixmax != null && $datdebut == null && $datefin == null) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie)
                ->andWhere('a.prixDefaut <= :prixDefaut')
                ->setParameter('prixDefaut', $prixmini)
                ->andWhere('a.prixDefaut <= :prixDefaut')
                ->setParameter('prixDefaut', $prixmax);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie != null && $pays == null && $depart == null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut != null && $datefin != null) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie)
                ->andWhere('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin);

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays != null && $depart != null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('adrss.pays= :pays')
                ->setParameter('pays', $pays)
                ->andWhere('adrss.departement= :departement')
                ->setParameter('departement', $depart)

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays != null && $depart == null && $ville != null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('adrss.pays= :pays')
                ->setParameter('pays', $pays)
                ->andWhere('adrss.ville= :ville')
                ->setParameter('ville', $ville)
                ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays != null && $depart == null && $ville == null &&
            $prixmini != null && $prixmax != null && $datdebut == null && $datefin == null) {

            $req->where('adrss.pays= :pays')
                ->setParameter('pays', $pays)
                ->andWhere($req->expr()->between('a.prixDefaut', $prixmini, $prixmax))
            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays != null && $depart == null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut != null && $datefin != null) {

            $req->where('adrss.pays= :pays')
                ->setParameter('pays', $pays)
                ->andWhere('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin)
            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart != null && $ville != null &&
            $prixmini == null && $prixmax == null && $datdebut == null && $datefin == null) {

            $req->where('adrss.departement= :departement')
                ->setParameter('departement', $depart)
                ->andWhere('adrss.ville= :ville')
                ->setParameter('ville', $ville)

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart != null && $ville == null &&
            $prixmini != null && $prixmax != null && $datdebut == null && $datefin == null) {

            $req->where('adrss.departement= :departement')
                ->setParameter('departement', $depart)
                ->andWhere($req->expr()->between('a.prixDefaut', $prixmini, $prixmax))

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart != null && $ville == null &&
            $prixmini == null && $prixmax == null && $datdebut != null && $datefin != null) {

            $req->where('adrss.departement= :departement')
                ->setParameter('departement', $depart)
                ->andWhere('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin)

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart == null && $ville != null &&
            $prixmini == null && $prixmax == null && $datdebut != null && $datefin != null) {

            $req->where('adrss.ville= :ville')
                ->setParameter('ville', $ville)
                ->andWhere('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin)

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart == null && $ville != null &&
            $prixmini != null && $prixmax != null && $datdebut == null && $datefin == null) {

            $req->where('adrss.ville= :ville')
                ->setParameter('ville', $ville)
                ->andWhere($req->expr()->between('a.prixDefaut', $prixmini, $prixmax))

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie == null && $pays == null && $depart == null && $ville == null &&
            $prixmini != null && $prixmax != null && $datdebut != null && $datefin != null) {

            $req->where('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin)
                ->andWhere($req->expr()->between('a.prixDefaut', $prixmini, $prixmax))

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }elseif ($categorie != null && $pays != null && $depart != null && $ville != null &&
            $prixmini != null && $prixmax != null && $datdebut != null && $datefin != null) {

            $req->where('cat.id= :id')
                ->setParameter('id', $categorie)
                ->andWhere('adrss.pays= :pays')
                ->setParameter('pays', $pays)
                ->andWhere('adrss.departement= :departement')
                ->setParameter('departement', $depart)
                ->andWhere('adrss.ville= :ville')
                ->setParameter('ville', $ville)
                ->andWhere('calendar.debut <= :debut')
                ->setParameter('debut', $datdebut)
                ->andWhere('calendar.fin <= :fin')
                ->setParameter('fin', $datefin)
                ->andWhere($req->expr()->between('a.prixDefaut', $prixmini, $prixmax))

            ;

            return $req->getQuery()->setMaxResults(10)->getResult();
        }

    }*/

    /**
     * @author Moro KONÉ
     * @param $categorie
     * @param $pays
     * @param $ville
     * @param $prixmini
     * @param $prixmax
     * @param $datdebut
     * @param $datefin
     * @return array
     */
    public function findByAnnonce($categorie, $pays, $ville, $prixmini, $prixmax, $datdebut, $datefin ){
        $req = $this->createQueryBuilder('a');
        $req
            ->innerJoin('a.adresse', 'adrss')
            ->addSelect('adrss')
            ->innerJoin('a.categorie', 'cat')
            ->addSelect('cat')
            ->innerJoin('a.calendrier', 'calendar')
            ->addSelect('calendar');

        if($categorie != null ) { $req->andWhere('cat.id= :id')->setParameter('id', $categorie);}

        if ($pays != null ) { $req->andWhere('adrss.pays= :pays') ->setParameter('pays', $pays);}

        if ($ville != null ) { $req->andWhere('adrss.ville= :ville')->setParameter('ville', $ville);}

        if ($prixmax != null ) {
            $req->andWhere($req->expr()->between('a.prixDefaut', $prixmini, $prixmax));}

        if ( $datdebut != null) { $req->andWhere('calendar.debut >= :debut')->setParameter('debut', $datdebut);}

        if ( $datefin != null) { $req->andWhere('calendar.fin <= :fin')->setParameter('fin', $datefin);}

        return $req->getQuery()->setMaxResults(10)->getResult();

    }

    // 
    /**
    * Recupere les annonces par catégorie
    * $nomCategorie est le nom de la catégorie recherchée
    * $id est id de l'annonce courante
    */
    public function findAnnonceParCategorie($nomCategorie, $id){
        $query = $this->createQueryBuilder('an');
        $query->innerJoin('an.categorie', 'c')
            ->addSelect('c')
            ->where('c.name = :cat')
            ->setParameter('cat', $nomCategorie)
            ->andWhere('an.id != :id')
            ->setParameter('id', $id)
            ->orderBy('an.dateCreation', 'DESC')
            ->setMaxResults(10)
        ;

        return $query->getQuery()->getResult();
    }

    // Recuperer les annonces de meme auteur.
    public function findAnnoncesMemeAuteur($idAuteur, $idAnnonce){
        $qb = $this->createQueryBuilder('a');

        $qb
            ->where('a.mairie = :idAuteur')
            ->setParameter('idAuteur', $idAuteur)
            ->andWhere('a.id != :idAnnonce')
            ->setParameter('idAnnonce', $idAnnonce)
            ->orderBy('a.dateCreation', 'DESC')
            ->setMaxResults(10)
        ;

        //Retourne un tableau d'annonce.
        return $qb->getQuery()->getResult();
    }

    // Recuperer les annonces de meme EPCI.
    public function findAnnoncesMemeEpci($nomEpci, $idAnnonce){
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.adresse', 'adr')
            ->addSelect('adr')
            ->innerJoin('adr.ville', 'v')
            ->addSelect('v')
            ->innerJoin('v.epci', 'e')
        ;

        $qb
            ->where('a.adresse = adr.id')
            ->andWhere('adr.ville = v.id')
            ->andWhere('v.epci = e.id')
            ->andWhere('e.nom = :nomEpci')
            ->setParameter('nomEpci', $nomEpci)
            ->andWhere('a.id != :idAnnonce')
            ->setParameter('idAnnonce', $idAnnonce)
            ->orderBy('a.dateCreation', 'DESC')
            ->setMaxResults(10)
        ;

        //Retourne un tableau d'annonce.
        return $qb->getQuery()->getResult();
    }

	// Requ?e qui g?e les annonces sur la map

public function findAnnon(){

    $req = $this->createQueryBuilder('a');

    $req->innerJoin('a.adresse', 'adr')
        ->addSelect('adr');

        return $req->getQuery()->getResult();
}

}
