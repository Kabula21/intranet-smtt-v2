<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Activator {

    public static function activate() {
       
        SMTT_Intranet_Capabilities::register();

        $sector_folders            = new SMTT_Intranet_Sector_Folders();
        $internal_systems          = new SMTT_Intranet_Internal_Systems();
        $sectors_taxonomy          = new SMTT_Intranet_Sectors_Taxonomy();
        $useful_links              = new SMTT_Intranet_Useful_Links();
        $documents                 = new SMTT_Intranet_Documents();
        $document_categories       = new SMTT_Intranet_Document_Categories();
        $agenda                    = new SMTT_Intranet_Agenda();
        $agenda_spaces             = new SMTT_Intranet_Agenda_Spaces();
        $agenda_categories         = new SMTT_Intranet_Agenda_Categories();
        $knowledge_base            = new SMTT_Intranet_Knowledge_Base();
        $knowledge_base_categories = new SMTT_Intranet_Knowledge_Base_Categories();
        $mural                     = new SMTT_Intranet_Mural();
        $mural_categories          = new SMTT_Intranet_Mural_Categories();
        $birthdays                 = new SMTT_Intranet_Birthdays();


        $sector_folders->register_post_type();
        $internal_systems->register_post_type();
        $sectors_taxonomy->register_taxonomy();
        $useful_links->register_post_type();
        $documents->register_post_type();
        $document_categories->register_taxonomy();
        $agenda->register_post_type();
        $agenda_spaces->register_taxonomy();
        $agenda_categories->register_taxonomy();
        $knowledge_base->register_post_type();
        $knowledge_base_categories->register_taxonomy();
        $mural->register_post_type();
        $mural_categories->register_taxonomy();
        $birthdays->register_post_type();
        $birthdays->register_settings();

        flush_rewrite_rules();
        self::create_ramais_tables();

        if (class_exists('SMTT_Intranet_Access_Gate')) {
            SMTT_Intranet_Access_Gate::create_table();
        }
        
    }


    private static function create_ramais_tables() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $table_ramais = $wpdb->prefix . 'smtt_intranet_ramais';
    $table_imports = $wpdb->prefix . 'smtt_intranet_ramais_imports';

    $sql_ramais = "CREATE TABLE {$table_ramais} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(190) NOT NULL,
        setor VARCHAR(190) DEFAULT NULL,
        cargo VARCHAR(190) DEFAULT NULL,
        ramal VARCHAR(50) NOT NULL,
        telefone_direto VARCHAR(50) DEFAULT NULL,
        whatsapp VARCHAR(50) DEFAULT NULL,
        email VARCHAR(190) DEFAULT NULL,
        unidade VARCHAR(190) DEFAULT NULL,
        sala VARCHAR(190) DEFAULT NULL,
        observacao TEXT DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'ativo',
        import_batch_id BIGINT(20) UNSIGNED DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_nome (nome),
        KEY idx_setor (setor),
        KEY idx_ramal (ramal),
        KEY idx_status (status),
        KEY idx_import_batch_id (import_batch_id)
    ) {$charset_collate};";

    $sql_imports = "CREATE TABLE {$table_imports} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        arquivo_nome VARCHAR(255) DEFAULT NULL,
        total_linhas INT UNSIGNED NOT NULL DEFAULT 0,
        total_inseridos INT UNSIGNED NOT NULL DEFAULT 0,
        total_atualizados INT UNSIGNED NOT NULL DEFAULT 0,
        total_ignorados INT UNSIGNED NOT NULL DEFAULT 0,
        total_erros INT UNSIGNED NOT NULL DEFAULT 0,
        relatorio_json LONGTEXT DEFAULT NULL,
        importado_por BIGINT(20) UNSIGNED DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_importado_por (importado_por),
        KEY idx_created_at (created_at)
    ) {$charset_collate};";

    dbDelta($sql_ramais);
    dbDelta($sql_imports);
}
}