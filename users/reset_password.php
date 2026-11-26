<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_system_admin();
$id=(int)($_GET['id']??0);
$query=$pdo->prepare('SELECT id,name,email,role FROM users WHERE id=?');
$query->execute([$id]);$target=$query->fetch(PDO::FETCH_ASSOC);
if(!$target){http_response_code(404);exit('User not found.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $password=$_POST['new_password']??'';
 if(strlen($password)<8||strlen($password)>72)$error='Use a password between 8 and 72 characters.';
 elseif($password!==($_POST['confirm_password']??''))$error='The passwords do not match.';
 else{
  $update=$pdo->prepare('UPDATE users SET password=? WHERE id=?');
  $update->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
  flash('Password reset successfully for '.$target['name'].'. The user can now sign in with the new password.');
  header('Location:index.php');exit;
 }
}
$page_title='Reset Password';require __DIR__.'/../includes/header.php';
?>
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-heading">Reset Password</h1><p class="page-subtitle">Set a new password for this account.</p></div><a class="btn btn-outline-secondary" href="index.php">Back to Manage Users</a></div>
<div class="panel" style="max-width:640px"><div class="border-bottom pb-3 mb-4"><b><?=e($target['name'])?></b><div class="text-muted"><?=e($target['email'])?> · <?=e($target['role'])?></div></div>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label class="form-label" for="newPassword">New Password</label><input class="form-control mb-3" type="password" id="newPassword" name="new_password" minlength="8" maxlength="72" autocomplete="new-password" required><label class="form-label" for="confirmPassword">Confirm New Password</label><input class="form-control" type="password" id="confirmPassword" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required><div class="form-text">Use 8–72 characters.</div><div class="form-check my-3"><input type="checkbox" class="form-check-input" id="showPassword"><label class="form-check-label" for="showPassword">Show the new password</label></div><button class="btn btn-primary" type="submit">Reset Password</button></form></div>
<script>document.getElementById('showPassword').addEventListener('change',function(){for(const id of ['newPassword','confirmPassword'])document.getElementById(id).type=this.checked?'text':'password';});</script>
<?php require __DIR__.'/../includes/footer.php';?>
