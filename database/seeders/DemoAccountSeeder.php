<?php

namespace Database\Seeders;

use App\Models\ActivityEvent;
use App\Models\HandymanProfile;
use App\Models\HomeownerProfile;
use App\Models\Skill;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds fictional demonstration accounts (reserved example.test addresses only).
 * Existing accounts are matched on email and never duplicated; existing passwords are kept.
 */
class DemoAccountSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Demo-password-2026!';

    /**
     * @var list<array{first_name: string, last_name: string, username: string, email: string, phone: string, city: string, address: string, skills: list<string>, joined_days_ago: int}>
     */
    public const HANDYMEN = [
        ['first_name' => 'Youssef', 'last_name' => 'El Amrani', 'username' => 'youssef-plomberie', 'email' => 'youssef.elamrani@example.test', 'phone' => '+212661000101', 'city' => 'Casablanca', 'address' => 'Maârif, Rue Taha Houcine', 'skills' => ['Pipe fitting', 'Leak detection', 'Water heater installation'], 'joined_days_ago' => 160],
        ['first_name' => 'Khalid', 'last_name' => 'Bennani', 'username' => 'khalid-electro', 'email' => 'khalid.bennani@example.test', 'phone' => '+212661000102', 'city' => 'Casablanca', 'address' => 'Sidi Maarouf, Lotissement Al Houda', 'skills' => ['Electrical wiring', 'Lighting installation', 'Electrical panel maintenance'], 'joined_days_ago' => 150],
        ['first_name' => 'Rachid', 'last_name' => 'Tazi', 'username' => 'rachid-peinture', 'email' => 'rachid.tazi@example.test', 'phone' => '+212661000103', 'city' => 'Casablanca', 'address' => 'Hay Hassani, Boulevard Oued Daoura', 'skills' => ['Interior painting', 'Exterior painting', 'Wallpapering & decorative finishes'], 'joined_days_ago' => 140],
        ['first_name' => 'Hamza', 'last_name' => 'Ouazzani', 'username' => 'hamza-menuiserie', 'email' => 'hamza.ouazzani@example.test', 'phone' => '+212661000104', 'city' => 'Casablanca', 'address' => 'Bourgogne, Rue Ibnou Khalikane', 'skills' => ['Furniture assembly', 'Custom woodworking', 'Door & window fitting'], 'joined_days_ago' => 130],
        ['first_name' => 'Mehdi', 'last_name' => 'Chraibi', 'username' => 'mehdi-clim', 'email' => 'mehdi.chraibi@example.test', 'phone' => '+212661000105', 'city' => 'Casablanca', 'address' => 'Aïn Sebaâ, Route de Rabat', 'skills' => ['Air conditioning installation', 'HVAC maintenance', 'Electrical wiring'], 'joined_days_ago' => 120],
        ['first_name' => 'Samira', 'last_name' => 'Lahlou', 'username' => 'samira-clean', 'email' => 'samira.lahlou@example.test', 'phone' => '+212661000106', 'city' => 'Casablanca', 'address' => 'Gauthier, Rue Jean Jaurès', 'skills' => ['Deep cleaning', 'Post-renovation cleaning'], 'joined_days_ago' => 110],
        ['first_name' => 'Abdellah', 'last_name' => 'Fassi-Fihri', 'username' => 'abdellah-carrelage', 'email' => 'abdellah.fassifihri@example.test', 'phone' => '+212661000107', 'city' => 'Casablanca', 'address' => 'Oulfa, Boulevard Oum Rabii', 'skills' => ['Tiling', 'Plastering & masonry'], 'joined_days_ago' => 100],
        ['first_name' => 'Nadia', 'last_name' => 'Berrada', 'username' => 'nadia-jardins', 'email' => 'nadia.berrada@example.test', 'phone' => '+212661000108', 'city' => 'Rabat', 'address' => 'Agdal, Avenue de France', 'skills' => ['Garden maintenance', 'Irrigation systems'], 'joined_days_ago' => 90],
        ['first_name' => 'Omar', 'last_name' => 'Skalli', 'username' => 'omar-renov', 'email' => 'omar.skalli@example.test', 'phone' => '+212661000109', 'city' => 'Mohammedia', 'address' => 'Quartier El Alia', 'skills' => ['Pipe fitting', 'Tiling', 'Plastering & masonry'], 'joined_days_ago' => 80],
        ['first_name' => 'Imane', 'last_name' => 'Zerouali', 'username' => 'imane-deco', 'email' => 'imane.zerouali@example.test', 'phone' => '+212661000110', 'city' => 'Casablanca', 'address' => 'Racine, Rue Ahmed Charci', 'skills' => ['Interior painting', 'Wallpapering & decorative finishes', 'Deep cleaning'], 'joined_days_ago' => 60],
        ['first_name' => 'Driss', 'last_name' => 'El Idrissi', 'username' => 'driss-multiservices', 'email' => 'driss.elidrissi@example.test', 'phone' => '+212661000111', 'city' => 'Casablanca', 'address' => 'Californie, Rue des Orangers', 'skills' => ['Electrical wiring', 'Lighting installation', 'Air conditioning installation'], 'joined_days_ago' => 45],
        ['first_name' => 'Soufiane', 'last_name' => 'Benkirane', 'username' => 'soufiane-bois', 'email' => 'soufiane.benkirane@example.test', 'phone' => '+212661000112', 'city' => 'Marrakech', 'address' => 'Guéliz, Rue de la Liberté', 'skills' => ['Custom woodworking', 'Door & window fitting', 'Plastering & masonry'], 'joined_days_ago' => 20],
    ];

    /**
     * @var list<array{first_name: string, last_name: string, username: string, email: string, phone: string, city: string, address: string, joined_days_ago: int}>
     */
    public const HOMEOWNERS = [
        ['first_name' => 'Fatima Zahra', 'last_name' => 'Alaoui', 'username' => 'fz-alaoui', 'email' => 'fatimazahra.alaoui@example.test', 'phone' => '+212662000201', 'city' => 'Casablanca', 'address' => 'Anfa, Boulevard d\'Anfa', 'joined_days_ago' => 120],
        ['first_name' => 'Karim', 'last_name' => 'Benjelloun', 'username' => 'karim-benjelloun', 'email' => 'karim.benjelloun@example.test', 'phone' => '+212662000202', 'city' => 'Casablanca', 'address' => 'Aïn Diab, Corniche', 'joined_days_ago' => 110],
        ['first_name' => 'Leila', 'last_name' => 'Mansouri', 'username' => 'leila-mansouri', 'email' => 'leila.mansouri@example.test', 'phone' => '+212662000203', 'city' => 'Casablanca', 'address' => 'Maârif, Rue Abou Hanifa', 'joined_days_ago' => 95],
        ['first_name' => 'Amine', 'last_name' => 'Kettani', 'username' => 'amine-kettani', 'email' => 'amine.kettani@example.test', 'phone' => '+212662000204', 'city' => 'Casablanca', 'address' => 'Oasis, Rue des Pléiades', 'joined_days_ago' => 85],
        ['first_name' => 'Salma', 'last_name' => 'Bouazzaoui', 'username' => 'salma-bouazzaoui', 'email' => 'salma.bouazzaoui@example.test', 'phone' => '+212662000205', 'city' => 'Casablanca', 'address' => 'Palmier, Boulevard Abdelmoumen', 'joined_days_ago' => 70],
        ['first_name' => 'Yassine', 'last_name' => 'Haddad', 'username' => 'yassine-haddad', 'email' => 'yassine.haddad@example.test', 'phone' => '+212662000206', 'city' => 'Rabat', 'address' => 'Hay Riad, Avenue Annakhil', 'joined_days_ago' => 60],
        ['first_name' => 'Houda', 'last_name' => 'Cherkaoui', 'username' => 'houda-cherkaoui', 'email' => 'houda.cherkaoui@example.test', 'phone' => '+212662000207', 'city' => 'Mohammedia', 'address' => 'Quartier Le Parc', 'joined_days_ago' => 50],
        ['first_name' => 'Anas', 'last_name' => 'Moutawakil', 'username' => 'anas-moutawakil', 'email' => 'anas.moutawakil@example.test', 'phone' => '+212662000208', 'city' => 'Bouskoura', 'address' => 'Ville Verte, Résidence Les Jardins', 'joined_days_ago' => 35],
        ['first_name' => 'Zineb', 'last_name' => 'Sebti', 'username' => 'zineb-sebti', 'email' => 'zineb.sebti@example.test', 'phone' => '+212662000209', 'city' => 'Casablanca', 'address' => 'Belvédère, Rue de Strasbourg', 'joined_days_ago' => 21],
        ['first_name' => 'Tarik', 'last_name' => 'El Ghazi', 'username' => 'tarik-elghazi', 'email' => 'tarik.elghazi@example.test', 'phone' => '+212662000210', 'city' => 'Dar Bouazza', 'address' => 'Tamaris, Route d\'Azemmour', 'joined_days_ago' => 9],
    ];

    /**
     * @var array{first_name: string, last_name: string, username: string, email: string}
     */
    public const ADMINISTRATOR = [
        'first_name' => 'Amina',
        'last_name' => 'Rhazi',
        'username' => 'brein-admin',
        'email' => 'admin@example.test',
    ];

    public function run(): void
    {
        $password = Hash::make(self::DEMO_PASSWORD);
        $skills = Skill::query()->pluck('id', 'name');

        foreach (self::HANDYMEN as $definition) {
            $user = $this->upsertUser($definition, UserRole::Handyman, $password);

            $profile = HandymanProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['address' => $definition['address'], 'city' => $definition['city']],
            );

            $profile->skills()->syncWithoutDetaching(
                collect($definition['skills'])->map(fn (string $skill): int => $skills[$skill])->all(),
            );
        }

        foreach (self::HOMEOWNERS as $definition) {
            $user = $this->upsertUser($definition, UserRole::Homeowner, $password);

            HomeownerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['address' => $definition['address'], 'city' => $definition['city']],
            );
        }

        if (! User::query()->where('role', UserRole::Administrator)->exists()) {
            $this->upsertUser([...self::ADMINISTRATOR, 'phone' => null, 'joined_days_ago' => 200], UserRole::Administrator, $password);
        }
    }

    /**
     * @param  array{first_name: string, last_name: string, username: string, email: string, phone: ?string, joined_days_ago: int}  $definition
     */
    private function upsertUser(array $definition, UserRole $role, string $password): User
    {
        $user = User::query()->withTrashed()->firstOrNew(['email' => $definition['email']]);
        $joinedAt = now()->subDays($definition['joined_days_ago'])->setTime(9, 30);

        $user->forceFill([
            'role' => $role,
            'first_name' => $definition['first_name'],
            'last_name' => $definition['last_name'],
            'username' => $definition['username'],
            'phone_e164' => $definition['phone'],
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?? $joinedAt,
            'password' => $user->exists ? $user->password : $password,
            'created_at' => $user->exists ? $user->created_at : $joinedAt,
        ])->save();

        ActivityEvent::query()->firstOrCreate(
            ['action' => 'user.registered', 'subject_type' => 'user', 'subject_id' => $user->id],
            ['actor_user_id' => $user->id, 'metadata' => ['role' => $role->value], 'created_at' => $joinedAt],
        );

        return $user;
    }
}
