<?php

global $debug;

if ($config['enable_printers'])
{
  $valid_toner = array();

  echo("Toner : ");

  if ($device['os_group'] == 'printer')
  {
    $oids = trim(snmp_walk($device, "1.3.6.1.2.1.43.11.1.1.3", "-OsqnU"));
    if (!$oids)
    {
      $oids = trim(snmp_walk($device, "1.3.6.1.2.1.43.12.1.1.2.1", "-OsqnU"));
    }
    if ($debug) { echo($oids."\n"); }
    if ($oids) echo("Jetdirect ");
    foreach (explode("\n", $oids) as $data)
    {
      $data = trim($data);
      if ($data)
      {
        list($oid,$role) = explode(" ", $data);
        $split_oid = explode('.',$oid);
        $index = $split_oid[count($split_oid)-1];
        if (is_numeric($role))
        {
          $toner_oid     = ".1.3.6.1.2.1.43.11.1.1.9.1.$index";
          $descr_oid     = ".1.3.6.1.2.1.43.11.1.1.6.1.$index";
          $capacity_oid  = ".1.3.6.1.2.1.43.11.1.1.8.1.$index";
          $type_oid      = ".1.3.6.1.2.1.43.11.1.1.5.1.$index";

          $resourcetype = snmp_get($device, $type_oid, "-Oqv");
          
          if ($resourcetype == 3 || $resourcetype == 21)
          {
            $descr         = trim(str_replace("\n","",str_replace('"','',snmp_get($device, $descr_oid, "-Oqv"))));
            if ($descr != "")
            {
              $current      = snmp_get($device, $toner_oid, "-Oqv");
              $capacity     = snmp_get($device, $capacity_oid, "-Oqv");
              $current      = $current / $capacity * 100;
              $type         = "jetdirect";
              if (isHexString($descr)) { $descr = snmp_hexstring($descr); }
              discover_toner($valid_toner,$device, $toner_oid, $index, $type, $descr, $capacity_oid, $capacity, $current);
            }
          }
        }
      }
    }
  }

  // Delete removed toners
  if ($debug) { echo("\n Checking ... \n"); print_r($valid_toner); }

  $sql = "SELECT * FROM toner WHERE device_id = '".$device['device_id']."'";
  if ($query = mysql_query($sql))
  {
    while ($test_toner = mysql_fetch_assoc($query))
    {
      $toner_index = $test_toner['toner_index'];
      $toner_type = $test_toner['toner_type'];
      if (!$valid_toner[$toner_type][$toner_index])
      {
        echo("-");
        mysql_query("DELETE FROM `toner` WHERE toner_id = '" . $test_toner['toner_id'] . "'");
        log_event("Toner removed: type ".mres($toner_type)." index ".mres($toner_index)." descr ". mres($test_toner['toner_descr']), $device, 'toner', $test_toner['toner_id']);
      }
    }
  }

  unset($valid_toner);
  echo("\n");
  
  // Discover other counters and monitored values
  echo("Attributes: ");
  
  $pagecounters = array("1.3.6.1.2.1.43.10.2.1.4.1.1");
  
  foreach ($pagecounters as $oid)
  {
    if (snmp_get($device, $oid, "-OUqnv"))
    {
      echo("Pagecounter ");
      set_dev_attrib($device, "pagecount_oid", $oid);
      break;
    }
  }
  
  echo("\n");
} # if ($config['enable_printers'])

?>