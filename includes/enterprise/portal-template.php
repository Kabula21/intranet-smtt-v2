<?php
/** Central Administrativa frontend e Minha Gestão — dados e ações reais, protegidos no servidor. */
if (!defined('ABSPATH')) { exit; }
$is_mine = get_query_var('smtt_intranet_my_management') ? true : false;
$admin = SMTT_Intranet_Corporate_Identity::is_admin();
$sections = $is_mine ? array('modules'=>'Meus conteúdos') : array('overview'=>'Início','modules'=>'Módulos','catalogs'=>'Criar módulos','home_layout'=>'Layout da Home','apps'=>'Menu de Aplicativos','sectors'=>'Setores e AD','permissions'=>'Permissões','icons'=>'Ícones e marcas','settings'=>'Configurações','chat'=>'Chat SMTT','gate'=>'Access Gate','audit'=>'Auditoria');
$section = sanitize_key(wp_unslash($_GET['section']??($is_mine?'modules':'overview')));
if (!isset($sections[$section])) { $section=array_key_first($sections); }
$modules = SMTT_Intranet_Policy::modules();
$module = sanitize_key(wp_unslash($_GET['module']??'systems'));
if (!isset($modules[$module])) { $module='systems'; }
$edit_id=absint($_GET['edit']??0);
$edit_post=$edit_id && $module!=='ramais' ? get_post($edit_id) : null;
if ($edit_post && $edit_post->post_type!==SMTT_Intranet_Policy::post_type($module)) { $edit_post=null; }
$edit_ramal=($module==='ramais'&&$edit_id)?SMTT_Intranet_Ramais_Repository::get_by_id($edit_id):null;
if ($edit_id && !$edit_post && !$edit_ramal) { $edit_id=0; }
$notice=sanitize_key(wp_unslash($_GET['msg']??$_GET['smtt_notice']??''));
$icon_manifest = in_array($section, array('modules', 'catalogs', 'icons', 'home_layout', 'apps'), true) ? SMTT_Intranet_Icon_Library::manifest() : array();
function smtt_portal_action($action) { echo esc_url(admin_url('admin-post.php')); }
function smtt_portal_nonce($action) { wp_nonce_field('smtt_portal_'.$action); }
function smtt_portal_link($args=array(),$mine=false) { echo esc_url(SMTT_Intranet_Manager::url($args,$mine)); }
function smtt_portal_inline_url($args=array(),$mine=false) { return esc_url(SMTT_Intranet_Manager::url($args,$mine)); }
function smtt_portal_help_tip($title,$content,$position='right') {
    $title = (string) $title;
    $content = (string) $content;
    echo '<span class="smtt-ep__info-tip smtt-ep__info-tip--'.esc_attr($position).'" tabindex="0" aria-label="'.esc_attr(wp_strip_all_tags($title)).'">';
    echo '<span class="smtt-ep__info-tip-trigger" aria-hidden="true">i</span>';
    echo '<span class="smtt-ep__info-tip-card"><strong>'.esc_html($title).'</strong><span>'.wp_kses_post($content).'</span></span>';
    echo '</span>';
}
function smtt_portal_field($key,$definition,$value) {
    $label=$definition[0]; $type=$definition[1]; $id='smtt-field-'.$key;
    $full = in_array($type,array('textarea'),true) ? ' smtt-ep__field--full' : '';
    echo '<div class="smtt-ep__field smtt-ep__form-field'.$full.'">';
    if ($type==='checkbox') {
        echo '<label class="smtt-ep__check" for="'.esc_attr($id).'">';
        echo '<input type="checkbox" name="'.esc_attr($key).'" id="'.esc_attr($id).'" value="1" '.checked($value,'1',false).'> ' . esc_html($label);
        echo '</label>';
    } else {
        echo '<label for="'.esc_attr($id).'">'.esc_html($label).'</label>';
        if ($type==='textarea') { echo '<textarea id="'.esc_attr($id).'" name="'.esc_attr($key).'" rows="3">'.esc_textarea($value).'</textarea>'; }
        elseif ($type==='icon') { echo '<div class="smtt-ep__iconinput"><input data-icon-value id="'.esc_attr($id).'" name="'.esc_attr($key).'" value="'.esc_attr($value).'" placeholder="Selecione na biblioteca de ícones"><button type="button" data-open-icons>Escolher ícone</button></div>'; }
        elseif (strpos($type,'select:')===0) { echo '<select name="'.esc_attr($key).'" id="'.esc_attr($id).'">'; foreach (explode('|',substr($type,7)) as $option) { echo '<option value="'.esc_attr($option).'" '.selected($value,$option,false).'>'.esc_html(ucfirst($option)).'</option>'; } echo '</select>'; }
        else { echo '<input id="'.esc_attr($id).'" name="'.esc_attr($key).'" type="'.esc_attr($type).'" value="'.esc_attr($value).'">'; }
    }
    echo '</div>';
}

$sidebar_children = array();
$module_children = array();
foreach ($modules as $child_key=>$child_module) {
    if (!$admin && !SMTT_Intranet_Policy::can($child_key,'create') && !SMTT_Intranet_Policy::can($child_key,'edit') && !SMTT_Intranet_Policy::can($child_key,'delete') && !SMTT_Intranet_Policy::has_management_access()) { continue; }
    $module_children[] = array(
        'label' => $child_module[0],
        'url'   => smtt_portal_inline_url(array('section'=>'modules','module'=>$child_key), $is_mine),
        'active'=> ($section==='modules' && $module===$child_key),
    );
}
if ($module_children) {
    $sidebar_children['modules'] = array(
        'label' => 'Selecionar o módulo',
        'items' => $module_children,
    );
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html(($is_mine ? 'Minha Gestão' : 'Administração SMTT') . ' - ' . get_bloginfo('name')); ?></title>
<?php wp_head(); ?>
</head>
<body class="smtt-enterprise-page">
<main id="smtt-enterprise-admin" class="smtt-ep <?php echo (!$is_mine && $section==='overview') ? 'smtt-ep--inicio' : ''; ?>" data-icon-base="<?php echo esc_attr(SMTT_INTRANET_URL . 'assets/enterprise/icons/'); ?>">
    <div class="smtt-ep__shell">
        <aside class="smtt-ep__sidebar">
            <a class="smtt-ep__brand" href="<?php echo esc_url(SMTT_Intranet_Home_Routing::url() ?: home_url('/')); ?>" aria-label="SMTT Intranet V2 — voltar à página inicial"><span class="smtt-ep__brandlogo"><img src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/public/img/logo-smtt.png'); ?>" alt="" width="500" height="216"></span><span class="smtt-ep__brandname">SMTT <small>INTRANET V2</small></span></a>
            <p class="smtt-ep__eyebrow"><?php echo $is_mine?'ÁREA DE TRABALHO':'CENTRAL ADMINISTRATIVA'; ?></p>
            <nav aria-label="Seções da administração">
                <?php $icons=array('overview'=>'dashicons-dashboard','modules'=>'dashicons-screenoptions','catalogs'=>'dashicons-plus-alt2','home_layout'=>'dashicons-layout','apps'=>'dashicons-grid-view','sectors'=>'dashicons-networking','permissions'=>'dashicons-shield','icons'=>'dashicons-art','settings'=>'dashicons-admin-generic','chat'=>'dashicons-format-chat','gate'=>'dashicons-lock','audit'=>'dashicons-list-view'); ?>
                <?php foreach ($sections as $key=>$title): $is_active_section = ($section===$key); ?>
                    <div class="smtt-ep__navgroup <?php echo $is_active_section?'is-active':''; ?>">
                        <a class="smtt-ep__nav <?php echo $is_active_section?'is-active':''; ?>" href="<?php smtt_portal_link(array('section'=>$key),$is_mine); ?>"><span class="dashicons <?php echo esc_attr($icons[$key]??'dashicons-admin-tools'); ?>"></span><span class="smtt-ep__navlabel"><?php echo esc_html($title); ?></span></a>
                        <?php if ($is_active_section && !empty($sidebar_children[$key]['items'])): ?>
                            <div class="smtt-ep__subnav" aria-label="<?php echo esc_attr($sidebar_children[$key]['label']); ?>">
                                <?php foreach ($sidebar_children[$key]['items'] as $child): ?>
                                    <a class="smtt-ep__subnavlink <?php echo !empty($child['active'])?'is-active':''; ?>" href="<?php echo esc_url($child['url']); ?>"><?php echo esc_html($child['label']); ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
            <div class="smtt-ep__sidebar-footer">
                <a class="smtt-ep__return" href="<?php echo esc_url(SMTT_Intranet_Home_Routing::url() ?: home_url('/')); ?>">← Voltar à Home</a>
                <small class="smtt-ep__copyright">v<?php echo esc_html(SMTT_INTRANET_VERSION); ?> · Acesso corporativo</small>
            </div>
        </aside>
        <div class="smtt-ep__body">
            <header class="smtt-ep__header">
                <div><p class="smtt-ep__eyebrow"><?php echo $is_mine?'ACESSOS DELEGADOS':'PAINEL DO CPD'; ?></p><div class="smtt-ep__titleline smtt-ep__page-titleline"><h1><?php echo esc_html($sections[$section]); ?></h1><?php if ($section==='chat' && $admin) { smtt_portal_help_tip('Privacidade e acesso', 'O CPD configura o serviço, mas não recebe acesso automático ao conteúdo das conversas privadas. Cada conversa só pode ser consultada por seus participantes autenticados.<br><br>O diretório mostra usuários existentes no WordPress. Após implantar SSO, as contas corporativas precisarão estar vinculadas ou provisionadas no WordPress.', 'right'); } ?></div><p><?php echo $is_mine?'Apenas conteúdos e operações liberados pelo CPD.':'Administre os conteúdos, setores, módulos, permissões e integrações da SMTT.'; ?></p></div>
                <span class="smtt-ep__status"><?php echo $is_mine?'Gestão delegada':'CPD · Administrador'; ?></span>
            </header>
            <?php if ($admin && SMTT_Intranet_Corporate_Identity::is_local_preview_admin()): ?>
                <div class="smtt-ep__message" role="status">Prévia local: acesso concedido ao administrador WordPress apenas neste ambiente de teste. Active Directory, SSO e FortiGate ainda não foram validados. Em produção, a Central exige os grupos corporativos.</div>
            <?php endif; ?>
            <?php if ($notice): ?><div class="smtt-ep__message"><?php echo esc_html(array('saved'=>'Alterações salvas e registradas.','deleted'=>'Registro excluído conforme solicitado.','imported'=>'Importação concluída.','sync_ok'=>'Sincronização concluída.','sync_error'=>'Não foi possível sincronizar. Verifique o diagnóstico.','groups_saved'=>'Grupos de administração atualizados.','chat_saved'=>'Configurações do Chat SMTT salvas.','chat_group_created'=>'Grupo criado pelo CPD.','chat_group_updated'=>'Grupo atualizado.','layout_draft'=>'Rascunho do layout salvo. Publique quando estiver pronto.','layout_published'=>'Layout publicado na Home.','layout_restored'=>'Layout anterior restaurado e publicado.','apps_added'=>'Aplicativo adicionado ao rascunho.','apps_draft'=>'Menu de aplicativos salvo em rascunho.','apps_removed'=>'Aplicativo retirado do rascunho.','apps_published'=>'Menu de aplicativos publicado na Home.','apps_restored'=>'Publicação anterior de aplicativos restaurada.')[$notice]??'Operação processada.'); ?></div><?php endif; ?>

            <?php if ($section==='overview' && $admin): ?>
                <section class="smtt-ep__inicio" aria-labelledby="smtt-inicio-title">
                    <div class="smtt-ep__inicio-network" aria-hidden="true">
                        <span class="smtt-ep__inicio-orbit smtt-ep__inicio-orbit--one"></span>
                        <span class="smtt-ep__inicio-orbit smtt-ep__inicio-orbit--two"></span>
                        <span class="smtt-ep__inicio-beam smtt-ep__inicio-beam--one"></span>
                        <span class="smtt-ep__inicio-beam smtt-ep__inicio-beam--two"></span>
                    </div>
                    <div class="smtt-ep__inicio-content">
                        <img class="smtt-ep__inicio-logo" src="<?php echo esc_url(SMTT_INTRANET_URL . 'assets/public/img/logo-smtt.png'); ?>" alt="SMTT Aracaju" width="500" height="216">
                        <span class="smtt-ep__inicio-rule" aria-hidden="true"></span>
                        <h1 id="smtt-inicio-title">Intranet Administração</h1>
                    </div>
                </section>

            <?php elseif ($section==='home_layout' && $admin): ?>
                <?php include SMTT_INTRANET_PATH . 'includes/enterprise/home-layout-template.php'; ?>

            <?php elseif ($section==='apps' && $admin): SMTT_Intranet_App_Launcher::admin_panel(); ?>
            <?php elseif ($section==='modules'): ?>
                <section class="smtt-ep__panel">
                    <div class="smtt-ep__section-heading"><div><h2><?php echo esc_html($modules[$module][0]); ?></h2><p>Registros existentes mantidos; nenhuma migração de dados necessária.</p></div>
                    </div>
                    <?php
                    $args=array('post_type'=>$modules[$module][1],'post_status'=>array('publish','draft','pending','private'),'posts_per_page'=>100,'orderby'=>'date','order'=>'DESC');
                    $items=$module==='ramais' ? SMTT_Intranet_Ramais_Repository::get_all(array('limit'=>100)) : get_posts($args);
                    $displayed=0;
                    ?>
                    <div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><thead><tr><th>Registro</th><th>Identificação</th><th>Ações</th></tr></thead><tbody>
                        <?php foreach ((array)$items as $item):
                            $iid=$module==='ramais'?(int)$item['id']:(int)$item->ID;
                            $label=$module==='ramais'?$item['nome']:$item->post_title;
                            $subtitle=$module==='ramais'?$item['ramal']:('#'.$iid.' · '.get_post_meta($iid,'_smtt_status',true));
                            if ($module==='custom') { $term_names=wp_get_object_terms($iid,'smtt_catalog',array('fields'=>'names')); $subtitle.= ' · '.((!is_wp_error($term_names)&&$term_names)?implode(', ',$term_names):'Sem módulo vinculado'); }
                            if (!$admin && !SMTT_Intranet_Policy::can($module,'view',$iid) && !SMTT_Intranet_Policy::can($module,'edit',$iid) && !SMTT_Intranet_Policy::can($module,'delete',$iid)) { continue; }
                            $displayed++;
                        ?>
                        <tr><td><strong><?php echo esc_html($label); ?></strong></td><td><?php echo esc_html($subtitle); ?></td><td class="smtt-ep__actions">
                            <?php if (in_array($module,array('documents','knowledge'),true) && SMTT_Intranet_Content_Files::link($iid)): ?><a href="<?php echo esc_url(SMTT_Intranet_Content_Files::link($iid)); ?>" target="_blank" rel="noopener">Ver arquivo</a><?php endif; ?>
                            <?php if ($module==='knowledge' && (get_post_meta($iid,'_smtt_content_type',true)==='artigo' || (!get_post_meta($iid,'_smtt_content_type',true) && !get_post_meta($iid,'_smtt_article_url',true)))): ?><a href="<?php echo esc_url(admin_url('admin-post.php?action=smtt_content_read&item='.$iid)); ?>" target="_blank" rel="noopener">Ver artigo</a><?php endif; ?>
                            <?php if (SMTT_Intranet_Policy::can($module,'edit',$iid)): ?><a href="<?php smtt_portal_link(array('section'=>'modules','module'=>$module,'edit'=>$iid),$is_mine); ?>">Editar</a><?php endif; ?>
                            <?php if (SMTT_Intranet_Policy::can($module,'delete',$iid)): ?><form method="post" action="<?php smtt_portal_action('delete'); ?>" data-confirm="Tem certeza que deseja excluir este registro?"><input type="hidden" name="action" value="smtt_portal_delete"><input type="hidden" name="module" value="<?php echo esc_attr($module); ?>"><input type="hidden" name="item_id" value="<?php echo $iid; ?>"><?php smtt_portal_nonce('delete'); ?><button type="submit" class="smtt-ep__danger">Excluir</button></form><?php endif; ?>
                        </td></tr><?php endforeach; ?>
                    </tbody></table><?php if (!$displayed): ?><p class="smtt-ep__empty">Nenhum registro disponível para seu perfil.</p><?php endif; ?></div>
                    <p class="smtt-ep__note">Exibindo até 100 registros por módulo. Conteúdos excluídos do WordPress vão para a lixeira; ramais são excluídos da tabela própria.</p>
                </section>
                <?php if ((!$edit_id && SMTT_Intranet_Policy::can($module,'create')) || ($edit_id && SMTT_Intranet_Policy::can($module,'edit',$edit_id))): ?>
                <section class="smtt-ep__panel" id="edit-record">
                    <h2><?php echo $edit_id?'Editar registro':'Novo registro'; ?> · <?php echo esc_html($modules[$module][0]); ?></h2>
                    <form method="post" enctype="multipart/form-data" action="<?php smtt_portal_action('save'); ?>" class="smtt-ep__form" data-smtt-module="<?php echo esc_attr($module); ?>">
                        <input type="hidden" name="action" value="smtt_portal_save"><input type="hidden" name="module" value="<?php echo esc_attr($module); ?>"><input type="hidden" name="item_id" value="<?php echo $edit_id; ?>"><?php smtt_portal_nonce('save'); ?>
                        <?php if ($module==='ramais'):
                            foreach (array('nome'=>'Nome','setor'=>'Setor','cargo'=>'Cargo','ramal'=>'Ramal','telefone_direto'=>'Telefone direto','whatsapp'=>'WhatsApp','email'=>'E-mail','unidade'=>'Unidade','sala'=>'Sala','observacao'=>'Observação') as $field=>$label) { smtt_portal_field($field,array($label,$field==='observacao'?'textarea':($field==='email'?'email':'text')), $edit_ramal[$field]??''); }
                            smtt_portal_field('status',array('Status','select:ativo|inativo'),$edit_ramal['status']??'ativo');
                        else:
                            if ($module==='custom') {
                                $chosen_catalog=absint($_GET['catalog']??0);
                                $assigned=$edit_post?wp_get_object_terms($edit_post->ID,'smtt_catalog',array('fields'=>'ids')):array();
                                if (!is_wp_error($assigned) && $assigned) { $chosen_catalog=(int)$assigned[0]; }
                                echo '<div class="smtt-ep__hint">Associe este item a um módulo criado em <strong>Criar módulos</strong>. O item pode ser um link ou um cartão informativo sem URL.</div>';
                                echo '<div class="smtt-ep__field"><label for="catalog-select">Módulo de destino (obrigatório)</label><select id="catalog-select" name="tax_smtt_catalog" required><option value="">Escolha o módulo...</option>';
                                $custom_terms=get_terms(array('taxonomy'=>'smtt_catalog','hide_empty'=>false));
                                if (!is_wp_error($custom_terms)) { foreach($custom_terms as $term) { echo '<option value="'.(int)$term->term_id.'" '.selected($chosen_catalog,(int)$term->term_id,false).'>'.esc_html($term->name).'</option>'; } }
                                echo '</select></div>';
                            }
                            if (in_array($module,array('systems','folders'),true)) {
                                $current_sectors=$edit_post ? wp_get_object_terms($edit_post->ID,'smtt_sector',array('fields'=>'ids')) : array();
                                $current_sectors=is_wp_error($current_sectors)?array():array_map('intval',$current_sectors);
                                $sectors_for_form=get_terms(array('taxonomy'=>'smtt_sector','hide_empty'=>false,'orderby'=>'name'));
                                if (is_wp_error($sectors_for_form)) { $sectors_for_form=array(); }
                                echo '<div class="smtt-ep__wide smtt-ep__destination"><h3>Onde este '.($module==='systems'?'sistema':'atalho de pasta').' aparecerá?</h3><p>As opções da navbar e da Central de Acessos são independentes. Para a Central, escolha pelo menos um setor.</p>';
                                $show_in_nav=$edit_post ? (string)get_post_meta($edit_post->ID,'_smtt_show_sidebar',true) !== '0' : true;
                                $center_meta=$edit_post ? (string)get_post_meta($edit_post->ID,'_smtt_show_access_center',true) : '';
                                $show_in_center=$edit_post ? ($center_meta === '1' || ($center_meta === '' && !empty($current_sectors))) : false;
                                echo '<div class="smtt-ep__choice-grid smtt-ep__choice-grid--destinations">';
                                echo '<label class="smtt-ep__check"><input type="checkbox" name="show_sidebar" value="1" '.checked($show_in_nav,true,false).'> Exibir na navbar</label>';
                                echo '<label class="smtt-ep__check"><input type="checkbox" name="show_access_center" value="1" '.checked($show_in_center,true,false).'> Exibir na Central de Acessos (por setor)</label>';
                                if ($module==='systems') {
                                    $show_apps=$edit_post && get_post_meta($edit_post->ID,'_smtt_show_app_launcher',true)==='1';
                                    echo '<label class="smtt-ep__check"><input type="checkbox" name="show_app_launcher" value="1" '.checked($show_apps,true,false).'> Exibir no Menu de Aplicativos (9 pontos); organizar em Menu de Aplicativos antes de publicar</label>';
                                }
                                echo '</div>';
                                echo '<label for="smtt-sector-multi">Setores vinculados (pode escolher vários)</label>';
                                if (!$sectors_for_form) {
                                    echo '<p class="smtt-ep__warning">Nenhum setor cadastrado. Antes de publicar na Central, <a href="'.esc_url(SMTT_Intranet_Manager::url(array('section'=>'sectors'))).'">cadastre um setor em Setores e AD</a>.</p>';
                                } else {
                                    echo '<div id="smtt-sector-multi" class="smtt-ep__sector-picks">';
                                    foreach ($sectors_for_form as $sector_obj) {
                                        echo '<label class="smtt-ep__check"><input type="checkbox" name="tax_smtt_sector[]" value="'.(int)$sector_obj->term_id.'" '.checked(in_array((int)$sector_obj->term_id,$current_sectors,true),true,false).'> '.esc_html($sector_obj->name).'</label>';
                                    }
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            smtt_portal_field('title',array('Título','text'),$edit_post?$edit_post->post_title:'');
                            if ($module==='knowledge') {
                                echo '<div class="smtt-ep__rich-editor" id="smtt-wiki-editor"><label for="smtt_portal_content">Artigo interno — conteúdo formatado</label>';
                                wp_editor($edit_post?$edit_post->post_content:'','smtt_portal_content',array('textarea_name'=>'content','media_buttons'=>false,'textarea_rows'=>12,'teeny'=>false,'quicktags'=>true));
                                echo '</div>';
                            } elseif (post_type_supports($modules[$module][1],'editor')) {
                                smtt_portal_field('content',array('Conteúdo (opcional)','textarea'),$edit_post?$edit_post->post_content:'');
                            }
                            foreach ((SMTT_Intranet_Manager::fields()[$module]??array()) as $field=>$definition) {
                                if (in_array($module,array('systems','folders'),true) && in_array($field,array('show_sidebar','show_access_center','show_app_launcher'),true)) { continue; }
                                $current=$edit_post?(string)get_post_meta($edit_post->ID,'_smtt_'.$field,true):'';
                                if (!$edit_post && $field==='status') { $current='ativo'; }
                                if ($module==='knowledge' && $field==='content_type' && !$current) { $current=$edit_post && get_post_meta($edit_post->ID,'_smtt_article_url',true)?'link':'artigo'; }
                                if (!$edit_post && $module==='knowledge' && $field==='public_visible') { $current='1'; }
                                smtt_portal_field($field,$definition,$current);
                            }
                            if (in_array($module,array('documents','knowledge'),true)) {
                                $existing_file=$edit_post?SMTT_Intranet_Content_Files::file_label($edit_post->ID):'';
                                $private_ready=(bool)SMTT_Intranet_Content_Files::private_dir();
                                echo '<div class="smtt-ep__file-panel" data-smtt-upload><label for="smtt-attachment">Enviar arquivo diretamente à Intranet (opcional ao editar)</label><input id="smtt-attachment" type="file" name="smtt_attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.txt,.csv">';
                                echo '<small>Até 15 MB; PDF, Word, Excel, PowerPoint, ODF, TXT ou CSV.</small>';
                                if ($existing_file) { echo '<p>Arquivo anexado: <strong>'.esc_html($existing_file).'</strong></p>'; }
                                if ($private_ready) { echo '<label class="smtt-ep__check"><input type="checkbox" name="storage_private" value="1" '.((!$edit_post || get_post_meta($edit_post->ID,'_smtt_private_file',true))?'checked':'').'> Guardar fora da pasta pública (download autenticado)</label>'; }
                                else { echo '<p class="smtt-ep__warning">Esta instalação ainda não tem diretório privado configurado. Arquivos enviados aqui recebem URL pública no WordPress. Não envie documentos sigilosos antes de configurar o armazenamento protegido no Ubuntu.</p>'; }
                                echo '</div>';
                            }
                            foreach ((SMTT_Intranet_Manager::taxonomies()[$module]??array()) as $tax) {
                                if (!taxonomy_exists($tax) || (in_array($module,array('systems','folders'),true) && $tax==='smtt_sector') || ($module==='custom' && $tax==='smtt_catalog') || (($module==='documents' && $tax==='smtt_document_category') || ($module==='knowledge' && $tax==='smtt_kb_category'))) { continue; }
                                $assigned=$edit_post?wp_get_object_terms($edit_post->ID,$tax,array('fields'=>'ids')):array();
                                $current=!is_wp_error($assigned)&&$assigned?(int)$assigned[0]:0;
                                $terms=get_terms(array('taxonomy'=>$tax,'hide_empty'=>false));
                                if (is_wp_error($terms)) { $terms=array(); }
                                echo '<div class="smtt-ep__field"><label for="tax_'.esc_attr($tax).'">'.esc_html(get_taxonomy($tax)->labels->singular_name).'</label><select name="tax_'.esc_attr($tax).'" id="tax_'.esc_attr($tax).'"><option value="0">Não atribuído</option>';
                                foreach ($terms as $term) { echo '<option value="'.(int)$term->term_id.'" '.selected($current,(int)$term->term_id,false).'>'.esc_html($term->name).'</option>'; }
                                echo '</select></div>';
                            }
                        endif; ?>
                        <button class="smtt-ep__button" type="submit">Salvar registro</button>
                    </form>
                </section>
                <?php endif; ?>
                <?php if (in_array($module,array('ramais','birthdays'),true) && SMTT_Intranet_Policy::can($module,'create')): ?>
                    <section class="smtt-ep__panel"><h2>Importar <?php echo esc_html($modules[$module][0]); ?></h2><p>Utiliza o importador existente: <?php echo $module==='ramais'?'CSV ou XLS':'CSV'; ?>, com atualização dos registros já cadastrados.</p><form action="<?php smtt_portal_action('import'); ?>" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="smtt_portal_import"><input type="hidden" name="module" value="<?php echo esc_attr($module); ?>"><?php smtt_portal_nonce('import'); ?><input type="file" name="import_file" accept="<?php echo $module==='ramais'?'.csv,.xls':'.csv'; ?>" required><button class="smtt-ep__button" type="submit">Importar arquivo</button></form></section>
                <?php endif; ?>

            <?php elseif ($section==='catalogs' && $admin):
                $catalogs=get_terms(array('taxonomy'=>'smtt_catalog','hide_empty'=>false)); if(is_wp_error($catalogs)){$catalogs=array();}
                $selected_id=absint($_GET['catalog']??0);
                $selected_cat=$selected_id?get_term($selected_id,'smtt_catalog'):null;
                if (is_wp_error($selected_cat)) { $selected_cat=null; }
                $selected_icon=$selected_cat?(string)get_term_meta($selected_id,'_smtt_portal_icon',true):'';
                $position=$selected_cat?(string)get_term_meta($selected_id,'_smtt_portal_position',true):'bottom'; ?>
                <section class="smtt-ep__panel"><h2><?php echo $selected_cat?'Editar módulo':'Criar módulo personalizado'; ?></h2><p>Crie um catálogo e gerencie seus itens. Módulos publicados aparecem na Home mesmo quando ainda não possuem links; itens informativos não precisam de URL.</p>
                    <form method="post" action="<?php smtt_portal_action('catalog'); ?>" class="smtt-ep__form"><input type="hidden" name="action" value="smtt_portal_catalog"><input type="hidden" name="term_id" value="<?php echo $selected_cat?(int)$selected_cat->term_id:0; ?>"><?php smtt_portal_nonce('catalog'); ?>
                        <div class="smtt-ep__field"><label for="smtt-cat-name">Nome do módulo</label><input id="smtt-cat-name" name="name" value="<?php echo esc_attr($selected_cat?$selected_cat->name:''); ?>" required maxlength="100" placeholder="Ex.: Sistemas de Fiscalização"></div>
                        <div class="smtt-ep__field"><label for="smtt-cat-description">Descrição</label><textarea id="smtt-cat-description" name="description"><?php echo esc_textarea($selected_cat?$selected_cat->description:''); ?></textarea></div>
                        <?php smtt_portal_field('icon',array('Ícone do módulo','icon'),$selected_icon); ?>
                        <p class="smtt-home-editor__hint">A posição, o tamanho e a visibilidade deste módulo são configurados em <a href="<?php smtt_portal_link(array('section'=>'home_layout')); ?>">Layout da Home</a>. Um módulo novo começa oculto até você publicá-lo no editor.</p>
                        <button type="submit" class="smtt-ep__button">Salvar módulo</button>
                    </form>
                </section>
                <section class="smtt-ep__panel"><h2>Módulos cadastrados</h2><p>A visibilidade é configurada em <a href="<?php smtt_portal_link(array('section'=>'home_layout')); ?>">Layout da Home</a>.</p><div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><thead><tr><th>Módulo</th><th>Exibir na Home</th><th>Itens</th><th>Ações</th></tr></thead><tbody><?php foreach($catalogs as $cat):
                    $count=(int)(new WP_Query(array('post_type'=>'smtt_custom_card','post_status'=>'publish','posts_per_page'=>1,'tax_query'=>array(array('taxonomy'=>'smtt_catalog','field'=>'term_id','terms'=>array($cat->term_id))))))->found_posts; ?>
                    <tr><td><strong><?php echo esc_html($cat->name); ?></strong></td><td><?php echo SMTT_Intranet_Home_Layout::visible('catalog-'.(int)$cat->term_id)?'Sim':'Não'; ?></td><td><?php echo $count; ?></td><td class="smtt-ep__actions"><a href="<?php smtt_portal_link(array('section'=>'catalogs','catalog'=>(int)$cat->term_id)); ?>">Editar</a><a href="<?php smtt_portal_link(array('section'=>'modules','module'=>'custom','catalog'=>(int)$cat->term_id)); ?>">Adicionar item</a><a href="<?php smtt_portal_link(array('section'=>'catalogs','preview'=>(int)$cat->term_id)); ?>">Pré-visualizar</a><form method="post" action="<?php smtt_portal_action('catalog_delete'); ?>" data-confirm="Excluir este módulo personalizado? Os itens serão enviados à lixeira. Esta ação não exclui módulos padrão."><input type="hidden" name="action" value="smtt_portal_catalog_delete"><input type="hidden" name="catalog_id" value="<?php echo (int)$cat->term_id; ?>"><?php wp_nonce_field('smtt_portal_catalog_delete'); ?><button class="smtt-ep__danger" type="submit">Excluir módulo</button></form></td></tr>
                    <?php endforeach; ?></tbody></table></div>
                </section>
                <?php $preview=absint($_GET['preview']??0); if ($preview && term_exists($preview,'smtt_catalog')): ?>
                    <section class="smtt-ep__panel"><h2>Pré-visualização do módulo</h2><?php echo SMTT_Intranet_Custom_Modules::home_html('bottom',$preview); ?></section>
                <?php endif; ?>

            <?php elseif ($section==='sectors' && $admin):
                $sectors=get_terms(array('taxonomy'=>'smtt_sector','hide_empty'=>false)); if(is_wp_error($sectors)){$sectors=array();}
                $groups=SMTT_Intranet_AD::list_objects('group','',500); $ous=SMTT_Intranet_AD::list_objects('ou','',500); ?>
                <div class="smtt-ep__grid">
                    <section class="smtt-ep__panel"><h2>Setores da Intranet</h2><p>OUs novas são detectadas, mas não criam setores públicos automaticamente.</p><div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><thead><tr><th>Setor</th><th>Grupo AD vinculado</th></tr></thead><tbody>
                        <?php foreach($sectors as $term): ?><tr><td><?php echo esc_html($term->name); ?> <small>#<?php echo (int)$term->term_id; ?></small></td><td><form method="post" action="<?php smtt_portal_action('binding'); ?>"><input type="hidden" name="action" value="smtt_portal_binding"><input type="hidden" name="term_id" value="<?php echo (int)$term->term_id; ?>"><?php smtt_portal_nonce('binding'); ?><select name="sid"><option value="">Sem vínculo</option><?php $linked=get_term_meta($term->term_id,'_smtt_ad_group_sid',true); foreach($groups as $g): ?><option value="<?php echo esc_attr($g['object_sid']); ?>" <?php selected($linked,$g['object_sid']); ?>><?php echo esc_html($g['object_name']); ?></option><?php endforeach; ?></select><button type="submit">Vincular</button></form></td></tr><?php endforeach; ?>
                    </tbody></table></div></section>
                    <section class="smtt-ep__panel"><h2>Cadastrar / atualizar setor</h2><form method="post" action="<?php smtt_portal_action('term'); ?>" class="smtt-ep__form"><input type="hidden" name="action" value="smtt_portal_term"><?php smtt_portal_nonce('term'); ?>
                        <div class="smtt-ep__field"><label for="smtt-term-id">ID existente (opcional)</label><input id="smtt-term-id" type="number" name="term_id" min="0" placeholder="Vazio para novo setor"></div>
                        <div class="smtt-ep__field"><label for="smtt-term-name">Nome do setor</label><input id="smtt-term-name" name="name" required maxlength="190"></div>
                        <div class="smtt-ep__field"><label for="smtt-term-short">Sigla</label><input id="smtt-term-short" name="short" maxlength="40"></div>
                        <div class="smtt-ep__field"><label for="smtt-term-parent">ID do setor pai</label><input id="smtt-term-parent" name="parent" type="number" min="0"></div>
                        <div class="smtt-ep__field"><label for="smtt-term-order">Ordem</label><input id="smtt-term-order" name="order" type="number" value="0"></div>
                        <label class="smtt-ep__check"><input type="checkbox" name="visible" value="1" checked> Visível na Central de Acessos</label>
                        <button class="smtt-ep__button" type="submit">Salvar setor</button></form></section>
                </div>
                <section class="smtt-ep__panel"><h2>Catálogo AD sincronizado</h2><div class="smtt-ep__catalog"><div><h3>Grupos de segurança</h3><ul><?php foreach($groups as $g): ?><li><strong><?php echo esc_html($g['object_name']); ?></strong><small><?php echo esc_html($g['object_dn']); ?></small></li><?php endforeach; ?></ul></div><div><h3>Unidades Organizacionais</h3><ul><?php foreach($ous as $o): ?><li><strong><?php echo esc_html($o['object_name']); ?></strong><small><?php echo esc_html($o['object_dn']); ?></small></li><?php endforeach; ?></ul></div></div></section>
                <?php $sync=get_option(SMTT_Intranet_AD::OPT_SYNC,array()); ?>
                <section class="smtt-ep__panel"><h2>Diagnóstico de sincronização</h2><p>Última execução: <?php echo esc_html($sync['at']??'Ainda não realizada'); ?>. <?php echo esc_html(get_option('smtt_intranet_ad_last_error','')); ?></p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="smtt_enterprise_sync_ad"><?php wp_nonce_field('smtt_enterprise_sync_ad'); ?><button class="smtt-ep__button">Sincronizar AD agora</button></form></section>
                <section class="smtt-ep__panel"><h2>Grupos administrativos</h2><p>Exige associação simultânea ao CPD e ao grupo exclusivo de administradores.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="smtt-ep__form"><input type="hidden" name="action" value="smtt_enterprise_set_groups"><?php wp_nonce_field('smtt_enterprise_set_groups'); ?><?php foreach(array('cpd_sid'=>'Grupo de referência CPD','admin_sid'=>'Grupo exclusivo de administração') as $key=>$label): ?><div class="smtt-ep__field"><label for="smtt-admin-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label><select id="smtt-admin-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>"><option value="">Selecione um grupo</option><?php foreach($groups as $g): ?><option value="<?php echo esc_attr($g['object_sid']); ?>" <?php selected(get_option($key==='cpd_sid'?SMTT_Intranet_AD::OPT_CPD:SMTT_Intranet_AD::OPT_ADMINS,''),$g['object_sid']); ?>><?php echo esc_html($g['object_name']); ?></option><?php endforeach; ?></select></div><?php endforeach; ?><button class="smtt-ep__button">Atualizar grupos</button></form><p class="smtt-ep__note">A configuração inicial dos grupos exige bootstrap técnico pelo WP-CLI.</p></section>

            <?php elseif ($section==='permissions' && $admin):
                global $wpdb; $t=SMTT_Intranet_Policy::table(); $grants=$wpdb->get_results("SELECT * FROM {$t} ORDER BY id DESC LIMIT 100",ARRAY_A);
                $sectors=get_terms(array('taxonomy'=>'smtt_sector','hide_empty'=>false)); if(is_wp_error($sectors)){$sectors=array();}
                $groups=SMTT_Intranet_AD::list_objects('group','',500);
            ?>
                <section class="smtt-ep__panel smtt-ep__permissions-panel">
                    <div class="smtt-ep__section-heading">
                        <div><h2>Nova permissão delegada</h2><p>Defina o destinatário, o módulo e o alcance do acesso. A gestão delegada não concede acesso à Central do CPD.</p></div>
                        <?php smtt_portal_help_tip('Como funcionam as permissões', 'Use um grupo AD, SID de usuário ou setor como destinatário. Depois escolha <strong>módulo</strong>, <strong>operação</strong> e <strong>alcance</strong>. Para alcance por setor, selecione um setor; para itens específicos, informe os IDs correspondentes. A permissão será validada pelo servidor.', 'right'); ?>
                    </div>
                    <form action="<?php smtt_portal_action('grant'); ?>" method="post" class="smtt-ep__stacked-form">
                        <input type="hidden" name="action" value="smtt_portal_grant"><?php smtt_portal_nonce('grant'); ?>
                        <div class="smtt-ep__field-grid">
                            <div class="smtt-ep__field"><label for="smtt-grant-kind">Tipo de destinatário</label><select name="subject_kind" id="smtt-grant-kind"><option value="group">Grupo de segurança AD</option><option value="user">Usuário (SID)</option><option value="sector">Setor vinculado</option></select></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-value">Identificador do destinatário</label><input name="subject_value" id="smtt-grant-value" placeholder="SID AD ou ID numérico do setor" required><small>Informe o SID do catálogo AD ou o ID do setor cadastrado.</small></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-module">Módulo</label><select name="module" id="smtt-grant-module"><?php foreach($modules as $key=>$m): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($m[0]); ?></option><?php endforeach; ?></select></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-operation">Operação permitida</label><select name="operation" id="smtt-grant-operation"><option value="view">Visualizar</option><option value="create">Inserir</option><option value="edit">Editar</option><option value="delete">Excluir</option></select></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-scope">Alcance</label><select name="scope_kind" id="smtt-grant-scope"><option value="sector">Setor autorizado</option><option value="own">Somente próprios</option><option value="items">Itens específicos</option><option value="all">Todo o módulo</option></select></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-sector">Setor autorizado</label><select name="sector_id" id="smtt-grant-sector"><option value="0">Não selecionado</option><?php foreach($sectors as $term): ?><option value="<?php echo (int)$term->term_id; ?>"><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select><small>Usado quando o alcance é por setor.</small></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-items">IDs de itens específicos</label><input name="item_ids" id="smtt-grant-items" placeholder="Ex.: 123, 456"><small>Separe por vírgula quando escolher itens específicos.</small></div>
                            <div class="smtt-ep__field"><label for="smtt-grant-expires">Expira em (UTC)</label><input type="datetime-local" id="smtt-grant-expires" name="expires_at"><small>Opcional. Deixe em branco para não definir validade.</small></div>
                        </div>
                        <div class="smtt-ep__form-actions"><button type="submit" class="smtt-ep__button">Conceder permissão</button></div>
                    </form>
                </section>
                <section class="smtt-ep__panel"><h2>Pesquisar funcionário no AD</h2><p>Localize uma conta corporativa para obter seu SID e criar autorizações individuais.</p><form method="get"><input type="hidden" name="section" value="permissions"><label for="smtt-people-q">Nome ou usuário AD</label><input id="smtt-people-q" name="people_q" minlength="2" maxlength="80" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['people_q']??''))); ?>" placeholder="Digite pelo menos 2 caracteres"><button class="smtt-ep__button" type="submit">Pesquisar</button></form>
                    <?php $users_query=sanitize_text_field(wp_unslash($_GET['people_q']??'')); $found_users=$users_query!=='' ? SMTT_Intranet_AD::search_users($users_query) : array(); ?>
                    <?php if (is_wp_error($found_users)): ?><p class="smtt-ep__warning"><?php echo esc_html($found_users->get_error_message()); ?></p><?php elseif ($found_users): ?><table class="smtt-ep__table"><thead><tr><th>Funcionário</th><th>SID para permissão</th></tr></thead><tbody><?php foreach($found_users as $u): ?><tr><td><?php echo esc_html($u['name'].' · '.$u['login']); ?></td><td><code><?php echo esc_html($u['sid']); ?></code></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
                </section>
                <section class="smtt-ep__panel"><h2>Permissões concedidas</h2><div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><thead><tr><th>Destinatário</th><th>Módulo / ação</th><th>Alcance</th><th>Expiração</th><th></th></tr></thead><tbody><?php foreach((array)$grants as $g): ?><tr><td><?php echo esc_html($g['subject_kind'].' · '.$g['subject_value']); ?></td><td><?php echo esc_html($g['module_key'].' · '.$g['operation']); ?></td><td><?php echo esc_html($g['scope_kind'].' '.($g['sector_id']?:'').($g['item_ids']?' · '.$g['item_ids']:'')); ?></td><td><?php echo esc_html($g['expires_at']?:'Sem data'); ?></td><td><form method="post" action="<?php smtt_portal_action('delete_grant'); ?>" data-confirm="Revogar esta autorização?"><input type="hidden" name="action" value="smtt_portal_delete_grant"><input type="hidden" name="grant_id" value="<?php echo (int)$g['id']; ?>"><?php smtt_portal_nonce('delete_grant'); ?><button class="smtt-ep__danger">Revogar</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>

            <?php elseif ($section==='icons' && $admin): ?>
                <section class="smtt-ep__panel"><div class="smtt-ep__section-heading"><div><h2>Biblioteca central de ícones</h2><p><strong><?php echo number_format_i18n(count($icon_manifest)); ?> opções locais</strong> organizadas por áreas da SMTT. Escolha uma categoria, navegue por todos os resultados ou faça uma pesquisa em português ou inglês.</p></div></div>
                <label for="smtt-icon-search">Pesquisar em toda a biblioteca</label><input id="smtt-icon-search" type="search" autocomplete="off" placeholder="Ex.: pasta, engenharia, sistema, trânsito, documento, folder..."><div id="smtt-icon-categories" class="smtt-ep__categories" role="group" aria-label="Categorias de ícones"></div><p id="smtt-icon-results" class="smtt-ep__icon-results" role="status" aria-live="polite"></p><div id="smtt-icon-grid" class="smtt-ep__icons" tabindex="0" aria-label="Resultados de ícones: role a lista para ver mais"></div><button id="smtt-icon-more" type="button" class="smtt-ep__more" hidden>Mostrar mais ícones</button><p id="smtt-icon-copied" class="smtt-ep__icon-feedback" role="status" aria-live="polite"></p>
                <p class="smtt-ep__note">Font Awesome Free (CC BY 4.0) preservado com atribuição; coleção complementar de ícones SMTT criados para o projeto. Marcas de terceiros pertencem a seus titulares.</p></section>
                <section class="smtt-ep__panel"><h2>Ícones próprios da SMTT</h2><p>Envie imagens PNG, WEBP ou JPEG. Upload de SVG é bloqueado para evitar scripts embutidos.</p><form action="<?php smtt_portal_action('logo'); ?>" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="smtt_portal_logo"><?php smtt_portal_nonce('logo'); ?><input type="file" name="smtt_logo" accept=".png,.webp,.jpg,.jpeg" required><button type="submit" class="smtt-ep__button">Enviar logomarca</button></form><div class="smtt-ep__icons smtt-ep__icons--static"><?php $logos=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>100,'meta_key'=>'_smtt_portal_logo','meta_value'=>'1')); foreach($logos as $logo): ?><button type="button" data-pick-icon="logo:<?php echo (int)$logo->ID; ?>" class="smtt-ep__iconchoice"><?php echo SMTT_Intranet_Icon_Library::html('logo:'.$logo->ID); ?><small>logo:<?php echo (int)$logo->ID; ?></small></button><?php endforeach; ?></div></section>

            <?php elseif ($section==='settings' && $admin): ?>
                <section class="smtt-ep__panel"><h2>Organização dos módulos</h2><p>Preferências do gerenciador de conteúdos. Para mostrar, ocultar ou reorganizar os cards da Home, utilize Layout da Home. Estas opções não apagam registros.</p><form method="post" action="<?php smtt_portal_action('options'); ?>"><input type="hidden" name="action" value="smtt_portal_options"><input type="hidden" name="option_group" value="modules"><?php smtt_portal_nonce('options'); ?><div class="smtt-ep__choice-grid smtt-ep__choice-grid--settings"><?php $enabled=get_option('smtt_portal_module_visibility',array()); foreach($modules as $key=>$m): ?><label class="smtt-ep__check"><input type="checkbox" name="mod_<?php echo esc_attr($key); ?>" value="1" <?php checked(($enabled[$key]??'1'),'1'); ?>> <?php echo esc_html($m[0]); ?></label><?php endforeach; ?></div><button class="smtt-ep__button">Salvar preferências</button></form></section>
                <section class="smtt-ep__panel">
                    <div class="smtt-ep__section-heading"><div><h2>Aniversariantes</h2><p>Configure como a lista de aniversários será apresentada na Intranet.</p></div></div>
                    <form method="post" action="<?php smtt_portal_action('options'); ?>" class="smtt-ep__stacked-form">
                        <input type="hidden" name="action" value="smtt_portal_options"><input type="hidden" name="option_group" value="birthdays"><?php smtt_portal_nonce('options'); ?>
                        <div class="smtt-ep__field-grid">
                            <div class="smtt-ep__field"><label for="smtt-birthday-mode">Modo de exibição</label><select id="smtt-birthday-mode" name="display_mode"><?php foreach(array('cartoes'=>'Cartões','lista'=>'Lista','compacto'=>'Compacto') as $mode=>$label): ?><option value="<?php echo esc_attr($mode); ?>" <?php selected(get_option(SMTT_Intranet_Birthdays::OPTION_DISPLAY_MODE,'cartoes'),$mode); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
                            <div class="smtt-ep__field"><label for="smtt-birthday-max">Máximo de itens</label><input id="smtt-birthday-max" type="number" min="1" max="100" name="max_items" value="<?php echo esc_attr(get_option(SMTT_Intranet_Birthdays::OPTION_MAX_ITEMS,30)); ?>"></div>
                        </div>
                        <fieldset class="smtt-ep__options-fieldset"><legend>Informações exibidas</legend>
                          <div class="smtt-ep__choice-grid smtt-ep__choice-grid--settings">
                            <?php foreach(array('show_photo'=>array(SMTT_Intranet_Birthdays::OPTION_SHOW_PHOTO,'Mostrar foto'), 'show_sector'=>array(SMTT_Intranet_Birthdays::OPTION_SHOW_SECTOR,'Mostrar setor'), 'show_role'=>array(SMTT_Intranet_Birthdays::OPTION_SHOW_ROLE,'Mostrar cargo')) as $k=>$item): ?>
                              <label class="smtt-ep__check"><input type="checkbox" name="<?php echo esc_attr($k); ?>" value="1" <?php checked(get_option($item[0],'1'),'1'); ?>> <?php echo esc_html($item[1]); ?></label>
                            <?php endforeach; ?>
                          </div>
                        </fieldset>
                        <div class="smtt-ep__form-actions"><button type="submit" class="smtt-ep__button">Salvar exibição</button></div>
                    </form>
                </section>
                <section class="smtt-ep__panel smtt-ep__tour-panel">
                    <div class="smtt-ep__section-heading"><div><h2>Tour da Intranet</h2><p>Configure a apresentação interativa sem acessar o painel WordPress.</p></div><?php smtt_portal_help_tip('Sobre o Tour', 'O Tour apresenta os principais espaços da Intranet ao funcionário. Aumentar a versão força a apresentação novamente. Escolha quais etapas serão mostradas e salve as alterações.', 'right'); ?></div>
                    <?php $tour=SMTT_Intranet_Tour::get_settings(); ?>
                    <form method="post" action="<?php smtt_portal_action('options'); ?>" class="smtt-ep__stacked-form">
                        <input type="hidden" name="action" value="smtt_portal_options"><input type="hidden" name="option_group" value="tour"><?php smtt_portal_nonce('options'); ?>
                        <div class="smtt-ep__tour-enable"><label class="smtt-ep__check"><input type="checkbox" name="enabled" value="1" <?php checked($tour['enabled'],'1'); ?>> Habilitar Tour da Intranet</label></div>
                        <div class="smtt-ep__field-grid">
                            <div class="smtt-ep__field"><label for="smtt-tour-version">Versão do Tour</label><input id="smtt-tour-version" type="number" min="1" max="9999" name="version" value="<?php echo (int)$tour['version']; ?>"><small>Incrementar a versão reinicia a apresentação para os usuários.</small></div>
                            <div class="smtt-ep__field"><label for="smtt-tour-delay">Espera antes do Tour</label><select id="smtt-tour-delay" name="delay"><?php foreach(array(0,500,1000,1500,2000,3000) as $d): ?><option value="<?php echo $d; ?>" <?php selected($tour['delay'],$d); ?>><?php echo $d; ?> ms</option><?php endforeach; ?></select></div>
                            <div class="smtt-ep__field smtt-ep__field--full"><label for="smtt-tour-title">Título de boas-vindas</label><input id="smtt-tour-title" name="welcome_title" value="<?php echo esc_attr($tour['welcome_title']); ?>"></div>
                            <div class="smtt-ep__field smtt-ep__field--full"><label for="smtt-tour-text">Texto inicial</label><textarea id="smtt-tour-text" name="welcome_text" rows="4"><?php echo esc_textarea($tour['welcome_text']); ?></textarea></div>
                        </div>
                        <fieldset class="smtt-ep__options-fieldset"><legend>Etapas exibidas no Tour</legend>
                          <div class="smtt-ep__choice-grid smtt-ep__choice-grid--settings">
                            <?php foreach(SMTT_Intranet_Tour::get_step_definitions() as $key=>$def): ?><label class="smtt-ep__check"><input type="checkbox" name="step_<?php echo esc_attr($key); ?>" value="1" <?php checked(($tour['steps'][$key]??'0'),'1'); ?>> <?php echo esc_html($def['admin_title']??$key); ?></label><?php endforeach; ?>
                          </div>
                        </fieldset>
                        <div class="smtt-ep__form-actions"><button type="submit" class="smtt-ep__button">Salvar Tour</button></div>
                    </form>
                </section>

            <?php elseif ($section==='chat' && $admin): ?>
                <?php $chat_settings = SMTT_Intranet_Native_Chat::settings(); $chat_stats = SMTT_Intranet_Native_Chat::statistics(); ?>
                <div class="smtt-ep__stats">
                    <div><span>Conversas</span><strong><?php echo (int)$chat_stats['conversations']; ?></strong><small>Individuais e grupos</small></div>
                    <div><span>Mensagens</span><strong><?php echo (int)$chat_stats['messages']; ?></strong><small>Somente texto</small></div>
                    <div><span>Participantes</span><strong><?php echo (int)$chat_stats['participants']; ?></strong><small>Usuários com conversa</small></div>
                    <div><span>Serviço</span><strong><?php echo SMTT_Intranet_Native_Chat::enabled() ? 'Ativo' : 'Desativado'; ?></strong><small>Chat interno SMTT</small></div>
                </div>
                <div class="smtt-ep__chat-actions"><a class="smtt-ep__button" href="<?php echo esc_url(SMTT_Intranet_Chat_Integration::url()); ?>" target="_blank" rel="noopener noreferrer">Abrir Chat SMTT em nova aba ↗</a></div>
                <section class="smtt-ep__panel" id="smtt-chat-settings">
                    <h2>Configurações do Chat SMTT</h2>
                    <p>Mensagens privadas e de grupo, texto, emojis e links. Sem anexos e sem dependência do Better Messages.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="smtt-ep__form">
                        <input type="hidden" name="action" value="smtt_chat_save_settings">
                        <?php wp_nonce_field('smtt_chat_settings'); ?>
                        <label class="smtt-ep__check"><input type="checkbox" name="enabled" value="1" <?php checked($chat_settings['enabled'],'1'); ?>> Ativar Chat SMTT</label>
                        <p>Grupos são administrados exclusivamente pelo CPD nesta Central.</p>
                        <div class="smtt-ep__field"><label for="smtt-chat-max-length">Máximo de caracteres por mensagem</label>
                        <input id="smtt-chat-max-length" type="number" min="200" max="5000" name="max_length" value="<?php echo esc_attr((int)$chat_settings['max_length']); ?>"></div>
                        <button type="submit" class="smtt-ep__button">Salvar configurações</button>
                    </form>
                </section>
                <?php
                $chat_groups = SMTT_Intranet_Native_Chat::admin_groups();
                $ad_groups = SMTT_Intranet_AD::list_objects('group','',500);
                $focused_group_id = absint($_GET['chat_group'] ?? 0);
                $focused_group = null;
                foreach ($chat_groups as $g) { if ((int)$g['id']===$focused_group_id) { $focused_group=$g; break; } }
                ?>
                <div class="smtt-ep__grid">
                    <section class="smtt-ep__panel">
                        <h2>Criar grupo de trabalho vinculado ao AD</h2>
                        <p>Selecione um grupo de segurança sincronizado. Os funcionários reconhecidos pelo SSO entram automaticamente no grupo de conversa, sem cadastro manual.</p>
                        <?php if (!$ad_groups): ?>
                            <p class="smtt-ep__warning">Nenhum grupo AD sincronizado. Configure a conexão e sincronize em <strong>Setores e AD</strong>. Você já pode criar grupos manuais para testar.</p>
                        <?php else: ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="smtt-ep__form">
                            <input type="hidden" name="action" value="smtt_chat_create_group">
                            <input type="hidden" name="group_source" value="ad">
                            <?php wp_nonce_field('smtt_chat_create_group'); ?>
                            <div class="smtt-ep__field"><label for="smtt-chat-ad-title">Nome exibido no Chat</label>
                            <input id="smtt-chat-ad-title" name="title" required maxlength="100" placeholder="Ex.: Equipe de Engenharia"></div>
                            <div class="smtt-ep__field"><label for="smtt-chat-ad-group">Grupo de segurança no AD</label>
                            <select id="smtt-chat-ad-group" name="ad_group_sid" required>
                                <option value="">Selecione um grupo sincronizado</option>
                                <?php foreach ($ad_groups as $ad_group): if (empty($ad_group['object_sid'])) {continue;} ?>
                                    <option value="<?php echo esc_attr($ad_group['object_sid']); ?>"><?php echo esc_html($ad_group['object_name'].' — '.$ad_group['object_dn']); ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <button class="smtt-ep__button" type="submit">Vincular grupo ao Chat</button>
                        </form>
                        <?php endif; ?>
                    </section>
                    <section class="smtt-ep__panel">
                        <h2>Criar grupo manual do CPD</h2>
                        <p>Para projetos, comissões e equipes especiais. Somente o CPD escolhe os integrantes.</p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="smtt-ep__form">
                            <input type="hidden" name="action" value="smtt_chat_create_group">
                            <input type="hidden" name="group_source" value="manual">
                            <?php wp_nonce_field('smtt_chat_create_group'); ?>
                            <div class="smtt-ep__field"><label for="smtt-chat-manual-title">Nome do grupo</label>
                            <input id="smtt-chat-manual-title" name="title" required maxlength="100" placeholder="Ex.: Comissão de Eventos"></div>
                            <div class="smtt-ep__field"><label for="smtt-chat-manual-users">Logins WordPress dos participantes (opcional)</label>
                            <textarea id="smtt-chat-manual-users" name="members_list" rows="3" placeholder="joao.silva, maria.souza"></textarea></div>
                            <p class="smtt-ep__note">Durante o teste local, informe os logins existentes. Na produção, serão usadas as contas provisionadas pelo SSO.</p>
                            <button class="smtt-ep__button" type="submit">Criar grupo manual</button>
                        </form>
                    </section>
                </div>
                <section class="smtt-ep__panel" id="smtt-chat-groups">
                    <h2>Grupos administrados pelo CPD</h2>
                    <p>Os grupos ligados ao AD recebem participantes conforme a autenticação corporativa. Grupos manuais são mantidos aqui. Não há botão de criar grupo no chat dos funcionários.</p>
                    <div class="smtt-ep__table-wrap"><table class="smtt-ep__table">
                        <thead><tr><th>Grupo</th><th>Origem</th><th>Participantes</th><th>Estado</th><th>Gerenciar</th></tr></thead>
                        <tbody>
                        <?php foreach ($chat_groups as $g): ?>
                            <tr>
                                <td><?php echo esc_html($g['title']); ?></td>
                                <td><?php echo $g['group_source']==='ad' ? 'Active Directory' : 'Manual CPD'; ?></td>
                                <td><?php echo (int)$g['members']; ?></td>
                                <td><?php echo $g['is_active'] ? 'Ativo' : 'Arquivado'; ?></td>
                                <td><a href="<?php smtt_portal_link(array('section'=>'chat','chat_group'=>(int)$g['id'])); ?>">Gerenciar</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$chat_groups): ?><tr><td colspan="5">Ainda não existem grupos.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                </section>
                <?php if ($focused_group): ?>
                <section class="smtt-ep__panel" id="smtt-chat-group-manage">
                    <h2>Gerenciar grupo: <?php echo esc_html($focused_group['title']); ?></h2>
                    <p>Origem: <?php echo $focused_group['group_source']==='ad' ? 'Active Directory — participantes definidos pelo grupo de segurança' : 'Manual — participantes definidos pelo CPD'; ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="smtt-ep__form">
                        <input type="hidden" name="action" value="smtt_chat_group_update">
                        <input type="hidden" name="group_action" value="rename">
                        <input type="hidden" name="group_id" value="<?php echo (int)$focused_group['id']; ?>">
                        <?php wp_nonce_field('smtt_chat_group_update'); ?>
                        <div class="smtt-ep__field"><label for="smtt-group-rename">Nome de exibição</label>
                        <input id="smtt-group-rename" name="title" maxlength="100" value="<?php echo esc_attr($focused_group['title']); ?>" required></div>
                        <button class="smtt-ep__button" type="submit">Salvar nome</button>
                    </form>
                    <?php if ($focused_group['group_source']==='manual' && $focused_group['is_active']): ?>
                        <h3>Participantes cadastrados</h3>
                        <div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><tbody>
                        <?php foreach (SMTT_Intranet_Native_Chat::admin_group_members((int)$focused_group['id']) as $member): ?>
                            <tr><td><?php echo esc_html($member['display_name']); ?> <small>(<?php echo esc_html($member['user_login']); ?>)</small></td><td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Remover este participante?');">
                                    <input type="hidden" name="action" value="smtt_chat_group_members"><input type="hidden" name="member_action" value="remove">
                                    <input type="hidden" name="group_id" value="<?php echo (int)$focused_group['id']; ?>"><input type="hidden" name="member_id" value="<?php echo (int)$member['user_id']; ?>">
                                    <?php wp_nonce_field('smtt_chat_group_members'); ?><button type="submit" class="smtt-ep__danger">Remover</button>
                                </form>
                            </td></tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="smtt-ep__form">
                            <input type="hidden" name="action" value="smtt_chat_group_members"><input type="hidden" name="member_action" value="add">
                            <input type="hidden" name="group_id" value="<?php echo (int)$focused_group['id']; ?>">
                            <?php wp_nonce_field('smtt_chat_group_members'); ?>
                            <div class="smtt-ep__field"><label for="smtt-chat-add-users">Adicionar participantes (logins separados por vírgula)</label>
                            <textarea id="smtt-chat-add-users" name="members_list" rows="2" placeholder="joao.silva, maria.souza"></textarea></div>
                            <button type="submit" class="smtt-ep__button">Adicionar ao grupo</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Alterar o estado deste grupo? O histórico será preservado.');">
                        <input type="hidden" name="action" value="smtt_chat_group_update"><input type="hidden" name="group_id" value="<?php echo (int)$focused_group['id']; ?>">
                        <input type="hidden" name="group_action" value="<?php echo $focused_group['is_active'] ? 'archive' : 'restore'; ?>">
                        <?php wp_nonce_field('smtt_chat_group_update'); ?>
                        <button type="submit" class="smtt-ep__button"><?php echo $focused_group['is_active'] ? 'Arquivar grupo' : 'Reativar grupo'; ?></button>
                    </form>
                </section>
                <?php endif; ?>

            <?php elseif ($section==='gate' && $admin): ?>
                <div class="smtt-ep__stats"><div><span>Modo operacional</span><strong>Auditoria</strong><small>Não altera firewall</small></div><div><span>FortiOS</span><strong>Não configurado</strong><small>Validar em ambiente real</small></div><div><span>FSSO/RSSO</span><strong>Pendente</strong><small>Depende da infraestrutura</small></div><div><span>Liberações reais</span><strong>0</strong><small>Conector em simulação</small></div></div>
                <section class="smtt-ep__panel" id="smtt-gate-events"><h2>Eventos simulados de rede</h2><p>Os registros abaixo representam a autorização esperada e sua revogação; nenhuma mensagem foi enviada ao FortiGate.</p><div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><thead><tr><th>Data UTC</th><th>Identidade</th><th>Evento</th><th>Expiração UTC</th></tr></thead><tbody><?php foreach(SMTT_Intranet_FortiGate_Simulator::recent(40) as $event): ?><tr><td><?php echo esc_html($event['created_at']); ?></td><td><?php echo esc_html($event['identity_upn']); ?></td><td><?php echo esc_html($event['event_type']); ?></td><td><?php echo esc_html($event['expires_at']); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
                <section class="smtt-ep__panel" id="smtt-gate-integration"><h2>Access Gate + FortiGate</h2><p>A passagem registrada e a efetiva liberação de Internet são estados diferentes. Nenhum mecanismo do plugin substitui as políticas de firewall.</p><p>O Access Gate atual registra passagens e expiração diária, mantendo as APIs de integração em modo auditoria. O adaptador real será implementado após identificar versão FortiOS, autenticação de rede e regras do equipamento.</p><p class="smtt-ep__warning">Não ative bloqueios em produção antes de validar autenticação, exceções essenciais, reconciliação e encerramento de sessões na virada do dia.</p></section>

            <?php elseif ($section==='audit' && $admin):
                global $wpdb; $at=SMTT_Intranet_Policy::audit_table(); $events=$wpdb->get_results("SELECT * FROM {$at} ORDER BY id DESC LIMIT 100",ARRAY_A); ?>
                <section class="smtt-ep__panel"><h2>Histórico das últimas 100 operações</h2><div class="smtt-ep__table-wrap"><table class="smtt-ep__table"><thead><tr><th>Data UTC</th><th>Usuário</th><th>Ação</th><th>Alvo</th><th>Resultado</th></tr></thead><tbody><?php foreach((array)$events as $e): ?><tr><td><?php echo esc_html($e['created_at']); ?></td><td><?php echo esc_html($e['actor_sid']?:'WP #'.$e['actor_wp']); ?></td><td><?php echo esc_html($e['action_name']); ?></td><td><?php echo esc_html($e['target']); ?></td><td><?php echo esc_html($e['result_name']); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
            <?php endif; ?>
        </div>
    </div>
    <dialog id="smtt-icon-dialog" class="smtt-ep__dialog" aria-label="Selecionar ícone"><div class="smtt-ep__dialoghead"><h2>Selecionar ícone</h2><button type="button" id="smtt-icon-close" aria-label="Fechar">✕</button></div><label for="smtt-modal-search">Pesquisar em toda a biblioteca</label><input id="smtt-modal-search" type="search" autocomplete="off" placeholder="Ex.: pasta, sistema, carro..." aria-label="Pesquisar ícone"><div id="smtt-modal-categories" class="smtt-ep__categories" role="group" aria-label="Categorias de ícones"></div><p id="smtt-modal-results" class="smtt-ep__icon-results" role="status" aria-live="polite"></p><div id="smtt-modal-grid" class="smtt-ep__icons" tabindex="0" aria-label="Resultados de ícones"></div><button id="smtt-modal-more" type="button" class="smtt-ep__more" hidden>Mostrar mais ícones</button></dialog>
</main>
<script id="smtt-portal-icon-data" type="application/json"><?php echo wp_json_encode($icon_manifest,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
<?php wp_footer(); ?>
</body>
</html>
