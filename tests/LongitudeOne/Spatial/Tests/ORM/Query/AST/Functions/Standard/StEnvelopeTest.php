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

namespace LongitudeOne\Spatial\Tests\ORM\Query\AST\Functions\Standard;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\ORMException;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\Exception\UnsupportedPlatformException;
use LongitudeOne\Spatial\Tests\Helper\PolygonHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_Envelope DQL function tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StEnvelopeTest extends OrmTestCase
{
    use PolygonHelperTrait;

    /**
     * Setup the function type test.
     *
     * @throws Exception                    when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @throws Exception                    when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws InvalidValueException        when geometries are not valid
     *
     * @group geometry
     */
    public function testSelectStEnvelope()
    {
        $this->createBigPolygon();
        $this->createHoleyPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT ST_AsText(ST_Envelope(p.polygon)) FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p'
        );
        $result = $query->getResult();

        switch ($this->getPlatform()->getName()) {
            case 'mysql':
                //polygon is equals, but not the same
                $expected = 'POLYGON((0 0,10 0,10 10,0 10,0 0))';
                break;
            case 'postgresql':
            default:
                $expected = 'POLYGON((0 0,0 10,10 10,10 0,0 0))';
        }
        static::assertEquals($expected, $result[0][1]);
        static::assertEquals($expected, $result[1][1]);
    }

    /**
     * Test a DQL containing function to test in the predicate.
     *
     * @throws Exception                    when connection failed
     * @throws ORMException                 when cache is not set
     * @throws UnsupportedPlatformException when platform is unsupported
     * @throws InvalidValueException        when geometries are not valid
     *
     * @group geometry
     */
    public function testStEnvelopeWhereParameter()
    {
        $holeyPolygon = $this->createHoleyPolygon();
        $this->createSmallPolygon();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            'SELECT p FROM LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity p WHERE ST_Envelope(p.polygon) = ST_GeomFromText(:p)'
            // phpcs:enable
        );

        switch ($this->getPlatform()->getName()) {
            case 'mysql':
                $parameter = 'POLYGON((0 0,10 0,10 10,0 10,0 0))';
                break;
            case 'postgresql':
            default:
                $parameter = 'POLYGON((0 0,0 10,10 10,10 0,0 0))';
        }

        $query->setParameter('p', $parameter, 'string');

        $result = $query->getResult();

        static::assertCount(1, $result);
        static::assertEquals($holeyPolygon, $result[0]);
    }
}
