<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../front/data/mock.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (preg_match('#^/assets/([a-zA-Z0-9._/-]+)$#', $path, $matches)) {
    $file = __DIR__ . '/../front/assets/' . $matches[1];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = ['css' => 'text/css', 'js' => 'application/javascript'][$ext] ?? 'application/octet-stream';

    if (!is_file($file)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Cache-Control: no-cache');
    readfile($file);
    exit;
}

$logged = !empty($_SESSION['logged']);

if (!$logged && $path !== '/login') {
    header('Location: /login');
    exit;
}

if ($logged && $path === '/login') {
    header('Location: /edital');
    exit;
}

$nextId = function (array $items): int {
    $max = 0;
    foreach ($items as $item) {
        $max = max($max, (int) $item['id']);
    }

    return $max + 1;
};

$nextSessionId = function (string $key) use ($nextId, $editais, $retornos, $contratos): int {
    $base = match ($key) {
        'editais' => $editais,
        'retornos' => $retornos,
        'contratos' => $contratos,
        default => [],
    };

    return $nextId(array_merge($base, $_SESSION[$key] ?? []));
};

switch ($path) {
    case '/login':
        if ($method === 'POST') {
            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $senha = (string) ($_POST['senha'] ?? '');

            if ($usuario === 'admin' && $senha === 'admin123') {
                $_SESSION['logged'] = true;
                $_SESSION['user'] = ['name' => 'Administrador', 'role' => 'admin'];
                header('Location: /edital');
                exit;
            }

            $_SESSION['login_error'] = 'Usuário ou senha inválidos.';
            header('Location: /login');
            exit;
        }

        require __DIR__ . '/../front/views/login.php';
        break;

    case '/logout':
        session_destroy();
        header('Location: /login');
        exit;

    case '/edital/novo':
        require __DIR__ . '/../front/views/edital_novo.php';
        break;

    case '/edital':
        if ($method === 'POST') {
            $numero = trim((string) ($_POST['numero'] ?? ''));
            $titulo = trim((string) ($_POST['titulo'] ?? ''));
            $entidade = trim((string) ($_POST['entidade'] ?? ''));
            $orgao = $entidade !== '' ? $entidade : trim((string) ($_POST['orgao'] ?? ''));
            $ano = trim((string) ($_POST['ano'] ?? ''));
            $tipo = trim((string) ($_POST['tipo'] ?? ''));
            $processo = trim((string) ($_POST['processo'] ?? ''));
            $site = trim((string) ($_POST['site'] ?? ''));
            $licenca = !empty($_POST['licenca']);
            $entrega = trim((string) ($_POST['entrega'] ?? ''));
            $total = (float) ($_POST['total'] ?? 0);

            $itens = [];
            foreach ((array) ($_POST['item_codigo'] ?? []) as $i => $codigo) {
                if (trim((string) $codigo) === '') {
                    continue;
                }
                $itens[] = [
                    'codigo' => $codigo,
                    'descricao' => trim((string) ($_POST['item_descricao'][$i] ?? '')),
                    'unidade' => trim((string) ($_POST['item_unidade'][$i] ?? '')),
                    'quantidade' => (float) ($_POST['item_quantidade'][$i] ?? 0),
                    'unitario' => (float) ($_POST['item_unitario'][$i] ?? 0),
                ];
            }

            $arquivos = [];
            foreach (($_FILES['documentos']['name'] ?? []) as $name) {
                if (is_string($name) && $name !== '') {
                    $arquivos[] = basename($name);
                }
            }

            if ($numero !== '' && $titulo !== '' && $orgao !== '') {
                $_SESSION['editais'][] = [
                    'id' => $nextSessionId('editais'),
                    'numero' => $numero,
                    'titulo' => $titulo,
                    'orgao' => $orgao,
                    'ano' => $ano,
                    'tipo' => $tipo,
                    'processo' => $processo,
                    'site' => $site,
                    'licenca' => $licenca,
                    'entrega' => $entrega,
                    'itens' => $itens,
                    'total' => $total,
                    'documentos' => $arquivos,
                    'status' => 'aberto',
                    'data' => date('d/m/Y'),
                ];
                $_SESSION['flash'] = 'Edital salvo com sucesso.';
            }

            header('Location: /edital');
            exit;
        }

        require __DIR__ . '/../front/views/edital.php';
        break;

    case '/edital/remover':
        $id = (int) ($_POST['id'] ?? 0);
        $_SESSION['editais'] = array_values(array_filter(
            $_SESSION['editais'] ?? [],
            fn(array $e) => (int) $e['id'] !== $id
        ));
        $_SESSION['flash'] = 'Edital excluído.';
        header('Location: /edital');
        exit;

    case '/licitantes':
        if ($method === 'POST') {
            $editalId = (int) ($_POST['edital_id'] ?? 0);
            $licitante = trim((string) ($_POST['licitante'] ?? ''));
            $cnpj = trim((string) ($_POST['cnpj'] ?? ''));

            if ($editalId > 0 && $licitante !== '' && $cnpj !== '') {
                $arquivos = [];
                foreach (($_FILES['propostas']['name'] ?? []) as $name) {
                    if (is_string($name) && $name !== '') {
                        $arquivos[] = basename($name);
                    }
                }

                $_SESSION['retornos'][] = [
                    'id' => $nextSessionId('retornos'),
                    'edital_id' => $editalId,
                    'licitante' => $licitante,
                    'cnpj' => $cnpj,
                    'status' => $arquivos ? 'aguardando' : 'aguardando',
                    'arquivos' => $arquivos,
                    'data' => date('d/m/Y'),
                ];
                $_SESSION['flash'] = 'Retorno do licitante salvo com sucesso.';
            }

            header('Location: /licitantes');
            exit;
        }

        require __DIR__ . '/../front/views/licitantes.php';
        break;

    case '/licitantes/remover':
        $id = (int) ($_POST['id'] ?? 0);
        $_SESSION['retornos'] = array_values(array_filter(
            $_SESSION['retornos'] ?? [],
            fn(array $r) => (int) $r['id'] !== $id
        ));
        $_SESSION['flash'] = 'Retorno excluído.';
        header('Location: /licitantes');
        exit;

    case '/contratos':
        if ($method === 'POST') {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $numero = trim((string) ($_POST['numero'] ?? ''));
            $licitante = trim((string) ($_POST['licitante'] ?? ''));
            $status = trim((string) ($_POST['status'] ?? 'ativo'));
            $valor = (float) ($_POST['valor'] ?? 0);
            $percentual = (int) ($_POST['percentual'] ?? 0);
            $vigencia = trim((string) ($_POST['vigencia'] ?? ''));

            if ($nome !== '' && $numero !== '' && $licitante !== '') {
                $vigenciaBr = $vigencia !== '' ? date('d/m/Y', strtotime($vigencia)) : '';
                $_SESSION['contratos'][] = [
                    'id' => $nextSessionId('contratos'),
                    'nome' => $nome,
                    'numero' => $numero,
                    'licitante' => $licitante,
                    'status' => $status,
                    'percentual' => max(0, min(100, $percentual)),
                    'valor' => $valor,
                    'vigencia' => $vigenciaBr,
                ];
                $_SESSION['flash'] = 'Contrato cadastrado com sucesso.';
            }

            header('Location: /contratos');
            exit;
        }

        require __DIR__ . '/../front/views/contratos.php';
        break;

    case '/contratos/remover':
        $id = (int) ($_POST['id'] ?? 0);
        $_SESSION['contratos'] = array_values(array_filter(
            $_SESSION['contratos'] ?? [],
            fn(array $c) => (int) $c['id'] !== $id
        ));
        $_SESSION['flash'] = 'Contrato excluído.';
        header('Location: /contratos');
        exit;

    default:
        http_response_code(404);
        echo '<h1>404</h1><p>Página não encontrada.</p>';
        break;
}
