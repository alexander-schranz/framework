<?php

declare(strict_types=1);

namespace Spiral\Translator\Catalogue;

use Spiral\Logger\Traits\LoggerTrait;
use Spiral\Translator\Catalogue;
use Spiral\Translator\CatalogueInterface;
use Spiral\Translator\Config\TranslatorConfig;
use Symfony\Component\Finder\Finder;

final class CatalogueLoader implements LoaderInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly TranslatorConfig $config
    ) {
    }

    public function hasLocale(string $locale): bool
    {
        $locale = \preg_replace('/[^a-zA-Z_]/', '', \mb_strtolower($locale));

        return \is_dir($this->config->getLocaleDirectory($locale));
    }

    public function getLocales(): array
    {
        if (!\is_dir($this->config->getLocalesDirectory())) {
            return [];
        }

        $finder = new Finder();
        $finder->in($this->config->getLocalesDirectory())->directories();

        $locales = [];

        foreach ($finder->directories() as $directory) {
            $locales[] = $directory->getFilename();
        }

        return $locales;
    }

    public function loadCatalogue(string $locale): CatalogueInterface
    {
        $locale = \preg_replace('/[^a-zA-Z_]/', '', \mb_strtolower($locale));
        $catalogue = new Catalogue($locale);

        if (!$this->hasLocale($locale)) {
            return $catalogue;
        }

        $finder = new Finder();
        $finder->in($this->config->getLocaleDirectory($locale));

        foreach ($finder->getIterator() as $file) {
            $this->getLogger()->info(
                \sprintf(
                    "found locale domain file '%s'",
                    $file->getFilename()
                ),
                ['file' => $file->getFilename()]
            );

            //Per application agreement domain name must present in filename
            $domain = \strstr($file->getFilename(), '.', true);

            if (!$this->config->hasLoader($file->getExtension())) {
                $this->getLogger()->warning(
                    \sprintf(
                        "unable to load domain file '%s', no loader found",
                        $file->getFilename()
                    ),
                    ['file' => $file->getFilename()]
                );

                continue;
            }

            $catalogue->mergeFrom(
                $this->config->getLoader($file->getExtension())->load(
                    (string)$file,
                    $locale,
                    $domain
                )
            );
        }

        return $catalogue;
    }
}
