<?php

declare(strict_types=1);

namespace Generator\Generators;

final readonly class EnvNameGenerator
{
    public function generate(string $namespacePrefix, string $className): string
    {
        return \sprintf(
            '%s_HOST',
            \strtoupper(
                \trim(
                    \implode(
                        '_',
                        \preg_split(
                            '/(?=[A-Z])/',
                            \str_replace('\\', '', $namespacePrefix) . '' . $this->generateClientClassName(
                                $className,
                            ),
                        ),
                    ),
                    '_',
                ),
            ),
        );
    }

    private function generateClientClassName(string $className): string
    {
        $className = \str_replace('Interface', '', $className);
        if (!\str_ends_with($className, 'Service')) {
            $className .= 'Service';
        }

        $className .= 'Client';
        return $className;
    }
}
