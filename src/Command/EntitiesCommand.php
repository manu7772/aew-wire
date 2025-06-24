<?php

namespace Aequation\WireBundle\Command;

// Aequation

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Aequation\WireBundle\Service\interface\NormalizerServiceInterface;
use Aequation\WireBundle\Service\interface\WireEntityManagerInterface;
use Aequation\WireBundle\Tools\Objects;
// Symfony
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'aew:dump-entities',
    description: 'Liste les entités de l\'application',
    aliases: ['aew:de'],
)]
class EntitiesCommand extends BaseCommand
{
    // public readonly string $path;

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

        $io->title('Liste des entités de l\'application');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Résultats shortnames ? (oui/non) (défaut: oui) ? ', true, '/^(o|oui|y|yes)/i');
        $shortname = $helper->ask($input, $output, $question);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $listes = [
            0 => 'All lists (classname => '.($shortname ? 'shortname' : 'classname').')',
            1 => 'All (classname => '.($shortname ? 'shortname' : 'classname').')',
            2 => 'All instantiables (classname => '.($shortname ? 'shortname' : 'classname').')',
            3 => 'App Wire (classname => '.($shortname ? 'shortname' : 'classname').')',
            4 => 'App Wire instantiables (classname => '.($shortname ? 'shortname' : 'classname').')',
            5 => 'Between (classname => '.($shortname ? 'shortname' : 'classname').')',
            6 => 'Translation (classname => '.($shortname ? 'shortname' : 'classname').')',
            7 => 'All final (classname => '.($shortname ? 'shortname' : 'classname').')',
            8 => 'App Wire final (classname => '.($shortname ? 'shortname' : 'classname').')',
        ];
        $question = new ChoiceQuestion(
            question: 'Choisissez un type de liste :',
            choices: $listes,
            default: 0
        );
        $question->setMultiselect(true);
        $types = $helper->ask($input, $output, $question);
        if(in_array($listes[0], $types)) {
            $types = $listes;
        }
        // RESULTS
        foreach ($types as $type) {
            $lines = [];
            switch ($type) {
                case 'All (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getEntityNames($shortname, true, false);
                    $io->writeln('<info>All entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'All instantiables (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getEntityNames($shortname, true, true);
                    $io->writeln('<info>All instantiables entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'App Wire (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getAppEntityNames($shortname, false);
                    $io->writeln('<info>App Wire entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'App Wire instantiables (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getAppEntityNames($shortname, true);
                    $io->writeln('<info>App Wire instantiables entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'Between (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getBetweenEntityNames($shortname);
                    $io->writeln('<info>Between entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'Translation (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getTranslationEntityNames($shortname);
                    $io->writeln('<info>Translation entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'All final (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getFinalEntities($shortname, true);
                    $io->writeln('<info>All final entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                case 'App Wire final (classname => '.($shortname ? 'shortname' : 'classname').')':
                    $entities_list = $this->wireEm->getFinalEntities($shortname, false);
                    $io->writeln('<info>App Wire final entities '.($shortname ? 'shortnames' : 'classnames').' (found '.count($entities_list).') :</info>');
                    break;
                default:
                    $io->error(vsprintf('Option "%s" not recognized.', [$type]));
                    break;
            }
            if(isset($entities_list)) {
                $lines = [];
                foreach ($entities_list as $classname => $value) {
                    $rc = new \ReflectionClass($classname);
                    $lines[] = [
                        $classname,
                        $value,
                        $this->wireEm->isAppWireEntity($classname) ? '<fg=green>Oui</>' : '<fg=red>Non</>',
                        $this->wireEm->isBetweenEntity($classname) ? '<fg=green>Oui</>' : '<fg=red>Non</>',
                        $this->wireEm->isTranslationEntity($classname) ? '<fg=green>Oui</>' : '<fg=red>Non</>',
                        $rc->isInstantiable() ? '<fg=green>Oui</>' : '<fg=red>Non</>',
                    ];
                }
                $io->table(['Classname', $shortname ? 'Shortname' : 'Classname', 'AppWire', 'Between','Translation','Instantiable'], $lines);
            }
        }

        return Command::SUCCESS;
    }

}
