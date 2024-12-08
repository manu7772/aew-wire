<?php
namespace Aequation\WireBundle\Command;

use Aequation\WireBundle\Component\Opresult;
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

        $configNames = $this->initializer->getConfigNames();
        $opresults = [];
        foreach($configNames as $name) {
            $opresults[$name] = $this->initializer->installConfig($name);
            if($opresults[$name]->isSuccess()) {
                $io->success(vsprintf("Le fichier de config '%s' a été installé.", [$name]));
            } else if($opresults[$name]->hasWarning()) {
                $io->warning($opresults[$name]->getMessagesAsString(Opresult::ACTION_WARNING));
            }
            if($opresults[$name]->isFail()) {
                $io->error($opresults[$name]->getMessagesAsString(Opresult::ACTION_DANGER));
            }
        }
        // Finally
        foreach ($opresults as $opresult) {
            if($opresult->isSuccess()) {
                $success_messages = $opresult->getMessages(Opresult::ACTION_SUCCESS);
                if(count($success_messages) > 0) {
                    $io->success($opresult->getMessagesAsString(Opresult::ACTION_SUCCESS));
                } else {
                    $io->success(vsprintf("Les fichiers de config '%s' ont été installés. \nPensez à rafraîchir le cache  => $ symfony console c:c.", [$name]));
                }
                $result = Command::SUCCESS;
            } else if($opresult->isFail()) {
                $io->warning(vsprintf('La configuration est incomplète.', []));
                $result = Command::FAILURE;
            }
        }
        // Result
        return $result;
    }

}
