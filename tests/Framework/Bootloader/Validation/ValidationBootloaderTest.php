<?php

declare(strict_types=1);

namespace Framework\Bootloader\Validation;

use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Spiral\Tests\Framework\BaseTest;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validation\Config\ValidationConfig;
use Spiral\Validation\Exception\ValidationException;
use Spiral\Validation\ValidationInterface;
use Spiral\Validation\ValidationProvider;
use Spiral\Validation\ValidationProviderInterface;
use Spiral\Validation\ValidatorInterface;

final class ValidationBootloaderTest extends BaseTest
{
    public function testValidationProviderInterfaceBinding(): void
    {
        $this->assertContainerBoundAsSingleton(ValidationProviderInterface::class, ValidationProvider::class);
    }

    public function testValidationInterfaceBinding(): void
    {
        $validator = $this->createValidator();

        $this->getContainer()->bind(ValidationConfig::class, new ValidationConfig(['defaultValidator' => 'foo']));
        $this->getContainer()
            ->get(ValidationProviderInterface::class)
            ->register('foo', static fn () => $validator);

        $this->assertContainerBoundAsSingleton(ValidationInterface::class, $validator::class);
    }

    public function testValidatorIsNotConfigured(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Default Validator is not configured.');
        $this->getContainer()->get(ValidationInterface::class);
    }

    public function testSetDefaultValidator(): void
    {
        $validator = $this->createValidator();
        $this->getContainer()
            ->get(ValidationProviderInterface::class)
            ->register('bar', static fn () => $validator);

        $bootloader = $this->getContainer()->get(ValidationBootloader::class);
        $bootloader->setDefaultValidator('bar');

        $this->assertContainerBoundAsSingleton(ValidationInterface::class, $validator::class);
    }

    public function testSetDefaultValidatorNotOverrideValueInConfig(): void
    {
        $this->getContainer()
            ->get(ConfiguratorInterface::class)
            ->modify(ValidationConfig::CONFIG, new Set('defaultValidator', 'foo'));

        $bootloader = $this->getContainer()->get(ValidationBootloader::class);
        $bootloader->setDefaultValidator('bar');

        $this->assertSame('foo', $this->getConfig(ValidationConfig::CONFIG)['defaultValidator']);
    }

    private function createValidator(): ValidationInterface
    {
        return new class implements ValidationInterface
        {
            public function validate(object|array $data, array $rules, mixed $context = null): ValidatorInterface
            {
            }
        };
    }
}
