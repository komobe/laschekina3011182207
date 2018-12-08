<?php


namespace LSI\MarketBundle\Repository;


use Doctrine\ORM\EntityRepository;

class MembreRepository extends EntityRepository {
	//Récupère un membre en focntion de son habilitation et de sa mairie
	public function findMembreHabilite($idMairie){
		$qb = $this->createQueryBuilder('m')
			->where('m.devis = :devis')
			->setParameter('devis', true)
			->andWhere('m.mairie = :idMairie')
			->setParameter('idMairie', $idMairie)
			;

		return $qb->getQuery()->getScalarResult();
	}
}