<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mapping\Serialization;

use App\Collection\AbstractCollection;
use App\Collection\CollectionInterface;
use App\Mapping\Serialization\AbstractCollectionMapping;
use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\Serialization\Mapping\NormalizationFieldMappingBuilder;
use Chubbyphp\Serialization\Mapping\NormalizationLinkMappingInterface;
use Chubbyphp\Serialization\Normalizer\NormalizerContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouterInterface;

/**
 * @covers \App\Mapping\Serialization\AbstractCollectionMapping
 */
class CollectionMappingTest extends TestCase
{
    use MockByCallsTrait;

    public function testGetClass(): void
    {
        /** @var RouterInterface|MockObject $router */
        $router = $this->getMockByCalls(RouterInterface::class);

        $mapping = $this->getCollectionMapping($router);

        self::assertSame($this->getClass(), $mapping->getClass());
    }

    public function testGetNormalizationType(): void
    {
        /** @var RouterInterface|MockObject $router */
        $router = $this->getMockByCalls(RouterInterface::class);

        $mapping = $this->getCollectionMapping($router);

        self::assertSame($this->getNormalizationType(), $mapping->getNormalizationType());
    }

    public function testGetNormalizationFieldMappings(): void
    {
        /** @var RouterInterface|MockObject $router */
        $router = $this->getMockByCalls(RouterInterface::class);

        $mapping = $this->getCollectionMapping($router);

        $fieldMappings = $mapping->getNormalizationFieldMappings('/');

        self::assertEquals([
            NormalizationFieldMappingBuilder::create('offset')->getMapping(),
            NormalizationFieldMappingBuilder::create('limit')->getMapping(),
            NormalizationFieldMappingBuilder::create('count')->getMapping(),
            NormalizationFieldMappingBuilder::create('sort')->getMapping(),
        ], $fieldMappings);
    }

    public function testGetNormalizationEmbeddedFieldMappings(): void
    {
        /** @var RouterInterface|MockObject $router */
        $router = $this->getMockByCalls(RouterInterface::class);

        $mapping = $this->getCollectionMapping($router);

        $fieldMappings = $mapping->getNormalizationEmbeddedFieldMappings('/');

        self::assertEquals([
            NormalizationFieldMappingBuilder::createEmbedMany('items')->getMapping(),
        ], $fieldMappings);
    }

    public function testGetNormalizationLinkMappings(): void
    {
        /** @var RouterInterface|MockObject $router */
        $router = $this->getMockByCalls(RouterInterface::class, [
            Call::create('pathFor')
                ->with($this->getListRoute(), [], ['offset' => 0, 'limit' => 20])
                ->willReturn(sprintf('%s?offset=0&limit=20', $this->getCollectionPath())),
            Call::create('pathFor')
                ->with($this->getCreateRoute(), [], [])
                ->willReturn(sprintf('%s', $this->getCollectionPath())),
        ]);

        $mapping = $this->getCollectionMapping($router);

        $linkMappings = $mapping->getNormalizationLinkMappings('/');

        self::assertCount(2, $linkMappings);

        self::assertInstanceOf(NormalizationLinkMappingInterface::class, $linkMappings[0]);
        self::assertInstanceOf(NormalizationLinkMappingInterface::class, $linkMappings[1]);

        $object = new class() extends AbstractCollection {
        };

        $object->setOffset(0);
        $object->setLimit(20);
        $object->setCount(25);

        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockByCalls(ServerRequestInterface::class, [
            Call::create('getQueryParams')->with()->willReturn([]),
        ]);

        /** @var NormalizerContextInterface|MockObject $context */
        $context = $this->getMockByCalls(NormalizerContextInterface::class, [
            Call::create('getRequest')->with()->willReturn($request),
        ]);

        $list = $linkMappings[0]->getLinkNormalizer()->normalizeLink('/', $object, $context);
        $create = $linkMappings[1]->getLinkNormalizer()->normalizeLink('/', $object, $context);

        self::assertSame([
            'href' => sprintf('%s?offset=0&limit=20', $this->getCollectionPath()),
            'templated' => false,
            'rel' => [],
            'attributes' => [
                'method' => 'GET',
            ],
        ], $list);

        self::assertSame([
            'href' => sprintf('%s', $this->getCollectionPath()),
            'templated' => false,
            'rel' => [],
            'attributes' => [
                'method' => 'POST',
            ],
        ], $create);
    }

    /**
     * @return string
     */
    protected function getClass(): string
    {
        return CollectionInterface::class;
    }

    /**
     * @return string
     */
    protected function getNormalizationType(): string
    {
        return 'collection';
    }

    /**
     * @return string
     */
    protected function getListRoute(): string
    {
        return 'collection_list';
    }

    /**
     * @return string
     */
    protected function getCreateRoute(): string
    {
        return 'collection_create';
    }

    /**
     * @return string
     */
    protected function getCollectionPath(): string
    {
        return '/api/collection';
    }

    /**
     * @param RouterInterface $router
     *
     * @return AbstractCollectionMapping
     */
    protected function getCollectionMapping(RouterInterface $router): AbstractCollectionMapping
    {
        return new class(
            $router,
            $this->getClass(),
            $this->getNormalizationType(),
            $this->getListRoute(),
            $this->getCreateRoute()
        ) extends AbstractCollectionMapping {
            /**
             * @var string
             */
            private $class;

            /**
             * @var string
             */
            private $normalizationType;

            /**
             * @var string
             */
            private $listRouteName;

            /**
             * @var string
             */
            private $createRouteName;

            /**
             * @param RouterInterface $router
             * @param string          $class
             * @param string          $normalizationType
             * @param string          $listRouteName
             * @param string          $createRouteName
             */
            public function __construct(
                RouterInterface $router,
                string $class,
                string $normalizationType,
                string $listRouteName,
                string $createRouteName
            ) {
                parent::__construct($router);

                $this->class = $class;
                $this->normalizationType = $normalizationType;
                $this->listRouteName = $listRouteName;
                $this->createRouteName = $createRouteName;
            }

            /**
             * @return string
             */
            public function getClass(): string
            {
                return $this->class;
            }

            /**
             * @return string
             */
            public function getNormalizationType(): string
            {
                return $this->normalizationType;
            }

            /**
             * @return string
             */
            protected function getListRouteName(): string
            {
                return $this->listRouteName;
            }

            /**
             * @return string
             */
            protected function getCreateRouteName(): string
            {
                return $this->createRouteName;
            }
        };
    }
}
