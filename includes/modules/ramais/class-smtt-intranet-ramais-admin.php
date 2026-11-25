<?php

if (!defined('ABSPATH')) {
    exit;
}

class SMTT_Intranet_Ramais_Admin {

    public function register_hooks() {
    // Importação será processada diretamente na página, seguindo o padrão do plugin.
        }
        

    public function render_page() {
        if (!current_user_can('smtt_manage_directory')) {
            wp_die('Você não tem permissão para acessar esta página.');
        }
        
        $import_result = null;
        $manual_result = null;

        if (
            isset($_POST['smtt_ramais_nonce']) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['smtt_ramais_nonce'])),
                'smtt_importar_ramais_csv'
            )
        ) {
            if (!empty($_FILES['smtt_ramais_csv']['tmp_name'])) {
                $import_result = SMTT_Intranet_Ramais_Importer::import_uploaded_file($_FILES['smtt_ramais_csv']);
            } else {
                $import_result = new WP_Error(
                    'smtt_ramais_no_file',
                    'Selecione um arquivo XLS ou CSV para importar.'
                );
            }
        }

        // Salvar ramal manualmente
        if (
            isset($_POST['smtt_ramais_manual_nonce']) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['smtt_ramais_manual_nonce'])),
                'smtt_salvar_ramal_manual'
            )
        ) {
            $ramal_id = isset($_POST['ramal_id']) ? absint($_POST['ramal_id']) : 0;

            $payload = array(
                'id'              => $ramal_id,
                'nome'            => isset($_POST['nome']) ? sanitize_text_field(wp_unslash($_POST['nome'])) : '',
                'setor'           => isset($_POST['setor']) ? sanitize_text_field(wp_unslash($_POST['setor'])) : '',
                'cargo'           => isset($_POST['cargo']) ? sanitize_text_field(wp_unslash($_POST['cargo'])) : '',
                'ramal'           => isset($_POST['ramal']) ? sanitize_text_field(wp_unslash($_POST['ramal'])) : '',
                'telefone_direto' => isset($_POST['telefone_direto']) ? sanitize_text_field(wp_unslash($_POST['telefone_direto'])) : '',
                'whatsapp'        => isset($_POST['whatsapp']) ? sanitize_text_field(wp_unslash($_POST['whatsapp'])) : '',
                'email'           => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
                'unidade'         => isset($_POST['unidade']) ? sanitize_text_field(wp_unslash($_POST['unidade'])) : '',
                'sala'            => isset($_POST['sala']) ? sanitize_text_field(wp_unslash($_POST['sala'])) : '',
                'observacao'      => isset($_POST['observacao']) ? sanitize_textarea_field(wp_unslash($_POST['observacao'])) : '',
                'status'          => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'ativo',
            );

            $saved = SMTT_Intranet_Ramais_Repository::save($payload);

            if (is_wp_error($saved)) {
                $manual_result = array(
                    'success' => false,
                    'message' => $saved->get_error_message(),
                );
            } else {
                delete_transient('smtt_ramais_public_ativos_v1');
                delete_transient('smtt_ramais_public_ativos_v2');
                delete_transient('smtt_ramais_public_payload_v3');
                $manual_result = array(
                    'success' => true,
                    'message' => $ramal_id > 0 ? 'Ramal atualizado com sucesso.' : 'Ramal cadastrado com sucesso.',
                );
            }
        }

        // Excluir ramal
        if (
            isset($_POST['smtt_ramais_delete_nonce']) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['smtt_ramais_delete_nonce'])),
                'smtt_excluir_ramal'
            )
        ) {
            $delete_id = isset($_POST['delete_ramal_id']) ? absint($_POST['delete_ramal_id']) : 0;

            if ($delete_id > 0) {
                $deleted = SMTT_Intranet_Ramais_Repository::delete($delete_id);

                if ($deleted === false) {
                    $manual_result = array(
                        'success' => false,
                        'message' => 'Erro ao excluir o ramal.',
                    );
                } else {
                    delete_transient('smtt_ramais_public_ativos_v1');
                    delete_transient('smtt_ramais_public_ativos_v2');
                delete_transient('smtt_ramais_public_payload_v3');
                    $manual_result = array(
                        'success' => true,
                        'message' => 'Ramal excluído com sucesso.',
                    );
                }
            }
        }

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $setor  = isset($_GET['setor']) ? sanitize_text_field(wp_unslash($_GET['setor'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

        $editing_ramal = null;

        if (isset($_GET['edit_ramal'])) {
            $editing_ramal_id = absint($_GET['edit_ramal']);

            if ($editing_ramal_id > 0) {
                $editing_ramal = SMTT_Intranet_Ramais_Repository::get_by_id($editing_ramal_id);
            }
        }

        $ramais = SMTT_Intranet_Ramais_Repository::get_all(array(
            'search' => $search,
            'setor'  => $setor,
            'status' => $status,
            'limit'  => 100,
            'offset' => 0,
        ));

        $setores = SMTT_Intranet_Ramais_Repository::get_setores();
        $imports = SMTT_Intranet_Ramais_Repository::get_imports(10);

        ?>
        <div class="wrap smtt-admin-page smtt-ramais-admin">
            <h1>Ramais</h1>

            <?php if ($import_result !== null) : ?>
            <?php if (is_wp_error($import_result)) : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($import_result->get_error_message()); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            'Importação %s finalizada. Linhas: %d | Inseridos: %d | Atualizados: %d | Ignorados: %d | Erros: %d',
                            strtoupper((string) ($import_result['formato'] ?? 'arquivo')),
                            (int) ($import_result['total_linhas'] ?? 0),
                            (int) ($import_result['total_inseridos'] ?? 0),
                            (int) ($import_result['total_atualizados'] ?? 0),
                            (int) ($import_result['total_ignorados'] ?? 0),
                            (int) ($import_result['total_erros'] ?? 0)
                        ));
                        ?>
                    </p>

                    <?php if (!empty($import_result['relatorio'])) : ?>
                        <ul style="margin-left:20px;">
                            <?php foreach (array_slice($import_result['relatorio'], 0, 10) as $item) : ?>
                                <li>
                                    <?php
                                    echo esc_html(sprintf(
                                        'Linha %d - %s - Ramal %s: %s',
                                        (int) ($item['linha'] ?? 0),
                                        $item['nome'] ?? '',
                                        $item['ramal'] ?? '',
                                        $item['mensagem'] ?? ''
                                    ));
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>          

            <?php if (is_array($manual_result)) : ?>
                <div class="notice <?php echo !empty($manual_result['success']) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                    <p><?php echo esc_html($manual_result['message']); ?></p>
                </div>
            <?php endif; ?>

            <div class="smtt-admin-card">
                <h2>Importar ramais por Excel / CSV</h2>

                <p>
                    Você pode enviar diretamente o arquivo <strong>.xls</strong> recebido pela SMTT
                    com as colunas <strong>SETOR</strong> e <strong>RAMAL</strong>.
                    O importador também continua aceitando CSV.
                </p>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('smtt_importar_ramais_csv', 'smtt_ramais_nonce'); ?>

                   

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="smtt_ramais_csv">Arquivo de ramais</label>
                            </th>
                            <td>
                                <input type="file" id="smtt_ramais_csv" name="smtt_ramais_csv" accept=".xls,.csv" required>
                                <p class="description">
                                    Modelo Excel aceito: <strong>SETOR | RAMAL</strong>. O plugin identifica automaticamente
                                    complementos como “CPD - SUPORTE”, “ATENDE - COORD.” e “DPS / SECRETÁRIA”.<br>
                                    CSV também é aceito com RAMAL + NOME ou SETOR.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('Importar planilha'); ?>
                </form>
            </div>

                        <div class="smtt-admin-card smtt-ramais-manual-card">
                <h2>
                    <?php echo !empty($editing_ramal) ? 'Editar ramal' : 'Cadastrar ramal manualmente'; ?>
                </h2>

                <form method="post" class="smtt-ramais-manual-form">
                    <?php wp_nonce_field('smtt_salvar_ramal_manual', 'smtt_ramais_manual_nonce'); ?>

                    <input
                        type="hidden"
                        name="ramal_id"
                        value="<?php echo esc_attr($editing_ramal['id'] ?? 0); ?>"
                    >

                    <div class="smtt-ramais-form-grid">
                        <p>
                            <label>Nome *</label>
                            <input
                                type="text"
                                name="nome"
                                value="<?php echo esc_attr($editing_ramal['nome'] ?? ''); ?>"
                                required
                            >
                        </p>

                        <p>
                            <label>Setor *</label>
                            <input
                                type="text"
                                name="setor"
                                value="<?php echo esc_attr($editing_ramal['setor'] ?? ''); ?>"
                                required
                            >
                        </p>

                        <p>
                            <label>Cargo/Função</label>
                            <input
                                type="text"
                                name="cargo"
                                value="<?php echo esc_attr($editing_ramal['cargo'] ?? ''); ?>"
                            >
                        </p>

                        <p>
                            <label>Ramal *</label>
                            <input
                                type="text"
                                name="ramal"
                                value="<?php echo esc_attr($editing_ramal['ramal'] ?? ''); ?>"
                                required
                            >
                        </p>

                        <p>
                            <label>Telefone direto</label>
                            <input
                                type="text"
                                name="telefone_direto"
                                value="<?php echo esc_attr($editing_ramal['telefone_direto'] ?? ''); ?>"
                            >
                        </p>

                        <p>
                            <label>Status</label>
                            <select name="status">
                                <option value="ativo" <?php selected($editing_ramal['status'] ?? 'ativo', 'ativo'); ?>>Ativo</option>
                                <option value="inativo" <?php selected($editing_ramal['status'] ?? 'ativo', 'inativo'); ?>>Inativo</option>
                            </select>
                        </p>
                    </div>
                    

                    <p class="smtt-ramais-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php echo !empty($editing_ramal) ? 'Salvar alterações' : 'Cadastrar ramal'; ?>
                        </button>

                        <?php if (!empty($editing_ramal)) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-directory')); ?>" class="button">
                                Cancelar edição
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            

            <div class="smtt-ramais-grid">
                <div class="smtt-admin-card">
                    <h2>Consultar ramais</h2>

                    <form method="get">
                        <input type="hidden" name="page" value="smtt-intranet-directory">

                        <p class="search-box">
                            <label class="screen-reader-text" for="ramais-search-input">Buscar ramais:</label>
                            <input type="search" id="ramais-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar por nome, setor, cargo ou ramal">
                            <input type="submit" class="button" value="Buscar">
                        </p>

                        <div class="smtt-ramais-filters">
                            <select name="setor">
                                <option value="">Todos os setores</option>
                                <?php foreach ($setores as $setor_item) : ?>
                                    <option value="<?php echo esc_attr($setor_item); ?>" <?php selected($setor, $setor_item); ?>>
                                        <?php echo esc_html($setor_item); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="status">
                                <option value="">Todos os status</option>
                                <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                                <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                            </select>

                            <button type="submit" class="button">Filtrar</button>
                        </div>
                    </form>

                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Setor</th>
                                <th>Cargo/Função</th>
                                <th>Ramal</th>
                                <th>Telefone</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ramais)) : ?>
                                <?php foreach ($ramais as $ramal) : ?>
                                    <tr>
                                        <td><?php echo esc_html($ramal['nome']); ?></td>
                                        <td><?php echo esc_html($ramal['setor']); ?></td>
                                        <td><?php echo esc_html($ramal['cargo']); ?></td>
                                        <td><strong><?php echo esc_html($ramal['ramal']); ?></strong></td>
                                        <td><?php echo esc_html($ramal['telefone_direto']); ?></td>
                                        <td><?php echo esc_html(ucfirst($ramal['status'])); ?></td>
                                        <td>
                                        <div class="smtt-ramais-row-actions">
                                            <a
                                                class="button button-small"
                                                href="<?php echo esc_url(admin_url('admin.php?page=smtt-intranet-directory&edit_ramal=' . absint($ramal['id']))); ?>"
                                            >
                                                Editar
                                            </a>

                                            <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este ramal?');">
                                                <?php wp_nonce_field('smtt_excluir_ramal', 'smtt_ramais_delete_nonce'); ?>
                                                <input type="hidden" name="delete_ramal_id" value="<?php echo esc_attr($ramal['id']); ?>">
                                                <button type="submit" class="button button-small button-link-delete">
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7">Nenhum ramal encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                            
                <div class="smtt-admin-card">
                    <h2>Últimas importações</h2>

                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Data</th>
                                <th>Linhas</th>
                                <th>Inseridos</th>
                                <th>Atualizado</th>
                                <th>Ignorados</th>
                                <th>Erros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($imports)) : ?>
                                <?php foreach ($imports as $import) : ?>
                                    <tr>
                                        <td><?php echo esc_html($import['arquivo_nome']); ?></td>
                                        <td><?php echo esc_html($import['created_at']); ?></td>
                                        <td><?php echo esc_html($import['total_linhas']); ?></td>
                                        <td><?php echo esc_html($import['total_inseridos']); ?></td>
                                        <td><?php echo esc_html($import['total_atualizados']); ?></td>
                                        <td><?php echo esc_html($import['total_ignorados']); ?></td>
                                        <td><?php echo esc_html($import['total_erros']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7">Nenhuma importação realizada ainda.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                       </div><!-- .smtt-ramais-grid -->
        </div><!-- .smtt-ramais-admin -->
        <?php
    }
}