<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CategoryBundle\Entity\Category;

/**
 * Class CategoryEvent
 *
 * @package Mautic\CategoryBundle\Event
 */
class CategoryEvent extends CommonEvent
{
    /**
     * @param Category $category
     * @param bool $isNew
     */
    public function __construct(Category &$category, $isNew = false)
    {
        $this->entity  =& $category;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Category entity
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->entity;
    }

    /**
     * Sets the Category entity
     *
     * @param Category $category
     */
    public function setCategory(Category $category)
    {
        $this->entity = $category;
    }
}