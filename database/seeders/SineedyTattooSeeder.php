<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use App\Models\Application;
use App\Models\Contact;
use App\Models\Record;
use App\Models\RecordType;
use App\Models\UserProfile;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SineedyTattooSeeder extends Seeder
{
    public function run(): void
    {
        $application = Application::updateOrCreate(
            ['namespace' => 'sineedy-tattoo'],
            [
                'name' => 'Sineedy Tattoo',
                'description' => 'Controle de gastos e receitas do estúdio Sineedy Tattoo.',
                'endpoint' => '/api/sineedy-tattoo',
            ]
        );

        $defaultCategories = [
            ['name' => 'Assinaturas',    'color' => '#6366f1'],
            ['name' => 'Cartões',        'color' => '#ec4899'],
            ['name' => 'Casa',           'color' => '#f59e0b'],
            ['name' => 'Comunicação',    'color' => '#06b6d4'],
            ['name' => 'Manutenção',     'color' => '#64748b'],
            ['name' => 'Material Tattoo', 'color' => '#0ea5e9'],
            ['name' => 'MEI / Impostos', 'color' => '#ef4444'],
            ['name' => 'Saúde',          'color' => '#10b981'],
            ['name' => 'Segurança',      'color' => '#d97706'],
            ['name' => 'Terreno',        'color' => '#78716c'],
            ['name' => 'Transporte',     'color' => '#3b82f6'],
            ['name' => 'Outros',         'color' => '#718096'],
        ];

        AppConfig::updateOrCreate(
            ['application_id' => $application->id],
            [
                'display_name' => 'Sineedy Tattoo',
                'primary_color' => '#3182ce',
                'secondary_color' => '#2d3748',
                'default_currency' => 'BRL',
                'categories' => json_encode($defaultCategories),
            ]
        );

        $recordTypes = [
            [
                'name' => 'Lançamento',
                'slug' => 'transaction',
                'description' => 'Receitas e despesas avulsas do estúdio (tatuagens, piercings, materiais, etc.).',
                'schema' => [
                    'type' => 'object',
                    'required' => ['name', 'type', 'amount'],
                    'properties' => [
                        'name' => ['type' => 'string',  'description' => 'Nome da instituição/item'],
                        'type' => ['type' => 'string',  'enum' => ['income', 'expense'], 'description' => 'Tipo do lançamento'],
                        'amount' => ['type' => 'number',  'description' => 'Valor em BRL'],
                        'category' => ['type' => 'string',  'description' => 'Categoria (Casa, Estúdio, Retiradas, etc.)'],
                        'description' => ['type' => 'string',  'description' => 'Observação opcional'],
                        'occurred_at' => ['type' => 'string',  'format' => 'date', 'description' => 'Data do lançamento'],
                    ],
                    'x-institutions' => [
                        ['name' => 'Tatuagem',             'defaultVal' => 600.00,  'type' => 'income',  'category' => 'Estúdio'],
                        ['name' => 'Sinal Tattoo',         'defaultVal' => 100.00,  'type' => 'income',  'category' => 'Estúdio'],
                        ['name' => 'Piercing',             'defaultVal' => 50.00,   'type' => 'income',  'category' => 'Estúdio'],
                        ['name' => 'Padaria',              'defaultVal' => null,    'type' => 'expense', 'category' => 'Casa'],
                        ['name' => 'Gasolina',             'defaultVal' => null,    'type' => 'expense', 'category' => 'Estúdio'],
                        ['name' => 'Sineedy Tattoo',       'defaultVal' => null,    'type' => 'expense', 'category' => 'Estúdio'],
                        ['name' => 'Dani',                 'defaultVal' => null,    'type' => 'expense', 'category' => 'Retiradas'],
                        ['name' => 'Denis',                'defaultVal' => null,    'type' => 'expense', 'category' => 'Retiradas'],
                        ['name' => 'Erika',                'defaultVal' => null,    'type' => 'expense', 'category' => 'Retiradas'],
                        ['name' => 'Água Galão',           'defaultVal' => 15.00,   'type' => 'expense', 'category' => 'Estúdio'],
                        ['name' => 'Mercado',              'defaultVal' => null,    'type' => 'expense', 'category' => 'Casa'],
                        ['name' => 'Estacionamento',       'defaultVal' => null,    'type' => 'expense', 'category' => 'Estúdio'],
                        ['name' => 'Material',             'defaultVal' => null,    'type' => 'expense', 'category' => 'Estúdio'],
                        ['name' => 'Farmácia',             'defaultVal' => null,    'type' => 'expense', 'category' => 'Saúde'],
                        ['name' => 'Restaurante / Lanche', 'defaultVal' => null,    'type' => 'expense', 'category' => 'Estúdio'],
                    ],
                ],
            ],
            [
                'name' => 'Conta Fixa',
                'slug' => 'recurring-bill',
                'description' => 'Contas recorrentes mensais (aluguel, energia, internet, etc.).',
                'schema' => [
                    'type' => 'object',
                    'required' => ['name', 'amount'],
                    'properties' => [
                        'name' => ['type' => 'string',  'description' => 'Nome da conta'],
                        'amount' => ['type' => 'number',  'description' => 'Valor pago em BRL'],
                        'default_amount' => ['type' => 'number',  'description' => 'Valor padrão esperado'],
                        'due_day' => ['type' => 'integer', 'description' => 'Dia de vencimento no mês'],
                        'category' => ['type' => 'string',  'description' => 'Categoria'],
                        'paid' => ['type' => 'boolean', 'description' => 'Se foi pago'],
                        'paid_at' => ['type' => 'string',  'format' => 'date', 'description' => 'Data do pagamento'],
                        'reference_month' => ['type' => 'string',  'description' => 'Mês de referência (YYYY-MM)'],
                    ],
                    'x-institutions' => [
                        ['name' => 'Aluguel Casa',             'defaultVal' => 1800.00, 'dueDay' => 15,  'category' => 'Casa'],
                        ['name' => 'Energia Elétrica Casa',    'defaultVal' => 350.00,  'dueDay' => 23,  'category' => 'Casa'],
                        ['name' => 'Água e Esgoto Casa',       'defaultVal' => 250.00,  'dueDay' => 13,  'category' => 'Casa'],
                        ['name' => 'Internet Fibra Casa',      'defaultVal' => 125.00,  'dueDay' => 10,  'category' => 'Casa'],
                        ['name' => 'Aluguel Estudio',          'defaultVal' => 1700.00, 'dueDay' => 5,   'category' => 'Estúdio'],
                        ['name' => 'Água e Luz Estudio',       'defaultVal' => 550.00,  'dueDay' => 5,   'category' => 'Estúdio'],
                        ['name' => 'Internet Fibra Estudio',   'defaultVal' => 125.00,  'dueDay' => 30,  'category' => 'Estúdio'],
                        ['name' => 'Plano de Celular',         'defaultVal' => 65.00,   'dueDay' => 20,  'category' => 'Comunicação'],
                        ['name' => 'Alarme Casa e Estudio',    'defaultVal' => 200.00,  'dueDay' => 15,  'category' => 'Segurança'],
                        ['name' => 'Parcela Terreno',          'defaultVal' => 2940.00, 'dueDay' => 28,  'category' => 'Terreno'],
                        ['name' => 'Parcela Carro',            'defaultVal' => 1000.00, 'dueDay' => 10,  'category' => 'Transporte'],
                        ['name' => 'Convênio Médico',          'defaultVal' => 76.00,   'dueDay' => 20,  'category' => 'Saúde'],
                        ['name' => 'Serviços de Software',     'defaultVal' => 100.00,  'dueDay' => 20,  'category' => 'Assinaturas'],
                        ['name' => 'Seguro de Vida',           'defaultVal' => 105.00,  'dueDay' => 20,  'category' => 'Saúde'],
                        ['name' => 'Cartão Denis',             'defaultVal' => 1500.00, 'dueDay' => 10,  'category' => 'Cartões'],
                        ['name' => 'Cartão Roldão',            'defaultVal' => 1000.00, 'dueDay' => 20,  'category' => 'Cartões'],
                        ['name' => 'Telefone Dani',            'defaultVal' => 44.00,   'dueDay' => 10,  'category' => 'Comunicação'],
                        ['name' => 'Telefone Denis',           'defaultVal' => 44.00,   'dueDay' => 10,  'category' => 'Comunicação'],
                        ['name' => 'Telefone Erika',           'defaultVal' => 39.00,   'dueDay' => 26,  'category' => 'Comunicação'],
                        ['name' => 'Assinatura Netflix',       'defaultVal' => 60.00,   'dueDay' => 10,  'category' => 'Assinaturas'],
                        ['name' => 'MEI Negociação Atrasados', 'defaultVal' => 110.00,  'dueDay' => 30,  'category' => 'MEI / Impostos'],
                        ['name' => 'MEI',                      'defaultVal' => 86.00,   'dueDay' => 20,  'category' => 'MEI / Impostos'],
                        ['name' => 'Clube de Assinaturas',     'defaultVal' => 200.00,  'dueDay' => 10,  'category' => 'Assinaturas'],
                    ],
                ],
            ],
        ];

        foreach ($recordTypes as $typeData) {
            RecordType::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'slug' => $typeData['slug'],
                ],
                [
                    'name' => $typeData['name'],
                    'description' => $typeData['description'],
                    'schema' => $typeData['schema'],
                    'active' => true,
                ]
            );
        }

        // Cadastra as contas recorrentes como registros do mês atual
        $recurringType = RecordType::where('application_id', $application->id)
            ->where('slug', 'recurring-bill')
            ->first();

        if ($recurringType) {
            $currentMonth = now()->format('Y-m');
            $institutions = $recurringType->schema['x-institutions'] ?? [];

            foreach ($institutions as $bill) {
                $dueDay = $bill['dueDay'] ?? 1;
                $dueDate = now()->startOfMonth()->addDays($dueDay - 1)->toDateString();

                Record::updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'record_type_id' => $recurringType->id,
                        'occurred_at' => $dueDate,
                        'payload->name' => $bill['name'],
                        'payload->reference_month' => $currentMonth,
                    ],
                    [
                        'payload' => [
                            'name' => $bill['name'],
                            'defaultVal' => $bill['defaultVal'] ?? null,
                            'dueDay' => $dueDay,
                            'category' => $bill['category'] ?? null,
                            'paid' => false,
                            'reference_month' => $currentMonth,
                        ],
                    ]
                );
            }

            $this->command->info('   Contas recorrentes cadastradas: '.count($institutions)." para {$currentMonth}");
        }

        // Cadastra fornecedores
        $suppliers = [
            ['category' => 'Material Tattoo', 'name' => 'Electric Ink',    'phone' => '5511999999999', 'email' => 'vendas@electricink.com.br', 'site' => 'https://www.electricink.com.br', 'notes' => 'Tintas e pigmentos'],
            ['category' => 'Material Tattoo', 'name' => 'Amazon Ink',      'phone' => '5511988888888', 'notes' => 'Agulhas e cartuchos'],
            ['category' => 'Descartáveis',    'name' => 'Descarpack',      'phone' => '5511977777777', 'notes' => 'Luvas, copos, barreiras'],
            ['category' => 'Manutenção',      'name' => 'Eletricista João', 'phone' => '5511966666666', 'notes' => 'Manutenção elétrica do estúdio'],
            ['category' => 'Gráfica',         'name' => 'Print Express',   'phone' => '5511955555555', 'phone2' => '551133334444', 'email' => 'contato@printexpress.com.br', 'site' => 'https://www.printexpress.com.br', 'notes' => 'Cartões de visita, adesivos, banners'],
        ];

        foreach ($suppliers as $s) {
            Contact::updateOrCreate(
                ['application_id' => $application->id, 'type' => 'supplier', 'name' => $s['name']],
                [
                    'phone' => $s['phone'] ?? null,
                    'phone2' => $s['phone2'] ?? null,
                    'email' => $s['email'] ?? null,
                    'category' => $s['category'] ?? null,
                    'active' => true,
                    'payload' => array_filter([
                        'site' => $s['site'] ?? null,
                        'notes' => $s['notes'] ?? null,
                        'contact' => $s['contact'] ?? null,
                    ]),
                ]
            );
        }


        $user = User::factory()->create([
            'name' => 'Sidney',
            'email' => 'rip_sined@hotmail.com',
            'password' => Hash::make('123456789'),
        ]);

        $plan = Plan::updateOrCreate(
            ['name' => 'premium', 'application_id' => $application->id],
            [
                'description' => 'Plano Premium do Sineedy Tattoo',
                'price' => 0,
                'currency' => 'BRL',
            ]
        );

        $user->plans()->syncWithoutDetaching([$plan->id]);

        UserProfile::create([
            'user_id' => $user->id,
            'name' => 'Denis',
            'avatar' => null,
            'pin' => null,
        ]);



        $this->command->info('   Fornecedores cadastrados: '.count($suppliers));
        $this->command->info('✅ Sineedy Tattoo seed concluída com sucesso!');
        $this->command->info("   Application: {$application->name} (namespace: {$application->namespace})");
        $this->command->info('   RecordTypes: transaction, recurring-bill');
    }
}
