# Inovcorp CRM

Sistema de gestão de relacionamento com clientes (CRM) desenvolvido como projeto de estágio na Inovcorp.

## 🚀 Stack Tecnológica

- **Backend:** Laravel 12
- **Frontend:** Vue 3 + Inertia.js
- **UI:** ShadcnVue + Tailwind CSS
- **Base de dados:** MySQL
- **Drag & Drop:** Vue Draggable Plus
- **Calendário:** FullCalendar
- **IA:** Groq API (Llama 3.3 70B) com streaming
- **Email:** Logs
- **2FA:** Google Authenticator (pragmarx/google2fa-laravel)
- **Ambiente local:** Laravel Herd

## 📋 Funcionalidades

### Módulos Nucleares
- **Entidades** — gestão de empresas/organizações com pesquisa, filtros, listagem de pessoas associadas e histórico de negócios
- **Pessoas** — gestão de contactos individuais associados a entidades, com histórico de negócios e eventos
- **Negócios** — pipeline Kanban com drag-and-drop, filtros, valor por etapa e detalhe completo
- **Calendário** — vistas mensal/semanal/diária/lista, eventos associados a entidades/pessoas/negócios

### Funcionalidades Avançadas
- **Detalhe do Negócio** — cronologia completa, criação rápida de atividades, gestão de produtos e propostas
- **Envio de Propostas** — upload de ficheiro, envio por email com texto editável, registo no histórico
- **Follow-up Automático** — ciclo de emails de 2 em 2 dias no estado "Follow Up", com 10 templates rotativos, respeitando horário de trabalho
- **Estatísticas de Produtos** — relatórios de produtos no pipeline, filtráveis e exportáveis em CSV
- **Automações** — regras configuráveis para criação automática de atividades em negócios sem atividade
- **Formulários Públicos** — geração de leads via formulários incorporáveis (iFrame), com criação automática de entidades e negócios
- **AI Chat** — assistente comercial com streaming, consulta dados reais do CRM em linguagem natural
- **Agente Comercial AI** — sugestões proativas no Dashboard baseadas em análise de dados
- **Notificações** — sino com dropdown, atualização em tempo real
- **Logs de Auditoria** — registo detalhado de todas as ações (criação, edição, eliminação, login/logout)

### Segurança e UX
- Autenticação de dois fatores (2FA) com Google Authenticator
- Dark/Light mode com persistência
- Pesquisa global (⌘K / Ctrl+K)
- Políticas de acesso — cada utilizador apenas vê os seus próprios dados

## 🛠️ Instalação

```bash
# Clonar o repositório
git clone https://github.com/teu-username/inovcorp-crm.git
cd inovcorp-crm

# Instalar dependências
composer install
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar base de dados no .env
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=

# Correr migrations
php artisan migrate

# Compilar assets
npm run dev
```

## ⚙️ Configuração de Serviços

### Groq API (AI Chat)
```env
GROQ_API_KEY=gsk_...
```
Cria uma conta gratuita em [console.groq.com](https://console.groq.com)

### Resend (Emails)
```env
MAIL_MAILER=log  # modo de desenvolvimento — emails ficam em storage/logs/laravel.log
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=onboarding@resend.dev
```
Cria uma conta gratuita em [resend.com](https://resend.com)

### Queue (Follow-up automático)
```env
QUEUE_CONNECTION=database
```
```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

## 📦 Estrutura do Pipeline (Kanban)