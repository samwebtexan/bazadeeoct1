
<div id="galleries">
	<?php do_action("gallery_before_create_content");?>
	<?php bp_gallery_locate_template( array( 'gallery/single/create-form.php' ), true );
				?>
<?php do_action( 'bp_after_gallery_create_content' ) ?>
</div><!--end of bp-gallery-->