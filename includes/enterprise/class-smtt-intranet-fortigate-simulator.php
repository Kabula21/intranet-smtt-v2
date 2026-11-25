<?php
/** Fila simulada, exclusivamente auditável; nunca emite comandos de rede. */
if (!defined('ABSPATH')) { exit; }
final class SMTT_Intranet_FortiGate_Simulator {
    const CRON='smtt_intranet_fortigate_simulation';
    public static function table() { global $wpdb; return $wpdb->prefix.'smtt_intranet_network_simulation'; }
    public static function register() {
        add_action('init',array(__CLASS__,'install'),8);
        add_action('init',array(__CLASS__,'schedule'),25);
        add_action(self::CRON,array(__CLASS__,'expire'));
        add_action('smtt_intranet_gate_verified_pass',array(__CLASS__,'record'),10,2);
    }
    public static function install() {
        if (get_option('smtt_network_simulator_schema')==='1') { return; }
        global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $table=self::table(); $collate=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            identity_sid VARCHAR(100) NOT NULL,
            identity_upn VARCHAR(190) NOT NULL,
            event_type VARCHAR(22) NOT NULL,
            expires_at DATETIME NULL,
            source_event BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY identity (identity_sid),
            KEY expires (expires_at)
        ) {$collate};");
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) {
            update_option('smtt_network_simulator_schema','1',false);
        }
    }
    public static function schedule() {
        if (!wp_next_scheduled(self::CRON)) { wp_schedule_event(time()+600,'smtt_fifteen_minutes',self::CRON); }
    }
    public static function record($identity,$passage) {
        if (!is_array($identity) || empty($identity['sid']) || empty($identity['upn'])) { return; }
        global $wpdb; $table=self::table();
        $expires='';
        if (is_array($passage) && isset($passage['valid_until'])) { $expires=(string)$passage['valid_until']; }
        if (!$expires) { return; }
        $date=DateTimeImmutable::createFromFormat('Y-m-d H:i:s',$expires,wp_timezone());
        if (!$date) { return; }
        $wpdb->insert($table,array('identity_sid'=>substr($identity['sid'],0,100), 'identity_upn'=>substr($identity['upn'],0,190),
            'event_type'=>'allow_expected', 'expires_at'=>$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'source_event'=>absint($passage['id']??0),'created_at'=>current_time('mysql',true)));
    }
    public static function expire() {
        global $wpdb; $table=self::table(); $now=current_time('mysql',true);
        // Ledger-only reconciliation; retains original passage and records expected revocation.
        $rows=$wpdb->get_results($wpdb->prepare(
            "SELECT a.* FROM {$table} a LEFT JOIN {$table} b ON a.identity_sid=b.identity_sid AND b.event_type='revoke_expected' AND b.source_event=a.id WHERE a.event_type='allow_expected' AND a.expires_at<%s AND b.id IS NULL ORDER BY a.id ASC LIMIT 500",$now),ARRAY_A);
        foreach((array)$rows as $row) {
            $wpdb->insert($table,array('identity_sid'=>$row['identity_sid'],'identity_upn'=>$row['identity_upn'],
                'event_type'=>'revoke_expected','expires_at'=>$row['expires_at'],'source_event'=>(int)$row['id'],'created_at'=>$now));
        }
    }
    public static function recent($limit=40) {
        global $wpdb; $table=self::table();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",min(100,max(1,absint($limit)))),ARRAY_A);
    }
}
