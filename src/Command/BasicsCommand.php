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
    name: 'app:basics',
    description: 'Génère des entités d\'après des fichiers de description',
)]
class BasicsCommand extends BaseCommand
{
    public const DEFAULT_DATA_PATH = '/src/DataBasics/data/';
    protected const ALL_CLASSES = 'Toute les classes';

    // public readonly string $path;

    public function __construct(
        protected NormalizerServiceInterface $hydrator,
        protected WireEntityManagerInterface $wireEm,
        // protected AppWireServiceInterface $appWire,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // $this->path = static::DEFAULT_DATA_PATH;
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

        $io->title('Génération d\'entités à partir de fichiers de données YAML');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $paths = [static::DEFAULT_DATA_PATH];
        $question = new Question(vsprintf('Indiquez le chemin vers les données (default: %s) :', [static::DEFAULT_DATA_PATH]), static::DEFAULT_DATA_PATH);
        $question->setAutocompleterValues($paths);
        $path = $helper->ask($input, $output, $question);
        $io->writeln(vsprintf('- Chemin vers les données de génération : %s', [$this->wireEm->appWire->getProjectDir($path)]));
        /** Get YAML files data */
        $data = $this->hydrator->getPathYamlData($path);
        if (false === $data) {
            $io->error(vsprintf('Le chemin de répertoire %s est invalide', [$path]));
            return Command::FAILURE;
        } else if (empty($data)) {
            $io->warning(vsprintf('Aucun fichier de données n\'a été trouvé dans le répertoire %s', [$path]));
            return Command::INVALID;
        }
        $io->writeln(vsprintf('<info>- Fichiers de données trouvés : %d</info>', [count($data)]));

        // Print results
        $classnames = $this->wireEm->getEntityNames(false, false, true);
        $entities_data = [];
        $lines = [];
        foreach ($data as $filename => $d) {
            $count = count($d['items'] ?? []);
            $available = $count > 0 && class_exists($d['entity']) && in_array($d['entity'], $classnames) && ($d['enabled'] ?? true);
            if ($available) $entities_data[$d['entity']] = $d;
            $start = $available ? '' : '<fg=gray>';
            $end = $available ? '' : '</>';
            $lines[] = [
                $start.($d['order'] ?? '?').$end,
                $start.$filename.$end,
                $start.$d['entity'].$end,
                $start.(class_exists($d['entity']) ? Objects::getShortname($d['entity']) : '???').$end,
                $start.$count.$end,
                $available ? '<fg=green>Oui</>' : $start.'Non'.$end,
                $d['enabled'] ?? true ? '<fg=green>Oui</>' : '<fg=red>Non</>',
            ];
        }
        $io->table(
            ['Ord', 'File', 'Classname', 'Name', 'Count', 'Available', 'Enabled'],
            $lines
        );

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        // $allClassnames = array_filter(
        //     $this->wireEm->getEntityNames(false, false, true),
        //     fn($classname) => preg_match('/^App\\\\Entity\\\\/', $classname)
        // );
        $question = new ChoiceQuestion(
            question: 'Choisissez une ou plusieurs classes d\'entités à générer :',
            choices: array_merge([0 => static::ALL_CLASSES], array_keys($entities_data)),
            default: 0
        );
        $question->setMultiselect(true);
        $classnames = $helper->ask($input, $output, $question);
        if (in_array(static::ALL_CLASSES, $classnames)) $classnames = array_keys($entities_data);
        // Filter data
        $entities_data = array_filter($entities_data, fn($d) => in_array($d['entity'], $classnames));
        $lines = [];
        foreach ($entities_data as $class => $values) {
            $lines[] = [
                $values['order'],
                $class,
                Objects::getShortname($class),
                count($values['items']),
            ];
        }
        $io->writeln('<info>Entités à générer :</info>');
        $io->table(['Ord', 'Classname', 'Shortname', 'Count'], $lines);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Remplace si existante (oui/non) (défaut: oui) ? ', true, '/^(o|oui|y|yes)/i');
        $replace = $helper->ask($input, $output, $question);
        $io->writeln(vsprintf('- Remplace : %s', [$replace ? 'Oui' : 'Non']));
        if ($replace) {
            $io->note('Remplace si existant.');
            // sleep(1);
        }

        foreach ($entities_data as $class => $data) {
            $opresult = $this->hydrator->generateEntities(
                $class,
                $data['items'],
                $replace,
                $io,
                true
            );
            if ($opresult->isSuccess()) {
                // $io->success(vsprintf('Entités générées pour %s', [$class]));
            } else {
                $io->error(vsprintf('Erreur(s) lors de la génération des entités pour %s', [$class]));
                // dd($opresult->getMessages());
                $this->printMessages($opresult, $io);
                $io->warning('Process aborted.');
                return Command::FAILURE;
            }
            try {
                // $this->wireEm->getEm()->flush();
                $io->success(vsprintf('Entités générées pour %s: %d', [$class, count($opresult->getData())]));
            } catch (\Throwable $th) {
                $io->error(vsprintf('Erreur lors de l\'enregistrement des entités pour %s%s%s', [$class, PHP_EOL, $th->getMessage()]));
                $io->warning('Process aborted.');
                // dd($opresult->getData());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

}
