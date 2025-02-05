<?php

declare(strict_types=1);

namespace Spiral\Tests\Scaffolder\Command;

use ReflectionClass;
use ReflectionException;
use Throwable;

class NamespacedNameTest extends AbstractCommandTest
{
    private const CLASS_NAME = '\\Spiral\\Tests\\Scaffolder\\App\\Controller\\Namespaced\\SampleController';

    public function tearDown(): void
    {
        $this->deleteDeclaration(self::CLASS_NAME);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function testScaffold(): void
    {
        $this->console()->run('create:controller', [
            'name'      => 'namespaced/sample',
            '--comment' => 'Sample Controller',
            '-a'        => ['index', 'save']
        ]);

        clearstatcache();
        $this->assertTrue(class_exists(self::CLASS_NAME));

        $reflection = new ReflectionClass(self::CLASS_NAME);

        $this->assertStringContainsString('strict_types=1', $this->files()->read($reflection->getFileName()));
        $this->assertStringContainsString('Sample Controller', $reflection->getDocComment());
        $this->assertTrue($reflection->hasMethod('index'));
        $this->assertTrue($reflection->hasMethod('save'));
    }
}
