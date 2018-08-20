<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Command;

use DateTimeImmutable;
use MauticPlugin\IntegrationsBundle\Event\SyncCompletedEvent;
use MauticPlugin\IntegrationsBundle\Services\SyncService\SyncServiceInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MauticPlugin\IntegrationsBundle\Event\SyncEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SyncCommand extends ContainerAwareCommand
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SyncServiceInterface
     */
    private $syncService;

    /**
     * SyncCommand constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param SyncServiceInterface     $syncService
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, SyncServiceInterface $syncService)
    {
        parent::__construct();

        $this->eventDispatcher = $eventDispatcher;
        $this->syncService     = $syncService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:integrations:sync')
            ->setDescription('Fetch objects from integration.')
            ->addArgument(
                'integration',
                InputOption::VALUE_REQUIRED,
                'Fetch objects from integration.',
                null
            )
            ->addOption(
                '--start-datetime',
                '-s',
                InputOption::VALUE_REQUIRED,
                'Set start date/time for updated values.'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io                  = new SymfonyStyle($input, $output);
        $integration         = $input->getArgument('integration');
        $startDateTimeString = $input->getOption('start-datetime');
        $env                 = $input->getOption('env');

        try {
            $startDateTime = ($startDateTimeString) ? new DateTimeImmutable($startDateTimeString) : null;
        } catch (\Exception $e) {
            $io->error("'$startDateTimeString' is not valid. Use 'Y-m-d H:i:s' format like '2018-12-24 20:30:00' or something like '-10 minutes'");

            return 1;
        }

        try {
            $event = new SyncEvent($integration);
            $this->eventDispatcher->dispatch(IntegrationEvents::ON_SYNC_TRIGGERED, $event);

            $this->syncService->processIntegrationSync($event->getDataExchange(), $event->getMappingManual(), $startDateTime);
        } catch (\Exception $e) {
            if ($env === 'dev' || MAUTIC_ENV === 'dev') {
                throw $e;
            }

            $io->error($e->getMessage());

            return 1;
        }

        $io->success('Execution time: '.number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3));

        return 0;
    }
}
