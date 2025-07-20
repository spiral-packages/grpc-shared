<?php

declare(strict_types=1);

namespace Generator\Console;

use Generator\CommandExecutor;
use Generator\Exception\CompileException;
use Generator\Generators\BootloaderGenerator;
use Generator\Generators\CommandClassGenerator;
use Generator\Generators\ConfigGenerator;
use Generator\Generators\EnvTemplateGenerator;
use Generator\Generators\GeneratedMessagesFixer;
use Generator\Generators\GeneratorInterface;
use Generator\Generators\ServiceClientGenerator;
use Generator\Generators\ServiceInterfaceAttributesGenerator;
use Generator\ProtocCommandBuilder;
use Generator\ProtoCompiler;
use Spiral\Files\Files;
use Spiral\Files\FilesInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'generate')]
final class GeneratorCommand extends Command
{
    /**
     * @param non-empty-string $rootDir
     */
    public function __construct(
        private readonly FilesInterface $files,
        private readonly string $rootDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate PHP gRPC classes from proto files')
            ->addOption(
                'proto',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Path to directory containing proto files (can be used multiple times)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $binaryPath = $this->rootDir . '/protoc-gen-php-grpc';

        if (!\file_exists($binaryPath)) {
            $output->writeln(
                '<error>protoc-gen-php-grpc binary not found. Please, run "composer download" to download it.</error>',
            );

            return self::FAILURE;
        }

        /** @var string[] $protoFileDirs */
        $protoFileDirs = $input->getOption('proto-path');

        if (empty($protoFileDirs)) {
            $output->writeln(
                '<error>No proto-path specified. Use --proto-path option to specify directories containing proto files.</error>',
            );
            $output->writeln('<info>Example: php console generate --proto-path /path/to/proto/files</info>');

            return self::FAILURE;
        }

        $namespace = 'Internal\Shared\gRPC';
        $files = new Files();

        $compiler = new ProtoCompiler(
            $this->rootDir . '/generated',
            $namespace,
            $this->files,
            new ProtocCommandBuilder($this->files, $this->rootDir . 'vendor/internal/proto-files', $binaryPath),
            new CommandExecutor(),
        );

        $compiled = [];
        foreach ($protoFileDirs as $dir) {
            if (!\is_dir($dir)) {
                $output->writeln("<error>Proto files dir `$dir` not found.</error>");
                continue;
            }

            if ($output->isVerbose()) {
                $output->writeln(\sprintf("\n<info>Compiling <fg=cyan>`%s`</fg=cyan>:</info>", \basename($dir)));
            }

            try {
                $result = $compiler->compile($dir);
            } catch (CompileException $e) {
                throw $e;
            } catch (\Throwable $e) {
                $output->writeln("<error>Error:</error> <fg=red>{$e->getMessage()}</fg=red>");
                continue;
            }

            if ($result === []) {
                $output->writeln("<error>No files were generated for `$dir`.</error>");
                continue;
            }

            foreach ($result as $file) {
                if ($output->isVerbose()) {
                    $output->writeln(
                        \sprintf(
                            "<fg=green>•</fg=green> %s%s%s",
                            "\033[1;38m",
                            $files->relativePath($file, $this->rootDir),
                            "\e[0m",
                        ),
                    );
                }

                $compiled[] = $file;
            }
        }

        /** @var GeneratorInterface[] $generators */
        $generators = [
            new ConfigGenerator($this->files, $output),
            new ServiceClientGenerator($this->files, $output),
            new BootloaderGenerator($this->files, $output),
            new GeneratedMessagesFixer($this->files, $output),
            new CommandClassGenerator($this->files, $output),
            new ServiceInterfaceAttributesGenerator($this->files, $output),
            new EnvTemplateGenerator($this->files, $output),
        ];

        foreach ($generators as $generator) {
            $output->writeln(\sprintf("<info>Running <fg=cyan>`%s`</fg=cyan>:</info>", $generator::class));
            $generator->run(
                $compiled,
                $this->rootDir . '/src',
                $namespace,
            );
        }

        $output->writeln("<info>Done!</info>");

        return Command::SUCCESS;
    }
}
