<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<div class="trigger-event-row <?php echo $containerClass; ?>" id="triggerEvent<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticPointBundle:TriggerBuilder:actions.html.php', array(
            'deleted'  => (!empty($deleted)) ? $deleted : false,
            'id'       => $id,
            'route'   => 'mautic_pointtriggerevent_action'
        ));
    ?>
    <span class="trigger-event-label"><?php echo $action['name']; ?></span>
    <?php if (!empty($action['description'])): ?>
    <span class="trigger-event-descr"><?php echo $action['description']; ?></span>
    <?php endif; ?>
</div>