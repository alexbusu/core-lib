<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\EventDispatcher;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;

class Condition implements EventInterface
{
    const TYPE = 'condition';

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Action constructor.
     *
     * @param EventLogger $eventLogger
     */
    public function __construct(EventLogger $eventLogger, EventDispatcher $dispatcher)
    {
        $this->eventLogger = $eventLogger;
        $this->dispatcher  = $dispatcher;
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $contacts
     *
     * @return mixed|void
     */
    public function executeForContacts(AbstractEventAccessor $config, Event $event, ArrayCollection $contacts)
    {
    }

    public function executeLogs(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
    }
}
