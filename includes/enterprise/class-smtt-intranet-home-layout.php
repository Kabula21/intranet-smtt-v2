<?php
/** Organizador da Home: apresentação por card, sem alterar ou eliminar conteúdos existentes. */
if (!defined('ABSPATH')) { exit; }

final class SMTT_Intranet_Home_Layout {
    const OPT_PUBLISHED = 'smtt_home_layout_published_v1';
    const OPT_DRAFT     = 'smtt_home_layout_draft_v1';
    const OPT_PREVIOUS  = 'smtt_home_layout_previous_v1';
    const NONCE         = 'smtt_home_layout_update';

    public static function register() {
        add_action('admin_post_smtt_home_layout', array(__CLASS__, 'save'));
    }

    /** IDs estáveis: os módulos existentes continuam com seu conteúdo/HTML próprio. */
    public static function cards() {
        $cards = array(
            'access'    => array('label'=>'Central de Acessos', 'description'=>'Sistemas e pastas por setor', 'selector'=>'#central-acessos-card', 'span'=>5, 'height'=>370),
            'agenda'    => array('label'=>'Agenda', 'description'=>'Eventos e calendário', 'selector'=>'#agenda', 'span'=>5, 'height'=>370),
            'mural'     => array('label'=>'Mural Interno', 'description'=>'Comunicados e destaques visuais', 'selector'=>'#mural', 'span'=>9, 'height'=>370),
            'wiki'      => array('label'=>'Wiki SMTT', 'description'=>'Conhecimento institucional', 'selector'=>'#base-interna', 'span'=>5, 'height'=>370),
            'links'     => array('label'=>'Links úteis', 'description'=>'Links e serviços frequentes', 'selector'=>'#links-uteis-card', 'span'=>5, 'height'=>250),
            'news'      => array('label'=>'Notícias', 'description'=>'Integração com o portal institucional', 'selector'=>'#comunicados', 'span'=>13, 'height'=>250),
            'birthdays' => array('label'=>'Aniversariantes', 'description'=>'Colaboradores do mês', 'selector'=>'#aniversariantes', 'span'=>6, 'height'=>250),
        );
        if (taxonomy_exists('smtt_catalog')) {
            $terms = get_terms(array('taxonomy'=>'smtt_catalog','hide_empty'=>false,'orderby'=>'term_id','order'=>'ASC'));
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $id = (int) $term->term_id;
                    $cards['catalog-'.$id] = array(
                        'label'       => $term->name,
                        'description' => 'Módulo personalizado',
                        'selector'    => '#smtt-catalog-'.$id,
                        'span'        => 8,
                        'height'      => 300,
                        'default_visible' => get_term_meta($id,'_smtt_portal_public',true) === '1',
                    );
                }
            }
        }
        return $cards;
    }

    public static function defaults() {
        $result = array();
        foreach (self::cards() as $key=>$c) {
            $result[] = array('id'=>$key, 'visible'=>isset($c['default_visible']) ? (bool)$c['default_visible'] : true,
                'span'=>(int)$c['span'], 'height'=>(int)$c['height'], 'title'=>'', 'subtitle'=>'', 'icon'=>'', 'tone'=>'default');
        }
        return $result;
    }

    /** Reconcilia módulos criados/removidos sem perder a ordem escolhida pelo CPD. */
    public static function normalize($saved) {
        $catalog = self::cards();
        $out = array(); $used = array();
        if (is_array($saved)) {
            foreach (array_slice($saved,0,200) as $entry) {
                if (!is_array($entry)) { continue; }
                $id = isset($entry['id']) ? (string)$entry['id'] : '';
                if (!isset($catalog[$id]) || isset($used[$id])) { continue; }
                $used[$id] = true;
                $valid_sizes = array(5,6,8,9,12,13,16,18,24);
                $span = isset($entry['span']) ? (int)$entry['span'] : (int)$catalog[$id]['span'];
                $height = isset($entry['height']) ? (int)$entry['height'] : (int)$catalog[$id]['height'];
                $out[] = array('id'=>$id,
                    'visible' => !empty($entry['visible']),
                    'span' => in_array($span,$valid_sizes,true) ? $span : (int)$catalog[$id]['span'],
                    'height' => in_array($height,array(220,250,300,370,430,520),true) ? $height : (int)$catalog[$id]['height'],
                    'title' => isset($entry['title']) ? substr(sanitize_text_field((string)$entry['title']),0,100) : '',
                    'subtitle' => isset($entry['subtitle']) ? substr(sanitize_text_field((string)$entry['subtitle']),0,160) : '',
                    'icon' => (!empty($entry['icon']) && SMTT_Intranet_Icon_Library::valid_value((string)$entry['icon'])) ? (string)$entry['icon'] : '',
                    'tone' => in_array(($entry['tone'] ?? 'default'),array('default','azul','turquesa','verde','amarelo','violeta'),true) ? ($entry['tone'] ?? 'default') : 'default');
            }
        }
        foreach (self::defaults() as $item) {
            if (!isset($used[$item['id']])) { $out[] = $item; }
        }
        return $out;
    }

    public static function published() {
        return self::normalize(get_option(self::OPT_PUBLISHED, array()));
    }
    public static function draft() {
        $data = get_option(self::OPT_DRAFT, null);
        return self::normalize(is_array($data) ? $data : get_option(self::OPT_PUBLISHED,array()));
    }
    public static function preview_requested() {
        if (!isset($_GET['smtt_home_preview']) || (string)$_GET['smtt_home_preview'] !== '1') { return false; }
        if (!is_user_logged_in() || !SMTT_Intranet_Corporate_Identity::is_admin()) { return false; }
        return isset($_GET['_smtt_preview_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_smtt_preview_nonce'])),'smtt_home_layout_preview');
    }
    public static function preview_url() {
        $home = SMTT_Intranet_Home_Routing::url() ?: home_url('/');
        return add_query_arg(array('smtt_home_preview'=>'1','_smtt_preview_nonce'=>wp_create_nonce('smtt_home_layout_preview')),$home);
    }
    public static function effective() {
        return self::preview_requested() ? self::draft() : self::published();
    }
    public static function visible($key) {
        foreach (self::effective() as $item) {
            if ($item['id'] === $key) { return $item['visible']; }
        }
        return false;
    }

    public static function header($key, $fallback_icon, $fallback_title, $fallback_subtitle = '') {
        $entry = null;
        foreach (self::effective() as $card) {
            if ($card['id'] === $key) { $entry = $card; break; }
        }
        $icon = $entry && $entry['icon'] ? SMTT_Intranet_Icon_Library::html($entry['icon']) : $fallback_icon;
        $title = $entry && $entry['title'] !== '' ? $entry['title'] : $fallback_title;
        $subtitle = $entry && $entry['subtitle'] !== '' ? $entry['subtitle'] : $fallback_subtitle;
        return '<h3>'.$icon.esc_html($title).'</h3>'.($subtitle !== '' ? '<p>'.esc_html($subtitle).'</p>' : '');
    }

    /** CSS escapado e exclusivamente baseado na lista de IDs conhecida, sem estilos arbitrários. */
    public static function inline_style() {
        $catalog = self::cards();
        $css = '';
        foreach (self::effective() as $i=>$item) {
            if (!isset($catalog[$item['id']])) { continue; }
            $selector = $catalog[$item['id']]['selector']; // nunca vem do POST.
            $tone_colors = array('azul'=>'#1c73ac','turquesa'=>'#0a9ead','verde'=>'#21875a','amarelo'=>'#c49324','violeta'=>'#7f63b5');
            $css .= '.smtt-dashboard-grid.smtt-home-layout-active > '.$selector.'{' .
                '--smtt-layout-order:'.(int)$i.';--smtt-layout-span:'.(int)$item['span'].';--smtt-layout-height:'.(int)$item['height'].'px;'.
                (!$item['visible'] ? 'display:none!important;' : '').
                (isset($tone_colors[$item['tone']]) ? 'border-top:3px solid '.$tone_colors[$item['tone']].'!important;' : '').'}';
            // Evita links âncora para cards ocultos na navegação lateral.
            $href = array('agenda'=>'#agenda','mural'=>'#mural','wiki'=>'#base-interna','news'=>'#comunicados','birthdays'=>'#aniversariantes');
            if (!$item['visible'] && isset($href[$item['id']])) {
                $css .= 'body.smtt-intranet-page .smtt-sidebar-nav a[href="'.$href[$item['id']].'"]{display:none!important;}';
            }
        }
        return $css;
    }

    public static function save() {
        if (!is_user_logged_in() || !SMTT_Intranet_Corporate_Identity::is_admin()) {
            wp_die('Somente o CPD autorizado pode modificar a Home.','Acesso negado',array('response'=>403));
        }
        check_admin_referer(self::NONCE);
        $operation = sanitize_key(wp_unslash($_POST['layout_operation'] ?? ''));
        if (!in_array($operation,array('draft','publish','restore'),true)) {
            wp_die('Operação inválida.','',array('response'=>400));
        }
        if ($operation==='restore') {
            $previous = get_option(self::OPT_PREVIOUS,null);
            if (!is_array($previous)) { wp_die('Não há um layout anterior disponível.','',array('response'=>400)); }
            $old = get_option(self::OPT_PUBLISHED,array());
            update_option(self::OPT_PUBLISHED,self::normalize($previous),false);
            update_option(self::OPT_PREVIOUS,self::normalize($old),false);
            update_option(self::OPT_DRAFT,self::normalize($previous),false);
        } else {
            $raw = isset($_POST['layout_json']) ? wp_unslash($_POST['layout_json']) : '';
            if (!is_string($raw) || strlen($raw)>40000) { wp_die('Layout inválido.','',array('response'=>400)); }
            $parsed = json_decode($raw,true);
            if (!is_array($parsed)) { wp_die('Não foi possível interpretar os cards enviados.','',array('response'=>400)); }
            $normalized = self::normalize($parsed);
            update_option(self::OPT_DRAFT,$normalized,false);
            if ($operation==='publish') {
                update_option(self::OPT_PREVIOUS,self::published(),false);
                update_option(self::OPT_PUBLISHED,$normalized,false);
            }
        }
        SMTT_Intranet_Policy::audit('home_layout_'.$operation, 'home');
        (new SMTT_Intranet_Public())->clear_home_cache();
        wp_safe_redirect(SMTT_Intranet_Manager::url(array('section'=>'home_layout','msg'=>($operation==='draft'?'layout_draft':($operation==='restore'?'layout_restored':'layout_published')))));
        exit;
    }
}
