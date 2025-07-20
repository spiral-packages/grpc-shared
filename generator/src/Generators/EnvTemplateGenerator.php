<?php

declare(strict_types=1);

namespace Generator\Generators;

use Spiral\Files\FilesInterface;
use Spiral\Reactor\FileDeclaration;
use Spiral\Reactor\Partial\PhpNamespace;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class EnvTemplateGenerator implements GeneratorInterface
{
    public function __construct(
        private FilesInterface $files,
        private OutputInterface $output,
    ) {}

    /**
     * @param  non-empty-string[]  $files
     * @param  non-empty-string    $targetPath
     * @param  non-empty-string    $namespace
     */
    public function run(
        array $files,
        string $targetPath,
        string $namespace,
    ): void {
        $result = [];

        foreach ($files as $service) {
            if (!\str_ends_with($service, 'Interface.php')) {
                continue;
            }

            $interfaceFile = FileDeclaration::fromCode(
                $this->files->read($service),
            );
            $interface = $interfaceFile->getInterfaces()->getIterator()
                ->current();
            $interfaceName = $interface->getName();

            \assert($interfaceName !== null);

            /** @var PhpNamespace $interfaceNamespace */
            $interfaceNamespace = $interfaceFile->getNamespaces()->getIterator()
                ->current();
            $namespacePrefix = $this->removePrefixBeforeVersion(
                $interfaceNamespace->getName(),
            );

            $result[] = \sprintf(
                '%s=',
                (new EnvNameGenerator())->generate(
                    $namespacePrefix,
                    $interfaceName,
                ),
            );
        }
        $this->output->writeln(\implode("\n", $result));
    }

    private function removePrefixBeforeVersion(string $classPath): string
    {
        // Pattern to match everything before v1 or v2 (non-greedy match) including v1 or v2
        $pattern = '/.*?(v[0-9]+)/';

        return \preg_replace_callback($pattern, static fn($matches) =>
            // Return only the version part (v1 or v2)
            $matches[1], $classPath);
    }
}
