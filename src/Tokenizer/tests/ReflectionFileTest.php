<?php

namespace Spiral\Tests\Tokenizer;

use PHPUnit\Framework\TestCase;
use Spiral\Tokenizer\Reflection\ReflectionArgument;
use Spiral\Tokenizer\Reflection\ReflectionFile;

class ReflectionFileTest extends TestCase
{
    public function testReflection()
    {
        $reflection = new ReflectionFile(__FILE__);

        $this->assertContains(self::class, $reflection->getClasses());
        $this->assertContains(TestTrait::class, $reflection->getTraits());
        $this->assertContains(TestInterface::class, $reflection->getInterfaces());

        $this->assertSame([__NAMESPACE__ . '\hello'], $reflection->getFunctions());

        $functionA = null;
        $functionB = null;

        foreach ($reflection->getInvocations() as $invocation) {
            if ($invocation->getName() == 'test_function_a') {
                $functionA = $invocation;
            }

            if ($invocation->getName() == 'test_function_b') {
                $functionB = $invocation;
            }
        }

        $this->assertNotEmpty($functionA);
        $this->assertNotEmpty($functionB);

        $this->assertSame(2, count($functionA->getArguments()));
        $this->assertSame(ReflectionArgument::VARIABLE, $functionA->getArgument(0)->getType());
        $this->assertSame('$this', $functionA->getArgument(0)->getValue());

        $this->assertSame(ReflectionArgument::EXPRESSION, $functionA->getArgument(1)->getType());
        $this->assertSame('$a+$b', $functionA->getArgument(1)->getValue());

        $this->assertSame(2, $functionB->countArguments());

        $this->assertSame(ReflectionArgument::STRING, $functionB->getArgument(0)->getType());
        $this->assertSame('"string"', $functionB->getArgument(0)->getValue());
        $this->assertSame('string', $functionB->getArgument(0)->stringValue());

        $this->assertSame(ReflectionArgument::CONSTANT, $functionB->getArgument(1)->getType());
        $this->assertSame('123', $functionB->getArgument(1)->getValue());
    }

    private function deadend()
    {
        $a = $b = null;
        test_function_a($this, $a + $b);
        test_function_b("string", 123);
    }
}

function hello()
{
}
// phpcs:disable
trait TestTrait
{

}

interface TestInterface
{

}
