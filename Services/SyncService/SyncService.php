<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Services\SyncService;

use MauticPlugin\IntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncProcess\SyncProcessFactoryInterface;

/**
 * Class SyncService
 */
final class SyncService implements SyncServiceInterface
{
    /**
     * @var SyncJudgeInterface
     */
    private $syncJudge;

    /**
     * @var SyncProcessFactoryInterface
     */
    private $integrationSyncProcessFactory;

    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * SyncService constructor.
     *
     * @param SyncJudgeInterface          $syncJudge
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncDateHelper              $syncDateHelper
     * @param MauticSyncDataExchange      $internalSyncDataExchange
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncDateHelper $syncDateHelper,
        MauticSyncDataExchange $internalSyncDataExchange
    ) {
        $this->syncJudge                     = $syncJudge;
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->syncDateHelper                = $syncDateHelper;
        $this->internalSyncDataExchange      = $internalSyncDataExchange;
    }

    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param MappingManualDAO          $integrationMappingManual
     * @param \DateTimeInterface|null   $syncFromDateTime
     */
    public function processIntegrationSync(
        SyncDataExchangeInterface $syncDataExchangeService,
        MappingManualDAO $integrationMappingManual,
        \DateTimeInterface $syncFromDateTime = null
    ) {
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $this->syncJudge,
            $integrationMappingManual,
            $this->internalSyncDataExchange,
            $syncDataExchangeService,
            $this->syncDateHelper,
            $syncFromDateTime
        );
        $integrationSyncProcess->execute();
    }
}
