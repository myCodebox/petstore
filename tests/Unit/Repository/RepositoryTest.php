<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Collection\CollectionInterface;
use App\Model\ModelInterface;
use App\Repository\Repository;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Repository\Repository
 */
final class RepositoryTest extends TestCase
{
    use MockByCallsTrait;

    public function testResolveCollection(): void
    {
        $model = $this->getModel();
        $modelClass = get_class($model);

        $items = [$model];

        /** @var CollectionInterface|MockObject $collection */
        $collection = $this->getMockByCalls(CollectionInterface::class, [
            Call::create('setCount')->with(count($items)),
            Call::create('getSort')->with()->willReturn(['name' => 'asc']),
            Call::create('getOffset')->with()->willReturn(0),
            Call::create('getLimit')->with()->willReturn(20),
            Call::create('setItems')->with($items),
        ]);

        /** @var Func|MockObject $func */
        $func = $this->getMockByCalls(Func::class);

        /** @var Expr|MockObject $expr */
        $expr = $this->getMockByCalls(Expr::class, [
            Call::create('count')->with('m.id')->willReturn($func),
        ]);

        /** @var AbstractQuery|MockObject $countQuery */
        $countQuery = $this->getMockByCalls(AbstractQuery::class, [
            Call::create('getSingleScalarResult')->with()->willReturn(count($items)),
        ]);

        /** @var AbstractQuery|MockObject $itemsQuery */
        $itemsQuery = $this->getMockByCalls(AbstractQuery::class, [
            Call::create('getResult')->with(AbstractQuery::HYDRATE_OBJECT)->willReturn($items),
        ]);

        /** @var QueryBuilder|MockObject $queryBuilder */
        $queryBuilder = $this->getMockByCalls(QueryBuilder::class, [
            Call::create('expr')->with()->willReturn($expr),
            Call::create('select')->with($func),
            Call::create('getQuery')->with()->willReturn($countQuery),
            Call::create('addOrderBy')->with('m.name', 'asc'),
            Call::create('setFirstResult')->with(0),
            Call::create('setMaxResults')->with(20),
            Call::create('getQuery')->with()->willReturn($itemsQuery),
        ]);

        /** @var EntityRepository|MockObject $repository */
        $repository = $this->getMockByCalls(EntityRepository::class, [
            Call::create('createQueryBuilder')->with('m', null)->willReturn($queryBuilder),
        ]);

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockByCalls(EntityManager::class, [
            Call::create('getRepository')->with($modelClass)->willReturn($repository),
        ]);

        $repository = new Repository($entityManager, $modelClass);
        $repository->resolveCollection($collection);
    }

    public function testFindById(): void
    {
        $model = $this->getModel();
        $modelClass = get_class($model);

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockByCalls(EntityManager::class, [
            Call::create('find')
                ->with($modelClass, '86c78085-edaf-4df9-95d0-563e45acf618', null, null)
                ->willReturn($model),
        ]);

        $repository = new Repository($entityManager, $modelClass);

        self::assertSame($model, $repository->findById('86c78085-edaf-4df9-95d0-563e45acf618'));
    }

    public function testPersist(): void
    {
        $model = $this->getModel();
        $modelClass = get_class($model);

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockByCalls(EntityManager::class, [
            Call::create('persist')->with($model),
        ]);

        $repository = new Repository($entityManager, $modelClass);
        $repository->persist($model);
    }

    public function testRemove(): void
    {
        $model = $this->getModel();
        $modelClass = get_class($model);

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockByCalls(EntityManager::class, [
            Call::create('remove')->with($model),
        ]);

        $repository = new Repository($entityManager, $modelClass);
        $repository->remove($model);
    }

    public function testFlush(): void
    {
        $model = $this->getModel();
        $modelClass = get_class($model);

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockByCalls(EntityManager::class, [
            Call::create('flush')->with(null),
        ]);

        $repository = new Repository($entityManager, $modelClass);
        $repository->flush();
    }

    /**
     * @return ModelInterface
     */
    private function getModel(): ModelInterface
    {
        /** @var ModelInterface|MockObject $model */
        $model = $this->getMockByCalls(ModelInterface::class);

        return $model;
    }
}
