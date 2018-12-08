<?php

namespace LSI\MarketBundle\Repository;

/**
 * VilleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VilleRepository extends \Doctrine\ORM\EntityRepository {
    // Récupérer le nom de l'epci
    public function findVilleEpci($ville_id){
        $qb = $this->createQueryBuilder('v')
            ->join('v.epci', 'e')
            ->addSelect('e');

        $qb
            ->where('v.epci = e.id')
            ->andWhere('v.id = :ville_id')
            ->setParameter('ville_id', $ville_id)
        ;

        return $qb->getQuery()->getScalarResult();
    }

    // Retourne toutes les villes par le nom
    public function findAllCities(){
        return $this->createQueryBuilder('v')
            //->select('nom')
            ->orderBy('v.nom', 'ASC');

        //return $qb->getQuery()->getResult();
    }

    // Récupère la liste des villes d'un EPCI
    public function findVillesEpci($idEpci){
        $qb = $this->createQueryBuilder('v')
            ;
        $qb->where('v.epci = :idEpci')
            ->setParameter('idEpci', $idEpci)
            ;
        return $qb->getQuery()->getResult();
    }
}
