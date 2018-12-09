<?php

namespace LSI\MarketBundle\Repository;


use Doctrine\ORM\EntityRepository;

class ReserverRepository extends  EntityRepository {

    /**
     * @author  Moro KONÉ
     * @param $idAuteur
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function prixTotalMesReservations($idAuteur){

        $qb = $this->getQueryMesReservation($idAuteur);
        $qb ->innerJoin('r.user','u')
            ->select('sum(r.montantReservation) as total')
            ->groupBy('u.id');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @author  Moro KONÉ
     * @param $userId
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function prixTotalAnnoncesReservees($userId){
        $qb = $this->getQueryMesAnnoncesReservees($userId);
        $qb ->select('sum(r.montantReservation) as total')
            ->groupBy('m.id');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function mesReservations($idAuteur){

        return $this->getQueryMesReservation($idAuteur) ->getQuery()->getResult();
    }

    // Les réservations sur mes annonces
    public function annonceReserver($userId){
        $qb = $this->getQueryMesAnnoncesReservees($userId)
                    ->addSelect('a')
                    ->addSelect('m')
                    ->addSelect('u');

        return $qb->getQuery()->getResult();
    }

    public function findreserveSurMesannonces($id) {
        $qb = $this->createQueryBuilder('r');
        $qb->innerJoin('r.annonce', 'a')
            ->addSelect('a')
            ->innerJoin('a.images', 'img')
            ->addSelect('img')
            ->innerJoin('a.categorie', 'categ')
            ->addSelect('categ')
            ->innerJoin('r.user', 'u')
            ->addSelect('u')
            ;
        $qb->where('u.id= :id')
            ->setParameter('id', $id);

        $rep = $qb->getQuery()->getResult();

        return $rep;
    }

    // Renvoyer id de la mairie qui a cree l'annonce

    public function findIdMairie(){
        $req = $this->createQueryBuilder('r');
            $req->innerJoin('r.annonce', 'a')
                ->addSelect('a')
                ->innerJoin('a.mairie', 'm')
                ->addSelect('m');
            $req->select('m.id');

            return $req->getQuery()->getResult();
    }

    // recuperer les reservations qui on été faites sur l'annonce de l'auteur

    public function voirReserver($id){
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.annonce', 'a')
            ->addSelect('a')
            ->leftJoin('a.mairie', 'm')
            ->addSelect('m')
            ->innerJoin('r.user', 'u')
            ->addSelect('u')
        ;

        $qb
            ->where('r.id = :id')
            ->setParameter('id', $id)
        ;

        return $qb->getQuery()->getResult();
    }

    // Requête qui envoie l'auteur de l'annonce

    public function findAuteurannonces($id) {
        $qb = $this->createQueryBuilder('r');
        $qb->innerJoin('r.annonce', 'a')
            ->addSelect('a')
            ->innerJoin('a.images', 'img')
            ->addSelect('img')
            ->innerJoin('a.categorie', 'categ')
            ->addSelect('categ')
            ->innerJoin('r.user', 'u')
            ->addSelect('u')
            ->innerJoin('u.mairie', 'm');

        $qb->where('r.id= :id')
            ->setParameter('id', $id)

            ;

        $rep = $qb->getQuery()->getResult();

        return $rep;
    }

    public function finddest($id) {
        $qb = $this->createQueryBuilder('r');
        $qb
            ->innerJoin('r.user', 'u')
            ->addSelect('u')
            ;

        $qb->where('r.id= :id')
            ->setParameter('id', $id)

        ;

        $rep = $qb->getQuery()->getResult();

        return $rep;
    }

    public function idmairieannoncer($id){
        $req = $this->createQueryBuilder('r');
        $req
            ->innerJoin('r.annonce', 'a')
            ->addSelect('a')
            ->innerJoin('r.user','u')
            ->addSelect('u')
            ;

        $req->where('r.id= :id')
            ->setParameter('id', $id)
            ;

        return $req->getQuery()->getResult();
    }

    public function MesReservation($ida){
        $qb = $this->createQueryBuilder('r');
            $qb->innerJoin('r.user', 'u')
                ->addSelect('u')
                ->innerJoin('r.annonce', 'a')
                ->addSelect('a')
                ->innerJoin('a.images', 'img')
                ->addSelect('img')
                ->innerJoin('a.categorie', 'categ')
                ->addSelect('categ')
               
            ;

        $qb
           /* ->where('r.id= :id')
            ->setParameter('id', $id) */
           /* ->andWhere('u.id= :id')
            ->setParameter('id', $userId) */
            ->where('a.id= :id')
            ->setParameter('id', $ida)
        ;

        return $qb->getQuery()->setMaxResults(1)->getResult();
    }

    public function AnnReserv($id){
        $req = $this->createQueryBuilder('r');

        $req->innerJoin('r.annonce', 'a')
            ->addSelect('a');
        $req->where('a.id= :id')
            ->setParameter('id', $id);

        return $req->getQuery()->getResult();
    }

 // Requête pour afficher les periodes de réservations sur le calendrier

    public function periodereserve($id){
        $req = $this->createQueryBuilder('r');

        $req->innerJoin('r.annonce', 'a')
            ->addSelect('a');

        $req->where('a.id= :id')
            ->setParameter('id', $id)
        ;

        return $req->getQuery()->getResult();
    }

    // Reservation : PRENEUR
    // Récupère les reservations non encore validées
    public function findReservationAttente($userId){
        $qb = $this->createQueryBuilder('r')
            ->join('r.annonce', 'a')
            ->addSelect('a');

        $qb->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('r.reserveEtat = :etat')
            ->setParameter('etat', 'A')
            ->andWhere('r.annonce = a.id');

        return $qb->getQuery()->getResult();
    }

    // Récupère les reservations validées
    public function findReservationValidee($userId){
        $qb = $this->createQueryBuilder('r')
            ->join('r.annonce', 'a')
            ->addSelect('a');

        $qb->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('r.reserveEtat = :etat')
            ->setParameter('etat', 'V')
            ->andWhere('r.annonce = a.id');

        return $qb->getQuery()->getResult();
    }

    // Récupère les reservations refusées
    public function findReservationRefusee($userId) {
        $qb = $this->createQueryBuilder('r')
            ->join('r.annonce', 'a')
            ->addSelect('a');

        $qb->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('r.reserveEtat = :etat')
            ->setParameter('etat', 'R')
            ->andWhere('r.annonce = a.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @author  Moro KONÉ
     * @param $id
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getQueryMesAnnoncesReservees($id){

        return $this->createQueryBuilder('r')
                    ->leftJoin('r.annonce', 'a')
                    ->leftJoin('a.mairie', 'm')
                    ->innerJoin('r.user', 'u')
                    ->where('m.id = :id')
                    ->andWhere('m.id = r.annonce')
                    ->setParameter('id', $id);
    }

    /**
     * @author  Moro KONÉ
     * @param $id
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getQueryMesReservation($id){

        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $id);
    }

}