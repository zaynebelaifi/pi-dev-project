<?php

namespace App\Tests\Repository;

use App\Repository\MenuRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MenuRepositoryTest extends TestCase
{
    public function testFindForAdminListDefaultsSortAndDir(): void
    {
        $repo = $this->createRepositoryMock();
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('m')
            ->willReturn($qb);

        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->never())->method('setParameter');
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('m.created_at', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $result = $repo->findForAdminList('', 'bad_sort', 'sideways');

        $this->assertSame([], $result);
    }

    public function testFindForAdminListAppliesSearchAndSort(): void
    {
        $repo = $this->createRepositoryMock();
        $qb = $this->createQueryBuilderMock();
        $query = $this->createQueryMock();

        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('m')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('LOWER(m.title) LIKE :q OR LOWER(m.description) LIKE :q')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('q', '%brunch%')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('m.title', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn(['ok']);

        $result = $repo->findForAdminList('  Brunch  ', 'title', 'ASC');

        $this->assertSame(['ok'], $result);
    }

    /**
     * @return MenuRepository&MockObject
     */
    private function createRepositoryMock(): MenuRepository
    {
        return $this->getMockBuilder(MenuRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }

    /**
     * @return QueryBuilder&MockObject
     */
    private function createQueryBuilderMock(): QueryBuilder
    {
        return $this->createMock(QueryBuilder::class);
    }

    /**
     * @return AbstractQuery&MockObject
     */
    private function createQueryMock(): AbstractQuery
    {
        return $this->createMock(AbstractQuery::class);
    }
}
