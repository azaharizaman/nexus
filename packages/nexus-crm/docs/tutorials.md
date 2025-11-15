# Nexus CRM Tutorials

## Level 1: Basic CRM Setup (10 minutes)

### Prerequisites
- Laravel 12+ application
- Nexus CRM package installed

### Step 1: Install and Configure

```bash
composer require nexus/crm
php artisan vendor:publish --provider="Nexus\Crm\CrmServiceProvider"
php artisan migrate
```

### Step 2: Add CRM to User Model

Edit `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Nexus\Crm\Traits\HasCrm;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasCrm;

    // ... existing code
}
```

### Step 3: Create Your First Contact

In a controller or route:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $contact = $user->createContact([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json($contact);
    }
}
```

### Step 4: Display Contacts

```php
public function index()
{
    $user = auth()->user();
    $contacts = $user->contacts;

    return view('contacts.index', compact('contacts'));
}
```

### Step 5: Update Contacts

```php
public function update(Request $request, $contactId)
{
    $user = auth()->user();
    $contact = $user->contacts()->findOrFail($contactId);

    $contact->update([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
    ]);

    return response()->json($contact);
}
```

### Verification

Visit your application and create/update contacts. You should see them persisted with the `HasCrm` trait.

## Level 2: Database-Driven CRM (20 minutes)

### Prerequisites
- Level 1 completed
- Database configured

### Step 1: Create CRM Definition

In a seeder or migration:

```php
<?php

namespace Database\Seeders;

use Nexus\Crm\Models\CrmDefinition;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    public function run()
    {
        CrmDefinition::create([
            'name' => 'Lead',
            'type' => 'lead',
            'schema' => [
                'first_name' => ['type' => 'string', 'required' => true],
                'last_name' => ['type' => 'string', 'required' => true],
                'email' => ['type' => 'string', 'required' => true],
                'company' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
                'budget' => ['type' => 'number'],
                'notes' => ['type' => 'text'],
            ],
        ]);
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=CrmSeeder
```

### Step 2: Create CRM Entities

```php
<?php

namespace App\Http\Controllers;

use Nexus\Crm\Actions\CreateEntity;
use Nexus\Crm\Models\CrmDefinition;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $definition = CrmDefinition::where('type', 'lead')->first();

        $entity = app(CreateEntity::class)->execute([
            'definition_id' => $definition->id,
            'data' => $request->only([
                'first_name', 'last_name', 'email', 'company', 'phone', 'budget', 'notes'
            ]),
        ]);

        return response()->json($entity);
    }
}
```

### Step 3: Display Entities

```php
public function index()
{
    $definition = CrmDefinition::where('type', 'lead')->first();
    $entities = $definition->entities()->with('assignments')->get();

    return view('leads.index', compact('entities'));
}
```

### Step 4: Assign Users

```php
use Nexus\Crm\Models\CrmEntity;

public function assign(Request $request, $entityId)
{
    $entity = CrmEntity::findOrFail($entityId);

    $entity->assignTo($request->user_id);

    return response()->json(['message' => 'Assigned successfully']);
}
```

### Step 5: Dashboard

```php
use Nexus\Crm\Services\CrmDashboard;

public function dashboard()
{
    $dashboard = app(CrmDashboard::class);
    $data = $dashboard->forUser(auth()->id());

    return view('dashboard', compact('data'));
}
```

### Verification

Create leads, assign them to users, and view the dashboard. Data should be stored in the CRM tables.

## Level 3: Pipeline Automation (30 minutes)

### Prerequisites
- Level 2 completed

### Step 1: Create Pipeline

```php
<?php

use Nexus\Crm\Models\CrmPipeline;
use Nexus\Crm\Models\CrmStage;

$pipeline = CrmPipeline::create([
    'name' => 'Sales Pipeline',
    'definition_id' => $definition->id,
]);

// Create stages
$prospect = CrmStage::create([
    'pipeline_id' => $pipeline->id,
    'name' => 'Prospect',
    'order' => 1,
    'entry_actions' => [],
    'exit_actions' => [],
]);

$qualified = CrmStage::create([
    'pipeline_id' => $pipeline->id,
    'name' => 'Qualified',
    'order' => 2,
    'entry_actions' => ['assign' => ['strategy' => 'round_robin']],
    'exit_actions' => [],
    'transition_conditions' => [
        [
            'field' => 'budget',
            'operator' => 'greater_than',
            'value' => 10000,
        ]
    ],
]);
```

### Step 2: Transition Entities

```php
<?php

use Nexus\Crm\Actions\TransitionEntity;
use Nexus\Crm\Models\CrmEntity;

public function transition(Request $request, $entityId)
{
    $entity = CrmEntity::findOrFail($entityId);

    try {
        app(TransitionEntity::class)->execute($entity, $request->stage_name);
        return response()->json(['message' => 'Transitioned successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

### Step 3: Add Integrations

Configure email integration:

```php
// In config/crm.php
'integrations' => [
    'email' => [
        'smtp_host' => env('MAIL_HOST'),
        'smtp_port' => env('MAIL_PORT'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],
],
```

Add integration to stage:

```php
$qualified->update([
    'entry_actions' => [
        'assign' => ['strategy' => 'round_robin'],
        'integration' => [
            'type' => 'email',
            'config' => [
                'to' => 'manager@example.com',
                'subject' => 'New qualified lead',
                'template' => 'Lead qualified: {{first_name}} {{last_name}}',
            ],
        ],
    ],
]);
```

### Step 4: Custom Assignment Strategy

Create custom strategy:

```php
<?php

use Nexus\Crm\Contracts\AssignmentStrategyContract;

class DepartmentAssignmentStrategy implements AssignmentStrategyContract
{
    public function resolve($entity, array $config = []): array
    {
        // Assign to sales department users
        return \App\Models\User::where('department', 'sales')->pluck('id')->toArray();
    }
}
```

Register in service provider:

```php
<?php

namespace App\Providers;

use Nexus\Crm\Core\AssignmentStrategyResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $resolver = app(AssignmentStrategyResolver::class);
        $resolver->registerStrategy('department', DepartmentAssignmentStrategy::class);
    }
}
```

### Verification

Create entities, transition them through stages, and observe assignments and integrations firing.