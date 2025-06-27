<?php
namespace Aequation\WireBundle\Command;

use Aequation\WireBundle\Component\interface\OpresultInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{

    protected function printMessages(
        OpresultInterface $opresult,
        SymfonyStyle $io,
        null|string|array $message_types = null
    ): void
    {
        $message_types = (array) $message_types;
        foreach ($opresult->getMessages() as $type => $messages) {
            if(empty($message_types) || in_array($type, $message_types)) {
                $message_type = [];
                foreach ($messages as $message) {
                    $message_type[] = $message;
                }
                if(!empty($message_type)) {
                    switch ($type) {
                        case 'dev':
                        case 'danger':
                            $io->error('- '.implode(PHP_EOL.'- ', $message_type));
                            break;
                        case 'undone':
                        case 'warning':
                            $io->warning('- '.implode(PHP_EOL.'- ', $message_type));
                            break;
                        case 'success':
                            $io->success('- '.implode(PHP_EOL.'- ', $message_type));
                            break;
                        default:
                            $io->info('- '.implode(PHP_EOL.'- ', $message_type));
                            break;
                    }
                }
            }
        }
    }


}