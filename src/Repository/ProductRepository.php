<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findPaginated(int $start, int $length, string $searchValue, int $orderColumn, string $orderDir): array
{
    $queryBuilder = $this->createQueryBuilder('p')
        ->setFirstResult($start)
        ->setMaxResults($length);

    if ($searchValue) {
        $queryBuilder->andWhere('p.name LIKE :search OR p.description LIKE :search')
            ->setParameter('search', '%' . $searchValue . '%');
    }

    if ($orderColumn == 1) {
        $queryBuilder->orderBy('p.name', $orderDir);
    }
    // Add more sorting options here

    return $queryBuilder->getQuery()->getResult();
}

public function countAll(): int
{
    return $this->createQueryBuilder('p')
        ->select('count(p.id)')
        ->getQuery()
        ->getSingleScalarResult();
}

public function countFiltered(string $searchValue): int
{
    $queryBuilder = $this->createQueryBuilder('p')
        ->select('count(p.id)');

    if ($searchValue) {
        $queryBuilder->andWhere('p.name LIKE :search OR p.description LIKE :search')
            ->setParameter('search', '%' . $searchValue . '%');
    }

    return $queryBuilder->getQuery()->getSingleScalarResult();
}

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
