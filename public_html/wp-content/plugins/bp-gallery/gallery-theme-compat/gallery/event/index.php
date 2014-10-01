<div id="buddypress">
			


			<div id="item-header">
				<?php bpcp_locate_template( array( 'type/single/type-header.php' ), true ) ?>
			</div><!-- #item-header -->

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="object-nav">
					<ul>
						<?php bp_get_options_nav() ?>
					</ul>
				</div>
			</div><!-- /#item-nav -->

			<div id="item-body">
						<?php do_action( 'template_notices' ) ?>

				<?php do_action( 'bp_before_event_body' ) ?>
	<?php if(bp_is_gallery_home())
			 bp_gallery_locate_template( array( 'gallery/event/home.php' ), true ) ;
			 
		else if(bp_is_gallery_create())
			bp_gallery_locate_template( array( 'gallery/event/create.php' ), true ) ;
			 	
		else if(bp_is_gallery_edit())
			bp_gallery_locate_template( array( 'gallery/event/edit.php' ), true ) ;
			 
		else if(bp_is_gallery_upload())
			bp_gallery_locate_template( array( 'gallery/event/upload.php' ), true ) ;
		 else if(bp_is_single_media())
			
			bp_gallery_locate_template( array( 'gallery/event/single-media.php' ), true ) ;
		 
		else if(bp_is_single_gallery())
			
			bp_gallery_locate_template( array( 'gallery/event/single.php' ), true ) ;
		 
		 else { ?>
					<?php /* The group is not visible, show the status message */ ?>

					<?php do_action( 'bp_before_group_status_message' ) ?>

					<div id="message" class="info">
						<p><?php //bp_group_status_message() ?></p>
					</div>

					<?php do_action( 'bp_after_group_status_message' ) ?>
				<?php }; ?>
			</div><!-- #item-body -->
			
		</div>
	
	