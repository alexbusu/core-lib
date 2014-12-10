<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class EventGeneratorEvent
 */
class EventGeneratorEvent extends Event
{

    /**
     * @var string
     */
    private $source;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var integer
     */
    private $entityId;

    /**
     * @var \Mautic\CoreBundle\Model\FormModel
     */
    private $model;

    /**
     * @var \Mautic\CoreBundle\Entity\FormEntity
     */
    private $entity;

    /**
     * @var string
     */
    private $contentTemplate;

    /**
     * @param string    $source
     * @param \DateTime $startDate
     * @param integer   $id
     */
    public function __construct($source, \DateTime $startDate, $entityId)
    {
        $this->source    = $source;
        $this->startDate = $startDate;
        $this->entityId  = $entityId;
    }

    /**
     * Set content template
     *
     * @param string $contentTemplate
     *
     * @return void
     */
    public function setContentTemplate($contentTemplate)
    {
        $this->contentTemplate = $contentTemplate;
    }

    /**
     * Fetches the event start date
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Fetches the event source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Fetches the event entityId
     *
     * @return integer
     */
    public function getEntityId()
    {
        return (int) $this->entityId;
    }

    /**
     * Fetches the event model
     *
     * @return \Mautic\CoreBundle\Model\FormModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the event model
     */
    public function setModel(\Mautic\CoreBundle\Model\FormModel $model)
    {
        $this->model = $model;
    }

    /**
     * Fetches the event entity
     *
     * @return \Mautic\CoreBundle\Entity\FormEntity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the event entity
     */
    public function setEntity(\Mautic\CoreBundle\Entity\FormEntity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Fetches the events content template
     *
     * @return string
     */
    public function getContentTemplate()
    {
        return $this->contentTemplate;
    }
}
