<form name="loginform" id="loginform" action="<?= home_url('/wp-login.php'); ?>" method="post">
    <div class="form-group">
        <label for="user_login">Uživatelské jméno nebo email</label>
        <input type="text" class="form-control" id="user_login" name="log" required>
    </div>
    <div class="form-group">
        <label for="user_pass">Heslo</label>
        <input type="password" class="form-control" id="user_pass" name="pwd" required>
    </div>

    <p class="login-submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="btn btn-primary" value="Přihlásit se">
        <input type="hidden" name="redirect_to" value="<?= home_url() ?>">
    </p>
</form>
