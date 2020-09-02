<?php
function date_add_days($date, $days,$start=0)
{
  $date = date_create($date);
  if($start != 0)
  {
    $d = date_create($start);
    if($d > $date)
    $date = $d;
  }
  date_add($date, date_interval_create_from_date_string(($days).' day'));
  return date_format($date, 'Y-m-d H:i');
}
?>