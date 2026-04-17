<div class="col-md-12 text-center">
    <section class="card">
        <div class="card-body">    
            <div class="restricted-page text-center">        
                <i class="fa fa-exclamation-triangle text-danger" style="font-size: 120px;"></i>
                <p class="title h1 text-danger">
                    <?php esc_html_e("Error Found!", 'wpcargo-invoice' ); ?>
                </p>
                <p>
                    <?php esc_html_e("Cannot find WPCargo Frontent Manager Add-on plugin activated in your system.", 'wpcargo-invoice' ); ?>
                </p>
                <p>
                    <?php 
                        printf( __( 'Please purchase', 'wpcargo-invoice' ). ' <a href="%s" class="your-class">' . __( 'WPCargo Frontent Manager Add-on', 'wpcargo-invoice' ) . '</a>',  __( 'https://www.wpcargo.com/product/wpcargo-frontend-manager/' ) );
                    ?>
                </p>
            </div>
        </div>
    </section>
</div>