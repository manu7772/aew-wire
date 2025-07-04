<?php
namespace Aequation\WireBundle\Command;

use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
// Symfony
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\QuestionHelper;

#[AsCommand(
    name: 'aew:dump-relations',
    description: 'Liste des relations des entités de l\'application',
    aliases: ['aew:dr'],
)]
class RelationsCommand extends BaseCommand
{
    public function __construct(
        protected WireEntityManagerInterface $wireEm,
    ) {
        parent::__construct();
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

        $io->title('Choix des entités de l\'application');

        // Your logic to list relations goes here
        $entities_list = $this->wireEm->getEntityNames(true, true, false);
        foreach ($entities_list as $classname => $shortname) {
            $liste[$shortname] = $classname;
        }
        asort($liste);
        $question = new ChoiceQuestion(
            question: 'Choisissez une entité :',
            choices: $liste,
            default: 0
        );
        $question->setMultiselect(false);
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $choice = $helper->ask($input, $output, $question);
        // preg_match('/^(.+?)\s+<fg=gray>\((.+)\)<\/>$/', $choice, $matches);
        // $io->info('Dump relations pour '.$matches[2]);
        $classname = in_array($choice, $liste) ? $choice : $liste[$choice];
        $shortname = array_search($classname, $liste);
        $io->info('Dump relations pour '.$classname.' => '.$shortname);
        // $io->writeln('Dump relations pour <options=bold;fg=bright-green>'.$matches[2].'</>');
        $ed = $this->wireEm->getEntitiesDescriptor();

        // Print entity info

        // Class informations
        $io->writeln('Class informations:');
        $infos = [];
        $infos[] = ['Class', $classname];
        $infos[] = ['Shortname', $shortname];
        $infos[] = ['Is entity', $ed->isEntity($classname) ? '<fg=green>Yes</>' : '<fg=yellow>No</>'];
        $infos[] = ['Final', $ed->isFinalEntity($classname) ? '<fg=green>Yes</>' : '<fg=yellow>No</>'];
        $infos[] = ['Abstract', $ed->isAbstract($classname) ? '<fg=yellow>Yes</>' : '<fg=green>No</>'];
        $io->table(
            ['Property', 'Value'],
            $infos
        );

        // Interfaces
        $io->writeln('Interfaces:');
        $interfaces = [];
        foreach ($ed->getInterfaces($classname) as $interface => $shortname) {
            $interfaces[] = ["<fg=blue>$interface</>", "<fg=gray>$shortname</>"];
        }
        asort($interfaces);
        $io->table(
            ['Interface', 'Shortname'],
            $interfaces
        );

        // Traits
        $io->writeln('Traits:');
        $traits = [];
        foreach ($ed->getTraits($classname) as $trait => $shortname) {
            $traits[] = ["<fg=blue>$trait</>", "<fg=gray>$shortname</>"];
        }
        asort($traits);
        $io->table(
            ['Trait', 'Shortname'],
            $traits
        );

        // Parents
        $io->writeln('Parents:');
        $parents = [];
        foreach ($ed->getParents($classname) as $parent => $shortname) {
            $parents[] = ["<fg=blue>$parent</>", "<fg=gray>$shortname</>", $ed->isEntity($parent) ? '<fg=green>Yes</>' : '<fg=yellow>No</>', $ed->isAbstract($parent) ? '<fg=yellow>Yes</>' : '<fg=green>No</>'];
        }
        // asort($parents);
        $io->table(
            ['Parent', 'Shortname', 'is entity', 'is abstract'],
            $parents
        );

        // Subclasses
        $io->writeln('Subclasses:');
        $subclasses = [];
        foreach ($ed->getSubclasses($classname) as $subclass => $shortname) {
            $subclasses[] = ["<fg=blue>$subclass</>", "<fg=gray>$shortname</>", $ed->isEntity($subclass) ? '<fg=green>Yes</>' : '<fg=yellow>No</>', $ed->isAbstract($parent) ? '<fg=yellow>Yes</>' : '<fg=green>No</>'];
        }
        // asort($subclasses);
        $io->table(
            ['Subclass', 'Shortname', 'is entity', 'is abstract'],
            $subclasses
        );

        return Command::SUCCESS;
    }
}