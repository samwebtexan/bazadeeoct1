<?php
bp_core_setup_message();
//sorry but gallery actions are called too late for the notice to be printed
?>

                <?php //do_action( 'template_notices' )  ?>

                <?php do_action('bp_before_group_body') ?>
                <?php
                if (bp_is_gallery_home())
                    bp_gallery_locate_template(array('gallery/groups/home.php'), true);

                else if (bp_is_gallery_create())
                    bp_gallery_locate_template(array('gallery/groups/create.php'), true);

                else if (bp_is_gallery_edit())
                    bp_gallery_locate_template(array('gallery/groups/edit.php'), true);

                else if (bp_is_gallery_upload())
                    bp_gallery_locate_template(array('gallery/groups/upload.php'), true);
                else if (bp_is_single_media())
                    bp_gallery_locate_template(array('gallery/groups/single-media.php'), true);

                else if (bp_is_single_gallery())
                    bp_gallery_locate_template(array('gallery/groups/single.php'), true);

                else {
                    ?>
                    <?php /* The group is not visible, show the status message */ ?>

                    <?php do_action('bp_before_group_status_message') ?>

                    <div id="message" class="info">
                        <p><?php bp_group_status_message() ?></p>
                    </div>

                    <?php do_action('bp_after_group_status_message') ?>
                <?php } ?>

                <?php do_action('bp_after_group_body') ?>
           

            <?php do_action('bp_after_group_home_content') ?>
