<?php if (!empty($_GET) || !empty($_POST) || !empty($_COOKIE) || !empty($_SESSION)) { ?>
<pre>
<?php if (!empty($_GET)) { ?>
$_GET = [
<?php foreach ($_GET as $key => $value) { ?>
    '<?= htmlspecialchars($key) ?>' => '<?= htmlspecialchars($value) ?>'
<?php } ?>
];
<?php } ?>
<?php if (!empty($_POST)) {?>

$_POST = [
<?php foreach ($_POST as $key => $value) { ?>
    '<?= htmlspecialchars($key) ?>' => '<?= htmlspecialchars($value) ?>'
<?php } ?>
];
<?php } ?>
<?php if (!empty($_COOKIE)) {?>

$_COOKIE = [
<?php foreach ($_COOKIE as $key => $value) { ?>
    '<?= htmlspecialchars($key) ?>' => '<?= htmlspecialchars($value) ?>'
<?php } ?>
];
<?php } ?>
<?php if (!empty($_SESSION)) {?>

$_SESSION = [
<?php foreach ($_SESSION as $key => $value) { ?>
    '<?= htmlspecialchars($key) ?>' => '<?= htmlspecialchars($value) ?>'
<?php } ?>
];
<?php } ?>
</pre>
<?php } ?>
