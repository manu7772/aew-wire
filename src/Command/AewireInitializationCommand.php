<?php
namespace Aequation\WireBundle\Command;

use Aequation\WireBundle\Service\interface\InitializerInterface;
// Symfony
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aewire:init',
    description: 'Initialization of Aequation Wire Bundle',
)]
class AewireInitializationCommand extends Command
{

    public function __construct(
        private InitializerInterface $initializer
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // $this
        //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        // ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if($this->initializer->installConfig('copy_yaml_files')) {
            $io->success(vsprintf("Les fichiers de config du bundle ont été installés.\nPensez à rafraîchir le cache ($ symfony console c:c).", []));
            return Command::SUCCESS;
        } else {
            $io->warning(vsprintf('Une erreur s\'est produite lors de la configuration. Les fichiers n\'ont pas pu être tous ajoutés.', []));
            return Command::FAILURE;
        }

    }

}
