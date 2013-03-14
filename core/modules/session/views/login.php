<h1>Login</h1>

<form action="<?php echo Html::url('//user/login'); ?>" method="post">
    <input type="text" name="Login[login]"
           value="<?php echo (!empty($_POST['Login']['login'])) ? $_POST['Login']['login'] : ""; ?>">
    <input type="password" name="Login[pass]">
    <input type="checkbox" name="Login[auto]" value="1">
    <input type="submit" value="Login"/>
</form>
