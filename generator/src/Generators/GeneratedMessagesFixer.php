<?php

declare(strict_types=1);

namespace Generator\Generators;

use Generator\PHP\AnnotationsParser;
use Generator\PHP\ClassDeclarationFactory;
use Google\Protobuf\Internal\Message;
use Spiral\Files\FilesInterface;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class GeneratedMessagesFixer implements GeneratorInterface
{
    public function __construct(
        private FilesInterface $files,
        private OutputInterface $output,
        private AnnotationsParser $annotationsParser = new AnnotationsParser(),
    ) {}

    public function run(array $files, string $targetPath, string $commandNamespace): void
    {
        $factory = new ClassDeclarationFactory($this->files);

        foreach ($files as $file) {
            try {
                $class = $factory->createFromFile($file);
            } catch (\Throwable) {
                $this->output->writeln('<error>Failed to parse file "' . $file . '"</error>');
                continue;
            }

            if (!$class->getReflection()->isSubclassOf(Message::class)) {
                continue;
            }

            $refl = $class->getReflection();
            $docblock = $refl->getDocComment();
            if (!empty($docblock)) {
                $annotations = $this->annotationsParser->parseFromClass($refl);
                $class->class->setComment(AnnotationsParser::fixDocComment($docblock));

                foreach ($annotations as $annotation) {
                    // todo do something with annotations here
                }
            }

            foreach ($refl->getProperties() as $property) {
                $docblock = $property->getDocComment();
                if (empty($docblock)) {
                    continue;
                }

                $docblock = AnnotationsParser::fixDocComment($docblock);
                try {
                    $p = $class->class->getProperty($property->getName());
                    $p->setComment($docblock);
                } catch (\Throwable) {
                    if ($this->output->isVerbose()) {
                        $this->output->writeln(
                            '<error>Failed to add attribute to property "' . $property->getName() . '"</error>',
                        );
                    }
                }
            }


            $class->persist();
        }
    }
}
