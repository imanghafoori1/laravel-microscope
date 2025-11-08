<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use ImanGhafoori\ComposerJson\ClassLists;
use ImanGhafoori\ComposerJson\ComposerJson as Comp;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\ReportMessages;
use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\TypeStatistics;

class CheckStatsCommand extends Command
{
    protected $signature = 'check:stats';

    protected $description = 'Get statistics of your laravel application.';

    public function handle()
    {
        $this->info('Your Laravel app consists of:');
        $time = microtime(true);
        $printer = ErrorPrinter::singleton($this->output);

        $composer = ComposerJson::make();
        $classLists = $this->getClassLists($composer);

        $types = $this->getTypes();
        $stats = $this->prepareStats($types);

        $events = resolve('events')->getRawListeners();
        foreach ($classLists->getAllLists() as $list) {
            foreach ($list as $entities) {
                foreach ($entities as $entity) {
                    /**
                     * @var \ImanGhafoori\ComposerJson\Entity $entity
                     */
                    $namespace = $entity->getClassDefinition()->getNamespace();
                    $class1 = $namespace.'\\'.$entity->getEntityName();

                    foreach ($types as $type => $id) {
                        is_subclass_of($class1, $type) && $stats[$id]['counts']++ && $stats[$id]['namespaces'][$namespace] = null;
                    }
                    if (isset($events[$class1])) {
                        $stats['Events']['counts']++;
                    }
                }
            }
        }

        foreach ($stats as $id => $int) {
            $this->info(' <fg=white> - </><fg=yellow>'.$int['counts'].' '.$id.'</> found.');
            //foreach ($int['namespaces'] as $namespace => $_) {
            //    $this->warn('       '.$namespace);
            //}
        }

        [$liteners, $eventsCount] = $this->getListenersCount($events);

        $this->info(' - <fg=yellow>'.$liteners.' listeners</> are listening to <fg=yellow>'.$eventsCount.' events</>.');
        $this->line('');

        $duration = round(microtime(true) - $time, 5);
        $this->printReport($printer, $duration, $composer->readAutoload(), $classLists);
    }

    private function printReport(ErrorPrinter $errorPrinter, $duration, $autoload, ClassLists $classLists)
    {
        $classListStatistics = self::countClasses($classLists);

        $this->write(ReportMessages::reportResult($autoload, $duration, $classListStatistics));
        $this->printMessages(ReportMessages::getErrorsCount($errorPrinter->total));
    }

    private function printMessages($messages)
    {
        foreach ($messages as [$message, $level]) {
            $this->$level($message);
        }
    }

    private static function countClasses(ClassLists $classLists)
    {
        $type = new TypeStatistics();

        foreach ($classLists->getAllLists() as $composerPath => $classList) {
            foreach ($classList as $namespace => $entities) {
                $type->namespaceFiles($namespace, count($entities));
                foreach ($entities as $entity) {
                    $type->increment($entity->getType());
                }
            }
        }

        return $type;
    }

    private function getClassLists(Comp $composer): ClassLists
    {
        return $composer->getClasslists(null, null);
    }

    private function write($text): void
    {
        $this->getOutput()->writeln($text);
    }

    private function getTypes(): array
    {
        return [
            '\Illuminate\Routing\Controller' => 'Controllers',
            '\Illuminate\Database\Eloquent\Model' => 'Models',
            '\Illuminate\Foundation\Http\FormRequest' => 'FormRequests',
            '\Illuminate\Http\Resources\Json\JsonResource' => 'JsonResources',
            '\Illuminate\Console\Command' => 'Commands',
            '\Illuminate\Notifications\Notification' => 'Notifications',
            '\Illuminate\Support\ServiceProvider' => 'ServiceProviders',
            '\Exception' => 'Exceptions',
            '\Illuminate\Contracts\Broadcasting\ShouldBroadcastNow' => 'Broadcasted Events',
            '\Illuminate\Contracts\Queue\ShouldQueue' => 'Queued Jobs',
            '\Illuminate\Database\Eloquent\Scope' => 'Eloquent Scopes',
            '\Illuminate\Database\Eloquent\Factories\Factory' => 'Factories',
            '\Illuminate\Database\Seeder' => 'Seeders',
        ];
    }

    private function getListenersCount(array $events): array
    {
        $liteners = 0;
        $eventsCount = 0;
        foreach ($events as $event => $listeners) {
            $eventsCount++;
            $liteners += count($listeners);
        }

        return [$liteners, $eventsCount];
    }

    private function prepareStats(array $types): array
    {
        $stats = [
            'Events' => [
                'counts' => 0,
                'namespaces' => [],
            ],
        ];

        foreach ($types as $id) {
            $stats[$id]['counts'] = 0;
            $stats[$id]['namespaces'] = [];
        }

        return $stats;
    }
}
