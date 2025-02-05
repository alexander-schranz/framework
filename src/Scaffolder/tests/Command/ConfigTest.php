<?php

declare(strict_types=1);

namespace Spiral\Tests\Scaffolder\Command;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\StringInput;
use Throwable;

class ConfigTest extends AbstractCommandTest
{
    private const CLASS_NAME = '\\Spiral\\Tests\\Scaffolder\\App\\Config\\SampleConfig';

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
        $this->console()->run('create:config', [
            'name'      => 'sample',
            '--comment' => 'Sample Config'
        ]);

        clearstatcache();
        $this->assertTrue(class_exists(self::CLASS_NAME));

        $reflection = new ReflectionClass(self::CLASS_NAME);
        $content = $this->files()->read($reflection->getFileName());

        $this->assertStringContainsString('strict_types=1', $content);
        $this->assertStringContainsString('{project-name}', $content);
        $this->assertStringContainsString('@author {author-name}', $content);
        $this->assertStringContainsString('Sample Config', $reflection->getDocComment());

        $this->assertTrue($reflection->hasConstant('CONFIG'));
        $this->assertTrue($reflection->hasProperty('config'));

        $this->assertIsString($reflection->getReflectionConstant('CONFIG')->getValue());
        $this->assertEquals([], $reflection->getDefaultProperties()['config']);
    }

    /**
     * @throws Throwable
     */
    public function testReverse(): void
    {
        $className = '\\Spiral\\Tests\\Scaffolder\\App\\Config\\ReversedConfig';
        $this->console()->run(null, new StringInput('create:config reversed -r'));

        clearstatcache();
        $this->assertTrue(class_exists($className));
    }

    /**
     * @throws Throwable
     */
    public function testReverseDefinition(): void
    {
        $className = '\\Spiral\\Tests\\Scaffolder\\App\\Config\\ReversedConfig';
        $this->console()->run('create:config', [
            'name'      => 'reversed',
            '--comment' => 'Reversed Config',
            '--reverse' => true
        ]);

        clearstatcache();
        $this->assertTrue(class_exists($className));

        $reflection = new ReflectionClass($className);

        $this->assertTrue($reflection->hasConstant('CONFIG'));
        $this->assertTrue($reflection->hasProperty('config'));

        $this->assertIsString($reflection->getReflectionConstant('CONFIG')->getValue());
        $this->assertIsArray($reflection->getDefaultProperties()['config']);
        $this->assertNotEmpty($reflection->getDefaultProperties()['config']);

        $methods = [
            'getStrParam'   => ['hint' => 'string', 'annotation' => 'string'],
            'getIntParam'   => ['hint' => 'int', 'annotation' => 'int'],
            'getFloatParam' => ['hint' => 'float', 'annotation' => 'float'],
            'getBoolParam'  => ['hint' => 'bool', 'annotation' => 'bool'],
            'getNullParam'  => ['hint' => null, 'annotation' => 'null'],

            'getArrParam' => ['hint' => 'array', 'annotation' => 'array|string[]'],

            'getMapParam'   => ['hint' => 'array', 'annotation' => 'array|string[]'],
            'getMapParamBy' => ['hint' => 'string', 'annotation' => 'string'],

            'getMixedArrParam' => ['hint' => 'array', 'annotation' => 'array'],
            'getParams'        => ['hint' => 'array', 'annotation' => 'array|string[]'],

            'getParameters' => ['hint' => 'array', 'annotation' => 'array|array[]'],
            'getParameter'  => ['hint' => 'array', 'annotation' => 'array'],

            'getConflicts'  => ['hint' => 'array', 'annotation' => 'array|array[]'],
            'getConflict'   => ['hint' => 'string', 'annotation' => 'string'],
            'getConflictBy' => ['hint' => 'array', 'annotation' => 'array|int[]'],

            'getValues'  => ['hint' => 'array', 'annotation' => 'array|array[]'],
            'getValue'   => ['hint' => 'string', 'annotation' => 'string'],
            'getValueBy' => ['hint' => 'string', 'annotation' => 'string'],
        ];

        $reflectionMethods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->name !== $reflection->name) {
                continue;
            }

            $reflectionMethods[$method->name] = $method;
            $this->assertArrayHasKey($method->name, $methods);

            if (!$method->hasReturnType()) {
                $this->assertNull($methods[$method->name]['hint']);
            } else {
                $this->assertEquals($methods[$method->name]['hint'], $method->getReturnType()->getName());
            }

            $this->assertStringContainsString($methods[$method->name]['annotation'], $method->getDocComment());
        }

        $this->assertCount(count($methods), $reflectionMethods);

        $this->deleteDeclaration($className);
    }

    /**
     * @throws Throwable
     */
    public function testReverseWeirdKeys(): void
    {
        $className = '\\Spiral\\Tests\\Scaffolder\\App\\Config\\WeirdConfig';
        $this->console()->run('create:config', [
            'name'      => 'weird',
            '--comment' => 'Weird Config',
            '--reverse' => true
        ]);

        clearstatcache();
        $this->assertTrue(class_exists($className));

        $reflection = new ReflectionClass($className);

        $this->assertTrue($reflection->hasConstant('CONFIG'));
        $this->assertTrue($reflection->hasProperty('config'));

        $this->assertIsString($reflection->getReflectionConstant('CONFIG')->getValue());
        $this->assertIsArray($reflection->getDefaultProperties()['config']);
        $this->assertNotEmpty($reflection->getDefaultProperties()['config']);

        $methods = [
            'getAthello',
            'getWithSpaces',
            'getAndOtherChars',
            'getWithUnderscoreAndDashes'
        ];

        $reflectionMethods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->name !== $reflection->name) {
                continue;
            }
            $reflectionMethods[$method->name] = $method;

            $this->assertContains($method->name, $methods);
        }

        $this->assertCount(count($methods), $reflectionMethods);

        $this->deleteDeclaration($className);
    }

    /**
     * @throws Throwable
     */
    public function testConfigFile(): void
    {
        $filename = $this->createConfig('sample', 'Sample Config');
        $this->assertStringContainsString('strict_types=1', $this->files()->read($filename));
        $this->assertStringContainsString(
            '@see \\Spiral\\Tests\\Scaffolder\\App\\Config\\SampleConfig',
            $this->files()->read($filename)
        );

        $this->deleteConfigFile($filename);
    }

    /**
     * @throws Throwable
     */
    public function testConfigFileExists(): void
    {
        $filename = $this->createConfig('sample2', 'Sample2 Config');
        $this->files()->append($filename, '//sample comment');

        $source = $this->files()->read($filename);
        $this->assertStringContainsString('//sample comment', $source);

        $filename = $this->createConfig('sample2', 'Sample2 Config');

        $source = $this->files()->read($filename);
        $this->assertStringContainsString('//sample comment', $source);

        $this->deleteConfigFile($filename);
        $this->deleteDeclaration('\\Spiral\\Tests\\Scaffolder\\App\\Config\\Sample2Config');
    }

    /**
     * @param string $filename
     * @throws Throwable
     */
    private function deleteConfigFile(string $filename): void
    {
        $this->files()->delete($filename);
    }

    /**
     * @param string $name
     * @param string $comment
     * @return string
     * @throws Throwable
     */
    private function createConfig(string $name, string $comment): string
    {
        $this->console()->run('create:config', [
            'name'      => $name,
            '--comment' => $comment
        ]);

        clearstatcache();

        $filename = $this->app->directory('config') . "$name.php";
        $this->assertFileExists($filename);

        return $filename;
    }
}
