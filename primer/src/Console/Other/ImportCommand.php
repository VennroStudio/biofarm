<?php

declare(strict_types=1);

namespace App\Console\Other;

use App\Modules\Command\Artist\Create;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ImportCommand extends Command
{
    public function __construct(
        private readonly Create\Handler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('other:import')
            ->setDescription('Import artists command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file =  __DIR__ . '/../../../var/temp/music.xlsx';
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        /** @var array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string} $row */
        foreach ($rows as $row) {
            $unionId = (int)$row[0];

            if ($unionId <= 0) {
                $output->writeln('<error>Error unionId</error>');
                continue;
            }

            try {
                $this->handler->handle(
                    new Create\Command(
                        name: trim($row['1']),
                        unionId: $unionId,
                        communityName: null,
                        description: null,
                        categoryId: null,
                        links: [
                            trim($row['2'] ?? ''),
                            trim($row['3'] ?? ''),
                            trim($row['4'] ?? ''),
                            trim($row['5'] ?? ''),
                        ]
                    )
                );
            } catch (Throwable $e) {
                $output->writeln('<error>' . $row['1'] . ' - ' . $e->getMessage() . '</error>');
                continue;
            }

            // $output->writeln('<info>' . $row['1'] . ' - added</info>');
        }

        $output->writeln('<info>Total: ' . \count($rows) . '</info>');

        return 0;
    }
}
