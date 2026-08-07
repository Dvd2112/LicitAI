# LicitAI

**Sistema de Análise Técnica de Propostas em Licitações + Gestão de Contratos**
Conformidade Lei nº 14.133/2021 com IA (RAG) — Human-in-the-Loop

## O que é

Assistente analítico que:

1. **Analisa propostas licitatórias** contra os requisitos do Termo de Referência usando DeepSeek + busca híbrida (pgvector + FTS), classificando cada requisito em `ATENDE | NÃO ATENDE | FALTAM EVIDÊNCIAS | AMBIGUIDADE` — com validação humana obrigatória e rastreabilidade página/linha.
2. **Gerencia contratos**: entregas, pagamentos, % entregue × % pago, romaneio em PDF, prazos/aditivos, ocorrências e alertas.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP puro 8.2+ (sem framework) |
| Frontend | Telas PHP server-side + Bootstrap 5 (CDN) |
| IA | DeepSeek API (`deepseek-chat`) |
| Embeddings | Gemini API (tier gratuito) |
| Banco | PostgreSQL 16 + pgvector + FTS |
| PDF | Dompdf |
| Fila | Tabela `jobs` no Postgres + worker PHP (CLI) |

## Documentação

- **ONBOARDING.md** — instalação, setup e primeiros passos (comece por aqui)
- **relatorio-tecnico-mvp.md** — arquitetura completa, requisitos e roadmap (Revisão 3.0)

## Estrutura

```
├── public/               # front controller (rotas → Services)
├── src/
│   ├── Core/             # Router, Database (PDO), Config
│   ├── Services/         # regra de negócio
│   └── Providers/        # DeepSeek, embeddings, OCR
├── database/migrations/  # SQL versionado
├── front/views/          # telas PHP + Bootstrap 5
├── storage/              # uploads, extracted, reports, logs
└── scripts/              # migrate.php, worker.php
```

## Início rápido

```bash
cp .env.example .env       # preencha DB + DEEPSEEK_API_KEY + GEMINI_API_KEY
composer install
php scripts/migrate.php
php -S localhost:8000 -t public   # terminal 1
php scripts/worker.php            # terminal 2
```

Acesse http://localhost:8000 — detalhes no [ONBOARDING.md](ONBOARDING.md).

---

*Revisão 3.0 | Documento gerado automaticamente | 06/08/2026*
