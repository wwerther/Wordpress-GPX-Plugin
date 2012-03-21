<?php
/* 
  Option page for GPX2Chart wordpress plugin
  inspired by MiKa (http://www.HanBlog.net)
  blog:   http://wwerther.de
  plugin: http://www.Fotomobil.at/wp-osm-plugin
*/
?>
<div class="wrap">
<table border="0">
 <tr>
  <td><p><img src="<?php echo GPX2CHART_PLUGIN_URL ?>/WP_GPX2Chart_Plugin_Logo.png" alt="Gpx2Chart Logo"></p></td>
  <td style="width:100%"><h2>GPX2Chart Plugin <?php echo GPX2CHART_PLUGIN_VER ?> </h2></td>
  <td>
Donate:<br\>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="CZYYX8C3M9N6L">
<input type="image" src="https://www.paypal.com/en_US/i/logo/PayPal_mark_50x34.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen  mit PayPal.">
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1">
</form>
<br/>
<a href="http://flattr.com/thing/589137/Wordpress-GPX2-Chart-Plugin" target="_blank">
<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>
   </td>
 </tr>
</table>
<form method="post">
<div class="submit"><input type="submit" name="Options" value="<?php _e('Update Options','OSM-plugin') ?> &raquo;" /></div>
</div>
</form>

