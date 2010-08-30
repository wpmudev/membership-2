<?php
?>
	<form action="" name="signup_form" id="signup_form" class="standard-form" method="post" enctype="multipart/form-data">

	<h2><?php _e( 'Create an Account', 'buddypress' ) ?></h2>

	<?php do_action( 'template_notices' ) ?>

	<p><?php _e( 'Registering for this site is easy, just fill in the fields below and we\'ll get a new account set up for you in no time.', 'buddypress' ) ?></p>

	<?php do_action( 'bp_before_account_details_fields' ) ?>

	<div class="register-section" id="basic-details-section">

		<?php /***** Basic Account Details ******/ ?>

		<h4><?php _e( 'Account Details', 'buddypress' ) ?></h4>

				<label for="signup_username"><?php _e( 'Username', 'buddypress' ) ?> <?php _e( '(required)', 'buddypress' ) ?></label>
				<?php do_action( 'bp_signup_username_errors' ) ?>
				<input type="text" name="signup_username" id="signup_username" value="<?php bp_signup_username_value() ?>" />

				<label for="signup_email"><?php _e( 'Email Address', 'buddypress' ) ?> <?php _e( '(required)', 'buddypress' ) ?></label>
				<?php do_action( 'bp_signup_email_errors' ) ?>
				<input type="text" name="signup_email" id="signup_email" value="<?php bp_signup_email_value() ?>" />

				<label for="signup_password"><?php _e( 'Choose a Password', 'buddypress' ) ?> <?php _e( '(required)', 'buddypress' ) ?></label>
				<?php do_action( 'bp_signup_password_errors' ) ?>
				<input type="password" name="signup_password" id="signup_password" value="" />

				<label for="signup_password_confirm"><?php _e( 'Confirm Password', 'buddypress' ) ?> <?php _e( '(required)', 'buddypress' ) ?></label>
				<?php do_action( 'bp_signup_password_confirm_errors' ) ?>
				<input type="password" name="signup_password_confirm" id="signup_password_confirm" value="" />

	</div><!-- #basic-details-section -->

	<?php do_action( 'bp_after_account_details_fields' ) ?>

	<?php /***** Extra Profile Details ******/ ?>

	<?php if ( bp_is_active( 'xprofile' ) ) : ?>

	<?php do_action( 'bp_before_signup_profile_fields' ) ?>

	<div class="register-section" id="profile-details-section">

		<h4><?php _e( 'Profile Details', 'buddypress' ) ?></h4>

		<?php /* Use the profile field loop to render input fields for the 'base' profile field group */ ?>
					<?php if ( function_exists( 'bp_has_profile' ) ) : if ( bp_has_profile( 'profile_group_id=1' ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

					<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

						<div class="editfield">

							<?php if ( 'textbox' == bp_get_the_profile_field_type() ) : ?>

								<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
								<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>
								<input type="text" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" value="<?php bp_the_profile_field_edit_value() ?>" />

							<?php endif; ?>

							<?php if ( 'textarea' == bp_get_the_profile_field_type() ) : ?>

								<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
								<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>
								<textarea rows="5" cols="40" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_edit_value() ?></textarea>

							<?php endif; ?>

							<?php if ( 'selectbox' == bp_get_the_profile_field_type() ) : ?>

								<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
								<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>
								<select name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>">
									<?php bp_the_profile_field_options() ?>
								</select>

							<?php endif; ?>

							<?php if ( 'multiselectbox' == bp_get_the_profile_field_type() ) : ?>

								<label for="<?php bp_the_profile_field_input_name() ?>"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
								<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>
								<select name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" multiple="multiple">
									<?php bp_the_profile_field_options() ?>
								</select>

							<?php endif; ?>

							<?php if ( 'radio' == bp_get_the_profile_field_type() ) : ?>

								<div class="radio">
									<span class="label"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></span>

									<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>
									<?php bp_the_profile_field_options() ?>

									<?php if ( !bp_get_the_profile_field_is_required() ) : ?>
										<a class="clear-value" href="javascript:clear( '<?php bp_the_profile_field_input_name() ?>' );"><?php _e( 'Clear', 'buddypress' ) ?></a>
									<?php endif; ?>
								</div>

							<?php endif; ?>

							<?php if ( 'checkbox' == bp_get_the_profile_field_type() ) : ?>

								<div class="checkbox">
									<span class="label"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></span>

									<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>
									<?php bp_the_profile_field_options() ?>
								</div>

							<?php endif; ?>

							<?php if ( 'datebox' == bp_get_the_profile_field_type() ) : ?>

								<div class="datebox">
									<label for="<?php bp_the_profile_field_input_name() ?>_day"><?php bp_the_profile_field_name() ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'buddypress' ) ?><?php endif; ?></label>
									<?php do_action( 'bp_' . bp_get_the_profile_field_input_name() . '_errors' ) ?>

									<select name="<?php bp_the_profile_field_input_name() ?>_day" id="<?php bp_the_profile_field_input_name() ?>_day">
										<?php bp_the_profile_field_options( 'type=day' ) ?>
									</select>

									<select name="<?php bp_the_profile_field_input_name() ?>_month" id="<?php bp_the_profile_field_input_name() ?>_month">
										<?php bp_the_profile_field_options( 'type=month' ) ?>
									</select>

									<select name="<?php bp_the_profile_field_input_name() ?>_year" id="<?php bp_the_profile_field_input_name() ?>_year">
										<?php bp_the_profile_field_options( 'type=year' ) ?>
									</select>
								</div>

							<?php endif; ?>

							<?php do_action( 'bp_custom_profile_edit_fields' ) ?>

							<p class="description"><?php bp_the_profile_field_description() ?></p>

						</div>

					<?php endwhile; ?>

					<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bp_the_profile_group_field_ids() ?>" />

					<?php endwhile; endif; endif; ?>

				</div><!-- #profile-details-section -->

				<?php do_action( 'bp_after_signup_profile_fields' ) ?>

			<?php endif; ?>

			<?php do_action( 'bp_before_registration_submit_buttons' ) ?>

			<div class="submit">
				<input type="submit"name="signup_submit" id="signup_submit" value="<?php _e( 'Sign Up', 'membership' ) ?> &rarr;" />
			</div>

			<?php do_action( 'bp_after_registration_submit_buttons' ) ?>

			<?php wp_nonce_field( 'bp_new_signup' ) ?>


		<?php do_action( 'bp_custom_signup_steps' ) ?>
		<input type="hidden" name="action" value="validatepage1bp" />
		</form>
<?php
?>