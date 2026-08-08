# SimplificaGov

**Plataforma de apoio à análise de licitações públicas** — comparação de propostas com evidências, validação humana obrigatória.

## O que é

Sistema que ajuda um órgão público a:

1. **Cadastrar licitações** (itens, unidades executoras, prazos, documentos, requisitos).
2. **Receber propostas** de empresas participantes, com upload de documentos em PDF.
3. **Extrair e analisar** o conteúdo dos documentos, comparando cada proposta com os requisitos da licitação.
4. **Apresentar aderência por empresa** com evidências (documento/página/trecho) — nunca uma decisão automática de vencedor.
5. **Registrar a validação humana** de cada classificação, com histórico completo em auditoria.

A IA nunca escolhe o vencedor. Ela classifica requisito por requisito com evidência textual; o backend calcula o placar; um responsável humano confirma ou corrige cada classificação.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP puro 8.2+ (sem framework, sem Composer) |
| Frontend | Telas PHP server-side + Bootstrap 5 (CDN) + CSS próprio (design tokens) |
| IA | OpenAI API (`gpt-4o-mini` por padrão) — único provedor, isolado em `src/Providers/OpenAIProvider.php` |
| Extração de PDF | `pdftotext` (camada de texto nativa); fallback para Tesseract OCR quando configurado |
| Banco | MySQL / MariaDB (XAMPP) |
| Fila | Tabela `jobs` no MySQL + `scripts/worker.php` (processamento sob demanda ou agendado) |

## Estrutura

```
├── public/               # front controller único (todas as rotas)
├── src/
│   ├── Core/              # Env, Database (PDO), Csrf
│   ├── Services/          # regra de negócio (Auth, Licitation, Proposal, Evaluation, Score, ...)
│   └── Providers/         # OpenAIProvider (único ponto de contato com a OpenAI)
├── database/migrations/   # SQL versionado, uma tabela por arquivo
├── prompts/                # prompts da IA em arquivos de texto, fora do código
├── front/
│   ├── views/              # telas PHP
│   └── assets/             # app.css (design system) + app.js
├── storage/uploads/        # documentos enviados (fora da pasta pública)
└── scripts/                # migrate.php, seed.php, worker.php
```

## Início rápido (Windows/XAMPP)

```bash
copy .env.example .env      # preencha DB_* e OPENAI_API_KEY
php scripts/migrate.php     # cria o banco e todas as tabelas
php scripts/seed.php        # cria o usuário admin e duas empresas de demonstração
```

O projeto já roda via Apache do XAMPP em `http://localhost:8080` (vhost apontando para `public/`). Para processar a fila de extração manualmente:

```bash
php scripts/worker.php
```

Também é possível clicar em **"Processar fila"** na tela Propostas (perfil gestor).

Login de demonstração: `admin` / `admin123` (gestor) e `techsolutions` / `empresa123` (empresa).

---

*Consulte [ONBOARDING.md](ONBOARDING.md) para o fluxo de ponta a ponta e solução de problemas.*
