<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../src/autoload.php';

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Env;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\CompanyService;
use App\Services\ContractService;
use App\Services\EvaluationService;
use App\Services\ExtractionService;
use App\Services\LicitationService;
use App\Services\ProposalService;
use App\Services\ComparisonService;
use App\Services\ScoreService;
use App\Services\UploadService;

Env::load(__DIR__ . '/../.env');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (preg_match('#^/assets/([a-zA-Z0-9._/-]+)$#', $path, $matches)) {
    $file = __DIR__ . '/../front/assets/' . $matches[1];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
    ][$ext] ?? 'application/octet-stream';

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
    header('Location: /dashboard');
    exit;
}

if ($logged && $path === '/') {
    header('Location: /dashboard');
    exit;
}

if ($method === 'POST' && !Csrf::verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo '<h1>403</h1><p>Sessão expirada. Volte à página anterior e tente novamente.</p>';
    exit;
}

$pdo = Database::connection();
$currentUser = AuthService::currentUser();
$isAdmin = AuthService::isAdmin();
$companyId = $currentUser['company_id'] ?? null;

$adminOnlyPrefixes = [
    '/empresas', '/edital/novo', '/edital/remover', '/edital/requisito', '/licitantes/remover',
    '/contratos', '/processar', '/analisar', '/analise', '/avaliacao/revisar', '/auditoria',
];
foreach ($adminOnlyPrefixes as $prefix) {
    if ($logged && str_starts_with($path, $prefix) && !$isAdmin) {
        http_response_code(403);
        echo '<h1>403</h1><p>Acesso não permitido para este perfil.</p>';
        exit;
    }
}

switch ($path) {
    case '/dashboard':
        require __DIR__ . '/../front/views/dashboard.php';
        break;

    case '/login':
        if ($method === 'POST') {
            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $senha = (string) ($_POST['senha'] ?? '');

            $user = AuthService::attempt($pdo, $usuario, $senha);

            if ($user) {
                AuthService::login($pdo, $user);

                if (!empty($_POST['lembrar'])) {
                    $cookieParams = session_get_cookie_params();
                    setcookie(session_name(), session_id(), [
                        'expires' => time() + 30 * 24 * 60 * 60,
                        'path' => $cookieParams['path'],
                        'domain' => $cookieParams['domain'],
                        'secure' => $cookieParams['secure'],
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }

                AuditLogger::log($pdo, (int) $user['id'], 'login', 'user', (int) $user['id']);
                header('Location: /dashboard');
                exit;
            }

            AuditLogger::log($pdo, null, 'login_failed', 'user', null, ['usuario' => $usuario]);
            $_SESSION['login_error'] = 'Usuário ou senha inválidos.';
            header('Location: /login');
            exit;
        }

        require __DIR__ . '/../front/views/login.php';
        break;

    case '/logout':
        if ($currentUser) {
            AuditLogger::log($pdo, $currentUser['id'], 'logout', 'user', $currentUser['id']);
        }
        session_destroy();
        header('Location: /login');
        exit;

    case '/edital/novo':
        require __DIR__ . '/../front/views/edital_novo.php';
        break;

    case '/edital':
        if ($method === 'POST') {
            try {
                $licitationId = LicitationService::createFromWizard($pdo, $_POST, (int) $currentUser['id']);

                if (!empty($_FILES['documentos']['name'][0])) {
                    $uploaded = UploadService::storeManyPdfs(
                        $_FILES['documentos'],
                        __DIR__ . '/../storage/uploads/licitations/' . $licitationId
                    );
                    LicitationService::attachDocuments(
                        $pdo,
                        $licitationId,
                        $uploaded,
                        trim((string) ($_POST['documento_tipo'] ?? '')) ?: null,
                        (int) $currentUser['id']
                    );
                }

                AuditLogger::log($pdo, $currentUser['id'], 'create', 'licitation', $licitationId);
                $_SESSION['flash'] = 'Licitação cadastrada com sucesso.';
                header('Location: /edital?id=' . $licitationId);
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: /edital/novo');
            }
            exit;
        }

        $licitationId = (int) ($_GET['id'] ?? 0);

        if ($licitationId > 0) {
            $licitacao = LicitationService::find($pdo, $licitationId);

            if (!$licitacao) {
                http_response_code(404);
                echo '<h1>404</h1><p>Licitação não encontrada.</p>';
                break;
            }

            $itens = LicitationService::items($pdo, $licitationId);
            $unidades = LicitationService::units($pdo, $licitationId);
            $requisitos = LicitationService::requirements($pdo, $licitationId);
            $propostasLic = ProposalService::listForLicitation($pdo, $licitationId);
            $propostas = $isAdmin
                ? $propostasLic
                : array_values(array_filter($propostasLic, fn(array $p): bool => (int) $p['company_id'] === (int) $companyId));

            require __DIR__ . '/../front/views/edital_detalhe.php';
            break;
        }

        $licitacoes = LicitationService::list($pdo);
        require __DIR__ . '/../front/views/edital.php';
        break;

    case '/edital/remover':
        $id = (int) ($_POST['id'] ?? 0);
        try {
            LicitationService::delete($pdo, $id);
            AuditLogger::log($pdo, $currentUser['id'], 'delete', 'licitation', $id);
            $_SESSION['flash'] = 'Licitação excluída.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: /edital');
        exit;

    case '/edital/requisito':
        $licitationId = (int) ($_POST['licitation_id'] ?? 0);
        try {
            $reqId = LicitationService::addRequirement($pdo, $licitationId, $_POST, (int) $currentUser['id']);
            AuditLogger::log($pdo, $currentUser['id'], 'create', 'licitation_requirement', $reqId);
            $_SESSION['flash'] = 'Requisito adicionado.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: /edital?id=' . $licitationId);
        exit;

    case '/edital/requisito/remover':
        $licitationId = (int) ($_POST['licitation_id'] ?? 0);
        $reqId = (int) ($_POST['id'] ?? 0);
        LicitationService::deleteRequirement($pdo, $reqId);
        AuditLogger::log($pdo, $currentUser['id'], 'delete', 'licitation_requirement', $reqId);
        $_SESSION['flash'] = 'Requisito removido.';
        header('Location: /edital?id=' . $licitationId);
        exit;

    case '/licitantes':
        if ($method === 'POST') {
            $licitationId = (int) ($_POST['licitation_id'] ?? 0);
            $targetCompanyId = $isAdmin ? (int) ($_POST['company_id'] ?? 0) : (int) $companyId;

            try {
                if (!$targetCompanyId) {
                    throw new RuntimeException('Selecione a empresa.');
                }

                $proposalId = ProposalService::create(
                    $pdo,
                    $licitationId,
                    $targetCompanyId,
                    (int) $currentUser['id'],
                    $_FILES['documentos'] ?? []
                );

                AuditLogger::log($pdo, $currentUser['id'], 'create', 'proposal', $proposalId);
                $_SESSION['flash'] = 'Proposta registrada com sucesso.';
                header('Location: /licitantes?id=' . $proposalId);
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: /licitantes');
            }
            exit;
        }

        $proposalIdParam = (int) ($_GET['id'] ?? 0);

        if ($proposalIdParam > 0) {
            $proposta = ProposalService::find($pdo, $proposalIdParam);

            if (!$proposta || (!$isAdmin && (int) $proposta['company_id'] !== (int) $companyId)) {
                http_response_code(404);
                echo '<h1>404</h1><p>Proposta não encontrada.</p>';
                break;
            }

            $documentos = ProposalService::documents($pdo, $proposalIdParam);
            $extractions = ExtractionService::forProposal($pdo, $proposalIdParam);
            $evaluations = EvaluationService::forProposal($pdo, $proposalIdParam);
            $evaluationSummary = ScoreService::find($pdo, $proposalIdParam);

            require __DIR__ . '/../front/views/proposta_detalhe.php';
            break;
        }

        $propostas = $isAdmin ? ProposalService::listForAdmin($pdo) : ProposalService::listForCompany($pdo, (int) $companyId);
        $licitacoesAbertas = LicitationService::listOpen($pdo);
        $empresasDisponiveis = $isAdmin ? CompanyService::list($pdo) : [];

        require __DIR__ . '/../front/views/licitantes.php';
        break;

    case '/processar':
        $results = ExtractionService::runPendingJobs($pdo, 20);
        $done = count(array_filter($results, fn(array $r): bool => $r['status'] === 'done'));
        $failed = count(array_filter($results, fn(array $r): bool => $r['status'] === 'failed'));
        AuditLogger::log($pdo, $currentUser['id'], 'process_queue', 'job', null, ['done' => $done, 'failed' => $failed]);
        $_SESSION['flash'] = $results
            ? "Processamento concluído: {$done} documento(s) extraído(s), {$failed} com falha."
            : 'Nenhum documento pendente de extração.';
        header('Location: /licitantes');
        exit;

    case '/analisar':
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);
        try {
            $result = EvaluationService::evaluateProposal($pdo, $proposalId);
            AuditLogger::log($pdo, $currentUser['id'], 'analyze', 'proposal', $proposalId, $result);
            $_SESSION['flash'] = "Análise concluída: {$result['saved']} requisito(s) avaliado(s)"
                . ($result['flagged'] > 0 ? ", {$result['flagged']} com necessidade de revisão humana." : '.');
        } catch (Throwable $e) {
            AuditLogger::log($pdo, $currentUser['id'], 'analyze_failed', 'proposal', $proposalId, ['error' => $e->getMessage()]);
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: /licitantes?id=' . $proposalId);
        exit;

    case '/licitantes/remover':
        $id = (int) ($_POST['id'] ?? 0);
        ProposalService::delete($pdo, $id);
        AuditLogger::log($pdo, $currentUser['id'], 'delete', 'proposal', $id);
        $_SESSION['flash'] = 'Proposta excluída.';
        header('Location: /licitantes');
        exit;

    case '/contratos':
        if ($method === 'POST') {
            try {
                $id = ContractService::create($pdo, [
                    'company_id' => (int) ($_POST['company_id'] ?? 0) ?: null,
                    'name' => trim((string) ($_POST['nome'] ?? '')),
                    'number' => trim((string) ($_POST['numero'] ?? '')),
                    'status' => trim((string) ($_POST['status'] ?? 'ativo')),
                    'value' => (float) ($_POST['valor'] ?? 0),
                    'percentage' => max(0, min(100, (int) ($_POST['percentual'] ?? 0))),
                    'end_date' => trim((string) ($_POST['vigencia'] ?? '')),
                ]);
                AuditLogger::log($pdo, $currentUser['id'], 'create', 'contract', $id);
                $_SESSION['flash'] = 'Contrato cadastrado com sucesso.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
            header('Location: /contratos');
            exit;
        }

        $contratos = ContractService::list($pdo);
        $empresasDisponiveis = CompanyService::list($pdo);
        require __DIR__ . '/../front/views/contratos.php';
        break;

    case '/contratos/remover':
        $id = (int) ($_POST['id'] ?? 0);
        ContractService::delete($pdo, $id);
        AuditLogger::log($pdo, $currentUser['id'], 'delete', 'contract', $id);
        $_SESSION['flash'] = 'Contrato excluído.';
        header('Location: /contratos');
        exit;

    case '/analise':
        $licitationId = (int) ($_GET['licitation_id'] ?? 0);
        $licitacao = LicitationService::find($pdo, $licitationId);

        if (!$licitacao) {
            http_response_code(404);
            echo '<h1>404</h1><p>Licitação não encontrada.</p>';
            break;
        }

        $matrix = ComparisonService::matrix($pdo, $licitationId);
        require __DIR__ . '/../front/views/analise.php';
        break;

    case '/avaliacao':
        $evaluationId = (int) ($_GET['id'] ?? 0);
        $evaluation = EvaluationService::find($pdo, $evaluationId);

        if (!$evaluation || (!$isAdmin && (int) $evaluation['company_id'] !== (int) $companyId)) {
            http_response_code(404);
            echo '<h1>404</h1><p>Avaliação não encontrada.</p>';
            break;
        }

        $evidencias = EvaluationService::evidences($pdo, $evaluationId);
        require __DIR__ . '/../front/views/avaliacao_detalhe.php';
        break;

    case '/avaliacao/revisar':
        $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
        try {
            EvaluationService::submitHumanReview(
                $pdo,
                $evaluationId,
                (string) ($_POST['classification'] ?? ''),
                trim((string) ($_POST['justification'] ?? '')),
                (int) $currentUser['id']
            );
            AuditLogger::log($pdo, $currentUser['id'], 'human_review', 'evaluation', $evaluationId, [
                'classification' => $_POST['classification'] ?? '',
            ]);
            $_SESSION['flash'] = 'Revisão salva com sucesso.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: /avaliacao?id=' . $evaluationId);
        exit;

    case '/empresas':
        if ($method === 'POST') {
            try {
                $newCompanyId = CompanyService::create($pdo, [
                    'corporate_name' => trim((string) ($_POST['corporate_name'] ?? '')),
                    'trade_name' => trim((string) ($_POST['trade_name'] ?? '')),
                    'cnpj' => trim((string) ($_POST['cnpj'] ?? '')),
                    'email' => trim((string) ($_POST['email'] ?? '')),
                    'phone' => trim((string) ($_POST['phone'] ?? '')),
                    'responsible_name' => trim((string) ($_POST['responsible_name'] ?? '')),
                    'login_email' => trim((string) ($_POST['login_email'] ?? '')),
                    'login_password' => (string) ($_POST['login_password'] ?? ''),
                ]);
                AuditLogger::log($pdo, $currentUser['id'], 'create', 'company', $newCompanyId);
                $_SESSION['flash'] = 'Empresa cadastrada com sucesso.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }

            header('Location: /empresas');
            exit;
        }

        $empresas = CompanyService::list($pdo);
        require __DIR__ . '/../front/views/empresas.php';
        break;

    case '/empresas/remover':
        $id = (int) ($_POST['id'] ?? 0);
        try {
            CompanyService::delete($pdo, $id);
            AuditLogger::log($pdo, $currentUser['id'], 'delete', 'company', $id);
            $_SESSION['flash'] = 'Empresa excluída.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /empresas');
        exit;

    case '/auditoria':
        $selectedType = trim((string) ($_GET['entity_type'] ?? ''));
        $entityTypes = AuditLogger::entityTypes($pdo);
        $logs = AuditLogger::list($pdo, 200, $selectedType);
        require __DIR__ . '/../front/views/auditoria.php';
        break;

    case '/documento':
        $tipo = $_GET['tipo'] ?? '';
        $docId = (int) ($_GET['id'] ?? 0);
        $doc = null;

        if ($tipo === 'proposta') {
            $stmt = $pdo->prepare(
                'SELECT pd.*, p.company_id FROM proposal_documents pd
                 JOIN proposals p ON p.id = pd.proposal_id WHERE pd.id = :id'
            );
            $stmt->execute(['id' => $docId]);
            $doc = $stmt->fetch();

            if ($doc && !$isAdmin && (int) $doc['company_id'] !== (int) $companyId) {
                $doc = null;
            }
        } elseif ($tipo === 'licitacao') {
            $stmt = $pdo->prepare('SELECT * FROM licitation_documents WHERE id = :id');
            $stmt->execute(['id' => $docId]);
            $doc = $stmt->fetch();
        }

        if (!$doc || !is_file($doc['path'])) {
            http_response_code(404);
            echo '<h1>404</h1><p>Documento não encontrado.</p>';
            break;
        }

        AuditLogger::log($pdo, $currentUser['id'], 'view_document', $tipo === 'proposta' ? 'proposal_document' : 'licitation_document', $docId);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename(str_replace(['"', "\r", "\n"], '', $doc['original_name'])) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($doc['path']);
        exit;

    default:
        http_response_code(404);
        echo '<h1>404</h1><p>Página não encontrada.</p>';
        break;
}
