<!-- Full Height Modal Left Info Demo-->
<div class="modal fade" id="wpcfe-registration" tabindex="-1" role="dialog"
    aria-labelledby="<?php esc_html_e( 'Registration', 'wpcargo-frontend-manager' ); ?>" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog modal-lg modal-notify modal-info" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header primary-color-dark">
                <p class="heading lead"><i class="fa fa-user-circle text-white"></i> <?php esc_html_e( 'Registration', 'wpcargo-frontend-manager' ); ?></p>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e( 'Close', 'wpcargo-frontend-manager' ); ?>">
                    <span aria-hidden="true" class="white-text">&times;</span>
                </button>
            </div>
            <!--Body-->
            <div class="modal-body">
              <?php require_once( WPCFE_PATH.'templates/registration-form.tpl.php'); ?>
            </div>
            <!--Footer-->
            <div class="modal-footer justify-content-center">
            </div>
        </div>
        <!--/.Content-->
    </div>
</div>
<!-- Full Height Modal Right Info Demo-->