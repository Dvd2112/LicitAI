# ONBOARDING — SimplificaGov

**Plataforma de apoio à análise de licitações públicas — Human-in-the-Loop**

---

## 1. VISÃO GERAL

Este repositório implementa uma plataforma que:

1. Permite ao **órgão/gestor** cadastrar licitações (itens, unidades, prazos, documentos, requisitos), acompanhar propostas recebidas e validar a análise da IA.
2. Permite à **empresa/licitante** visualizar licitações abertas, enviar sua proposta com documentos em PDF e acompanhar o status do processamento.
3. **Extrai texto** dos PDFs (`pdftotext`, com caminho para OCR via Tesseract) e envia para a OpenAI apenas o texto já extraído — nunca o arquivo bruto.
4. **Classifica cada requisito** da licitação contra o texto da proposta em `ATENDE | ATENDE_PARCIALMENTE | NÃO_ATENDE | EVIDÊNCIA_INSUFICIENTE | NÃO_IDENTIFICADO | REQUER_REVISÃO`, sempre com justificativa e evidência textual.
5. **Calcula a aderência de cada empresa no backend** (PHP), nunca a partir da "opinião" do modelo — o modelo só classifica e cita evidência.
6. Exige **validação humana**: cada classificação pode ser confirmada ou alterada por um responsável, preservando sempre a classificação original da IA.
7. Registra toda ação relevante em **auditoria** (`audit_logs`).

**Stack:**

| Camada | Tecnologia |
|---|---|
| Backend | PHP puro 8.2+ (sem framework, sem Composer) |
| Frontend | PHP server-side + Bootstrap 5 (CDN) + design system próprio (`front/assets/app.css`) |
| IA | OpenAI API (`gpt-4o-mini`) |
| Extração de PDF | `pdftotext` nativo; Tesseract OCR opcional (fallback para PDFs escaneados) |
| Banco | MySQL / MariaDB |
| Fila | Tabela `jobs` + `scripts/worker.php` |

---

## 2. PRÉ-REQUISITOS DO AMBIENTE

| Ferramenta | Verificação |
|---|---|
| PHP 8.2+ com `pdo_mysql`, `curl`, `mbstring` | `php -v` / `php -m` |
| MySQL/MariaDB acessível | credenciais em `.env` |
| `pdftotext` (poppler ou xpdf) | `pdftotext -v` |
| Tesseract OCR (opcional, para PDFs escaneados) | `tesseract --version` |

No Windows, o caminho do `pdftotext` costuma não estar no PATH do processo do Apache — configure o caminho completo em `PDFTOTEXT_BIN` no `.env` (veja `.env.example`).

Este projeto **não usa Composer** — não há dependências de terceiros, só extensões nativas do PHP.

---

## 3. CONFIGURAÇÃO INICIAL

```bash
copy .env.example .env
```

Preencha no `.env`:

| Variável | Obrigatória? | Observação |
|---|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Sim | Seu MySQL/MariaDB local |
| `OPENAI_API_KEY` | Sim, para a Fase de análise | https://platform.openai.com — **nunca** commitar este valor |
| `OPENAI_MODEL` | Não | Padrão `gpt-4o-mini` |
| `PDFTOTEXT_BIN` | Recomendado no Windows | Caminho completo do executável |
| `TESSERACT_BIN` | Opcional | Só necessário para PDFs escaneados (sem camada de texto) |

```bash
php scripts/migrate.php    # cria o banco (se não existir) e aplica todas as migrations
php scripts/seed.php       # cria o admin e duas empresas de demonstração
```

O `.env` já está no `.gitignore` — nunca será commitado.

---

## 4. RODANDO O SISTEMA

O front controller é `public/index.php`. No XAMPP, o jeito mais simples é criar um vhost do Apache apontando `DocumentRoot` para essa pasta (já configurado neste ambiente em `http://localhost:8080`). Alternativa rápida sem Apache:

```bash
php -S localhost:8000 public/index.php
```

Para processar a fila de extração de documentos (pode rodar sob demanda ou agendado via cron/Task Scheduler):

```bash
php scripts/worker.php
```

O mesmo processamento também pode ser disparado manualmente pelo botão **"Processar fila"** na tela Propostas (perfil gestor).

---

## 5. FLUXO DE USO (DEMO DE PONTA A PONTA)

1. **Login como gestor**: `admin` / `admin123`.
2. **Cadastrar licitação**: menu Licitações → Nova licitação → preencha o wizard de 7 etapas (identificação, itens, locais, faturamento, prazos, documentos, revisão).
3. **Cadastrar requisitos**: abra a licitação criada e adicione requisitos estruturados (categoria, descrição, obrigatoriedade).
4. **Login como empresa**: `techsolutions` / `empresa123`.
5. **Enviar proposta**: menu Propostas → Registrar proposta → escolha a licitação aberta e anexe PDFs.
6. **Login como gestor novamente** e clique em **Processar fila** (extrai o texto dos PDFs enviados).
7. Abra a proposta e clique em **Analisar com IA** — a OpenAI classifica cada requisito com base apenas no texto extraído.
8. Veja o resultado em **Análise da licitação** (`/analise?licitation_id=...`): visão geral de aderência por empresa + matriz comparativa requisito × empresa.
9. Clique em uma célula da matriz para ver o detalhe da avaliação (evidência, confiança) e **confirmar ou alterar** a classificação como revisor humano.
10. Acompanhe tudo em **Auditoria** — toda ação crítica fica registrada.

---

## 6. COMO O CÓDIGO SE ORGANIZA

```
public/index.php          # roteador único (switch por path); toda regra de negócio delega para src/Services
src/Core/                 # Env (.env loader), Database (PDO singleton), Csrf
src/Services/             # AuthService, LicitationService, ProposalService, ExtractionService,
                           # EvaluationService, ScoreService, ComparisonService, ContractService,
                           # CompanyService, AuditLogger, UploadService
src/Providers/             # OpenAIProvider — único ponto de contato HTTP com a OpenAI
prompts/                   # texto dos prompts da IA, versionado fora do código PHP
front/views/                # uma tela por arquivo, sem duplicar HTML
front/assets/app.css        # design system: tokens --color-* centralizados no :root
database/migrations/        # uma tabela (ou grupo pequeno) por arquivo, numerado
```

**Regras do projeto:**

- Toda query é parametrizada (PDO, `ERRMODE_EXCEPTION`, sem `EMULATE_PREPARES`).
- Toda rota que muda estado (POST) exige token CSRF (`App\Core\Csrf`).
- Rotas administrativas são bloqueadas por perfil em `public/index.php` (`$adminOnlyPrefixes`).
- Uploads são validados por conteúdo real (`finfo`), não por extensão/MIME informado pelo cliente, e armazenados fora da pasta pública com nome gerado.
- A IA nunca decide vencedor nem calcula pontuação — só classifica com evidência; o placar é sempre calculado em `ScoreService`.
- Toda ação crítica (login, criação, exclusão, análise, revisão humana, visualização de documento) grava em `audit_logs` — nunca com segredos/API keys no `details`.

---

## 7. SOLUÇÃO DE PROBLEMAS COMUNS

| Sintoma | Causa provável | Correção |
|---|---|---|
| 403 em rota após login | Token CSRF ausente/expirado | Recarregue a página do formulário antes de enviar |
| Documento fica "Na fila" indefinidamente | `worker.php` não foi executado | `php scripts/worker.php` ou clique em "Processar fila" |
| Falha ao extrair PDF | `PDFTOTEXT_BIN` incorreto no `.env` | Confirme o caminho completo do executável |
| "PDF parece digitalizado... Tesseract não configurado" | PDF escaneado sem camada de texto e sem OCR configurado | Configure `TESSERACT_BIN` no `.env` (Tesseract precisa estar instalado) |
| Erro da API OpenAI / análise falha | `OPENAI_API_KEY` ausente, inválida ou sem crédito | Verifique a chave em https://platform.openai.com |
| "Access denied" ao rodar `migrate.php` | Credenciais erradas em `DB_*` no `.env` | Confirme usuário/senha do MySQL local |

---

*SimplificaGov — apoio técnico à análise de licitações, com validação humana sempre no controle final.*
