<?php
/** Catálogo SVG embarcado (Font Awesome Free), SVG nunca editado nem recebido pelo navegador. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_Icon_Library {
    public static function manifest() {
        $file=SMTT_INTRANET_PATH.'assets/enterprise/icons/catalog.json';
        if (!is_readable($file)) { return array(); }
        $data=json_decode((string)file_get_contents($file),true);
        return is_array($data)?$data:array();
    }
    public static function valid_value($value) {
        if (preg_match('/^smtt:([a-z0-9-]{1,90})$/', (string) $value, $m)) {
            return is_file(SMTT_INTRANET_PATH . 'assets/enterprise/icons/smtt/' . $m[1] . '.svg');
        }
        if (preg_match('/^fa:(solid|regular|brands):([a-z0-9-]+)$/',$value,$m)) {
            return is_file(SMTT_INTRANET_PATH.'assets/enterprise/icons/'.$m[1].'/'.$m[2].'.svg');
        }
        if (preg_match('/^logo:([0-9]{1,10})$/',$value,$m)) {
            return wp_attachment_is_image(absint($m[1])) && get_post_meta(absint($m[1]),'_smtt_portal_logo',true)==='1';
        }
        // Existing legacy identifiers remain accepted for backwards compatibility.
        return (bool)preg_match('/^(dashicons-[a-z0-9-]+|[a-z][a-z0-9-]{0,60})$/',$value);
    }
    public static function url($value) {
        if (preg_match('/^smtt:([a-z0-9-]{1,90})$/', (string) $value, $m)) {
            return self::valid_value($value) ? SMTT_INTRANET_URL . 'assets/enterprise/icons/smtt/' . $m[1] . '.svg' : '';
        }
        if (preg_match('/^fa:(solid|regular|brands):([a-z0-9-]+)$/',(string)$value,$m)) {
            if (!self::valid_value($value)) { return ''; }
            return SMTT_INTRANET_URL.'assets/enterprise/icons/'.$m[1].'/'.$m[2].'.svg';
        }
        if (preg_match('/^logo:([0-9]{1,10})$/',(string)$value,$m) && self::valid_value($value)) {
            return (string)wp_get_attachment_image_url((int)$m[1],'thumbnail');
        }
        return '';
    }
    public static function html($value,$extra_class='') {
        $src=self::url($value);
        if ($src) { return '<img class="smtt-portal-svg '.esc_attr($extra_class).'" src="'.esc_url($src).'" alt="" loading="lazy">'; }
        return '<span class="dashicons '.esc_attr(preg_match('/^dashicons-[a-z0-9-]+$/',(string)$value)?$value:'dashicons-admin-generic').'" aria-hidden="true"></span>';
    }
}
