<?php
namespace Aequation\WireBundle\Command;

use Aequation\WireBundle\Entity\interface\WireUserInterface;
use Aequation\WireBundle\Service\interface\WireUserServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aew:sadmin',
    description: 'Create or restore super admin user',
)]
class SadminCommand extends Command
{
    public function __construct(
        private WireUserServiceInterface $userService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Checking super admin user!');

        // Check or create super admin user
        $sadmin = $this->userService->checkMainSuperadmin();

        if($sadmin instanceof WireUserInterface) {
            $io->success(sprintf('Super admin user found: %s (%s)', $sadmin->getUserIdentifier(), implode(', ', $sadmin->getRoles())));
        } else {
            $io->error('No super admin user found and could not create one!');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
