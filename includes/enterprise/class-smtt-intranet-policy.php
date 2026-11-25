<?php
/** Política corporativa: autorização obrigatória no servidor e escopos por registro. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_Policy {
    const VERSION = '1';
    public static function table() { global $wpdb; return $wpdb->prefix . 'smtt_intranet_grants'; }
    public static function audit_table() { global $wpdb; return $wpdb->prefix . 'smtt_intranet_audit'; }
    public static function install() {
        if (get_option('smtt_portal_policy_schema') === self::VERSION) { return; }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collation = $wpdb->get_charset_collate(); $t = self::table(); $a = self::audit_table();
        dbDelta("CREATE TABLE {$t} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subject_kind VARCHAR(12) NOT NULL,
            subject_value VARCHAR(100) NOT NULL,
            module_key VARCHAR(50) NOT NULL,
            operation VARCHAR(12) NOT NULL,
            scope_kind VARCHAR(12) NOT NULL,
            sector_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            item_ids TEXT NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY module_action (module_key,operation),
            KEY subject (subject_kind,subject_value)
        ) {$collation};");
        dbDelta("CREATE TABLE {$a} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_sid VARCHAR(100) NOT NULL DEFAULT '',
            actor_wp BIGINT UNSIGNED NOT NULL DEFAULT 0,
            action_name VARCHAR(90) NOT NULL,
            target VARCHAR(190) NOT NULL DEFAULT '',
            result_name VARCHAR(24) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY created_at (created_at)
        ) {$collation};");
        $has_grants = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($t))) === $t;
        $has_audit = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($a))) === $a;
        if ($has_grants && $has_audit) {
            update_option('smtt_portal_policy_schema', self::VERSION, false);
        }
    }
    public static function modules() {
        return array(
            'systems'=>array('Sistemas internos','smtt_internal_system'),
            'folders'=>array('Pastas dos setores','smtt_sector_folder'),
            'links'=>array('Links úteis','smtt_useful_link'),
            'documents'=>array('Documentos','smtt_document'),
            'agenda'=>array('Agenda','smtt_agenda_item'),
            'mural'=>array('Mural','smtt_mural_item'),
            'knowledge'=>array('Base de conhecimento','smtt_kb_article'),
            'birthdays'=>array('Aniversariantes','smtt_birthday_person'),
            'ramais'=>array('Ramais','ramais'),
            'custom'=>array('Recursos personalizados','smtt_custom_card'),
        );
    }
    public static function post_type($module) {
        $modules = self::modules(); return isset($modules[$module]) ? $modules[$module][1] : '';
    }
    public static function module_for_type($post_type) {
        foreach (self::modules() as $key=>$m) { if ($m[1] === $post_type) { return $key; } }
        return '';
    }
    public static function sector_ids_for_identity($identity) {
        if (!is_array($identity)) { return array(); }
        $groups = $identity['groups'] ?? array();
        if (!$groups) { return array(); }
        global $wpdb;
        // Only explicit CPD-configured AD-group -> sector bindings; no guesswork based on OU names.
        $terms = get_terms(array('taxonomy'=>'smtt_sector','hide_empty'=>false));
        if (is_wp_error($terms)) { return array(); }
        $ids = array();
        foreach ($terms as $term) {
            $sid = (string) get_term_meta($term->term_id, '_smtt_ad_group_sid', true);
            if ($sid && in_array($sid, $groups, true)) { $ids[] = (int) $term->term_id; }
        }
        return $ids;
    }
    public static function can($module, $operation, $item = 0, $item_sector = 0) {
        $valid = self::modules();
        if (!isset($valid[$module]) || !in_array($operation, array('view','create','edit','delete'), true)) { return false; }
        if (SMTT_Intranet_Corporate_Identity::is_admin()) { return true; }
        $identity = SMTT_Intranet_Corporate_Identity::current();
        if (is_wp_error($identity) || empty($identity['sid'])) { return false; }
        global $wpdb; $t=self::table();
        $now=current_time('mysql', true);
        $grants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$t} WHERE module_key=%s AND operation=%s AND (expires_at IS NULL OR expires_at>%s)",
            $module,$operation,$now), ARRAY_A);
        $sectors = self::sector_ids_for_identity($identity);
        $subject = (string) $identity['sid'];
        $groups = (array) ($identity['groups'] ?? array());
        $item = absint($item);
        $post = null;
        if ($item && $module !== 'ramais') {
            $post = get_post($item);
            if (!$post || $post->post_type !== $valid[$module][1]) { return false; }
            $tax = wp_get_object_terms($item, 'smtt_sector', array('fields'=>'ids'));
            $item_sectors = is_wp_error($tax) ? array() : array_map('intval', $tax);
        } else { $item_sectors = $item_sector ? array(absint($item_sector)) : array(); }
        foreach ((array) $grants as $g) {
            if ($g['subject_kind'] === 'user' && !hash_equals($g['subject_value'], $subject)) { continue; }
            if ($g['subject_kind'] === 'group' && !in_array($g['subject_value'], $groups, true)) { continue; }
            if ($g['subject_kind'] === 'sector' && !in_array((int) $g['subject_value'], $sectors, true)) { continue; }
            $scope = $g['scope_kind'];
            if ($scope === 'all') { return true; }
            if ($scope === 'own' && $post && (int) $post->post_author === get_current_user_id()) { return true; }
            if ($scope === 'sector') {
                $sector=(int) $g['sector_id'];
                if ($sector && in_array($sector, $sectors, true) && ($operation === 'create' || in_array($sector,$item_sectors,true))) { return true; }
            }
            if ($scope === 'items' && $item && in_array($item, array_map('absint', explode(',', (string)$g['item_ids'])), true)) { return true; }
        }
        return false;
    }
    public static function has_management_access() {
        if (SMTT_Intranet_Corporate_Identity::is_admin()) { return true; }
        foreach (self::modules() as $key=>$module) {
            foreach (array('create','edit','delete') as $op) { if (self::can($key,$op)) { return true; } }
        }
        // A scoped grant may require an item to prove permission; find a matching existing item.
        $identity = SMTT_Intranet_Corporate_Identity::current();
        if (is_wp_error($identity)) { return false; }
        global $wpdb; $table=self::table();
        $values=array_merge(array($identity['sid']), (array)($identity['groups'] ?? array()));
        $sector_ids=self::sector_ids_for_identity($identity);
        $values=array_merge($values,array_map('strval',$sector_ids));
        if (!$values) { return false; }
        $placeholders=implode(',',array_fill(0,count($values),'%s'));
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$table} WHERE subject_value IN ({$placeholders}) AND operation IN ('create','edit','delete') AND (expires_at IS NULL OR expires_at > %s) LIMIT 1",
            array_merge($values,array(current_time('mysql',true)))
        ));
    }
    public static function audit($action, $target, $result='ok') {
        global $wpdb;
        $identity = SMTT_Intranet_Corporate_Identity::current();
        $wpdb->insert(self::audit_table(), array(
            'actor_sid'=>is_wp_error($identity)?'':substr((string)$identity['sid'],0,100),
            'actor_wp'=>get_current_user_id(), 'action_name'=>substr(sanitize_key($action),0,90),
            'target'=>substr(sanitize_text_field((string)$target),0,190),
            'result_name'=>substr(sanitize_key($result),0,24), 'created_at'=>current_time('mysql',true)
        ));
    }
}
