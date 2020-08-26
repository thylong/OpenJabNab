<?php
require_once "../include/common.php";
$ojnTemplate->setTitle(__tr('How to setup my bunny ?'));
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-list-alt"></i> <?php echo __tr("How to setup my bunny ?") ?>
  </h5>
  <table class="card-body table table-striped">
    <tr>
      <td class="col-sm-1"><?php echo __tr('Step %1', 1) ?></td>
      <td>
        <b><?php echo __tr('Put the bunny in adhoc mode') ?></b><br />
        <?php echo __tr('The adhoc mode is when the bunny is lighted on blue. To launch this mode') ?> :
        <ul>
        <li><?php echo __tr('Unplug the power adapter') ?></li>
        <li><?php echo __tr('Press the button on the head, and stay pressed') ?></li>
        <li><?php echo __tr('Plug the power adapter, and release the button as soon as the bunny is blue') ?></li>
        </ul>
      </td>
    </tr>
    <tr>
      <td><?php echo __tr('Step %1', 2) ?></td>
      <td>
        <b><?php echo __tr('Connect to adhoc wifi') ?></b><br />
        <?php echo __tr('In adhoc mode, your bunny produce an adhoc wifi network') ?>.
        <?php echo __tr('The name of the network created is "NabaztagXX", where XX are the last two caracters of the MAC address') ?>.<br />
        <i><?php echo __tr('For example, if the MAC address of the bunny is 00123456789, the wifi is going to be named "Nabaztag89"') ?>.</i><br />
        <?php echo __tr('Connect your PC (or any wifi capable device such as smartphone, tablet, etc) to this wifi network') ?>.<br />
        <i><?php echo __tr('You may temporary loose your internet connection during the setup') ?></i>
      </td>
    </tr>
    <tr>
      <td><?php echo __tr('Step %1', 3) ?></td>
      <td>
        <b><?php echo __tr('Access the configuration page') ?></b><br />
        <?php echo __tr('In your browser, go to the url') ?> : <a target="_blank" href="http://192.168.0.1/">http://192.168.0.1/</a>
      </td>
    </tr>
    <tr>
      <td><?php echo __tr('Step %1', 4) ?></td>
      <td>
        <b><?php echo __tr('If your bunny already have a working wifi connection') ?></b><br />
        <?php echo __tr("Goto step %1", 5) ?><br />
        <b><?php echo __tr('If your bunny doesn\'t have a working wifi connection') ?></b><br />
        <?php echo __tr("Setup the form according to your wifi access point setup") ?>
      </td>
    </tr>
    <tr>
      <td><?php echo __tr('Step %1', 5) ?></td>
      <td>
        <b><?php echo __tr('Find the field to change') ?></b><br />
        <?php echo __tr("Click on the 'Click here to Start' link.") ?><br />
        <?php echo __tr("Click on the 'Advanced configuration' link (on the right of the page).") ?><br />
        <?php echo __tr("Scroll down to the 'General Info' frame") ?>
      </td>
    </tr>
    <tr>
      <td><?php echo __tr('Step %1', 6) ?></td>
      <td>
        <b><?php echo __tr('Change the server and restart') ?></b><br />
        <?php echo __tr("Change the field 'Violet Platform' with ") ?> <b>openjabnab.fr/vl</b><br />
        <br />
        <i><?php echo __tr("Click on the 'Update and Start' button") ?></i><br />
      </td>
    </tr>
  </table>
</div>
<?php
require_once "../include/append.php";
?>
