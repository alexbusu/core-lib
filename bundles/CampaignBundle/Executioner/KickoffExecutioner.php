<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContacts;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFound;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFound;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class KickoffExecutioner
{
    /**
     * @var null|int
     */
    private $contactId;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var int
     */
    private $batchLimit = 100;

    /**
     * @var int|null
     */
    private $maxEventsToExecute;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KickoffContacts
     */
    private $kickoffContacts;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventExecutioner
     */
    private $executioner;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var int
     */
    private $batchCounter = 0;

    /**
     * @var ArrayCollection
     */
    private $rootEvents;

    /**
     * KickoffExecutioner constructor.
     *
     * @param LoggerInterface     $logger
     * @param KickoffContacts     $kickoffContacts
     * @param TranslatorInterface $translator
     * @param EventExecutioner    $executioner
     * @param EventScheduler      $scheduler
     */
    public function __construct(
        LoggerInterface $logger,
        KickoffContacts $kickoffContacts,
        TranslatorInterface $translator,
        EventExecutioner $executioner,
        EventScheduler $scheduler
    ) {
        $this->logger          = $logger;
        $this->kickoffContacts = $kickoffContacts;
        $this->translator      = $translator;
        $this->executioner     = $executioner;
        $this->scheduler       = $scheduler;
    }

    /**
     * @param Campaign             $campaign
     * @param int                  $batchLimit
     * @param OutputInterface|null $output
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws NotSchedulableException
     */
    public function executeForCampaign(Campaign $campaign, $batchLimit = 100, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->contactId  = null;
        $this->batchLimit = $batchLimit;
        $this->output     = ($output) ? $output : new NullOutput();

        $this->execute();
    }

    /**
     * @param Campaign             $campaign
     * @param                      $contactId
     * @param OutputInterface|null $output
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws NotSchedulableException
     */
    public function executeForContact(Campaign $campaign, $contactId, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->contactId  = $contactId;
        $this->output     = ($output) ? $output : new NullOutput();
        $this->batchLimit = null;

        $this->execute();
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws NotSchedulableException
     */
    private function execute()
    {
        try {
            $this->prepareForExecution();
            $this->executeOrScheduleEvent();
        } catch (NoContactsFound $exception) {
            $this->logger->debug('CAMPAIGN: No more contacts to process');
        } catch (NoEventsFound $exception) {
            $this->logger->debug('CAMPAIGN: No events to process');
        } finally {
            if ($this->progressBar) {
                $this->progressBar->finish();
                $this->output->writeln("\n");
            }
        }
    }

    /**
     * @throws NoEventsFound
     */
    private function prepareForExecution()
    {
        $this->logger->debug('CAMPAIGN: Triggering kickoff events');

        $this->batchCounter = 0;

        $this->rootEvents = $this->campaign->getRootEvents();
        $totalRootEvents  = $this->rootEvents->count();
        $this->logger->debug('CAMPAIGN: Processing the following events: '.implode(', ', $this->rootEvents->getKeys()));

        $totalContacts      = $this->kickoffContacts->getContactCount($this->campaign->getId(), $this->rootEvents->getKeys(), $this->contactId);
        $totalKickoffEvents = $totalRootEvents * $totalContacts;

        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.event_count',
                [
                    '%events%' => $totalKickoffEvents,
                    '%batch%'  => $this->batchLimit,
                ]
            )
        );

        $this->progressBar = ProgressBarHelper::init($this->output, $totalKickoffEvents);
        $this->progressBar->start();

        if (!$totalKickoffEvents) {
            throw new NoEventsFound();
        }
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws NoContactsFound
     * @throws NotSchedulableException
     */
    private function executeOrScheduleEvent()
    {
        // Use the same timestamp across all contacts processed
        $now = new \DateTime();

        // Loop over contacts until the entire campaign is executed
        $contacts = $this->kickoffContacts->getContacts($this->campaign->getId(), $this->batchLimit, $this->contactId);
        while ($contacts->count()) {
            /** @var Event $event */
            foreach ($this->rootEvents as $event) {
                $this->progressBar->advance($contacts->count());

                // Check if the event should be scheduled (let the schedulers do the debug logging)
                $executionDate = $this->scheduler->getExecutionDateTime($event, $now);
                $this->logger->debug(
                    'CAMPAIGN: Event ID# '.$event->getId().
                    ' to be executed on '.$executionDate->format('Y-m-d H:i:s').
                    ' compared to '.$now->format('Y-m-d H:i:s')
                );

                if ($executionDate > $now) {
                    $this->scheduler->schedule($event, $executionDate, $contacts);
                    continue;
                }

                // Execute the event for the batch of contacts
                $this->executioner->executeForContacts($event, $contacts);
            }

            $this->kickoffContacts->clear();

            // Get the next batch
            $contacts = $this->kickoffContacts->getContacts($this->campaign->getId(), $this->batchLimit, $this->contactId);
        }
    }
}
