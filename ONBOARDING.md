# ONBOARDING — LicitAI

**Sistema de Análise Técnica de Propostas em Licitações + Gestão de Contratos**
**Conformidade Lei nº 14.133/2021 com IA (RAG) — Human-in-the-Loop**

> Leia antes: [relatorio-tecnico-mvp.md](relatorio-tecnico-mvp.md) — arquitetura completa (Revisão 3.0)

---

## 1. VISÃO GERAL DO PROJETO

Este repositório implementa um **assistente analítico** que:

1. **Analisa propostas licitatórias** contra os requisitos do Termo de Referência (TR), usando DeepSeek + busca híbrida (pgvector + FTS), classificando cada requisito em `ATENDE | NÃO ATENDE | FALTAM EVIDÊNCIAS | AMBIGUIDADE` — sempre com validação humana obrigatória e rastreabilidade página/linha.
2. **Gerencia contratos** de forma independente: entregas, pagamentos, % entregue × % pago, romaneio em PDF, prazos/aditivos, ocorrências e alertas.

**Stack (Revisão 3.0 — simplificada):**

| Camada | Tecnologia |
|---|---|
| Backend | PHP puro 8.2+ (sem framework) |
| Frontend | Telas PHP server-side + Bootstrap 5 (CDN) |
| IA | DeepSeek API (`deepseek-chat`) |
| Embeddings | Gemini API (tier gratuito) |
| Banco | PostgreSQL 16 + pgvector + FTS |
| PDF | Dompdf |
| Fila | Tabela `jobs` no Postgres + worker PHP (CLI) |

---

## 2. PRÉ-REQUISITOS DO AMBIENTE

| Ferramenta | Versão mínima | Verificação |
|---|---|---|
| PHP (CLI + FPM/built-in) | 8.2 | `php -v` |
| Composer | 2.x | `composer --version` |
| PostgreSQL | 16 | `psql --version` |
| Extensão pgvector | 0.7+ | `SELECT extversion FROM pg_extension WHERE extname='vector';` |
| poppler-utils (`pdftotext`, `pdfinfo`) | — | `pdftotext -v` |
| Tesseract OCR (idioma `por`) | 4.x | `tesseract --list-langs` (deve listar `por`) |
| Git | — | `git --version` |
| cURL PHP ext | — | `php -m \| grep curl` |
| PDO PostgreSQL | — | `php -m \| grep pdo_pgsql` |

### 2.1 Instalação do ambiente — Debian/Ubuntu (passo a passo)

```bash
# 1. Atualize os repositórios
sudo apt update

# 2. PHP 8.2+ + extensões necessárias (curl p/ APIs, pdo_pgsql p/ o banco, mbstring/xml p/ Dompdf)
sudo apt install -y php-cli php-curl php-mbstring php-xml php-pgsql

# 3. Composer (gerenciador de dependências do PHP)
sudo apt install -y composer

# 4. PostgreSQL 16 + extensão vetorial pgvector (busca semântica)
sudo apt install -y postgresql postgresql-contrib postgresql-16-pgvector

# 5. poppler-utils (pdftotext — extração de PDFs digitais) + Tesseract (OCR de escaneados, idioma pt-BR)
sudo apt install -y poppler-utils tesseract-ocr tesseract-ocr-por

# 6. Git
sudo apt install -y git
```

> **Não achou `postgresql-16-pgvector`?** O pacote varia por distro/versão do Postgres. Alternativas:
> - Ubuntu 24.04+: o pacote pode se chamar `postgresql-16-vector`
> - Compile manualmente: consulte https://github.com/pgvector/pgvector#installation
> - Depois, valide: `psql -U postgres -d licitacao_ai -c "CREATE EXTENSION IF NOT EXISTS vector;"`

### 2.2 Instalação das dependências do repositório (PHP)

As bibliotecas do projeto (Dompdf, Guzzle, phpdotenv) são instaladas via Composer:

```bash
cd LicitAI
composer install        # lê o composer.json e baixa vendor/
```

> Sempre que alguém adicionar uma dependência nova no `composer.json`, rode `composer install` de novo (ou `composer update` para atualizar).

### 2.3 Instalação do banco

```bash
# Cria o banco e a extensão vetorial (uma vez)
psql -U postgres -c "CREATE DATABASE licitacao_ai;"
psql -U postgres -d licitacao_ai -c "CREATE EXTENSION IF NOT EXISTS vector;"

# Aplica as migrations versionadas (database/migrations/)
php scripts/migrate.php
```

### 2.4 Verificação final do ambiente

```bash
php -v                    # ≥ 8.2
composer --version        # 2.x
psql --version            # 16
pdftotext -v              # poppler instalado
tesseract --list-langs    # deve listar "por"
php -m | grep -E "curl|pdo_pgsql|mbstring"   # extensões presentes
```

---

## 3. CONFIGURAÇÃO INICIAL (PRIMEIRA VEZ)

### 3.1 Clonar e instalar dependências

```bash
git clone https://github.com/Dvd2112/LicitAI.git
cd LicitAI
composer install
```

### 3.2 Variáveis de ambiente

```bash
cp .env.example .env
nano .env   # preencha as chaves abaixo
```

Chaves necessárias:

| Variável | Obrigatória? | Onde conseguir |
|---|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Sim | Seu PostgreSQL local |
| `DEEPSEEK_API_KEY` | Sim | https://platform.deepseek.com — pague ~US$ 2 (custos por licitação ficam em centavos) |
| `GEMINI_API_KEY` | Sim | https://aistudio.google.com — tier gratuito (1.500 req/dia de embedding) |
| `APP_ENV`, `APP_DEBUG` | Opcional | `local` / `true` em dev |

### 3.3 Rodar o sistema (2 terminais)

```bash
# Terminal 1 — servidor de desenvolvimento
php -S localhost:8000 -t public

# Terminal 2 — worker (processa uploads, classificação e alertas)
php scripts/worker.php
```

Acesse http://localhost:8000

---

## 4. FLUXO DE USO (DEMO DE 5 MINUTOS)

1. **Upload**: na tela inicial, envie o PDF do TR + uma ou mais propostas (todos em PDF).
2. **Aguarde o processamento**: o worker extrai o texto (pdftotext → fallback Tesseract), faz chunking com página/linha e enfileira as etapas.
3. **Requisitos**: a tela da licitação lista os requisitos atômicos extraídos pelo DeepSeek; verifique os sinalizados com `ambiguity_flag`.
4. **Classificar**: clique em **"Classificar licitação"** — o pipeline roda busca híbrida + DeepSeek e gera `evaluation` por requisito × proposta.
5. **Validar (HITL)**: revise cada classificação no dashboard; **aprovar/rejeitar/marcar para revisão** — tudo vai para o `audit_log` com hash.
6. **Parecer**: gere o PDF consolidado (Dompdf) em `storage/reports/`.
7. **Contratos**: cadastre um contrato, lance entregas e pagamentos, acompanhe % entregue × % pago no dashboard e gere o **romaneio** em PDF.

---

## 5. COMO O CÓDIGO SE ORGANIZA

```
LicitAI/
├── public/               # front controller único (rotas GET/POST → Services)
├── src/
│   ├── Core/             # Router, Database (PDO), Config/Env
│   ├── Services/         # Regra de negócio (chamados direto das telas)
│   └── Providers/        # LlmProvider (DeepSeek), EmbeddingProvider, OcrProvider
├── database/migrations/  # SQL versionado (001_create_tables.sql, ...)
├── front/
│   ├── views/            # Telas PHP + Bootstrap 5 (CDN)
│   └── assets/           # CSS/JS custom (JS vanilla)
├── storage/              # uploads/ extracted/ reports/ logs/
├── scripts/              # migrate.php, worker.php
├── .env.example          # modelo de configuração
└── composer.json         # dompdf, guzzlehttp, phpdotenv
```

**Regras de ouro:**

- **Telas NUNCA acessam o banco direto** — sempre via `Services`.
- **Toda query SQL é parametrizada** (PDO prepared statements) e **filtra `tenant_id`**.
- **Nova tabela?** Crie um migration numerado (`003_...sql`) e rode `php scripts/migrate.php`.
- **Nova tela?** View em `front/views/` + rota no `public/index.php` chamando um `Service`.
- **Nunca** logue CPF, RG, CNPJ ou e-mails — passe pela `RedactionService` (LGPD).

---

## 6. GIT WORKFLOW

O repositório já existe no GitHub (https://github.com/Dvd2112/LicitAI). Primeiro commit do seu trabalho:

```bash
# .gitignore mínimo (crie o arquivo na raiz):
#   /vendor/
#   /.env
#   /storage/uploads/*
#   /storage/extracted/*
#   /storage/reports/*
#   /storage/logs/*
#   !/storage/uploads/.gitkeep
#   !/storage/extracted/.gitkeep
#   !/storage/reports/.gitkeep
#   !/storage/logs/.gitkeep

git add .
git commit -m "chore: bootstrap do projeto LicitAI (Rev 3.0)"
git push -u origin main
```

Convenção de commits: `feat:`, `fix:`, `chore:`, `docs:`, `refactor:` — em inglês ou português, mas **um padrão só**.

Fluxo de trabalho: nunca commitar direto na `main` — crie `feature/xyz`, faça PR (pode usar merge local sem GitHub se preferir) e só então merge.

---

## 7. SOLUÇÃO DE PROBLEMAS COMUNS

| Sintoma | Causa provável | Correção |
|---|---|---|
| `Undefined constant / 404` em rotas | Router não encontrou a rota | Verifique o padrão de rota em `public/index.php` |
| Upload "pendente" para sempre | Worker não está rodando | `php scripts/worker.php` no Terminal 2; veja `storage/logs/` |
| `ERROR: extension "vector" is not available` | pgvector não instalado | `apt install postgresql-16-pgvector` e recrie a extensão |
| `tesseract: command not found` | poppler/tesseract ausentes | `sudo apt install -y poppler-utils tesseract-ocr tesseract-ocr-por` |
| Classificação sai vazia / erro 401 | `DEEPSEEK_API_KEY` inválida ou sem saldo | Renove a chave / adicione créditos na plataforma DeepSeek |
| Embeddings falham | `GEMINI_API_KEY` inválida ou limite diário | Cheque o tier gratuito; o sistema cai para OpenAI se configurado |
| Página demora e trava | Pipeline rodando síncrono | Deve rodar via worker/fila — confira `jobs` no banco |
| PDF sem acentos | Fonte do Dompdf | Use fonte DejaVu Sans no template do relatório |

---

## 8. INSTALAR O OPENCODE (ASSISTENTE DE IA NO TERMINAL)

O OpenCode é o agente de IA usado para desenvolver este repositório (editar código, rodar comandos, testar). Opcional, mas recomendado.

### 8.1 Instalação

**Linux/macOS — script oficial (recomendado):**

```bash
curl -fsSL https://opencode.ai/install | bash
```

**Alternativas:**

```bash
# Via Node.js (npm/bun/pnpm/yarn)
npm install -g opencode-ai
bun install -g opencode-ai
pnpm install -g opencode-ai
yarn global add opencode-ai

# Via Homebrew (Linux/macOS)
brew install anomalyco/tap/opencode

# Arch Linux
sudo pacman -S opencode            # estável
paru -S opencode-bin               # último release (AUR)

# Windows — use WSL (recomendado) e o script curl acima;
# ou choco install opencode / scoop install opencode
```

Verifique: `opencode --version`

### 8.2 Primeiro uso

```bash
cd LicitAI
opencode            # abre o agente no terminal (terminal moderno: WezTerm/Alacritty/Ghostty/Kitty)
```

Dentro do OpenCode:

1. **Conectar um provedor de IA**: digite `/connect` → escolha `opencode` (OpenCode Zen, o mais simples — paga conforme usa) ou qualquer outro provedor (OpenAI, Anthropic, DeepSeek, etc.) e cole a API key.
2. **Inicializar o projeto**: digite `/init` — o agente analisa o código e cria um `AGENTS.md` na raiz (commit esse arquivo; ele ensina o agente as regras do projeto).

### 8.3 Comandos úteis no dia a dia

| Comando | Função |
|---|---|
| `Tab` | Alterna entre **Plan mode** (só planeja, não altera) e Build mode (aplica mudanças) |
| `/undo` / `/redo` | Desfaz/refaz as últimas alterações feitas pelo agente |
| `/share` | Gera link compartilhável da conversa atual |
| `@arquivo.php` | Referencia um arquivo específico na pergunta |
| `/init` | Cria/atualiza o `AGENTS.md` do projeto |

Exemplo de uso: `me ajuda a criar o migration 003 para a tabela de aditivos, seguindo o padrão de @database/migrations/002_exemplo.sql`

---

## 9. CHECKLIST DE ONBOARDING (CONCLUÍDO QUANDO...)

- [ ] `php -v` ≥ 8.2 e extensões `curl`, `pdo_pgsql`, `mbstring` presentes
- [ ] PostgreSQL rodando com extensão `vector` criada
- [ ] `composer install` sem erros
- [ ] `.env` preenchido com banco + chaves DeepSeek e Gemini
- [ ] `php scripts/migrate.php` aplica todas as migrations sem erro
- [ ] `php -S localhost:8000 -t public` abre a home (Bootstrap carregado)
- [ ] Worker rodando e processando um upload de teste (PDF de 1 página)
- [ ] Upload de TR + proposta → requisitos extraídos → classificação gerada
- [ ] Validação humana gravada no `audit_log` com hash
- [ ] Relatório PDF e romaneio gerados em `storage/reports/`
- [ ] (Opcional) OpenCode instalado e `/init` executado no repo

---

## 10. REFERÊNCIAS

- Relatório técnico completo (arquitetura, requisitos, roadmap): `relatorio-tecnico-mvp.md`
- Lei nº 14.133/2021: https://www.planalto.gov.br/ccivil_03/_ato2019-2022/2021/lei/l14133.htm
- DeepSeek API docs: https://api-docs.deepseek.com
- Gemini API (embeddings free tier): https://ai.google.dev
- pgvector: https://github.com/pgvector/pgvector
- Dompdf: https://github.com/dompdf/dompdf
- OpenCode (docs): https://opencode.ai/docs

---

*Documento gerado automaticamente | 06/08/2026 | Revisão 3.0*
