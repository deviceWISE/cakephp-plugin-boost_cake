<?php
if (!isset($class)) {
	$class = false;
}
if (!isset($close)) {
	$close = true;
}
?>
<div class="dialog<?php echo ($class) ? ' ' . $class : null; ?>">
	<?php echo $message; ?>
</div>