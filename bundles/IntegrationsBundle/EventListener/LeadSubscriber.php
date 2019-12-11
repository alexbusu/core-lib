<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\IntegrationsBundle\Entity\FieldChange;
use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var FieldChangeRepository
     */
    private $fieldChangeRepo;

    /**
     * @var VariableExpresserHelperInterface
     */
    private $variableExpressor;

    /**
     * @var SyncIntegrationsHelper
     */
    private $syncIntegrationsHelper;

    /**
     * @param FieldChangeRepository            $fieldChangeRepo
     * @param VariableExpresserHelperInterface $variableExpressor
     * @param SyncIntegrationsHelper           $syncIntegrationsHelper
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepo,
        VariableExpresserHelperInterface $variableExpressor,
        SyncIntegrationsHelper $syncIntegrationsHelper
    ) {
        $this->fieldChangeRepo        = $fieldChangeRepo;
        $this->variableExpressor      = $variableExpressor;
        $this->syncIntegrationsHelper = $syncIntegrationsHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE    => ['onLeadPostDelete', 255],
            LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
            LeadEvents::COMPANY_POST_DELETE => ['onCompanyPostDelete', 255],
        ];
    }

    /**
     * @param Events\LeadEvent $event
     *
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function onLeadPostSave(Events\LeadEvent $event): void
    {
        $lead = $event->getLead();
        if ($lead->isAnonymous()) {
            // Do not track visitor changes
            return;
        }

        if (defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS')) {
            // Don't track changes just made by an active sync
            return;
        }

        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(Contact::NAME)) {
            // Only track if an integration is syncing with contacts
            return;
        }

        $changes = $lead->getChanges(true);

        if (!empty($changes['owner'])) {
            // Force record of owner change if present in changelist
            $changes['fields']['owner_id'] = $changes['owner'];
        }

        if (!empty($changes['points'])) {
            // Add ability to update points custom field in target
            $changes['fields']['points'] = $changes['points'];
        }

        if (isset($changes['fields'])) {
            $this->recordFieldChanges($changes['fields'], $lead->getId(), Lead::class);
        }

        if (isset($changes['dnc_channel_status'])) {
            $dncChanges = [];
            foreach ($changes['dnc_channel_status'] as $channel => $change) {
                $oldValue = $change['old_reason'] ?? '';
                $newValue = $change['reason'];

                $dncChanges['mautic_internal_dnc_'.$channel] = [$oldValue, $newValue];
            }

            $this->recordFieldChanges($dncChanges, $lead->getId(), Lead::class);
        }
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event): void
    {
        $this->fieldChangeRepo->deleteEntitiesForObject((int) $event->getLead()->deletedId, Lead::class);
    }

    /**
     * @param Events\CompanyEvent $event
     *
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function onCompanyPostSave(Events\CompanyEvent $event): void
    {
        if (defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS')) {
            // Don't track changes just made by an active sync
            return;
        }

        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(MauticSyncDataExchange::OBJECT_COMPANY)) {
            // Only track if an integration is syncing with companies
            return;
        }

        $company = $event->getCompany();
        $changes = $company->getChanges(true);

        if (!empty($changes['owner'])) {
            // Force record of owner change if present in changelist
            $changes['fields']['owner_id'] = $changes['owner'];
        }

        if (!isset($changes['fields'])) {
            return;
        }

        $this->recordFieldChanges($changes['fields'], $company->getId(), Company::class);
    }

    /**
     * @param Events\CompanyEvent $event
     */
    public function onCompanyPostDelete(Events\CompanyEvent $event): void
    {
        $this->fieldChangeRepo->deleteEntitiesForObject((int) $event->getCompany()->deletedId, Company::class);
    }

    /**
     * @param array  $fieldChanges
     * @param int    $objectId
     * @param string $objectType
     *
     * @throws IntegrationNotFoundException
     */
    private function recordFieldChanges(array $fieldChanges, $objectId, string $objectType): void
    {
        $toPersist     = [];
        $changedFields = [];
        $objectId      = (int) $objectId;
        foreach ($fieldChanges as $key => [$oldValue, $newValue]) {
            $valueDAO          = $this->variableExpressor->encodeVariable($newValue);
            $changedFields[]   = $key;
            $fieldChangeEntity = (new FieldChange())
                ->setObjectType($objectType)
                ->setObjectId($objectId)
                ->setModifiedAt(new \DateTime())
                ->setColumnName($key)
                ->setColumnType($valueDAO->getType())
                ->setColumnValue($valueDAO->getValue());

            foreach ($this->syncIntegrationsHelper->getEnabledIntegrations() as $integrationName) {
                $integrationFieldChangeEntity = clone $fieldChangeEntity;
                $integrationFieldChangeEntity->setIntegration($integrationName);

                $toPersist[] = $integrationFieldChangeEntity;
            }
        }

        $this->fieldChangeRepo->deleteEntitiesForObjectByColumnName($objectId, $objectType, $changedFields);
        $this->fieldChangeRepo->saveEntities($toPersist);

        $this->fieldChangeRepo->clear();
    }
}
