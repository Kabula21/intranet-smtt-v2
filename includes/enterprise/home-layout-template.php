<?php
/** Editor CPD da apresentação da Home. A edição não modifica os conteúdos dos módulos. */
if (!defined('ABSPATH') || !SMTT_Intranet_Corporate_Identity::is_admin()) { exit; }
$cards = SMTT_Intranet_Home_Layout::cards();
$draft = SMTT_Intranet_Home_Layout::draft();
$previous = get_option(SMTT_Intranet_Home_Layout::OPT_PREVIOUS, null);
?>
<section class="smtt-ep__panel smtt-home-editor" data-smtt-home-editor>
    <div class="smtt-home-editor__intro">
        <div>
            <div class="smtt-ep__titleline">
                <h2>Editor visual da Home</h2>
                <?php smtt_portal_help_tip('Ajuda rápida do editor', 'Organize os cards e clique em <strong>Salvar rascunho</strong>. Para que os funcionários vejam as mudanças, use <strong>Publicar layout</strong>.<br><br>Use <strong>Módulos</strong> para editar os registros e <strong>Criar módulos</strong> para cadastrar novos catálogos. Eles aparecem automaticamente nesta grade, inicialmente ocultos até a publicação.<br><br>O editor altera apenas o layout da Home. Permissões por setor e regras específicas da Intranet continuam sendo tratadas separadamente.'); ?>
            </div>
            <p>Arraste os cards para reorganizar, selecione a largura e a altura e escolha quais aparecem. O conteúdo dos módulos será preservado.</p>
        </div>
        <span class="smtt-ep__status" data-home-summary>Carregando cards…</span>
    </div>
    <div class="smtt-home-editor__hint" id="smtt-home-layout-help">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <span>As instruções detalhadas agora ficam concentradas no ícone de informações ao lado do título. Aqui você mantém só o essencial para trabalhar com mais foco.</span>
    </div>
    <div class="smtt-home-editor__toolbar">
        <label for="smtt-layout-device">Visualizar disposição:</label>
        <select data-home-device id="smtt-layout-device">
            <option value="desktop">Desktop</option><option value="mobile">Celular (simulação)</option>
        </select>
        <a href="<?php echo esc_url(SMTT_Intranet_Home_Layout::preview_url()); ?>" class="smtt-ep__button" target="_blank" rel="noopener noreferrer">↗ Prévia na Home (rascunho salvo)</a>
        <a href="<?php echo esc_url(SMTT_Intranet_Home_Routing::url() ?: home_url('/')); ?>" class="smtt-ep__button" target="_blank" rel="noopener noreferrer">↗ Ver Home publicada</a>
    </div>
    <p class="smtt-home-editor__scroll-help"><strong>Cards da Home:</strong> role a área de conteúdo à direita para ver todos os módulos. Arraste pela alça ⠿ ou use ↑/↓. As alterações só aparecem para todos após <strong>Publicar layout</strong>. <a href="#smtt-home-layout-actions">Ir aos botões de salvar/publicar</a>.</p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-smtt-home-form>
        <input type="hidden" name="action" value="smtt_home_layout">
        <?php wp_nonce_field(SMTT_Intranet_Home_Layout::NONCE); ?>
        <input type="hidden" name="layout_json" value="<?php echo esc_attr(wp_json_encode($draft)); ?>">
        <div class="smtt-home-editor__grid" id="smtt-home-layout-grid" data-home-cards role="region" tabindex="0" aria-label="Cards da Home; percorra a página, arraste pela alça ou utilize os botões de mover">
          <?php foreach ($draft as $index=>$item): if (!isset($cards[$item['id']])) continue;
            $card = $cards[$item['id']]; ?>
            <div class="smtt-home-editor__card" data-card-id="<?php echo esc_attr($item['id']); ?>" style="--editor-span:<?php echo (int)$item['span']; ?>;--editor-height:<?php echo (int)$item['height']; ?>px;">
                <div class="smtt-home-editor__cardhead">
                    <button class="smtt-home-editor__grip" type="button" draggable="true" data-card-grip title="Arrastar; Alt+↑/↓ para mover" aria-label="Arrastar <?php echo esc_attr($card['label']); ?>">⠿</button>
                    <span class="smtt-home-editor__cardname"><?php echo esc_html($card['label']); ?></span>
                    <span class="smtt-home-editor__count" data-card-order><?php echo (int)$index + 1; ?></span>
                </div>
                <p class="smtt-home-editor__description"><?php echo esc_html($card['description']); ?></p>
                <div class="smtt-home-editor__fields">
                    <label class="smtt-ep__check"><input type="checkbox" data-card-visible <?php checked($item['visible']); ?>> Exibir na Home</label>
                    <label for="span-<?php echo esc_attr($item['id']); ?>">Largura</label>
                    <label for="height-<?php echo esc_attr($item['id']); ?>">Altura</label>
                    <select data-card-span id="span-<?php echo esc_attr($item['id']); ?>">
                      <?php foreach (array(5=>'Compacto (5)',6=>'Pequeno (6)',8=>'Médio (8)',9=>'Médio (9)',12=>'Metade (12)',13=>'Largo (13)',16=>'Grande (16)',18=>'Extra grande (18)',24=>'Largura inteira') as $size=>$label): ?>
                        <option value="<?php echo (int)$size; ?>" <?php selected((int)$item['span'],(int)$size); ?>><?php echo esc_html($label); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <select data-card-height id="height-<?php echo esc_attr($item['id']); ?>">
                      <?php foreach (array(220=>'Compacta',250=>'Baixa',300=>'Média',370=>'Alta',430=>'Grande',520=>'Extra grande') as $size=>$label): ?>
                        <option value="<?php echo (int)$size; ?>" <?php selected((int)$item['height'],(int)$size); ?>><?php echo esc_html($label); ?></option>
                      <?php endforeach; ?>
                    </select>
                </div>
                <details class="smtt-home-editor__appearance">
                  <summary>Nome, subtítulo, ícone e cor</summary>
                  <label for="title-<?php echo esc_attr($item['id']); ?>">Nome personalizado</label>
                  <input data-card-title id="title-<?php echo esc_attr($item['id']); ?>" type="text" maxlength="100" placeholder="<?php echo esc_attr($card['label']); ?>" value="<?php echo esc_attr($item['title']??''); ?>">
                  <label for="subtitle-<?php echo esc_attr($item['id']); ?>">Subtítulo personalizado</label>
                  <input data-card-subtitle id="subtitle-<?php echo esc_attr($item['id']); ?>" type="text" maxlength="160" placeholder="Opcional" value="<?php echo esc_attr($item['subtitle']??''); ?>">
                  <label for="icon-<?php echo esc_attr($item['id']); ?>">Ícone</label>
                  <div class="smtt-ep__iconinput">
                    <input data-icon-value data-card-icon id="icon-<?php echo esc_attr($item['id']); ?>" value="<?php echo esc_attr($item['icon']??''); ?>" placeholder="Ícone original" readonly>
                    <button type="button" data-open-icons>Escolher</button>
                    <button type="button" data-reset-icon title="Restaurar ícone original">↶</button>
                  </div>
                  <label for="tone-<?php echo esc_attr($item['id']); ?>">Cor de destaque</label>
                  <select data-card-tone id="tone-<?php echo esc_attr($item['id']); ?>">
                    <?php foreach (array('default'=>'Padrão SMTT','azul'=>'Azul','turquesa'=>'Turquesa','verde'=>'Verde','amarelo'=>'Amarelo','violeta'=>'Violeta') as $tone=>$label): ?>
                      <option value="<?php echo esc_attr($tone); ?>" <?php selected(($item['tone']??'default'),$tone); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                </details>
                <div class="smtt-home-editor__move">
                  <button type="button" data-move="up" aria-label="Mover <?php echo esc_attr($card['label']); ?> para cima">↑ Subir</button>
                  <button type="button" data-move="down" aria-label="Mover <?php echo esc_attr($card['label']); ?> para baixo">↓ Descer</button>
                  <?php if (strpos($item['id'],'catalog-')===0): ?>
                    <a href="<?php echo esc_url(SMTT_Intranet_Manager::url(array('section'=>'catalogs','catalog'=>absint(substr($item['id'],8))))); ?>">Editar módulo</a>
                  <?php endif; ?>
                </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="smtt-home-editor__draft-alert" data-home-dirty hidden>Há alterações ainda não salvas. Salve o rascunho antes de abrir a prévia em outra aba.</p>
        <div class="smtt-home-editor__actions" id="smtt-home-layout-actions">
            <button type="submit" name="layout_operation" value="draft" class="smtt-ep__button">Salvar rascunho</button>
            <button type="submit" name="layout_operation" value="publish" class="smtt-ep__button">Publicar layout</button>
            <span class="smtt-ep__note">Publicar altera apenas a disposição e a visibilidade dos cards, sem excluir cadastros.</span>
        </div>
    </form>
    <?php if (is_array($previous)): ?>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-home-restore>
        <input type="hidden" name="action" value="smtt_home_layout">
        <input type="hidden" name="layout_operation" value="restore">
        <?php wp_nonce_field(SMTT_Intranet_Home_Layout::NONCE); ?>
        <button type="submit" class="smtt-ep__button">↶ Restaurar layout publicado anteriormente</button>
      </form>
    <?php endif; ?>
</section>
