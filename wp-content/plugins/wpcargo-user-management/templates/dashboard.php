<div id="wpcumanage-table-wrapper" class="table-responsive">
    <h1 class="h4"><?php _e('Users', 'wpcargo-umanagement'); ?></h1>
    <?php do_action('wpcumanage_before_user_table', $wpcumanage_query); ?>
    <table id="wpcumanage-user-list" class="table table-hover table-sm">
        <thead>
            <tr>
                <?php do_action('wpcumanage_user_table_header'); ?>
                <td class="text-center wpcumanage-header-action"><?php _e('Action', 'wpcargo-umanagement'); ?></td>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($wpcumanage_query->get_results())): ?>
                <?php foreach ($wpcumanage_query->get_results() as $user): ?>
                    <?php
                    $access         = wpcumanage_user_access($user->ID);
                    $str_access     = is_array($access) ? implode(',', $access)  : '';
                    ?>
                    <tr id="user-<?php echo $user->ID; ?>" class="user-row">
                        <?php do_action('wpcumanage_user_table_data', $user); ?>
                        <td class="wpcumanage-action text-center">
                            <a href="<?php echo $page_url; ?>?umpage=edit&uid=<?php echo $user->ID; ?>" title="<?php _e('Update', 'wpcargo-umanagement'); ?>" class="mr-2"><i class="fa fa-edit text-info"></i></a>
                            <a href="#" title="<?php _e('Add Access', 'wpcargo-umanagement'); ?>" data-id="<?php echo $user->ID; ?>" data-access="<?php echo $str_access; ?>" class="wpcumange-update-access mr-2" data-toggle="modal" data-target="#wpcumanageAccessModal"><i class="fa fa-key text-success"></i></a>
                            <a href="#" class="wpcumange-deactivate-account" data-id="<?php echo $user->ID; ?>" title="<?php _e('Deactivate', 'wpcargo-umanagement'); ?>"><i class="fa fa-user-times text-danger"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php do_action('wpcumanage_after_user_table', $wpcumanage_query); ?>
    <div class="row">
        <section id="wpcumanage-user-pagination" class="col-md-5 my-4">
            <?php

            echo paginate_links(array(
                'base' => get_pagenum_link(1) . '%_%',
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => 'Previous',
                'next_text' => 'Next',
                'type'     => 'list',
            ));
            ?>
        </section>
    </div>
    <?php do_action('wpcumanage_after_user_table_pagination', $wpcumanage_query); ?>
</div>