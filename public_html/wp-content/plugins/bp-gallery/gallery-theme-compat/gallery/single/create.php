		<div id="buddypress">

			<?php do_action( 'bp_before_member_plugin_template' ) ?>

			<div id="item-header">
				<?php bp_locate_template( array( 'members/single/member-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="sub-nav">
					<ul>
						<?php bp_get_user_nav() ?>

						<?php do_action( 'bp_members_directory_member_types' ) ?>
					</ul>
				</div>
			</div>

			<div id="item-body">

				<div class="item-list-tabs no-ajax" id="subnav">
					<ul>
						<?php bp_get_options_nav() ?>
					</ul>
				</div>

				<div class="bp-gallery">
				<?php do_action( 'bp_before_gallery_edit_content' ) ?>
				<?php bp_gallery_locate_template( array( 'gallery/create-form.php' ), true );
				?>
		
		
				<?php do_action( 'bp_after_gallery_edit_content' ) ?>
			
				</div><!--end of bp-gallery-->

			</div><!-- #item-body -->

			<?php do_action( 'bp_after_member_plugin_template' ) ?>

		</div>
	
	