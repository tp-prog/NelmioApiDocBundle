<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\DependencyInjection;

use Nelmio\ApiDocBundle\DependencyInjection\Configuration;
use Nelmio\ApiDocBundle\Routing\FilteredRouteCollectionBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ConfigurationTest extends TestCase
{
    public function testDefaultArea()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [['areas' => ['path_patterns' => ['/foo']]]]);

        $this->assertSame(['default' => ['path_patterns' => ['/foo'], 'host_patterns' => []]], $config['areas']);
    }

    public function testAreas()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [['areas' => $areas = [
            'default' => ['path_patterns' => ['/foo'], 'host_patterns' => []],
            'internal' => ['path_patterns' => ['/internal'], 'host_patterns' => ['^swagger\.']],
            'commercial' => ['path_patterns' => ['/internal'], 'host_patterns' => []],
            'admin' => ['path_patterns' => ['/'], 'host_patterns' => [], 'check_default' => '_areas'],
        ]]]);

        $this->assertSame($areas, $config['areas']);
    }

    public function testDefaultFilter()
    {
        $pathPattern = [
            '^/api',
        ];
        $checkDefault = 'api_doc';
        $area = 'admin';

        $routes = new RouteCollection();
        $routes->add('r1', new Route('/api/bar/action1', ['api_doc' => 'admin']));
        $routes->add('r2', new Route('/api/foo/action1', ['api_doc' => 'admin']));
        $routes->add('r3', new Route('/api/foo/action2'));
        $routes->add('r4', new Route('/api/demo', ['api_doc' => ['admin', 'default']]));
        $routes->add('r5', new Route('/_profiler/test/test'));

        $options = [
            'path_pattern'  => $pathPattern,
            'host_pattern'  => [],
            'check_default' => $checkDefault,
            'area' => $area
        ];

        $routeBuilder = new FilteredRouteCollectionBuilder($options);
        $filteredRoutes = $routeBuilder->filter($routes);

        $this->assertCount(3, $filteredRoutes);
    }

    public function testAlternativeNames()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[
            'models' => [
                'names' => [
                    [
                        'alias' => 'Foo1',
                        'type' => 'App\Foo',
                        'groups' => ['group'],
                    ],
                    [
                        'alias' => 'Foo2',
                        'type' => 'App\Foo',
                        'groups' => [],
                    ],
                    [
                        'alias' => 'Foo3',
                        'type' => 'App\Foo',
                    ],
                    [
                        'alias' => 'Foo4',
                        'type' => 'App\Foo',
                        'groups' => ['group'],
                        'areas' => ['internal'],
                    ],
                    [
                        'alias' => 'Foo1',
                        'type' => 'App\Foo',
                        'areas' => ['internal'],
                    ],
                ],
            ],
        ]]);
        $this->assertEquals([
            [
                'alias' => 'Foo1',
                'type' => 'App\Foo',
                'groups' => ['group'],
                'areas' => [],
            ],
            [
                'alias' => 'Foo2',
                'type' => 'App\Foo',
                'groups' => [],
                'areas' => [],
            ],
            [
                'alias' => 'Foo3',
                'type' => 'App\Foo',
                'groups' => [],
                'areas' => [],
            ],
            [
                'alias' => 'Foo4',
                'type' => 'App\\Foo',
                'groups' => ['group'],
                'areas' => ['internal'],
            ],
            [
                'alias' => 'Foo1',
                'type' => 'App\\Foo',
                'groups' => [],
                'areas' => ['internal'],
            ],
        ], $config['models']['names']);
    }

    /**
     * @group legacy
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You must not use both `nelmio_api_doc.areas` and `nelmio_api_doc.routes` config options. Please update your config to only use `nelmio_api_doc.areas`.
     */
    public function testBothAreasAndRoutes()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [['areas' => [], 'routes' => []]]);
    }

    /**
     * @group legacy
     * @expectedDeprecation The `nelmio_api_doc.routes` config option is deprecated. Please use `nelmio_api_doc.areas` instead (just replace `routes` by `areas` in your config).
     */
    public function testDefaultConfig()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [['routes' => ['path_patterns' => ['/foo']]]]);

        $this->assertSame(['default' => ['path_patterns' => ['/foo'], 'host_patterns' => []]], $config['areas']);
    }
}
