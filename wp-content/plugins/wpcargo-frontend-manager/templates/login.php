<div class="row">
	<div class="col-md-4 offset-md-4">
		<!-- Material form login -->
		<?php $user_name 	= ( !empty( $_POST ) && array_key_exists( 'billing_email', $_POST  ) ) ? $_POST['billing_email'] : '' ; ?>
		<?php if( isset( $_GET['login'] ) && $_GET['login'] == 'failed' ): ?>
			<?php $user_name 	= isset( $_GET['user'] ) ? $_GET['user'] : '' ; ?>
			<div class="alert alert-danger" role="alert">
				<span><b><?php esc_html_e( 'Error', 'wpcargo-frontend-manager' ); ?> - </b> <?php echo apply_filters( 'wpcfe_login_error', esc_html__( 'Please check your Username or Password.', 'wpcargo-frontend-manager' ) ); ?></span>
			</div>
		<?php endif; ?>
		<div class="card">
			<h5 class="card-header primary-color-dark darken-2 white-text text-center py-4">
				<strong><?php esc_html_e( 'Sign in', 'wpcargo-frontend-manager' ); ?></strong>
			</h5>
			<!--Card content-->
			<div class="card-body px-lg-5 pt-0">
				<div class="my-2 text-center">
					<?php $site_logo = $wpcargo->logo ? '<img style="width:160px;" src="'.$wpcargo->logo.'" alt="Site Logo">' : '<h1 class="h3">'.get_bloginfo( 'name' ).'</h1>' ; ?>
					<a href="<?php echo get_bloginfo( 'url' ); ?>"><?php echo $site_logo; ?></a>
				</div>
				<?php do_action( 'wpcfe_before_login_form' ); ?>
				<!-- Form -->
				<form name="loginform" id="loginform" action="<?php echo site_url( '/wp-login.php' ); ?>" method="post">
					<!-- Email -->
					<div class="md-form login-username">
						<label class="form-check-label" for="user_login"><?php esc_html_e( 'Username/E-mail', 'wpcargo-frontend-manager' ); ?></label>
						<input id="user_login" class="form-control border-input" type="text" size="20" value="<?php echo $user_name; ?>" name="log" required="required">
					</div>
					<!-- Password -->
					<div class="md-form login-password">
						<label class="form-check-label" for="user_pass"><?php esc_html_e( 'Password', 'wpcargo-frontend-manager' ); ?></label>
						<div class="wpcfe-password-field-wrap">
							<input id="user_pass" class="form-control border-input" type="password" size="20" value="" name="pwd" required="required">
							<button type="button" class="wpcfe-password-toggle" aria-controls="user_pass" aria-label="<?php esc_attr_e( 'Show password', 'wpcargo-frontend-manager' ); ?>" aria-pressed="false">
								<span class="wpcfe-eye-open" aria-hidden="true">
									<svg viewBox="0 0 24 24" width="18" height="18" focusable="false">
										<path fill="currentColor" d="M12 5c5.27 0 9.22 3.62 10.67 6.02.2.33.2.63 0 .96C21.22 14.38 17.27 18 12 18S2.78 14.38 1.33 11.98c-.2-.33-.2-.63 0-.96C2.78 8.62 6.73 5 12 5zm0 2C7.95 7 4.62 9.73 3.37 11.5 4.62 13.27 7.95 16 12 16s7.38-2.73 8.63-4.5C19.38 9.73 16.05 7 12 7zm0 1.75A2.75 2.75 0 1 1 12 14.25 2.75 2.75 0 0 1 12 8.75z"/>
									</svg>
								</span>
								<span class="wpcfe-eye-closed" aria-hidden="true" style="display:none;">
									<svg viewBox="0 0 24 24" width="18" height="18" focusable="false">
										<path fill="currentColor" d="M3.28 2 2 3.27l3.02 3.02c-1.7 1.2-2.95 2.74-3.69 4.01-.2.34-.2.64 0 .98C2.78 13.68 6.73 17.3 12 17.3c1.8 0 3.43-.42 4.88-1.07L20.73 20 22 18.73 3.28 2zM12 15.3c-4.04 0-7.38-2.73-8.63-4.5.62-.88 2.01-2.61 4.02-3.73l1.62 1.61a2.75 2.75 0 0 0 3.31 3.31l2.08 2.08c-.76.26-1.56.43-2.4.43zm9.67-4.52c-.96 1.58-2.49 3.22-4.52 4.35l-1.49-1.49c2.08-1.3 3.57-3.25 4.35-4.36C18.76 7.5 15.43 4.77 11.38 4.77c-.8 0-1.58.11-2.3.3L7.5 3.5c1.42-.46 2.92-.73 4.5-.73 5.27 0 9.22 3.62 10.67 6.01.2.34.2.64 0 1z"/>
									</svg>
								</span>
							</button>
						</div>
					</div>
					<?php if( has_action('register_form') ): ?>
					<div class="col-lg-12 p-0">
						<?php do_action( 'register_form' ); ?>
					</div>
					<?php endif ?>
					<div class="d-flex justify-content-around">
						<div>
							<!-- Remember me -->
							<div class="form-check">
								<input name="rememberme" type="checkbox" id="rememberme" class="form-check-input" value="forever">
								<label class="form-check-label" for="rememberme"><?php esc_html_e( 'Remember me', 'wpcargo-frontend-manager' ); ?></label>
							</div>
						</div>
						<div>
							<a href="<?php echo wp_lostpassword_url( $redirect_to ); ?>"><?php esc_html_e( 'Forgot password?', 'wpcargo-frontend-manager' ); ?></a>
						</div>
					</div>
					<div class="md-form login-submit">
						<input type="hidden" value="<?php echo esc_attr( apply_filters( 'wpcfe_login_redirect', $redirect_to ) ); ?>" name="redirect_to">
						<button id="wp-submit" class="btn btn-outline-primary btn-rounded btn-block my-4 waves-effect z-depth-0" type="submit" name="wp-submit"><?php esc_html_e('Login', 'wpcargo-frontend-manager' ); ?></button>
					</div>
				</form>
				<!-- Form -->
				<?php do_action( 'wpcfe_after_login_form' ); ?>				
			</div>
		</div>
		<!-- Material form login -->
	</div>
</div>
<style>
	#loginform .login-username > label,
	#loginform .login-password > label {
		position: static !important;
		transform: none !important;
		display: block;
		margin-bottom: 6px;
		left: auto !important;
		top: auto !important;
		font-size: 1rem;
		line-height: 1.25;
	}

	#loginform .login-username,
	#loginform .login-password {
		padding-top: 0;
	}

	#loginform .wpcfe-password-field-wrap {
		position: relative;
	}


	#loginform .wpcfe-password-toggle {
		position: absolute;
		right: 6px;
		top: 50%;
		transform: translateY(-50%);
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 38px;
		height: 38px;
		padding: 0;
		border: 0;
		background: transparent;
		color: #6c757d;
		cursor: pointer;
		line-height: 1;
		appearance: none;
		-webkit-appearance: none;
		touch-action: manipulation;
		z-index: 3;
	}

	#loginform .wpcfe-password-toggle:focus {
		outline: none;
	}

	#loginform .wpcfe-password-toggle svg {
		pointer-events: none;
	}
</style>
<script type="text/javascript">
(function(){
	function initPasswordToggle(){
		var passInput = document.getElementById('user_pass');
		var toggleBtn = document.querySelector('#loginform .wpcfe-password-toggle');
		var lastToggleAt = 0;
		if(!passInput || !toggleBtn){
			return;
		}
		if(toggleBtn.getAttribute('data-password-toggle-bound') === '1'){
			return;
		}
		toggleBtn.setAttribute('data-password-toggle-bound', '1');

		function handleToggle(event){
			if(event){
				event.preventDefault();
				event.stopPropagation();
			}
			var now = Date.now();
			if(now - lastToggleAt < 200){
				return;
			}
			lastToggleAt = now;

			var willShow = passInput.type === 'password';
			passInput.type = willShow ? 'text' : 'password';
			toggleBtn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
			toggleBtn.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');

			var openEye = toggleBtn.querySelector('.wpcfe-eye-open');
			var closedEye = toggleBtn.querySelector('.wpcfe-eye-closed');
			if(openEye && closedEye){
				openEye.style.display = willShow ? 'none' : 'inline-flex';
				closedEye.style.display = willShow ? 'inline-flex' : 'none';
			}
		}

		toggleBtn.addEventListener('click', handleToggle);
		toggleBtn.addEventListener('touchend', handleToggle, { passive: false });
		if(window.PointerEvent){
			toggleBtn.addEventListener('pointerup', handleToggle);
		}
	}

	if(document.readyState === 'loading'){
		document.addEventListener('DOMContentLoaded', initPasswordToggle);
	}else{
		initPasswordToggle();
	}
})();
</script>
