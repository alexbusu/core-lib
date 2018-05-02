<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContacts;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;

class KickoffExecutionerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|KickoffContacts
     */
    private $kickoffContacts;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Translator
     */
    private $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventExecutioner
     */
    private $executioner;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventScheduler
     */
    private $scheduler;

    protected function setUp()
    {
        $this->kickoffContacts = $this->getMockBuilder(KickoffContacts::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executioner = $this->getMockBuilder(EventExecutioner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoContactsResultInEmptyResults()
    {
        $this->kickoffContacts->expects($this->once())
            ->method('getContactCount')
            ->willReturn(0);

        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getRootEvents')
            ->willReturn(new ArrayCollection());

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $counter = $this->getExecutioner()->execute($campaign, $limiter);

        $this->assertEquals(0, $counter->getTotalEvaluated());
    }

    public function testEventsAreScheduledAndExecuted()
    {
        $this->kickoffContacts->expects($this->once())
            ->method('getContactCount')
            ->willReturn(2);

        $this->kickoffContacts->expects($this->exactly(3))
            ->method('getContacts')
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([3 => new Lead()]),
                new ArrayCollection([10 => new Lead()]),
                new ArrayCollection([])
            );

        $event    = new Event();
        $event2   = new Event();
        $campaign = $this->getMockBuilder(Campaign::class)
            ->getMock();
        $campaign->expects($this->once())
            ->method('getRootEvents')
            ->willReturn(new ArrayCollection([$event, $event2]));
        $event->setCampaign($campaign);
        $event2->setCampaign($campaign);

        $limiter = new ContactLimiter(0, 0, 0, 0);

        $this->scheduler->expects($this->exactly(4))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        // Schedule one event and execute the other
        $this->scheduler->expects($this->exactly(4))
            ->method('shouldSchedule')
            ->willReturnOnConsecutiveCalls(true, true, false, false);

        $this->scheduler->expects($this->exactly(2))
            ->method('schedule');

        $this->executioner->expects($this->exactly(2))
            ->method('executeForContacts');

        $counter = $this->getExecutioner()->execute($campaign, $limiter);

        $this->assertEquals(4, $counter->getTotalEvaluated());
        $this->assertEquals(2, $counter->getTotalScheduled());
    }

    /**
     * @return KickoffExecutioner
     */
    private function getExecutioner()
    {
        return new KickoffExecutioner(
            new NullLogger(),
            $this->kickoffContacts,
            $this->translator,
            $this->executioner,
            $this->scheduler
        );
    }
}
