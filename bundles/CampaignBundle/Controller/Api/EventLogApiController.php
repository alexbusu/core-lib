<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Serializer\Exclusion\FieldInclusionStrategy;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\EventLogModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class EventLogApiController.
 */
class EventLogApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var Lead
     */
    protected $contact;

    /** @var EventLogModel */
    protected $model;

    public function initialize(FilterControllerEvent $event)
    {
        $this->model                    = $this->getModel('campaign.event_log');
        $this->entityClass              = 'Mautic\CampaignBundle\Entity\LeadEventLog';
        $this->entityNameOne            = 'event';
        $this->entityNameMulti          = 'events';
        $this->parentChildrenLevelDepth = 1;
        $this->serializerGroups         = [
            'campaignList',
            'ipAddressList',
        ];

        // Only include the id of the parent
        $this->addExclusionStrategy(new FieldInclusionStrategy(['id'], 1, 'parent'));

        parent::initialize($event);
    }

    /**
     * Get a list of events.
     *
     * @param      $contactId
     * @param null $campaignId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactEventsAction($contactId, $campaignId = null)
    {
        // Ensure contact exists and user has access
        $contact = $this->checkLeadAccess($contactId, 'view');
        if ($contact instanceof Response) {
            return $contact;
        }

        // Ensure campaign exists and user has access
        if (!empty($campaignId)) {
            $campaign = $this->getModel('campaign')->getEntity($campaignId);
            if (null == $campaign || !$campaign->getId()) {
                return $this->notFound();
            }
            if (!$this->checkEntityAccess($campaign, 'view')) {
                return $this->accessDenied();
            }
            // Check that contact is part of the campaign
            $membership = $campaign->getContactMembership($contact);
            if (count($membership) === 0) {
                return $this->returnError('mautic.campaign.error.contact_not_in_campaign', Codes::HTTP_CONFLICT);
            }

            $this->campaign           = $campaign;
            $this->serializerGroups[] = 'campaignEventWithLogsList';
            $this->serializerGroups[] = 'campaignLeadList';
            $this->serializerGroups[] = 'campaignEventLogDetails';
        } else {
            $this->serializerGroups[] = 'campaignEventStandaloneList';
            $this->serializerGroups[] = 'campaignEventStandaloneLogDetails';
        }

        $this->contact                   = $contact;
        $this->extraGetEntitiesArguments = [
            'contact_id'  => $contactId,
            'campaign_id' => $campaignId,
        ];

        return $this->getEntitiesAction();
    }

    /**
     * @param $eventId
     * @param $contactId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editContactEventAction($eventId, $contactId)
    {
        $parameters = $this->request->request->all();

        // Ensure contact exists and user has access
        $contact = $this->checkLeadAccess($contactId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }

        /** @var EventModel $eventModel */
        $eventModel = $this->getModel('campaign.event');
        /** @var Event $event */
        $event = $eventModel->getEntity($eventId);
        if (null === $event || !$event->getId()) {
            return $this->notFound();
        }

        // Ensure campaign edit access
        $campaign = $event->getCampaign();
        if (!$this->checkEntityAccess($campaign, 'edit')) {
            return $this->accessDenied();
        }

        $result = $this->model->updateContactEvent($event, $contact, $parameters);

        if (is_string($result)) {
            return $this->returnError($result, Codes::HTTP_CONFLICT);
        } else {
            list($log, $created) = $result;
        }

        $event->addContactLog($log);
        $view = $this->view(
            [
                $this->entityNameOne => $event,
            ],
            ($created) ? Codes::HTTP_CREATED : Codes::HTTP_OK
        );
        $this->serializerGroups[] = 'campaignEventWithLogsList';
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    /**
     * @param null  $data
     * @param null  $statusCode
     * @param array $headers
     *
     * @return \FOS\RestBundle\View\View
     */
    protected function view($data = null, $statusCode = null, array $headers = [])
    {
        if ($this->campaign) {
            $data['campaign'] = $this->campaign;

            if ($this->contact) {
                list($data['membership'], $ignore) = $this->prepareEntitiesForView($this->campaign->getContactMembership($this->contact));
            }
        }

        return parent::view($data, $statusCode, $headers);
    }
}
