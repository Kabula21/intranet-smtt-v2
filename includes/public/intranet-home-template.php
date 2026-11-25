<?php
/** Template enxuto somente para a Home criada automaticamente pelo plugin. */
if (!defined('ABSPATH')) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('smtt-intranet-page'); ?>>
<?php wp_body_open(); ?>
<?php
// O shortcode continua sendo a fonte única da Home (Notícias, Ramais e Access Gate intactos).
echo do_shortcode('[smtt_intranet_home]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<?php wp_footer(); ?>
</body>
</html>
