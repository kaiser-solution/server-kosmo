# API Kosmo - Sineedy Tattoo (Controle de Gastos)

Este projeto é a API backend desenvolvida em Laravel 13 para o sistema de gerenciamento financeiro **Sineedy Tattoo**. A API foi projetada para ser multitenant, permitindo que diferentes usuários gerenciem suas próprias aplicações (namespaces) com configurações, categorias e registros independentes.

## 🚀 Como Funciona

A API atua como o núcleo de processamento para o frontend, oferecendo:
- **Multi-Aplicação:** Suporte a múltiplos namespaces por usuário.
- **Autenticação:** Gerenciamento de perfis e login de dispositivos via Laravel Fortify/Sanctum.
- **Configuração Dinâmica:** Cada aplicação possui suas próprias categorias de receitas/despesas e cores personalizadas.
- **Gestão Financeira:** Endpoints para listagem, criação e edição de registros (Receitas e Despesas).
- **Contatos:** Cadastro e gerenciamento de clientes e fornecedores.

---

## 🛠️ Instalação e Configuração (Docker)

Esta instalação pressupõe que você **não possui PHP ou NPM instalados na sua máquina local**, utilizando apenas o Docker para todo o ambiente.

### 1. Clonar o Repositório
```bash
git clone <url-do-repositorio>
cd api-kosmo
```

### 2. Configurar Variáveis de Ambiente
Copie o arquivo de exemplo para o arquivo oficial:
```bash
cp .env.example .env
```

### 3. Subir os Containers
Execute o comando para construir e iniciar os serviços:
```bash
docker compose up -d --build
```

### 4. Instalar Dependências do PHP (Composer)
Execute a instalação do Composer dentro do container:
```bash
docker compose exec app composer install
```

### 5. Instalar Dependências do Frontend (NPM)
Execute a instalação e o build dos assets dentro do container:
```bash
docker compose exec app npm install
docker compose exec app npm run build
```

### 6. Configurar o Banco de Dados (SQLite)
Crie o arquivo do banco de dados e gere a chave da aplicação:
```bash
docker compose exec app touch database/database.sqlite
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

---

## 🏃 Como Executar

Após a instalação, o projeto estará rodando automaticamente. Para iniciar ou parar os serviços futuramente, use:

- **Iniciar:** `docker compose up -d`
- **Parar:** `docker compose down`

A API estará disponível em: [http://localhost:8000](http://localhost:8000)

---

## 📂 Estrutura de Rotas Principais

As rotas são prefixadas com `/api`. A maioria das operações exige um `{namespace}` que identifica a aplicação do usuário.

- `POST /api/device-login`: Login de usuário.
- `GET /api/{namespace}/config`: Carrega as configurações da aplicação.
- `GET /api/{namespace}/records/{typeSlug}`: Lista registros (receitas/despesas).
- `GET /api/{namespace}/contacts`: Lista contatos (clientes/fornecedores).

---

## 🐳 Comandos Úteis do Docker

- **Acessar o terminal do container:**
  ```bash
  docker compose exec app sh
  ```
- **Ver logs em tempo real:**
  ```bash
  docker compose logs -f app
  ```
- **Executar comandos Artisan:**
  ```bash
  docker compose exec app php artisan <comando>
  ```
