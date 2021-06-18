<?php
/**
 * This file is part of the doctrine spatial extension.
 *
 * PHP 7.4 | 8.0
 *
 * (c) Alexandre Tranchant <alexandre.tranchant@gmail.com> 2017 - 2021
 * (c) Longitude One 2020 - 2021
 * (c) 2015 Derek J. Lambert
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace LongitudeOne\Spatial\Tests\ORM\Query\AST\Functions\PostgreSql;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\Exception\UnsupportedPlatformException;
use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * SP_Split DQL function tests.
 * This function is not issue from the OGC, but it is useful for Database postgresql.
 *
 * @see https://postgis.net/docs/ST_Split.html
 *
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://alexandre-tranchant.mit-license.org MIT
 *
 * @group dql
 * @group pgsql-only
 *
 * @internal
 * @coversDefaultClass
 */
class SpSplitTest extends OrmTestCase
{
    use LineStringHelperTrait;

    /**
     * Setup the function type test.
     *
     * @throws Exception                    when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
        $this->supportsPlatform('postgresql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @throws Exception                    when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws InvalidValueException        when geometries are not valid
     * @throws MappingException             when clear fails
     *
     * @group geometry
     */
    public function testFunctionInSelect()
    {
        $straightLineString = $this->createStraightLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT l, ST_AsText(PgSQL_Split(l.lineString, ST_GeomFromText(:g))) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
            // phpcs:enable
        );
        $query->setParameter('g', 'POINT (3 3)');
        $result = $query->getResult();

        static::assertIsArray($result);
        static::assertCount(1, $result);
        static::assertEquals($straightLineString, $result[0][0]);
        static::assertSame('GEOMETRYCOLLECTION(LINESTRING(0 0,2 2,3 3),LINESTRING(3 3,5 5))', $result[0][1]);
    }
}
