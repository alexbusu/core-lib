<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

//Custom form widgets
$container->setDefinition('mautic.form.type.page', new Definition(
    'Mautic\PageBundle\Form\Type\PageType',
    array(
        new Reference('mautic.factory')
    )
))
    ->addTag('form.type', array(
        'alias' => 'page',
    ));

$container->setDefinition('mautic.form.type.pagevariant', new Definition(
    'Mautic\PageBundle\Form\Type\VariantType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'pagevariant',
    ));

$container->setDefinition('mautic.form.type.pointaction_pointhit', new Definition(
    'Mautic\PageBundle\Form\Type\PointActionPageHitType'))
    ->addTag('form.type', array(
        'alias' => 'pointaction_pagehit',
    ));

$container->setDefinition('mautic.form.type.pagehit.campaign_trigger', new Definition(
    'Mautic\PageBundle\Form\Type\CampaignEventPageHitType'))
    ->addTag('form.type', array(
        'alias' => 'campaignevent_pagehit',
    ));

$container->setDefinition('mautic.form.type.pagelist', new Definition(
    'Mautic\PageBundle\Form\Type\PageListType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'page_list',
    ));