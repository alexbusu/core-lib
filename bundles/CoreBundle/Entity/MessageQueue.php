<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class MessageQueue
 *
 * @package Mautic\CoreBundle\Entity
 */
class MessageQueue
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var
     */
    private $channelId;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var int
     */
    private $priority = 2;

    /**
     * @var int
     */
    private $maxAttempts = 3;

    /**
     * @var int
     */
    private $attempts = 0;

    /**
     * @var bool
     */
    private $success = false;

    /**
     * @var string
     */
    private $status = 'pending';

    /**
     * @var \DateTime
     **/
    private $datePublished;

    /**
     * @var null|\DateTime
     */
    private $scheduledDate;

    /**
     * @var null|\DateTime
     */
    private $lastAttempt;

    /**
     * @var null|\DateTime
     */
    private $dateSent;

    /**
     * @var array()
     */
    private $options = [];

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createField('id', 'integer')
            ->isPrimaryKey()
            ->generatedValue()
            ->build();

        $builder->setTable('message_queue')
            ->setCustomRepositoryClass('Mautic\CoreBundle\Entity\MessageQueueRepository')
            ->addIndex(['status'], 'message_status_search')
            ->addIndex(['date_sent'], 'message_date_sent')
            ->addIndex(['scheduled_date'], 'message_scheduled_date')
            ->addIndex(['priority'], 'message_priority')
            ->addIndex(['success'], 'message_success')
            ->addIndex(['channel', 'channel_id'], 'message_channel_search');

        $builder->addField('channel', 'string');
        $builder->addNamedField('channelId', 'integer', 'channel_id');

        $builder->createManyToOne('event', 'Mautic\CampaignBundle\Entity\Event')
            ->addJoinColumn('event_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', false);

        $builder->createField('priority', 'smallint')
            ->columnName('priority')
            ->build();

        $builder->createField('maxAttempts', 'smallint')
            ->columnName('max_attempts')
            ->build();

        $builder->createField('attempts', 'smallint')
            ->columnName('attempts')
            ->build();

        $builder->createField('success', 'boolean')
            ->columnName('success')
            ->build();

        $builder->createField('status', 'string')
            ->columnName('status')
            ->build();

        $builder->createField('datePublished', 'datetime')
            ->columnName('date_published')
            ->nullable()
            ->build();

        $builder->createField('scheduledDate', 'datetime')
            ->columnName('scheduled_date')
            ->nullable()
            ->build();

        $builder->createField('lastAttempt', 'datetime')
            ->columnName('last_attempt')
            ->nullable()
            ->build();

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->nullable()
            ->build();

        $builder->createField('options', 'array')
            ->nullable()
            ->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getAttempts ()
    {
        return $this->attempts;
    }

    /**
     * @param integer $attempts
     */
    public function setAttempts ($attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * @return array
     */
    public function getOptions ()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions ($options)
    {
        $this->options[] = $options;
    }

    /**
     * @return string
     */
    public function getChannel ()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel ($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param mixed $channelId
     *
     * @return MessageQueue
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param Event $event
     *
     * @return MessageQueue
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDatePublished ()
    {
        return $this->datePublished;
    }

    /**
     * @param \DateTime $datePublished
     */
    public function setDatePublished ($datePublished)
    {
        $this->datePublished = $datePublished;
    }

    /**
     * @return \DateTime
     */
    public function getDateSent ()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime $dateSent
     */
    public function setDateSent ($dateSent)
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return \DateTime
     */
    public function getLastAttempt ()
    {
        return $this->lastAttempt;
    }

    /**
     * @param \DateTime $lastAttempt
     */
    public function setLastAttempt ($lastAttempt)
    {
        $this->lastAttempt = $lastAttempt;
    }
    /**
     * @return Lead
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     */
    public function setLead (Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return integer
     */
    public function getMaxAttempts ()
    {
        return $this->maxAttempts;
    }

    /**
     * @param integer $maxAttempts
     */
    public function setMaxAttempts ($maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @return integer
     */
    public function getPriority ()
    {
        return $this->priority;
    }

    /**
     * @param integer $priority
     */
    public function setPriority ($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return mixed
     */
    public function getScheduledDate ()
    {
        return $this->scheduledDate;
    }

    /**
     * @param mixed $scheduledDate
     */
    public function setScheduledDate($scheduledDate)
    {
        $this->scheduledDate = $scheduledDate;
    }

    /**
     * @return string
     */
    public function getStatus ()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus ($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function getSuccess ()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess ($success)
    {
        $this->success = $success;
    }
}