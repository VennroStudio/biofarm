<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FixturesLoadCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('fixtures:load')
            ->setDescription('Load fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Fixtures loading not implemented yet</info>');
        
        // TODO: Implement fixtures loading
        // Example:
        // $fixtures = [...];
        // foreach ($fixtures as $fixture) {
        //     $this->em->persist($fixture);
        // }
        // $this->em->flush();
        
        return Command::SUCCESS;
    }
}
