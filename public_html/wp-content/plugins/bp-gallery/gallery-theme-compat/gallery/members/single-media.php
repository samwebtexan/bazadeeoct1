<?php  $gallery=bp_get_single_gallery();?>
<div class="gnav"><?php bp_gallery_bcomb();?>	</div>
<?php //do_action( 'bp_before_gallery_content' ) ?>
	<div id="galleries">
	<?php bp_gallery_locate_template( array( 'gallery/single/media/'.$gallery->gallery_type.'-single.php','gallery/single/media/single.php' ), true ) ;?>

	</div>