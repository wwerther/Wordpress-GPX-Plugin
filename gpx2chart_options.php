<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim
/* 
  Option page for GPX2Chart wordpress plugin
               inspired by MiKa (http://www.HanBlog.net)
  blog:   http://wwerther.de
  plugin: http://www.wwerther.de/statc/gpx2chart
*/
?>
<style type="text/css">
    .fileedit-sub {
        line-height: 180%;
        padding: 10px 0 8px;
    }
#template textarea {
    font-family: Consolas,Monaco,Courier,monospace;
    font-size: 12px;
    width: 97%;
}
#template p {
    width: 97%;
}
#templateside {
    float: right;
    width: 190px;
    word-wrap: break-word;
}
#templateside h3, #postcustomstuff p.submit {
    margin: 0;
}
#templateside h4 {
    margin: 1em 0 0;
}
#templateside ol, #templateside ul {
    margin: 0.5em;
    padding: 0;
}
#templateside li {
    margin: 4px 0;
}
#templateside ul li a span.highlight {
    display: block;
}
.nonessential {
    font-size: 11px;
    font-style: italic;
    padding-left: 12px;
}
.highlight {
    border-radius: 8px 8px 8px 8px;
    font-weight: bold;
    margin-left: -12px;
    padding: 3px 3px 3px 12px;
}
    
</style>

<div class="wrap">
<table border="0">
 <tr>
  <td><p><img src="<?php echo GPX2CHART_PLUGIN_URL ?>/icons/WP_GPX2Chart_Plugin_Logo.png" alt="Gpx2Chart Logo"></p></td>
  <td style="width:100%"><h2>GPX2Chart Plugin <?php echo GPX2CHART_PLUGIN_VER ?> </h2></td>
  <td>
Donate via<br\>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="margin:0;">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="CZYYX8C3M9N6L">
<input type="image" src="<?php echo GPX2CHART_PLUGIN_ICONS_URL?>PayPal_mark_50x34.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen mit PayPal.">
</form>
<a href="http://flattr.com/thing/589137/Wordpress-GPX2-Chart-Plugin" target="_blank"><img src="<?php echo GPX2CHART_PLUGIN_ICONS_URL?>flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a><br/>
&nbsp;or send me an <a href="mailto:gpx2chart@wwerther.de">email</a> if you like this plugin.
   </td>
 </tr>
</table>

<form method="post">
<textarea name="gpx2chartoptions" cols="150" rows="20" ><?php echo $gpx2chart_config ?> </textarea>
<div class="submit"><input type="submit" name="Options" value="<?php _e('Update Options','GPX2Chart-plugin') ?> &raquo;" /><input type="submit" name="Reset" value="<?php _e('Reset Options','GPX2Chart-plugin') ?> &raquo;" /></div>
</div>
</form>
<?php

$meta['Stylesheet Files']['dir']=GPX2CHART_CSS_DIR;
$meta['Stylesheet Files']['ext']='.css';
$meta['Profile Files']['dir']=GPX2CHART_PROFILES;
$meta['Profile Files']['ext']='.profile';

$scrollto = isset($_REQUEST['scrollto']) ? (int) $_REQUEST['scrollto'] : 0;

$file=$_REQUEST['file'];
$file_show = basename( $file );


if ($_REQUEST['action']=='update') {
	$newcontent = stripslashes($_POST['newcontent']);
	if (is_writeable($file)) {
		//is_writable() not always reliable, check return value. see comments @ http://uk.php.net/is_writable
		$f = fopen($file, 'w+');
		if ($f !== FALSE) {
			fwrite($f, $newcontent);
			fclose($f);
			$location = "?page=gpx2chart.php&file=$file&theme=$theme&a=te&scrollto=$scrollto";
		} else {
    		$error = 1;
            $errormessage="File '$file' write error";
		}
	} else {
		$error = 1;
        $errormessage="File '$file' is not writeable";
  	}

/*	$location = wp_kses_no_null($location);
	$strip = array('%0d', '%0a', '%0D', '%0A');
	$location = _deep_replace($strip, $location);
	header("Location: $location");
	exit(); */
} elseif ($_REQUEST['action']=='cloneprofile') {
    $from=$meta['Profile Files']['dir'].basename($_REQUEST['from'],$meta['Profile Files']['ext']).$meta['Profile Files']['ext'];
    $to=$meta['Profile Files']['dir'].basename($_REQUEST['to'],$meta['Profile Files']['ext']).$meta['Profile Files']['ext'];
	if (is_writeable($meta['Profile Files']['dir'])) {
        if (copy($from,$to)) {
/*            $error = 1;
            $errormessage="file copy success from '$from' to '$to'";*/
        } else {
            $error = 1;
            $errormessage="Can't copy file '$from' to '$to', because directory is not writeable";
        };
    } else {
        $error = 1;
        $errormessage="Can't clone file '$from' to '$to', because directory is not writeable";
    }
}  elseif ($_REQUEST['action']=='clonestyle') {
    $from=$meta['Stylesheet Files']['dir'].basename($_REQUEST['from'],$meta['Stylesheet Files']['ext']).$meta['Stylesheet Files']['ext'];
    $to=$meta['Stylesheet Files']['dir'].basename($_REQUEST['to'],$meta['Stylesheet Files']['ext']).$meta['Stylesheet Files']['ext'];
	if (is_writeable($meta['Stylesheet Files']['dir'])) {
        if (copy($from,$to)) {
/*            $error = 1;
            $errormessage="file copy success from '$from' to '$to'";*/
        } else {
            $error = 1;
            $errormessage="Can't copy file '$from' to '$to', because directory is not writeable";
        };
    } else {
        $error = 1;
        $errormessage="Can't clone file '$from' to '$to', because directory is not writeable";
    }
};

    foreach ($meta as $name=>$type) {
        # echo "$name:$type[dir]:$type[ext]<br/>";
        $template_files[$name]=glob($type['dir'].DIRECTORY_SEPARATOR.'*'.$type['ext']);
    }

    $file=$_REQUEST['file'] ? $_REQUEST['file'] : $template_files['Stylesheet Files'][0];

	if ( !is_file($file) ) {
		$error = 1;
        $errormessage="Oops, no such file exists! Double check the name and try again, merci.";
    }

	$content = '';
	if ( !$error && filesize($file) > 0 ) {
		$f = fopen($file, 'r');
		$content = fread($f, filesize($file));

		if ( '.php' == substr( $file, strrpos( $file, '.' ) ) ) {
			$functions = wp_doc_link_parse( $content );

			$docs_select = '<select name="docs-list" id="docs-list">';
			$docs_select .= '<option value="">' . esc_attr__( 'Function Name...' ) . '</option>';
			foreach ( $functions as $function ) {
				$docs_select .= '<option value="' . esc_attr( urlencode( $function ) ) . '">' . htmlspecialchars( $function ) . '()</option>';
			}
			$docs_select .= '</select>';
		}

		$content = esc_textarea( $content );
	}

	?>
<?php if (isset($_GET['a'])) : ?>
 <div id="message" class="updated"><p><?php _e('File edited successfully.') ?></p></div>
<?php endif;

$description = get_file_description($file);
$desc_header = ( $description != $file_show ) ? "$description <span>($file_show)</span>" : $file_show;

?>
<div class="fileedit-sub">
<div class="alignleft">
<h2>Advance-Options</h2>
</div>
<br class="clear" />
</div>
	<div id="templateside">
        <?php foreach ($template_files as $module=>$content_files) : ?>
        	<h3><?php _e($module); ?></h3>
	        <ul>
                <?php
            	    $template_mapping = array();
                	$template_dir = $themes[$theme]['Template Dir'];
            	    foreach ( $content_files as $template_file ) {
                        $description = trim( get_file_description($template_file) );
                		$template_show = basename($template_file,$meta[$module]['ext']);
            	    	$filedesc = ( $description != $template_file ) ? "$description<br /><span class='nonessential'>($template_show)</span>" : "$description";
        	    	    $filedesc = ( $template_file == $file ) ? "<span class='highlight'>$description<br /><span class='nonessential'>($template_show)</span></span>" : $filedesc;
            		    $template_mapping[ $description ] = array( _get_template_edit_filename($template_file, $template_dir), $filedesc );
                   	}
                 	ksort( $template_mapping );
                 	while ( list( $template_sorted_key, list( $template_file, $filedesc ) ) = each( $template_mapping ) ) :
                ?>
	    	        <li><a href="?page=gpx2chart.php&amp;file=<?php echo urlencode( $template_file ) ?>&amp;module=<?php echo urlencode( $module ) ?>"><?php echo $filedesc ?></a></li>
                <?php endwhile; ?>
        	</ul>
        <?php endforeach;    ?>
         <h3>Cloning</h3>
         <h4>Profiles</h4>
         <form method="post">
            <?php $module='Profile Files'; ?>
            <input type="hidden" name="action" value="cloneprofile" />
            <input type="hidden" name="module" value="<?php echo $module ?>" />
            <label for="from">From:</label><select name="from">
                <?php foreach ($template_files[$module] as $file) {
                    $displayname=basename($file,$meta[$module]['ext']);
                    $basename=basename($file);
                    echo "<option value='$basename'>$displayname</option>";
                } ?>
             </select><br/>
             <label for="to">To:</label><input length="12" type="text" name="to" value="">
             <?php submit_button( __( 'Clone' ), '', 'submit', true, array() ) ?>
         </form>
         <h4>Styles</h4>
         <form method="post">
            <?php $module='Stylesheet Files'; ?>
            <input type="hidden" name="action" value="clonestyle" />
            <input type="hidden" name="module" value="<?php echo $module ?>" />
            <label for="from">From:</label><select name="from">
                <?php foreach ($template_files[$module] as $file) {
                    $displayname=basename($file,$meta[$module]['ext']);
                    $basename=basename($file);
                    echo "<option value='$basename'>$displayname</option>";
                } ?>
             </select><br/>
             <label for="to">To:</label><input length="12" type="text" name="to" value="">
             <?php submit_button( __( 'Clone' ), '', 'submit', true, array() ) ?>
         </form>
    </div>
<?php if (!$error) { ?>
	<form name="template" id="template" action="?page=gpx2chart.php" method="post">
      	 <?php wp_nonce_field('edit-file_' . $file ) ?>
		 <div><textarea cols="70" rows="25" name="newcontent" id="newcontent" tabindex="1"><?php echo $content ?></textarea>
		 <input type="hidden" name="action" value="update" />
		 <input type="hidden" name="file" value="<?php echo esc_attr($file) ?>" />
		 <input type="hidden" name="module" value="<?php echo esc_attr($module) ?>" />
		 <input type="hidden" name="scrollto" id="scrollto" value="<?php echo $scrollto; ?>" />
		 </div>
	<?php if ( isset($functions ) && count($functions) ) { ?>
		<div id="documentation" class="hide-if-no-js">
		<label for="docs-list"><?php _e('Documentation:') ?></label>
		<?php echo $docs_select; ?>
		<input type="button" class="button" value=" <?php esc_attr_e( 'Lookup' ); ?> " onclick="if ( '' != jQuery('#docs-list').val() ) { window.open( 'http://api.wordpress.org/core/handbook/1.0/?function=' + escape( jQuery( '#docs-list' ).val() ) + '&amp;locale=<?php echo urlencode( get_locale() ) ?>&amp;version=<?php echo urlencode( $wp_version ) ?>&amp;redirect=true'); }" />
		</div>
	<?php } ?>
		<div>
        <?php if ( is_writeable( $file ) ) :
		    submit_button( __( 'Update File' ), '', 'submit', true, array( 'tabindex' => '2' ) );
    	else : ?>
            <p><em><?php _e('You need to make this file writable before you can save your changes. See <a href="http://codex.wordpress.org/Changing_File_Permissions">the Codex</a> for more information.'); ?></em></p>
        <?php endif; ?>
		</div>
	</form>
<?php
    } else {
		echo '<div class="error"><p>' . __($errormessage) . '</p></div>';
	}
?>
<br class="clear" />
</div>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function($){
	$('#template').submit(function(){ $('#scrollto').val( $('#newcontent').scrollTop() ); });
	$('#newcontent').scrollTop( $('#scrollto').val() );
});
/* ]]> */
</script>

