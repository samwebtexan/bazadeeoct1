<?php bp_core_setup_message();?>

			<?php
			
				
			if(bp_current_action()=="create")
                            bp_gallery_locate_template( array( 'gallery/members/create.php' ), true ) ;
			else if(bp_current_action()=="manage")
                            bp_gallery_locate_template( array( 'gallery/members/edit.php' ), true ) ;
			else if(bp_current_action()=="upload")
                            bp_gallery_locate_template( array( 'gallery/single/media/upload-form.php' ), true ) ;
			else if(bp_is_single_media())
                            bp_gallery_locate_template( array( 'gallery/members/single-media.php' ), true ) ;
			 else if(bp_is_single_gallery())
                             bp_gallery_locate_template( array( 'gallery/members/single.php' ), true ) ;
                        else if(bp_is_my_group_galleries ())
                             bp_gallery_locate_template( array( 'gallery/members/group-galleries.php' ), true ) ;
                        else
			 bp_gallery_locate_template( array( 'gallery/members/home.php' ), true ) ;
			
			

			?>
				
			
