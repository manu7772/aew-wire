<?php
namespace Aequation\WireBundle\Command;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dbcheck',
    description: 'Génère des entités d\'après des fichiers de description',
)]
class checkDatabase extends BaseCommand
{
    protected const ALL_CLASSES = 'Toute les classes';

    public function __construct(
        protected WireEntityManagerInterface $wireEm
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check the database for consistency')
            ->setHelp('This command allows you to check the database for consistency');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /**
         * @see https://symfony.com/doc/current/components/console/helpers/questionhelper.html
         * Styles
         * @see https://symfony.com/doc/current/console/style.html
         * @see https://symfony.com/doc/current/console/coloring.html
         * colors: black, red, green, yellow, blue, magenta, cyan, white, default, gray, bright-red, bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
         */

        $io->title('Contrôle des entités de la base de données');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $all_classnames = $this->wireEm->getEntityNames(true, false, true);
        $question = new ChoiceQuestion(
            question: 'Choisissez une ou plusieurs classes d\'entités à contrôler :',
            choices: array_merge([0 => static::ALL_CLASSES], array_keys($all_classnames)),
            default: 0
        );
        $question->setMultiselect(true);
        $classnames = $helper->ask($input, $output, $question);
        if (in_array(static::ALL_CLASSES, $classnames)) $classnames = array_keys($all_classnames);
        $lines = [];
        $total = 0;
        $to_check = [];
        foreach ($classnames as $classname) {
            $to_check[$classname] = $this->wireEm->getEntitiesCount($classname);
            $start = $to_check[$classname] > 0 ? '' : '<fg=gray>';
            $end = $to_check[$classname] > 0 ? '' : '</>';
            $total += $to_check[$classname];
            $lines[] = [
                $start.$classname.$end,
                $start.Objects::getShortname($classname).$end,
                $start.$to_check[$classname].$end,
            ];
        }
        if($total === 0) {
            $io->warning('Aucune entité à controler');
            return Command::INVALID;
        }
        $io->writeln('<info>Entités à contrôler :</info>');
        $io->table(['Classname', 'Shortname', 'Count'], $lines);

        foreach ($to_check as $classname => $count) {
            if($count <= 0) continue;
            $io->writeln('<info>Contrôle de '.$count.' x '.$classname.'</info>');
            $opresult = $this->wireEm->checkDatabase($classname, null, true);
            $this->printMessages($opresult, $io);
        }

        $io->info('Contrôle terminé');
        return Command::SUCCESS;
    }

}