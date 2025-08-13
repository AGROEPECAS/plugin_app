<?php
/*
Plugin Name: Consulta de Peças Escalável AGROEPECAS
Description: Ferramenta de consulta de peças baseada em tabelas internas do WordPress, atualizadas automaticamente via Google Planilhas.
Version: 2.6
Author: AGRO&PEÇAS
*/

if (!defined('ABSPATH')) exit;

function consulta_pecas_shortcode($atts) {
    $a = shortcode_atts([
        'nome'     => 'Conversão de Peças',
        'tabela'   => 'wp_pecas_zf',
        'planilha' => '',
        'aba'      => '',
        'fonte'    => 'tabela', // futuro uso
    ], $atts);

    global $wpdb;
    $montadoras  = $wpdb->get_col("SELECT DISTINCT oem FROM {$a['tabela']} WHERE oem IS NOT NULL AND oem <> '' ORDER BY oem");
    $fornecedores = $wpdb->get_col("SELECT DISTINCT oes FROM {$a['tabela']} WHERE oes IS NOT NULL AND oes <> '' ORDER BY oes");

    // Processa pesquisa
    $resultados = [];
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && (
        isset($_GET['search_oem']) || isset($_GET['search_oes']) ||
        isset($_GET['codes_oem']) || isset($_GET['codes_oes'])
    )) {
        $where  = [];
        $params = [];

        // Pesquisa por OEM
        if (!empty($_GET['search_oem']) && $_GET['search_oem'] !== 'Todos') {
            $where[]  = "oem = %s";
            $params[] = $_GET['search_oem'];
        }
        if (!empty($_GET['codes_oem'])) {
            $codes = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $_GET['codes_oem'])));
            $likes = [];
            foreach ($codes as $code) {
                if (strlen($code) >= 4) {
                    $likes[]  = "oem_code LIKE %s";
                    $params[] = '%' . $code . '%';
                }
            }
            if ($likes) {
                $where[] = '(' . implode(' OR ', $likes) . ')';
            }
        }

        // Pesquisa por OES
        if (!empty($_GET['search_oes']) && $_GET['search_oes'] !== 'Todos') {
            $where[]  = "oes = %s";
            $params[] = $_GET['search_oes'];
        }
        if (!empty($_GET['codes_oes'])) {
            $codes = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $_GET['codes_oes'])));
            $likes = [];
            foreach ($codes as $code) {
                if (strlen($code) >= 4) {
                    $likes[]  = "oes_code LIKE %s";
                    $params[] = '%' . $code . '%';
                }
            }
            if ($likes) {
                $where[] = '(' . implode(' OR ', $likes) . ')';
            }
        }

        $sql = "SELECT * FROM {$a['tabela']}";
        if ($where) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " LIMIT 20";
        $resultados = $params ? $wpdb->get_results($wpdb->prepare($sql, ...$params)) : $wpdb->get_results($sql);
    }

    ob_start();
    ?>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600&display=swap" rel="stylesheet">
    <style>
    .consulta-pecas-wrapper {
        max-width: 800px;
        margin: 0 auto 40px auto;
    }
    .consulta-pecas-header {
        background: #171616;
        color: #fff;
        border-radius: 16px 16px 0 0;
        padding: 18px 32px 10px 32px;
        display: flex;
        align-items: center;
        gap: 18px;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
        width: 100%;
        box-sizing: border-box;
    }
    .consulta-pecas-header .logo {
        height: 48px;
        width: auto;
        margin-right: 11px;
        user-select: none;
        pointer-events: none;
    }
    .consulta-pecas-header .title {
        font-size: 2rem;
        font-weight: 600;
        letter-spacing: 1px;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
    }
    .consulta-pecas-main {
        background: linear-gradient(120deg, #221300 0%, #EE8800 100%);
        padding: 32px 32px 24px 32px;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 6px 24px rgba(0,0,0,0.10);
        font-family: 'Montserrat', Arial, sans-serif;
        font-size: 0.75rem;
        width: 100%;
        box-sizing: border-box;
    }
    .consulta-pecas-form-flex {
        display: flex;
        gap: 18px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        justify-content: center;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
        font-size: 0.75rem;
    }
    .consulta-pecas-form-box {
        background: #fff;
        border-radius: 14px;
        padding: 14px 14px 10px 14px;
        width: 330px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        min-width: 230px;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
        font-size: 0.80rem;
    }
    .consulta-pecas-form-box h3 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
    }
    .consulta-pecas-form-box label {
        font-size: 0.70rem;
        color: #555;
        margin-bottom: 2px;
        display: block;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
    }
    .consulta-pecas-form-box select,
    .consulta-pecas-form-box textarea {
        width: 100%;
        font-size: 0.90rem;
        margin-bottom: 9px;
        border-radius: 7px;
        border: 1px solid #e0e0e0;
        padding: 7px 8px;
        background: #f9f9f9;
        color: #222;
        transition: border 0.2s;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    .consulta-pecas-form-box select:focus,
    .consulta-pecas-form-box textarea:focus {
        border-color: #EE8800;
        outline: none;
    }
    .consulta-pecas-form-box textarea {
        resize: vertical;
        min-height: 45px;
        max-height: 120px;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    .consulta-pecas-btn {
        background: #EE8800;
        color: #fff;
        font-weight: bold;
        border: none;
        padding: 9px 21px;
        border-radius: 9px;
        font-size: 0.90rem;
        box-shadow: 0 2px 8px rgba(103,58,183,0.11);
        cursor: pointer;
        transition: background 0.2s;
        margin: 12px auto 0 auto;
        display: block;
        user-select: none;
        font-family: 'Montserrat', Arial, sans-serif;
    }
    .consulta-pecas-btn.limpar { background: #b71c1c; }
    .consulta-pecas-btn:hover { background: #e67e22; }
    #consulta-pecas-resultados {
        font-family: Consolas, 'Courier New', monospace !important;
        font-size: 0.85rem;
    }
    .consulta-pecas-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.03);
        margin-top: 18px;
        overflow: hidden;
        user-select: none;
        font-family: Consolas, 'Courier New', monospace !important;
        font-size: 0.85rem;
    }
    .consulta-pecas-table th, .consulta-pecas-table td {
        padding: 9px 7px;
        text-align: center;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.85rem;
        user-select: none;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    .consulta-pecas-table th {
        background: #f7f7f7;
        color: #222;
        font-weight: 600;
        border-top: none;
        user-select: none;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    .consulta-pecas-table td {
        color: #444;
        background: #fff;
        user-select: none;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    .consulta-pecas-table tr:last-child td {
        border-bottom: none;
        user-select: none;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    .consulta-pecas-table .no-result {
        text-align: center;
        color: #b71c1c;
        font-style: italic;
        padding: 14px;
        user-select: none;
        font-family: Consolas, 'Courier New', monospace !important;
    }
    @media (max-width: 900px) {
        .consulta-pecas-wrapper { max-width: 100vw; }
        .consulta-pecas-main { padding: 6px 2px 6px 2px; }
        .consulta-pecas-form-flex { flex-direction: column; gap: 8px; }
        .consulta-pecas-form-box { width: 100%; min-width: unset; }
        .consulta-pecas-header { flex-direction: column; gap: 4px; padding: 10px 8px 4px 8px;}
        .consulta-pecas-btn { width: 100%; margin: 10px 0 0 0; }
        #consulta-pecas-resultados { font-size: 0.80rem; }
    }
    @media print {
        .consulta-pecas-header, .consulta-pecas-main, .consulta-pecas-table {
            display: none !important;
        }
        body::before {
            content: "Impressão desativada nesta página.";
            font-size: 2em;
            color: #b71c1c;
            display: block;
            margin: 50px;
        }
    }
    </style>
    <div class="consulta-pecas-wrapper">
        <div class="consulta-pecas-header">
            <img class="logo" src="https://i.imgur.com/xqCuadH.png" alt="Logo AGROEPECAS" />
            <div class="title"><?php echo esc_html($a['nome']); ?></div>
        </div>
        <div class="consulta-pecas-main">
            <form method="get" class="consulta-pecas-form-flex" id="consulta-pecas-form" autocomplete="off">
                <div class="consulta-pecas-form-box">
                    <h3>Pesquisa por OEM</h3>
                    <label for="search_oem">Selecione a Montadora OEM</label>
                    <select id="search_oem" name="search_oem">
                        <option value="Todos">Todos</option>
                        <?php foreach ($montadoras as $montadora): ?>
                            <option value="<?php echo esc_attr($montadora); ?>" <?php if (isset($_GET['search_oem']) && $_GET['search_oem'] == $montadora) echo 'selected'; ?>>
                                <?php echo esc_html($montadora); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="codes_oem">Códigos OEM (um por linha)</label>
                    <textarea id="codes_oem" name="codes_oem" placeholder="Digite os códigos OEM, um por linha"><?php echo esc_textarea($_GET['codes_oem'] ?? ''); ?></textarea>
                </div>
                <div class="consulta-pecas-form-box">
                    <h3>Pesquisa por OES</h3>
                    <label for="search_oes">Selecione o Fornecedor OES</label>
                    <select id="search_oes" name="search_oes">
                        <option value="Todos">Todos</option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <option value="<?php echo esc_attr($fornecedor); ?>" <?php if (isset($_GET['search_oes']) && $_GET['search_oes'] == $fornecedor) echo 'selected'; ?>>
                                <?php echo esc_html($fornecedor); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="codes_oes">Códigos OES (um por linha)</label>
                    <textarea id="codes_oes" name="codes_oes" placeholder="Digite os códigos OES, um por linha"><?php echo esc_textarea($_GET['codes_oes'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="consulta-pecas-btn" id="consulta-pecas-btn">Pesquisar</button>
            </form>
            <div id="consulta-pecas-resultados">
                <table class="consulta-pecas-table">
                    <thead>
                        <tr>
                            <th>Montadora OEM</th>
                            <th>Código OEM</th>
                            <th>Código OES</th>
                            <th>Fornecedor OES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultados): ?>
                            <?php foreach ($resultados as $row): ?>
                                <tr>
                                    <td><?php echo esc_html($row->oem); ?></td>
                                    <td><?php echo esc_html($row->oem_code); ?></td>
                                    <td><?php echo esc_html($row->oes_code); ?></td>
                                    <td><?php echo esc_html($row->oes); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-result">Nenhum resultado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('consulta-pecas-form');
        var btn = document.getElementById('consulta-pecas-btn');
        var resultados = document.getElementById('consulta-pecas-resultados');
        var isSearch = true;

        // Detecta se há parâmetros de pesquisa na URL
        var urlParams = new URLSearchParams(window.location.search);
        var hasSearch = (
            (urlParams.get('search_oem') && urlParams.get('search_oem') !== 'Todos') ||
            urlParams.get('codes_oem') ||
            (urlParams.get('search_oes') && urlParams.get('search_oes') !== 'Todos') ||
            urlParams.get('codes_oes')
        );

        if (hasSearch) {
            btn.textContent = 'Limpar';
            btn.classList.add('limpar');
            isSearch = false;
        }

        btn.addEventListener('click', function(e) {
            if (isSearch) {
                // Submete normalmente
            } else {
                e.preventDefault();
                form.reset();
                btn.textContent = 'Pesquisar';
                btn.classList.remove('limpar');
                isSearch = true;
                document.getElementById('codes_oem').value = '';
                document.getElementById('codes_oes').value = '';
                window.history.replaceState({}, document.title, window.location.pathname);
                var tabela = document.querySelector('.consulta-pecas-table tbody');
                if (tabela) {
                    tabela.innerHTML = '<tr><td colspan="4" class="no-result">Nenhum resultado.</td></tr>';
                }
            }
        });

        // Bloqueia Ctrl+C, botão direito e impressão nos resultados
        if (resultados) {
            resultados.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
            resultados.addEventListener('copy', function(e) {
                e.preventDefault();
            });
            resultados.addEventListener('cut', function(e) {
                e.preventDefault();
            });
            resultados.addEventListener('selectstart', function(e) {
                e.preventDefault();
            });
        }

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P' || e.key === 'c' || e.key === 'C')) {
                e.preventDefault();
                alert('Esta função está desativada nesta página.');
            }
        });

        window.onbeforeprint = function() {
            alert('Impressão desativada nesta página!');
        };
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('consulta_pecas', 'consulta_pecas_shortcode');

// Atualização da tabela via Google Planilha
function consulta_pecas_atualiza_tabela($planilha_url, $aba, $tabela) {
    global $wpdb;
    if (!preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $planilha_url, $m)) return "Erro: Link da planilha inválido.";
    $planilha_id = $m[1];
    $url = "https://docs.google.com/spreadsheets/d/$planilha_id/gviz/tq?tqx=out:json&sheet=$aba";
    $response = wp_remote_get($url);
    if (is_wp_error($response)) return "Erro ao acessar a planilha.";
    $body = wp_remote_retrieve_body($response);
    $jsonText = substr($body, strpos($body, '{'), strrpos($body, '}') - strpos($body, '{') + 1);
    $json = json_decode($jsonText, true);
    if (!$json || !isset($json['table']['rows'])) return "Erro ao ler dados da planilha.";
    $rows = $json['table']['rows'];

    $wpdb->query("CREATE TABLE IF NOT EXISTS $tabela (
        id INT AUTO_INCREMENT PRIMARY KEY,
        oem VARCHAR(255), oem_code VARCHAR(255), oes_code VARCHAR(255), oes VARCHAR(255)
    )");

    $wpdb->query("TRUNCATE TABLE $tabela");

    foreach ($rows as $i => $row) {
        if ($i == 0) continue;
        $c = $row['c'];
        if ($c) {
            $wpdb->insert($tabela, [
                'oem'       => $c[0]['v'] ?? '',
                'oem_code'  => $c[1]['v'] ?? '',
                'oes_code'  => $c[2]['v'] ?? '',
                'oes'       => $c[3]['v'] ?? ''
            ]);
        }
    }
    return "Tabela '$tabela' atualizada com sucesso!";
}

// Cron para atualização automática
if (!wp_next_scheduled('consulta_pecas_cron')) {
    wp_schedule_event(time(), 'daily', 'consulta_pecas_cron');
}
add_action('consulta_pecas_cron', function() {
    $ferramentas = [
        [
            'planilha' => 'https://docs.google.com/spreadsheets/d/xxxxxx1/edit',
            'aba'      => 'Sheet1',
            'tabela'   => 'wp_pecas_zf'
        ],
        [
            'planilha' => 'https://docs.google.com/spreadsheets/d/xxxxxx2/edit',
            'aba'      => 'Sheet1',
            'tabela'   => 'wp_pecas_bosch'
        ]
    ];
    foreach ($ferramentas as $f) {
        consulta_pecas_atualiza_tabela($f['planilha'], $f['aba'], $f['tabela']);
    }
});
?>