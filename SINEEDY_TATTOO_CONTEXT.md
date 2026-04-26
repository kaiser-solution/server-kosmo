# Contexto: Sineedy Tattoo (Controle de Gastos)

Este documento descreve a relação entre o frontend `Sineedy_Tattoo_controle_de_gastos` (localizado na pasta superior) e esta API (`api-kosmo`).

## Informações Gerais
- **Multi-Aplicação:** O sistema agora suporta múltiplas aplicações por usuário. O namespace é selecionado dinamicamente após o login.
- **Frontend:** HTML/JS/CSS puro, localizado em `../Sineedy_Tattoo_controle_de_gastos/`.
- **Base URL da API:** Geralmente `http://localhost:8000/api` (desenvolvimento) ou `https://teste.kaisersolution.com.br/api`.

## Estrutura do Frontend
- `index.html`, `clientes.html`, etc.: Páginas localizadas na raiz.
- `css/`: Pasta contendo arquivos de estilo (`style.css`, `clientes.css`, `fornecedores.css`, `relatorio.css`, `disparos.css`, `login.css`).
- `js/`: Pasta contendo a lógica JavaScript (`index.js`, `clientes.js`, `fornecedores.js`, `relatorio.js`, `disparos.js`, `api.js`, `login.js`, `config.js`).
- `sw.js`: Service Worker localizado na raiz para garantir o escopo do PWA.
- `img/`: Pasta com ícones e imagens.

## Fluxo de Autenticação
1. **Login:** O usuário fornece e-mail e senha.
2. **Seleção de Aplicação:** Se o usuário possuir mais de uma aplicação associada aos seus planos, uma tela de escolha é exibida.
3. **Seleção de Perfil:** O usuário escolhe seu perfil de acesso.
4. **Dashboard:** O sistema carrega as configurações específicas da aplicação selecionada.

## Funcionalidades Principais (Frontend)
1. **Lançamentos:** Registro de Receitas (R) e Despesas (D).
2. **Relatórios:** Geração de relatórios visuais com gráficos e planilhas compactas.
3. **Contatos:** Gerenciamento de clientes e fornecedores.
4. **Configuração Dinâmica:** Cores e categorias mapeadas por tipo de registro (ex: "Aluguel", "Tatuagem", "Material").

## Integração com API
O frontend consome as seguintes rotas principais:
- `GET /{namespace}/config`: Carrega configurações do app.
- `GET /{namespace}/records/{typeSlug}`: Lista lançamentos.
- `POST /{namespace}/records/{typeSlug}`: Cria novos lançamentos.
- `GET /{namespace}/contacts`: Lista clientes/fornecedores.

## Pendências Identificadas (15/04/2026)
- Melhorar intuitividade nos lançamentos (sugerir tipo R/D baseado na tag).
- Remover nomes hardcoded ("Sineedy") e usar do config.
- Ordenação de lançamentos na tela "Todos os Lançamentos".
- Melhorias na impressão de relatórios (tamanho de cabeçalho, exibição de gráficos).
- Atualização em tempo real dos gráficos ao mudar o mês.
