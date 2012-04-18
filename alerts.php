#!/usr/bin/env php
<?php

/**
 * Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage alerts
 * @author     Adam Armstrong <adama@memetic.org>
 * @copyright  (C) 2006 - 2012 Adam Armstrong
 * @license    http://gnu.org/copyleft/gpl.html GNU GPL
 *
 */

include("includes/defaults.inc.php");
include("config.php");
include("includes/functions.php");

foreach (dbFetchRows("SELECT *, A.id AS id FROM `alerts` AS A, `devices` AS D WHERE A.device_id = D.device_id AND alerted = '0'") as $alert)
{
  $id = $alert['id'];
  $host = $alert['hostname'];
  $date = $alert['time_logged'];
  $msg = $alert['message'];
  $alert_text .= "$date $host $msg";

  dbUpdate(array('alerted' => '1'), 'alerts', '`id` = ?' array($id))

}

if ($alert_text)
{
  echo("$alert_text");
  #  `echo '$alert_text' | gnokii --sendsms <NUMBER>`;
}

?>
